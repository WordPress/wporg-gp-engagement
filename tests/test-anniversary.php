<?php
namespace WordPressdotorg\GlotPress\Engagement;

class Anniversary_Test extends \GP_UnitTestCase {
	public function test_anniversary() {
		$user = $this->factory->user->create();
		$translation = $this->factory->translation->create( array(
			'status' => 'current',
			'locale' => 'en',
			'translation_set_id' => 1,
			'user_id' => $user,
			'user_id_last_modified' => $user,
		) );

		remove_all_actions( 'wporg_translate_notification_milestone' );
		remove_all_actions( 'wporg_translate_notification_summary_milestone' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_milestone', array( $mock, 'action' ), 10, 2 );

		$translation_milestone = new Translation_Milestone();
		$translation_milestone( $translation );

		// Ensure the email was sent.
		$this->assertEquals( 1, $mock->get_call_count() );
	}
}
