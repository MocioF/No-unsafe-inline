<?php
/**
 * Log to stdError Class
 *
 * Class with methods to write to stdError.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

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
