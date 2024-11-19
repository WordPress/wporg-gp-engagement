<?php
/**
 * This class sends an email to translators who have been inactive in the last years.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use DateTime;
use WP_CLI;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Inactive {
	/**
	 * Send an email to translators who have been inactive in the last years.
	 *
	 * @return void
	 */
	public function __invoke() {
			$one_year_ago   = ( new DateTime() )->modify( '-1 year' )->format( 'Y-m-d' );
			$all_users      = $this->get_users_with_translation_on_date( $one_year_ago );
			$inactive_users = $this->get_inactive_users( $all_users, $one_year_ago );
			$this->send_email_to_translators( $inactive_users );
			$this->send_slack_notification( $inactive_users );
	}

	/**
	 * Get all users who have made a translation on a specific date.
	 *
	 * @param string $date The date to check for translations. Format 'Y-m-d'.
	 *
	 * @return array An array with the user_id of the users who have made a translation on the date.
	 */
	private function get_users_with_translation_on_date( string $date ): array {
		global $wpdb;
		$users = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$max_user_id = $wpdb->get_var( 'SELECT MAX(user_id) FROM translate_translations' );

		$first_id   = 1;
		$batch_size = 50_000;

		do {
			$query = $wpdb->prepare(
				"SELECT user_id
				FROM `{$wpdb->gp_translations}`
				WHERE DATE(date_added) = %s
				  AND user_id BETWEEN %d AND %d
				GROUP BY user_id",
				$date,
				$first_id,
				$first_id + $batch_size - 1,
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$batch_users = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $batch_users as $user ) {
				$users[] = $user['user_id'];
			}
			$first_id += $batch_size;
		} while ( $first_id < $max_user_id );

		return array_unique( $users );
	}

	/**
	 * Get the users who have been inactive since a specific date.
	 *
	 * @param array|null $users An array with the user_id of users.
	 * @param string     $date  The date to check for translations. Format 'Y-m-d'.
	 *
	 * @return array An array with the user_id of the users who have been inactive since the date.
	 */
	private function get_inactive_users( ?array $users, string $date ): array {
		global $wpdb;
		$inactive_users = array();

		foreach ( $users as $user_id ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(*)
            FROM `{$wpdb->gp_translations}`
            WHERE user_id = %d
              AND DATE(date_added) > %s",
				$user_id,
				$date
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$translations_count = (int) $wpdb->get_var( $query );
			if ( 0 === $translations_count ) {
				$inactive_users[] = $user_id;
			}
		}

		return $inactive_users;
	}

	/**
	 * Send an email to the inactive translators.
	 *
	 * @param array $inactive_users An array with the user_id of the inactive users.
	 *
	 * @return void
	 */
	private function send_email_to_translators( array $inactive_users ) {
		$subject = esc_html__( 'We did not see you in the last year and we miss you! ⏳', 'wporg-gp-engagement' );
		foreach ( $inactive_users as $user_id ) {
			$user    = get_user_by( 'id', $user_id );
			$message = sprintf(
			// translators: Email body. %s: Display name.
				__(
					'We miss you, %s,
<br><br>
we\'re writing you because you have previously contributed to translate.wordpress.org. Thank you for that!
<br><br>
Unfortunately, we noticed that you did not contribute in the last year and we were hoping that you might come back to translating again?
<br><br>
We won’t bother you again on this but just wanted to check.
<br><br>
Thank you!
<br><br>
The Global Polyglots Team
',
					'wporg-gp-engagement'
				),
				$user->display_name,
			);

			$allowed_html = array(
				'br' => array(),
			);

			$message = wp_kses( $message, $allowed_html );

			$random_sentence = new Random_Sentence();
			$message        .= '<h3>💡 ' . esc_html__( 'Did you know...', 'wporg-gp-engagement' ) . '</h3>';
			$message        .= $random_sentence->random_string();

			$notification = new Notification();
			$notification->send_email( $user, $subject, $message );
		}
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param array $inactive_users An array with the user_id of the inactive users.
	 *
	 * @return void
	 */
	private function send_slack_notification( array $inactive_users ) {
		$users = array();
		foreach ( $inactive_users as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$users[] = $user->display_name;
		}
		if ( empty( $users ) ) {
			return;
		}

		$users_list = implode( ', ', $users );

		$message = sprintf(
			// translators: Slack message. %s: List of users.
			esc_html__( 'We have sent a new message to *%s* about the last year of inactivity.', 'wporg-gp-engagement' ),
			$users_list
		);

		$notification = new Notification();
		$notification->send_slack_notification( $message, '@amieiro' );
	}
}
