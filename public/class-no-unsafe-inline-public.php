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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_styles(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// ~ wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/no-unsafe-inline-public.min.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// ~ wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/no-unsafe-inline-public.min.js', array( 'jquery' ), $this->version, false );

		$options = (array) get_option( 'no-unsafe-inline' );
		$tools   = (array) get_option( 'no-unsafe-inline-tools' );
		if ( ( 1 === $tools['enable_protection'] || 1 === $tools['test_policy'] || 1 === $tools['capture_enabled'] ) &&
		( 1 === $options['fix_setattribute_style'] )
			) {
			wp_enqueue_script( $this->plugin_name . '_jquery-htmlprefilter-override', plugin_dir_url( __FILE__ ) . '../includes/js/no-unsafe-inline-prefilter-override.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '_fix_setattribute_style', plugin_dir_url( __FILE__ ) . '../includes/js/no-unsafe-inline-fix-style.js', array(), $this->version, false );
		}
	}

	/**
	 * Check if string is json
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $string The string to check.
	 * @return bool
	 */
	private function is_json( $string ) {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
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
		if ( $this->is_json( $htmlsource ) ) {
			return $htmlsource;
		}

		$options = (array) get_option( 'no-unsafe-inline' );
		$tools   = (array) get_option( 'no-unsafe-inline-tools' );

		if ( 1 === $tools['capture_enabled'] ) {
			$capture = new NUNIL\Nunil_Capture();
			$capture->load_html( $htmlsource );

			$capture_tags = new \NUNIL\Nunil_Captured_Tags();
			$tags         = $capture_tags->get_captured_tags();

			$capture->capture_tags( $tags );

			if ( 0 === $options['use_unsafe-hashes'] ) {
				$capture->capture_handlers();
				$capture->capture_inline_style();
			}
		}

		if ( 1 === $tools['test_policy'] || 1 === $tools['enable_protection'] ) {
			if ( false === is_admin() || ( true === is_admin() && 1 === $options['protect_admin'] ) ) {
				$manipulated = new NUNIL\Nunil_Manipulate_DOM();
				$manipulated->load_html( $htmlsource );
				$this->csp_local_whitelist = $manipulated->get_local_csp();
				$htmlsource                = $manipulated->get_manipulated();
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
	 * @return void
	 */
	public function output_csp_headers() {
		$options = (array) get_option( 'no-unsafe-inline' );
		$tools   = (array) get_option( 'no-unsafe-inline-tools' );

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
						$dir = str_replace( '_base_rule', '', $directive );
						$csp = trim( strval( $base_sources ) );
						if ( 'script-src' === $dir && 1 === $options['use_strict-dynamic'] ) {
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
							'script-src' === $dir || 'style-src' === $dir ) {
								$csp = $csp . ' \'report-sample\'';
							}
						}
						$header_csp = trim( $header_csp ) . ' ' . $dir . ' ' . trim( $csp ) . '; ';
					}

					$report_uri       = '';
					$header_report_to = '';
					$report_to        = '';

					if ( 1 === $tools['capture_enabled'] || 1 === $tools['enable_protection'] || 1 === $tools['test_policy'] ) {
						if ( 1 === $tools['capture_enabled'] ) {
							$report_uri = $report_uri . site_url( '/wp-json/no-unsafe-inline/v1/capture-by-violation' ) . ' ';
						}
						if ( true === $endpoints_in_use ) {
							if ( is_array( $options['endpoints'] ) ) { // This check should be unuseful.
								foreach ( $options['endpoints'] as $url ) {
									$report_uri = $report_uri . $url . ' ';
								}
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
								if ( is_array( $options['endpoints'] ) ) { // This check should be unuseful.
									foreach ( $options['endpoints'] as $url ) {
										$my_endpoints = $my_endpoints
										. '{ "url": "' . $url . '" }, ';
									}
									$my_endpoints = substr( $my_endpoints, 0, strlen( $my_endpoints ) - 2 );
								}

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

					if ( ! headers_sent( $filename, $linenum ) ) {
						header( $header_csp );
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
}
