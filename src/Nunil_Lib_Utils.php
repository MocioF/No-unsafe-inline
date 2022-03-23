<?php
/**
 * Static utility functions
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with static utility functions used by the plugin
 */
class Nunil_Lib_Utils {

	/**
	 * Get the current page URL
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string
	 */
	public static function get_page_url() {
		$protocol = is_ssl() ? 'https://' : 'http://';
		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			$myurl = ( $protocol ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		} else {
			$myurl = '';
		}
		return $myurl;
	}

	/**
	 * Displays a message
	 *
	 * @since  1.0.0
	 * @access public
	 * @param string $message  The message to show.
	 * @param string $type     Default: success, values: success, warning, error.
	 * @return void
	 */
	public static function show_message( $message, $type = 'success' ): void {
		$notice = array(
			'type'    => $type,
			'message' => $message,
		);
		set_transient( 'no_unsafe_inline_admin_notice', $notice );
	}

	/**
	 * Clean the transients used to show notice messages
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public static function clean_messages() {
		delete_transient( 'no_unsafe_inline_admin_notice' );
	}


	/**
	 * Checks if all values in array are integers
	 *
	 * @since 1.0.0
	 * @param mixed $array The array to check.
	 * @return bool True if all values of $array are integers, false otherwise
	 */
	public static function is_array_of_integer_strings( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		foreach ( $array as $value ) {
			if ( ! is_numeric( $value ) ) {
				return false;
			} else {
				if ( ! ctype_digit( $value ) ) {
					return false;
				}
			}
		}
		return true;
	}

}
