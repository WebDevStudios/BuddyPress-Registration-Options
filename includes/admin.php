<?php
/**
 * Admin Settings for BP Registration Options.

 * @package BP-Registration-Options
 */

/**
 * Get a count of our pending users
 *
 * @since 4.2.0
 *
 * @return integer  count of our current pending users
 */
function bp_registration_get_pending_user_count() {

	if ( false === ( $rs = get_transient( 'bpro_user_count' ) ) ) {
		global $wpdb;

		$sql = "SELECT count( user_id ) AS count FROM " . $wpdb->usermeta . " WHERE meta_key = %s AND meta_value = %s";

		$rs = $wpdb->get_col( $wpdb->prepare( $sql, '_bprwg_is_moderated', 'true' ) );

		if ( !empty( $rs ) ) {
			set_transient( 'bpro_user_count', $rs, 60*5 );
		}
	}

	return ( $rs ) ? $rs[0] : '0';
}

/**
 * Get our pending users
 *
 * @since 4.2.0
 *
 * @param integer $start_from Offset to start from with our paging of pending users.
 * @return array Array of user ID objects or empty array.
 */
function bp_registration_get_pending_users( $start_from = 0 ) {
	global $wpdb;

	$sql = "
		SELECT u.ID AS user_id
		FROM " . $wpdb->users . " AS u
		INNER JOIN " . $wpdb->usermeta . " AS um
		WHERE u.ID = um.user_id
		AND um.meta_key = %s
		AND meta_value = %s
		ORDER BY u.user_registered
		LIMIT %d, 20";

	$results = $wpdb->get_results( $wpdb->prepare( $sql, '_bprwg_is_moderated', 'true', $start_from ) );

	/**
	 * Filters the results of the pending users.
	 *
	 * @since 4.3.0
	 *
	 * @param array $results Array of found pending users.
	 */
	$results = apply_filters( 'bpro_hook_get_pending_users', $results );

	return ( !empty( $results ) ) ? $results : array();
}

/**
 * Delete our stored options so that they get reset next time.
 *
 * @since  4.2.0
 */
function bp_registration_handle_reset_messages() {

	delete_option( 'bprwg_activate_message' );
	delete_option( 'bprwg_approved_message' );
	delete_option( 'bprwg_denied_message' );
	delete_option( 'bprwg_admin_pending_message' );
	delete_option( 'bprwg_user_pending_message' );

	/**
	 * Fires after we've deleted our four message options
	 *
	 * @since 4.3.0
	 */
	do_action( 'bpro_hook_after_reset_messages' );
}

/**
 * Handle processing of new values for general options page
 *
 * @since 4.2.0
 *
 * @param array $args Array of inputs to save.
 */
function bp_registration_handle_general_settings( $args = array() ) {

	/**
	 * Fires before we've saved our options
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Array of inputs to be saved.
	 */
	do_action( 'bpro_hook_before_save_settings', $args );

	// Handle saving our moderate setting.
	if ( !empty( $args['set_moderate'] ) ) {
		$bp_moderate = sanitize_text_field( $args['set_moderate'] );
		update_option( 'bprwg_moderate', $bp_moderate );
	} else {
		delete_option( 'bprwg_moderate' );
	}

	// Handle saving our private network setting.
	if ( !empty( $args['set_private'] ) ) {
		$privacy_network = sanitize_text_field( $args['set_private'] );
		update_option( 'bprwg_privacy_network', $privacy_network );
	} else {
		delete_option( 'bprwg_privacy_network' );
	}

	// Handle saving our BuddyPress notifications setting.
	if ( !empty( $args['enable_notifications'] ) ) {
		$enable_notifications = sanitize_text_field( $args['enable_notifications'] );
		update_option( 'bprwg_enable_notifications', $enable_notifications );
	} else {
		delete_option( 'bprwg_enable_notifications' );
	}

	$activate_message = esc_textarea( $args['activate_message'] );
	update_option( 'bprwg_activate_message', $activate_message );

	$approved_message = esc_textarea( $args['approved_message'] );
	update_option( 'bprwg_approved_message', $approved_message );

	$denied_message = esc_textarea( $args['denied_message'] );
	update_option( 'bprwg_denied_message', $denied_message );

	$admin_pending_message = esc_textarea( $args['admin_pending_message'] );
	update_option( 'bprwg_admin_pending_message', $admin_pending_message );

	$user_pending_message = esc_textarea( $args['user_pending_message'] );
	update_option( 'bprwg_user_pending_message', $user_pending_message );

	/**
	 * Fires after we've saved our options
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Array of inputs that were saved.
	 */
	do_action( 'bpro_hook_after_save_settings', $args );
}

/**
 * Process approved or denied users. Sends out the notifications and handles user deletion when applicable
 *
 * @since  unknown
 */
function bp_registration_options_form_actions() {

	// Settings save.
	if ( isset( $_POST['save_general'] ) ) {

		check_admin_referer( 'bp_reg_options_check' );

		bp_registration_handle_general_settings(
			array(
				'set_moderate'          => empty( $_POST['bp_moderate'] ) ? '' : $_POST['bp_moderate'],
				'set_private'           => empty( $_POST['privacy_network'] ) ? '' : $_POST['privacy_network'],
				'enable_notifications'  => empty( $_POST['enable_notifications'] ) ? '' : $_POST['enable_notifications'],
				'activate_message'      => empty( $_POST['activate_message'] ) ? '' : $_POST['activate_message'],
				'approved_message'      => empty( $_POST['approved_message'] ) ? '' : $_POST['approved_message'],
				'denied_message'        => empty( $_POST['denied_message'] ) ? '' : $_POST['denied_message'],
				'admin_pending_message' => empty( $_POST['admin_pending_message'] ) ? '' : $_POST['admin_pending_message'],
				'user_pending_message'  => empty( $_POST['user_pending_message'] ) ? '' : $_POST['user_pending_message']
			)
		);
	}

	if ( isset( $_POST['reset_messages'] ) ) {

		check_admin_referer( 'bp_reg_options_check' );

		bp_registration_handle_reset_messages();
	}

	// Request submissions.
	if ( isset( $_POST['moderate'] ) ) {

		check_admin_referer( 'bp_reg_options_check' );

		$action = $_POST['moderate'];

		$checked_members = array();
		$send = false;
		$subject = '';
		$message = '';

		if ( isset( $_POST['bp_member_check'] ) ) {
			$checked_members = $_POST['bp_member_check'];
		}

		if ( !is_array( $checked_members ) ) {
			$checked_members = array( $checked_members );
		}

		if ( 'Deny' == $action ) { // Leave capitalized and don't i18n, because we're comparing button values for the time being.
			$send = true;
			$subject = __( 'Membership Denied', 'bp-registration-options' );
			$message = get_option( 'bprwg_denied_message' );
		}
		if ( 'Approve' == $action ) { // Leave capitalized and don't i18n, because we're comparing button values for the time being.
			$send = true;
			$subject = __( 'Membership Approved', 'bp-registration-options' );
			$message = get_option( 'bprwg_approved_message' );
		}

		foreach( $checked_members as $user_id ) {

			// Grab our userdata object while we still have a user.
			$user = get_userdata( $user_id );
			if ( 'Deny' == $action || 'Ban' == $action ) { // Leave capitalized and don't i18n, because we're comparing button values for the time being.

				/*
				 // Add our user to the IP ban option.
				 if ( 'Ban' == $action ) {

					$blockedIPs = get_option( 'bprwg_blocked_ips', array() );
					$blockedemails = get_option( 'bprwg_blocked_emails', array() );
					$blockedIPs[] = get_user_meta( $user_id, 'bprwg_ip_address', true);
					$blockedemails[] = $user->data->user_email;
					$successIP = update_option( 'bprwg_blocked_ips', $blockedIPs );
					$successEmail = update_option( 'bprwg_blocked_emails', $blockedemails );
				}
				*/

				do_action( 'bpro_hook_denied_user_before_delete', $user_id );

				if ( is_multisite() ) {
					wpmu_delete_user( $user_id );
				} else {
					wp_delete_user( $user_id );
				}

				do_action( 'bpro_hook_denied_user_after_delete', $user_id );

				bp_registration_options_delete_user_count_transient();

			} elseif ( 'Approve' == $action ) {
				// Mark as not spam for BuddyPress Registration Options.
				bp_registration_set_moderation_status( $user_id, 'false' );
				// Mark as not spam for BuddyPress Core.
				bp_core_process_spammer_status( $user_id, 'ham' );

				do_action( 'bpro_hook_approved_user', $user_id );

				bp_registration_options_delete_user_count_transient();
			}

			// Only send out message if one exists.
			if ( $send ) {

				$mailme = array(
					'user_email' => $user->data->user_email,
					'user_subject' => $subject,
					'user_message' => str_replace( '[username]', $user->data->user_login, $message )
				);

				$mailme_filtered = apply_filters( 'bpro_hook_before_email', $mailme, $user );

				add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

				wp_mail( $mailme_filtered['user_email'], $mailme_filtered['user_subject'], $mailme_filtered['user_message'] );

				remove_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );
			}
		}
	}
}
add_action( 'admin_init', 'bp_registration_options_form_actions' );

/**
 * Adds our admin notices with pending users info, for people who can manage users
 *
 * @since  unknown
 */
function bp_registration_options_admin_messages() {

	$member_requests = bp_registration_get_pending_user_count();

	if ( $member_requests > 0 && isset( $_GET['page'] ) != 'bp_registration_options_member_requests' && current_user_can( 'add_users' ) ) {

		$s = '';
		if ( $member_requests > 1 ) {
			$s = 's';
		}

		$message = '<div class="error"><p>';
		$numeral_url = sprintf(
			'<a href="%s"><strong>%s</strong></a>',
			admin_url( '/admin.php?page=bp_registration_options_member_requests' ),
			$member_requests
		);

		$click_url = sprintf(
			'<a href="%s">%s</a>',
			admin_url( '/admin.php?page=bp_registration_options_member_requests' ),
			__( 'click here', 'bp-registration-options' )
		);

		$message .= sprintf(
			_n(
				sprintf(
					'You have %s new member request that needs to be approved or denied. Please %s to take action',
					$numeral_url,
					$click_url
				),
				sprintf(
					'You have %s new member requests that needs to be approved or denied. Please %s to take action',
					$numeral_url,
					$click_url
				),
				$member_requests
			)
		);



		$message .= '</p></div>';

		echo $message;
	}
}
add_action('admin_notices', 'bp_registration_options_admin_messages');

/**
 * Register our plugins menus
 *
 * @since unknown
 */
function bp_registration_options_plugin_menu() {
	global $blog_id;

	$member_requests = bp_registration_get_pending_user_count();

	$capability = ( is_multisite() ) ? 'create_users' : 'manage_options';

	$minimum_cap = apply_filters( 'bp_registration_filter_minimum_caps', $capability );

	add_menu_page(
		__( 'BP Registration', 'bp-registration-options' ),
		__( 'BP Registration', 'bp-registration-options' ),
		$minimum_cap,
		'bp_registration_options',
		'bp_registration_options_settings',
		plugins_url( 'images/webdevstudios-16x16.png' , dirname( __FILE__ ) )
	);

	$count = '<span class="update-plugins count-' . $member_requests . '"><span class="plugin-count">' . $member_requests . '</span></span>';

	add_submenu_page(
		'bp_registration_options',
		__( 'Member Requests ', 'bp-registration-options' ) . $member_requests,
		__( 'Member Requests ', 'bp-registration-options' ) . $count,
		$minimum_cap,
		'bp_registration_options_member_requests',
		'bp_registration_options_member_requests'
	);

	add_submenu_page(
		'bp_registration_options',
		__( 'Banned Sources', 'bp-registration-options' ),
		__( 'Banned Sources', 'bp-registration-options' ),
		$minimum_cap,
		'bp_registration_options_banned',
		'bp_registration_options_banned'
	);

	add_submenu_page(
		'bp_registration_options',
		__( 'Help / Support', 'bp-registration-options' ),
		__( 'Help / Support', 'bp-registration-options' ),
		$minimum_cap,
		'bp_registration_options_help_support',
		'bp_registration_options_help_support'
	);
}
add_action( 'admin_menu', 'bp_registration_options_plugin_menu' );

/**
 * Create our tab navigation between setting pages
 *
 * @since  unknown
 *
 * @param string $page Page title to render.
 */
function bp_registration_options_tab_menu( $page = '' ) {

	$member_requests = bp_registration_get_pending_user_count(); ?>

	<h2 class="nav-tab-wrapper">
	<?php _e( 'BP Registration Options', 'bp-registration-options' ); ?>
	<a class="nav-tab<?php if ( !$page ) echo ' nav-tab-active';?>" href="<?php echo admin_url( 'admin.php?page=bp_registration_options' ); ?>"><?php _e( 'General Settings', 'bp-registration-options' ); ?></a>
	<a class="nav-tab<?php if ( $page == 'requests' ) echo ' nav-tab-active';?>" href="<?php echo admin_url( 'admin.php?page=bp_registration_options_member_requests' ); ?>"><?php _e( 'Member Requests', 'bp-registration-options' ); ?> (<?php echo $member_requests;?>)</a>
	<?php // <a class="nav-tab<?php if ( $page == 'banned' ) echo ' nav-tab-active';?" <?php //href="<?php echo admin_url( 'admin.php?page=bp_registration_options_banned' ); ?"><?php //_e( 'Banned', 'bp-registration-options' ); </a>?>
	</h2>
<?php }

/**
 * Options page for settings and messages to use
 *
 * @since unknown
 */
function bp_registration_options_settings() {

	// Check for already saved values.
	$bp_moderate           = get_option( 'bprwg_moderate' );
	$privacy_network       = get_option( 'bprwg_privacy_network' );
	$enable_notifications  = get_option( 'bprwg_enable_notifications' );
	$activate_message      = get_option( 'bprwg_activate_message' );
	$approved_message      = get_option( 'bprwg_approved_message' );
	$denied_message        = get_option( 'bprwg_denied_message' );
	$admin_pending_message = get_option( 'bprwg_admin_pending_message' );
	$user_pending_message = get_option( 'bprwg_user_pending_message' );

	if ( ! $activate_message ) {
		$activate_message = __( 'Your membership account is awaiting approval by the site administrator. You will not be able to fully interact with the social aspects of this website until your account is approved. Once approved or denied you will receive an email notice.', 'bp-registration-options' );

		update_option( 'bprwg_activate_message', $activate_message );
	}

	if ( ! $approved_message ) {
		$approved_message = sprintf(
			__( 'Hi [username], your member account at %s has been approved! You can now login and start interacting with the rest of the community...', 'bp-registration-options' ),
			get_bloginfo( 'url' )
		);

		update_option( 'bprwg_approved_message', $approved_message );
	}

	if ( ! $denied_message ) {
		$denied_message = sprintf(
			__( 'Hi [username], we regret to inform you that your member account at %s has been denied...', 'bp-registration-options' ),
			get_bloginfo( 'url' )
		);

		update_option( 'bprwg_denied_message', $denied_message );
	}

	if ( ! $admin_pending_message ) {
		$admin_pending_message = sprintf(
			__( '[username] ( [user_email] ) would like to become a member of your website. To accept or reject their request, please go to %s', 'bp-registration-options' ),
			'<a href="' . admin_url( '/admin.php?page=bp_registration_options_member_requests' ) . '">' . __( 'Member Requests', 'bp-registration-options' ) . '</a>'
		);

		update_option( 'bprwg_admin_pending_message', $admin_pending_message );
	}

	if ( ! $user_pending_message ) {
		$user_pending_message = sprintf(
			__( 'Hi [username], your account at %s is currently pending approval.', 'bp-registration-options' ),
			get_bloginfo( 'url' )
		);

		update_option( 'bprwg_denied_message', $user_pending_message );
	}
	?>

	<div class="wrap gensettings">
		<?php bp_registration_options_tab_menu(); ?>

		<form method="post">
			<?php wp_nonce_field('bp_reg_options_check'); ?>

			<?php do_action('bpro_hook_before_general_settings_form'); ?>

			<p>
				<input type="checkbox" id="bp_moderate" name="bp_moderate" value="1" <?php checked( $bp_moderate, '1' ); ?>/>
				<label for="bp_moderate">
					<strong>
						<?php _e( 'Moderate New Members', 'bp-registration-options' ); ?>
					</strong> (<?php _e( 'Every new member will have to be approved by an administrator before they can interact with BuddyPress/bbPress components.', 'bp-registration-options' ); ?>)
				</label>
			</p>

			<p>
				<input type="checkbox" id="privacy_network" name="privacy_network" value="1" <?php checked( $privacy_network, '1' ); ?>/>
				<label for="privacy_network">
					<?php _e( 'Only registered or approved members can view BuddyPress/bbPress pages (Private Network).', 'bp-registration-options' ); ?>
				</label>
			</p>

			<p>
				<input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" <?php checked( $enable_notifications, '1' ); ?>/>
				<label for="enable_notifications">
					<?php _e( 'Add new user notification to admin user account BuddyPress notification inbox.', 'bp-registration-options' ); ?>
				</label>
			</p>

			<table>
				<tr>
					<td class="alignright">
						<label for="activate_message"><?php _e( 'Activate & Profile Alert Message:', 'bp-registration-options' ); ?></label>
					</td>
					<td>
						<textarea id="activate_message" name="activate_message"><?php echo stripslashes( $activate_message );?></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright">
						<label for="approved_message"><?php _e( 'Account Approved Email:', 'bp-registration-options' ); ?></label>
					</td>
					<td>
						<textarea id="approved_message" name="approved_message"><?php echo stripslashes( $approved_message );?></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright">
						<label for="denied_message"><?php _e( 'Account Denied Email:', 'bp-registration-options' ); ?></label>
					</td>
					<td>
						<textarea id="denied_message" name="denied_message"><?php echo stripslashes( $denied_message );?></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright">
						<label for="admin_pending_message"><?php _e( 'Admin Pending Email Message:', 'bp-registration-options' ); ?></label>
					</td>
					<td>
						<textarea id="admin_pending_message" name="admin_pending_message"><?php echo stripslashes( $admin_pending_message );?></textarea>
					</td>
				</tr>
				<tr>
					<td class="alignright">
						<label for="user_pending_message"><?php _e( 'User Pending Email Message:', 'bp-registration-options' ); ?></label>
					</td>
					<td>
						<textarea id="user_pending_message" name="user_pending_message"><?php echo stripslashes( $user_pending_message );?></textarea>
					</td>
				</tr>
				<tr>
					<td></td>
					<td class="alignright">
						<table width="100%">
							<tr>
								<td>
									<?php _e( 'Short Code Key: [username], [user_email]', 'bp-registration-options' ); ?>
								</td>
								<td class="alignright">
									<input type="submit" id="reset_messages" name="reset_messages" class="button button-secondary" value="<?php esc_attr_e( 'Reset Messages', 'bp-registration-options' ); ?>" />
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<?php do_action('bpro_hook_after_general_settings_form'); ?>

			<input type="submit" class="button button-primary" name="save_general" value="<?php esc_attr_e( 'Save Options', 'bp-registration-options' ); ?>" />
		</form>
	</div>

	<?php bp_registration_options_admin_footer();
}

/**
 * Options page for managing pending members.
 *
 * @since unknown
 */
function bp_registration_options_member_requests() { ?>

	<div class="wrap">
		<?php
		bp_registration_options_tab_menu( 'requests' );

		$member_requests = bp_registration_get_pending_user_count();

		if ( $member_requests > 0 ) { ?>

			<form method="POST" name="bprwg">
			<?php

			do_action( 'bpro_hook_before_pending_member_list' );

			wp_nonce_field( 'bp_reg_options_check' ); ?>

			<p><?php _e( 'Please approve or deny the following new members:', 'bp-registration-options' ); ?></p>

			<table class="widefat">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column" scope="col">
						<label><input type="checkbox" id="bp_checkall_top" name="checkall" /></label>
					</th>
					<th><?php _e( 'Photo', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Name', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Email', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Created', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Additional Data', 'bp-registration-options' ); ?></th>
				</tr>
			</thead>
			<?php

			$odd = true;

			// Get paged value, determine total pages, and calculate start_from value for offset.
			$page = ( isset( $_GET['p'] ) ) ? $_GET['p'] : 1;
			$total_pages = ceil( $member_requests / 20 ); // TODO: Test pagination.
			$start_from = ( $page - 1 ) * 20;

			$pending_users = bp_registration_get_pending_users( $start_from );

			foreach( $pending_users as $pending ) {
				if ( class_exists( 'BP_Core_User' ) ) {
					$user = new BP_Core_User( $pending->user_id );
				}

				$user_data = get_userdata( $pending->user_id );

				if ( $odd ) { ?>
					<tr class="alternate">
					<?php
					$odd = false;
				} else { ?>
					<tr>
					<?php
					$odd = true;
				}
				?>
					<th class="check-column" scope="row">
						<label><input type="checkbox" class="bpro_checkbox" id="bp_member_check_<?php echo $pending->user_id; ?>" name="bp_member_check[]" value="<?php echo $pending->user_id; ?>"  /></label>
					</th>
					<td>
						<?php if ( isset( $user ) ) { ?>
							<a target="_blank" href="<?php echo $user->user_url; ?>">
								<?php echo $user->avatar_mini; ?>
							</a>
						<?php } ?>
					</td>
					<td>
						<?php if ( isset( $user ) ) { ?>
							<strong><a target="_blank" href="<?php echo $user->user_url; ?>">
								<?php
								if ( ! empty( $user->fullname ) ) {
									echo $user->fullname;
								} else {
									echo $user->profile_data['user_login'];
								}
								?>
							</a></strong>
						<?php } else {
							echo $user_data->user_login;
						} ?>
					</td>
					<td>
						<a href="mailto:<?php echo $user_data->data->user_email;?>">
							<?php echo $user_data->data->user_email; ?>
						</a>
					</td>
					<td>
						<?php echo $user_data->data->user_registered; ?>
					</td>
					<td>
						<?php do_action( 'bpro_hook_member_item_additional_data', $pending->user_id ); ?>
					</td>
				</tr>
				<?php
					do_action( 'bpro_hook_after_pending_member_list_item' );
				} ?>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" scope="col"><label><input type="checkbox" id="bp_checkall_bottom" name="checkall" /></label></th>
					<th><?php _e( 'Photo', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Name', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Email', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Created', 'bp-registration-options' ); ?></th>
					<th><?php _e( 'Additional Data', 'bp-registration-options' ); ?></th>
				</tr>
			</tfoot>
			</table>

			<p><input type="submit" class="button button-primary" name="moderate" value="<?php esc_attr_e( 'Approve', 'bp-registration-options' ); ?>" id="bpro_approve" />
			<input type="submit" class="button button-secondary" name="moderate" value="<?php esc_attr_e( 'Deny', 'bp-registration-options' ); ?>" id="bpro_deny" />
			<input type="submit" class="button button-secondary" name="moderate" disabled value="<?php esc_attr_e( 'Ban', 'bp-registration-options' ); ?>" id="bpro_ban" /></p>

			<?php if ( $total_pages > 1 ) {
				$current = ( !empty( $_GET['p'] ) ) ? $_GET['p'] : 1;
				echo '<p>' . __( 'Pagination: ', 'bp-registration-options' );
				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$classes = ( $i == $current ) ? 'bpro_pagination bpro_current wp-ui-highlight' : 'bpro_pagination';
					$classes = 'class="' . $classes . '"';
					printf(
						'<a href="%s" %s>%s</a> ',
						esc_url( add_query_arg( 'p', $i ) ),
						$classes,
						$i
					);
				}
				echo '</p>';
			}

			do_action( 'bpro_hook_after_pending_member_list' ); ?>

			</form>

		<?php } else {
			echo '<p><strong>' . __( 'No new members to approve.', 'bp-registration-options' ) . '</strong></p>';
		} ?>
	</div> <!--End Wrap-->

	<?php
	bp_registration_options_admin_footer();
}

/**
 * Render our banned members management page
 */
function bp_registration_options_banned() {
	// NEEDS DONE.
	?>
	<div class="wrap">
	<?php

	bp_registration_options_tab_menu( 'banned' );

	$blockedIPs = get_option( 'bprwg_blocked_ips' );
	$blockedemails = get_option( 'bprwg_blocked_emails' );

	if ( !empty( $blockedIPs ) || !empty( $blockedemails ) ) { ?>

		<h3><?php _e( 'The following IP addresses are currently banned.', 'bp-registration-options' ); ?></h3>
		<table class="widefat">
		<thead>
			<tr>
				<th id="cb" class="manage-column column-cb check-column" scope="col">
					<label><input type="checkbox" id="bp_checkall_top_blocked" name="checkall" /></label>
				</th>
				<th><?php _e( 'IP Address', 'bp-registration-options' ); ?></th>
			</tr>
		</thead>
		<?php

		$odd = true;

		foreach( $blockedIPs as $IP ) {
			if ( $odd ) {
				$attributes = ' class="alternate"';
				$odd = false;
			} else {
				$attributes = '';
				$odd = true;
			}
			?>
			<tr<?php echo $attributes; ?>>
			<th class="check-column" scope="row"><label><input type="checkbox" class="bpro_checkbox" id="bp_blocked_check_<?php echo $IP; ?>" name="bp_blockedip_check[]" value="<?php echo $IP; ?>"  /></label></th>
			<td><?php echo $IP; ?></a></td>
			</tr>
		<?php } ?>
		<tfoot>
			<tr>
				<th id="cb" class="manage-column column-cb check-column" scope="col">
					<label><input type="checkbox" id="bp_checkall_top_blocked" name="checkall" /></label>
				</th>
				<th><?php _e( 'IP Address', 'bp-registration-options' ); ?></th>
			</tr>
		</tfoot>
		</table>

		<h3><?php _e( 'The following Email addresses are currently banned.', 'bp-registration-options' ); ?></h3>

		<table class="widefat">
		<thead>
			<tr>
				<th id="cb" class="manage-column column-cb check-column" scope="col">
					<label><input type="checkbox" id="bp_checkall_top_blocked" name="checkall" /></label>
				</th>
				<th><?php _e( 'Email Address', 'bp-registration-options' ); ?></th>
			</tr>
		</thead>
		<?php

		$odd = true;

		foreach( $blockedemails as $email ) {
			if ( $odd ) { ?>
				<tr class="alternate">
				<?php
				$odd = false;
			} else { ?>
				<tr>
				<?php
				$odd = true;
			}
			?>
			<th class="check-column" scope="row"><label><input type="checkbox" class="bpro_checkbox" id="bp_member_check_<?php echo $user_id; ?>" name="bp_blockedemail_check[]" value=""  /></label></th>
			<td><?php echo $email; ?></a></td>
			</tr>
		<?php } ?>
		<tfoot>
			<tr>
				<th id="cb" class="manage-column column-cb check-column" scope="col">
					<label><input type="checkbox" id="bp_checkall_top_blocked" name="checkall" /></label>
				</th>
				<th><?php _e( 'Email Address', 'bp-registration-options' ); ?></th>
			</tr>
		</tfoot>
		</table>
		<?php } else {
			echo '<p><strong>' . __( 'You have no blocked IP Addresses or Email Addresses at the moment', 'bp-registration-options' ) . '</strong></p>';
		}
		bp_registration_options_admin_footer();
}

/**
 * Render our help/support page
 */
function bp_registration_options_help_support() {
	// NEEDS DONE.
	?>
	<div class="wrap">
		<?php bp_registration_options_tab_menu( 'help' );?>
	</div>
	<?php bp_registration_options_admin_footer();
}

/**
 * Render our content at the bottom of each page. Displays contact and credit information
 *
 * @since unknown
 */
function bp_registration_options_admin_footer() {

	do_action( 'bpro_hook_before_footer' );

	?>
	<p class="createdby">
		<?php _e( 'BuddyPress Registration Options plugin created by', 'bp-registration-options' ); ?>
		<a target="_blank" href="http://webdevstudios.com">WebDevStudios.com</a>
	</p>
	<table>
		<tr>
			<td>
				<table>
					<tr>
						<td>
							<a target="_blank" href="http://webdevstudios.com">
								<img width="50" src="<?php echo plugins_url( '/images/WDS-150x150.png', dirname( __FILE__ ) );?>" />
							</a>
						</td>
						<td>
							<strong><?php _e( 'Follow', 'bp-registration-options' ); ?> WebDevStudios</strong><br />
							<a target="_blank" href="https://plus.google.com/108871619014334838112">
								<img src="<?php echo plugins_url( '/images/google-icon.png', dirname( __FILE__ ) );?>" />
							</a>
							<a target="_blank" href="http://twitter.com/webdevstudios">
								<img src="<?php echo plugins_url( '/images/twitter-icon.png', dirname( __FILE__ ) );?>" />
							</a>
							<a target="_blank" href="http://facebook.com/webdevstudios">
								<img src="<?php echo plugins_url( '/images/facebook-icon.png', dirname( __FILE__ ) );?>" />
							</a>
						<td>
					</tr>
				</table>
			</td>
			<td>
				<table>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<strong><?php _e( 'Follow', 'bp-registration-options' ); ?> Michael Beckwith</strong><br />
							<a target="_blank" href="http://twitter.com/tw2113">
								<img src="<?php echo plugins_url( '/images/twitter-icon.png', dirname( __FILE__ ) );?>" />
							</a>
							<a target="_blank" href="http://facebook.com/tw2113">
								<img src="<?php echo plugins_url( '/images/facebook-icon.png', dirname( __FILE__ ) );?>" />
							</a>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<table>
					<tr>
						<td>
							&nbsp;
						</td>
						<td>
							<strong><?php _e( 'Follow', 'bp-registration-options' ); ?> Brian Messenlehner</strong><br />
							<a target="_blank" href="http://twitter.com/bmess">
								<img src="<?php echo plugins_url( '/images/twitter-icon.png', dirname( __FILE__ ) );?>" />
							</a>
							<a target="_blank" href="http://facebook.com/bmess">
								<img src="<?php echo plugins_url( '/images/facebook-icon.png', dirname( __FILE__ ) );?>" />
							</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?php
	do_action( 'bpro_hook_after_footer' );
}

/**
 * Add User-provided CSS to our admin_head output for styling purposes.
 *
 * @since 4.2.0
 */
function bp_registration_options_css() {
	$styles = apply_filters( 'bpro_hook_admin_styles', '' );
	if ( !empty( $styles ) ) {
		echo '<style>' . $styles . '</style>';
	}
}
add_action( 'admin_head', 'bp_registration_options_css' );

/**
 * Add our core plugin CSS
 *
 * @since 4.2.0
 */
function bp_registration_options_stylesheet() {
	wp_enqueue_style( 'bp-registration-options-stylesheet', plugins_url( 'assets/bp-registration-options.css', dirname( __FILE__ ) ) );
}
add_action( 'admin_enqueue_scripts', 'bp_registration_options_stylesheet' );

/**
 * Add JS to our admin_footer output for DOM manipulation purposes
 *
 * @since unknown
 */
function bp_registration_options_js() {
	?>
	<script language="javascript">
		(function($) {
			//Handle our checkboxes
			var checkboxes = $('.bpro_checkbox');
			$('#bp_checkall_top,#bp_checkall_bottom').on('click',function(){
				if ( $(this).attr('checked')) {
					$(checkboxes).each(function(){
						if ( $(this).prop('checked',false) ){
							$(this).prop('checked',true);
						}
					});
				} else {
					$(checkboxes).each(function(){
						$(this).prop('checked',false);
					});
				}
			});
			//Confirm/cancel on deny/ban.
			$('#bpro_deny').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to deny and delete the checked member(s)?', 'bp-registration-options' ); ?>");
			});
			$('#bpro_ban').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to ban and delete the checked member(s)?', 'bp-registration-options' ); ?>");
			});
			$('#reset_messages').on('click',function(){
				return confirm("<?php _e( 'Are you sure you want to reset to the default messages?', 'bp-registration-options' ); ?>");
			});
		})(jQuery);
	</script>
<?php
}
add_action( 'admin_footer', 'bp_registration_options_js' );

/**
 * Callback function for HTML email purposes.
 *
 * @since 4.2.0
 *
 * @param string $content_type Content type.
 *
 * @return string $value new content type to use
 */
function bp_registration_options_set_content_type( $content_type ) {
	return 'text/html';
}

/**
 * Delete user count transient as needed.
 *
 * @since 4.2.0
 * @since 4.3.0 Delete bpro_total_user_count transient as well.
 *
 * @return boolean
 */
function bp_registration_options_delete_user_count_transient() {
	delete_transient( 'bpro_user_count' );
	delete_transient( 'bpro_total_user_count' );

	return true;
}
add_action( 'deleted_user', 'bp_registration_options_delete_user_count_transient' );

/**
 * Filters the IP data into the user list table.
 *
 * @since 4.3.0
 *
 * @param int $user_id ID of the user being listed.
 */
function bp_registration_options_ip_data( $user_id ) {
	$userip = trim( get_user_meta( $user_id, '_bprwg_ip_address', true ) );
	?>
	<div class="ip_address_wrap">
	<div class="alignleft">
		<img height="50" src="http://api.hostip.info/flag.php?ip=<?php echo $userip; ?>" / >
	</div>
	<div class="alignleft">
		<?php
			$response = wp_remote_get( 'http://api.hostip.info/get_html.php?ip=' . $userip );
			if ( !is_wp_error( $response ) ) {
				$data = $response['body'];
				$data = str_replace( 'City:', '<br>' . __( 'City:', 'bp-registration-options' ), $data);
				$data = str_replace( 'IP:', '<br>' . __( 'IP:', 'bp-registration-options' ), $data);
				echo $data;
			} else {
				echo $userip;
			}
		?>
	</div>
	</div>
<?php
}
add_action( 'bpro_hook_member_item_additional_data', 'bp_registration_options_ip_data', 10, 1 );
