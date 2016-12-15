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
