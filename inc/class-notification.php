<?php
/**
 * This class manages the notifications.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_User;

/**
 * Manages the notifications.
 */
class Notification {
	/**
	 * The email to use for testing.
	 *
	 * @var string
	 */
	private string $testing_email = 'amieiro@gmail.com';

	/**
	 * The Slack channel ID for the Polyglots Engagement Logs.
	 *
	 * @var string
	 */
	private string $polyglots_engagement_logs_channel_id = 'C0828HUHGNA';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wporg_send_email_event', array( $this, 'send_scheduled_email' ), 10, 4 );
		add_action( 'wporg_send_slack_notification_event', array( $this, 'send_scheduled_slack_notification' ), 10, 1 );
	}

	/**
	 * Send an email to a user.
	 *
	 * @param WP_User|null $user    The user to send the email.
	 * @param string       $subject The subject of the email.
	 * @param string       $message The message of the email.
	 *
	 * @return void
	 */
	public function send_email( ?WP_User $user, string $subject, string $message ) {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Translating WordPress.org <no-reply@wordpress.org>',
		);

		if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
			$email = $this->testing_email;
			wp_mail( $email, $subject, $message, $headers );
		} else {
			if ( ! $user ) {
				return;
			}
			$email = sanitize_email( $user->user_email );
			wp_schedule_single_event( time(), 'wporg_send_email_event', array( $email, $subject, $message, $headers ) );
		}
	}

	/**
	 * Send a scheduled email.
	 *
	 * @param string $email   The email to send.
	 * @param string $subject The subject of the email.
	 * @param string $message The message of the email.
	 * @param array  $headers The headers of the email.
	 *
	 * @return void
	 */
	public function send_scheduled_email( string $email, string $subject, string $message, array $headers ) {
		wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param string $message The message to send.
	 *
	 * @return void
	 */
	public function send_slack_notification( string $message ) {
		if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
			slack_dm( $message, '@amieiro' );
			slack_dm( $message, $this->polyglots_engagement_logs_channel_id );
		} else {
			wp_schedule_single_event( time(), 'wporg_send_slack_notification_event', array( $message ) );
		}
	}

	/**
	 * Send a scheduled Slack notification.
	 *
	 * @param string $message The message to send.
	 *
	 * @return void
	 */
	public function send_scheduled_slack_notification( string $message ) {
		slack_dm( $message, $this->polyglots_engagement_logs_channel_id );
	}
}
