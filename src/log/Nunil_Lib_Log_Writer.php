<?php

namespace NUNIL\log;

/**
 * /***
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
	public function write ( $level, $message );
}
