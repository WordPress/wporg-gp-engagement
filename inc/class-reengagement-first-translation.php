<?php
/**
 * This class sends an email to translators who for the first time had a translation approved.
 *
 * @package wporg-gp-customizations
 */

namespace WordPressdotorg\GlotPress\Engagement;

use GP;
use GP_Translation;
use function Automattic\Jetpack\Creative_Mail\error_notice;
use function cli\err;

/**
 * Sends an email to translators who for the first time had a translation approved.
 */
class Reengagement_First_Translation {
	/**
	 * Send an email to translators who for the first time today had a translation approved.
	 *
	 * @param GP_Translation|null $translation The translation that was saved.
	 *
	 * @return void
	 */
	public function __invoke( ?GP_Translation $translation ) {
		if ( ! $this->checks_ok( $translation ) ) {
			return;
		}
		if ( $this->has_the_notification_been_sent( $translation ) ) {
			return;
		}
		if ( $this->date_of_first_approved_translation( $translation->user_id ) !== gmdate( 'Y-m-d' ) ) {
			return;
		}

		$this->send_email_to_translator( $translation );
		$this->update_user_option( $translation->user_id );
		$this->send_slack_notification( $translation );
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
		if ( $translation->user_id === $translation->user_id_last_modified ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the notification has been sent, so we don't send it again.
	 *
	 * @param GP_Translation $translation The translation.
	 *
	 * @return bool
	 */
	private function has_the_notification_been_sent( GP_Translation $translation ): bool {
		$reengagement_options = get_user_option( 'gp_engagement', $translation->user_id );
		$reengagement_options = $reengagement_options ? $reengagement_options : array();
		return ! empty( $reengagement_options['first_translation_approved_date'] );
	}

	/**
	 * Get the date of the first approved translation for a user.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return string
	 */
	private function date_of_first_approved_translation( int $user_id ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(date_modified) FROM `{$wpdb->gp_translations}` WHERE user_id = %d",
				$user_id
			)
		);

		return gmdate( 'Y-m-d', strtotime( $result ) );
	}

	/**
	 * Send an email to the translator.
	 *
	 * @param GP_Translation $translation The translation.
	 *
	 * @return void
	 */
	private function send_email_to_translator( GP_Translation $translation ) {
		$notification_elements = $this->get_notification_elements( $translation );
		if ( false === $notification_elements ) {
			return;
		}
		list( $user, $translation_url, $project_url ) = $notification_elements;

		// translators: Email subject.
		$subject = __( 'Your first translation has been approved!', 'wporg' );
		$message = sprintf(
		// translators: Email body. %1$s: Display name. %2$s: Translation URL. %3$s: Project URL.
			'
Congratulations %1$s,
<br><br>
Your <a href="%2$s" target="_blank">first translation</a> at WordPress.org,
included in <a href="%3$s" target="_blank">this project</a>, has been approved. Keep up the great work!
<br><br>
Have a nice day
<br><br>
The Global Polyglots Team
',
			$user->display_name,
			$translation_url,
			$project_url
		);

		$allowed_html = array(
			'a'  => array(
				'href'   => array(),
				'target' => array(),
			),
			'br' => array(),
		);

		$message = wp_kses( $message, $allowed_html );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Translating WordPress.org <no-reply@wordpress.org>',
		);

		wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param GP_Translation $translation The translation.
	 *
	 * @return void
	 */
	private function send_slack_notification( GP_Translation $translation ) {
		$notification_elements = $this->get_notification_elements( $translation );
		if ( false === $notification_elements ) {
			return;
		}
		list( $user, $translation_url, $project_url ) = $notification_elements;

		// translators: Slack message.
		$message = sprintf(
			"We have sent a new message to *%s* about the first translation:\n- <%s|Translation>.\n- <%s|Project>.",
			$user->display_name,
			$translation_url,
			$project_url
		);

		// Todo: Update to use a dedicated channel.
		slack_dm( $message, '@amieiro', false );
	}

	/**
	 * Insert the user option to avoid sending the notification again.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return void
	 */
	private function update_user_option( int $user_id ) {
		$reengagement_options['first_translation_approved_date'] = gmdate( 'Y-m-d' );
		update_user_option( $user_id, 'gp_engagement', $reengagement_options );
	}
	/**
	 * Get the elements for the notification.
	 *
	 * @param GP_Translation $translation The translation.
	 *
	 * @return array|false
	 */
	private function get_notification_elements( GP_Translation $translation ) {
		$user = get_user_by( 'id', $translation->user_id );
		if ( ! $user ) {
			return false;
		}

		$original = GP::$original->get( $translation->original_id );
		if ( ! $original ) {
			return false;
		}

		$project = GP::$project->get( $original->project_id );
		if ( ! $project ) {
			return false;
		}

		$translation_set = GP::$translation_set->get( $translation->translation_set_id );
		if ( ! $translation_set ) {
			return false;
		}

		$translation_url = gp_url_join( gp_url_public_root(), 'projects', $project->path, $translation_set->locale, '/', $translation_set->slug ) . '?filters%5Bstatus%5D=either&filters%5Boriginal_id%5D=' . $original->id . '&filters%5Btranslation_id%5D=' . $translation->id;
		$project_url     = gp_url_join( gp_url_public_root(), 'projects', $project->path, $translation_set->locale, '/', $translation_set->slug );

		return array(
			$user,
			$translation_url,
			$project_url,
		);
	}
}
