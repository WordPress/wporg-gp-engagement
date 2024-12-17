<?php
/**
 * Tests for the Anniversary class.
 *
 * @package wporg-gp-engagement
 */

namespace Wporg\Tests;

/**
 * Test the Anniversary class.
 */
class Anniversary_Test extends Base_Test {
	/**
	 * Data provider for the anniversary tests.
	 *
	 * @return array
	 */
	public function anniversary_data_provider() {
		return array(
			'today'         => array( time(), 0, 0 ),
			'1 years ago'   => array( strtotime( '-1 year' ), 1, 1 ),
			'1.5 years ago' => array( strtotime( '-1.5 year' ), 0, 0 ),
			'2 years ago'   => array( strtotime( '-2 year' ), 1, 1 ),
		);
	}

	/**
	 * Test the anniversary notification
	 *
	 * @dataProvider anniversary_data_provider
	 *
	 * @param int $date     The date to test.
	 * @param int $expected The expected number of calls to the mock action.
	 */
	public function test_anniversary( $date, $expected, $expected_summary ) {

		$user = $this->factory->user->create();

		$translation = $this->factory->translation->create(
			array(
				'status'  => 'current',
				'user_id' => $user,
			)
		);
		$translation->update( array( 'date_added' => gmdate( 'Y-m-d H:i:s', $date ) ) );

		$anniversary = new \WordPressdotorg\GlotPress\Engagement\Anniversary();
		remove_all_actions( 'wporg_translate_notification_anniversary' );
		remove_all_actions( 'wporg_translate_notification_summary_anniversary' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_anniversary', array( $mock, 'action' ), 10, 2 );
		add_action( 'wporg_translate_notification_summary_anniversary', array( $mock, 'action' ) );

		$anniversary();

		$this->assertEquals( $expected, $mock->get_call_count('wporg_translate_notification_anniversary') );
		$this->assertEquals( $expected_summary, $mock->get_call_count('wporg_translate_notification_summary_anniversary') );
	}
}
