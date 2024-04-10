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
 * @link  https://stackoverflow.com/a/22818089/17718976
 * @since 1.0.0
 */

ob_start();

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
