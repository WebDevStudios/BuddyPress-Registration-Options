<?php

function bp_registration_is_current_user_pending( $user_id = 0 ) {

	$pending = false;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$pending_users = bp_registration_get_pending_users();
	$pending_user_ids = wp_list_pluck( $pending_users, 'user_id' );

	if ( in_array( $user_id, $pending_user_ids ) ) {
		$pending = true;
	}

	return $pending;
}

function bp_registration_is_moderated() {
	$moderate = get_option( 'bprwg_moderate' );

	if ( empty( $moderate ) || ! $moderate ) {
		return false;
	}

	return true;
}
function bp_registration_is_private_network() {
	$private_network = get_option( 'bprwg_privacy_network' );

	if ( empty( $private_network ) || ! $private_network ) {
		return false;
	}

	return true;
}


/**
 * Queries for all existing approved members that still have an IP address saved as user meta.
 *
 * Helper method to help clear up saved personal data for GDPR compliance.
 *
 * @since 4.3.5
 *
 * @return WP_User_Query User query.
 */
function bp_registration_get_user_ip_query() {
	$args = array(
		'meta_query' => array(
			array(
				'key'     => '_bprwg_ip_address',
				'compare' => 'exists',
			),
			array(
				'key'   => '_bprwg_is_moderated',
				'value' => 'false',
			),
		),
		'fields'     => 'ID',
	);

	return new WP_User_Query( $args );
}

/**
 * Checks whether or not we have existing users with saved IP addresses.
 *
 * @since 4.3.5
 *
 * @return bool
 */
function bp_registration_has_users_with_ips() {
	$users = bp_registration_get_user_ip_query();

	return ( $users->get_total() > 0 );
}

/**
 * Iterates over results of found users with IP addresses saved, and removes meta key.
 *
 * @since 4.3.5
 */
function bp_registration_delete_ip_addresses() {
	$users = bp_registration_get_user_ip_query();

	if ( $users->get_total() > 0 ) {
		foreach ( $users->get_results() as $user_id ) {
			delete_user_meta( $user_id, '_bprwg_ip_address' );
		}
	}
}
