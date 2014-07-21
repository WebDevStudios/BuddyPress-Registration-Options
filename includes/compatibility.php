<?php
class BP_Registration_Compatibility {

	public function __construct() {
		//WP FB AutoConnect
		global $jfb_name;

		if ( !empty( $jfb_name) ) {
			add_action( 'wpfb_inserted_user', array( $this, 'wp_fb_autoconnect_compat' ) );
		}

	}

	/*
	Adds compatibility to http://wordpress.org/plugins/wp-fb-autoconnect/
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

}
