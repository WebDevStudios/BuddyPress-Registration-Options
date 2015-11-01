<?php
/**
 * BP Registration Options Tests
 *
 * @package BP-Registration-Options-Tests
 */

/**
 * Tests our file existence.
 *
 * @since 4.3.0
 */
class bpro_files_test extends WP_UnitTestCase {

	/**
	 * Test if have all our files.
	 */
	public function test_bp_registration_should_init() {
		$dir = plugin_dir_path( dirname( dirname( dirname( __FILE__ ) ) ) );
		echo $dir . 'bp-registration-options.php';
		$this->assertTrue( file_exists( $dir . 'bp-registration-options.php' ) );
		$this->assertTrue( file_exists( $dir . 'loader.php' ) );

		$this->assertTrue( file_exists( $dir . 'includes/admin.php' ) );
		$this->assertTrue( file_exists( $dir . 'includes/compatibility.php' ) );
		$this->assertTrue( file_exists( $dir . 'includes/core.php' ) );

		$this->assertTrue( file_exists( $dir . 'assets/bp-registration-options.css' ) );
	}
}
