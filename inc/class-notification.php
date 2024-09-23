<?php
/**
 * This class manages the notifications.
 *
 * @package wporg-gp-customizations
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
	 * Constructor.
	 */
	public function __construct() {
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
		} else {
			if ( ! $user ) {
				return;
			}
			$email = sanitize_email( $user->user_email );
		}

		wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Send a Slack notification.
	 *
	 * @param string $message The message to send.
	 * @param string $channel The channel to send the message.
	 *
	 * @return void
	 */
	public function send_slack_notification( string $message, string $channel ) {
		if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
			slack_dm( $message, $channel );
		}
		// Todo: define the channel to use in production.
	}
}
