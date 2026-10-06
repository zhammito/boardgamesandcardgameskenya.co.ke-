<?php
/**
 * Plugin Name: BGCK Card Short Text
 * Description: Keeps the line under the name on product cards (Elementor "Product Short Description" tag) to about two lines: first sentence, cut at a word near 80 characters. Product pages keep the full short description.
 * Version: 1.2
 */

defined( 'ABSPATH' ) || exit;

const BGCK_CARD_TEXT_MAX = 80;

add_filter( 'woocommerce_product_get_short_description', function ( $text, $product ) {
	if ( '' === trim( (string) $text ) ) {
		return $text;
	}

	$from_card_tag = false;
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ) as $frame ) {
		if ( isset( $frame['class'] ) && 'ElementorPro\Modules\Woocommerce\Tags\Product_Short_Description' === $frame['class'] ) {
			$from_card_tag = true;
			break;
		}
	}
	if ( ! $from_card_tag ) {
		return $text;
	}

	$plain = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );

	// A line that only repeats the product name adds nothing to the card.
	if ( 0 === strcasecmp( rtrim( $plain, '.!' ), trim( html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ) ) ) ) {
		return '';
	}

	// Quote marks around a tagline ("Pick your tiles.") look stray once the sentence is cut, and emojis crowd a two-line card.
	$plain = str_replace( array( '“', '”', '"' ), '', $plain );
	$plain = trim( preg_replace( array( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{20E3}]/u', '/\s+/u' ), array( '', ' ' ), $plain ) );

	// Whole sentences while they fit, so a very short first one ("30 seconds.") gets company.
	$sentences = preg_split( '/(?<=[.!?])\s+/u', $plain );
	$fit       = '';
	foreach ( $sentences as $sentence ) {
		$next = '' === $fit ? $sentence : $fit . ' ' . $sentence;
		if ( mb_strlen( $next ) > BGCK_CARD_TEXT_MAX ) {
			break;
		}
		$fit = $next;
	}
	if ( '' !== $fit ) {
		return esc_html( $fit );
	}
	if ( mb_strlen( $plain ) <= BGCK_CARD_TEXT_MAX ) {
		return esc_html( $plain );
	}

	$cut = mb_substr( $plain, 0, BGCK_CARD_TEXT_MAX );
	$cut = mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) );
	return esc_html( rtrim( $cut, " ,;:–-" ) ) . '…';
}, 100, 2 );
