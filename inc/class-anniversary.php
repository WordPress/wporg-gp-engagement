<?php
/**
 * This class sends an email to translators in their translation anniversary.
 *
 * @package wporg-gp-customizations
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Anniversary {
	/**
	 * Send an email to translators in their translation anniversary.
	 *
	 * @return void
	 */
	public function __invoke() {
		$all_users              = $this->get_users_and_first_translation_date();
		$anniversary_users      = $this->get_translators_in_anniversary( $all_users );
		$number_of_translations = $this->get_number_of_translations( $anniversary_users );
		$this->send_email_to_translator( $anniversary_users, $number_of_translations );
		$this->send_slack_notification( $anniversary_users, $number_of_translations );
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
				FROM translate_translations
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
	 * @param array|null $users An array with the user_id as key and the date of the first translation as value.
	 *
	 * @return array An array with the user_id as key and the number of translations as value.
	 */
	private function get_number_of_translations( ?array $users ): array {
		$number_of_translations = array();
		global $wpdb;
		foreach ( $users as $user_id => $date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$number_of_translations[ $user_id ] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM translate_translations
					WHERE user_id = %d
					AND status = 'current'",
					$user_id
				)
			);
		}

		return $number_of_translations;
	}

	/**
	 * Send an email to the translators.
	 *
	 * @param array|null $anniversary_users      The user_id (key) that have an anniversary and their start date (value. Y-m-d format).
	 * @param array|null $number_of_translations The number of translations for each user as value. The user_id is the key.
	 *
	 * @return void
	 */
	private function send_email_to_translator( ?array $anniversary_users, ?array $number_of_translations ) {
		foreach ( $anniversary_users as $user_id => $date ) {
			$user       = get_userdata( $user_id );
			$start_date = new \DateTime( $date );
			$today      = new \DateTime();
			$interval   = $start_date->diff( $today );
			$years      = $interval->y;

			// translators: Email subject.
			$subject = __( 'Today is your WordPress translation anniversary!', 'wporg' );

			$message = sprintf(
			// translators: Email body. %1$s: Display name. %2$s: Translation URL. %3$s: Project URL.
				_n(
					'
Happy translation anniversary %1$s 🎉,
<br><br>
Today is the day you started translating WordPress. You started %2$s year ago and you have made %3$s translations. 
Keep up the great work!
<br><br>
Have a nice day
<br><br>
The Global Polyglots Team
',
					'
Happy translation anniversary %1$s 🎉,
<br><br>
Today is the day you started translating WordPress. You started %2$s years ago and you have made %3$s translations. 
Keep up the great work!
<br><br>
Have a nice day
<br><br>
The Global Polyglots Team
',
					$years,
					'wporg-gp-engagement'
				),
				$user->display_name,
				$years,
				$number_of_translations[ $user_id ]
			);

			$allowed_html = array(
				'br' => array(),
			);

			$message = wp_kses( $message, $allowed_html );
			$email   = new Notification();
			$email->send_email( $user, $subject, $message );
		}
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
				$number_of_translations[ $user_id ]
			);
			// Todo: Update to use a dedicated channel.
			slack_dm( $message, '@amieiro' );
		}
	}
}
