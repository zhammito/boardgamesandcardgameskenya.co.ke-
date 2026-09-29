<?php
/**
 * Plugin Name: BGCK Free Delivery Over 5000
 * Description: Makes every delivery option free when the order subtotal is KSh 5,000 or more. Customers still pick their zone so you know where to deliver.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BGCK_FREE_DELIVERY_MIN', 5000 );

add_filter( 'woocommerce_package_rates', function ( $rates, $package ) {
	$subtotal = 0;
	foreach ( $package['contents'] as $item ) {
		$subtotal += (float) $item['line_total'] + (float) $item['line_tax'];
	}
	if ( $subtotal < BGCK_FREE_DELIVERY_MIN ) {
		return $rates;
	}
	foreach ( $rates as $rate ) {
		if ( 'flat_rate' !== $rate->get_method_id() || 0.0 === (float) $rate->get_cost() ) {
			continue;
		}
		$rate->set_cost( 0 );
		$rate->set_taxes( array() );
		$rate->set_label( $rate->get_label() . ' (FREE on orders of KSh 5,000 or more)' );
	}
	return $rates;
}, 20, 2 );
