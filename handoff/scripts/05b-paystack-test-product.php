<?php
/*
 * Step 5b: hidden KSh 10 product for one real live-mode purchase.
 * Hidden from shop and search, virtual (so no delivery fee is added), one in stock.
 * Prints the direct link to open in a private window. Safe to run again.
 */
$sku = 'BGCK-LIVE-TEST';
$id  = wc_get_product_id_by_sku( $sku );
$p   = $id ? wc_get_product( $id ) : new WC_Product_Simple();
$p->set_name( 'Live payment test (do not buy)' );
$p->set_sku( $sku );
$p->set_regular_price( '10' );
$p->set_status( 'publish' );
$p->set_catalog_visibility( 'hidden' );
$p->set_virtual( true );
$p->set_manage_stock( true );
$p->set_stock_quantity( 1 );
$p->set_reviews_allowed( false );
$p->set_short_description( 'Internal test product for checking live payments.' );
$id = $p->save();
update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', '1' );
update_post_meta( $id, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
do_action( 'litespeed_purge_all' );
echo "Product #$id\nAdd to cart link: " . add_query_arg( 'add-to-cart', $id, wc_get_cart_url() );
