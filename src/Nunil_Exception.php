<?php
/**
 * Exception
 *
 * Class used to manage software Exceptions
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.2.0
 */

namespace NUNIL;

use NUNIL\Nunil_Lib_Log as Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to manage and log software exceptions
 *
 * @package No_unsafe-inline
 * @since   1.2.0
 */
class Nunil_Exception extends \Exception {

	public const DEBUG   = 0;
	public const INFO    = 1;
	public const WARNING = 2;
	public const ERROR   = 3;

	/**
	 * The message to log
	 *
	 * @var string
	 */
	private string $log_msg;

	/**
	 * The log level to use
	 *
	 * @var int
	 */
	private int $log_level;

	/**
	 * Exception object builder
	 *
	 * @param string     $message Exception message.
	 * @param int        $code Error code.
	 * @param int        $log_level Log level to use.
	 * @param \Throwable $previous Previous exception.
	 */
	public function __construct( $message = 'Unknown exception', $code = 0, $log_level = 0, \Throwable $previous = null ) {
		$this->log_level = intval( $log_level );
		if ( intval( $log_level ) > 3 ) {
			$this->log_level = 3;
		}
		if ( intval( $log_level ) < 0 ) {
			$this->log_level = 0;
		}
		parent::__construct( $message, intval( $code ), $previous );
	}

	/**
	 * Build log message
	 *
	 * @return void
	 */
	private function set_log_msg() {
		$log_msg = 'Error on line ' . $this->getLine() . ' in ' . $this->getFile() .
		' - [' . $this->getCode() . ']: ' . $this->getMessage() . PHP_EOL . $this->getTraceAsString();
		$options = get_option( 'no-unsafe-inline' );
		if (
			is_array( $options ) &&
			array_key_exists( 'log_driver', $options ) &&
			'db' === $options['log_driver']
		) {
			$log_msg       = nl2br( str_replace( NO_UNSAFE_INLINE_PLUGIN_DIR, '[…]', $log_msg ) );
			$this->log_msg = mb_strimwidth( $log_msg, 0, 1800, ' …', 'utf-8' );
		} else {
			$this->log_msg = $log_msg;
		}
	}

	/**
	 * Log excemption message
	 *
	 * @return void
	 */
	public function logexception() {
		$this->set_log_msg();
		switch ( $this->log_level ) {
			case self::DEBUG:
				Log::debug( $this->log_msg );
				break;
			case self::INFO:
				Log::info( $this->log_msg );
				break;
			case self::WARNING:
				Log::warning( $this->log_msg );
				break;
			case self::ERROR:
				Log::error( $this->log_msg );
				break;
			default:
				Log::debug( $this->log_msg );
		}
	}

	/**
	 * Custom string representation of object
	 *
	 * @return string
	 */
	public function __toString() {
		$this->set_log_msg();
		return __CLASS__ . ": $this->log_msg\n";
	}
}
