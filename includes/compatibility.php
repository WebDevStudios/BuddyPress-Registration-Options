<?php
/**
 * Compatibility fixes for various other BuddyPress related plugins.

 * @package BP-Registration-Options
 */

/**
 * Class BP_Registration_Compatibility
 *
 * Adds compatibility with other 3rd party plugins.
 */
class BP_Registration_Compatibility {

	/**
	 * Piece it all together.
	 */
	public function __construct() {
		// WP FB AutoConnect.
		global $jfb_name;

		if ( ! empty( $jfb_name ) ) {
			add_action( 'wpfb_inserted_user', array( $this, 'wp_fb_autoconnect_compat' ) );
		}

		$this->buddypress_like();
		$this->buddypress_send_invites();

		// Filter BuddyPress Docs capabilities.
		add_filter( 'bp_docs_map_meta_caps', array( $this, 'bp_docs_map_meta_caps' ), 100, 4 );
	}

	/**
	 * Adds compatibility support for WP-FB-AutoConnect.
	 * http://wordpress.org/plugins/wp-fb-autoconnect/
	 *
	 * @param array $fbuser Array hoding Facebook user data.
	 */
	function wp_fb_autoconnect_compat( $fbuser ) {

		$id = $fbuser['WP_ID'];

		// Hide activity created by new user
		// $sql = 'UPDATE ' . $wpdb->base_prefix . 'bp_activity SET hide_sitewide = 1 WHERE user_id = %d';
		// $wpdb->query( $wpdb->prepare( $sql, $id ) );
		// email admin about new member request.
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
				),
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

	/**
	 * Prevents moderated users from critical interactions with BP Docs
	 *
	 * @param array  $caps    Capabilities for meta capability.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User id.
	 * @param mixed  $args    Arguments passed to map_meta_cap filter.
	 * @return array $caps Capabilities for meta capability
	 */
	public function bp_docs_map_meta_caps( $caps, $cap, $user_id, $args ) {

		$moderate = (bool) get_option( 'bprwg_moderate' );
		if ( empty( $moderate ) || ! $moderate ) {
			return $caps;
		}

		if ( ! bp_registration_get_moderation_status( $user_id ) ) {
			return $caps;
		}

		// Do not allow these actions.
		switch ( $cap ) {
			case 'bp_docs_create' :
			case 'bp_docs_edit' :
			case 'bp_docs_manage' :
			case 'bp_docs_post_comments' :
				$caps = array( 'do_not_allow' );
				break;
		}

		return $caps;
	}
}
