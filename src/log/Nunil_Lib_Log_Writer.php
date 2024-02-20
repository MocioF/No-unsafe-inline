<?php
/**
 * Log writer interface
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL\log;

/**
 * Writer interface
 */
interface Nunil_Lib_Log_Writer {
	/**
	 * Write a log message
	 *
	 * @param string $level Error Level.
	 * @param string $message Message to log.
	 *
	 * @return mixed
	 */
	public function write( $level, $message );
}
