<?php
/**
 * Base test class.
 *
 * @package wporg-gp-engagement
 */

namespace Wporg\Tests;

use GP_UnitTestCase;

/**
 * Base test class.
 */
abstract class Base_Test extends GP_UnitTestCase {
	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Don't send anything.
		remove_all_actions( 'wporg_translate_notification_email' );
		remove_all_actions( 'wporg_translate_notification_slack' );

		add_action( 'wporg_translate_notification_email', array( $this, 'never_call_me' ) );
		add_action( 'wporg_translate_notification_slack', array( $this, 'never_call_me' ) );
	}

	/**
	 * Should never be called.
	 */
	public function never_call_me() {
		$this->fail( sprintf( 'The hook %s should never be called.', current_action() ) );
	}
}
