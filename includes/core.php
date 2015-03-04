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
	$moderate = get_option( 'bprwg_moderate' );

	$activate_screen = ( false === strpos( $_SERVER['REQUEST_URI'], 'activate' ) ) ? false : true;
	if ( $activate_screen || bp_registration_get_moderation_status( $user ) ) {
		if ( $moderate ) {
			$activate_message = stripslashes( get_option( 'bprwg_activate_message' ) );
			echo '<div id="message" class="error"><p>' . $activate_message . '</p></div>';
		}
	}
}
add_filter( 'bp_after_activate_content', 'bp_registration_options_bp_after_activate_content' );
add_filter( 'bp_before_member_header', 'bp_registration_options_bp_after_activate_content' );

/**
 * Set up our user upon activation, email appropriate people
 *
 * @since  4.2.2
 *
 * @param  integer  $user_id User ID
 */
function bp_registration_options_bp_core_register_account( $user_id ) {

	$moderate = get_option( 'bprwg_moderate' );

	if ( $moderate && $user_id > 0 ) {

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
		bp_registration_options_delete_user_count_transient();
	}
}
add_action( 'user_register', 'bp_registration_options_bp_core_register_account' );

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

	$private_network = get_option( 'bprwg_privacy_network' );

	if ( empty( $private_network ) || ! $private_network ) {
		return;
	}

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
 * Hide BP posting UI components for private networks.
 *
 * @since 4.2.1
 */
function bp_registration_hide_ui() {

	$user = get_current_user_id();
	$moderate = (bool) get_option( 'bprwg_moderate' );

	if ( empty( $moderate ) || ! $moderate ) {
		return;
	}
	$count = bp_registration_get_pending_user_count();
	if ( absint( $count ) ) {
		add_filter( 'bp_before_has_members_parse_args', 'bp_registration_hide_widget_members' );
	}

	if ( ! bp_registration_get_moderation_status( $user ) ) {
		return;
	}

	remove_action( 'bp_directory_members_actions', 'bp_member_add_friend_button' );

	add_filter( 'bp_activity_can_favorite', '__return_false' );
	//hide friend buttons
	add_filter( 'bp_get_add_friend_button', '__return_empty_array' );
	add_filter( 'bp_get_send_public_message_button', '__return_empty_array' );
	add_filter( 'bp_get_send_message_button', '__return_false' );
	add_filter( 'bp_get_send_message_button_args', '__return_empty_array' );

	//hide group buttons
	add_filter( 'bp_user_can_create_groups', '__return_false' );
	add_filter( 'bp_get_group_join_button', '__return_empty_string' );
	add_filter( 'bp_get_group_create_button', '__return_empty_array' );

	//hide activity comment buttons
	add_filter( 'bp_activity_can_comment_reply', '__return_false' );
	add_filter( 'bp_activity_can_comment', '__return_false' );
	add_filter( 'bp_acomment_name', '__return_false' );
	add_filter( 'bp_get_activity_delete_link', '__return_empty_string' );

	add_filter( 'bbp_current_user_can_access_create_reply_form', '__return_false' );
	add_filter( 'bbp_current_user_can_access_create_topic_form', '__return_false' );
	add_filter( 'bbp_get_topic_reply_link', '__return_empty_string' );
	add_filter( 'bbp_get_user_subscribe_link', '__return_empty_string' );
	add_filter( 'bbp_get_user_favorites_link', '__return_empty_string' );
	add_action( 'bp_before_activity_post_form', 'bp_registration_hide_whatsnew_start' );
	add_action( 'bp_after_activity_post_form', 'bp_registration_hide_whatsnew_end' );

	add_filter( 'bp_messages_admin_nav', 'bp_registration_hide_messages_adminbar' );
	add_filter( 'bp_groups_admin_nav', 'bp_registration_hide_groups_adminbar' );

	add_action( 'wp_head', 'bp_registration_options_disable_ajax' );
}
add_action( 'bp_ready', 'bp_registration_hide_ui' );

function bp_registration_options_disable_ajax() {
	?>
<script>
	jQuery(document).ready(function($) {
		$('.item-list-tabs, .item-list-tabs li, .item-list-tabs a').addClass('no-ajax');
	});
</script>
<?php
}

/**
 * Start output buffering before the start of whats new fields.
 *
 * @since 4.2.5
 */
function bp_registration_hide_whatsnew_start() {
	ob_start();
}

/**
 * Start output buffering after the end of whats new fields.
 *
 * @since 4.2.5
 */
function bp_registration_hide_whatsnew_end() {
	ob_end_clean();
}

/**
 * Hide interaction menu items from Admin Bar.
 *
 * @since 4.2.5
 *
 * @param array $items Array of menu items to be displayed.
 *
 * @return array $items Filtered menu items.
 */
function bp_registration_hide_messages_adminbar( $items = array() ) {
	foreach( $items as $key => $value ) {
		if ( 'my-account-messages-compose' == $value['id'] ) {
			unset( $items[ $key ] );
			break;
		}
	}
	return $items;
}

/**
 * Hide interaction menu items from Admin Bar.
 *
 * @since 4.2.5
 *
 * @param array $items Array of menu items to be displayed.
 *
 * @return array $items Filtered menu items.
 */
function bp_registration_hide_groups_adminbar( $items = array() ) {
	foreach( $items as $key => $value ) {
		if ( 'my-account-groups-create' == $value['id'] ) {
			unset( $items[ $key ] );
			break;
		}
	}
	return $items;
}

/**
 * Removes pending users from member listings.
 *
 * @since 4.2.5
 *
 * @param array $r Query args for member list.
 *
 * @return array $r Amended query args.
 */
function bp_registration_hide_widget_members( $r = array() ) {
	$exclude_me = bp_registration_get_pending_users();
	$excluded = array();

	foreach( $exclude_me as $exclude ) {
		$excluded[] = $exclude->user_id;
	}
	$r['exclude'] = implode( ',', $excluded );

	return $r;
}

/**
 * Check if current user should be denied access or not
 *
 * @since  4.2.0
 */
function bp_registration_deny_access() {

	$user = new WP_User( get_current_user_id() );
	$private_network = (bool) get_option( 'bprwg_privacy_network' );

	if ( $private_network ) {

		if ( bp_registration_buddypress_allowed_areas() ) {
			return;
		}

		if ( bp_registration_bbpress_allowed_areas() ) {
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

	if ( !function_exists( 'bbp_is_user_home' ) ) { return false; }

	if ( bbp_is_user_home() || bbp_is_user_home_edit() ) {
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
	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return false;
	}

	delete_user_meta( $user_id, '_bprwg_is_moderated' );
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

/**
 * Hide Compose menu from pending users.
 *
 * @since 4.2.3
 */
function bp_registration_options_remove_compose_message() {
	if ( true === bp_registration_get_moderation_status( get_current_user_id() ) ) {
		bp_core_remove_subnav_item( 'messages', 'compose' );
	}
}
add_action( 'bp_setup_nav', 'bp_registration_options_remove_compose_message' );

/**
 * Filter our user count to take into account spam members.
 * @param string $count Total active users.
 *
 * @return mixed|null|string
 */
function bp_registration_options_remove_moderated_count( $count ) {

	$pending_count = bp_registration_get_pending_user_count();

	if ( 0 == $pending_count ) {
		return $count;
	}

	global $wpdb;

	$total_count = get_transient( 'bpro_total_user_count' );

	if ( false === $total_count ) {

		$status_sql = "user_status = 0";

		if ( is_multisite() ) {
			$status_sql = "spam = 0 AND deleted = 0 AND user_status = 0";
		}

		$total_count = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->users} WHERE {$status_sql}" );

		set_transient( 'bpro_total_user_count', $total_count, HOUR_IN_SECONDS );
	}

	$final_count = ( $total_count - $pending_count );

	return ( $final_count > 0 ) ? $final_count : $count;

}
#add_filter( 'bp_get_total_member_count', 'bp_registration_options_remove_moderated_count' );
