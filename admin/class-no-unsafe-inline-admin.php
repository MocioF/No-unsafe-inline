<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 */
 
 use NUNIL\Nunil_Lib_Db As DB;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Admin {

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
	 * The managed csp src directives of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array<string>    $managed_src_directives   The CSP -src directives managed by the plugin.
	 */
	private $managed_src_directives;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string        $plugin_name               The name of this plugin.
	 * @param    string        $version                   The version of this plugin.
	 * @param    array<string> $managed_src_directives    The CSP -src directives managed by this plugin.
	 */
	public function __construct( $plugin_name, $version, $managed_src_directives ) {
		$this->plugin_name            = $plugin_name;
		$this->version                = $version;
		$this->managed_src_directives = $managed_src_directives;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_styles(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in No_Unsafe_Inline_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The No_Unsafe_Inline_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen(); 
		if ( 'no-unsafe-inline' === $screen->id ) {
		
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/no-unsafe-inline-admin.css', array(), $this->version, 'all' );
			//~ wp_enqueue_style( $this->plugin_name . 'jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css', array(), $this->version, 'all' );
			//~ wp_enqueue_style( $this->plugin_name . 'jquery-ui-sctructure', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.structure.min.css', array(), $this->version, 'all' );
			
			//Enqueue the jQuery UI theme css file from google:
			$wp_scripts = wp_scripts();
	 
			wp_enqueue_style(
				'jquery-ui-theme-smoothness', //select ui theme: base...
				sprintf(
					'https://ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/smoothness/jquery-ui.css',
					$wp_scripts->registered['jquery-ui-core']->ver
				)
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in No_Unsafe_Inline_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The No_Unsafe_Inline_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$screen = get_current_screen(); 
		if ( 'no-unsafe-inline' === $screen->id ) {

			// ~ wp_enqueue_script( 'no-unsafe-inline-prefilter', plugin_dir_url( __FILE__ ) . '../includes/js/no-unsafe-inline.js', array( 'jquery' ), time(), false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/no-unsafe-inline-admin.js', array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-tabs', 'wp-i18n' ), $this->version, false );

			wp_localize_script(
				$this->plugin_name,
				'nunil_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
		
		$tools = (array) get_option( 'no-unsafe-inline-tools' );

		// VA IMPOSTATO A 1. ORA è disabilitato sempre
		if ( 22 === $tools['capture_enabled'] ) {
			wp_enqueue_script( $this->plugin_name . '_observer', plugin_dir_url( __FILE__ ) . '../includes/js/no-unsafe-inline.js', array(), time(), false ); // usa time() al posto di $this->version per costringere il reload

			wp_localize_script(
				$this->plugin_name . '_observer',
				'WPURLS',
				array(
					'restRoute' => get_rest_url( null, 'no-unsafe-inline/v1/capture-by-observer' ),
				)
			);
		}
	}

	/**
	 * Adds extra links to the plugin activation page
	 *
	 * @param  array<string> $meta   Extra meta links.
	 * @param  string        $file   Specific file to compare against the base plugin.
	 * @return array<string>          Return the meta links array
	 *
	 * @since    1.0.0
	 */
	public function nunil_get_extra_meta_links( $meta, $file ) {
		if ( NO_UNSAFE_INLINE_PLUGIN_BASENAME === $file ) {
			$plugin_page = admin_url( 'admin.php?page=no-unsafe-inline' );
			$meta[]      = "<a href='https://wordpress.org/support/plugin/no-unsafe-inline/' target='_blank' title'" . __( 'Support', 'no-unsafe-inline' ) . "'>" . __( 'Support', 'no-unsafe-inline' ) . '</a>';
			$meta[]      = "<a href='https://wordpress.org/support/plugin/no-unsafe-inline/reviews#new-post' target='_blank' title='" . __( 'Leave a review', 'no-unsafe-inline' ) . "'><i class='nunil-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
		}
		return $meta;
	}

	/**
	 * Print the WordPress directory plugin links
	 *
	 * @param array<string> $actions Array with links.
	 *
	 * @return array<string>
	 * @since    1.0.0
	 */
	public function plugin_directory_links( $actions ) {
		$links   = array(
			'<a href="' . admin_url( 'options-general.php?page=no-unsafe-inline' ) . '">' . esc_html__( 'Settings', 'no-unsafe-inline' ) . '</a>',
			'<a href="https://CHANGEME/" target="_blank">' . esc_html__( 'Documentation', 'no-unsafe-inline' ) . '</a>',
		);
		$actions = array_merge( $actions, $links );
		return $actions;
	}


	/**
	 * Updates the plugin version number in the database
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function nunil_upgrade(): void {
		$old_ver = get_option( 'no-unsafe-inline_version', '0' );
		$new_ver = NO_UNSAFE_INLINE_VERSION;

		if ( $old_ver === $new_ver ) {
			return;
		}
		// controlla l'azione
		do_action( 'nunil_upgrade', $new_ver, $old_ver );
		update_option( 'no-unsafe-inline_version', $new_ver );
	}

	/**
	 * Creates the admin submenu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function nunil_admin_options_submenu(): void {
		$edit = add_submenu_page(
			'options-general.php', // parent slug.
			__( 'Management of no-unsafe-inline settings', 'no-unsafe-inline' ), // page title.
			__( 'CSP settings', 'no-unsafe-inline' ),
			'manage_options', // capability.
			'no-unsafe-inline', // menu_slug.
			function () {
				$this->nunil_manage_options();
			} // callable.
		);

		add_action( 'load-' . $edit, array( $this, 'nunil_set_screen' ), 10, 0 );
	}

	/**
	 * Set the screen object.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function nunil_set_screen(): void {
		set_current_screen( 'no-unsafe-inline' );
		$current_screen = get_current_screen();

		// Get the active tab from the $_GET param.
		$default_tab = null;
		$tab         = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab;

		$help_tabs = new \NUNIL\Nunil_Admin_Help_Tabs( $current_screen );

		switch ( $tab ) :
			case 'settings':
				$help_tabs->set_help_tabs( 'settings' );
				break;
			case 'base-src':
				$help_tabs->set_help_tabs( 'base-src' );
				break;
			case 'external':
				$help_tabs->set_help_tabs( 'external' );
				break;
			case 'inline':
				$help_tabs->set_help_tabs( 'inline' );
				break;
			case 'events':
				$help_tabs->set_help_tabs( 'events' );
				break;
			default:
				$help_tabs->set_help_tabs( 'nunil-tools' );
		endswitch;
	}

	/**
	 * Register the plugin options
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_options(): void {
		register_setting(
			'no-unsafe-inline_group',
			'no-unsafe-inline',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'no-unsafe-inline_fetch_directives_settings',
			esc_html__( 'Fetch directives managed', 'no-unsafe-inline' ),
			array( $this, 'print_fetch_directives_section' ),
			'no-unsafe-inline-options'
		);

		foreach ( $this->managed_src_directives as $src_directive ) {
			$args = array(
				'option_name' => $src_directive . '_enabled',
				// translators: %s is the CSP -src directive, as script-src.
				'label'       => sprintf( esc_html__( 'Enable managing of the %s directive.', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#directive-' . $src_directive . '" target="_blank">' . $src_directive . '</a>' ),
			);

			$id    = $src_directive . '_enabled';
			$title = $src_directive;

			add_settings_field(
				$id,
				$title,
				array( $this, 'print_directive_src_enabled' ), // $callback:  (callable) (Required) Function that fills the field with the desired form inputs. The function should echo its output.
				'no-unsafe-inline-options', // $page: (string) (Required) The slug-name of the settings page on which to show the section (general, reading, writing, ...).
				'no-unsafe-inline_fetch_directives_settings', // , // $section: (string) (Optional) The slug-name of the section of the settings page in which to show the box. Default value: 'default'
				$args // $args passed to the callback
			);
		}

		add_settings_section(
			'external_host_mode',
			esc_html__( 'External source identification', 'no-unsafe-inline' ),
			array( $this, 'print_external_host_mode_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'external_host_mode',
			esc_html__( 'External hosts identification', 'no-unsafe-inline' ),
			array( $this, 'print_external_host_mode_option' ),
			'no-unsafe-inline-options',
			'external_host_mode'
		);
		/*** SRI section. */
		add_settings_section(
			'no-unsafe-inline_use_sri',
			esc_html__( 'Use Subresource Integrity', 'no-unsafe-inline' ),
			array( $this, 'print_use_sri_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'sri_script',
			esc_html__( 'Add integrity attribute to <script>', 'no-unsafe-inline' ),
			array( $this, 'print_sri_script_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'sri_link',
			esc_html__( 'Add integrity attribute to <link>', 'no-unsafe-inline' ),
			array( $this, 'print_sri_link_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'use_strict-dynamic',
			esc_html__( 'Use sctrict-dynamic in <script>', 'no-unsafe-inline' ),
			array( $this, 'print_use_strict_dynamic_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'sri_sha256',
			esc_html__( 'Add sha256 in integrity attribute', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha256_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'sri_sha384',
			esc_html__( 'Add sha384 in integrity attribute', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha384_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'sri_sha512',
			esc_html__( 'Add sha512 in integrity attribute', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha512_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);
		/*** End of SRI section. */

		add_settings_section(
			'no-unsafe-inline_inline_script_mode',
			esc_html__( 'Inline script mode', 'no-unsafe-inline' ),
			array( $this, 'print_inline_script_mode_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'inline_scripts_mode',
			esc_html__( 'Inline script mode', 'no-unsafe-inline' ),
			array( $this, 'print_inline_script_mode_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_inline_script_mode'
		);

		add_settings_section(
			'no-unsafe-inline_misc',
			esc_html__( 'Misc options', 'no-unsafe-inline' ),
			array( $this, 'print_misc_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'no-unsafe-inline_upgrade_insecure',
			esc_html__( 'Set upgrade-insecure-requests directive', 'no-unsafe-inline' ),
			array( $this, 'print_upgrade_insecure' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'protect_admin',
			esc_html__( 'Enforce policy in admin', 'no-unsafe-inline' ),
			array( $this, 'print_protect_admin' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'use_unsafe-hashes',
			sprintf( esc_html__( 'Use \'%s\' for JS event handlers attributes of HTML elements. (Say NO)', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#unsafe-hashes-usage" target="_blank">unsafe-hashes</a>' ),
			array( $this, 'print_use_unsafe_hashes' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'fix_setattribute_style',
			sprintf(
				esc_html__( 'Fix the use of %1$s in 3th party libraries and override %2$s', 'no-unsafe-inline' ),
				'<a href="https://csplite.com/csp/test343/" target="_blank">setAttribute(\'style\')</a>',
				'<a href="https://csplite.com/csp/test433/" target="_blank">jQuery htmlPrefilter()(\'style\')</a>'
			),
			array( $this, 'print_fix_setattribute_style' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'logs_enabled',
			esc_html__( 'Enable Server Logs', 'no-unsafe-inline' ),
			array( $this, 'print_logs_enabled' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

	}

	/**
	 * Register the plugin tools status
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_tools_status(): void {
		register_setting(
			'no-unsafe-inline_tools_group',
			'no-unsafe-inline-tools',
			array( $this, 'sanitize_tools' )
		);

		add_settings_section(
			'no-unsafe-inline-tools-status',
			esc_html__( 'No unsafe-inline tools', 'no-unsafe-inline' ),
			array( $this, 'print_tools_section' ),
			'no-unsafe-inline-tools-page'
		);

		add_settings_field(
			'capture_enabled',
			esc_html__( 'Enable tag capture', 'no-unsafe-inline' ),
			array( $this, 'print_capture_enabled' ),
			'no-unsafe-inline-tools-page',
			'no-unsafe-inline-tools-status'
		);

		add_settings_field(
			'test_policy',
			esc_html__( 'Test current csp policy', 'no-unsafe-inline' ),
			array( $this, 'print_test_policy' ),
			'no-unsafe-inline-tools-page',
			'no-unsafe-inline-tools-status'
		);

		add_settings_field(
			'enable_protection',
			esc_html__( 'Enable csp protection', 'no-unsafe-inline' ),
			array( $this, 'print_enable_protection' ),
			'no-unsafe-inline-tools-page',
			'no-unsafe-inline-tools-status'
		);
	}


	/**
	 * Register the base -src rules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_base_src_sources(): void {

		$options = (array) get_option( 'no-unsafe-inline' );

		register_setting(
			'no-unsafe-inline_base_src_group',
			'no-unsafe-inline-base-src',
			array( $this, 'sanitize_base_src' )
		);

		add_settings_section(
			'no-unsafe-inline-base-src-section',
			esc_html__( 'Base CSP -src sources', 'no-unsafe-inline' ),
			array( $this, 'print_base_src_section' ),
			'no-unsafe-inline-base-src-page'
		);

		foreach ( $this->managed_src_directives as $directive ) {
			$setting_name = $directive . '_enabled';

			// Show only enabled -src directives.
			if ( 1 === $options[ $setting_name ] ) {
				$args = array(
					'option_name' => $directive . '_base_source',
					'label'       => sprintf(
						// translators: %s is the CSP directive, like script-src.
						esc_html__( 'Base sources for the %s directive.', 'no-unsafe-inline' ),
						$directive
					),
				);

				add_settings_field(
					$args['option_name'],
					// translators: %s is the CSP directive, like script-src.
					sprintf( esc_html__( 'Base %s sources', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#directive-' . $directive . '" target="_blank">' . $directive . '</a>' ),
					array( $this, 'print_base_srcs' ),
					'no-unsafe-inline-base-src-page',
					'no-unsafe-inline-base-src-section',
					$args
				);
			}
		}
	}

	/**
	 * Sanitize the settings
	 *
	 * @param array<int|string> $input Contains the settings.
	 * @return array<int|string>
	 */
	public function sanitize_options( $input ) {
		$new_input = array();

		$options = get_option( 'no-unsafe-inline' );
		if ( ! is_array( $options ) ) {
			throw( new Exception( 'Option no-unsafe-inline is not an array' ) );
		}

		// Checkboxes.
		foreach ( $this->managed_src_directives as $directive ) {
			$setting_name = $directive . '_enabled';

			if ( isset( $input[ $setting_name ] ) ) {
				$new_input[ $setting_name ] = 1;
			} else {
				$new_input[ $setting_name ] = 0;
			}
		}

		if ( isset( $input['sri_script'] ) ) {
			$new_input['sri_script'] = 1;
		} else {
			$new_input['sri_script'] = 0;
		}

		if ( isset( $input['sri_link'] ) ) {
			$new_input['sri_link'] = 1;
		} else {
			$new_input['sri_link'] = 0;
		}

		if ( isset( $input['use_strict-dynamic'] ) ) {
			$new_input['use_strict-dynamic'] = 1;
		} else {
			$new_input['use_strict-dynamic'] = 0;
		}

		if ( isset( $input['sri_sha256'] ) ) {
			$new_input['sri_sha256'] = 1;
		} else {
			$new_input['sri_sha256'] = 0;
		}
		if ( isset( $input['sri_sha384'] ) ) {
			$new_input['sri_sha384'] = 1;
		} else {
			$new_input['sri_sha384'] = 0;
		}
		if ( isset( $input['sri_sha512'] ) ) {
			$new_input['sri_sha512'] = 1;
		} else {
			$new_input['sri_sha512'] = 0;
		}
		// One hash has to be selected, if we are using SRI
		if ( ( isset( $input['sri_script'] ) || isset( $input['sri_link'] ) ) &&
			( ! isset( $input['sri_sha256'] ) && ! isset( $input['sri_sha384'] ) && ! isset( $input['sri_sha512'] ) )
		) {
			$new_input['sri_sha256'] = 1;
		}

		if ( isset( $input['no-unsafe-inline_upgrade_insecure'] ) ) {
			$new_input['no-unsafe-inline_upgrade_insecure'] = 1;
		} else {
			$new_input['no-unsafe-inline_upgrade_insecure'] = 0;
		}

		if ( isset( $input['protect_admin'] ) ) {
			$new_input['protect_admin'] = 1;
		} else {
			$new_input['protect_admin'] = 0;
		}

		if ( isset( $input['use_unsafe-hashes'] ) ) {
			$new_input['use_unsafe-hashes'] = 1;
		} else {
			$new_input['use_unsafe-hashes'] = 0;
		}

		if ( isset( $input['fix_setattribute_style'] ) ) {
			$new_input['fix_setattribute_style'] = 1;
		} else {
			$new_input['fix_setattribute_style'] = 0;
		}

		if ( isset( $input['logs_enabled'] ) ) {
			$new_input['logs_enabled'] = 1;
		} else {
			$new_input['logs_enabled'] = 0;
		}

		// Radio.
		$inline_script_modes = array( 'nonce', 'sha256', 'sha384', 'sha512' );
		if ( in_array( $input['inline_scripts_mode'], $inline_script_modes, true ) ) {
			$new_input['inline_scripts_mode'] = $input['inline_scripts_mode'];
		} else {
			$new_input['inline_scripts_mode'] = 'nonce';
		}
		$external_host_modes = array( 'host', 'sch-host', 'domain', 'resource' );
		if ( in_array( $input['external_host_mode'], $external_host_modes, true ) ) {
			$new_input['external_host_mode'] = $input['external_host_mode'];
		} else {
			$new_input['external_host_mode'] = 'sch-host';
		}

		$new_input = array_merge( $options, $new_input );
		return $new_input;
	}

	/**
	 * Sanitize the tools status
	 *
	 * @param array<int> $input Contains the settings.
	 * @return array<int>
	 */
	public function sanitize_tools( $input ) {
		$new_input = array();
		$options   = get_option( 'no-unsafe-inline-tools' );

		if ( isset( $input['capture_enabled'] ) ) {
			$new_input['capture_enabled'] = 1;
		} else {
			$new_input['capture_enabled'] = 0;
		}
		if ( isset( $input['test_policy'] ) ) {
			$new_input['test_policy'] = 1;
		} else {
			$new_input['test_policy'] = 0;
		}
		if ( isset( $input['enable_protection'] ) ) {
			$new_input['enable_protection'] = 1;
		} else {
			$new_input['enable_protection'] = 0;
		}

		if ( ! $options ) {
			return $new_input;
		} else {
			if ( ! is_array( $options ) ) {
				throw new Exception( 'Option no-unsafe-inline-tools is not an array' );
			}
			$new_input = array_merge( $options, $new_input );
			return $new_input;
		}
	}

	/**
	 * Sanitize the base CSP -src sources
	 *
	 * @param array<string> $input Contains the settings.
	 * @return array<string>
	 */
	public function sanitize_base_src( $input ) {
		$new_input = array();
		$options   = get_option( 'no-unsafe-inline-base-src' );
		foreach ( $this->managed_src_directives as $directive ) {
			$setting_name = $directive . '_base_source';
			if ( isset( $input[ $setting_name ] ) ) {
				$new_input[ $setting_name ] = sanitize_text_field( $input[ $setting_name ] );
			}
		}
		if ( ! $options ) {
			return $new_input;
		} else {
			if ( ! is_array( $options ) ) {
				throw new Exception( 'Option no-unsafe-inline-base-src is not an array' );
			}
			$new_input = array_merge( $options, $new_input );
			return $new_input;
		}
	}

	/**
	 * Print the fetch directives section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_fetch_directives_section(): void {
		print esc_html__( 'Select the CSP fetch directives that you want to manage with this plugin.', 'no-unsafe-inline' );
	}

	/**
	 * Print the tools section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_tools_section(): void {
		print esc_html__( 'Manage plugin tools.', 'no-unsafe-inline' );
	}

	/**
	 * Print the inline script mode section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_script_mode_section(): void {
		print esc_html__( 'Select how to identify whitelisted inline scripts.', 'no-unsafe-inline' );
	}

	/**
	 * Print the misc section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_misc_section(): void {
		print esc_html__( 'Misc options.', 'no-unsafe-inline' );
	}

	/**
	 * Print the external source mode section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_external_host_mode_section(): void {
		print esc_html__( 'Select how to identify external hosts.', 'no-unsafe-inline' );
	}

	/**
	 * Print the use Subresource Integrity session
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_sri_section(): void {
		printf(
			esc_html__( 'Options to use %s', 'no-unsafe-inline' ),
			'<a href="https://w3c.github.io/webappsec-subresource-integrity/" target="_blank">Subresource Integrity</a>'
		);
	}

	/**
	 * Print the base -src sources section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_base_src_section(): void {
		print esc_html__( 'Input here the base sources allowed for each CSP -src directive', 'no-unsafe-inline' );
		echo '<br />';
		print esc_html__( 'You can populate these fields by ticking the checkboxes in the table below.', 'no-unsafe-inline' );
	}

	/**
	 * Print the *-src directive option.
	 *
	 * @param array<string> $args Function arguments.
	 * @since 1.0.0
	 * @return void
	 */
	public function print_directive_src_enabled( $args ): void {
		$option_name = $args['option_name'];
		$label       = $args['label'];
		$options     = (array) get_option( 'no-unsafe-inline' );
		$value       = isset( $options[ $option_name ] ) ? esc_attr( $options[ $option_name ] ) : 0;
		$enabled     = $value ? 'checked="checked"' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[%1$s]"' .
			'name="no-unsafe-inline[%2$s]" %3$s />
			<label for="no-unsafe-inline[%4$s]">%5$s</label>',
			esc_html( $option_name ),
			esc_html( $option_name ),
			esc_html( $enabled ),
			esc_html( $option_name ),
			$label
		);
	}

	/**
	 * Print the *-src directive option.
	 *
	 * @since 1.0.0
	 * @param array<string> $args Function arguments.
	 * @return void
	 */
	public function print_base_srcs( $args ): void {
		$option_name = $args['option_name'];
		$label       = $args['label'];
		$options     = (array) get_option( 'no-unsafe-inline-base-src' );
		$value       = isset( $options[ $option_name ] ) ? esc_attr( $options[ $option_name ] ) : '';

		printf(
			'<div class="nunil-base-src-container">' .
			'<label for="no-unsafe-inline-base-src[%1$s]" class="nunil-base-src"/>' .
			'<input type="text" id="no-unsafe-inline-base-src[%1$s]" name="no-unsafe-inline-base-src[%1$s]" class="nunil-base-src" value="%3$s"/>%2$s</label>' .
			'</div>',
			esc_html( $option_name ),
			esc_html( $label ),
			esc_html( $value )
		);
	}

	/**
	 * Print the sri_script option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_script_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_script'] ) ? esc_attr( $options['sri_script'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_script]"' .
			'name="no-unsafe-inline[sri_script]" %s />
			<label for="no-unsafe-inline[sri_script]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Add integrity attribute to external resources loaded by <script> tag.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the sri_link option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_link_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_link'] ) ? esc_attr( $options['sri_link'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_link]"' .
			'name="no-unsafe-inline[sri_link]" %s />
			<label for="no-unsafe-inline[sri_link]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Add integrity attribute to external resources loaded by <link> tag.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the use_strict-dynamic option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_strict_dynamic_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['use_strict-dynamic'] ) ? esc_attr( $options['use_strict-dynamic'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[use_strict-dynamic]"' .
			'name="no-unsafe-inline[use_strict-dynamic]" %s />
			<label for="no-unsafe-inline[use_strict-dynamic]">%s</label>',
			esc_html( $enabled ),
			sprintf( esc_html__( 'Add %s in script-src.', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#strict-dynamic-usage" target="_blank">\'strict-dynamic\'</a>' ) . '<br>' . sprintf(
				esc_html__( 'This is only partially supported in Mozilla/Firefox. Read %1$s and %2$s', 'no-unsafe-inline' ),
				'<a href="https://bugzilla.mozilla.org/show_bug.cgi?id=1409200#c6" target="_blank">https://bugzilla.mozilla.org/show_bug.cgi?id=1409200#c6</a>',
				'<a href="https://webcompat.com/issues/85780" target="_blank">https://webcompat.com/issues/85780</a>'
			)
		);
	}

	/**
	 * Print the sri_sha256 option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_sha256_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_sha256'] ) ? esc_attr( $options['sri_sha256'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha256]"' .
			'name="no-unsafe-inline[sri_sha256]" %s />
			<label for="no-unsafe-inline[sri_sha256]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha256 hash in integrity attributes.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the sri_sha384 option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_sha384_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_sha384'] ) ? esc_attr( $options['sri_sha384'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha384]"' .
			'name="no-unsafe-inline[sri_sha384]" %s />
			<label for="no-unsafe-inline[sri_sha384]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha384 hash in integrity attributes.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the sri_sha512 option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_sha512_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_sha512'] ) ? esc_attr( $options['sri_sha512'] ) : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha512]"' .
			'name="no-unsafe-inline[sri_sha512]" %s />
			<label for="no-unsafe-inline[sri_sha512]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha512 hash in integrity attributes.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the inline script mode option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_script_mode_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['inline_scripts_mode'] ) ? esc_attr( $options['inline_scripts_mode'] ) : 'nonce';

		echo (
			'<div class="nunil-radio-div">' .
			'<label for="nonce" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="nonce" value="nonce" ' );
		if ( is_array( $options ) ) {
			echo( checked( 'nonce', $options['inline_scripts_mode'], false ) );
		}
		echo( '/>' .
			'<span>' . esc_html__( 'nonce', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .

			'<label for="sha256" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha256" value="sha256" ' );
		if ( is_array( $options ) ) {
			echo( checked( 'sha256', $options['inline_scripts_mode'], false ) );
		}
		echo( '/>' .
			'<span>' . esc_html__( 'sha256', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .

			'<label for="sha384" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha384" value="sha384" ' );
		if ( is_array( $options ) ) {
			echo( checked( 'sha384', $options['inline_scripts_mode'], false ) );
		}
		echo( '/>' .
			'<span>' . esc_html__( 'sha384', 'no-unsafe-inline' ) . '</span>' .
			'</label>' .

			'<label for="sha512" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha512" value="sha512" ' );
		if ( is_array( $options ) ) {
			echo( checked( 'sha512', $options['inline_scripts_mode'], false ) );
		}
		echo( '/>' .
			'<span>' . esc_html__( 'sha512', 'no-unsafe-inline' ) . '</span>' .
			'</label>' .
			'</div>'
		);
	}

	/**
	 * Print the external host mode option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_external_host_mode_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['external_host_mode'] ) ? esc_attr( $options['external_host_mode'] ) : 'host';

		echo (
			'<div class="nunil-radio-div">' .
			'<label for="resource" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="resource" value="resource" ' );
		echo( checked( 'resource', $options['external_host_mode'], false ) );
		echo( '/>' .
			'<span>' . esc_html__( 'resource (eg. https://www.example.org/script.js)', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .
			'<label for="sch-host" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="sch-host" value="sch-host" ' );

		echo( checked( 'sch-host', $options['external_host_mode'], false ) );
		echo( '/>' .
			'<span>' . esc_html__( 'scheme-host (eg. https://www.example.org)', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .
			'<label for="host" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="host" value="host" ' );
		echo( checked( 'host', $options['external_host_mode'], false ) );
		echo( '/>' .
			'<span>' . esc_html__( 'host (eg. www.example.org)', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .

			'<label for="domain" class="nunil-l-radio">' .
			'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="domain" value="domain" ' );
		echo( checked( 'domain', $options['external_host_mode'], false ) );
		echo( '/>' .
			'<span>' . esc_html__( 'domain (eg *.example.org)', 'no-unsafe-inine' ) . '</span>' .
			'</label>' .
			'</div>'
		);

	}



	/**
	 * Print the upgrade insecure requests option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_upgrade_insecure(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['no-unsafe-inline_upgrade_insecure'] ) ? esc_attr( $options['no-unsafe-inline_upgrade_insecure'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]"' .
			'name="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]" %s />
			<label for="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]">%s</label>',
			esc_html( $enabled ),
			sprintf( esc_html__( 'Set the CSP directive: %s', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/upgrade-insecure-requests/" target="_blank"><b>upgrade-insecure-requests</b></a>' )
		);
	}

	/**
	 * Print the protect admin option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_protect_admin(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['protect_admin'] ) ? esc_attr( $options['protect_admin'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[protect_admin]"' .
			'name="no-unsafe-inline[protect_admin]" %s />
			<label for="no-unsafe-inline[protect_admin]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Enforce CSP policy when true === is_admin().', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the unsafe-hashes option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_unsafe_hashes(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['use_unsafe-hashes'] ) ? esc_attr( $options['use_unsafe-hashes'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[use_unsafe-hashes]"' .
			'name="no-unsafe-inline[use_unsafe-hashes]" %s />
			<label for="no-unsafe-inline[use_unsafe-hashes]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'The unsafe-hashes Content Security Policy (CSP) keyword allows the execution of inline scripts within a JavaScript event handler attribute of a HTML element. This is not safe and this plugin can handle event handlers HTML attributes without \'unsafe-hashes\'.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the fis setAttribute('style') option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_fix_setattribute_style(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['fix_setattribute_style'] ) ? esc_attr( $options['fix_setattribute_style'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[fix_setattribute_style]"' .
			'name="no-unsafe-inline[fix_setattribute_style]" %s />
			<label for="no-unsafe-inline[fix_setattribute_style]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Global replacing Element.setAttribute() with Element.style.property = val and override jQuery htmlPrefilter() for CSP-safe applying of inline styles.', 'no-unsafe-inline' )
		);
	}



	/**
	 * Print the logs_enabled option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_logs_enabled(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['logs_enabled'] ) ? esc_attr( $options['logs_enabled'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[logs_enabled]"' .
			'name="no-unsafe-inline[logs_enabled]" %s />
			<label for="no-unsafe-inline[logs_enabled]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Enable logs in database.', 'no-unsafe-inline' )
		);
	}


	/**
	 * Print the capture toggle option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_capture_enabled(): void {
		$options = (array) get_option( 'no-unsafe-inline-tools' );
		$value   = isset( $options['capture_enabled'] ) ? esc_attr( $options['capture_enabled'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[capture_enabled]"' .
			'name="no-unsafe-inline-tools[capture_enabled]" %s />
			<label for="no-unsafe-inline-tools[capture_enabled]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Enable tag capturing on this site.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the test policy option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_test_policy(): void {
		$options = (array) get_option( 'no-unsafe-inline-tools' );
		$value   = isset( $options['test_policy'] ) ? esc_attr( $options['test_policy'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[test_policy]"' .
			'name="no-unsafe-inline-tools[test_policy]" %s />
			<label for="no-unsafe-inline-tools[test_policy]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Test policy with Content-Security-Policy-Report-Only.', 'no-unsafe-inline' )
		);
	}


	/**
	 * Print the test policy option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_enable_protection(): void {
		$options = (array) get_option( 'no-unsafe-inline-tools' );
		$value   = isset( $options['enable_protection'] ) ? esc_attr( $options['enable_protection'] ) : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[enable_protection]"' .
			'name="no-unsafe-inline-tools[enable_protection]" %s />
			<label for="no-unsafe-inline-tools[enable_protection]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Enable CSP protection.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the main tab admin options group
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function nunil_manage_options(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the active tab from the $_GET param.
		$default_tab = null;
		$tab         = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab;

		?>
		<div class="wrap">
			<h1><?php printf( esc_html__( 'No unsafe-inline Settings', 'no-unsafe-inline' ) ); ?></h1>
			<div class="notice notice-notice">
				<p>
					<?php
					printf(
					// translators: %1$s is the opening a tag %2$s is the closing a tag.
						esc_html__( 'All the settings are described in the %1$s documentation%2$s.', 'no-unsafe-inline' ),
						'<a target="_blank" href="https://CHANGEME">',
						'</a>'
					);
					?>
				</p>
			</div>
			<div class="wrap">
				<!-- Print the page title -->
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<!-- Here are our tabs -->
				<nav class="nav-tab-wrapper">
					<a href="?page=no-unsafe-inline" class="nav-tab 
					<?php
					if ( null === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Tools', 'no-unsafe-inline' ) ); ?></a>
					<a href="?page=no-unsafe-inline&tab=base-src" class="nav-tab 
					<?php
					if ( 'base-src' === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Base -src', 'no-unsafe-inline' ) ); ?></a>
					<a href="?page=no-unsafe-inline&tab=external" class="nav-tab 
					<?php
					if ( 'external' === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'External whitelist', 'no-unsafe-inline' ) ); ?></a>
					<a href="?page=no-unsafe-inline&tab=inline" class="nav-tab 
					<?php
					if ( 'inline' === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Inline whitelist', 'no-unsafe-inline' ) ); ?></a>

					<a href="?page=no-unsafe-inline&tab=events" class="nav-tab 
					<?php
					if ( 'events' === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Events whitelist', 'no-unsafe-inline' ) ); ?></a>

					<a href="?page=no-unsafe-inline&tab=settings" class="nav-tab 
					<?php
					if ( 'settings' === $tab ) :
						?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Settings', 'no-unsafe-inline' ) ); ?></a>
				</nav>

				<div class="tab-content">
				<?php
				switch ( $tab ) :
					case 'settings':
						self::print_options_page();
						break;
					case 'base-src':
						self::print_base_src_page();
						break;
					case 'external':
						self::print_external_page();
						break;
					case 'inline':
						self::print_inline_page();
						break;
					case 'events':
						self::print_events_page();
						break;
					default:
						self::print_tools_page();
						break;
				endswitch;
				?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the options page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_options_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-admin-options.php';
	}

	/**
	 * Renders the tools page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_tools_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-tools.php';
	}

	/**
	 * Renders the base-src page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_base_src_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-base-src-list.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-base-src.php';
	}

	/**
	 * Renders the external page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_external_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-external-list.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-external.php';
	}

	/**
	 * Renders the inline scripts page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_page(): void {
		$current_screen = get_current_screen();
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'nunil_inline_per_page',
			)
		);

		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-inline-list.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-inline.php';
	}

	/**
	 * Renders the events scripts page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_events_page(): void {
		$current_screen = get_current_screen();
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'nunil_events_per_page',
			)
		);

		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-events-list.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-events.php';
	}

	/**
	 * Show the admin notices
	 *
	 * Gets the transient 'no_unsafe_inline_admin_notice' which is an
	 * array with the type as key and a message as value
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show_admin_notice(): void {
		$notice = get_transient( 'no_unsafe_inline_admin_notice' );

		if ( $notice && is_array( $notice ) ) {
			$type    = $notice['type'];
			$message = $notice['message'];

			if ( $type === 'warning' ) {
				printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message );
			} elseif ( $type === 'error' ) {
				printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', $message );
			} else {
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', $message );
			}

			delete_transient( 'no_unsafe_inline_admin_notice' );
		}
	}


	/**
	 * Trigger Clustering of inline scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function trigger_clustering(): void {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'nunil_trigger_clustering_nonce' ) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}

		$obj = new NUNIL\Nunil_Clustering();

		$result = $obj->cluster_by_dbscan();

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {

			echo json_encode( $result );
		} else {
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		}

		wp_die();
	}

	/**
	 * Removes all captured data from database
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clean_database(): void {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'nunil_trigger_clean_database' ) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}

		$tables = array(
			'event_handlers',
			'external_scripts',
			'inline_scripts',
			'occurences',
		);
		
		$result_string = '<br><b> --- ' . esc_html( 'DELETE ALL SCRIPTS FROM DATABASE', 'no-unsafe-inline' ) . ' --- </b><br>';
		$result_string = '<ul>';

		foreach ( $tables as $table ) {
			
			$delete = DB::truncate_table( $table );

			$delete_string = $delete ? esc_html__( 'succeded', 'no-unsafe-inline' ) : esc_html__( 'FAILED', 'no-unsafe-inline' );

			$result_string = $result_string . ( "<li>TRUNCATE $tablename: $delete_string</li>" );

		}
		$result_string = $result_string . '</ul>';

		\NUNIL\Nunil_Lib_Utils::show_message( '<strong>No unsafe-inline</strong> ' . esc_html__( 'cleaned up the database at the user\'s request', 'no-unsafe-inline' ), 'success' );
		\NUNIL\Nunil_Lib_Log::info( 'cleaned up the database at the user\'s request' );

		$result = array(
			'type'   => 'success',
			'report' => $result_string,
		);
		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
			echo json_encode( $result );
		} else {
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		}

		wp_die();
	}

	/**
	 * Updates the summary table of inline scripts
	 * Called by ajax
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $table Internal table name.
	 * @return void
	 */
	public function update_summary_tables(): void {
		
		$result = array();
		$result['global']   = DB::get_database_summary_data( 'global' );
		$result['external'] = DB::get_database_summary_data( 'external_scripts' );
		$result['inline']   = DB::get_database_summary_data( 'inline_scripts' );
		$result['events']   = DB::get_database_summary_data( 'event_handlers' );
		
		//~ $result = DB::get_database_summary_data( 'inline_scripts' );
		
		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
			echo json_encode( $result );
		} else {
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		}

		wp_die();
	}

	/**
	 * Output the summary table of all scripts in db
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The html of the table
	 */
	public function output_summary_tables() {
		$result = DB::get_database_summary_data( 'global' );
		
		$htb    = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Type', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num. Clusters', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_db_summary_body">';

		foreach ( $result as $print ) {
			$htb = $htb . '<tr>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Type', 'no-unsafe-inline' ) . '">' . $print->Type . '</td>';
			if ( '0' === $print->whitelist ) {
				$wl_text = __( 'BL', 'no-unsafe-inline' );
			} else {
				$wl_text = __( 'WL', 'no-unsafe-inline' );
			}
			$htb = $htb . '<td data-th="' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->Num . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Num. Clusters', 'no-unsafe-inline' ) . '">' . $print->Clusters . '</td>';
			$htb = $htb . '</tr>';
		}
		$htb = $htb . '</tbody></table>';

		return $htb;
	}

/**
	 * Output the summary table of external scripts
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The html of the table
	 */
	public function output_summary_external_table() {
		$result = DB::get_database_summary_data( 'external_scripts' );
		
		$htb    = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_external_table_summary_body">';

		foreach ( $result as $print ) {
			$htb = $htb . '<tr>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '">' . $print->directive . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
			if ( '0' === $print->whitelist ) {
				$wl_text = __( 'BL', 'no-unsafe-inline' );
			} else {
				$wl_text = __( 'WL', 'no-unsafe-inline' );
			}
			$htb = $htb . '<td data-th="' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
			$htb = $htb . '</tr>';
		}
		$htb = $htb . '</tbody></table>';

		return $htb;
	}

	/**
	 * Output the summary table of inline scripts
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The html of the table
	 */
	public function output_summary_inline_table() {
		$result = DB::get_database_summary_data( 'inline_scripts' );
		
		$htb    = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_inline_table_summary_body">';

		foreach ( $result as $print ) {
			$htb = $htb . '<tr>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '">' . $print->directive . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '">' . $print->clustername . '</td>';
			if ( '0' === $print->whitelist ) {
				$wl_text = __( 'BL', 'no-unsafe-inline' );
			} else {
				$wl_text = __( 'WL', 'no-unsafe-inline' );
			}
			$htb = $htb . '<td data-th="' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
			$htb = $htb . '</tr>';
		}
		$htb = $htb . '</tbody></table>';

		return $htb;
	}

	/**
	 * Output the summary table of events scripts
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The html of the table
	 */
	public function output_summary_eventhandlers_table() {
		$result = DB::get_database_summary_data( 'event_handlers' );
		
		$htb    = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Event Attribute', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_eventhandlers_table_summary_body">';

		foreach ( $result as $print ) {
			$htb = $htb . '<tr>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Event Attribute', 'no-unsafe-inline' ) . '">' . $print->event_attribute . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '">' . $print->clustername . '</td>';
			if ( '0' === $print->whitelist ) {
				$wl_text = __( 'BL', 'no-unsafe-inline' );
			} else {
				$wl_text = __( 'WL', 'no-unsafe-inline' );
			}
			$htb = $htb . '<td data-th="' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
			$htb = $htb . '<td data-th="' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
			$htb = $htb . '</tr>';
		}
		$htb = $htb . '</tbody></table>';

		return $htb;
	}

	/**
	 * Performs a simple test on classifier.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function test_classifier(): void {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'nunil_test_classifier_nonce' ) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}
		$test = new NUNIL\Nunil_Classification();

		$result_string = $test->test_cases();
		$result        = array(
			'type'   => 'success',
			'report' => $result_string,
		);
		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
			echo json_encode( $result );
		} else {
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		}

		wp_die();

	}
}
