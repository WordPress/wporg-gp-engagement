<?php
/**
 * This WP_CLI class sends an email to translators in their translation anniversary.
 *
 * It has been developed with testing purposes, because the email sending should
 * be done with a cron job.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI;
use WP_CLI_Command;

/**
 * Sends an email to translators in their translation anniversary.
 */
class Anniversary_CLI extends WP_CLI_Command {
	/**
	 * Send an email to translators in their translation anniversary.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wporg-translate engagement-anniversary --url=translate.wordpress.org
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$anniversary = new Anniversary();
		$anniversary();
	}
}
