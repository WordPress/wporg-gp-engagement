<?php
/**
 * This file contains the main plugin file.
 *
 * @package    WordPressdotorg\GlotPress\Engagement
 * @author     WordPress.org
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License
 * @link       https://wordpress.org/
 */

namespace WordPressdotorg\GlotPress\Engagement;

use GP_Route;
use GP_Translation;
use WP_CLI;

/**
 * Main plugin class.
 */
class Plugin extends GP_Route {

	/**
	 * The instance of the class.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Get the instance of the class.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'init', array( $this, 'wp_schedule_crons' ) );
		add_action( 'gp_translation_saved', array( new Reengagement_First_Translation(), '__invoke' ) );
		add_action( 'gp_translation_saved', array( new Translation_Milestone(), '__invoke' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'gp_engagement_anniversary', array( new Anniversary(), '__invoke' ) );
		add_action( 'gp_engagement_inactive', array( new Inactive(), '__invoke' ) );
	}

	/**
	 * Register the WP CLI command.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		// Restrict WP-CLI command to sandboxes.
		if ( ! defined( 'WPORG_SANDBOXED' ) || ! WPORG_SANDBOXED ) {
			return;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'wporg-translate engagement-anniversary', __NAMESPACE__ . '\Anniversary_CLI' );
			WP_CLI::add_command( 'wporg-translate engagement-inactive', __NAMESPACE__ . '\Inactive_CLI' );
			WP_CLI::add_command( 'wporg-translate engagement-consistency', __NAMESPACE__ . '\Consistency_CLI' );
		}
	}

	/**
	 * Schedule the crons.
	 *
	 * @return void
	 */
	public function wp_schedule_crons() {
		if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
			return;
		}

		if ( ! wp_next_scheduled( 'gp_engagement_anniversary' ) ) {
			wp_schedule_event( time(), 'daily', 'gp_engagement_anniversary' );
		}
		if ( ! wp_next_scheduled( 'gp_engagement_inactive' ) ) {
			wp_schedule_event( time(), 'daily', 'gp_engagement_inactive' );
		}
	}
}
