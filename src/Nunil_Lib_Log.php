<?php
/**
 * Nunil_Lib_Log
 *
 * Class used to log messages in DB or on debug.log.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

/**
 * Logger
 */
class Nunil_Lib_Log {
	public const DEBUG   = 0;
	public const INFO    = 1;
	public const WARNING = 2;
	public const ERROR   = 3;

	/**
	 * Obj of class implementing writer interface.
	 *
	 * @var \NUNIL\log\Nunil_Lib_Log_Writer An obj of class implementing the Nunil_Lib_Log_Writer interface
	 */
	private static $writer;

	/**
	 * Determines if log interface is enabled.
	 *
	 * @var bool Set to true to enable logging
	 */
	private static $enabled = true;

	/**
	 * Log level. Set to one of defined debug level.
	 *
	 * @var int See consts DEBUG, INFO, WARNING, ERROR
	 */
	private static $level = self::ERROR;

	/**
	 * Sets the Log writer
	 *
	 * @param \NUNIL\log\Nunil_Lib_Log_Writer $writer Class.
	 * @param int                             $level Error level.
	 * @return void
	 */
	public static function init( log\Nunil_Lib_Log_Writer $writer, $level = self::ERROR ) {
		self::$writer = $writer;
		self::$level  = $level;
	}

	/**
	 * Enable the logger
	 *
	 * @return void
	 */
	public static function enable() {
		self::$enabled = true;
	}

	/**
	 * Disable the logger
	 *
	 * @return void
	 */
	public static function disable() {
		self::$enabled = false;
	}

	/**
	 * Logs a debug message
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public static function debug( $message ) {
		if ( ! self::$enabled || self::$level > self::DEBUG ) {
			return;
		}
		self::$writer->write( self::level_to_string( self::DEBUG ), $message );
	}

	/**
	 * Logs a info message
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public static function info( $message ) {
		if ( ! self::$enabled || self::$level > self::INFO ) {
			return;
		}
		self::$writer->write( self::level_to_string( self::INFO ), $message );
	}

	/**
	 * Logs a warning message
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public static function warning( $message ) {
		if ( ! self::$enabled || self::$level > self::WARNING ) {
			return;
		}
		self::$writer->write( self::level_to_string( self::WARNING ), $message );
	}

	/**
	 * Logs an error message
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	public static function error( $message ) {
		if ( ! self::$enabled || self::$level > self::ERROR ) {
			return;
		}
		self::$writer->write( self::level_to_string( self::ERROR ), $message );
	}

	/**
	 * Get the string from the level code
	 *
	 * @since 1.0.0
	 * @param int $level Level code.
	 * @return string
	 */
	private static function level_to_string( $level ) {
		$string = '';
		switch ( $level ) {
			case self::DEBUG:
				$string = 'debug';
				break;
			case self::INFO:
				$string = 'info';
				break;
			case self::WARNING:
				$string = 'warning';
				break;
			case self::ERROR:
				$string = 'error';
				break;
			default:
				break;
		}
		return $string;
	}

	/**
	 * Get the level code from the string
	 *
	 * @param string $level_string Level string.
	 *
	 * @return int
	 * @since 1.6.0
	 */
	public static function string_to_level( $level_string ) {
		$level = self::ERROR;
		switch ( $level_string ) {
			case 'debug':
				$level = self::DEBUG;
				break;
			case 'info':
				$level = self::INFO;
				break;
			case 'warning':
				$level = self::WARNING;
				break;
			default:
				break;
		}
		return $level;
	}
}
