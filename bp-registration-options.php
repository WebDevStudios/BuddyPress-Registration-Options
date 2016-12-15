<?php
/**
 * BP-Registration-Options Core Real Initialization.
 *
 * @package BP-Registration-Options
 */

/**
 * Does all the actual loading.
 */
class BP_Registration_Options {

	/**
	 * Current version.
	 *
	 * @since unknown
	 * @var string
	 */
	public $version = '';

	/**
	 * Plugin basename.
	 *
	 * @since unknown
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin directory server path.
	 *
	 * @since unknown
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Piece it all together
	 */
	function __construct() {
		// Define plugin constants.
		$this->version = BP_REGISTRATION_OPTIONS_VERSION;
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		require_once( $this->directory_path . 'includes/utility.php' );
		require_once( $this->directory_path . 'includes/admin.php' );
		require_once( $this->directory_path . 'includes/core.php' );
		require_once( $this->directory_path . 'includes/compatibility.php' );

		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Activation hook for the plugin.
	 */
	function activate() {
		// Verify user is running WP 3.0 or newer.
	    if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) );
	        wp_die( esc_html__( 'This plugin requires WordPress version 3.0 or higher.', 'bp-registration-options' ) );
	    }
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook for the plugin.
	 */
	function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Load our textdomain
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'bp-registration-options', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}
