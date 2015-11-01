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
		/*
		 * Set 1 user at pending status, check return value for 1.
		 * Set X users at pending status, check return value for X users.
		 */
	}

	/**
	 * Tests pending users.
	 */
	public function test_get_pending_users() {
		/*
		 * Set X users, at pending status
		 * Check return value.
		 */

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
		 * Pass in dummby $_POST data. Check if matching values exist.
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
		/*
		 * Boolean: wp_style_is( $handle, $list = 'enqueued' )
		 */
	}
}
