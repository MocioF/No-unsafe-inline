<?php

namespace NUNIL\log;

/**
 * Log writter that uses error_log()
 */
class Nunil_Lib_Log_ErrorLog implements Nunil_Lib_Log_Writer {

	/**
	 * Write a message
	 *
	 * @param string $level Level code.
	 * @param string $message Message to log.
	 */
	public function write( $level, $message ) {
		error_log( strtoupper( $level ) . ' | ' . addslashes( $message ) );
	}
}
