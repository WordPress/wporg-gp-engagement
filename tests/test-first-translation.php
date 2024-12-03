<?php
/**
 * Tests for the First_Translation class.
 *
 * @package wporg-gp-engagement
 */

namespace Wporg\Tests;

/**
 * Test the First_Translation class.
 */
class First_Translation_Test extends Base_Test {
	/**
	 * Test the first translation notification.
	 */
	public function test_first_translation_notification() {
		$user         = $this->factory->user->create();
		$approver     = $this->factory->user->create();
		$translation1 = $this->factory->translation->create(
			array(
				'status'  => 'waiting',
				'user_id' => $user,
			)
		);
		$translation1->update(
			array(
				'user_id_last_modified' => $approver,
				'status'                => 'current',
			)
		);
		$translation1 = \GP::$translation->get( $translation1->id );

		$first_translation = new \WordPressdotorg\GlotPress\Engagement\First_Translation();
		remove_all_actions( 'wporg_translate_notification_first_translation' );
		remove_all_actions( 'wporg_translate_notification_summary_first_translation' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_first_translation', array( $mock, 'action' ), 10, 2 );

		$first_translation( $translation1 );

		$this->assertEquals( 1, $mock->get_call_count() );

		$translation2 = $this->factory->translation->create(
			array(
				'status'  => 'current',
				'user_id' => $user,
			)
		);

		$first_translation( $translation1 );

		$this->assertEquals( 1, $mock->get_call_count() );
	}

	/**
	 * Test the first translation hook notification.
	 */
	public function test_first_translation_hook_notification() {
		$first_translation = new \WordPressdotorg\GlotPress\Engagement\First_Translation();
		add_action( 'gp_translation_saved', array( $first_translation, '__invoke' ) );

		remove_all_actions( 'wporg_translate_notification_first_translation' );
		remove_all_actions( 'wporg_translate_notification_summary_first_translation' );

		$mock = new \MockAction();
		add_action( 'wporg_translate_notification_first_translation', array( $mock, 'action' ), 10, 2 );

		$user         = $this->factory->user->create();
		$approver     = $this->factory->user->create();
		$translation1 = $this->factory->translation->create(
			array(
				'status'  => 'waiting',
				'user_id' => $user,
			)
		);
		$this->assertEquals( 0, $mock->get_call_count() );
		$translation1->save(
			array(
				'user_id_last_modified' => $approver,
				'status'                => 'current',
			)
		);
		$translation1 = \GP::$translation->get( $translation1->id );

		$this->assertEquals( 1, $mock->get_call_count() );

		$translation2 = $this->factory->translation->create(
			array(
				'status'  => 'current',
				'user_id' => $user,
			)
		);

		$first_translation( $translation1 );

		$this->assertEquals( 1, $mock->get_call_count() );
	}
}
