<?php
/**
 * The file that turns on output buffering
 *
 * This file is a mu-plugin that turns on output buffering to buffer the
 * entire WP process. It collects the buffer's output in the final output
 * that the plugin will parse and manipulate.
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/mu-plugins
 *
 * @link  https://profiles.wordpress.org/mociofiletto/
 * @link  https://stackoverflow.com/a/22818089/17718976
 * @since 1.0.0
 */

ob_start();

add_action(
	'shutdown',
	function () {
		$final = '';

		/**
		 * We'll need to get the number of ob levels we're in, so that
		 * we can iterate over each, collecting that buffer's output
		 * into the final output.
		 */
		$levels = ob_get_level();

		for ( $i = 0; $i < $levels; $i++ ) {
			$final .= ob_get_clean();
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

			do_action( 'nunil_output_csp_headers' );
		} else {
			$manipulated = $final;
		}
		// phpcs:ignore
		echo $manipulated;
	},
	0
);
