<?php
/**
 * Plugin Name: BGCK Cache Warmer
 * Description: Keeps the LiteSpeed page cache warm. Every 10 minutes it re-checks the key pages and a few product pages, so visitors get cached copies instead of waiting for WordPress to build each page.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['bgck_10min'] = array( 'interval' => 600, 'display' => 'Every 10 minutes' );
	return $schedules;
} );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'bgck_warm_cache' ) ) {
		wp_schedule_event( time() + 120, 'bgck_10min', 'bgck_warm_cache' );
	}
} );

add_action( 'bgck_warm_cache', 'bgck_warm_cache_run' );

/** Pages checked on every run (cheap when already cached). */
function bgck_warm_cache_key_urls() {
	$urls = array( home_url( '/' ) );
	foreach ( array( 209, 211, 213, 215 ) as $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			$urls[] = $url;
		}
	}
	return $urls;
}

/** Products and categories, warmed a few at a time in rotation. */
function bgck_warm_cache_rotating_urls() {
	$urls = array();
	$ids  = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => -1,
		'fields'      => 'ids',
		'orderby'     => 'ID',
		'order'       => 'ASC',
	) );
	foreach ( $ids as $id ) {
		$urls[] = get_permalink( $id );
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$urls[] = $link;
			}
		}
	}
	return array_values( array_unique( array_filter( $urls ) ) );
}

function bgck_warm_cache_run() {
	if ( get_transient( 'bgck_warm_cache_lock' ) ) {
		return;
	}
	set_transient( 'bgck_warm_cache_lock', 1, 300 );

	$agents = array( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36 BGCK-Warmer' );
	if ( get_option( 'litespeed.conf.cache-mobile' ) ) {
		$agents[] = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36 BGCK-Warmer';
	}

	$rotating = bgck_warm_cache_rotating_urls();
	$offset   = (int) get_option( 'bgck_warm_cache_offset', 0 );
	if ( $offset >= count( $rotating ) ) {
		$offset = 0;
	}
	$batch = array_slice( $rotating, $offset, 4 );
	update_option( 'bgck_warm_cache_offset', $offset + 4, false );

	$urls    = array_merge( bgck_warm_cache_key_urls(), $batch );
	$started = microtime( true );
	$misses  = 0;

	foreach ( $urls as $url ) {
		foreach ( $agents as $agent ) {
			// Stay well inside PHP's time limit and go easy on the shared server.
			if ( microtime( true ) - $started > 40 || $misses >= 6 ) {
				break 2;
			}
			$response = wp_remote_get( $url, array(
				'timeout'    => 20,
				'sslverify'  => false,
				'user-agent' => $agent,
				'headers'    => array( 'Accept' => 'text/html' ),
			) );
			$state = is_wp_error( $response ) ? 'error' : (string) wp_remote_retrieve_header( $response, 'x-litespeed-cache' );
			if ( 'hit' !== $state ) {
				$misses++;
				sleep( 2 );
			}
		}
	}

	update_option( 'bgck_warm_cache_last', array( 'time' => time(), 'misses' => $misses, 'seconds' => round( microtime( true ) - $started, 1 ) ), false );
	delete_transient( 'bgck_warm_cache_lock' );
}
