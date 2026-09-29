<?php
/*
 * Step 4: "Returns, Refunds & Delivery" page, built only from Elementor v4 atomic elements
 * (e-flexbox / e-div-block, e-heading, e-paragraph) and the site's existing global classes.
 * No HTML widget, no shortcode, no local styles. Then adds a footer link to it (template 263).
 *
 * Layout mirrors the Contact page: page-banner > container > page-head > h1 + lead,
 * then section > container > faq-list > faq-item (h3 + body-text).
 *
 * Run with $apply = false first. It prints the global classes it found, which labels it
 * resolved, and the shape of the heading/paragraph/container it copies from existing pages.
 * Set $apply = true to create the page and add the footer link. Safe to run again: it
 * updates the existing page (by slug) and will not add a second footer link.
 */
$apply     = false;
$footer_id = 263;
$slug      = 'returns-refunds-delivery';
$title     = 'Returns, Refunds & Delivery';
$email     = 'boardgamescardgames@gmail.com';

$sections = array(
	array( 'Returns', array(
		'Returns only apply when we deliver the wrong item for your order. You have 7 days from delivery to tell us. After 7 days we can\'t offer a refund or exchange.',
		'The item must be unused, in the same condition you received it, and in its original packaging. We check your order details, then arrange the exchange.',
	) ),
	array( 'Refunds', array(
		'We refund only when the product you ordered is sold out, or your delivery address is outside our delivery zones.',
		'Approved refunds go back to your M-Pesa or original payment method within 5 working days. If it hasn\'t arrived by then, email ' . $email . ' or message us on Instagram @boardgames_cardgames.',
	) ),
	array( 'Sale items', array(
		'Only full-price items can be refunded. Sale items can\'t be refunded.',
	) ),
	array( 'Exchanges', array(
		'We replace items that are defective, damaged, or not what you ordered. A rider from our team collects the item and delivers the correct one.',
		'Exchanges are done within 7 working days. During sales it may take longer.',
	) ),
	array( 'Delivery', array(
		'Nairobi: order before 6pm for same-day or next-day delivery.',
		'Outside Nairobi: 24–72 hours, excluding Sundays. During sales, delivery can take longer because of high order volumes.',
		'Free pickup at Royal Palm Mall, Wing A, 4th Floor, Shop AT4, until 6pm.',
		'Free delivery on orders of KSh 5,000 or more.',
	) ),
);
$lead = 'How returns, refunds, exchanges and delivery work when you shop with us.';

$out = array();

// ---- Global classes: label => id ----------------------------------------------------------
$kit_id  = (int) get_option( 'elementor_active_kit' );
$gc      = get_post_meta( $kit_id, '_elementor_global_classes', true );
$gc      = is_string( $gc ) ? json_decode( $gc, true ) : $gc;
$by_name = array();
if ( is_array( $gc ) && isset( $gc['items'] ) ) {
	foreach ( $gc['items'] as $id => $item ) {
		$by_name[ $item['label'] ] = isset( $item['id'] ) ? $item['id'] : $id;
	}
}
$out[] = 'Kit #' . $kit_id . ', ' . count( $by_name ) . ' global classes: ' . implode( ', ', array_keys( $by_name ) );

$GLOBALS['bgck_by_name'] = $by_name;
$GLOBALS['bgck_missing'] = array();
function bgck_classes( $labels ) {
	$ids = array();
	foreach ( $labels as $l ) {
		if ( isset( $GLOBALS['bgck_by_name'][ $l ] ) ) {
			$ids[] = $GLOBALS['bgck_by_name'][ $l ];
		} else {
			$GLOBALS['bgck_missing'][ $l ] = true;
		}
	}
	return array( '$$type' => 'classes', 'value' => $ids );
}

// ---- Templates copied from existing atomic pages ------------------------------------------
$tpl = array();
function bgck_find( $nodes, &$tpl ) {
	foreach ( $nodes as $n ) {
		$key = isset( $n['widgetType'] ) ? $n['widgetType'] : $n['elType'];
		if ( in_array( $key, array( 'e-heading', 'e-paragraph', 'e-flexbox', 'e-div-block' ), true ) && ! isset( $tpl[ $key ] ) ) {
			$tpl[ $key ] = $n;
		}
		if ( ! empty( $n['elements'] ) ) {
			bgck_find( $n['elements'], $tpl );
		}
	}
}
$source_id = 0;
foreach ( array( 215, 213, 211, 209, 115 ) as $pid ) {
	$d = json_decode( (string) get_post_meta( $pid, '_elementor_data', true ), true );
	if ( is_array( $d ) ) {
		bgck_find( $d, $tpl );
		if ( ! $source_id ) {
			$source_id = $pid;
		}
	}
}
foreach ( array( 'e-heading', 'e-paragraph', 'e-flexbox', 'e-div-block' ) as $k ) {
	if ( isset( $tpl[ $k ] ) ) {
		$t = $tpl[ $k ];
		unset( $t['elements'] );
		$out[] = "Template $k: " . mb_substr( wp_json_encode( $t ), 0, 600 );
	} else {
		$out[] = "Template $k: NOT FOUND on pages 115/209-215";
	}
}
$GLOBALS['bgck_tpl'] = $tpl;

function bgck_id() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

// Put $text into a copied text prop, whatever shape this Elementor version uses.
function bgck_text_prop( $prop, $text ) {
	if ( is_array( $prop ) && array_key_exists( 'value', $prop ) ) {
		if ( is_string( $prop['value'] ) ) {
			$prop['value'] = $text;
			return $prop;
		}
		if ( is_array( $prop['value'] ) && array_key_exists( 'content', $prop['value'] ) ) {
			$prop['value']['content'] = is_array( $prop['value']['content'] ) && array_key_exists( 'value', $prop['value']['content'] )
				? array_merge( $prop['value']['content'], array( 'value' => $text ) )
				: $text;
			if ( isset( $prop['value']['children'] ) ) {
				$prop['value']['children'] = array();
			}
			return $prop;
		}
	}
	return array( '$$type' => 'string', 'value' => $text );
}

function bgck_widget( $type, $text_key, $text, $labels, $tag = null ) {
	$tpl  = $GLOBALS['bgck_tpl'];
	$base = isset( $tpl[ $type ] ) ? $tpl[ $type ] : array( 'elType' => 'widget', 'widgetType' => $type, 'settings' => array() );
	$settings              = array( 'classes' => bgck_classes( $labels ) );
	$settings[ $text_key ] = bgck_text_prop( isset( $base['settings'][ $text_key ] ) ? $base['settings'][ $text_key ] : null, $text );
	if ( $tag ) {
		$settings['tag'] = array( '$$type' => 'string', 'value' => $tag );
	}
	$node = array(
		'id'         => bgck_id(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => $settings,
		'elements'   => array(),
		'styles'     => array(),
	);
	foreach ( array( 'version', 'editor_settings', 'interactions' ) as $k ) {
		if ( isset( $base[ $k ] ) ) {
			$node[ $k ] = 'version' === $k ? $base[ $k ] : array();
		}
	}
	return $node;
}

function bgck_box( $labels, $children, $tag = null, $top = false ) {
	$tpl  = $GLOBALS['bgck_tpl'];
	$type = $top && isset( $tpl['e-flexbox'] ) ? 'e-flexbox' : ( isset( $tpl['e-div-block'] ) ? 'e-div-block' : 'e-flexbox' );
	$base = isset( $tpl[ $type ] ) ? $tpl[ $type ] : array();
	$settings = array( 'classes' => bgck_classes( $labels ) );
	if ( $tag ) {
		$settings['tag'] = array( '$$type' => 'string', 'value' => $tag );
	}
	$node = array(
		'id'       => bgck_id(),
		'elType'   => $type,
		'settings' => $settings,
		'elements' => $children,
		'isInner'  => ! $top,
		'styles'   => array(),
	);
	foreach ( array( 'version', 'editor_settings', 'interactions' ) as $k ) {
		if ( isset( $base[ $k ] ) ) {
			$node[ $k ] = 'version' === $k ? $base[ $k ] : array();
		}
	}
	return $node;
}

// ---- Build the page tree --------------------------------------------------------------
$faq = array();
foreach ( $sections as $s ) {
	$kids = array( bgck_widget( 'e-heading', 'title', $s[0], array( 'h3' ), 'h2' ) );
	foreach ( $s[1] as $p ) {
		$kids[] = bgck_widget( 'e-paragraph', 'paragraph', $p, array( 'body-text' ) );
	}
	$faq[] = bgck_box( array( 'faq-item' ), $kids );
}
$data = array(
	bgck_box( array( 'page-banner', 'page-banner--contact', 'on-dark' ), array(
		bgck_box( array( 'container' ), array(
			bgck_box( array( 'page-head' ), array(
				bgck_widget( 'e-heading', 'title', $title, array( 'h1' ), 'h1' ),
				bgck_widget( 'e-paragraph', 'paragraph', $lead, array( 'lead' ) ),
			) ),
		) ),
	), 'section', true ),
	bgck_box( array( 'section' ), array(
		bgck_box( array( 'container' ), array(
			bgck_box( array( 'faq-list' ), $faq ),
		) ),
	), 'section', true ),
);

$out[] = 'Class labels not found (left off): ' . ( $GLOBALS['bgck_missing'] ? implode( ', ', array_keys( $GLOBALS['bgck_missing'] ) ) : 'none' );

// ---- Create or update the page ------------------------------------------------------------
$page     = get_page_by_path( $slug, OBJECT, 'page' );
$page_id  = $page ? $page->ID : 0;
$out[]    = $page_id ? "Page exists: #$page_id" : 'Page will be created';
$plain    = '';
foreach ( $sections as $s ) {
	$plain .= '<h2>' . esc_html( $s[0] ) . "</h2>\n<p>" . implode( "</p>\n<p>", array_map( 'esc_html', $s[1] ) ) . "</p>\n";
}

if ( $apply ) {
	$args = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $plain,
	);
	if ( $page_id ) {
		$args['ID'] = $page_id;
		wp_update_post( $args );
	} else {
		$page_id = wp_insert_post( $args );
	}
	update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	foreach ( array( '_elementor_version', '_elementor_pro_version', '_wp_page_template', '_elementor_page_settings' ) as $k ) {
		$v = get_post_meta( $source_id, $k, true );
		if ( '' !== $v && null !== $v ) {
			update_post_meta( $page_id, $k, $v );
		}
	}
	delete_post_meta( $page_id, '_elementor_element_cache' );
	delete_post_meta( $page_id, '_elementor_css' );
	$out[] = "Saved page #$page_id (layout copied from #$source_id): " . get_permalink( $page_id );
}

// ---- Footer link --------------------------------------------------------------------------
// Copies the existing "Delivery and pickup" footer link and inserts the copy right after it.
$url    = $page_id ? get_permalink( $page_id ) : home_url( '/' . $slug . '/' );
$footer = json_decode( (string) get_post_meta( $footer_id, '_elementor_data', true ), true );

function bgck_plain_texts( $n, &$acc ) {
	if ( is_array( $n ) ) {
		foreach ( $n as $v ) {
			bgck_plain_texts( $v, $acc );
		}
	} elseif ( is_string( $n ) ) {
		$acc[] = $n;
	}
}
function bgck_swap( &$n, $from_text, $to_text, $to_url, $page_id ) {
	if ( is_array( $n ) ) {
		// A link to a post by id: point it at the new page.
		if ( isset( $n['$$type'] ) && 'query' === $n['$$type'] && isset( $n['value']['id'] ) ) {
			$n['value']['id'] = $page_id;
		}
		foreach ( $n as $k => &$v ) {
			if ( 'id' === $k && ! is_array( $v ) ) {
				continue;
			}
			bgck_swap( $v, $from_text, $to_text, $to_url, $page_id );
		}
		return;
	}
	if ( is_string( $n ) ) {
		if ( false !== stripos( $n, $from_text ) ) {
			$n = str_ireplace( $from_text, $to_text, $n );
		} elseif ( preg_match( '#^(ht' . 'tps?:)?//|^/#', $n ) && false === strpos( $n, 'mailto:' ) ) {
			$n = $to_url;
		}
	}
}
function bgck_insert_link( &$nodes, $url, $page_id, &$log ) {
	foreach ( $nodes as $i => &$n ) {
		$texts = array();
		bgck_plain_texts( isset( $n['settings'] ) ? $n['settings'] : array(), $texts );
		$joined = implode( ' | ', $texts );
		if ( isset( $n['widgetType'] ) && false !== stripos( $joined, 'Delivery and pickup' ) && empty( $n['elements'] ) ) {
			$copy       = $n;
			$copy['id'] = bgck_id();
			bgck_swap( $copy['settings'], 'Delivery and pickup', 'Returns, Refunds & Delivery', $url, $page_id );
			$log[] = 'Footer link source: ' . mb_substr( wp_json_encode( $n ), 0, 500 );
			$log[] = 'Footer link copy:   ' . mb_substr( wp_json_encode( $copy ), 0, 500 );
			array_splice( $nodes, $i + 1, 0, array( $copy ) );
			return true;
		}
		if ( ! empty( $n['elements'] ) && bgck_insert_link( $n['elements'], $url, $page_id, $log ) ) {
			return true;
		}
	}
	return false;
}

if ( ! is_array( $footer ) ) {
	$out[] = "Footer #$footer_id: no Elementor data found";
} elseif ( false !== strpos( wp_json_encode( $footer ), 'Returns, Refunds' ) ) {
	$out[] = 'Footer already links the returns page, left alone';
} else {
	$log = array();
	if ( bgck_insert_link( $footer, $url, $page_id, $log ) ) {
		$out = array_merge( $out, $log );
		if ( $apply ) {
			update_post_meta( $footer_id, '_elementor_data', wp_slash( wp_json_encode( $footer ) ) );
			delete_post_meta( $footer_id, '_elementor_element_cache' );
			$out[] = 'Footer link added';
		}
	} else {
		$out[] = 'Footer: no "Delivery and pickup" link found to copy; add the link by hand in the footer template';
	}
}

if ( $apply ) {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	do_action( 'litespeed_purge_all' );
	$out[] = 'Caches cleared.';
} else {
	$out[] = 'DRY RUN: nothing saved.';
}

echo implode( "\n", $out );
