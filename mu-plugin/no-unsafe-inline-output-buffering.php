<?php
/**
 * The file that turns on output buffering
 *
 * This file is a mu-plugin that turns on output buffering to buffer the
 * entire WP process. It collects the buffer's output in the final output
 * that the plugin will parse and manipulate.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/mu-plugin
 *
 * @link  https://profiles.wordpress.org/mociofiletto/
 * @link  https://stackoverflow.com/a/78469593/17718976
 * @since 1.0.0
 */

ob_start( 'no_unsafe_inline_mu_plugin_callback' );

add_action(
	'shutdown',
	function () {
		$final = '';

		/* @var string $nunil_csp_meta The <meta> to inject in page. */
		global $nunil_csp_meta;
		$nunil_csp_meta = '';

		/**
		 * We'll need to get the number of ob levels we're in, so that
		 * we can iterate over each, collecting that buffer's output
		 * into the final output.
		 */
		$levels = ob_get_level();

		/**
		 * With output_buffering turned on, there will be one more level managed
		 * by "default output handler" upper in the stack.
		 * Some plugins can open an output buffering level with
		 * ob_start( 'callback') trusting that the callback will be executed to
		 * modify the output before it is sent to the browser.
		 * Those level will be lower in the stack than this mu-plugin's
		 * buffering level.
		 * Callbacks are executed only on ob_flush() and not on ob_get_clean().
		 * If we capture and destroy these buffering levels with ob_get_clean()
		 * those callbacks will never been called and the output captured and
		 * modified by this mu-plugin could be different from the real output 
		 * that would have been sent to the browser with the mu-plugin not in 
		 * place.
		 * So we need to flush the levels that are down in the stack untill we
		 * reach our ob_level and then capture its content with
		 * ob_get_contents() to manipulate it.
		 * To catch this mu-plugin's buffering level, we open it with a specific
		 * callback that doesn't do anything, ut allows us to locate it in the
		 * stack pile by its name.
		 * 
		 * The for loop loops ob levels from the more internal one (when $i = 0)
		 * to the more external one.
		 * We calculate the $i value of our buffering level and then we flush
		 * all lower levels before capturing and modifying output.
		 *
		 * Not calling ob_get_clean on upper levels will respect the use of such
		 * a mechanics in another eventual mu-plugin that could open a buffer
		 * before this one (that will result in a level upper in the stack than
		 * the one opened by this mu-plugin).
		 */
		$status_info       = ob_get_status( true );
		$key               = no_unsafe_inline_search_by_name( 'no_unsafe_inline_mu_plugin_callback', $status_info );
		$mu_plugin_i_value = $levels - $key - 1;

		for ( $i = 0; $i <= $mu_plugin_i_value; $i++ ) {
			if ( $i !== $mu_plugin_i_value ) {
				ob_end_flush();
			} else {
				$final .= ob_get_clean();
			}
		}

		$tools = (array) get_option( 'no-unsafe-inline-tools' );
		if ( ( isset( $tools['test_policy'] ) && 1 === $tools['test_policy'] ) ||
		( isset( $tools['enable_protection'] ) && 1 === $tools['enable_protection'] ) ||
		( isset( $tools['capture_enabled'] ) && 1 === $tools['capture_enabled'] )
		) {
			/**
			 * Apply any filters to the final output
			 */
			$manipulated = apply_filters( 'no_unsafe_inline_final_output', $final );

			do_action( 'nunil_output_csp_headers', $manipulated );

			/**
			 * Inject meta http-equiv="Content-Security-Policy" if variable is set
			 */
			$manipulated = apply_filters( 'no_unsafe_inline_meta_injector', $manipulated );
		} else {
			$manipulated = $final;
		}
		// phpcs:ignore
		echo $manipulated;
		if ( class_exists( 'Fiber' ) ) {
			global $nunil_fibers;
			if ( is_array( $nunil_fibers ) ) {
				foreach ( $nunil_fibers as $i => $fiber ) {
					if ( $fiber instanceof Fiber ) {
						if ( ! $fiber->isStarted() ) {
							$fiber->start();
						}
						if ( $fiber->isSuspended() ) {
							$fiber->resume();
						}
					}
				}
			}
		}
	},
	0
);

/**
 * Callback of the ob_start
 *
 * The name is used to detect the buffer level started by the mu-plugin.
 *
 * @param string $data String present in the output buffer.
 * @return string
 */
function no_unsafe_inline_mu_plugin_callback( $data ) {
	return $data;
}

/**
 * Search for callback name in the 2 dimensional array returned by ob_get_status( true )
 * 
 * Returns the array key.
 *
 * @param string              $name The name of the callback.
 * @param array{string: name} $ob_status The array returned by ob_get_status().
 * @return int
 */
function no_unsafe_inline_search_by_name( $name, $ob_status ) {
	foreach ( $ob_status as $key => $val ) {
		if ( $val['name'] === $name ) {
			return $key;
		}
	}
	return null;
}
