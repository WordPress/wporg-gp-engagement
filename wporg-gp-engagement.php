<?php
/**
 * Plugin Name: WordPress.org Engagement
 * Description: Send notifications to try to engage the translators.
 * Version:     0.1.0
 * Author:      WordPress.org
 * Author URI:  https://wordpress.org/
 * License:     GPLv2 or later
 * Text Domain: wporg-gp-engagement
 *
 * @package WordPressdotorg\GlotPress\Engagement
 */

namespace WordPressdotorg\GlotPress\Engagement;

use WordPressdotorg\Autoload;

// Store the root plugin file for usage with functions which use the plugin basename.
define( __NAMESPACE__ . '\PLUGIN_FILE', __FILE__ );

if ( ! class_exists( '\WordPressdotorg\Autoload\Autoloader', false ) ) {
	include __DIR__ . '/vendor/wordpressdotorg/autoload/class-autoloader.php';
}

// Register an Autoloader for all files.
Autoload\register_class_path( __NAMESPACE__, __DIR__ . '/inc' );
// Instantiate the Plugin.
Plugin::get_instance();
