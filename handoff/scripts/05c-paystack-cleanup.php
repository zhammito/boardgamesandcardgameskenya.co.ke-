<?php
/*
 * Step 5c: after the live KSh 10 purchase has gone through.
 * Deletes test orders #456 and #457 and draft #439, and removes the hidden test product.
 * The real KSh 10 order is kept as the record of the refund. Refund it from the Paystack dashboard.
 * Set $apply = true to delete. With false it only lists what it would delete.
 */
$apply = false;
$out   = array();
foreach ( array( 456, 457, 439 ) as $oid ) {
	$o = wc_get_order( $oid );
	if ( ! $o ) {
		$out[] = "#$oid not found (already gone)";
		continue;
	}
	$out[] = "#$oid " . $o->get_status() . ' KSh ' . $o->get_total() . ' ' . $o->get_billing_email() . ' ' . $o->get_payment_method() . ( $apply ? ' -> deleted' : '' );
	if ( $apply ) {
		$o->delete( true );
	}
}
$pid = wc_get_product_id_by_sku( 'BGCK-LIVE-TEST' );
if ( $pid ) {
	$out[] = "Test product #$pid" . ( $apply ? ' -> deleted' : '' );
	if ( $apply ) {
		wc_get_product( $pid )->delete( true );
	}
}
if ( $apply ) {
	do_action( 'litespeed_purge_all' );
}
$out[] = $apply ? 'Done.' : 'DRY RUN: nothing deleted.';
echo implode( "\n", $out );
