<?php
/*
 * Step 5a: read-only Paystack check. Shows mode and whether keys are filled in, never the keys themselves.
 * Run before and after the owner switches to live.
 */
$s   = get_option( 'woocommerce_paystack_settings', array() );
$out = array();
if ( ! $s ) {
	echo 'No woocommerce_paystack_settings option found. Is the Paystack WooCommerce plugin active?';
	return;
}
$mask = function ( $v ) {
	return $v ? substr( $v, 0, 8 ) . '... (' . strlen( $v ) . ' chars)' : 'EMPTY';
};
$out[] = 'Enabled: ' . ( isset( $s['enabled'] ) ? $s['enabled'] : '?' );
$out[] = 'Test mode: ' . ( isset( $s['testmode'] ) ? $s['testmode'] : '?' );
foreach ( array( 'live_public_key', 'live_secret_key', 'test_public_key', 'test_secret_key' ) as $k ) {
	$out[] = $k . ': ' . $mask( isset( $s[ $k ] ) ? $s[ $k ] : '' );
}
if ( isset( $s['live_public_key'] ) && $s['live_public_key'] && 0 !== strpos( $s['live_public_key'], 'pk_live_' ) ) {
	$out[] = 'WARNING: live public key should start with pk_live_';
}
if ( isset( $s['live_secret_key'] ) && $s['live_secret_key'] && 0 !== strpos( $s['live_secret_key'], 'sk_live_' ) ) {
	$out[] = 'WARNING: live secret key should start with sk_live_';
}
$out[] = 'Store currency: ' . get_woocommerce_currency() . ' (Paystack Kenya needs KES)';
$out[] = 'Webhook URL to paste in Paystack (Settings > API Keys & Webhooks, Live): ' . WC()->api_request_url( 'Tbz_WC_Paystack_Webhook' );
$out[] = 'Callback URL: leave blank (the plugin sets it per payment)';
echo implode( "\n", $out );
