<?php
/**
 * This class sends an email to translators with translation consistency:
 *  they have been translating for a long time, and they have been consistent.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Consistency {
	/**
	 * The months to notify.
	 *
	 * @var array
	 */
	private array $months_to_notify = array( 48, 24, 12, 6 );
	/**
	 * Send an email to translators in their translation anniversary.
	 *
	 * @return void
	 */
	public function __invoke() {
		$users_to_notify = $this->get_users_to_notify();
		$this->send_email_to_translators( $users_to_notify );
		$this->send_slack_notifications( $users_to_notify );
	}

	/**
	 * Get the users to notify.
	 *
	 * @return array The users to notify.
	 */
	private function get_users_to_notify(): array {
		$users_to_notify = array();

		foreach ( $this->months_to_notify as $month ) {
			$current_users = $this->get_users_with_consistency_last_months( $month );
			// Remove users from previous months.
			foreach ( $users_to_notify as $previous_users ) {
				$current_users = array_diff( $current_users, $previous_users );
			}

			$users_to_notify[ $month ] = $current_users;
		}
		return $users_to_notify;
	}

	/**
	 * Get users with translations in the last $months months.
	 *
	 * @param int $months The number of months to check.
	 *
	 * @return array The user IDs with translations in the last $months months.
	 */
	private function get_users_with_consistency_last_months( int $months = 6 ): array {
		global $wpdb;

		$date_ranges = array();
		$user_ids    = array();

		// Calculate the start and end dates for each of the last $months months.
		for ( $i = 1; $i <= $months; $i++ ) {
			$start_date    = gmdate( 'Y-m-01 00:00:00', strtotime( "-$i months" ) );
			$end_date      = gmdate( 'Y-m-t 23:59:59', strtotime( "-$i months" ) );
			$date_ranges[] = array( $start_date, $end_date );
		}

		foreach ( $date_ranges as $range ) {
			list($start_date, $end_date) = $range;

			$query = $wpdb->prepare(
				"SELECT DISTINCT user_id
             FROM `{$wpdb->gp_translations}`
             WHERE `date_added` BETWEEN %s AND %s order by user_id",
				$start_date,
				$end_date
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$month_user_ids = $wpdb->get_col( $query );

			if ( empty( $user_ids ) ) {
				$user_ids = $month_user_ids;
			} else {
				$user_ids = array_intersect( $user_ids, $month_user_ids );
			}

			// If no users are found in any month, return an empty array.
			if ( empty( $user_ids ) ) {
				return array();
			}
		}

		return array_values( $user_ids );
	}

	/**
	 * Send an email to the translators.
	 *
	 * @param array $users_to_notify The users to notify.
	 *
	 * @return void
	 */
	private function send_email_to_translators( array $users_to_notify ): void {
		foreach ( $users_to_notify as $months => $user_ids ) {
			$years = intdiv( $months, 12 );
			// Translators: Number of years or months of translation consistency, to be used in the email subject.
			$time_period = $years > 0 ? sprintf( _n( '%d year', '%d years', $years, 'wporg-gp-engagement' ), $years ) : sprintf( _n( '%d month', '%d months', $months, 'wporg-gp-engagement' ), $months );

			// Translators: Email subject. %s is the number of years or months of translation consistency.
			$subject = sprintf( __( 'Thank you for your %s of translation consistency!', 'wporg-gp-engagement' ), $time_period );

			foreach ( $user_ids as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( ! $user ) {
					continue;
				}

				if ( $this->has_the_notification_been_sent( $user_id, $months ) ) {
					continue;
				}

				$message = sprintf(
					// Translators: Email message. %1$s is the user display name, %2$s is the number of years or months of translation consistency.
					__(
						'Dear %1$s,<br><br>Thank you for your %2$s of consistent translations at translate.wordpress.org. 
Your contributions are invaluable in making WordPress available in multiple languages.
<br><br>
Best regards,
<br><br>
The Global Polyglots Team',
						'wporg-gp-engagement'
					),
					$user->display_name,
					$time_period
				);

				$allowed_html = array(
					'br' => array(),
				);

				$message      = wp_kses( $message, $allowed_html );
				$notification = new Notification();
				$notification->send_email( $user, $subject, $message );
				$this->update_user_option( $user_id, $months );
			}
		}
	}

	/**
	 * Send a Slack notification to the users
	 *
	 * @param array $users_to_notify The users to notify.
	 */
	private function send_slack_notifications( array $users_to_notify ) {
		foreach ( $users_to_notify as $months => $user_ids ) {
			$years = intdiv( $months, 12 );
			// Translators: Number of years or months of translation consistency, to be used in the Slack message.
			$time_period = $years > 0 ? sprintf( _n( '%d year', '%d years', $years, 'wporg-gp-engagement' ), $years ) : sprintf( _n( '%d month', '%d months', $months, 'wporg-gp-engagement' ), $months );

			$users = array();
			foreach ( $user_ids as $user_id ) {
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					continue;
				}
				$users[] = $user->display_name;
			}

			if ( empty( $users ) ) {
				continue;
			}

			$users_list = implode( ', ', $users );

			// Translators: Slack message.
			$message = sprintf(
				'We have sent a thank you message to *%s* for their %s of translation consistency.',
				$users_list,
				$time_period
			);

			$notification = new Notification();
			$notification->send_slack_notification( $message, '@amieiro' );
		}
	}

	/**
	 * Check if the notification has been sent.
	 *
	 * It avoids sending the notification again.
	 *
	 * @param int $user_id The user ID.
	 * @param int $months  The number of months.
	 *
	 * @return bool True if the notification has been sent, false otherwise.
	 */
	private function has_the_notification_been_sent( int $user_id, int $months ): bool {
		$reengagement_options = get_user_option( 'gp_engagement', $user_id );
		$reengagement_options = $reengagement_options ? $reengagement_options : array();
		return ! empty( $reengagement_options[ 'consistency_' . $months ] );
	}

	/**
	 * Update the user option, to avoid sending the notification again.
	 *
	 * @param int $user_id The user ID.
	 * @param int $months  The number of months.
	 *
	 * @return void
	 */
	private function update_user_option( int $user_id, int $months ) {
		foreach ( $this->months_to_notify as $month ) {
			$reengagement_options[ 'consistency_' . $month ] = null;
		}
		$reengagement_options[ 'consistency_' . $months ] = gmdate( 'Y-m-d' );
		update_user_option( $user_id, 'gp_engagement', $reengagement_options );
	}
}
