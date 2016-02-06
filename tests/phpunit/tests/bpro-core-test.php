<?php
/**
 * BP Registration Options Tests
 *
 * @package BP-Registration-Options-Tests
 */

/**
 * Tests our core functionality.
 *
 * @since 4.3.0
 */
class bpro_core_test extends WP_UnitTestCase {

	/**
	 * Tests the return values from moderation status.
	 *
	 * Based on boolean return value compared to text in user meta.
	 */
	public function test_get_moderation_status() {
		$u1 = $this->factory->user->create();
		$return = bp_registration_set_moderation_status( $u1 );
		$this->assertTrue( bp_registration_get_moderation_status( $u1 ) );
		$return = bp_registration_set_moderation_status( $u1, 'false' );
		$this->assertFalse( bp_registration_get_moderation_status( $u1 ) );
	}

	/**
	 * Tests the setting of moderation status.
	 *
	 * Return value is the integer of the user meta row created if successful,
	 * thus why we're checking for is_int for legit users.
	 */
	public function test_set_moderation_status() {
		$u1 = $this->factory->user->create();

		// Non existing user.
		$invalid_user = bp_registration_set_moderation_status( 42 );
		$this->assertFalse( $invalid_user );

		// No user meta set yet.
		$empty = bp_registration_get_moderation_status( $u1 );
		$this->assertFalse( $empty );

		// Setting to moderated.
		$set1 = bp_registration_set_moderation_status( $u1 );
		$this->assertTrue( is_int( $set1 ) );

		// Setting to not moderated.
		$set2 = bp_registration_set_moderation_status( $u1, 'false' );
		$this->assertTrue( is_int( $set2 ) );
	}

	public function test_admin_bar_additions() {
		/*
		 * Check if we have registered admin bar items.
		 */
	}
	public function test_register_components() {

		$this->markTestIncomplete(
			'This test needs more research.'
		);

		$this->assertFalse( isset( buddypress()->active_components['bp_registration_options'] ) );

		$submitted = array( 'bp_registration_options' => 1 );
		bp_core_admin_get_active_components_from_submitted_settings( $submitted );

		$components = bp_registration_options_get_registered_components( buddypress()->active_components );

		$this->assertTrue( isset( buddypress()->active_components['bp_registration_options'] ) );

		/*
		 * Check if we're properly registering out component.
		 */
	}
}
