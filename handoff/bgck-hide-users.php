<?php
/**
 * Plugin Name: BGCK Hide Users
 * Description: Stops bots from finding the admin login name. Closes the public REST user list and the author archive pages.
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

// The REST user list is only for people who can manage users. Logged-in users can still read their own record (/users/me).
add_filter( 'rest_request_before_callbacks', function ( $response, $handler, $request ) {
	$route = $request->get_route();
	if ( 0 !== strpos( $route, '/wp/v2/users' ) || current_user_can( 'list_users' ) ) {
		return $response;
	}
	if ( is_user_logged_in() && preg_match( '#^/wp/v2/users/me/?$#', $route ) ) {
		return $response;
	}
	return new WP_Error( 'rest_user_cannot_view', 'Sorry, you are not allowed to list users.', array( 'status' => 401 ) );
}, 10, 3 );

// Author pages (/?author=1, /author/name/) go to the home page instead of revealing the login name.
add_action( 'template_redirect', function () {
	if ( isset( $_GET['author'] ) || is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );
