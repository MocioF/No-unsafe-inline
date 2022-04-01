<?php
/**
 * Log to DB Class
 *
 * Class with methods to write logs in DB.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL\log;

/**
 * Log writer that uses the DB
 */
class Nunil_Lib_Log_Db implements Nunil_Lib_Log_Writer {

	/**
	 * Insert a log entry in the DB
	 *
	 * @param string $level Level code.
	 * @param string $message Message to log.
	 */
	public function write( $level, $message ) {
		return \NUNIL\Nunil_Lib_Db::insert_log( $level, $message );
	}

	/**
	 * Delete the logs older than the number of days
	 *
	 * @param int $days Number of days.
	 * @return void
	 */
	public function delete_old_logs( $days ) {
		\NUNIL\Nunil_Lib_Db::delete_old_logs( $days );
	}
}
