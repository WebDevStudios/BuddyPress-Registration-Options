<?php
/**
 * BP Registration Options Tests
 *
 * @package BP-Registration-Options-Tests
 */

/**
 * Tests our admin functionality.
 *
 * @since 4.3.0
 */
class bpro_admin_test extends WP_UnitTestCase {

	/**
	 * Tests pending user count total.
	 */
	public function test_get_pending_user_count() {
		$none = bp_registration_get_pending_user_count();
		$this->assertTrue( empty( $none ) );

		$u1     = $this->factory->user->create();
		bp_registration_set_moderation_status( $u1 );

		delete_transient( 'bpro_user_count' );

		$one = bp_registration_get_pending_user_count();
		$this->assertFalse( empty( $one ) );
		$this->assertEquals( '1', $one );

		delete_transient( 'bpro_user_count' );

		$u2     = $this->factory->user->create();
		bp_registration_set_moderation_status( $u2 );
		$two = bp_registration_get_pending_user_count();

		$this->assertFalse( empty( $two ) );
		$this->assertEquals( '2', $two );
		$this->assertNotEquals( '3', $two );
	}

	/**
	 * Tests pending users.
	 */
	public function test_get_pending_users() {
		$none = bp_registration_get_pending_users();
		$this->assertTrue( is_array( $none ) );
		$this->assertTrue( empty( $none ) );

		$u1     = $this->factory->user->create();
		bp_registration_set_moderation_status( $u1 );

		$users_single = bp_registration_get_pending_users();
		$this->assertFalse( empty( $users_single ) );
		$this->assertEquals( $u1, $users_single[0]->user_id );

		$u2     = $this->factory->user->create();
		bp_registration_set_moderation_status( $u2 );

		$users_multiple = bp_registration_get_pending_users();

		$this->assertFalse( empty( $users_multiple ) );
		$this->assertEquals( $u1, $users_multiple[0]->user_id );
		$this->assertEquals( $u2, $users_multiple[1]->user_id );

	}

	/**
	 * Tests messages resetting.
	 */
	public function test_handle_reset_messages() {
		/*
		 * Set some option values. Run function, check for empty option values.
		 */
	}

	/**
	 * Tests general settings setting.
	 */
	public function test_handle_general_settings() {
		/*
		 * Pass in dummy $_POST data. Check if matching values exist.
		 *
		 * Pass in empty $_POST data. Check if options deleted. Checked values only.
		 */
	}

	/**
	 * Tests admin messages return in English.
	 */
	public function test_options_admin_messages() {
		/*
		 * Set a pending user count. Check return message. May need to ob_cache due to echo and action hook.
		 */
	}

	/**
	 * Tests stylesheet enqueue status.
	 */
	public function test_options_stylesheet() {

		$this->assertFalse( wp_style_is( 'bp-registration-options-stylesheet' ) );

		bp_registration_options_stylesheet();

		$this->assertTrue( wp_style_is( 'bp-registration-options-stylesheet' ) );
	}
}
