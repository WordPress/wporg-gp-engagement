<?php
/**
 * This WP_CLI class sends an email to translators when they have been inactive in the last years.
 *
 * It has been developed with testing purposes, because the email sending should
 * be done with a cron job.
 *
 * @package wporg-gp-customizations
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI;
use WP_CLI_Command;

/**
 * Sends an email to translators when they have been inactive in the last years.
 */
class Inactive_CLI extends WP_CLI_Command {
	/**
	 * Send an email to translators when they have been inactive in the last years.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wporg-translate engagement-inactive --url=translate.wordpress.org
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$inactive = new Inactive();
		$inactive();
	}
}
