<?php

namespace NUNIL;

class Nunil_Debug {
	public static function var_error_log( $object = null ) {
		ob_start();                    // start buffer capture
		var_dump( $object );           // dump the values
		$contents = ob_get_contents(); // put the buffer into a variable
		ob_end_clean();                // end capture
		error_log( $contents );        // log contents of the result of var_dump( $object )
	}

	/**
	 * Temp function to report logs in developing state
	 *
	 * @param mixed $log The string to log.
	 * @return void
	 */
	public static function report_log( $log ): void {
		$file = NO_UNSAFE_INLINE_PLUGIN_DIR . '/logs.txt';
		$open = fopen( $file, 'a+' );
		if ( $open ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				$write = fputs( $open, '[' . wp_date( 'Y-m-d H:i:s' ) . '] ' . print_r( $log, true ) . PHP_EOL );
			} else {
				$write = fputs( $open, '[' . wp_date( 'Y-m-d H:i:s' ) . '] ' . $log . PHP_EOL );
			}
			fclose( $open );
		}
	}
}
