<?php
/**
 * BP-Registration-Options Core Initialization.
 *
 * @package BP-Registration-Options
 */

/**
 * Display pending message to users until they're activated.
 *
 * @since unknown
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
 * Set up our user upon activation, email appropriate people.
 *
 * @since 4.2.2
 *
 * @param int $user_id User ID.
 */
function bp_registration_options_bp_core_register_account( $user_id ) {

	$moderate = get_option( 'bprwg_moderate' );

	if ( $moderate && $user_id > 0 ) {

		/* Somehow the WP-FB-AutoConnect plugin uses $_GET['key'] as well for user IDs. Let's check if the value returns a user. */

		/*
		 $is_user = get_userdata( $_GET['key'] );

		if ( !$is_user ) {
			return;
		}
		*/

		bp_registration_set_moderation_status( $user_id );

		$user = get_userdata( $user_id );

		/** This filter is documented in includes/core.php */
		//$admin_email = apply_filters( 'bprwg_admin_email_addresses', array( get_bloginfo( 'admin_email' ) ) );
		// Used for BP Notifications.
		$admins = get_users( 'role=administrator' );

		// Add HTML capabilities temporarily.
		add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

		// If their IP or email is blocked, don't proceed and exit silently.
		//$blockedIPs    = get_option( 'bprwg_blocked_ips', array() );
		//$blockedemails = get_option( 'bprwg_blocked_emails', array() );

		/*if ( in_array( $_SERVER['REMOTE_ADDR'], $blockedIPs ) || in_array( $user->user_email, $blockedemails ) ) {

			/**
			 * Filters the email content for the admin user when banned IP tries to register.
			 *
			 * @since 4.2.0
			 */
			/*$message = apply_filters( 'bprwg_banned_user_admin_email', __( 'Someone with a banned IP address or email just tried to register with your site', 'bp-registration-options' ) );

			wp_mail( $admin_email, __( 'Banned member registration attempt', 'bp-registration-options' ), $message );

			// Delete their account thus far.
			if ( is_multisite() ) {
				wpmu_delete_user( $user_id );
			} else {
				wp_delete_user( $user_id );
			}

			return;
		}*/

		// Set them as in moderation.
		bp_registration_set_moderation_status( $user_id );

		/**
		 * Filters the SERVER global reported remote address.
		 *
		 * @since 4.3.0
		 *
		 * @param string $value IP Address of the user being registered.
		 */
		update_user_meta( $user_id, '_bprwg_ip_address', apply_filters( '_bprwg_ip_address', $_SERVER['REMOTE_ADDR'] ) );

		// Admin email.
		$message = get_option( 'bprwg_admin_pending_message' );
		$message = str_replace( '[username]', $user->data->user_login, $message );
		$message = str_replace( '[user_email]', $user->data->user_email, $message );

		bp_registration_options_send_admin_email(
			array(
				'user_login' => $user->data->user_login,
				'user_email' => $user->data->user_email,
				'message'    => $message,
			)
		);

		bp_registration_options_delete_user_count_transient();

		// Set admin notification for new member.
		$enable_notifications = (bool) get_option( 'bprwg_enable_notifications' );
		if ( bp_is_active( 'notifications' ) && $enable_notifications ) {
			foreach ( $admins as $admin ) {
				bp_notifications_add_notification( array(
					'user_id'          => $admin->ID,
					'component_name'   => 'bp_registration_options',
					'component_action' => 'bp_registration_options',
					'allow_duplicate'  => true,
				) );
			}
		}
	}
}
add_action( 'user_register', 'bp_registration_options_bp_core_register_account' );

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
	$rs = $wpdb->get_results( $wpdb->prepare( $sql, 'true' ), ARRAY_N );
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
 * Check if current user should be denied access or not.
 *
 * @since 4.2.0
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

		/**
		 * Sets up the ability for 3rd parties to do their own redirect based on their own conditions.
		 *
		 * @since 4.3.0
		 *
		 * @param array $value Array with keys for whether to redirect and where.
		 */
		$custom_redirect = (array) apply_filters( 'bprwg_custom_redirect', array( 'redirect' => 'false', 'url' => '' ) );

		if ( 'true' === $custom_redirect['redirect'] ) {
			wp_redirect( esc_url( $custom_redirect['url'] ) );
			exit;
		}

		// Not logged in user.
		if ( 0 === $user->ID ) {

			/**
			 * Filters the URL to redirect to for logged out users.
			 *
			 * @since 4.3.0
			 *
			 * @param string $value URL to redirect to.
			 */
			$logged_out_url = apply_filters( 'bprwg_logged_out_redirect_url', get_bloginfo( 'url' ) );

			if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
				wp_redirect( $logged_out_url );
				exit;
			}
			if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
				wp_redirect( $logged_out_url );
				exit;
			}
		}

		// Logged in user but moderated.
		if ( $user->ID > 0 ) {
			if ( bp_registration_get_moderation_status( $user->ID ) ) {
				if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {

					/**
					 * Filters the URL to redirect to for moderated logged in users and BuddyPress areas.
					 *
					 * @since 4.3.0
					 *
					 * @param string $value URL to redirect to.
					 */
					wp_redirect( apply_filters( 'bprwg_bp_logged_in_redirect_url', bp_core_get_user_domain( $user->ID ) ) );
					exit;
				}
				if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {

					/**
					 * Filters the URL to redirect to for moderated logged in users and bbPress areas.
					 *
					 * @since 4.3.0
					 *
					 * @param string $value URL to redirect to.
					 */
					wp_redirect( apply_filters( 'bprwg_bbp_logged_in_redirect_url', bbp_get_user_profile_url( $user->ID ) ) );
					exit;
				}
			}
		}
	}
}
add_action( 'template_redirect', 'bp_registration_deny_access' );

/**
 * Check if on an allowed bbPress component.
 *
 * @since 4.2.0
 *
 * @return boolean True if an allowed component, false otherwise.
 */
function bp_registration_bbpress_allowed_areas() {

	$allowed = false;

	/*
	 * At time of this comment, bbp_is_user_home and bbp_is_user_home_edit are one after
	 * another in the same file. If one doesn't exist, both don't, and vice versa. Both will
	 * exist if one does.
	 */
	if ( function_exists( 'bbp_is_user_home' ) ) {
		if ( bbp_is_user_home() || bbp_is_user_home_edit() ) {
			$allowed = true;
		}
	}

	/**
	 * Filter for user-set custom areas of bbPress.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $allowed Current allowed value.
	 */
	return apply_filters( 'bprwg_bbpress_allowed_areas', $allowed );

}

/**
 * Check if on an allowed BuddyPress component.
 *
 * @since 4.2.0
 *
 * @return boolean True if an allowed component, false otherwise.
 */
function bp_registration_buddypress_allowed_areas() {

	$allowed = false;

	if ( function_exists( 'bp_is_my_profile' ) ) {
		global $bp;

		if ( bp_is_my_profile() || bp_is_user_profile() || bp_is_user_profile_edit() || 'register' === $bp->current_component || 'activate' === $bp->current_component ) {
			$allowed = true;
		}
	}

	/**
	 * Filter for user-set custom areas of BuddyPress.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $allowed Current allowed value.
	 */
	return apply_filters( 'bprwg_buddypress_allowed_areas', $allowed );
}

/**
 * Check our moderation status and return boolean values based on that.
 *
 * @since 4.2.0
 *
 * @param int $user_id User ID to check.
 * @return boolean Whether or not they're in moderation status.
 */
function bp_registration_get_moderation_status( $user_id ) {
	$moderated = get_user_meta( $user_id, '_bprwg_is_moderated', true );

	if ( 'true' == $moderated ) {
		return true;
	}
	return false;
}

/**
 * Update our moderation status for a user.
 *
 * @since 4.2.0
 *
 * @param int    $user_id User ID to update.
 * @param string $status   Value to update the user meta to.
 * @return int Meta row ID that got updated.
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
 * Send an email to the administrator email upon new user registration.
 *
 * @since 4.2.0
 *
 * @param array $args Array of arguments for the email.
 */
function bp_registration_options_send_admin_email( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'user_login' => '',
		'user_email' => '',
		'message'    => '',
	) );

	/**
	 * Filters the email address(es) to send admin notifications to.
	 *
	 * @since 4.3.0
	 *
	 * @param array $value Array of email addresses to send notification to.
	 */
	$admin_email = apply_filters( 'bprwg_admin_email_addresses', array( get_bloginfo( 'admin_email' ) ) );

	/**
	 * Filters the email text for admin when new member signs up.
	 *
	 * @since 4.2.0
	 *
	 * @param string $value Message to send.
	 * @param string $value User login name.
	 * @param string $value User email address.
	 */
	$mod_email = apply_filters( 'bprwg_new_member_request_admin_email_message', $args['message'], $args['user_login'], $args['user_email'] );

	add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

	wp_mail( $admin_email, __( 'New Member Request', 'bp-registration-options' ), $mod_email );

	remove_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );
}

/**
 * Send an email to the pending user upon registration.
 *
 * @since 4.3.0
 *
 * @param array $args Array of argumetns for the email.
 */
function bp_registration_options_send_pending_user_email( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'user_login' => '',
		'user_email' => '',
		'message'    => '',
	) );

	/**
	 * Filters the arguments used for wp_mail call for pending users.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Array of wp_mail args.
	 */
	$args = apply_filters( 'bprwg_pending_user_email_args', $args );

	wp_mail( $args['user_email'], __( 'Pending Membership', 'bp-registration-options' ), $args['message'] );

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
 *
 * @param string $count Total active users.
 * @return mixed|null|string
 */
function bp_registration_options_remove_moderated_count( $count ) {

	$pending_count = bp_registration_get_pending_user_count();

	if ( '0' === $pending_count ) {
		return $count;
	}

	global $wpdb;

	$total_count = get_transient( 'bpro_total_user_count' );

	if ( false === $total_count ) {

		$status_sql = 'user_status = 0';

		if ( is_multisite() ) {
			$status_sql = 'spam = 0 AND deleted = 0 AND user_status = 0';
		}

		$total_count = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->users} WHERE {$status_sql}" );

		set_transient( 'bpro_total_user_count', $total_count, HOUR_IN_SECONDS );
	}

	$final_count = ( $total_count - $pending_count );

	return ( $final_count > 0 ) ? $final_count : $count;

}
add_filter( 'bp_get_total_member_count', 'bp_registration_options_remove_moderated_count' );

/**
 * Adds our setting links to the BuddyPress member menu for our administrators.
 *
 * @since 4.3.0
 *
 * @return bool
 */
function bp_registration_options_admin_bar_add() {
	global $wp_admin_bar, $bp;

	if ( ! bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) ) {
		return false;
	}

	if ( ! current_user_can( 'delete_users' ) ) {
		return false;
	}

	$general_settings = admin_url( 'admin.php?page=bp_registration_options' );
	$member_requests  = admin_url( 'admin.php?page=bp_registration_options_member_requests' );

	$wp_admin_bar->add_menu( array(
		'parent' => $bp->my_account_menu_id,
		'id'     => 'bp-registration-options',
		'title'  => __( 'BP Registration Options', 'bp-registration-options' ),
		'meta' => array( 'class' => 'menupop' ),
		'href'   => $general_settings,
	) );

	// Submenus.
	$wp_admin_bar->add_menu( array(
		'parent' => 'bp-registration-options',
		'id'     => 'bp-registration-options-general-settings',
		'title'  => __( 'General Settings', 'bp-registration-options' ),
		'href'   => $general_settings,
	) );
	// Submenus.
	$wp_admin_bar->add_menu( array(
		'parent' => 'bp-registration-options',
		'id'     => 'bp-registration-options-member-requests',
		'title'  => __( 'Member Requests', 'bp-registration-options' ),
		'href'   => $member_requests,
	) );

	return true;
}
add_action( 'bp_setup_admin_bar', 'bp_registration_options_admin_bar_add', 300 );

/**
 * Prevents "___ has become a registered member" messages in activity.
 *
 * @since 4.3.0
 *
 * @param BP_Activity_Activity $args Array of arguments for activity item.
 */
function bp_registration_options_prevent_activity_posting( $args ) {
	if ( true === bp_registration_get_moderation_status( $args->user_id ) && 'new_member' === $args->type ) {
		$args->type = '';
	}
}
add_action( 'bp_activity_before_save', 'bp_registration_options_prevent_activity_posting' );

/**
 * Add activity item for newly approved member.
 *
 * @since 4.3.0
 *
 * @param int $user_id ID of the approved user.
 */
function bp_registration_options_display_activity_posting( $user_id ) {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_add( array(
			'user_id'   => $user_id,
			'component' => buddypress()->members->id,
			'type'      => 'new_member',
		) );
	}
}
add_action( 'bpro_hook_approved_user', 'bp_registration_options_display_activity_posting' );

/**
 * Add BP-Registration-Options as a possible component.
 *
 * @since 4.3.0
 *
 * @param array $component_names Array of component names.
 * @return array $component_names Array of updated component names.
 */
function bp_registration_options_get_registered_components( $component_names = array() ) {
	// Force $component_names to be an array.
	if ( ! is_array( $component_names ) ) {
		$component_names = array();
	}

	array_push( $component_names, 'bp_registration_options' );

	return $component_names;
}
add_filter( 'bp_notifications_get_registered_components', 'bp_registration_options_get_registered_components' );


function bprwg_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $component_action_name, $component_name ) {

	if ( 'bp_registration_options' === $component_action_name ) {

		/**
		 * Filters the text used for the notification generated by BuddyPress Registration Options.
		 *
		 * @since 4.3.0
		 *
		 * @param string $value Notification text.
		 */
		$text  = apply_filters( 'bprwg_notification_text', __( 'You have a new pending user to moderate.', 'bp-registration-options' ) );
		$link  = admin_url( 'admin.php?page=bp_registration_options_member_requests' );

		$result = array(
			'text' => $text,
			'link' => $link
		);

		// WordPress Toolbar
		if ( 'string' === $format ) {
			$result = sprintf( '<a href="%s">%s</a>', $link, $text );
		}

		return $result;
	}

	return $action;
}
add_filter( 'bp_notifications_get_notifications_for_user', 'bprwg_notifications', 11, 7 );

/**
 * Emails user about pending status upon activation.
 *
 * @since 4.3.0
 *
 * @param int    $user_id ID of the user being checked.
 * @param string $key     Activation key.
 * @param array  $user    Array of user data.
 */
function bp_registration_options_notify_pending_user( $user_id, $key, $user ) {

	$user_info = get_userdata( $user_id );
	$pending_message = get_option( 'bprwg_user_pending_message' );
	$filtered_message = str_replace( '[username]', $user_info->data->user_login, $pending_message );
	$filtered_message = str_replace( '[user_email]', $user_info->data->user_email, $filtered_message );

	/**
	 * Filters the message to be sent to user upon activation.
	 *
	 * @since 4.3.0
	 *
	 * @param string  $filtered_message Message to be sent with placeholders changed.
	 * @param string  $pending_message  Original message before placeholders filtered.
	 * @param WP_User $user_info        WP_User object for the newly activated user.
	 */
	$filtered_message = apply_filters( 'bprwg_pending_user_activation_email_message', $filtered_message, $pending_message, $user_info );
	bp_registration_options_send_pending_user_email(
		array(
			'user_login' => $user_info->data->user_login,
			'user_email' => $user_info->data->user_email,
			'message'    => $filtered_message,
		)
	);
}
add_action( 'bp_core_activated_user', 'bp_registration_options_notify_pending_user', 10, 3 );
