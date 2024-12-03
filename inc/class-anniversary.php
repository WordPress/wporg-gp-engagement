<?php
/**
 * This class sends an email to translators in their translation anniversary.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Anniversary {
	public function __construct() {
		add_action( 'wporg_translate_notification_anniversary', array( $this, 'send_email_to_translator' ) );
		add_action( 'wporg_translate_notification_summary_anniversary', array( $this, 'send_slack_notification' ) );
	}

	/**
	 * Send an email to translators in their translation anniversary.
	 *
	 * @return void
	 */
	public function __invoke() {
		$all_users              = $this->get_users_and_first_translation_date();
		$anniversary_users      = $this->get_translators_in_anniversary( $all_users );
		$number_of_translations = array();
		foreach ( $anniversary_users as $user_id => $date ) {
			$number_of_translations[ $user_id ] = $this->get_number_of_translations( $user_id );
			do_action( 'wporg_translate_notification_anniversary', $user_id, $date, $number_of_translations[ $user_id ] );
		}

		do_action( 'wporg_translate_notification_summary_anniversary', $anniversary_users, $number_of_translations );
	}

	/**
	 * Get all users and the date of their first translation.
	 *
	 * @return array An array with the user_id as key and the date of the first translation as value.
	 */
	private function get_users_and_first_translation_date(): array {
		global $wpdb;
		$users = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$max_user_id = $wpdb->get_var( 'SELECT MAX(user_id) FROM translate_translations' );

		// Todo: change to 1.
		$first_id   = 21_000_000;
		$batch_size = 50_000;

		do {
			$query = $wpdb->prepare(
				"SELECT user_id, DATE(MIN(date_added)) AS min_date
				FROM `{$wpdb->gp_translations}`
				WHERE status = 'current'
				  AND user_id BETWEEN %d AND %d
				  AND YEAR(date_added) < YEAR(CURDATE())
				GROUP BY user_id",
				$first_id,
				$first_id + $batch_size - 1,
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$batch_users = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $batch_users as $user ) {
				$users[ $user['user_id'] ] = $user['min_date'];
			}
			$first_id += $batch_size;
		} while ( $first_id < $max_user_id );

		return $users;
	}

	/**
	 * Get the translators that have an anniversary today.
	 *
	 * @param array|null $users An array with the user_id as key and the date of the first translation as value.
	 *
	 * @return array An array with the user_id as key and the date of the first translation as value.
	 */
	private function get_translators_in_anniversary( ?array $users ): array {
		$today             = gmdate( 'm-d' );
		$anniversary_users = array();

		foreach ( $users as $user_id => $date ) {
			$user_date = gmdate( 'm-d', strtotime( $date ) );
			if ( $user_date === $today && gmdate( 'Y', strtotime( $date ) ) !== gmdate( 'Y' ) ) {
				$anniversary_users[ $user_id ] = $date;
			}
		}

		return $anniversary_users;
	}

	/**
	 * Get the number of translations for each user.
	 *
	 * @param int $user_id The user_id.
	 *
	 * @return array An array with the user_id as key and the number of translations as value.
	 */
	private function get_number_of_translations( int $user_id ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM translate_translations
				WHERE user_id = %d
				AND status = 'current'",
				$user_id
			)
		);
	}

	/**
	 * Send an email to the translators.
	 *
	 * @param int $anniversary_users      The user_id (key) that have an anniversary and their start date (value. Y-m-d format).
	 * @param int $number_of_translations The number of translations for each user as value. The user_id is the key.
	 *
	 * @return void
	 */
	private function send_email_to_translator( int $user_id, string $date, int $number_of_translations ) {
		$user       = get_userdata( $user_id );
		$start_date = new \DateTime( $date );
		$today      = new \DateTime();
		$interval   = $start_date->diff( $today );
		$years      = $interval->y;

		// translators: Email subject.
		$subject = __( 'Happy translation anniversary! 🎂', 'wporg-gp-engagement' );

		$message = sprintf(
		// translators: Email body. %1$s: Display name. %2$d: number of years since the first translation. %3$d: number of translations.
			_n(
					'
Dear %1$s,
<br><br>
do you remember? On this day, %2$d year ago, you contributed your first translation to translate.wordpress.org.
<br><br>
In this %2$d year, you have contributed %3$d translations. We really appreciate it, thank you so much!
<br><br>
Keep up the great work!
<br><br>
The Global Polyglots Team
',
					'
Dear %1$s,
<br><br>
do you remember? On this day, %2$d years ago, you contributed your first translation to translate.wordpress.org.
<br><br>
In this %2$d years, you have contributed %3$d translations. We really appreciate it, thank you so much!
<br><br>
Keep up the great work!
<br><br>
The Global Polyglots Team
',
				$years,
				'wporg-gp-engagement'
			),
			$user->display_name,
			$years,
			number_format_i18n( $number_of_translations )
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
	 * @param array|null $anniversary_users      The users that have an anniversary and their start date (Y-m-d).
	 * @param array|null $number_of_translations The number of translations for each user.
	 *
	 * @return void
	 */
	private function send_slack_notification( ?array $anniversary_users, ?array $number_of_translations ) {
		if ( ! $anniversary_users ) {
			return;
		}

		foreach ( $anniversary_users as $user_id => $date ) {
			$user = get_userdata( $user_id );
			// translators: Slack message.
			$message = sprintf(
				'We have sent a new message to *%s* about his/her translation anniversary. He/she starts translating on *%s* and has made *%s* translations in current status.',
				$user->display_name,
				$date,
				number_format_i18n( $number_of_translations[ $user_id ] )
			);

			do_action( 'wporg_translate_notification_slack', $message );
		}
	}
}
