<?php

class BP_Registration_Options {
	function __construct() {
		// Define plugin constants
		$this->version = BP_REGISTRATION_OPTIONS_VERSION;
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		//$this->directory_url = plugins_url( 'bp-registration-options/' );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		require_once( $this->directory_path . 'includes/admin.php' );
		require_once( $this->directory_path . 'includes/core.php' );
		require_once( $this->directory_path . 'includes/compatibility.php' );

		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Activation hook for the plugin.
	 */
	function activate() {
		$this->includes();

		//verify user is running WP 3.0 or newer
	    if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) ); // Deactivate our plugin
	        wp_die( __( 'This plugin requires WordPress version 3.0 or higher.', 'bp-registration-options' ) );
	    }
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook for the plugin.
	 */
	function deactivate() {
		flush_rewrite_rules();
	}

	function load_textdomain() {
		load_plugin_textdomain( 'bp-registration-options', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}
