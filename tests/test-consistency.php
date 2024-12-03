<?php
/**
 * Tests for the Consistency class.
 *
 * @package wporg-gp-engagement
 */

namespace Wporg\Tests;

/**
 * Test the Consistency class.
 */
class Consistency_Test extends Base_Test {
	/**
	 * Data provider for the consistency tests.
	 *
	 * @return array
	 */
	public function consistency_data_provider() {
		return array(
			'3 months' => array( 3, 3 ),
			'6 months' => array( 6, 2 ),
			'12 months' => array( 12, 1 ),
		);
	}

	/**
	 * Test notifications for consistently translating users.
	 *
	 * @dataProvider consistency_data_provider
	 *
	 * @param int $months The months to check.
	 * @param int $expected The expected number of users.
	 */
	public function test_users_with_consistent_translations( $months, $expected ) {
		foreach (
			array(
				// in which previous months did these users have translations?
				array( 1, 2, 3, 4, 5, 6 ),
				array( 1, 2, 3, 5, 6 ),
				array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ),
				array( 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ),
			) as $generate_months ) {
			$user = $this->factory->user->create();
			foreach ( $generate_months as $month ) {
				$translation = $this->factory->translation->create( array( 'user_id' => $user ) );
				$translation->update( array( 'date_added' => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $month . ' months' ) ) ) );
			}
		}

		$consistency = new \WordPressdotorg\GlotPress\Engagement\Consistency();
		remove_all_actions( 'wporg_translate_notification_consistency' );
		remove_all_actions( 'wporg_translate_notification_summary_consistency' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_consistency', array( $mock, 'action' ), 10, 2 );

		$consistency->months_to_notify = array( $months );
		$consistency();

		$this->assertEquals( $expected, $mock->get_call_count() );
	}
}
