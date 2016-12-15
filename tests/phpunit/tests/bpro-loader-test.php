<?php
/**
 * BP Registration Options Tests
 *
 * @package BP-Registration-Options-Tests
 */

/**
 * Tests our functions found in the loader.php file
 *
 * @since 4.3.0
 */
class bpro_loader_test extends WP_UnitTestCase {

	/**
	 * Test if we return appropriate instances.
	 */
	public function test_bp_registration_options_compat_init() {
		$compat = bp_registration_options_compat_init();
		$this->assertInstanceOf( 'BP_Registration_Compatibility', $compat );
	}

	/**
	 * Test if we meet requirements to load anything.
	 */
	public function test_bp_registration_should_init() {
		$bp = buddypress();
		$bbp = bbpress();

		$original_bp = $bp->version;
		$original_bbp = $bbp->version;

		// Both meet requirements.
		$this->assertTrue( bp_registration_should_init( $bp, $bbp ) );

		$bp->version = '1.6.0';
		$bbp->version = '1.9.0';

		// Neither meet requirements.
		$this->assertFalse( bp_registration_should_init( $bp, $bbp ) );

		$bp->version = $original_bp;
		$bbp->version = '1.9.0';
		// When BuddyPress meets requirements.
		$this->assertTrue( bp_registration_should_init( $bp, $bbp ) );

		$bp->version  = '1.6.0';
		$bbp->version = $original_bbp;
		// When bbPress meets requirements.
		$this->assertTrue( bp_registration_should_init( $bp, $bbp ) );
	}
}
