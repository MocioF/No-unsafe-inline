<?php

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
}
