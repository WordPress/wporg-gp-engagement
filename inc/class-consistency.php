<?php
/**
 * This class sends an email to translators with translation consistency:
 *  they have been translating for a long time, and they have been consistent.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Consistency {
	/**
	 * Send an email to translators in their translation anniversary.
	 *
	 * @return void
	 */
	public function __invoke() {
		$users_to_notify = $this->get_users_to_notify();
	}

	/**
	 * Get users with translations in the last $months months.
	 *
	 * @param int $months The number of months to check.
	 *
	 * @return array The user IDs with translations in the last $months months.
	 */
	public function get_users_with_consistency_last_months( int $months = 6 ): array {
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
	 * Get the users to notify.
	 *
	 * @return array The users to notify.
	 */
	private function get_users_to_notify(): array {
		$months_to_notify = array( 48, 24, 12, 6 );
		$users_to_notify  = array();

		foreach ( $months_to_notify as $month ) {
			$current_users = $this->get_users_with_consistency_last_months( $month );
			// Remove users from previous months.
			foreach ( $users_to_notify as $previous_users ) {
				$current_users = array_diff( $current_users, $previous_users );
			}

			$users_to_notify[ $month ] = $current_users;
		}
		return $users_to_notify;
	}
}
