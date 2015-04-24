<?php
/*
Plugin Name: BP Registration Options
Plugin URI: http://wordpress.org/plugins/bp-registration-options/
Description: This BuddyPress extension allows you to enable user moderation for new members, as well as help create a private network for your users. If moderation is enabled, any new members will be denied access to your BuddyPress and bbPress areas on your site, with the exception of their own user profile. They will be allowed to edit and configure that much. They will also not be listed in the members lists on the frontend until approved. Custom messages are available so you can tailor them to the tone of your website and community. When an admin approves or denies a user, email notifications will be sent to let them know of the decision.
Version: 4.2.6
Author: Brian Messenlehner and Michael Beckwith of WebDevStudios
Author URI: http://webdevstudios.com/about/
Licence: GPLv3
Text Domain: bp-registration-options
*/

define( 'BP_REGISTRATION_OPTIONS_VERSION', '4.2.6' );

/**
 * Loads BP Registration Options files only if BuddyPress is present
 *
 * @package BP-Registration-Options
 *
 */
function bp_registration_options_init() {

	# Not using bp_includes because we want to be able to be run with just bbPress as well.
	if ( function_exists( 'buddypress' ) ) {
		$bp = buddypress();
	}

	if ( function_exists( 'bbpress' ) ) {
		$bbp = bbpress();
	}

	if (
	    ( isset( $bp ) && version_compare( $bp->version, '1.7.0', '>=' ) ) ||
	    ( isset( $bbp ) && version_compare( $bbp->version, '2.0.0', '>=' ) )
	   ) {
		require( dirname( __FILE__ ) . '/bp-registration-options.php' );
		$bp_registration_options = new BP_Registration_Options;
		$bp_registration_compatibility = new BP_Registration_Compatibility;
	}

}
add_action( 'init', 'bp_registration_options_init' );
