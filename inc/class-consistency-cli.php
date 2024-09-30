<?php
/**
 * This WP_CLI class sends an email to translators with translation consistency:
 * they have been translating for a long time, and they have been consistent.
 *
 * It has been developed with testing purposes, because the email sending should
 * be done with a cron job.
 *
 * @package wporg-gp-engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WP_CLI_Command;

/**
 * Sends an email to translators with translation consistency.
 */
class Consistency_CLI extends WP_CLI_Command {
	/**
	 * Send an email to translators with translations, at least, in the last 6 months.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wporg-translate engagement-consistency --url=translate.wordpress.org
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$consistency = new Consistency();
		$consistency();
	}
}
