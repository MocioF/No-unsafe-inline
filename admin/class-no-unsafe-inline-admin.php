<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Utils as Utils;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Exception;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
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
	 * @var      array<string>    $managed_directives   The CSP directives managed by the plugin.
	 */
	private $managed_directives;

	/**
	 * WP_List_Table object.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      \WP_List_Table    $show_table
	 */
	public $show_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string        $plugin_name               The name of this plugin.
	 * @param    string        $version                   The version of this plugin.
	 * @param    array<string> $managed_directives    The CSP -src directives managed by this plugin.
	 */
	public function __construct( $plugin_name, $version, $managed_directives ) {
		$this->plugin_name        = $plugin_name;
		$this->version            = $version;
		$this->managed_directives = $managed_directives;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_styles(): void {
		$suffix = wp_scripts_get_suffix();
		$screen = get_current_screen();
		if ( ! is_null( $screen ) && 'settings_page_no-unsafe-inline' === $screen->id ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . "css/no-unsafe-inline-admin$suffix.css",
				array(),
				$this->version,
				'all'
			);

			$wp_scripts = wp_scripts();
			wp_enqueue_style(
				'jquery-ui-theme-smoothness',
				plugin_dir_url( __FILE__ ) .
				sprintf(
					"css/jqueryui/%s/themes/smoothness/jquery-ui$suffix.css",
					$wp_scripts->registered['jquery-ui-core']->ver
				),
				array(),
				$this->version,
				'all'
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
		$plugin = new No_Unsafe_Inline();
		$plugin->enqueue_common_scripts();
		$suffix = wp_scripts_get_suffix();

		$screen = get_current_screen();
		if ( ! is_null( $screen ) && 'settings_page_no-unsafe-inline' === $screen->id ) {
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . "js/no-unsafe-inline-admin$suffix.js",
				array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-accordion', 'jquery-ui-tabs', 'jquery-ui-spinner', 'jquery-ui-tooltip', 'wp-i18n' ),
				$this->version,
				false
			);

			wp_localize_script(
				$this->plugin_name,
				'nunil_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
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
			$meta[]      = "<style>
			.nunil-stars{display:inline-block;color:#ffb900;position:relative;top:3px}
			.nunil-stars svg{fill:#ffb900}
			.nunil-stars svg:hover{fill:#ffb900}
			.nunil-stars svg:hover ~ svg{fill:none}
			</style>
			<a href='https://wordpress.org/support/plugin/no-unsafe-inline/reviews#new-post' target='_blank' title='" . __( 'Leave a review', 'no-unsafe-inline' ) . "'><i class='nunil-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
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
			'<a href="https://wordpress.org/plugins/no-unsafe-inline/" target="_blank">' . esc_html__( 'Documentation', 'no-unsafe-inline' ) . '</a>',
		);
		$actions = array_merge( $actions, $links );
		return $actions;
	}


	/**
	 * Updates the plugin version number in the database
	 *
	 * This method is hooked on admin_init.
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
		// Calls the callback functions that have been added to the nunil_upgrade action hook.
		do_action( 'nunil_upgrade', $new_ver, $old_ver );
		update_option( 'no-unsafe-inline_version', $new_ver );
	}

	/**
	 * Updates the mu-plugin to the last one
	 *
	 * This method is hooked on nunil_upgrade.
	 *
	 * @since 1.0.1
	 * @param string $new_ver New plugin version.
	 * @param string $old_ver Old plugin version.
	 * @return void
	 */
	public function update_muplugin( $new_ver, $old_ver ): void {
		if ( $old_ver === $new_ver ) {
			return;
		}
		if ( NUNIL\Nunil_Manage_Muplugin::is_nunil_muplugin_installed() ) {
			NUNIL\Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
			NUNIL\Nunil_Manage_Muplugin::toggle_nunil_muplugin_installation();
		}
	}

	/**
	 * Adds new options to the database
	 *
	 * This method is hooked on nunil_upgrade.
	 *
	 * @since 1.0.2
	 * @param string $new_ver New plugin version.
	 * @param string $old_ver Old plugin version.
	 * @return void
	 */
	public function add_new_options( $new_ver, $old_ver ): void {
		if ( version_compare( $old_ver, '1.0.1', '<' ) ) {
			$options = (array) get_option( 'no-unsafe-inline' );
			if ( ! array_key_exists( 'max_response_header_size', $options ) ) {
				$options['max_response_header_size'] = 8192;
				update_option( 'no-unsafe-inline', $options );
			}
		}
		if ( version_compare( $old_ver, '1.1.0', '<' ) ) {
			$options = (array) get_option( 'no-unsafe-inline' );
			if ( ! array_key_exists( 'script-src_mode', $options ) ) {
				if ( ( array_key_exists( 'hash_in_script-src', $options ) && 1 === $options['hash_in_script-src'] ) ||
				( array_key_exists( 'hash_in_all', $options ) && 1 === $options['hash_in_all'] ) ) {
					$options['script-src_mode'] = 'hash';
				} else {
					$options['script-src_mode'] = 'nonce';
				}
				if ( array_key_exists( 'hash_in_script-src', $options ) ) {
					unset( $options['hash_in_script-src'] );
				}
			}
			if ( ! array_key_exists( 'style-src_mode', $options ) ) {
				if ( ( array_key_exists( 'hash_in_style-src', $options ) && 1 === $options['hash_in_style-src'] ) ||
				( array_key_exists( 'hash_in_all', $options ) && 1 === $options['hash_in_all'] ) ) {
					$options['style-src_mode'] = 'hash';
				} else {
					$options['style-src_mode'] = 'nonce';
				}
				if ( array_key_exists( 'hash_in_style-src', $options ) ) {
					unset( $options['hash_in_style-src'] );
				}
			}
			if ( ! array_key_exists( 'img-src_mode', $options ) ) {
				if ( ( array_key_exists( 'hash_in_img-src', $options ) && 1 === $options['hash_in_img-src'] ) ||
				( array_key_exists( 'hash_in_all', $options ) && 1 === $options['hash_in_all'] ) ) {
					$options['img-src_mode'] = 'hash';
				} else {
					$options['img-src_mode'] = 'none';
				}
				if ( array_key_exists( 'hash_in_img-src', $options ) ) {
					unset( $options['hash_in_img-src'] );
				}
			}
			unset( $options['hash_in_all'] );

			update_option( 'no-unsafe-inline', $options );
		}

		if ( version_compare( $old_ver, '1.1.2a', '<' ) ) {
			$options = (array) get_option( 'no-unsafe-inline' );
			if ( 'hash' === $options['style-src_mode'] ) {
				$options['style-src_mode'] = 'none';
			}
			/**
			 * In CSP3 hashes are only allowed for inline script, inline styles and external script
			 * but support for external styles or imgs in the specification has not been announced
			 * https://www.w3.org/TR/CSP3/#external-hash
			 */
			unset( $options['img-src_mode'] );

			update_option( 'no-unsafe-inline', $options );
		}
	}

	/**
	 * Cleanup occurences for unhashed external assets.
	 *
	 * This method is hooked on nunil_upgrade.
	 *
	 * @since 1.1.3
	 * @param string $new_ver New plugin version.
	 * @param string $old_ver Old plugin version.
	 * @return void
	 */
	public function delete_old_occurences( $new_ver, $old_ver ): void {
		if ( version_compare( $old_ver, '1.1.3', '<' ) ) {
			$externals = DB::get_external_rows();
			if ( ! is_null( $externals ) && count( $externals ) > 1 ) {
				foreach ( $externals as $ext_asset ) {
					if ( false === Utils::is_resource_hash_needed( $ext_asset->directive, $ext_asset->tagname ) ) {
						DB::ext_occurences_delete( $ext_asset->ID );
					}
				}
			}
		}
	}

	/**
	 * Alters the database structure.
	 *
	 * This method is hooked on nunil_upgrade.
	 *
	 * @since 1.1.4
	 * @param string $new_ver New plugin version.
	 * @param string $old_ver Old plugin version.
	 * @return void
	 */
	public function alter_database( $new_ver, $old_ver ): void {
		if ( version_compare( $old_ver, '1.1.4', '<' ) ) {
			DB::extend_ext_src_attrib_size();
		}
	}

	/**
	 * Update version and rehash plugin's assets after version update
	 *
	 * @since 1.0.2
	 * @param string $new_ver New plugin version.
	 * @param string $old_ver Old plugin version.
	 * @return void
	 */
	public function renew_plugin_assets( $new_ver, $old_ver ): void {
		$ids = DB::get_last_nunil_ids( $old_ver );
		if ( $ids ) {
			foreach ( $ids as $id ) {
				DB::update_nunil_version( $id->ID, $new_ver, $old_ver );
				try {
					$sri = new \NUNIL\Nunil_SRI();
					$sri->put_hashes_in_db( $id->ID, $overwrite = true );
				} catch ( Nunil_Exception $ex ) {
					$ex->logexception();
				}
			}
			DB::delete_legacy_nunil_assets( $new_ver );
		}
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
		$current_screen = get_current_screen();
		if ( ! is_null( $current_screen ) ) {
			// Get the active tab from the $_GET param.
			$default_tab = null;
			$tab         = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab;

			$help_tabs = new \NUNIL\Nunil_Admin_Help_Tabs( $current_screen );

			switch ( $tab ) :
				case 'settings':
					$help_tabs->set_help_tabs( 'settings' );
					break;
				case 'base-rule':
					$help_tabs->set_help_tabs( 'base-rule' );
					break;
				case 'external':
					$help_tabs->set_help_tabs( 'external' );
					$args = array(
						'label'   => __( 'External scripts per page', 'no-unsafe-inline' ),
						'default' => 20,
						'option'  => 'nunil_external_per_page',
					);
					add_screen_option( 'per_page', $args );
					require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-external-list.php';
					$this->show_table = new No_Unsafe_Inline_External_List();
					break;
				case 'inline':
					$help_tabs->set_help_tabs( 'inline' );
					$args = array(
						'label'   => __( 'Inline scripts per page', 'no-unsafe-inline' ),
						'default' => 20,
						'option'  => 'nunil_inline_per_page',
					);
					add_screen_option( 'per_page', $args );
					require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-inline-list.php';
					$this->show_table = new No_Unsafe_Inline_Inline_List();
					break;
				case 'events':
					$help_tabs->set_help_tabs( 'events' );
					$args = array(
						'label'   => __( 'Events per page', 'no-unsafe-inline' ),
						'default' => 20,
						'option'  => 'nunil_events_per_page',
					);
					add_screen_option( 'per_page', $args );
					require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-events-list.php';
					$this->show_table = new No_Unsafe_Inline_Events_List();
					break;
				default:
					$help_tabs->set_help_tabs( 'nunil-tools' );
				endswitch;
		}
	}

	/**
	 * Register the plugin options
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_options(): void {
		try {
			register_setting(
				'no-unsafe-inline_group',
				'no-unsafe-inline',
				array( $this, 'sanitize_options' )
			);
		} catch ( Nunil_Exception $ex ) {
			$ex->logexception();
		}

		add_settings_section(
			'no-unsafe-inline_fetch_directives_settings',
			esc_html__( 'Directives managed', 'no-unsafe-inline' ),
			array( $this, 'print_directives_section' ),
			'no-unsafe-inline-options'
		);

		foreach ( $this->managed_directives as $src_directive ) {
			$args = array(
				'option_name' => $src_directive . '_enabled',
				// translators: %s is the CSP -src directive, as script-src.
				'label'       => $src_directive,
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
			esc_html__( 'External sources, base identification', 'no-unsafe-inline' ),
			array( $this, 'print_external_host_mode_option' ),
			'no-unsafe-inline-options',
			'external_host_mode'
		);

		/*** External source mode. */
		add_settings_section(
			'no-unsafe-inline_ext_mode',
			esc_html__( 'External source csp mode:', 'no-unsafe-inline' ),
			array( $this, 'print_ext_mode' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'script-src_mode',
			'script-src',
			array( $this, 'print_mode_for_script_src' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_ext_mode'
		);

		add_settings_field(
			'style-src_mode',
			'style-src',
			array( $this, 'print_mode_for_style_src' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_ext_mode'
		);

		/**
		 * In CSP3 hashes are only allowed for inline script, inline styles and external script
		 * but support for external styles or imgs in the specification has not been announced
		 * https://www.w3.org/TR/CSP3/#external-hash
		 * add_settings_field(
		 *  'img-src_mode',
		 *  'img-src',
		 *  array( $this, 'print_mode_for_img_src' ),
		 *  'no-unsafe-inline-options',
		 *  'no-unsafe-inline_ext_mode'
		 * );
		 */

		/*** Used hashes */
		add_settings_section(
			'no-unsafe-inline_algo_in_use',
			esc_html__( 'Select which hashes to use', 'no-unsafe-inline' ),
			array( $this, 'print_algo_in_use' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'sri_sha256',
			esc_html__( 'sha256', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha256_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_algo_in_use'
		);

		add_settings_field(
			'sri_sha384',
			esc_html__( 'sha384', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha384_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_algo_in_use'
		);

		add_settings_field(
			'sri_sha512',
			esc_html__( 'sha512', 'no-unsafe-inline' ),
			array( $this, 'print_sri_sha512_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_algo_in_use'
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
			esc_html__( 'SRI for <script>', 'no-unsafe-inline' ),
			array( $this, 'print_sri_script_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		add_settings_field(
			'sri_link',
			esc_html__( 'SRI for <link>', 'no-unsafe-inline' ),
			array( $this, 'print_sri_link_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_use_sri'
		);

		/*** Inline script mode section. */
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

		/*** Start misc section */
		add_settings_section(
			'no-unsafe-inline_misc',
			esc_html__( 'Misc options', 'no-unsafe-inline' ),
			array( $this, 'print_misc_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'use_strict-dynamic',
			esc_html__( 'Use strict-dynamic for <script>', 'no-unsafe-inline' ),
			array( $this, 'print_use_strict_dynamic_option' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
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
			// translators: %s is unsafe-hashes link to w3.org site.
			sprintf( esc_html__( 'Use \'%s\' for JS event handlers attributes of HTML elements. (Say NO)', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#unsafe-hashes-usage" target="_blank">unsafe-hashes</a>' ),
			array( $this, 'print_use_unsafe_hashes' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'fix_setattribute_style',
			sprintf(
			// translators: %1$s is setAttribute link, %2$s is jQuery htmlPrefilte link.
				esc_html__( 'Fix the use of %1$s in 3th party libraries and override %2$s', 'no-unsafe-inline' ),
				'<a href="https://csplite.com/csp/test343/" target="_blank">setAttribute(\'style\')</a>',
				'<a href="https://csplite.com/csp/test433/" target="_blank">jQuery htmlPrefilter()(\'style\')</a>'
			),
			array( $this, 'print_fix_setattribute_style' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'add_wl_by_cluster_to_db',
			esc_html__( 'Add to the database the scripts authorized by classification in a whitelisted cluster.', 'no-unsafe-inline' ),
			array( $this, 'print_add_wl_by_cluster_to_db' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		add_settings_field(
			'max_response_header_size',
			esc_html__( 'Define the max response HTTP header size, based on your server / hosting provider.', 'no-unsafe-inline' ),
			array( $this, 'print_max_response_header_size' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_misc'
		);

		/*** Start report section */
		add_settings_section(
			'no-unsafe-inline_report',
			esc_html__( 'Violations report options', 'no-unsafe-inline' ),
			array( $this, 'print_report_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'use_reports',
			esc_html__( 'Report CSP violations to endpoints', 'no-unsafe-inline' ),
			array( $this, 'print_use_reports' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_report'
		);

		add_settings_field(
			'group_name',
			esc_html__( 'Group name', 'no-unsafe-inline' ),
			array( $this, 'print_group_name' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_report'
		);

		add_settings_field(
			'max_age',
			esc_html__( 'Max age', 'no-unsafe-inline' ),
			array( $this, 'print_max_age' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_report'
		);

		add_settings_field(
			'endpoints',
			esc_html__( 'Endpoints', 'no-unsafe-inline' ),
			array( $this, 'print_endpoints' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_report'
		);

		/*** Logging section */
		add_settings_section(
			'no-unsafe-inline_logs',
			esc_html__( 'Logs', 'no-unsafe-inline' ),
			array( $this, 'print_logs_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'log_driver',
			esc_html__( 'Log driver', 'no-unsafe-inline' ),
			array( $this, 'print_log_driver' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_logs'
		);

		add_settings_field(
			'log_level',
			esc_html__( 'Log level', 'no-unsafe-inline' ),
			array( $this, 'print_log_level' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_logs'
		);

		/*** Start deactivate section */
		add_settings_section(
			'no-unsafe-inline_deactivate',
			esc_html__( 'Deactivation options', 'no-unsafe-inline' ),
			array( $this, 'print_deactivate_section' ),
			'no-unsafe-inline-options'
		);

		add_settings_field(
			'remove_tables',
			esc_html__( 'Remove tables', 'no-unsafe-inline' ),
			array( $this, 'print_remove_tables' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_deactivate'
		);

		add_settings_field(
			'remove_options',
			esc_html__( 'Remove options', 'no-unsafe-inline' ),
			array( $this, 'print_remove_options' ),
			'no-unsafe-inline-options',
			'no-unsafe-inline_deactivate'
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
	 * Register the base rules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_base_rule(): void {
		$options = (array) get_option( 'no-unsafe-inline' );

		register_setting(
			'no-unsafe-inline_base_rule_group',
			'no-unsafe-inline-base-rule',
			array( $this, 'sanitize_base_rule' )
		);

		add_settings_section(
			'no-unsafe-inline-base-rule-section',
			esc_html__( 'Base CSP rules', 'no-unsafe-inline' ),
			array( $this, 'print_base_rule_section' ),
			'no-unsafe-inline-base-rule-page'
		);

		foreach ( $this->managed_directives as $directive ) {
			$setting_name = $directive . '_enabled';

			// Show only enabled rules directives.
			if ( 1 === $options[ $setting_name ] ) {
				$args = array(
					'option_name' => $directive . '_base_rule',
					'label'       => sprintf(
						// translators: %s is the CSP directive, like script-src.
						esc_html__( 'Base rules for the %s directive.', 'no-unsafe-inline' ),
						$directive
					),
				);

				add_settings_field(
					$args['option_name'],
					// translators: %s is the CSP directive, like script-src.
					sprintf( esc_html__( 'Base %s sources', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#directive-' . $directive . '" target="_blank">' . $directive . '</a>' ),
					array( $this, 'print_base_rule' ),
					'no-unsafe-inline-base-rule-page',
					'no-unsafe-inline-base-rule-section',
					$args
				);
			}
		}
	}

	/**
	 * Sanitize the settings
	 *
	 * @throws \NUNIL\Nunil_Exception Main option is not an array.
	 * @param array<string|array<string>> $input Contains the settings.
	 * @return array<mixed>
	 */
	public function sanitize_options( $input ) {
		// This field is used just to populate array of endopoints in UI.
		unset( $input['new_endpoint'] );

		$new_input = array();

		$options = (array) get_option( 'no-unsafe-inline' );
		if ( empty( $options ) ) {
			throw new Nunil_Exception( esc_html__( 'The wp-option no-unsafe-inline is not an array', 'no-unsafe-inline' ) );
		}

		// Checkboxes.
		$admitted_values   = array( 0, 1, 'on' );
		$check_box_options = array();
		foreach ( $this->managed_directives as $directive ) {
			array_push( $check_box_options, $directive . '_enabled' );
		}
		array_push(
			$check_box_options,
			'sri_sha256',
			'sri_sha384',
			'sri_sha512',
			'sri_script',
			'sri_link',
			'use_strict-dynamic',
			'no-unsafe-inline_upgrade_insecure',
			'protect_admin',
			'use_unsafe-hashes',
			'fix_setattribute_style',
			'add_wl_by_cluster_to_db',
			'use_reports',
			'remove_tables',
			'remove_options'
		);

		foreach ( $check_box_options as $option_name ) {
			if ( ! isset( $input[ $option_name ] ) ) {
				$new_input[ $option_name ] = 0;
			} elseif ( ! in_array( $input[ $option_name ], $admitted_values, true ) ) {
				$new_input[ $option_name ] = 0;
			} elseif ( 'on' === $input[ $option_name ] ) {
				$new_input[ $option_name ] = 1;
			} else {
				$new_input[ $option_name ] = $input[ $option_name ];
			}
		}

		// One hash has to be selected, if we are using SRI or hashes for CSP.
		if (
		(
		! empty( $input['sri_script'] ) ||
		! empty( $input['sri_link'] ) ||

		/**
		 * In CSP3 hashes are only allowed for inline script, inline styles and external script
		 * but support for external styles or imgs in the specification has not been announced
		 * https://www.w3.org/TR/CSP3/#external-hash
		 * 'hash' === $input['script-src_mode'] ||
		 * 'hash' === $input['img-src_mode']
		 */
		'hash' === $input['script-src_mode']

		) &&
		(
		empty( $input['sri_sha256'] ) &&
		empty( $input['sri_sha384'] ) &&
		empty( $input['sri_sha512'] )
		)
		) {
			$new_input['sri_sha256'] = 1;
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
		$script_src_mode = array( 'nonce', 'hash', 'none' );
		if ( in_array( $input['script-src_mode'], $script_src_mode, true ) ) {
			$new_input['script-src_mode'] = $input['script-src_mode'];
		} else {
			$new_input['script-src_mode'] = 'nonce';
		}
		$style_src_mode = array( 'nonce', 'none' );
		if ( in_array( $input['style-src_mode'], $style_src_mode, true ) ) {
			$new_input['style-src_mode'] = $input['style-src_mode'];
		} else {
			$new_input['style-src_mode'] = 'nonce';
		}
		/**
		 * In CSP3 hashes are only allowed for inline script, inline styles and external script
		 * but support for external styles or imgs in the specification has not been announced
		 * https://www.w3.org/TR/CSP3/#external-hash
		 *
		 * $img_src_mode = array( 'hash', 'none' );
		 * if ( in_array( $input['img-src_mode'], $img_src_mode, true ) ) {
		 *  $new_input['img-src_mode'] = $input['img-src_mode'];
		 * } else {
		 *  $new_input['img-src_mode'] = 'none';
		 * }
		 */

		// text.
		if ( isset( $input['group_name'] ) && is_string( $input['group_name'] ) ) {
			$new_input['group_name'] = sanitize_text_field( $input['group_name'] );
		}

		if ( isset( $input['max_age'] ) ) {
			$filtered_value = filter_var(
				$input['max_age'],
				FILTER_VALIDATE_INT,
				array( 'options' => array( 'min_range' => 0 ) )
			);
			if ( false !== $filtered_value ) {
				$new_input['max_age'] = $filtered_value;
			}
		}

		$admitted_log_drivers = array(
			'db',
			'errorlog',
		);
		if ( isset( $input['log_driver'] ) && is_string( $input['log_driver'] ) ) {
			if ( in_array( $input['log_driver'], $admitted_log_drivers, true ) ) {
				$new_input['log_driver'] = sanitize_text_field( $input['log_driver'] );
			}
		}

		$admitted_log_levels = array(
			'error',
			'warning',
			'info',
			'debug',
		);
		if ( isset( $input['log_level'] ) && is_string( $input['log_level'] ) ) {
			if ( in_array( $input['log_level'], $admitted_log_levels, true ) ) {
				$new_input['log_level'] = sanitize_text_field( $input['log_level'] );
			}
		}

		unset( $options['endpoints'] );
		if ( isset( $input['endpoints'] ) && is_array( $input['endpoints'] ) ) {
			$new_input['endpoints'] = array_map( 'esc_url_raw', $input['endpoints'], $protocols = array( array( 'https' ) ) );
		}

		if ( isset( $input['max_response_header_size'] ) ) {
			$filtered_value = filter_var(
				$input['max_response_header_size'],
				FILTER_VALIDATE_INT,
				array( 'options' => array( 'min_range' => 0 ) )
			);
			if ( false !== $filtered_value ) {
				$new_input['max_response_header_size'] = $filtered_value;
			}
		}

		$new_options = array_merge( $options, $new_input );

		return $new_options;
	}

	/**
	 * Sanitize the tools status
	 *
	 * @param array<int|string>|null $input Contains the settings.
	 * @return array<mixed>
	 */
	public function sanitize_tools( $input ) {
		$options = (array) get_option( 'no-unsafe-inline-tools' );

		$admitted_values = array( 0, 1, 'on' );
		$options_checked = array(
			'capture_enabled',
			'test_policy',
			'enable_protection',
		);

		// All tools are unchecked.
		if ( is_null( $input ) ) {
			foreach ( $options_checked as $option_name ) {
				$input[ $option_name ] = 0;
			}
			$new_options = array_merge( $options, $input );
			return $new_options;
		}

		foreach ( $input as $key => $value ) {
			if ( ! in_array( $key, $options_checked, true ) ) {
				unset( $input[ $key ] );
			}
		}
		foreach ( $options_checked as $option_name ) {
			if ( ! isset( $input[ $option_name ] ) ) {
				$input[ $option_name ] = 0;
			}
			if ( ! in_array( $input[ $option_name ], $admitted_values, true ) ) {
				$input[ $option_name ] = 0;
			}
			if ( 'on' === $input[ $option_name ] ) {
				$input[ $option_name ] = 1;
			}
		}

		$new_options = array_merge( $options, $input );

		return $new_options;
	}

	/**
	 * Sanitize the base CSP rules
	 *
	 * @param array<string> $input Contains the settings.
	 * @return array<string>
	 */
	public function sanitize_base_rule( $input ) {
		$new_input = array();
		$options   = get_option( 'no-unsafe-inline-base-rule' );
		foreach ( $this->managed_directives as $directive ) {
			$setting_name = $directive . '_base_rule';
			if ( isset( $input[ $setting_name ] ) ) {
				$new_input[ $setting_name ] = sanitize_text_field( $input[ $setting_name ] );
			}
		}
		if ( is_array( $options ) ) {
			$new_input = array_merge( $options, $new_input );
		}
		return $new_input;
	}

	/**
	 * Options block
	 */
	/**
	 * Print the fetch directives section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_directives_section(): void {
		print esc_html__( 'Select the CSP directives that you want to manage with this plugin.', 'no-unsafe-inline' );
	}

	/**
	 * Print the option to enable a  *-src directive.
	 *
	 * @param array<string> $args Function arguments.
	 * @since 1.0.0
	 * @return void
	 */
	public function print_directive_src_enabled( $args ): void {
		$option_name = $args['option_name'];
		$label       = $args['label'];
		$options     = (array) get_option( 'no-unsafe-inline' );
		$value       = isset( $options[ $option_name ] ) ? esc_attr( strval( Utils::cast_strval( $options[ $option_name ] ) ) ) : 0;
		$enabled     = $value ? 'checked="checked"' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[%1$s]"' .
			'name="no-unsafe-inline[%2$s]" %3$s />
			<label for="no-unsafe-inline[%4$s]">%5$s</label>',
			esc_html( $option_name ),
			esc_html( $option_name ),
			esc_html( $enabled ),
			esc_html( $option_name ),
			// translators: %s is a CSP directory link on w3.org specs for CSP3.
			sprintf( esc_html__( 'Enable managing of the %s directive.', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#directive-' . esc_html( $label ) . '" target="_blank">' . esc_html( $label ) . '</a>' )
		);
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
	 * Print the external host mode option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_external_host_mode_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['external_host_mode'] ) ? strval( Utils::cast_strval( $options['external_host_mode'] ) ) : 'host';

		echo (
		'<div class="nunil-radio-div">' .
		'<label for="resource" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="resource" value="resource" ' );
		echo( checked( 'resource', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'resource (eg. https://www.example.org/script.js)', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="sch-host" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="sch-host" value="sch-host" ' );
		echo( checked( 'sch-host', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'scheme-host (eg. https://www.example.org)', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="host" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="host" value="host" ' );
		echo( checked( 'host', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'host (eg. www.example.org)', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="domain" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[external_host_mode]" id="domain" value="domain" ' );
		echo( checked( 'domain', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'domain (eg *.example.org)', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .
		'</div>'
		);
	}

	/**
	 * Print the use hashes for external script session
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_ext_mode(): void {
		print esc_html__( 'Select CSP mode for selected directives.', 'no-unsafe-inline' );
	}

	/**
	 * Print the script-src_mode option
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function print_mode_for_script_src(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['script-src_mode'] ) ? $options['script-src_mode'] : 'nonce';
		echo (
		'<div class="nunil-radio-div-horiz">' .
		'<label for="script-src-nonce" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[script-src_mode]" id="script-src-nonce" value="nonce" ' );
		echo( checked( 'nonce', $value, false ) );
		echo( '/><span>' . esc_html__( 'nonce', 'no-unsafe-inline' ) . '</span></label>' );

		echo (
		'<label for="script-src-hash" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[script-src_mode]" id="script-src-hash" value="hash" ' );
		echo( checked( 'hash', $value, false ) );
		echo( '/><span>' . esc_html__( 'hash', 'no-unsafe-inline' ) . '</span></label>' );

		echo (
		'<label for="script-src-none" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[script-src_mode]" id="script-src-none" value="none" ' );
		echo( checked( 'none', $value, false ) );
		echo( '/><span>' . esc_html__( 'none', 'no-unsafe-inline' ) . '</span></label></div>' );
	}

	/**
	 * Print the style-src_mode option
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function print_mode_for_style_src(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['style-src_mode'] ) ? $options['style-src_mode'] : 'nonce';
		echo (
		'<div class="nunil-radio-div-horiz">' .
		'<label for="style-src-nonce" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[style-src_mode]" id="style-src-nonce" value="nonce" ' );
		echo( checked( 'nonce', $value, false ) );
		echo( '/><span>' . esc_html__( 'nonce', 'no-unsafe-inline' ) . '</span></label>' );

		echo (
		'<label for="style-src-none" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[style-src_mode]" id="style-src-none" value="none" ' );
		echo( checked( 'none', $value, false ) );
		echo( '/><span>' . esc_html__( 'none', 'no-unsafe-inline' ) . '</span></label></div>' );
	}

	/**
	 * Print the img-src_mode option
	 *
	 * @since 1.1.0
	 * @return void
	 * @deprecated since versione 1.1.2a
	 */
	public function print_mode_for_img_src(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['img-src_mode'] ) ? $options['img-src_mode'] : 'none';

		echo (
		'<div class="nunil-radio-div-horiz">' .
		'<label for="img-src-hash" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[img-src_mode]" id="img-src-hash" value="hash" ' );
		echo( checked( 'hash', $value, false ) );
		echo( '/><span>' . esc_html__( 'hash', 'no-unsafe-inline' ) . '</span></label>' );

		echo (
		'<label for="img-src-none" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[img-src_mode]" id="img-src-none" value="none" ' );
		echo( checked( 'none', $value, false ) );
		echo( '/><span>' . esc_html__( 'none', 'no-unsafe-inline' ) . '</span></label></div>' );
	}

	/**
	 * Print the select algos session
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_algo_in_use(): void {
		print esc_html__( 'Select algos to be used in external resource identification', 'no-unsafe-inline' );
	}

	/**
	 * Print the sri_sha256 option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sri_sha256_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['sri_sha256'] ) ? $options['sri_sha256'] : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha256]"' .
			'name="no-unsafe-inline[sri_sha256]" %s />
			<label for="no-unsafe-inline[sri_sha256]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha256 hashes in csp and integrity attribute.', 'no-unsafe-inline' )
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
		$value   = isset( $options['sri_sha384'] ) ? $options['sri_sha384'] : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha384]"' .
			'name="no-unsafe-inline[sri_sha384]" %s />
			<label for="no-unsafe-inline[sri_sha384]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha384 hashes in csp and integrity attribute.', 'no-unsafe-inline' )
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
		$value   = isset( $options['sri_sha512'] ) ? $options['sri_sha512'] : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[sri_sha512]"' .
			'name="no-unsafe-inline[sri_sha512]" %s />
			<label for="no-unsafe-inline[sri_sha512]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use sha512 hashes in csp and integrity attribute.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the use Subresource Integrity session
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_sri_section(): void {
		printf(
		// translators: Subresource Integrity link.
			esc_html__( 'Options to use %s', 'no-unsafe-inline' ),
			'<a href="https://w3c.github.io/webappsec-subresource-integrity/" target="_blank">Subresource Integrity</a>'
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
		$value   = isset( $options['sri_script'] ) ? $options['sri_script'] : 0;
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
		$value   = isset( $options['sri_link'] ) ? $options['sri_link'] : 0;
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
	 * Print the inline script mode section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_script_mode_section(): void {
		print esc_html__( 'Select how to identify whitelisted inline scripts.', 'no-unsafe-inline' );
	}

	/**
	 * Print the inline script mode option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_script_mode_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['inline_scripts_mode'] ) ? strval( Utils::cast_strval( $options['inline_scripts_mode'] ) ) : 'nonce';

		echo (
		'<div class="nunil-radio-div">' .
		'<label for="nonce" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="nonce" value="nonce" ' );
		echo( checked( 'nonce', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'nonce', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="sha256" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha256" value="sha256" ' );
		echo( checked( 'sha256', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'sha256', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="sha384" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha384" value="sha384" ' );
		echo( checked( 'sha384', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'sha384', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .

		'<label for="sha512" class="nunil-l-radio">' .
		'<input type="radio" name="no-unsafe-inline[inline_scripts_mode]" id="sha512" value="sha512" ' );
		echo( checked( 'sha512', $value, false ) );
		echo( '/>' .
		'<span>' . esc_html__( 'sha512', 'no-unsafe-inline' ) . '</span>' .
		'</label>' .
		'</div>'
		);
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
	 * Print the use_strict-dynamic option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_strict_dynamic_option(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['use_strict-dynamic'] ) ? $options['use_strict-dynamic'] : 0;
		$enabled = $value ? 'checked' : '';
		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[use_strict-dynamic]"' .
			'name="no-unsafe-inline[use_strict-dynamic]" %s />
			<label for="no-unsafe-inline[use_strict-dynamic]">%s</label>',
			esc_html( $enabled ),
			// translators: strict-dynamic link.
			sprintf( esc_html__( 'Add %s in script-src and default-src.', 'no-unsafe-inline' ), '<a href="https://www.w3.org/TR/CSP3/#strict-dynamic-usage" target="_blank">\'strict-dynamic\'</a>' ) . '<br>' . sprintf(
			// translators: %1$s and %2$s are link to external websites.
				esc_html__( 'This is only partially supported in Mozilla/Firefox. Read %1$s and %2$s', 'no-unsafe-inline' ),
				'<a href="https://bugzilla.mozilla.org/show_bug.cgi?id=1409200#c6" target="_blank">https://bugzilla.mozilla.org/show_bug.cgi?id=1409200#c6</a>',
				'<a href="https://webcompat.com/issues/85780" target="_blank">https://webcompat.com/issues/85780</a>'
			)
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
		$value   = isset( $options['no-unsafe-inline_upgrade_insecure'] ) ? $options['no-unsafe-inline_upgrade_insecure'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]"' .
			'name="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]" %s />
			<label for="no-unsafe-inline[no-unsafe-inline_upgrade_insecure]">%s</label>',
			esc_html( $enabled ),
			// translators: upgrade-insecure-requests link.
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
		$value   = isset( $options['protect_admin'] ) ? $options['protect_admin'] : 0;

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
		$value   = isset( $options['use_unsafe-hashes'] ) ? $options['use_unsafe-hashes'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[use_unsafe-hashes]"' .
			'name="no-unsafe-inline[use_unsafe-hashes]" %s />
			<label for="no-unsafe-inline[use_unsafe-hashes]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'The \'unsafe-hashes\' Content Security Policy (CSP) keyword allows the execution of inline scripts within a JavaScript event handler attribute of a HTML element. This is not safe and this plugin can handle event handlers HTML attributes without \'unsafe-hashes\'.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the fix setAttribute('style') option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_fix_setattribute_style(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['fix_setattribute_style'] ) ? $options['fix_setattribute_style'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[fix_setattribute_style]"' .
			'name="no-unsafe-inline[fix_setattribute_style]" %s />
			<label for="no-unsafe-inline[fix_setattribute_style]">%s<br>%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Globally replace Element.setAttribute() with Element.style.property = val, Element.insertAdjacentHTML(), Element.innerHTML setter, Node.appendChild(), Node.insertBefore() and override jQuery htmlPrefilter() for CSP-safe applying of inline styles.', 'no-unsafe-inline' ),
			esc_html__( 'This cannot avoid CSP errors triggered by react-dom.js using innerHTML because it\'s overrided by react itself.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print add_wl_by_cluster_to_db option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_add_wl_by_cluster_to_db(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['add_wl_by_cluster_to_db'] ) ? $options['add_wl_by_cluster_to_db'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[add_wl_by_cluster_to_db]"' .
			'name="no-unsafe-inline[add_wl_by_cluster_to_db]" %s />
			<label for="no-unsafe-inline[add_wl_by_cluster_to_db]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Add auto-authorized scripts in db.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print max_response_header_size option
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function print_max_response_header_size(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['max_response_header_size'] ) ? $options['max_response_header_size'] : 8192;

		printf(
			'<input class="nunil-max-response-header-size" type="text" id="no-unsafe-inline[max_response_header_size]"' .
			'name="no-unsafe-inline[max_response_header_size]" value="%d" />
			<label for="no-unsafe-inline[max_response_header_size]">%s %s</label>',
			intval( Utils::cast_intval( $value ) ),
			esc_html__( 'Write here the HTTP Response Header Size Limit allowed by your server (in bytes). See: ', 'no-unsafe-inline' ),
			'<a href="https://maxchadwick.xyz/blog/http-response-header-size-limits" target="_blank">https://maxchadwick.xyz/blog/http-response-header-size-limits</a>'
		);
	}

	/**
	 * Print the logs section
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_logs_section(): void {
		print esc_html__( 'Plugin logs', 'no-unsafe-inline' );
	}

	/**
	 * Print the logs driver setting
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_log_driver(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = ( isset( $options['log_driver'] ) && is_string( $options['log_driver'] ) ) ? esc_attr( $options['log_driver'] ) : 'errorlog';

		$print_selected = function ( $val ) use ( $value ) {
			return $val === $value ? 'selected' : '';
		};
		printf(
			'<select name="no-unsafe-inline[log_driver]" id="no-unsafe-inline[log_driver]">' .
			'<option value="errorlog" ' . esc_html( $print_selected( 'errorlog' ) ) . '>PHP - error_log()</option>' .
			'<option value="db"' . esc_html( $print_selected( 'db' ) ) . '>Database</option>' .
			'</select>'
		);
	}

	/**
	 * Print the logs level setting
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_log_level(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = ( isset( $options['log_level'] ) && is_string( $options['log_level'] ) ) ? esc_attr( $options['log_level'] ) : 'error';

		$print_selected = function ( $val ) use ( $value ) {
			return $val === $value ? 'selected' : '';
		};
		printf(
			'<select name="no-unsafe-inline[log_level]" id="no-unsafe-inline[log_level]">' .
			'<option value="error" ' . esc_html( $print_selected( 'error' ) ) . '>Error</option>' .
			'<option value="warning"' . esc_html( $print_selected( 'warning' ) ) . '>Warning</option>' .
			'<option value="info"' . esc_html( $print_selected( 'info' ) ) . '>Info</option>' .
			'<option value="debug"' . esc_html( $print_selected( 'debug' ) ) . '>Debug</option>' .
			'</select>'
		);
	}

	/**
	 * Print the report section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_report_section(): void {
		print esc_html__( 'Violation reports options.', 'no-unsafe-inline' );
	}

	/**
	 * Print the use_reports option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_use_reports(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['use_reports'] ) ? $options['use_reports'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[use_reports]"' .
			'name="no-unsafe-inline[use_reports]" %s />
			<label for="no-unsafe-inline[use_reports]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Use report-to and report-uri.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the group_name option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_group_name(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = ( isset( $options['group_name'] ) && is_string( $options['group_name'] ) ) ? $options['group_name'] : 'csp-endpoint';

		$in_use = isset( $options['use_reports'] ) ? $options['use_reports'] : '';

		$disabled = ( '' !== $in_use ) ? '' : 'disabled';

		printf(
			'<input class="nunil-text-group" type="text" id="no-unsafe-inline[group_name]"' .
			'name="no-unsafe-inline[group_name]" value="%s" %s />
			<label for="no-unsafe-inline[group_name]">%s</label>',
			esc_html( $value ),
			esc_html( $disabled ),
			esc_html__( 'Optional. If a group name is not specified, the endpoints\' group is given the name of "csp-endpoint".', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the max_age option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_max_age(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['max_age'] ) ? $options['max_age'] : 10886400;

		$in_use = isset( $options['use_reports'] ) ? $options['use_reports'] : '';

		$disabled = ( '' !== $in_use ) ? '' : 'disabled';

		printf(
			'<input class="nunil-text-maxage" type="text" id="no-unsafe-inline[max_age]"' .
			'name="no-unsafe-inline[max_age]" value="%d" %s />
			<label for="no-unsafe-inline[max_age]">%s</label>',
			intval( Utils::cast_intval( $value ) ),
			esc_html( $disabled ),
			esc_html__( 'Required. A non-negative integer that defines the lifetime of the endpoint in seconds (how long the browser should use the endpoint and report errors to it). A value of "0" will cause the endpoint group to be removed from the user agent’s reporting cache.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the endpoints option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_endpoints(): void {
		$options   = (array) get_option( 'no-unsafe-inline' );
		$endpoints = ( isset( $options['endpoints'] ) && is_array( $options['endpoints'] ) ) ? $options['endpoints'] : array();

		$in_use = isset( $options['use_reports'] ) ? $options['use_reports'] : '';

		$disabled = ( '' !== $in_use ) ? '' : 'disabled';

		// Add new endpoint button.
		printf(
			'<input class="nunil-btn nunil-btn-addnew" type="button" id="no-unsafe-inline[add_new_endpoint]"' .
			'name="no-unsafe-inline[add_new_endpoint]" value="%s" %s />' .
			'<input class="nunil-new-endpoint" type="text" id="no-unsafe-inline[new_endpoint]"' .
			'name="no-unsafe-inline[new_endpoint]" %s /> 
			<label for="no-unsafe-inline[new_endpoint]">%s</label>',
			esc_html__( 'Add a new endpoint', 'no-unsafe-inline' ),
			esc_html( $disabled ),
			esc_html( $disabled ),
			esc_html__( 'Required. An array of JSON objects that specify the actual URL of your report collector.', 'no-unsafe-inline' )
		);

		print( '<ol class="nunil-endpoints-list" id="nunil-endpoints-list">' );
		if ( is_array( $endpoints ) ) {
			// Add a line for each url.
			foreach ( $endpoints as $index => $endpoint ) {
				printf(
					'<li>' .
					'<button class="nunil-btn nunil-btn-del-endpoint" ' .
					'id="no-unsafe-inline[del-endpoint][%d]" name="no-unsafe-inline[del-endpoint][%d]">' .
					'<span class="dashicons dashicons-remove"> </span></button>' .
					'<span class="nunil-endpoint-string txt-active">%s</span>' .
					'<input class="nunil-hidden-endpoint" type="hidden" id="no-unsafe-inline[endpoints][%d]" ' .
					'name="no-unsafe-inline[endpoints][%d]" value="%s" />' .
					'</li>',
					esc_html( $index ),
					esc_html( $index ),
					esc_html( $endpoint ),
					esc_html( $index ),
					esc_html( $index ),
					esc_html( $endpoint )
				);
			}
		}
		print( '</ol>' );
	}


	/**
	 * Print the deactivate section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_deactivate_section(): void {
		print esc_html__( 'Deactivation options.', 'no-unsafe-inline' );
	}

	/**
	 * Print the remove_tables option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_remove_tables(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['remove_tables'] ) ? $options['remove_tables'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[remove_tables]"' .
			'name="no-unsafe-inline[remove_tables]" %s />
			<label for="no-unsafe-inline[remove_tables]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Remove data tables from DB on single site plugin deactivation.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Print the remove_tables option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_remove_options(): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$value   = isset( $options['remove_options'] ) ? $options['remove_options'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline[remove_options]"' .
			'name="no-unsafe-inline[remove_options]" %s />
			<label for="no-unsafe-inline[remove_options]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Remove plugin options from DB on single site plugin deactivation.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Tools block
	 */
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
	 * Print the capture toggle option
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_capture_enabled(): void {
		$options = (array) get_option( 'no-unsafe-inline-tools' );
		$value   = isset( $options['capture_enabled'] ) ? $options['capture_enabled'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[capture_enabled]" ' .
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
		$value   = isset( $options['test_policy'] ) ? $options['test_policy'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[test_policy]" ' .
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
		$value   = isset( $options['enable_protection'] ) ? $options['enable_protection'] : 0;

		$enabled = $value ? 'checked' : '';

		printf(
			'<input class="nunil-ui-toggle" type="checkbox" id="no-unsafe-inline-tools[enable_protection]" ' .
			'name="no-unsafe-inline-tools[enable_protection]" %s />
			<label for="no-unsafe-inline-tools[enable_protection]">%s</label>',
			esc_html( $enabled ),
			esc_html__( 'Enable CSP protection.', 'no-unsafe-inline' )
		);
	}

	/**
	 * Base src block
	 */
	/**
	 * Print the base -src sources section info
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_base_rule_section(): void {
		print esc_html__( 'Input here the base sources allowed for each CSP -src directive and rules for document and navigation directives.', 'no-unsafe-inline' );
		echo '<br />';
		print esc_html__( 'You can populate some of these fields by ticking the checkboxes in the table at the bottom of the page.', 'no-unsafe-inline' );
	}

	/**
	 * Print the directive option.
	 *
	 * @since 1.0.0
	 * @param array<string> $args Function arguments.
	 * @return void
	 */
	public function print_base_rule( $args ): void {
		$option_name = $args['option_name'];
		$label       = $args['label'];
		$options     = (array) get_option( 'no-unsafe-inline-base-rule' );
		$value       = isset( $options[ $option_name ] ) ? esc_attr( strval( Utils::cast_strval( $options[ $option_name ] ) ) ) : '';

		printf(
			'<div class="nunil-base-rule-container">' .
			'<label for="no-unsafe-inline-base-rule[%1$s]" class="nunil-base-rule"/>' .
			'<input type="text" id="no-unsafe-inline-base-rule[%1$s]" name="no-unsafe-inline-base-rule[%1$s]" class="nunil-base-rule" value="%3$s"/>%2$s</label>' .
			'</div>',
			esc_html( $option_name ),
			esc_html( $label ),
			esc_html( $value )
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
					esc_html__( 'All the settings are described in the %1$sinline help%2$s.', 'no-unsafe-inline' ),
					'<a href="#" id="nunil-help-link">',
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
					<a href="?page=no-unsafe-inline&tab=base-rule" class="nav-tab 
						<?php
						if ( 'base-rule' === $tab ) :
							?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Base rules', 'no-unsafe-inline' ) ); ?></a>
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

					<a href="?page=no-unsafe-inline&tab=logs" class="nav-tab 
						<?php
						if ( 'logs' === $tab ) :
							?>
						nav-tab-active<?php endif; ?>"><?php printf( esc_html__( 'Logs', 'no-unsafe-inline' ) ); ?></a>
				</nav>

				<div class="tab-content">
					<?php
					switch ( $tab ) :
						case 'settings':
							self::print_options_page();
							break;
						case 'base-rule':
							self::print_base_rule_page();
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
						case 'logs':
							self::print_logs_page();
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
	 * Save screen options.
	 *
	 * @param boolean $status false by default, return this to not save options.
	 * @param string  $option The option name, a key in user_meta.
	 * @param mixed   $value  The option value for user_meta.
	 * @return mixed
	 */
	public function save_screen_options( $status, $option, $value ) {
		$this_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		switch ( $this_page ) {
			case 'no-unsafe-inline':
				return $value;
		}
		return $status;
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
	 * Renders the base rules page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_base_rule_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-base-rule-list.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-base-rule.php';
	}

	/**
	 * Renders the external page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_external_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-whitelist-tables.php';
	}

	/**
	 * Renders the inline scripts page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_inline_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-whitelist-tables.php';
	}

	/**
	 * Renders the events scripts page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_events_page(): void {
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-whitelist-tables.php';
	}

	/**
	 * Renders the logs table page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_logs_page(): void {
		$options      = (array) get_option( 'no-unsafe-inline', array() );
		$enabled_logs = isset( $options['log_driver'] ) && is_string( $options['log_driver'] ) && 'db' === $options['log_driver'];
		if ( ! $enabled_logs ) {
			$message = esc_html__( 'You are using a logger that does not support the Log viewer (supported: Database)', 'no-unsafe-inline' );
		}
		require_once plugin_dir_path( __FILE__ ) . 'partials/class-no-unsafe-inline-admin-logs-table.php';
		require_once plugin_dir_path( __FILE__ ) . 'partials/no-unsafe-inline-logs.php';
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

			$allowed_html_in_notice = array(
				'br'     => array(),
				'i'      => array(),
				'b'      => array(),
				'strong' => array(),
				'em'     => array(),
			);

			if ( 'warning' === $type ) {
				printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses( $message, $allowed_html_in_notice ) );
			} elseif ( 'error' === $type ) {
				printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses( $message, $allowed_html_in_notice ) );
			} else {
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', wp_kses( $message, $allowed_html_in_notice ) );
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
		if ( ! (
		isset( $_REQUEST['nonce'] )
		&& wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'nunil_trigger_clustering_nonce' )
		) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}

		$obj = new NUNIL\Nunil_Clustering();

		$result = $obj->cluster_by_dbscan();

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			echo wp_json_encode( $result );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Location: ' . esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
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
		if ( ! (
		isset( $_REQUEST['nonce'] )
		&& wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'nunil_trigger_clean_database' )
		) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}

		$tables = array(
			'event_handlers',
			'external_scripts',
			'inline_scripts',
			'occurences',
		);

		$result_string = '<br><b> --- ' . esc_html__( 'DELETE ALL SCRIPTS FROM DATABASE', 'no-unsafe-inline' ) . ' --- </b><br>';
		$result_string = $result_string . '<ul>';

		foreach ( $tables as $table ) {
			$delete = DB::truncate_table( $table );

			$delete_string = $delete ? esc_html__( 'succeded', 'no-unsafe-inline' ) : esc_html__( 'FAILED', 'no-unsafe-inline' );

			$result_string = $result_string . ( "<li>TRUNCATE $table: $delete_string</li>" );
			Utils::set_last_modified( $table );
		}
		$result_string = $result_string . '</ul>';

		Utils::show_message( '<strong>No unsafe-inline</strong> ' . esc_html__( 'Eliminated the content of the database at the user\'s request', 'no-unsafe-inline' ), 'success' );
		Log::info( esc_html__( 'Eliminated the content of the database at the user\'s request', 'no-unsafe-inline' ) );

		$result = array(
			'type'   => 'success',
			'report' => $result_string,
		);

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			echo wp_json_encode( $result );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Location: ' . esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		}

		wp_die();
	}

	/**
	 * Prunes script tables
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prune_database(): void {
		if ( ! (
		isset( $_REQUEST['nonce'] )
		&& wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'nunil_trigger_prune_database' )
		) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}

		$prune = new \NUNIL\Nunil_Prune_Db();

		$result_string = '<br><b> --- ' . esc_html__( 'Pruning data from database', 'no-unsafe-inline' ) . ' --- </b><br>';
		$result_string = $result_string . $prune->delete_orphan_occurences();
		$result_string = $result_string . $prune->prune_big_clusters();
		$result_string = $result_string . $prune->prune_external_assets();
		$result_string = $result_string . $prune->prune_old_occurences();

		Utils::show_message( '<strong>No unsafe-inline</strong> ' . esc_html__( 'Pruned the database at the user\'s request', 'no-unsafe-inline' ), 'success' );
		Log::info( esc_html__( 'Pruned the database at the user\'s request', 'no-unsafe-inline' ) );

		$result = array(
			'type'   => 'success',
			'report' => $result_string,
		);

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			echo wp_json_encode( $result );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Location: ' . esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		}

		wp_die();
	}

	/**
	 * Updates the summary table of inline scripts
	 * Called by ajax
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function update_summary_tables(): void {
		$result             = array();
		$result['global']   = DB::get_database_summary_data( 'global' );
		$result['external'] = DB::get_database_summary_data( 'external_scripts' );
		$result['inline']   = DB::get_database_summary_data( 'inline_scripts' );
		$result['events']   = DB::get_database_summary_data( 'event_handlers' );

		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			echo wp_json_encode( $result );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Location: ' . esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
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
	public static function output_summary_tables() {
		$result = DB::get_database_summary_data( 'global' );

		$htb = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Type', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num. Clusters', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_db_summary_body">';
		if ( isset( $result ) ) {
			foreach ( $result as $print ) {
				$htb = $htb . '<tr>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Type', 'no-unsafe-inline' ) . '">' . $print->type . '</td>';
				if ( '0' === $print->whitelist ) {
					$wl_text = __( 'BL', 'no-unsafe-inline' );
				} else {
					$wl_text = __( 'WL', 'no-unsafe-inline' );
				}
				$htb = $htb . '<td data-th="' . esc_attr__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Num. Clusters', 'no-unsafe-inline' ) . '">' . $print->clusters . '</td>';
				$htb = $htb . '</tr>';
			}
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
	public static function output_summary_external_table() {
		$result = DB::get_database_summary_data( 'external_scripts' );

		$htb = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Nonceable', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_external_table_summary_body">';
		if ( isset( $result ) ) {
			foreach ( $result as $print ) {
				$htb = $htb . '<tr>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Directive', 'no-unsafe-inline' ) . '">' . $print->directive . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Nonceable', 'no-unsafe-inline' ) . '">' . $print->nonceable . '</td>';
				if ( '0' === $print->whitelist ) {
					$wl_text = __( 'BL', 'no-unsafe-inline' );
				} else {
					$wl_text = __( 'WL', 'no-unsafe-inline' );
				}
				$htb = $htb . '<td data-th="' . esc_attr__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
				$htb = $htb . '</tr>';
			}
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
	public static function output_summary_inline_table() {
		$result = DB::get_database_summary_data( 'inline_scripts' );

		$htb = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Directive', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_inline_table_summary_body">';
		if ( isset( $result ) ) {
			foreach ( $result as $print ) {
				$htb = $htb . '<tr>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Directive', 'no-unsafe-inline' ) . '">' . $print->directive . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Cluster', 'no-unsafe-inline' ) . '">' . $print->clustername . '</td>';
				if ( '0' === $print->whitelist ) {
					$wl_text = __( 'BL', 'no-unsafe-inline' );
				} else {
					$wl_text = __( 'WL', 'no-unsafe-inline' );
				}
				$htb = $htb . '<td data-th="' . esc_attr__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
				$htb = $htb . '</tr>';
			}
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
	public static function output_summary_eventhandlers_table() {
		$result = DB::get_database_summary_data( 'event_handlers' );

		$htb = '
		<table class="rwd-table">
			<tr>
			 <th>' . esc_html__( 'Tagname', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Event Attribute', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Cluster', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Whitelist', 'no-unsafe-inline' ) . '</th>
			 <th>' . esc_html__( 'Num.', 'no-unsafe-inline' ) . '</th>
			</tr>
			<tbody id="nunil_eventhandlers_table_summary_body">';
		if ( isset( $result ) ) {
			foreach ( $result as $print ) {
				$htb = $htb . '<tr>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Tagname', 'no-unsafe-inline' ) . '">' . $print->tagname . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Event Attribute', 'no-unsafe-inline' ) . '">' . $print->event_attribute . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Cluster', 'no-unsafe-inline' ) . '">' . $print->clustername . '</td>';
				if ( '0' === $print->whitelist ) {
					$wl_text = __( 'BL', 'no-unsafe-inline' );
				} else {
					$wl_text = __( 'WL', 'no-unsafe-inline' );
				}
				$htb = $htb . '<td data-th="' . esc_attr__( 'Whitelist', 'no-unsafe-inline' ) . '">' . $wl_text . '</td>';
				$htb = $htb . '<td data-th="' . esc_attr__( 'Num.', 'no-unsafe-inline' ) . '">' . $print->num . '</td>';
				$htb = $htb . '</tr>';
			}
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
		if ( ! (
		isset( $_REQUEST['nonce'] )
		&& wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'nunil_test_classifier_nonce' )
		) ) {
			exit( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
		}
		$test = new NUNIL\Nunil_Classification();

		$result_string = $test->test_cases();
		$result        = array(
			'type'   => 'success',
			'report' => $result_string,
		);
		if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest' ) {
			echo wp_json_encode( $result );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Location: ' . esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		}

		wp_die();
	}
}
