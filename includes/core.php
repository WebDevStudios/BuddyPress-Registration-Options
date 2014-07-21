<?php
/**
 * BP-Registration-Options Core Initialization
 *
 * @package BP-Registration-Options
 */

/**
 * Display pending message to users until they're activated.
 *
 * @since  unknown
 *
 * @return string  HTML message
 */
function bp_registration_options_bp_after_activate_content() {
	$user = get_current_user_id();

	if ( isset( $_GET['key'] ) || bp_registration_get_moderation_status( $user ) ) {
		$activate_message = stripslashes( get_option( 'bprwg_activate_message' ) );
		echo '<div id="message" class="error"><p>' . $activate_message . '</p></div>';
	}
}
add_filter( 'bp_after_activate_content', 'bp_registration_options_bp_after_activate_content' );
add_filter( 'bp_before_member_header', 'bp_registration_options_bp_after_activate_content' );

/**
 * Set up our user upon activation, email appropriate people
 *
 * @since  unknown
 *
 * @param  integer  $user_id User ID
 */
function bp_registration_options_bp_core_activate_account( $user_id ) {

	$private_network = get_option( 'bprwg_privacy_network' );

	if ( $private_network && $user_id > 0 ) {
		if ( isset( $_GET['key'] ) ) {

			//Somehow the WP-FB-AutoConnect plugin uses $_GET['key'] as well for user IDs. Let's check if the value returns a user.
			/*$is_user = get_userdata( $_GET['key'] );

			if ( !$is_user ) {
				return;
			}*/

			bp_registration_set_moderation_status( $user_id );

			$user = get_userdata( $user_id );
			$admin_email = get_bloginfo( 'admin_email' );

			//add HTML capabilities temporarily
			add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

			//If their IP or email is blocked, don't proceed and exit silently.
			//$blockedIPs = get_option( 'bprwg_blocked_ips', array() );
			//$blockedemails = get_option( 'bprwg_blocked_emails', array() );

			//Warning: in_array() expects parameter 2 to be array, boolean given in /Applications/XAMPP/xamppfiles/htdocs/wp/buddypress/wp-content/plugins/BuddyPress-Registration-Options/includes/core.php on line 50
			/*if ( in_array( $_SERVER['REMOTE_ADDR'], $blockedIPs ) || in_array( $user->user_email, $blockedemails ) ) {
				$message = apply_filters( 'bprwg_banned_user_admin_email', __( 'Someone with a banned IP address or email just tried to register with your site', 'bp-registration-options' ) );

				wp_mail( $admin_email, __( 'Banned member registration attempt', 'bp-registration-options' ), $message );

				//Delete their account thus far.
				if ( is_multisite() ) {
					wpmu_delete_user( $user_id );
				}
				wp_delete_user( $user_id );

				return;
			}*/

			//Set them as in moderation.
			bp_registration_set_moderation_status( $user_id );

			//save user ip address
			update_user_meta( $user_id, '_bprwg_ip_address', $_SERVER['REMOTE_ADDR'] );

			bp_registration_options_send_admin_email(
				array(
					'user_login' => $user->data->user_login,
					'user_email' => $user->data->user_email,
					'message'    => sprintf(
						__( '%s ( %s ) would like to become a member of your website. To accept or reject their request, please go to <a href="%s">%s</a>.', 'bp-registration-options' ),
						$user->data->user_nicename,
						$user->data->user_email,
						admin_url( '/admin.php?page=bp_registration_options_member_requests' ),
						admin_url( '/admin.php?page=bp_registration_options_member_requests' )
					)
				)
			);
		}
	}
}
add_action( 'bp_core_activate_account', 'bp_registration_options_bp_core_activate_account');

/**
 * Hide members, who haven't been approved yet, on the frontend listings.
 *
 * @since  4.1.0
 *
 * @param  object  $args Arguments that BuddyPress will use to query for members
 *
 * @return object        Amended arguments with IDs to exclude.
 */
function bp_registration_hide_pending_members( $args ) {
	global $wpdb;

	$ids = array();

	$sql = "SELECT user_id FROM " . $wpdb->prefix . "usermeta WHERE meta_key = '_bprwg_is_moderated' AND meta_value = %s";
	$rs = $wpdb->get_results( $wpdb->prepare( $sql, 'true' ), ARRAY_N );
	//Grab the actual IDs
	foreach( $rs as $key => $value) {
		$ids[] = $value[0];
	}

	if ( $ids ) {
		$args->query_vars['exclude'] = $ids;
	}

	return $args;

}
add_action( 'bp_pre_user_query_construct', 'bp_registration_hide_pending_members' );

/**
 * Check if current user should be denied access or not
 *
 * @since  4.2.0
 */
function bp_registration_deny_access() {

	$user = new WP_User( get_current_user_id() );
	$private_network = get_option( 'bprwg_privacy_network' );

	if ( bp_registration_buddypress_allowed_areas() || bp_registration_bbpress_allowed_areas() || !$private_network ) {
		return;
	}

	//Not logged in user.
	if ( $user->ID == 0 ) {
		if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
			wp_redirect( get_bloginfo( 'url' ) );
			exit;
		}
		if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
			wp_redirect( get_bloginfo( 'url' ) );
			exit;
		}
	}

	//Logged in user but moderated.
	if ( $user->ID > 0 ) {
		if ( bp_registration_get_moderation_status( $user->ID ) ) {
			if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
				wp_redirect( bp_core_get_user_domain( $user->ID ) );
				exit;
			}
			if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
				wp_redirect( bbp_get_user_profile_url( $user->ID ) );
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'bp_registration_deny_access' );

/**
 * Check if on an allowed bbPress component
 *
 * @since  4.2.0
 *
 * @return boolean  true if an allowed component, false otherwise
 */
function bp_registration_bbpress_allowed_areas() {

	if ( !function_exists( 'bbp_is_single_user_edit' ) ) { return false; }

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
function bp_registration_buddypress_allowed_areas() {

	if ( !function_exists( 'bp_is_my_profile' ) ) { return false; }

	global $bp;

	if ( bp_is_my_profile() || bp_is_user_profile() || bp_is_user_profile_edit() || $bp->current_component == 'register' || $bp->current_component == 'activate' ) {
		return true;
	}

	return false;
}

/**
 * Check our moderation status and return boolean values based on that
 *
 * @since  4.2.0
 *
 * @param  integer  $user_id User ID to check
 *
 * @return boolean           Whether or not they're in moderation status.
 */
function bp_registration_get_moderation_status( $user_id ) {
	$moderated = get_user_meta( $user_id, '_bprwg_is_moderated', true );

	if ( 'true' == $moderated ) {
		return true;
	}
	return false;
}

/**
 * Update our moderation status for a user
 *
 * @since  4.2.0
 *
 * @param  integer  $user_id User ID to update
 * @param  string  $status   Value to update the user meta to
 *
 * @return integer           meta row ID that got updated.
 */
function bp_registration_set_moderation_status( $user_id = 0, $status = 'true' ) {
	return update_user_meta( absint( $user_id ), '_bprwg_is_moderated', $status );
}

/**
 * Send an email to the administrator email upon new user registration
 *
 * @since  4.2.0
 *
 * @param  array   $args Array of arguments for the email
 */
function bp_registration_options_send_admin_email( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'user_login' => '',
		'user_email' => '',
		'message'    => '',
	) );

	$admin_email = get_bloginfo( 'admin_email' );

	//add our filter and provide the user name and user email for them to utilize.
	$mod_email = apply_filters( 'bprwg_new_member_request_admin_email_message', $args['message'], $args['user_login'], $args['user_email'] );

	wp_mail( $admin_email, __( 'New Member Request', 'bp-registration-options' ), $mod_email );

	remove_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );
}
