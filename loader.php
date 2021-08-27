<?php
/**
 * WordPress plugin loader file.
 *
 * @package BP-Registration-Options
 */

/**
 * Plugin Name: BP Registration Options
 * Plugin URI: https://pluginize.com
 * Description: Enable user moderation and private BuddyPress and bbPress areas.
 * Version: 4.4.0
 * Author: Pluginize
 * Author URI: https://pluginize.com
 * Licence: GPLv3
 * Text Domain: bp-registration-options
 */

define( 'BP_REGISTRATION_OPTIONS_VERSION', '4.4.0' );

/**
 * Loads BP Registration Options files only if BuddyPress is present.
 */
function bp_registration_options_init() {

	$bp  = '';
	$bbp = '';

	// Not using bp_includes because we want to be able to be run with just bbPress as well.
	if ( function_exists( 'buddypress' ) ) {
		$bp = buddypress();
	}

	if ( function_exists( 'bbpress' ) ) {
		$bbp = bbpress();
	}

	if ( bp_registration_should_init( $bp, $bbp ) ) {
		require_once __DIR__ . '/bp-registration-options.php';
		$bp_registration_options = new BP_Registration_Options();

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
	return new BP_Registration_Compatibility();
}

/**
 * Checks if we should init our settings and code.
 *
 * @since 4.2.8
 * @since 4.4.0 Added BuddyBoss checking.
 *
 * @param object|string $bp  BuddyPress instance, if available.
 * @param object|string $bbp bbPress instance, if available.
 *
 * @return bool
 */
function bp_registration_should_init( $bp = '', $bbp = '' ) {

	$should_init = ( is_object( $bp ) && version_compare( $bp->version, '1.7.0', '>=' ) ) ||
	               ( is_object( $bbp ) && version_compare( $bbp->version, '2.0.0', '>=' ) );

	if ( defined( 'BP_PLATFORM_VERSION' ) ) {
		$should_init = version_compare( BP_PLATFORM_VERSION, '1.3.5', '>=' );
	}

	$should_init = (bool) apply_filters( 'bprwg_should_init', $should_init );

	return $should_init;
}
