<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/includes
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      No_Unsafe_Inline_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The CSP src directive managed by this plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array<string>  $managed_directives    The CSP directives managed from this plugin
	 */
	public $managed_directives;


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Set the array of managed CSP -src directives
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'NO_UNSAFE_INLINE_VERSION' ) ) {
			$this->version = NO_UNSAFE_INLINE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'no-unsafe-inline';

		$this->managed_directives = array(
			'default-src',
			'script-src',
			'style-src',
			'img-src',
			'font-src',
			'connect-src',
			'media-src',
			'object-src',
			'prefetch-src',
			'child-src',
			'frame-src',
			'worker-src',
			'manifest-src',
			'base-uri',
			'form-action',
			'frame-ancestors',
		);

		$this->load_dependencies();
		$this->load_logger();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - No_Unsafe_Inline_Loader. Orchestrates the hooks of the plugin.
	 * - No_Unsafe_Inline_i18n. Defines internationalization functionality.
	 * - No_Unsafe_Inline_Admin. Defines all hooks for the admin area.
	 * - No_Unsafe_Inline_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-no-unsafe-inline-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-no-unsafe-inline-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-no-unsafe-inline-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-no-unsafe-inline-public.php';

		$this->loader = new No_Unsafe_Inline_Loader();
	}

	/**
	 * Loads the logger library.
	 * This code is taken from: https://github.com/perfectyorg/perfecty-push-wp
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_logger() {
		$options = (array) get_option( 'no-unsafe-inline', array() );
		$driver  = ( isset( $options['log_driver'] ) && is_string( $options['log_driver'] ) ) ? $options['log_driver'] : 'errorlog';
		$level   = ( isset( $options['log_level'] ) && is_string( $options['log_level'] ) ) ? $options['log_level'] : 'error';

		if ( 'db' === $driver ) {
			$logger = new \NUNIL\log\Nunil_Lib_Log_Db();
			$logger->delete_old_logs( 10 );
		} else {
			$logger = new \NUNIL\log\Nunil_Lib_Log_ErrorLog();
		}
		\NUNIL\Nunil_Lib_Log::init( $logger, \NUNIL\Nunil_Lib_Log::string_to_level( $level ) );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the No_Unsafe_Inline_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new No_Unsafe_Inline_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new No_Unsafe_Inline_Admin( $this->get_plugin_name(), $this->get_version(), $this->get_managed_directives() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 0 );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'nunil_upgrade', 10, 0 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'nunil_admin_options_submenu' );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'show_admin_notice' );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_options' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_tools_status' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_base_rule' );

		$this->loader->add_action( 'wp_ajax_nunil_update_summary_tables', $plugin_admin, 'update_summary_tables' );

		$this->loader->add_action( 'wp_ajax_nunil_trigger_clustering', $plugin_admin, 'trigger_clustering' );
		$this->loader->add_action( 'wp_ajax_nunil_clean_database', $plugin_admin, 'clean_database' );
		$this->loader->add_action( 'wp_ajax_nunil_prune_database', $plugin_admin, 'prune_database' );
		$this->loader->add_action( 'wp_ajax_nunil_test_classifier', $plugin_admin, 'test_classifier' );

		$this->loader->add_filter( 'plugin_action_links_' . NO_UNSAFE_INLINE_PLUGIN_BASENAME, $plugin_admin, 'plugin_directory_links' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'nunil_get_extra_meta_links', 10, 2 );

		$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'save_screen_options', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_public_hooks() {
		$plugin_public = new No_Unsafe_Inline_Public( $this->get_plugin_name(), $this->get_version() );

		//~ $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts', 0 );

		// This will output CSP (and Report-To) headers.
		$this->loader->add_action( 'nunil_output_csp_headers', $plugin_public, 'output_csp_headers', 100, 1 );

		// This is the main filter hook applied by the mu-plugin.
		$this->loader->add_filter( 'no_unsafe_inline_final_output', $plugin_public, 'filter_final_output' );

		// Register a route to capture CSP violation of some -src directives.
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_capture_routes' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    No_Unsafe_Inline_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the CSP managed -src directives.
	 *
	 * @since     1.0.0
	 * @return    array<string>    The array of CSP directives, managed by the plugin.
	 */
	public function get_managed_directives() {
		return $this->managed_directives;
	}
}
