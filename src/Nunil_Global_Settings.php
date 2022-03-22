<?php
/**
 * Global Setting used in plugin
 *
 * This parameters can be tweaked, but are not options available to users.
 * They have been setted in an empirical way.
 * Site admins can override them by placing an option named no-unsafe-inline-global-settings
 * in wp_otions table
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

/**
 * The global settings class.
 *
 * This is used to define some settings used inside the plugin not available for users.
 *
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class Nunil_Global_Settings {

	/**
	 * The espilon parameter used in clustering inline scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $dbscan_epsilon_inl
	 */
	public $dbscan_epsilon_inl;

	/**
	 * The minSamples parameter used in clustering inline scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $dbscan_minsamples_inl
	 */
	public $dbscan_minsamples_inl;

	/**
	 * The espilon parameter used in clustering event handlers scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $dbscan_epsilon_evh
	 */
	public $dbscan_epsilon_evh;

	/**
	 * The minSamples parameter used in clustering event handlers scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $dbscan_minsamples_evh
	 */
	public $dbscan_minsamples_evh;

	/**
	 * K paramenter of KNearestNeighbors for classification of innline scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $knn_k_inl
	 */
	public $knn_k_inl;

	/**
	 * K paramenter of KNearestNeighbors for classification of event handlers scripts
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      int    $knn_k_evh
	 */
	public $knn_k_evh;

	/**
	 * Array of cache $expire_secs keyed by $cache_key in 'no-unsafe-inline' $cache_group
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array<int> $expire_secs
	 */
	public $expire_secs;

	/**
	 * Time limit to perform DBSCAN
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int Second set for set_time_limit() on clustering job
	 */
	public $clustering_time_limit;

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
		$def_opts = array(

			/* php-ml settings */
			'dbscan_epsilon_inl'    => 40,
			'dbscan_minsamples_inl' => 3,
			'dbscan_epsilon_evh'    => 60,
			'dbscan_minsamples_evh' => 3,
			'knn_k_inl'             => 3,
			'knn_k_evh'             => 3,

			/* wp-cache expire time */
			'expire_secs'           => array(
				'inline_rows'   => 600, // 10 minutes.
				'events_rows'   => 600,
				'external_rows' => 600,

			),
			/* misc */
			'clustering_time_limit' => 500,
		);

		$opts = get_option( 'no-unsafe-inline-global-settings', $def_opts );
		if ( $opts && is_array( $opts ) ) {
			foreach ( $opts as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}
