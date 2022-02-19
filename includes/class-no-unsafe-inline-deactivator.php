<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 */

use NUNIL\Nunil_Manage_Muplugin;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Db as DB;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Deactivator {

	/**
	 * Fired during plugin deactivation.
	 *
	 * On plugin complete deactivation we uninstall the mu-plugin (and we don't delete the db tables).
	 *
	 * @param bool $network_wide Indicates if the plugin is network activated.
	 * @since    1.0.0
	 */
	public static function deactivate( $network_wide ) {

		if ( is_multisite() && $network_wide ) {
			$blog_ids         = DB::get_blogs_ids();
			$remove_mu_plugin = true;
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				// We don't remove mu-plugin if plugin is local activated.
				if ( in_array( NO_UNSAFE_INLINE_PLUGIN_BASENAME, (array) get_option( 'active_plugins', array() ), true ) ) {
					$remove_mu_plugin = false;
				}
				Log::info( 'Deactivated plugin.' );
				restore_current_blog();
			}
			// On network wide deactivation, remove mu-plugin.
			try {
				if ( true === $remove_mu_plugin && Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
					Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
				}
			} catch ( Exception $ex ) {
				Log::error( 'Impossible to uninstall mu-plugin: ' . $ex->getMessage() . ', ' . $ex->getTraceAsString() );
			}
		} else {
			// On single site deactivation, remove tables and settings, if set in options.
			$options = get_option( 'no-unsafe-inline' );
			if ( 1 === $options['remove_tables'] ) {
				DB::remove_data_tables();
			}

			if ( 1 === $options['remove_options'] ) {
				$plugin_options = array(
					'no-unsafe-inline',
					'no-unsafe-inline_version',
					'no-unsafe-inline-tools',
					'no-unsafe-inline-base-rule',
					'no-unsafe-inline-global-settings',
					'no-unsafe-inline_db_version',
				);

				foreach ( $plugin_options as $setting_name ) {
					delete_option( $setting_name );
				}
			}
		}
	}

	/**
	 * Run when a blog is removed from network
	 *
	 * @since 1.0.0
	 * @param \WP_Site $params Site object.
	 * @return void
	 */
	public static function remove_blog( $params ) {

		switch_to_blog( $params->blog_id );

		DB::remove_data_tables();

		restore_current_blog();

	}
}
