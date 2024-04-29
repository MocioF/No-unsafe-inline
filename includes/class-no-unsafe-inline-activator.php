<?php
/**
 * Fired during plugin activation
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/includes
 */

use NUNIL\Nunil_Manage_Muplugin;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Exception;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/includes
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Activator {

	/**
	 * Run activation routine for multisite plugin.
	 *
	 * If is a multisite installation, we run an activation routine for each blog.
	 *
	 * @since 1.0.0
	 * @param bool $network_wide Indicates if the plugin is network activated.
	 * @return void
	 */
	public static function activate( $network_wide ): void {
		$check_versions   = self::nunil_check_minimum_versions();
		$check_extensions = self::check_php_required_extensions();
		$check_build_opts = self::check_php_build_options();
		$error            = new \WP_Error();
		if ( false === $check_versions ) {
			$error_msg = sprintf(
				// translators: %1$s is a php version; %2$s is a wp version.
				esc_html__(
					'no-unsafe-inline requires minimum php version %1$s and minimum WP version %2$s.',
					'no-unsafe-inline'
				),
				NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION,
				NO_UNSAFE_INLINE_MINIMUM_WP_VERSION
			);
			$error->add( 'minimum_versions', $error_msg );
		}
		if ( '' !== $check_extensions ) {
			$error_msg = sprintf(
				// translators: %s is a list of PHP extensions separated by space.
				esc_html__(
					'no-unsafe-inline requires some PHP extensions to work. The following extension are needed and are not loaded on your server: %s',
					'no-unsafe-inline'
				),
				$check_extensions
			);
			$error->add( 'required_extensions', $error_msg );
		}
		if ( '' !== $check_build_opts ) {
			$error_msg = sprintf(
				// translators: %s is a list of PHP build options separated by space.
				esc_html__(
					'no-unsafe-inline requires PHP to be built with some configure options. It seems that PHP is built with the following NOT compatible options: %s',
					'no-unsafe-inline'
				),
				$check_build_opts
			);
			$error->add( 'php_build_options', $error_msg );
		}
		if ( 0 === count( $error->get_error_codes() ) ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
				if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
					$args  = array(
						'orderby' => 'id',
						'order'   => 'asc',
					);
					$sites = get_sites( $args );
				} else {
					// WP < 4.6; however it is unsupported.
					$sites = wp_get_sites();
				}
				if ( is_iterable( $sites ) && ! empty( $sites ) ) {
					foreach ( $sites as $site ) {
						if ( is_object( $site ) ) {
							switch_to_blog( intval( $site->blog_id ) );
						} else {
							switch_to_blog( intval( $site['blog_id'] ) );
						}
						if ( ! is_plugin_active( 'no-unsafe-inline/no-unsafe-inline.php' ) ) {
							self::single_activate();
						}
						restore_current_blog();
					}
				} else {
					// on wp < 4.6 wp_get_sites() return empty array if nework is bigger than 10000 sites.
					$error_msg = esc_html__( 'no-unsafe-inline cannot be network activated on very big networks.', 'no-unsafe-inline' );
					$error->add( 'too_big_network', $error_msg );
				}
			} else {
				self::single_activate();
			}
		}
		if ( 0 < count( $error->get_error_codes() ) ) {
			$codes         = $error->get_error_codes();
			$admin_message = '';
			foreach ( $codes as $error_code ) {
				$admin_message .= '<p><b>' . $error_code . '</b>: ' . $error->get_error_message( $error_code ) . '</p>';
			}

			$allowed_html = array(
				'p' => array(),
				'b' => array(),
			);

			wp_die(
				wp_kses( $admin_message, $allowed_html ),
				esc_html__( 'no-unsafe-inline activation error', 'no-unsafe-inline' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Activate the plugin.
	 *
	 * On plugin activation we install the mu-plugin and create the tables.
	 *
	 * @since    1.0.0
	 * @param bool $network_wide Indicates if the plugin is network activated.
	 * @return void
	 */
	public static function single_activate( $network_wide = false ): void {
		set_time_limit( 360 );
		DB::db_create();
		self::disable_all_tools();
		self::set_default_options();

		if ( ! Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
			try {
				Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
			} catch ( Nunil_Exception $ex ) {
				$ex->logexception();
				require_once __DIR__ . '/class-no-unsafe-inline-deactivator.php';
				\No_Unsafe_Inline_Deactivator::deactivate( $network_wide );
			}
		}
		Log::info( 'Activated plugin.' );
	}

	/**
	 * Sets option used for tools at default value (all disabled)
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private static function disable_all_tools(): void {
		$tools          = get_option( 'no-unsafe-inline-tools' );
		$tools_disabled = array(
			'capture_enabled'   => 0,
			'test_policy'       => 0,
			'enable_protection' => 0,
		);
		if ( false !== $tools ) {
			delete_option( 'no-unsafe-inline-tools' );
		}
		add_option( 'no-unsafe-inline-tools', $tools_disabled );
	}

	/**
	 * Sets default plugin options if not in database
	 *
	 * @since = 1.0.0
	 * @access private
	 * @return void
	 */
	private static function set_default_options(): void {
		$plugin_options = get_option( 'no-unsafe-inline' );
		if ( false === $plugin_options ) {
			$plugin_options = array();
			$class          = new No_Unsafe_Inline();
			foreach ( $class->managed_directives as $src_directive ) {
				$plugin_options[ $src_directive . '_enabled' ] = 1;
			}
			$plugin_options['prefetch-src_enabled'] = 0;

			$plugin_options['external_host_mode'] = 'sch-host';
			$plugin_options['script-src_mode']    = 'nonce';
			$plugin_options['style-src_mode']     = 'nonce';

			/**
			 * In CSP3 hashes are only allowed for inline script, inline styles and external script
			 * but support for external styles or imgs in the specification has not been announced
			 * https://www.w3.org/TR/CSP3/#external-hash
			 *
			 * $plugin_options['img-src_mode']                      = 'none';
			 */

			$plugin_options['sri_sha256']                        = 1;
			$plugin_options['sri_sha384']                        = 0;
			$plugin_options['sri_sha512']                        = 0;
			$plugin_options['sri_script']                        = 1;
			$plugin_options['sri_link']                          = 1;
			$plugin_options['inline_scripts_mode']               = 'nonce';
			$plugin_options['use_strict-dynamic']                = 0;
			$plugin_options['no-unsafe-inline_upgrade_insecure'] = 1;
			$plugin_options['protect_admin']                     = 1;
			$plugin_options['use_unsafe-hashes']                 = 0;
			$plugin_options['fix_setattribute_style']            = 1;
			$plugin_options['add_wl_by_cluster_to_db']           = 1;
			$plugin_options['log_level']                         = 'error';
			$plugin_options['log_driver']                        = 'db';
			$plugin_options['remove_tables']                     = 0;
			$plugin_options['remove_options']                    = 0;
			$plugin_options['use_reports']                       = 0;
			$plugin_options['group_name']                        = 'csp-endpoint';
			$plugin_options['max_age']                           = 10886400;
			$plugin_options['max_response_header_size']          = 8192;
		}
		add_option( 'no-unsafe-inline', $plugin_options );

		$base_rule = get_option( 'no-unsafe-inline-base-rule' );

		if ( false === $base_rule ) {
			$base_rule = array();
		} else {
			$base_rule = (array) $base_rule;
		}
		$class = new No_Unsafe_Inline();
		foreach ( $class->managed_directives as $src_directive ) {
			switch ( $src_directive ) {
				case 'object-src':
					if ( ! array_key_exists( $src_directive . '_base_rule', $base_rule ) || '' === trim( strval( $base_rule[ $src_directive . '_base_rule' ] ) ) ) {
						$base_rule[ $src_directive . '_base_rule' ] = '\'none\'';
					}
					break;
				case 'prefetch-src':
					if ( ! array_key_exists( $src_directive . '_base_rule', $base_rule ) || '' === trim( strval( $base_rule[ $src_directive . '_base_rule' ] ) ) ) {
						$base_rule[ $src_directive . '_base_rule' ] = '\'none\'';
					}
					break;
				case 'frame-ancestors':
					if ( ! array_key_exists( $src_directive . '_base_rule', $base_rule ) || '' === trim( strval( $base_rule[ $src_directive . '_base_rule' ] ) ) ) {
						$base_rule[ $src_directive . '_base_rule' ] = 'https:';
					}
					break;
				default:
					if ( ! array_key_exists( $src_directive . '_base_rule', $base_rule ) || '' === trim( strval( $base_rule[ $src_directive . '_base_rule' ] ) ) ) {
						$base_rule[ $src_directive . '_base_rule' ] = '\'self\'';
					}
			}
		}
		update_option( 'no-unsafe-inline-base-rule', $base_rule );
	}

	/**
	 * Running setup whenever a new blog is created
	 *
	 * @since 1.0.0
	 * @param WP_Site $params New site object.
	 * @return void
	 */
	public static function add_blog( $params ) {
		if ( is_plugin_active_for_network( 'no-unsafe-inline/no-unsafe-inline.php' ) ) {
			switch_to_blog( intval( $params->blog_id ) );

			self::single_activate();

			restore_current_blog();
		}
	}

	/**
	 * Check if installed php version is compatible with plugin
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private static function nunil_check_minimum_versions() {
		global $wp_version;
		$php_version = phpversion();
		if ( version_compare( $php_version, NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION, '<' ) ||
		version_compare( $wp_version, NO_UNSAFE_INLINE_MINIMUM_WP_VERSION, '<' ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check if required php extensions are loaded
	 *
	 * @since 1.1.5
	 * @return string
	 */
	public static function check_php_required_extensions() {
		$needed   = '';
		$required = array(
			// no-unsafe-inline plugin.
			'ctype',
			'date',
			'dom',
			'filter',
			'hash',
			'json',
			'libxml',
			'mbstring',
			'pcre',
			// League\Uri\Components .
			array( 'bcmath', 'gmp' ),

			/**
			 * League\Uri 6 since 6.2; Not needed for League\Uri 7, but it needs PHP>=8.1
			 * https://uri.thephpleague.com/uri/6.0/#system-requirements
			 * https://uri.thephpleague.com/uri/7.0/#system-requirements
			 */
			'fileinfo',
		);

		/* php 8.2 */
		if (
			version_compare( PHP_VERSION, '8.2.0', '>=' ) &&
			version_compare( PHP_VERSION, '8.3.0', '<' )
		) {
			$required[] = 'random';
		}

		/* php 8.3 */
		if (
			version_compare( PHP_VERSION, '8.3.0', '>=' ) &&
			version_compare( PHP_VERSION, '8.4.0', '<' )
		) {
			$required[] = 'random';
		}

		foreach ( $required as $extension ) {
			if ( is_array( $extension ) ) {
				// Alternative extensions needed.
				$found = false;
				foreach ( $extension as $alternative ) {
					$found = $found || extension_loaded( $alternative );
				}
				if ( false === $found ) {
					$needed .= ' (';
					foreach ( $extension as $alternative ) {
						$needed .= $alternative . ' or ';
					}
					$needed  = substr( $needed, 0, strlen( $needed ) - 4 );
					$needed .= ')';
				}
			} elseif ( false === extension_loaded( $extension ) ) {
					$needed .= ' ' . $extension;
			}
		}
		if ( '' !== $needed && 'cli' === php_sapi_name() ) {
			echo PHP_VERSION . PHP_EOL;
			echo 'EXTENSIONS REQUIRED: ' . PHP_EOL;
			echo esc_html( $needed );
		}
		return $needed;
	}

	/**
	 * Check if PHP is built with some uncompatible options
	 *
	 * @since 1.1.6
	 * @return string
	 */
	public static function check_php_build_options() {
		$options        = '';
		$check_mb_regex = function_exists( 'mb_regex_encoding' );
		if ( false === $check_mb_regex ) {
			$options .= ' --disable-mbregex';
		}
		return $options;
	}
}
