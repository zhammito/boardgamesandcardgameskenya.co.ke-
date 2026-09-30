<?php
/**
 * Plugin Name: BGCK Product Schema
 * Description: Uses the plain product name (not the SEO page title) in Google product data, and adds the brand from the WooCommerce Brands field when one is set.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_singular( 'product' ) ) {
		return $data;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return $data;
	}
	$brands = get_the_terms( $product->get_id(), 'product_brand' );
	foreach ( $data as $key => $entity ) {
		if ( ! is_array( $entity ) || ! isset( $entity['@type'] ) || 'Product' !== $entity['@type'] ) {
			continue;
		}
		$data[ $key ]['name'] = $product->get_name();
		if ( $brands && ! is_wp_error( $brands ) && empty( $entity['brand'] ) ) {
			$data[ $key ]['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brands[0]->name,
			);
		}
	}
	return $data;
}, 99 );
