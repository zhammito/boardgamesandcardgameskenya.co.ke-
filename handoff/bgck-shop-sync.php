<?php
/**
 * Plugin Name: BGCK Shop Sync
 * Description: Keeps the automatic Shop page (/games/) in step with WooCommerce. Stores each product's add-to-cart and WhatsApp order links for the Loop cards, keeps the per-section game counts current, and refreshes the cached Shop and Home pages whenever a product changes.
 * Version: 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BGCK_SHOP_PAGE_ID = 209;
const BGCK_HOME_PAGE_ID = 115;
const BGCK_WHATSAPP     = '254727997511';

/**
 * Shop page sections in display order: count meta key => product_cat term ID.
 * Each product sits in exactly one section (its "shelf"): its Rank Math primary
 * category when that is one of these, otherwise the first of these it belongs to.
 */
function bgck_shop_sections() {
	return array(
		'bgck_count_board_games'    => 31,
		'bgck_count_card_games'     => 27,
		'bgck_count_party_drinking' => 29,
		'bgck_count_couples'        => 30,
		'bgck_count_kids_family'    => 28,
		'bgck_count_jigsaw'         => 32,
	);
}

// Private taxonomy the Shop page Loops filter on. Terms use the product_cat slugs.
add_action( 'init', function () {
	register_taxonomy( 'bgck_shelf', 'product', array(
		'label'             => 'Shop shelf',
		'public'            => false,
		'show_ui'           => false,
		'show_in_rest'      => true,
		'hierarchical'      => false,
		'rewrite'           => false,
		'query_var'         => false,
		'show_admin_column' => false,
	) );
}, 5 );

function bgck_shop_assign_shelf( $product_id ) {
	$sections = array_values( bgck_shop_sections() );
	$cats     = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
	$primary  = (int) get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
	$shelf    = 0;
	if ( $primary && in_array( $primary, $sections, true ) && in_array( $primary, $cats, true ) ) {
		$shelf = $primary;
	} else {
		foreach ( $sections as $term_id ) {
			if ( in_array( $term_id, $cats, true ) ) {
				$shelf = $term_id;
				break;
			}
		}
	}
	if ( ! $shelf ) {
		wp_set_object_terms( $product_id, array(), 'bgck_shelf' );
		return;
	}
	$cat  = get_term( $shelf, 'product_cat' );
	$term = term_exists( $cat->slug, 'bgck_shelf' );
	if ( ! $term ) {
		$term = wp_insert_term( $cat->name, 'bgck_shelf', array( 'slug' => $cat->slug ) );
	}
	if ( ! is_wp_error( $term ) ) {
		wp_set_object_terms( $product_id, array( (int) $term['term_id'] ), 'bgck_shelf' );
	}
}

function bgck_shop_update_product_links( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}
	$text = rawurlencode( "Hi, I'd like to order " . $product->get_name() );
	update_post_meta( $product_id, 'bgck_whatsapp_url', 'https://wa.me/' . BGCK_WHATSAPP . '?text=' . $text );

	// Products with options (like the puzzle designs) go to their page so the customer can choose.
	$cart_url = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock()
		? add_query_arg( 'add-to-cart', $product_id, wc_get_cart_url() )
		: get_permalink( $product_id );
	update_post_meta( $product_id, 'bgck_cart_url', $cart_url );

	// Red "18+" chip on the Shop cards; empty (and hidden) for everything else.
	update_post_meta( $product_id, 'bgck_adult_label', has_term( 'adults-only', 'product_tag', $product_id ) ? '18+' : '' );
}

function bgck_shop_update_counts() {
	foreach ( bgck_shop_sections() as $key => $term_id ) {
		$cat   = get_term( $term_id, 'product_cat' );
		$count = $cat ? count( wc_get_products( array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => -1,
			'return'     => 'ids',
			'tax_query'  => array(
				array(
					'taxonomy' => 'bgck_shelf',
					'field'    => 'slug',
					'terms'    => array( $cat->slug ),
				),
			),
		) ) ) : 0;
		update_post_meta( BGCK_SHOP_PAGE_ID, $key, $count . ( 1 === $count ? ' game' : ' games' ) );
	}
}

function bgck_shop_refresh_pages() {
	bgck_shop_update_counts();
	do_action( 'litespeed_purge_post', BGCK_SHOP_PAGE_ID );
	do_action( 'litespeed_purge_post', BGCK_HOME_PAGE_ID );
}

function bgck_shop_sync_all() {
	foreach ( wc_get_products( array( 'status' => 'any', 'limit' => -1, 'return' => 'ids' ) ) as $id ) {
		bgck_shop_update_product_links( $id );
		bgck_shop_assign_shelf( $id );
	}
	bgck_shop_refresh_pages();
}

add_action( 'woocommerce_new_product', 'bgck_shop_on_change' );
add_action( 'woocommerce_update_product', 'bgck_shop_on_change' );
function bgck_shop_on_change( $product_id ) {
	bgck_shop_update_product_links( $product_id );
	bgck_shop_assign_shelf( $product_id );
	bgck_shop_refresh_pages();
}

add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( 'product' === $post->post_type && $new_status !== $old_status ) {
		bgck_shop_refresh_pages();
	}
}, 10, 3 );

add_action( 'woocommerce_product_set_stock_status', 'bgck_shop_on_change' );
add_action( 'deleted_post', function ( $post_id, $post ) {
	if ( $post && 'product' === $post->post_type ) {
		bgck_shop_refresh_pages();
	}
}, 10, 2 );
