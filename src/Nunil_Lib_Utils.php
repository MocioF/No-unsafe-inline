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
	 * @param mixed $myarray The array to check.
	 * @return bool True if all values of $array are integers, false otherwise
	 */
	public static function is_array_of_integer_strings( $myarray ) {
		if ( ! is_array( $myarray ) ) {
			return false;
		}
		foreach ( $myarray as $value ) {
			if ( ! is_numeric( $value ) ) {
				return false;
			}
			if ( ! is_string( $value ) ) {
				return false;
			}
			if ( ! ctype_digit( $value ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns true if sha hashes are needed for a resource
	 *
	 * We need to hash only
	 * <script> to use it both for CSP source values and in SRI
	 * <link> to use it in SRI
	 *
	 * @since 1.1.2
	 * @param string $directive The CSP src- directive.
	 * @param string $tagname The HTML tag name.
	 * @return bool
	 */
	public static function is_resource_hash_needed( $directive, $tagname ): bool {
		if (
				( 'script' === $tagname && ( 'script-src' === $directive || 'script-src-elem' === $directive ) ) ||
				( 'link' === $tagname && ( 'style-src' === $directive || 'style-src-elem' === $directive ) ) ||
				( 'worker-src' === $directive )
			) {
			return true;
		}
		return false;
	}

	/**
	 * Casts $var to admitted type for strval or returns null
	 *
	 * @since 1.1.2
	 * @param mixed $myvar The var to get string value from.
	 * @return bool|float|int|resource|string|null
	 */
	public static function cast_strval( $myvar ) {
		$new_var = $myvar;
		if (
				is_bool( $new_var ) ||
				is_float( $new_var ) ||
				is_int( $new_var ) ||
				is_resource( $new_var ) ||
				is_string( $new_var ) ||
				null === $new_var
			) {
			return( $new_var );
		} else {
			return null;
		}
	}

	/**
	 * Casts $var to admitted type for intval or returns null
	 *
	 * @since 1.1.2
	 * @param mixed $myvar The var to get int value from.
	 * @return array<mixed>|bool|float|int|resource|string|null
	 */
	public static function cast_intval( $myvar ) {
		$new_var = $myvar;
		if (
				is_array( $new_var ) ||
				is_bool( $new_var ) ||
				is_float( $new_var ) ||
				is_int( $new_var ) ||
				is_resource( $new_var ) ||
				is_string( $new_var ) ||
				null === $new_var
			) {
			return( $new_var );
		} else {
			return null;
		}
	}

	/**
	 * Writes in a WP_Option the last time plugin tables have been modified
	 *
	 * @param string $table_name The internal (unprefixed) table name.
	 * @return void
	 */
	public static function set_last_modified( $table_name ) {
		$nunil_lm_opt = 'no-unsafe-inline-dbtables-mtime';
		$now          = time();

		$default       = array(
			'event_handlers'   => $now,
			'external_scripts' => $now,
			'inline_scripts'   => $now,
			'logs'             => $now,
			'occurences'       => $now,
		);
		$last_modifies = get_option( $nunil_lm_opt, $default );
		if ( is_array( $last_modifies ) && array_key_exists( $table_name, $last_modifies ) ) {
			$last_modifies[ $table_name ] = $now;
			update_option( $nunil_lm_opt, $last_modifies, true );
		}
	}

	/**
	 * Checks if all elements of array are strings
	 *
	 * @param mixed $value The array to check.
	 * @phpstan-assert-if-true array<string> $value
	 */
	public static function is_one_dimensional_string_array( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $element ) {
			if ( is_array( $element ) || ! is_string( $element ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Move a value to a position in array
	 *
	 * @param array<int, mixed> $simplearray The array to be reordered.
	 * @param mixed             $value The value to be searched.
	 * @param integer           $newpos The new array key.
	 * @return void
	 */
	public static function move_array_element( &$simplearray, $value, $newpos ) {
		$oldpos = array_search( $value, $simplearray, true );
		if ( false === $oldpos ) {
			return;
		}
		array_splice( $simplearray, max( $newpos, 0 ), 0, array_splice( $simplearray, max( $oldpos, 0 ), 1 ) );
	}
}
