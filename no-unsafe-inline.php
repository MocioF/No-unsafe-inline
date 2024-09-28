<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/mociofiletto/
 * @since             1.0.0
 * @package           No_unsafe-inline
 *
 * @wordpress-plugin
 * Plugin Name:       No unsafe-inline
 * Plugin URI:        https://github.com/MocioF/No-unsafe-inline
 * Description:       This plugin helps you to build a CSP to avoid using 'unsafe-inline' in your .htaccess
 * Version:           1.2.2
 * Author:            Giuseppe Foti
 * Author URI:        https://profiles.wordpress.org/mociofiletto/
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       no-unsafe-inline
 * Domain Path:       /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NO_UNSAFE_INLINE_VERSION', '1.2.2' );
define( 'NO_UNSAFE_INLINE_DB_VERSION', '1.0' );
define( 'NO_UNSAFE_INLINE_MINIMUM_WP_VERSION', '5.9' );
define( 'NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION', '7.4' );
define( 'NO_UNSAFE_INLINE_PLUGIN', __FILE__ );
define( 'NO_UNSAFE_INLINE_PLUGIN_BASENAME', plugin_basename( NO_UNSAFE_INLINE_PLUGIN ) );
define( 'NO_UNSAFE_INLINE_PLUGIN_NAME', trim( dirname( NO_UNSAFE_INLINE_PLUGIN_BASENAME ), '/' ) );
define( 'NO_UNSAFE_INLINE_PLUGIN_DIR', untrailingslashit( dirname( NO_UNSAFE_INLINE_PLUGIN ) ) );

/**
 * The table prefix for db tables used by the plugin
 */
global $wpdb;
$nunil_table_prefix = $wpdb->prefix . 'nunil_';
define( 'NO_UNSAFE_INLINE_TABLE_PREFIX', $nunil_table_prefix );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-no-unsafe-inline-activator.php
 *
 * @since 1.0.0
 * @param bool $network_wide True if plugin is network-wide activated.
 * @return void
 */
function no_unsafe_inline_activate( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-no-unsafe-inline-activator.php';
	No_Unsafe_Inline_Activator::activate( $network_wide );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-no-unsafe-inline-deactivator.php
 *
 * @since 1.0.0
 * @param bool $network_wide True if plugin is network-wide activated.
 * @return void
 */
function no_unsafe_inline_deactivate( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-no-unsafe-inline-deactivator.php';
	No_Unsafe_Inline_Deactivator::deactivate( $network_wide );
}

register_activation_hook( __FILE__, 'no_unsafe_inline_activate' );
register_deactivation_hook( __FILE__, 'no_unsafe_inline_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-no-unsafe-inline.php';

/**
 * Include all dependencies, managed by composer.
 */
require plugin_dir_path( NO_UNSAFE_INLINE_PLUGIN ) . 'vendor/autoload.php';

/**
 * Extra settings for visualization in plugin list.
 */
// ~ require_once plugin_dir_path( NO_UNSAFE_INLINE_PLUGIN ) . 'settings.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 * @return   void
 */
function no_unsafe_inline_run() {
	add_action(
		'init',
		function () {
			$plugin = new No_Unsafe_Inline();

			$plugin->run();

			// if we have a new blog on a multisite let's set it up.
			add_action( 'wp_initialize_site', 'no_unsafe_inline_run_multisite_new_site' );

			// if a blog is removed, let's remove the settings.
			add_action( 'wp_uninitialize_site', 'no_unsafe_inline_run_multisite_delete' );
		}
	);
}
no_unsafe_inline_run();

/**
 * Trigger plugin activation on a new blog creations
 *
 * @since 1.0.0
 * @param WP_Site $params New site object.
 * @return void
 */
function no_unsafe_inline_run_multisite_new_site( $params ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-no-unsafe-inline-activator.php';
	No_Unsafe_Inline_Activator::add_blog( $params );
}

/**
 * Trigger table removes on blog deletion
 *
 * @since 1.0.0
 * @param WP_Site $params Old site object.
 * @return void
 */
function no_unsafe_inline_run_multisite_delete( $params ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-no-unsafe-inline-deactivator.php';
	No_Unsafe_Inline_Deactivator::remove_blog( $params );
}
