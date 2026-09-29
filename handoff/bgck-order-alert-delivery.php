<?php
/**
 * Plugin Name: BGCK Order Alert Delivery
 * Description: Removes the customer Reply-To header from shop alert emails (new, cancelled and failed orders). Gmail was silently hiding these alerts because of it. The customer's email and phone still appear inside each alert.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'woocommerce_email_headers', function ( $headers, $email_id ) {
	if ( in_array( $email_id, array( 'new_order', 'cancelled_order', 'failed_order' ), true ) ) {
		$headers = preg_replace( '/^Reply-to:.*(\r\n|\n)?/mi', '', $headers );
	}
	return $headers;
}, 20, 2 );
