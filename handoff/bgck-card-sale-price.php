<?php
/**
 * Plugin Name: BGCK Card Sale Price
 * Description: On the product cards (Elementor "Product Price" tag), shows a sale as "new price  old price" with the old price small and struck through. The atomic paragraph drops class attributes, so WooCommerce's hidden screen-reader text would otherwise show on the card.
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( ! $product->is_type( 'simple' ) || ! $product->is_on_sale() || '' === $product->get_regular_price() ) {
		return $html;
	}

	$from_card_tag = false;
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 ) as $frame ) {
		if ( isset( $frame['class'] ) && 'ElementorPro\Modules\Woocommerce\Tags\Product_Price' === $frame['class'] ) {
			$from_card_tag = true;
			break;
		}
	}
	if ( ! $from_card_tag ) {
		return $html;
	}

	return wc_price( wc_get_price_to_display( $product ) ) . ' <del><small>' . wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) ) . '</small></del>' . $product->get_price_suffix();
}, 100, 2 );
