<?php
/**
 * This class sends an email to translators who reached a translation milestone.
 *
 * @package wporg-gp-engagement
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wporg_translate_notification_milestone', array( $this, 'send_email_to_translator' ), 10, 2 );
		add_action( 'wporg_translate_notification_summary_milestone', array( $this, 'send_slack_notification' ), 10, 2 );
	}

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
		do_action( 'wporg_translate_notification_milestone', $translation, $milestone );
		do_action( 'wporg_translate_notification_summary_milestone', $translation, $milestone );
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
	public function send_email_to_translator( GP_Translation $translation, int $milestone ) {
		$user    = get_userdata( $translation->user_id );
		$subject = sprintf(
		// translators: Email subject.
			esc_html__( 'Thank you for contributing %s translations! 🏆', 'wporg-gp-engagement' ),
			number_format_i18n( $milestone )
		);
		$message = sprintf(
		// translators: Email body. %1$s: Display name. %2$s: Number of translations achieved.
			'
Dear %1$s,
<br><br>
we have noticed that you have been contributing translations to translate.wordpress.org, thank you so much for that!
<br><br>
It turns out, today you contributed your %2$sth translation! This is amazing! Congratulations for reaching this milestone!
<br><br>
Thank you so much and looking forward to you reaching the next milestone,
<br><br>
The Global Polyglots Team',
			$user->display_name,
			number_format_i18n( $milestone )
		);

		$allowed_html = array(
			'br' => array(),
		);

		$message = wp_kses( $message, $allowed_html );

		do_action( 'wporg_translate_notification_email', $user, $subject, $message );
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param GP_Translation $translation The translation.
	 * @param int            $milestone   The milestone reached.
	 *
	 * @return void
	 */
	public function send_slack_notification( GP_Translation $translation, int $milestone ) {
		$user = get_userdata( $translation->user_id );

		// translators: Slack message. %s: Display name. %d: Milestone.
		$message = sprintf(
			'We have sent a new message to *%s* about a milestone translation: %s translations approved.',
			$user->display_name,
			number_format_i18n( $milestone ),
		);

		do_action( 'wporg_translate_notification_slack', $message );
	}
}
