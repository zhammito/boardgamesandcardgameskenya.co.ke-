<?php
/*
 * Step 3: delivery wording across Elementor data, post content and product descriptions.
 * First run with $apply = false: it lists every change it would make, plus any other
 * delivery phrases it will NOT touch, so nothing surprising gets rewritten.
 * Then set $apply = true and run again. Safe to run repeatedly.
 */
$apply = false;

$banner = 'Order before 6pm for same-day or next-day delivery in Nairobi, or have it sent anywhere in Kenya.';

global $wpdb;

function bgck_fix_text( $s, $banner ) {
	$plain = trim( wp_strip_all_tags( $s ) );
	// The shop banner: a plain string that starts "Order before" and talks about delivery.
	if ( $plain === $s && preg_match( '/^order before/i', $plain ) && false !== stripos( $plain, 'deliver' ) ) {
		return $banner;
	}
	$s = preg_replace( '#<span[^>]*>\s*\[CUT-OFF TIME\]\s*</span>#i', '6pm', $s );
	$s = str_ireplace( '[CUT-OFF TIME]', '6pm', $s );
	$s = preg_replace( '/\b(same-day)(\s+delivery)/i', '$1 or next-day$2', $s );
	$s = preg_replace( '/\b(arrive the same day)(?! or)/i', '$1 or the next day', $s );
	return $s;
}

function bgck_walk( &$node, $banner, &$log, $path = '' ) {
	if ( is_array( $node ) ) {
		foreach ( $node as $k => &$v ) {
			bgck_walk( $v, $banner, $log, $path . '/' . $k );
		}
		return;
	}
	if ( ! is_string( $node ) || '' === $node ) {
		return;
	}
	$new = bgck_fix_text( $node, $banner );
	if ( $new !== $node ) {
		$log[] = "  CHANGE $path\n    - " . mb_substr( $node, 0, 220 ) . "\n    + " . mb_substr( $new, 0, 220 );
		$node  = $new;
	} elseif ( preg_match( '/same.?day|next.?day|cut.?off|order before/i', $node ) ) {
		$log[] = "  (unchanged) $path: " . mb_substr( $node, 0, 220 );
	}
}

// Pages named in the handoff, header/footer templates, every product, plus anything else that mentions these phrases.
$ids   = array( 115, 209, 211, 213, 215, 262, 263 );
$ids   = array_merge( $ids, get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) );
$extra = $wpdb->get_col(
	"SELECT DISTINCT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data'
	 WHERE p.post_type NOT IN ('revision','nav_menu_item') AND p.post_status <> 'trash'
	 AND ( p.post_content LIKE '%CUT-OFF%' OR p.post_content LIKE '%ame-day%' OR p.post_excerpt LIKE '%ame-day%'
	    OR m.meta_value LIKE '%CUT-OFF%' OR m.meta_value LIKE '%ame-day%' OR m.meta_value LIKE '%rder before%' OR m.meta_value LIKE '%arrive the same day%' )"
);
$ids   = array_values( array_unique( array_map( 'intval', array_merge( $ids, $extra ) ) ) );

$report  = array();
$changed = array();

foreach ( $ids as $id ) {
	$post = get_post( $id );
	if ( ! $post ) {
		$report[] = "#$id missing";
		continue;
	}
	$log = array();

	$raw = get_post_meta( $id, '_elementor_data', true );
	if ( is_string( $raw ) && '' !== $raw ) {
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) {
			$before = $log;
			bgck_walk( $data, $banner, $log, 'elementor' );
			if ( $apply && count( array_filter( $log, function ( $l ) { return 0 === strpos( $l, '  CHANGE' ); } ) ) > count( array_filter( $before, function ( $l ) { return 0 === strpos( $l, '  CHANGE' ); } ) ) ) {
				update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
				$changed[ $id ] = true;
			}
		} else {
			$log[] = '  elementor data did not decode, skipped';
		}
	}

	$fields = array();
	foreach ( array( 'post_content', 'post_excerpt' ) as $f ) {
		$new = bgck_fix_text( $post->$f, $banner );
		if ( $new !== $post->$f ) {
			$log[]        = "  CHANGE $f";
			$fields[ $f ] = $new;
		}
	}
	if ( $apply && $fields ) {
		$wpdb->update( $wpdb->posts, $fields, array( 'ID' => $id ) );
		clean_post_cache( $id );
		$changed[ $id ] = true;
	}

	if ( $log ) {
		$report[] = "#$id " . $post->post_type . ' "' . $post->post_title . "\"\n" . implode( "\n", $log );
	}
}

// Store notice option, in case the banner lives there.
$notice = get_option( 'woocommerce_demo_store_notice' );
if ( $notice && preg_match( '/same.?day|cut.?off|order before/i', $notice ) ) {
	$new      = bgck_fix_text( $notice, $banner );
	$report[] = "Store notice option:\n    - $notice\n    + $new";
	if ( $apply && $new !== $notice ) {
		update_option( 'woocommerce_demo_store_notice', $new );
	}
}

if ( $apply ) {
	foreach ( array_keys( $changed ) as $id ) {
		delete_post_meta( $id, '_elementor_element_cache' );
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	do_action( 'litespeed_purge_all' );
	$report[] = 'Saved ' . count( $changed ) . ' posts, cleared element cache and purged LiteSpeed.';
} else {
	$report[] = 'DRY RUN: nothing saved. Checked ' . count( $ids ) . ' posts.';
}

echo implode( "\n", $report );
