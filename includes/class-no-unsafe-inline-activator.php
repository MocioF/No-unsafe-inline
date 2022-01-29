<?php

/**
 * Fired during plugin activation
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 */

use NUNIL\Nunil_Manage_Muplugin;
use NUNIL\Nunil_Lib_Log as Log;
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Activator {

	/**
	 * Activate the plugin.
	 *
	 * On plugin activation we install the mu-plugin and create the tables.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public static function activate(): void {
		global $wpdb;
		set_time_limit( 360 );
		self::disable_all_tools();
		\NUNIL\Nunil_Lib_Db::db_create();
		self::set_default_options();

		try {
			if ( ! Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
				Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
			}
		} catch ( Exception $ex ) {
			Log::error( 'Impossible to install mu-plugin: ' . $ex->getMessage() . ', ' . $ex->getTraceAsString() );
			Log::error( 'no-unsafe-inline cannot work without mu-plugin. Deactivate.' );
			No_Unsafe_Inline_Deactivator::deactivate;
		}
		Log::info( 'Activated plugin.' );
	}

	/**
	 * Sets option used for tools at default value (all disabled)
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private static function disable_all_tools(): void {
		$tools_disabled = array(
			'capture_enabled'   => 0,
			'test_policy'       => 0,
			'enable_protection' => 0,
		);
		update_option( 'no-unsafe-inline-tools', $tools_disabled );
	}

	/**
	 * Sets default plugin options if not in database
	 *
	 * @since = 1.0.0
	 * @access private
	 * @return void
	 */
	private static function set_default_options(): void {
		$plugin_options = get_option( 'no-unsafe-inline' );
		if ( ! $plugin_options ) {
			$plugin_options = array();
			$class          = new No_Unsafe_Inline();
			foreach ( $class->managed_src_directives as $src_directive ) {
				$plugin_options[ $src_directive . '_enabled' ] = 1;
			}
			$plugin_options['prefetch-src_enabled'] = 0;

			$plugin_options['external_host_mode']      = 'sch-host';
			$plugin_options['sri_script']              = 1;
			$plugin_options['sri_link']                = 1;
			$plugin_options['use_strict-dynamic']      = 0;
			$plugin_options['sri_sha256']              = 1;
			$plugin_options['sri_sha384']              = 0;
			$plugin_options['sri_sha512']              = 0;
			$plugin_options['inline_scripts_mode']     = 'sha256';
			$plugin_options['protect_admin']           = 1;
			$plugin_options['use_unsafe-hashes']       = 0;
			$plugin_options['fix_setattribute_style']  = 1;
			$plugin_options['add_wl_by_cluster_to_db'] = 1;
			$plugin_options['logs_enabled']            = 1;
		}
		update_option( 'no-unsafe-inline', $plugin_options );
	}

	// TUTTO DA FARE, E NON QUI
	private function check_php_version() {
		$php_version = phpversion();
		if ( version_compare( $php_version, 'NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION', '<' ) ) {
			$string = sprintf( esc_html__( 'no-unsafe-inline requires minimum php version %s.', 'no-unsafe-inline' ), NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION );
			// deactivate your plugin or abort installing your theme and tell the user why
		}
	}


}
