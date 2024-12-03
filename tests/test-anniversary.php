<?php
namespace WordPressdotorg\GlotPress\Engagement;

class Anniversary_Test extends \GP_UnitTestCase {

	public function anniversary_data_provider() {
		return array(
			'today' => array( time(), 0 ),
			'1 years ago' => array( strtotime( '-1 year' ), 1 ),
			'1.5 years ago' => array( strtotime( '-1.5 year' ), 0 ),
			'2 years ago' => array( strtotime( '-2 year' ), 1 ),
		);
	}

	/**
	 * @dataProvider anniversary_data_provider
	 */
	public function test_anniversary( $date, $expected ) {

		$user = $this->factory->user->create();

		$translation = $this->factory->translation->create( array(
			'status' => 'current',
			'locale' => 'en',
			'translation_set_id' => 1,
			'user_id' => $user,
			'user_id_last_modified' => $user,
		) );

		$translation->update( array( 'date_added' => date( 'Y-m-d H:i:s', $date ) ) );

		remove_all_actions( 'wporg_translate_notification_milestone' );
		remove_all_actions( 'wporg_translate_notification_summary_milestone' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_anniversary', array( $mock, 'action' ), 10, 2 );

		$anniversary = new Anniversary();
		$anniversary();

		$this->assertEquals( $expected, $mock->get_call_count() );
	}
}
