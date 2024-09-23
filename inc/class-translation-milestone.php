<?php
/**
 * This class sends an email to translators who reached a translation milestone.
 *
 * @package wporg-gp-customizations
 */

namespace WordPressdotorg\GlotPress\Engagement;

use GP;
use GP_Translation;

/**
 * Sends an email to translators who reached a translation milestone.
 */
class Translation_Milestone {

	/**
	 * Milestones to send the notification.
	 *
	 * @var array
	 */
	private array $milestones = array(
		10,
		50,
		100,
		500,
		1000,
		5000,
		10000,
		50000,
		100000,
		500000,
		1000000,
	);

	/**
	 * Send an email to translators who reached a translation milestone.
	 *
	 * @param GP_Translation|null $translation The translation that was saved.
	 *
	 * @return void
	 */
	public function __invoke( ?GP_Translation $translation ) {
		if ( ! $this->checks_ok( $translation ) ) {
			return;
		}
		$milestone = $this->is_milestone( $translation );
		if ( ! $milestone ) {
			return;
		}
		$this->send_email_to_translator( $translation, $milestone );
		$this->send_slack_notification( $translation, $milestone );
	}

		/**
		 * Check if the translation is valid to send the notification.
		 *
		 * @param GP_Translation|null $translation The translation.
		 *
		 * @return bool
		 */
	private function checks_ok( ?GP_Translation $translation ): bool {
		if ( ! $translation ) {
			return false;
		}
		if ( 'current' !== $translation->status ) {
			return false;
		}
		if ( ! $translation->user_id ) {
			return false;
		}
		if ( ! $translation->user_id_last_modified ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the translation is a milestone and return the milestone number.
	 * If it's not a milestone, return false.
	 *
	 * @param GP_Translation|null $translation The translation.
	 *
	 * @return bool|int
	 */
	private function is_milestone( ?GP_Translation $translation ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$approved_translations_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->gp_translations} WHERE user_id = %d AND status = 'current'",
				$translation->user_id
			)
		);
		if ( in_array( (int) $approved_translations_count, $this->milestones, true ) ) {
			return (int) $approved_translations_count;
		} else {
			return false;
		}
	}

	/**
	 * Send an email to the translator.
	 *
	 * @param GP_Translation $translation The translation.
	 * @param int            $milestone   The milestone reached.
	 *
	 * @return void
	 */
	private function send_email_to_translator( GP_Translation $translation, int $milestone ) {
		$user = get_userdata( $translation->user_id );
		// translators: Email subject.
		$subject = sprintf( esc_html__( 'Your have reached a new translation milestone: %d translations', 'wporg-gp-customizations' ), $milestone );
		$message = sprintf(
		// translators: Email body. %1$s: Display name. %2$s: Translation URL. %3$s: Project URL.
			'
Congratulations %1$s,
<br><br>
You have reached a new milestone: %2$d translations at translate.wordpress.org! 🎉
<br>
Thank you for your contributions to the WordPress community. Keep up the good work!
<br><br>
Have a nice day
<br><br>
The Global Polyglots Team',
			$user->display_name,
			$milestone
		);

		$allowed_html = array(
			'br' => array(),
		);

		$message = wp_kses( $message, $allowed_html );

		$email = new Notification();
		$email->send_email( $user, $subject, $message );
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param GP_Translation $translation The translation.
	 * @param int            $milestone   The milestone reached.
	 *
	 * @return void
	 */
	private function send_slack_notification( GP_Translation $translation, int $milestone ) {
		$user = get_userdata( $translation->user_id );

		// translators: Slack message. %s: Display name. %d: Milestone.
		$message = sprintf(
			'We have sent a new message to *%s* about a milestone translation: %d translation approved.',
			$user->display_name,
			$milestone,
		);

		// Todo: Update to use a dedicated channel.
		slack_dm( $message, '@amieiro' );
	}
}
