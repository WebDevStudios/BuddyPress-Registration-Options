<?php

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	// Test for BP Component object.
	if ( ! empty( $_POST['object'] ) ) {
		$object = sanitize_title( $_POST['object'] );

		if ( bp_is_active( $object ) ) {
			add_filter( 'wp_ajax_' . $object . '_filter', 'bp_registration_hide_ui', 1 );
		}
	} else {
		// Some AJAX requests still come through the 'init' action.
		bp_registration_hide_ui();
	}
}

/**
 * Hide BP posting UI components for private networks.
 *
 * @since 4.2.1
 */
function bp_registration_hide_ui() {

	$user     = get_current_user_id();
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
	// Hide friend buttons.
	add_filter( 'bp_get_add_friend_button', '__return_empty_array' );
	add_filter( 'bp_get_send_public_message_button', '__return_empty_array' );
	add_filter( 'bp_get_send_message_button', '__return_false' );
	add_filter( 'bp_get_send_message_button_args', '__return_empty_array' );

	// Hide group buttons.
	add_filter( 'bp_user_can_create_groups', '__return_false' );
	add_filter( 'bp_get_group_join_button', '__return_empty_string' );
	add_filter( 'bp_get_group_create_button', '__return_empty_array' );

	// Hide activity comment buttons.
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

	add_filter( 'wpmu_active_signup', 'bp_registration_filter_wpmu_active_signup' );
}
add_action( 'bp_ready', 'bp_registration_hide_ui' );

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
 * @return array $items Filtered menu items.
 */
function bp_registration_hide_messages_adminbar( $items = array() ) {
	foreach ( $items as $key => $value ) {
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
 * @return array $items Filtered menu items.
 */
function bp_registration_hide_groups_adminbar( $items = array() ) {
	foreach ( $items as $key => $value ) {
		if ( 'my-account-groups-create' == $value['id'] ) {
			unset( $items[ $key ] );
			break;
		}
	}

	return $items;
}

/**
 * Prevents blog creation in multisite for moderaated users.
 *
 * @since 4.3.0
 *
 * @param string $active_signup Active signup value.
 * @return string
 */
function bp_registration_filter_wpmu_active_signup( $active_signup = '' ) {
	return 'none';
}

/**
 * Removes pending users from member listings.
 *
 * @since 4.2.5
 *
 * @param array $r Query args for member list.
 * @return array $r Amended query args.
 */
function bp_registration_hide_widget_members( $r = array() ) {
	$exclude_me = bp_registration_get_pending_users();

	if ( empty( $exclude_me ) ) {
		return $r;
	}

	if ( ! is_active_widget( false, false, 'bp_core_members_widget', true ) ) {
		return $r;
	}

	$excluded = array();

	foreach ( $exclude_me as $exclude ) {
		$excluded[] = $exclude->user_id;
	}

	// Prevent overwriting of existing exclude values.
	if ( empty( $r['exclude'] ) ) {
		$r['exclude'] = implode( ',', $excluded );
	} else {
		$r['exclude'] .= ',' . implode( ',', $excluded );
	}

	return $r;
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
 * Hide members, who haven't been approved yet, on the frontend listings.
 *
 * @since 4.1.0
 *
 * @param object $args Arguments that BuddyPress will use to query for members.
 * @return object Amended arguments with IDs to exclude.
 */
function bp_registration_hide_pending_members( $args ) {
	global $wpdb;

	$private_network = get_option( 'bprwg_privacy_network' );

	if ( empty( $private_network ) || ! $private_network ) {
		return false;
	}

	$ids = array();

	$sql = "SELECT user_id FROM " . $wpdb->prefix . "usermeta WHERE meta_key = '_bprwg_is_moderated' AND meta_value = %s";
	$rs  = $wpdb->get_results( $wpdb->prepare( $sql, 'true' ), ARRAY_N );
	// Grab the actual IDs.
	foreach ( $rs as $key => $value ) {
		$ids[] = $value[0];
	}

	if ( $ids ) {
		$args->query_vars['exclude'] = $ids;
	}

	return $args;

}
add_action( 'bp_pre_user_query_construct', 'bp_registration_hide_pending_members' );
