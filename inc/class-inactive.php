<?php
/**
 * This class sends an email to translators who have been inactive in the last years.
 *
 * @package wporg-gp-customizations
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
		$years_to_check = array( 1, 2, 3 );
		foreach ( $years_to_check as $years ) {
			$date_years_ago = ( new DateTime() )->modify( "-$years year" )->format( 'Y-m-d' );
			$all_users      = $this->get_users_with_translation_on_date( $date_years_ago );
			$inactive_users = $this->get_inactive_users( $all_users, $date_years_ago );
			$this->send_email_to_translators( $inactive_users, 1 );
			$this->send_slack_notification( $inactive_users, 1 );
		}
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
	 * @param string     $date      The date to check for translations. Format 'Y-m-d'.
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
	 * @param int   $years          The number of years the user has been inactive.
	 *
	 * @return void
	 */
	private function send_email_to_translators( array $inactive_users, int $years ) {
		// Translators: Email subject. %d: Number of years the user has been inactive.
		$subject = sprintf( _n( 'We didn\'t see you the last %d year and we miss you', 'We didn\'t see you the last %d years and we miss you', $years, 'wporg-gp-engagement' ), $years );
		foreach ( $inactive_users as $user_id ) {
			$user    = get_user_by( 'id', $user_id );
			$message = sprintf(
			// translators: Email body. %1$s: Display name. %2$s: Translation URL. %3$s: Project URL.
				_n(
					'
We miss you, %1$s,
<br><br>
We didn\'t see you at translate.wordpress.org the last %2$d year and we miss you. We hope you can come back soon, 
because we need your help to make WordPress available in your language for everyone. 
<br><br>
Have a nice day
<br><br>
The Global Polyglots Team
',
					'
We miss you, %1$s,
<br><br>
We didn\'t see you at translate.wordpress.org the last %2$d years and we miss you. We hope you can come back soon, 
because we need your help to make WordPress available in your language for everyone. 
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
			);

			$allowed_html = array(
				'br' => array(),
			);

			$message      = wp_kses( $message, $allowed_html );
			$notification = new Notification();
			$notification->send_email( $user, $subject, $message );
		}
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param array $inactive_users An array with the user_id of the inactive users.
	 * @param int   $years          The number of years the user has been inactive.
	 *
	 * @return void
	 */
	private function send_slack_notification( array $inactive_users, int $years ) {
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

		// Translators: Slack message.
		$message = sprintf(
			'We have sent a new message to *%s* about the last %d years of inactivity.',
			$users_list,
			$years
		);

		$notification = new Notification();
		$notification->send_slack_notification( $message, '@amieiro' );
	}
}
