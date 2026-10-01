<?php
/**
 * Plugin Name: BGCK Simple Checkout
 * Description: Shortens the Kenya checkout to Full name, Phone, Location and Email. Delivery zone is still picked from the delivery options.
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$hide = array( 'required' => false, 'hidden' => true );

	$locale['KE'] = array_merge( isset( $locale['KE'] ) ? $locale['KE'] : array(), array(
		'first_name' => array( 'label' => 'Full name' ),
		'last_name'  => $hide,
		'address_1'  => array( 'label' => 'Location (e.g. Lavington, Nairobi)' ),
		'city'       => $hide,
		'state'      => $hide,
		'postcode'   => $hide,
	) );

	return $locale;
} );
