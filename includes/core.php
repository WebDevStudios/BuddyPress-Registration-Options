<?php
/**
 * BP-Registration-Options Core Initialization
 *
 * @package BP-Registration-Options
 */

/**
 * Show a custom message on the activation page and on users profile header.
 */
function wds_bp_registration_options_bp_after_activate_content(){
	$user = get_current_user_id();

	if ( isset( $_GET['key'] ) || wds_get_moderation_status( $user ) ) {
		$activate_message = stripslashes( get_option( 'bprwg_activate_message' ) );
		echo '<div id="message" class="error"><p>' . $activate_message . '</p></div>';
	}
}
add_filter( 'bp_after_activate_content', 'wds_bp_registration_options_bp_after_activate_content' );
add_filter( 'bp_before_member_header', 'wds_bp_registration_options_bp_after_activate_content' );

/**
 * Custom activation functionality
 */
function wds_bp_registration_options_bp_core_activate_account( $user_id ){

	$private_network = get_option( 'bprwg_privacy_network' );

	if ( $private_network && $user_id > 0 ) {
		if ( isset( $_GET['key'] ) ) {

			$user = get_userdata( $user_id );
			$admin_email = get_bloginfo( 'admin_email' );

			//add HTML capabilities temporarily
			add_filter('wp_mail_content_type','bp_registration_options_set_content_type');

			//If their IP or email is blocked, don't proceed and exit silently.
			$blockedIPs = get_option( 'bprwg_blocked_ips' );
			$blockedemails = get_option( 'bprwg_blocked_emails' );

			if ( in_array( $_SERVER['REMOTE_ADDR'], $blockedIPs ) || in_array( $user->user_email, $blockedemails ) ) {
				$message = apply_filters( 'bprwg_banned_user_admin_email', __( 'Someone with a banned IP address or email just tried to register with your site', 'bp-registration-options' ) );

				wp_mail( $admin_email, __( 'Banned member registration attempt', 'bp-registration-options' ), $message );

				//Delete their account thus far.
				if ( is_multisite() ) {
					wpmu_delete_user( $user_id );
				}
				wp_delete_user( $user_id );

				return;
			}

			//Set them as in moderation.
			wds_set_moderation_status( $user_id );

			//save user ip address
			update_user_meta( $user_id, '_bprwg_ip_address', $_SERVER['REMOTE_ADDR'] );

			//email admin about new member request
			$user_name = $user->user_login;
			$user_email = $user->user_email;
			$message = $user_name . ' ( ' . $user_email . ' ) ' . __( 'would like to become a member of your website, to accept or reject their request please go to ', 'bp-registration-options') . admin_url( '/admin.php?page=bp_registration_options_member_requests' );

			//add our filter and provide the user name and user email for them to utilize.
			$mod_email = apply_filters( 'bprwg_new_member_request_admin_email', $message, $user_name, $user_email );

			wp_mail( $admin_email, __( 'New Member Request', 'bp-registration-options' ), $mod_email );

			remove_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );
		}
	}
}
add_action( 'bp_core_activate_account', 'wds_bp_registration_options_bp_core_activate_account');



/**
 * Hide members, who haven't been approved yet, on the frontend listings.
 * @param  object $args arguments that BuddyPress will use to query for members
 * @return object       amended arguments with IDs to exclude.
 * @since  4.1
 */
function bp_registration_hide_pending_members( $args ) {
	global $wpdb;

	$ids = array();
	$sql = "SELECT ID FROM " . $wpdb->base_prefix . "users WHERE user_status IN (2,69)";
	$rs = $wpdb->get_results( $wpdb->prepare( $sql, '' ), ARRAY_N );
	//Grab the actual IDs
	foreach( $rs as $key => $value) {
		$ids[] = $value[0];
	}

	if ( $ids )
		$args->query_vars['exclude'] = $ids;

	return $args;
}
add_action( 'bp_pre_user_query_construct', 'bp_registration_hide_pending_members' );

function wds_bp_registration_deny_access() {

	$user = new WP_User( get_current_user_id() );
	$deny = wds_bp_registration_get_user_status_values();
	$private_network = get_option('bprwg_privacy_network');

	if ( wds_buddypress_allowed_areas() || wds_bbpress_allowed_areas() || !$private_network ) {
		return;
	}

	if ( $user->ID == 0 && ( is_buddypress() || is_bbpress() ) ) {
		wp_redirect( get_bloginfo( 'url' ) );
		exit;
	}

	if ( $user->ID > 0 ) {
		if ( in_array( $user->data->user_status, $deny ) ) {
			if ( is_buddypress() ) {
				wp_redirect( bp_core_get_user_domain( $user->ID ) );
				exit;
			} elseif ( is_bbpress() ) {
				wp_redirect( bbp_get_user_profile_url( $user->ID ) );
				exit;
			}
		}
	}

}
add_action( 'template_redirect', 'wds_bp_registration_deny_access' );

/**
 * Return an array of user statuses to check for.
 *
 * @since  4.2
 *
 * @return array  array of user statuses
 */
function wds_bp_registration_get_user_status_values() {
	return array( 2, 69 );
}

/**
 * Check if on an allowed bbPress component
 *
 * @since  4.2.0
 *
 * @return boolean  true if an allowed component, false otherwise
 */
function wds_bbpress_allowed_areas() {

	if ( bbp_is_single_user_edit() || bbp_is_single_user() || bbp_is_user_home() || bbp_is_user_home_edit() ) {
		return true;
	}
	return false;

}

/**
 * Check if on an allowed BuddyPress component
 *
 * @since  4.2.0
 *
 * @return boolean  true if an allowed component, false otherwise
 */
function wds_buddypress_allowed_areas() {
	global $bp;

	if ( bp_is_my_profile() || bp_is_user_profile() || bp_is_user_profile_edit() || $bp->current_component == 'register' || $bp->current_component == 'activate' ) {
		return true;
	}
	return false;
}
