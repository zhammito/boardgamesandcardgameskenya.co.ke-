<?php
/**
 * Plugin Name: BGCK Image Optimizer
 * Description: Shrinks every photo uploaded to the Media Library before WordPress stores it: phone rotation fixed, camera data removed, longest edge at most 1600px, saved as WebP under 400 KB. PNGs with transparency stay PNG (resized only).
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BGCK_IMG_MAX_EDGE  = 1600;
const BGCK_IMG_MAX_BYTES = 409600; // 400 KB

add_filter( 'wp_handle_upload', 'bgck_optimize_upload', 10, 2 );

function bgck_png_has_transparency( Imagick $im ) {
	if ( ! $im->getImageAlphaChannel() ) {
		return false;
	}
	$range = $im->getImageChannelRange( Imagick::CHANNEL_ALPHA );
	return isset( $range['minima'] ) && $range['minima'] < Imagick::getQuantum();
}

function bgck_optimize_upload( $upload, $context = 'upload' ) {
	if ( ! empty( $upload['error'] ) || ! class_exists( 'Imagick' ) ) {
		return $upload;
	}
	if ( ! in_array( $upload['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return $upload;
	}

	try {
		$im = new Imagick( $upload['file'] );
		if ( $im->getNumberImages() > 1 ) {
			return $upload; // Leave animated images alone.
		}

		$im->autoOrient();
		$resized = false;
		if ( max( $im->getImageWidth(), $im->getImageHeight() ) > BGCK_IMG_MAX_EDGE ) {
			$im->resizeImage( BGCK_IMG_MAX_EDGE, BGCK_IMG_MAX_EDGE, Imagick::FILTER_LANCZOS, 1, true );
			$resized = true;
		}
		$im->stripImage();

		// Transparent PNGs (logos, icons) keep their format.
		if ( 'image/png' === $upload['type'] && bgck_png_has_transparency( $im ) ) {
			if ( $resized ) {
				$im->writeImage( $upload['file'] );
			}
			$im->clear();
			return $upload;
		}

		// Already a small WebP within limits: nothing to do.
		if ( 'image/webp' === $upload['type'] && ! $resized && filesize( $upload['file'] ) <= BGCK_IMG_MAX_BYTES ) {
			$im->clear();
			return $upload;
		}

		$im->setImageFormat( 'webp' );
		$blob = '';
		for ( $attempt = 0; $attempt < 4; $attempt++ ) {
			foreach ( array( 82, 74, 66, 58, 50 ) as $quality ) {
				$im->setImageCompressionQuality( $quality );
				$blob = $im->getImageBlob();
				if ( strlen( $blob ) <= BGCK_IMG_MAX_BYTES ) {
					break 2;
				}
			}
			// Still too big at the lowest quality: shrink a little and try again.
			$im->resizeImage( (int) ( $im->getImageWidth() * 0.85 ), (int) ( $im->getImageHeight() * 0.85 ), Imagick::FILTER_LANCZOS, 1 );
		}
		$im->clear();
		if ( '' === $blob || strlen( $blob ) > BGCK_IMG_MAX_BYTES ) {
			return $upload;
		}

		$info = pathinfo( $upload['file'] );
		$name = wp_unique_filename( $info['dirname'], $info['filename'] . '.webp' );
		$path = $info['dirname'] . '/' . $name;
		if ( false === file_put_contents( $path, $blob ) ) {
			return $upload;
		}
		if ( $path !== $upload['file'] ) {
			wp_delete_file( $upload['file'] );
		}

		$upload['file'] = $path;
		$upload['url']  = trailingslashit( dirname( $upload['url'] ) ) . $name;
		$upload['type'] = 'image/webp';
	} catch ( Exception $e ) {
		return $upload;
	}

	return $upload;
}
