<?php
/*
 * Step 1: Kenya shipping zone with free HQ pickup first, then every rate in delivery-rates.json.
 * Run through novamira/execute-php. Safe to run again: existing methods with the same title are
 * updated instead of duplicated, and the order is reset each run.
 * Set $apply = false to only print what would change.
 */
$apply = true;

$pickup = array( 'HQ Pick Up: Royal Palm Mall, Wing A, 4th Floor, Shop AT4 (FREE, pick up until 6pm)', 0 );
$rates  = array(
	array( 'Zone A: Within CBD', 100 ),
	array( 'Pick Up Mtaani agent drop-off (write your location and preferred agent in the order note)', 140 ),
	array( 'Super Metro: Thika, Kikuyu, Ruiru, Juja, Ngong, Kitengela etc.', 250 ),
	array( 'Zone B: Upperhill, Valley Road, Community, Hurlingham, Nairobi Hospital, Pangani, Ngara, KNH, Ojijo Road etc.', 300 ),
	array( 'Zone C: Riverside, Westlands, ABC, Kilimani, Kileleshwa, Westgate, General Mathenge, Parklands, MP Shah, Aga Khan, Oshwal etc.', 350 ),
	array( "Zone E: South B/C, Mbagathi, Madaraka, Nairobi West, Lang'ata, Carnivore, Bellevue, NextGen Mall, Panari, Imara etc.", 350 ),
	array( 'Zone J: Roasters, Mountain Mall, Garden City, TRM, Lumumba Drive, USIU, Ngumba etc.', 350 ),
	array( 'Zone D: Kangemi, Loresho, Mountain View, Spring Valley, Lower Kabete, Industrial Area', 400 ),
	array( 'Zone F: Junction Mall, Lavington, Kibra, Dagoretti Corner, Kawangware, Wanyee etc.', 400 ),
	array( 'Zone N: Donholm, Uhuru Estate, Buruburu, Fedha, Tassia, Savannah, Pipeline, Mtindwa, Lucky Summer', 400 ),
	array( 'Zone G: Ruaka, Runda, Nyari, Gigiri, UNEP, Mucatha, Thindigua, Muthaiga North, Fourways, Ridgeways, Komarock etc.', 450 ),
	array( 'Zone M: Mirema, Kahawa Sukari, Zimmerman, Githurai, Kahawa West, Kahawa Wendani, Clayworks etc.', 450 ),
	array( 'Zone H: Gateway Mall, Syokimau and nearby', 600 ),
	array( 'Zone L: Nakuru, Mombasa, Eldoret, Kisumu', 350 ),
	array( 'Zone S: Kakamega, Kitale, Bungoma, Busia, Kericho, Kisii, Migori, Homa Bay, Nyeri, Meru, Embu, Machakos, Naivasha, Nanyuki, Malindi, Kilifi, Narok etc.', 350 ),
	array( 'Zone K: Other towns outside Nairobi (write your town and preferred courier in the order note)', 350 ),
);
array_unshift( $rates, $pickup );

global $wpdb;
$out = array();

// Find or create the "Kenya" zone.
$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Kenya' === $z['zone_name'] ) {
		$zone = new WC_Shipping_Zone( $z['id'] );
		break;
	}
}
if ( ! $zone ) {
	$out[] = 'Creating zone Kenya';
	if ( $apply ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Kenya' );
		$zone->save();
	}
} else {
	$out[] = 'Zone Kenya exists (id ' . $zone->get_id() . ')';
}

if ( $zone ) {
	$has_ke = false;
	foreach ( $zone->get_zone_locations() as $loc ) {
		if ( 'country' === $loc->type && 'KE' === $loc->code ) {
			$has_ke = true;
		}
	}
	if ( ! $has_ke ) {
		$out[] = 'Adding country KE to zone';
		if ( $apply ) {
			$zone->add_location( 'KE', 'country' );
			$zone->save();
		}
	}

	// Index existing flat rates by title.
	$existing = array();
	foreach ( $zone->get_shipping_methods( false ) as $m ) {
		if ( 'flat_rate' === $m->id ) {
			$existing[ $m->get_option( 'title' ) ] = $m->instance_id;
		}
	}

	$order = 0;
	foreach ( $rates as $r ) {
		list( $title, $cost ) = $r;
		if ( isset( $existing[ $title ] ) ) {
			$iid   = $existing[ $title ];
			$out[] = "Update #$iid: $title = $cost";
		} else {
			$iid   = $apply ? $zone->add_shipping_method( 'flat_rate' ) : 0;
			$out[] = "Add #$iid: $title = $cost";
		}
		if ( $apply && $iid ) {
			$key      = 'woocommerce_flat_rate_' . $iid . '_settings';
			$settings = get_option( $key, array() );
			$settings = array_merge(
				is_array( $settings ) ? $settings : array(),
				array(
					'title'      => $title,
					'tax_status' => 'none',
					'cost'       => (string) $cost,
				)
			);
			update_option( $key, $settings );
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array( 'method_order' => $order, 'is_enabled' => 1 ),
				array( 'instance_id' => $iid )
			);
		}
		$order++;
	}

	// Report methods in the zone that are not in the list (left alone, but worth knowing about).
	$titles = array_map( function ( $r ) { return $r[0]; }, $rates );
	foreach ( $zone->get_shipping_methods( false ) as $m ) {
		if ( ! in_array( $m->get_option( 'title' ), $titles, true ) ) {
			$out[] = 'Other method in zone (not changed): #' . $m->instance_id . ' ' . $m->id . ' ' . $m->get_option( 'title' );
		}
	}
}

$out[] = 'woocommerce_calc_shipping was ' . get_option( 'woocommerce_calc_shipping' );
if ( $apply ) {
	update_option( 'woocommerce_calc_shipping', 'yes' );
	WC_Cache_Helper::get_transient_version( 'shipping', true );
}

echo implode( "\n", $out );
