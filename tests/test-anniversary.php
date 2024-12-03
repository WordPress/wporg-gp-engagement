<?php
/**
 * Tests for the Anniversary class.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

/**
 * Test the Anniversary class.
 */
class Anniversary_Test extends \GP_UnitTestCase {
	/**
	 * Data provider for the anniversary tests.
	 *
	 * @return array
	 */
	public function anniversary_data_provider() {
		return array(
			'today'         => array( time(), 0 ),
			'1 years ago'   => array( strtotime( '-1 year' ), 1 ),
			'1.5 years ago' => array( strtotime( '-1.5 year' ), 0 ),
			'2 years ago'   => array( strtotime( '-2 year' ), 1 ),
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
	public function test_anniversary( $date, $expected ) {

		$user = $this->factory->user->create();

		$translation = $this->factory->translation->create(
			array(
				'status'                => 'current',
				'locale'                => 'en',
				'translation_set_id'    => 1,
				'user_id'               => $user,
				'user_id_last_modified' => $user,
			)
		);

		$translation->update( array( 'date_added' => gmdate( 'Y-m-d H:i:s', $date ) ) );

		remove_all_actions( 'wporg_translate_notification_milestone' );
		remove_all_actions( 'wporg_translate_notification_summary_milestone' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_anniversary', array( $mock, 'action' ), 10, 2 );

		$anniversary = new Anniversary();
		$anniversary();

		$this->assertEquals( $expected, $mock->get_call_count() );
	}
}
