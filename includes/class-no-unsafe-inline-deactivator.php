<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/includes
 */

use NUNIL\Nunil_Manage_Muplugin;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Exception;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/includes
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
	 * @return   void
	 */
	public static function deactivate( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
				$args  = array(
					'orderby' => 'id',
					'order'   => 'asc',
				);
				$sites = get_sites( $args );
			} else {
				// WP < 4.6; however it is unsupported.
				$sites = wp_get_sites();
			}
			if ( is_iterable( $sites ) && ! empty( $sites ) ) {
				$remove_mu_plugin = true;
				foreach ( $sites as $site ) {
					if ( is_object( $site ) ) {
						switch_to_blog( intval( $site->blog_id ) );
					} else {
						switch_to_blog( intval( $site['blog_id'] ) );
					}
					// We don't remove mu-plugin if plugin is local activated.
					if ( in_array( NO_UNSAFE_INLINE_PLUGIN_BASENAME, (array) get_option( 'active_plugins', array() ), true ) ) {
						$remove_mu_plugin = false;
					}
					Log::info( esc_html__( 'Deactivated plugin.', 'no-unsafe-inline' ) );
					restore_current_blog();
				}
				// On network wide deactivation, remove mu-plugin.
				if ( true === $remove_mu_plugin && Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
					try {
						Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
					} catch ( Nunil_Exception $ex ) {
						$ex->logexception();
					}
				}
			} else {
				Log::error( esc_html__( 'Impossible to perform network deactivation on older wp installation with more than 10000 sites', 'no-unsafe-inline' ) );
			}
		} else {
			// On single site deactivation, remove tables and settings, if set in options.
			$options = get_option( 'no-unsafe-inline' );
			if ( is_array( $options ) ) {
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
			// Remove mu-plugin only if is not a multisite enviroment.
			if ( false === is_multisite() && Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
				try {
					Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
				} catch ( Nunil_Exception $ex ) {
					$ex->logexception();
				}
			}
		}
	}

	/**
	 * Run when a blog is removed from network
	 *
	 * @since 1.0.0
	 * @param WP_Site $params Site object.
	 * @return void
	 */
	public static function remove_blog( $params ) {
		switch_to_blog( intval( $params->blog_id ) );

		DB::remove_data_tables();

		restore_current_blog();
	}
}
