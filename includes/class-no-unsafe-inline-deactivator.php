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
	 * Array ot tables creted in the database
	 */
	private static $tables_name = array(
		'inline_scripts',
		'external_scripts',
		'event_handlers',
		'occurences',
		'nunil_logs',
	);

	/**
	 * Fired during plugin deactivation.
	 *
	 * On plugin deactivation we uninstall the mu-plugin (and we don't delete the db tables).
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		for ( $i = 0, $size = count( self::$tables_name ); $i < $size; ++$i ) {
			$tname = NO_UNSAFE_INLINE_TABLE_PREFIX . self::$tables_name[ $i ];
			self::drop_table( $tname );
		}
		try {
			if ( Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
				Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
			}
		} catch ( Exception $ex ) {
			Log::error( 'Impossible to uninstall mu-plugin: ' . $ex->getMessage() . ', ' . $ex->getTraceAsString() );
		}
		Log::info( 'Deactivated plugin.' );
	}

	/**
	 * Drops DB tables.
	 *
	 * Method eventually used to drop DB Tables.
	 *
	 * @since    1.0.0
	 */
	public static function drop_table( $table ) {
		global $wpdb;
		$structure = "DROP TABLE IF EXISTS $table";
		$wpdb->query( $structure );
	}
}
