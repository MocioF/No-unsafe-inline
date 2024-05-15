<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/public
 */

use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;


/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/public
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Array of [src-directive] [hash or nonce] for whitelisted inline scripts
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<array{directive: string, source: string}> $csp_local_whitelist returned by get method.
	 */
	private $csp_local_whitelist;

	/**
	 * Capture instance used when capture is enabled
	 *
	 * @since 1.2.2
	 * @access private
	 * @var \NUNIL\Nunil_Capture The Nunil_Capture instance used.
	 */
	private $capture;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->get_capture();
	}

	/**
	 * Initialize the capture instance if capture is enabled
	 *
	 * @since 1.2.2
	 * @return void
	 */
	private function get_capture() {
		$tools = (array) get_option( 'no-unsafe-inline-tools' );
		if ( array_key_exists( 'capture_enabled', $tools ) && 1 === $tools['capture_enabled'] ) {
			$this->capture = new NUNIL\Nunil_Capture();
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_styles(): void {
	}

	/**
	 * Register the JavaScript for the public-facing side of the site
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$plugin_name = $this->plugin_name; // keep as placeholder.
		$version     = $this->version; // keep as placeholder.
		$plugin      = new No_Unsafe_Inline();
		$plugin->enqueue_common_scripts();
	}

	/**
	 * Check if string is json
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $mystring The string to check.
	 * @return bool
	 */
	private function is_json( $mystring ) {
		json_decode( $mystring );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Check if string is XML
	 *
	 * @since 1.2.1
	 * @access private
	 * @param string $mystring The string to check.
	 * @return bool
	 */
	private function is_xml( $mystring ) {
		return ( 0 === strpos( $mystring, '<?xml' ) );
	}

	/**
	 * Check if string is HTML
	 *
	 * @since 1.0.2
	 * @access private
	 * @param string $mystring The string to check.
	 * @return bool
	 */
	private function is_html( $mystring ) {
		if ( $this->is_json( $mystring ) ) {
			return false;
		}
		if ( $this->is_xml( $mystring ) ) {
			return false;
		}
		return wp_strip_all_tags( $mystring ) !== trim( $mystring ) ? true : false;
	}

	/**
	 * Filter the final output.
	 *
	 * This is the callback of the mu-plugin filter.
	 * If enabled, captures records in database.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $htmlsource The page generated at the end of the wp process.
	 * @return string The manipulated output if protection or test protection is enabled
	 *                the input htmlsource if it is not enabled or it is a json answer.
	 */
	public function filter_final_output( $htmlsource ) {
		if ( false === $this->is_html( $htmlsource ) ) {
			return $htmlsource;
		}
		$options = (array) get_option( 'no-unsafe-inline' );
		$tools   = (array) get_option( 'no-unsafe-inline-tools' );

		if ( 1 === $tools['capture_enabled'] ) {
			$this->capture->load_html( $htmlsource );

			if ( class_exists( 'Fiber' ) ) {
				global $nunil_fibers;
				$capture        = $this->capture;
				$nunil_fibers[] = new Fiber(
					function () use ( $capture, $options ) {
						$capture_tags = new \NUNIL\Nunil_Captured_Tags();
						$tags         = $capture_tags->get_captured_tags();

						$capture->capture_tags( $tags );

						if ( 0 === $options['use_unsafe-hashes'] ) {
								$capture->capture_handlers();
								$capture->capture_inline_style();
						}
					}
				);
			} else {
				$capture_tags = new \NUNIL\Nunil_Captured_Tags();
				$tags         = $capture_tags->get_captured_tags();

				$this->capture->capture_tags( $tags );

				if ( 0 === $options['use_unsafe-hashes'] ) {
					$this->capture->capture_handlers();
					$this->capture->capture_inline_style();
				}
			}
		}

		if ( 1 === $tools['test_policy'] || 1 === $tools['enable_protection'] ) {
			if ( false === is_admin() || ( true === is_admin() && 1 === $options['protect_admin'] ) ) {
				$manipulated = new NUNIL\Nunil_Manipulate_DOM();
				$manipulated->load_html( $htmlsource );
				$this->csp_local_whitelist = $manipulated->get_local_csp();
				$htmlsource                = $manipulated->debug_preamble . $manipulated->get_manipulated();
			}
		}
		return $htmlsource;
	}

	/**
	 * Output the CSP header
	 *
	 * The CSP header is built from base rule options, the content of
	 * csp_local_whitelist property and some settings in no-unsafe-inline
	 * option.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $htmlsource The page generated at the end of the wp process.
	 * @return void
	 */
	public function output_csp_headers( $htmlsource ) {
		/**
		 * Policy has to be delivered only for documents (html or xml) and Workers.
		 * https://www.w3.org/TR/CSP2/#which-policy-applies
		 * https://www.w3.org/TR/CSP3/#goals
		 */
		if ( false === $this->is_html( $htmlsource ) ) {
			return;
		}
		$options = (array) get_option( 'no-unsafe-inline' );
		$tools   = (array) get_option( 'no-unsafe-inline-tools' );

		global $nunil_csp_meta;

		if ( 1 === $tools['test_policy'] || 1 === $tools['enable_protection'] ) {
			if ( false === is_admin() || ( true === is_admin() && 1 === $options['protect_admin'] ) ) {
				if ( 1 === $tools['test_policy'] ) {
					$header_csp = 'Content-Security-Policy-Report-Only: ';
				}
				if ( 1 === $tools['enable_protection'] ) {
					$header_csp = 'Content-Security-Policy: ';
				}

				if ( isset( $header_csp ) ) {
					if ( 1 === $options['use_reports']
						&& isset( $options['endpoints'] )
						&& is_array( $options['endpoints'] )
						&& ! empty( $options['endpoints'] ) ) {
							$endpoints_in_use = true;
					} else {
						$endpoints_in_use = false;
					}

					// The Content Security Policy directive 'upgrade-insecure-requests' is ignored when delivered in a report-only policy.
					if ( 1 === $options['no-unsafe-inline_upgrade_insecure'] && 1 !== $tools['test_policy'] ) {
						$header_csp = $header_csp . 'upgrade-insecure-requests; ';
					}

					$base_src = (array) get_option( 'no-unsafe-inline-base-rule' );

					foreach ( $base_src as $directive => $base_sources ) {
						$dir     = str_replace( '_base_rule', '', $directive );
						$csp     = trim( strval( Utils::cast_strval( $base_sources ) ) );
						$enabled = $dir . '_enabled';
						if ( ( 'script-src' === $dir || 'default-src' === $dir ) && 1 === $options['use_strict-dynamic'] ) {
							$csp = $csp . ' \'strict-dynamic\'';
						}
						// If in base rules is set 'none' for a directive, don't add anything to that.
						if ( is_array( $this->csp_local_whitelist ) && '\'none\'' !== $csp ) {
							foreach ( $this->csp_local_whitelist as $local ) {
								if ( $dir === $local['directive'] ) {
									$csp = $csp . ' \'' . $local['source'] . '\'';
								}
							}
							if ( ( 1 === $tools['capture_enabled'] || true === $endpoints_in_use ) &&
							( 'script-src' === $dir || 'style-src' === $dir ) ) {
								$csp = $csp . ' \'report-sample\'';
							}
						}
						if ( 1 === $options[ $enabled ] ) { // add only rule for enabled directives.
							$header_csp = trim( $header_csp ) . ' ' . $dir . ' ' . trim( $csp ) . '; ';
						}
					}

					$report_uri       = '';
					$header_report_to = '';
					$report_to        = '';

					if ( 1 === $tools['capture_enabled'] || 1 === $tools['enable_protection'] || 1 === $tools['test_policy'] ) {
						if ( 1 === $tools['capture_enabled'] ) {
							$report_uri = $report_uri . site_url( '/wp-json/no-unsafe-inline/v1/capture-by-violation' ) . ' ';
						}
						if ( true === $endpoints_in_use ) {
							foreach ( $options['endpoints'] as $url ) {
								$report_uri = $report_uri . $url . ' ';
							}
						}

						if ( 1 === $tools['capture_enabled'] || true === $endpoints_in_use ) {
							$header_report_to = $header_report_to
							. '{ "group": ';
							if ( '' !== $options['group_name'] && true === $endpoints_in_use ) {
								$header_report_to = $header_report_to . '"' . $options['group_name'] . '", ';
								$report_to        = $report_to . $options['group_name'];
							} else {
								$header_report_to = $header_report_to . '"csp-endpoint", ';
								$report_to        = $report_to . 'csp-endpoint';
							}
							$header_report_to = $header_report_to
							. '"max_age": ';
							if ( '' !== $options['max_age'] && true === $endpoints_in_use ) {
								$header_report_to = $header_report_to . $options['max_age'] . ', ';
							} else {
								$header_report_to = $header_report_to . '10886400, ';
							}
							$header_report_to = $header_report_to
							. '"endpoints": [';
							if ( 1 === $tools['capture_enabled'] ) {
								$header_report_to = $header_report_to
								. '{ "url": "' . site_url( '/wp-json/no-unsafe-inline/v1/capture-by-violation' ) . '" }';
							}
							if ( true === $endpoints_in_use ) {
								$my_endpoints = '';
								foreach ( $options['endpoints'] as $url ) {
									$my_endpoints = $my_endpoints
									. '{ "url": "' . $url . '" }, ';
								}
									$my_endpoints = substr( $my_endpoints, 0, strlen( $my_endpoints ) - 2 );

								if ( 1 === $tools['capture_enabled'] ) {
									$header_report_to = $header_report_to . ', ' . $my_endpoints;
								} else {
									$header_report_to = $header_report_to . $my_endpoints;
								}
							}
							$header_report_to = $header_report_to . '] }';
						}
					}
					if ( 0 < strlen( $report_uri ) ) {
						$header_csp = $header_csp . ' report-uri ' . $report_uri . ';';
					}
					if ( 0 < strlen( $report_to ) ) {
						$header_csp = $header_csp . ' report-to ' . $report_to . ';';
					}

					if ( 0 < strlen( $header_report_to ) ) {
						if ( ! headers_sent( $filename, $linenum ) ) {
							header( 'Report-To: ' . $header_report_to );
						} else {
							Log::warning(
								sprintf(
									// translators: %1$s is the filename of the file that sent headers, %2$d is the line in filename where headers where sent.
									esc_html__( 'CSP headers not sent because headers were sent by %1$s at line %2$d', 'no-unsafe-inline' ),
									$filename,
									$linenum
								)
							);
						}
					}

					if ( ! headers_sent( $filename, $linenum ) ) {

						/**
						 * Apache set a limit to max size of header sent.
						 * Its default is 8190.
						 * We need a strategy to send the CSP when hashes are too long.
						 * In order:
						 * 1. We remove optional ascii white spaces
						 * 2. We try to reduce size by allowing all img ( = set "img-src *;")
						 * 3. We Deploy simplified policy to meta tag
						 */
						$max_http_header_size             = $options['max_response_header_size'] ? $options['max_response_header_size'] : 8192;
						$res_current_response_header_size = self::response_headers_size();

						// Keep a 100 byte as a security buffer.
						$max_csp_allowed_size = $max_http_header_size - $res_current_response_header_size - 100;

						$csp_size = strlen( $header_csp );
						if ( $csp_size > $max_csp_allowed_size ) {
							if ( 'nonce' !== $options['inline_scripts_mode'] ) {
								Log::warning( esc_html__( 'CSP header is too long: please try to use \'nonce\' for inline_scripts_mode', 'no-unsafe-inline' ) );
							}

							// 1. We remove optional-ascii-whitespace
							$header_csp = str_replace( ' ;', ';', $header_csp );
							$header_csp = str_replace( '; ', ';', $header_csp );
							Log::warning( esc_html__( 'CSP header is too long: removed optional-ascii-whitespace', 'no-unsafe-inline' ) );
						}

						$csp_size = strlen( $header_csp );
						if ( $csp_size > $max_csp_allowed_size ) {
							// 2. We reduce img-src to *;
							$header_csp = preg_replace( '/img-src(.*?);/m', 'img-src *;', $header_csp );
							Log::warning( esc_html__( 'CSP header is too long: img-src was reduced to * (every image allowed)', 'no-unsafe-inline' ) );
						}

						if ( ! is_null( $header_csp ) ) {
							$csp_size = strlen( $header_csp );
							if ( $csp_size > $max_csp_allowed_size ) {
								// 3. Moving policy to meta
								$csp_meta_fallback = preg_replace(
									array(
										'/report-uri(.*?);/m',
										'/report-to(.*?);/m',
										'/frame-ancestors(.*?);/m',
										'/sandbox(.*?);/m',
										'/Content-Security-Policy-Report-Only: /',
										'/Content-Security-Policy: /',
									),
									array( '', '', '', '', '', '' ),
									$header_csp
								);
								$nunil_csp_meta    = '<meta http-equiv="Content-Security-Policy" content="' . $csp_meta_fallback . '">';
								// translators: %s is &lt;meta http-equiv&gt;.
								Log::warning( sprintf( esc_html__( 'CSP header is too long: reduced CSP was deployed via %s', 'no-unsafe-inline' ), '&lt;meta http-equiv&gt;' ) );
							}
						}
						if ( '' === $nunil_csp_meta && ( ! is_null( $header_csp ) ) ) {
							header( $header_csp );
						}
					} else {
						NUNIL\Nunil_Lib_Log::warning(
							sprintf(
								// translators: %1$s is the filename of the file that sent headers, %2$d is the line in filename where headers where sent.
								esc_html__( 'CSP headers not sent because headers were sent by %1$s at line %2$d', 'no-unsafe-inline' ),
								$filename,
								$linenum
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Register capture route
	 *
	 * We use this route to capture some fetch sources by violation.
	 * This route is created only when capture is enabled, and is used
	 * only if while capturing, is enabled test_policy or protection.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_capture_routes(): void {
		$tools = (array) get_option( 'no-unsafe-inline-tools' );
		if ( 1 === $tools['capture_enabled'] ) {
			$capture_1 = new NUNIL\Nunil_Capture_CSP_Violations();
			register_rest_route(
				'no-unsafe-inline/v1',
				'/capture-by-violation',
				array(
					'methods'             => array( 'GET', 'POST' ),
					'callback'            => array( $capture_1, 'capture_violations' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * If meta http-equiv has been stored in variable.
	 * filters output adding the <meta> in header with CSP.
	 *
	 * @since 1.0.1
	 * @access public
	 * @param string $htmlsource The page generated at the end of the wp process, pre filtered.
	 * @return string The manipulated output with meta injected, if setted.
	 */
	public function filter_manipulated( $htmlsource ) {
		global $nunil_csp_meta;
		if ( '' === $nunil_csp_meta || $this->is_json( $htmlsource ) ) {
			return $htmlsource;
		}
		$htmlsource_with_meta = str_replace( '</head>', $nunil_csp_meta . '</head>', $htmlsource );
		return $htmlsource_with_meta;
	}

	/**
	 * Returns the number of bytes of HTTP headers
	 *
	 * @since 1.0.1
	 * @access private
	 * @return int
	 */
	private static function response_headers_size() {
		if ( ! function_exists( 'apache_response_headers' ) ) {
			$arh     = array();
			$headers = headers_list();
			foreach ( $headers as $header ) {
				$header                        = explode( ':', $header );
				$arh[ array_shift( $header ) ] = trim( implode( ':', $header ) );
			}
			$size = mb_strlen( serialize( $arh ), '8bit' );
		} else {
			$size = mb_strlen( serialize( (array) apache_response_headers() ), '8bit' );
		}
		return $size;
	}

	/**
	 * Upgrades external_scripts entries after upgrade
	 *
	 * This function is hooked on upgrader_process_complete.
	 *
	 * @since 1.1.5
	 * @access public
	 * @param \WP_Upgrader|\Theme_Upgrader|\Plugin_Upgrader                                                              $upgrader_object WP_Upgrader instance.
	 * @param array{'action': string, 'type': string, 'bulk': bool, 'plugins'?: array<string>, 'themes'?: array<string>} $options Array of bulk item update data.
	 * @return void
	 */
	public function update_external_script_entries( $upgrader_object, $options ): void {
		NUNIL\Nunil_Script_Upgrader::update_external_script_entries( $upgrader_object, $options );
	}

	/**
	 * Sets/Updates a transient with data of the upgraded plugin/theme
	 *
	 * This function is hooked on upgrader_pre_install hook.
	 *
	 * @since 1.1.5
	 * @access public
	 * @param  bool|\WP_Error                                                                                                 $response Installation response.
	 * @param array{'plugin'?: string, 'theme'?: string, 'temp_backup': array{'slug': string, 'src': string, 'dir': string}} $hook_extra Array of bulk item update data.
	 * @return void
	 */
	public function set_info_from_upgrader_pre_install( $response, $hook_extra ): void {
		NUNIL\Nunil_Script_Upgrader::set_info_from_upgrader_pre_install( $response, $hook_extra );
	}

	/**
	 * Sets/Updates a transient with data on core upgrade
	 *
	 * This function is hooked on update_feedback hook.
	 *
	 * @since 1.1.5
	 * @access public
	 * @return void
	 */
	public function set_info_from_core_upgrader(): void {
		NUNIL\Nunil_Script_Upgrader::set_info_from_core_upgrader();
	}
}
