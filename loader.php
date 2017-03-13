<?php
/**
 * WordPress plugin loader file.
 *
 * @package BP-Registration-Options
 */

/**
 * Plugin Name: BP Registration Options
 * Plugin URI: https://pluginize.com
 * Description: This BuddyPress extension allows you to enable user moderation for new members, as well as help create a private network for your users. If moderation is enabled, any new members will be denied access to your BuddyPress and bbPress areas on your site, with the exception of their own user profile. They will be allowed to edit and configure that much. They will also not be listed in the members lists on the frontend until approved. Custom messages are available so you can tailor them to the tone of your website and community. When an admin approves or denies a user, email notifications will be sent to let them know of the decision.
 * Version: 4.3.2
 * Author: Pluginize
 * Author URI: https://pluginize.com
 * Licence: GPLv3
 * Text Domain: bp-registration-options
 */

define( 'BP_REGISTRATION_OPTIONS_VERSION', '4.3.2' );

/**
 * Loads BP Registration Options files only if BuddyPress is present.
 */
function bp_registration_options_init() {

	$bp = '';
	$bbp = '';

	// Not using bp_includes because we want to be able to be run with just bbPress as well.
	if ( function_exists( 'buddypress' ) ) {
		$bp = buddypress();
	}

	if ( function_exists( 'bbpress' ) ) {
		$bbp = bbpress();
	}

	if ( bp_registration_should_init( $bp, $bbp ) ) {
		require_once( dirname( __FILE__ ) . '/bp-registration-options.php' );
		$bp_registration_options = new BP_Registration_Options;

		add_action( 'init', 'bp_registration_options_compat_init' );
	}
}
add_action( 'plugins_loaded', 'bp_registration_options_init' );

/**
 * Loads the BP Registration Options Compatibility features.
 *
 * @since 4.2.8
 */
function bp_registration_options_compat_init() {
	return new BP_Registration_Compatibility;
}

/**
 * Checks if we should init our settings and code.
 *
 * @since 4.2.8
 *
 * @param object|string $bp  BuddyPress instance, if available.
 * @param object|string $bbp bbPress instance, if available.
 *
 * @return bool
 */
function bp_registration_should_init( $bp = '', $bbp = '' ) {
	if (
	    ( is_object( $bp ) && version_compare( $bp->version, '1.7.0', '>=' ) ) ||
	    ( is_object( $bbp ) && version_compare( $bbp->version, '2.0.0', '>=' ) )
	   ) {
		return true;
	}

	return false;
}
