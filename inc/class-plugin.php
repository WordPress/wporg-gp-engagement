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
		add_action( 'gp_translation_saved', array( $this, 'gp_translation_saved' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Send an email to translators who for the first time had a translation approved.
	 *
	 * @param GP_Translation $translation The translation that was saved.
	 *
	 * @return void
	 */
	public function gp_translation_saved( GP_Translation $translation ) {
		$reengament = new Reengagement_First_Translation();
		$reengament( $translation );
		$milestone = new Translation_Milestone();
		$milestone( $translation );
	}

	/**
	 * Register the WP CLI command.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'wporg-translate engagement-anniversary', __NAMESPACE__ . '\Anniversary_CLI' );
		}
	}
}
