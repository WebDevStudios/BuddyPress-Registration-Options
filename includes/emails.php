<?php

/**
 * BP Email Support

 * @package BP-Registration-Options
 */

/**
 * Class BP_Registration_Emails
 *
 * Adds support for BP Emails
 */


class BP_Registration_Emails {

	/**
	 * @var BP_Registration_Emails
	 */
	private static $instance;

	/**
	 * Main BP_Registration_Emails Instance
	 *
	 * Insures that only one instance of BP_Registration_Emails exists in memory at
	 * any one time. Also prevents needing to define globals all over the place.
	 *
	 * @since 4.4.0
	 *
	 * @staticvar array $instance
	 *
	 * @return BP_Registration_Emails
	 */
	public static function instance( ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new BP_Registration_Emails;
			self::$instance->setup_actions();
		}

		return self::$instance;
	}

	/**
	 * A dummy constructor to prevent loading more than one instance
	 *
	 * @since 4.4.0
	 */
	private function __construct() { /* Do nothing here */
	}




	/**
	 * Setup the actions
	 *
	 * @since 4.4.0
	 * @access private
	 *
	 * @uses remove_action() To remove various actions
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		add_action( 'bp_core_install_emails', array( $this, 'install_bp_emails' ) );

	}

	/**
	 * Return if BP Emails are available
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public static function bp_emails_available() {
		$bp = buddypress();

		return version_compare( $bp->version, '2.5.0', '>=' );
	}


	/**
	 * Get a list of emails
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	function get_email_schema() {
		return array(
			'account_approved' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership Approved', 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Hi {{recipient.name}}, your member account at <a href=\"{{{site.url}}}\">{{{site.name}}}</a> has been approved! You can now login and start interacting with the rest of the community...\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Hi {{recipient.name}}, your member account at {{{site.url}}} has been approved! You can now login and start interacting with the rest of the community...\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
			),
			'account_denied' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership Denied', 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Hi {{recipient.name}}, we regret to inform you that your member account at <a href=\"{{{site.url}}}\">{{{site.name}}}</a> has been denied...\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Hi {{recipient.name}}, we regret to inform you that your member account at {{{site.url}}} has been denied...\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
			),
			'admin_pending' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New Member Request', 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{username}} ( {{user_email}} ) would like to become a member of your website. To accept or reject their request, please go to <a href=\"{{{admin.url}}}\">Member Requests</a>\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{username}} ( {{user_email}} ) would like to become a member of your website. To accept or reject their request, please go to: {{{admin.url}}}\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
			),
			'user_pending' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Pending Membership', 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Hi {{recipient.name}}, your account at <a href=\"{{{site.url}}}\">{{{site.name}}}</a> is currently pending approval.\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Hi {{recipient.name}}, your account at {{{site.url}}} is currently pending approval.\n\nYour {{{site.name}}} Team", 'bp-registration-options' ),
			),
		);
	}

	/**
	 * Get a list of email type taxonomy terms.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	function get_email_type_schema() {
		return array(
			'account_approved'	=> __( 'The user account has been approved', 'bp-registration-options' ),
			'account_denied' 	=> __( 'The user account has been denied', 'bp-registration-options' ),
			'admin_pending'		=> __( 'Admin notification about pending registration', 'bp-registration-options' ),
			'user_pending'		=> __( 'User notification about pending registration', 'bp-registration-options' ),
		);
	}

	/**
	 * Install BuddyPress Emails
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	function install_bp_emails() {

		$emails = $this->get_email_schema();
		$terms  = $this->get_email_type_schema();

		foreach ( $emails as $term_name => $email ) {

			// Do not create if it already exists and is not in the trash
			$post_exists = get_posts( array(
				'post_type'	  => bp_get_email_post_type(),
				'post_status' => 'publish',
				'tax_query'   => array(
					array(
						'taxonomy' => bp_get_email_tax_type(),
						'field'    => 'name',
						'terms'    => $term_name,
					),
				),
			) );
			if ( ! empty( $post_exists ) )
				continue;

			// Create post object
			$email_post = array(
			  'post_title'    => $email['post_title'],
			  'post_content'  => $email['post_content'],  // HTML email content.
			  'post_excerpt'  => $email['post_excerpt'],  // Plain text email content.
			  'post_status'   => 'publish',
			  'post_type' 	  => bp_get_email_post_type() // this is the post type for emails
			);

			// Insert the email post into the database
			$post_id = wp_insert_post( $email_post );

			if ( $post_id ) {
			  // add our email to the right taxonomy term

				$tt_ids = wp_set_object_terms( $post_id, $term_name, bp_get_email_tax_type() );
				foreach ( $tt_ids as $tt_id ) {
					$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
					wp_update_term( (int) $term->term_id, bp_get_email_tax_type(), array(
						'description' => $terms[$term_name],
					) );
				}
			}
		}

	}

	/**
	 * Send moderation email
	 *
	 * checks if new BP Emails are available and calls the legacy implementation otherwise
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for user being moderated.
	 * @param string $action either 'deny' or 'approve'
	 *
	 */
	public function send_moderation_email( $user, $action ) {
		if ( $this->bp_emails_available() ) {
			$this->bp_send_moderation_email( $user, $action );
		} else {
			$this->legacy_send_moderation_email( $user, $action );
		}
	}

	/**
	 * Send moderation email (legacy)
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for user being moderated.
	 * @param string $action either 'deny' or 'approve'
	 *
	 */
	public function legacy_send_moderation_email( $user, $action ) {
		$subject = '';
		$message = '';

		if ( 'deny' === $action ) {
			$subject = __( 'Membership Denied', 'bp-registration-options' );
			$message = get_option( 'bprwg_denied_message' );
		}

		if ( 'approve' === $action ) {
			$subject = __( 'Membership Approved', 'bp-registration-options' );
			$message = get_option( 'bprwg_approved_message' );
		}

		$mailme = array(
			'user_email' => $user->data->user_email,
			'user_subject' => $subject,
			'user_message' => str_replace( '[username]', $user->data->user_login, wpautop( $message ) ),
		);

		/**
		 * Filters the email arguments before mailing.
		 *
		 * @since 4.2.0
		 *
		 * @param array  $emailme Array of email arguments for wp_mail.
		 * @param object $user User object for user being moderated.
		 */
		$mailme_filtered = apply_filters( 'bpro_hook_before_email', $mailme, $user );

		add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

		wp_mail( $mailme_filtered['user_email'], $mailme_filtered['user_subject'], $mailme_filtered['user_message'] );

		remove_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

	}

	/**
	 * Send moderation email (BP)
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for user being moderated.
	 * @param string $action either 'deny' or 'approve'
	 *
	 */
	public function bp_send_moderation_email( $user, $action ) {

		$email_type = '';
		if ( 'deny' === $action ) {
			$email_type = 'account_denied';
		}

		if ( 'approve' === $action ) {
			$email_type = 'account_approved';
		}

		bp_send_email( $email_type, $user->user_email );
	}

	/**
	 * Send an email to the administrator email upon new user registration.
	 *
	 * checks if new BP Emails are available and calls the legacy implementation otherwise
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for registered user
	 *
	 */
	public function send_admin_email( $user ) {
		if ( $this->bp_emails_available() ) {
			$this->bp_send_admin_email( $user );
		} else {
			$this->legacy_send_admin_email( $user );
		}
	}

	/**
	 * Send an email to the administrator email upon new user registration. (legacy)
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for registered user
	 *
	 */
	public function legacy_send_admin_email( $user ) {

		$message = get_option( 'bprwg_admin_pending_message' );
		$message = str_replace( '[username]', $user->data->user_login, $message );
		$message = str_replace( '[user_email]', $user->data->user_email, $message );

		// Add HTML capabilities temporarily.
		add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

		bp_registration_options_send_admin_email(
			array(
				'user_login' => $user->data->user_login,
				'user_email' => $user->data->user_email,
				'message'    => $message,
			)
		);
	}

	/**
	 * Send an email to the administrator email upon new user registration. (BP)
	 *
	 * @since 4.4.0
	 *
	 * @param object $user User object for registered user
	 *
	 */
	public function bp_send_admin_email( $user ) {

		// add tokens to parse in email
		$args = array(
			'tokens' => array(
				'username' => $user->data->user_login,
				'user_email' => $user->data->user_email,
			),
		);

		/**
		 * Filters the email address(es) to send admin notifications to.
		 *
		 * @since 4.3.0
		 *
		 * @param array $value Array of email addresses to send notification to.
		 */
		$admin_email = apply_filters( 'bprwg_admin_email_addresses', array( get_bloginfo( 'admin_email' ) ) );

		bp_send_email( 'admin_pending', $admin_email, $args );
	}

	/**
	 * Send an email to the pending user upon registration.
	 *
	 * checks if new BP Emails are available and calls the legacy implementation otherwise
	 *
	 * @since 4.4.0
	 *
	 * @param WP_User $user_info  WP_User object for the newly activated user.
	 *
	 */
	public function send_pending_user_email( $user ) {
		if ( $this->bp_emails_available() ) {
			$this->bp_send_pending_user_email( $user );
		} else {
			$this->legacy_send_pending_user_email( $user );
		}
	}

	/**
	 * Send an email to the pending user upon registration. (legacy)
	 *
	 *
	 * @since 4.4.0
	 *
	 * @param WP_User $user_info  WP_User object for the newly activated user.
	 *
	 */
	public function legacy_send_pending_user_email( $user_info ) {
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

	/**
	 * Send an email to the pending user upon registration. (BP)
	 *
	 *
	 * @since 4.4.0
	 *
	 * @param WP_User $user  WP_User object for the newly activated user.
	 *
	 */
	public function bp_send_pending_user_email( $user ) {
		bp_send_email( 'user_pending', $user->ID );
	}

}

BP_Registration_Emails::instance();