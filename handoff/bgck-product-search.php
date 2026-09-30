<?php
/**
 * Plugin Name: BGCK Product Search
 * Description: Makes the site search (the header search box) look only at products, so results open in the shop's search results design.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	if ( ! $query->get( 'post_type' ) ) {
		$query->set( 'post_type', 'product' );
	}
} );
