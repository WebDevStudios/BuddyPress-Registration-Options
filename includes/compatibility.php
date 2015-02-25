<?php
class BP_Registration_Compatibility {

	public function __construct() {
		//WP FB AutoConnect
		global $jfb_name;

		if ( !empty( $jfb_name) ) {
			add_action( 'wpfb_inserted_user', array( $this, 'wp_fb_autoconnect_compat' ) );
		}

		$this->buddypress_like();
		$this->buddypress_send_invites();

	}

	/*
	 * Adds compatibility support for WP-FB-AutoConnect.
	 * http://wordpress.org/plugins/wp-fb-autoconnect/
	 */
	function wp_fb_autoconnect_compat( $fbuser ) {

		$id = $fbuser['WP_ID'];

		//Hide activity created by new user
		//$sql = 'UPDATE ' . $wpdb->base_prefix . 'bp_activity SET hide_sitewide = 1 WHERE user_id = %d';
		//$wpdb->query( $wpdb->prepare( $sql, $id ) );

		//email admin about new member request
		$user = get_userdata( $id );

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

	/**
	 * Adds compatibility support for BuddyPress Like.
	 * https://wordpress.org/plugins/buddypress-like/
	 */
	function buddypress_like() {

		$user = get_current_user_id();
		$moderate = (bool) get_option( 'bprwg_moderate' );

		if ( empty( $moderate ) || ! $moderate ) {
			return;
		}

		if ( ! bp_registration_get_moderation_status( $user ) ) {
			return;
		}

		remove_filter( 'bp_activity_entry_meta' , 'bp_like_button', 1000 );
		remove_filter( 'bp_activity_comment_options' , 'bp_like_button', 1000 );
		remove_action( 'bp_before_blog_single_post' , 'bp_like_button' , 1000 );

		remove_action( 'bp_activity_filter_options' , 'bp_like_activity_filter' );
		remove_action( 'bp_group_activity_filter_options' , 'bp_like_activity_filter' );
		remove_action( 'bp_member_activity_filter_options' , 'bp_like_activity_filter' );

		remove_action( 'bp_setup_nav', 'invite_anyone_setup_nav' );
	}

	/**
	 * Adds compatibility support for BuddyPress Invite Anyone.
	 * https://wordpress.org/plugins/invite-anyone/
	 */
	function buddypress_send_invites() {

		$user = get_current_user_id();
		$moderate = (bool) get_option( 'bprwg_moderate' );

		if ( empty( $moderate ) || ! $moderate ) {
			return;
		}

		if ( ! bp_registration_get_moderation_status( $user ) ) {
			return;
		}

		remove_action( 'bp_setup_nav', 'invite_anyone_setup_nav' );
	}

}
