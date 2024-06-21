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
	 * The maximum number of samples that each leaf node can contain (inl BallTree).
	 *
	 * @access public
	 * @var int $balltree_maxleafsize_inl
	 */
	public $balltree_maxleafsize_inl;

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
	 * The maximum number of samples that each leaf node can contain (evh BallTree).
	 *
	 * @access public
	 * @var int $balltree_maxleafsize_evh
	 */
	public $balltree_maxleafsize_evh;

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
	 * Max numbers of samples to use at once in online knn training
	 *
	 * @sice 1.2.0
	 * @access public
	 * @var int knn_train_batch_size
	 */
	public $knn_train_batch_size;

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
	 * Time limit to perform the classifier's test.
	 *
	 * @since 1.2.2
	 * @access public
	 * @var int Second set for set_time_limit() on testing classifier
	 */
	public $test_classifier_time_limit;

	/**
	 * Class constructor
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$def_opts = array(

			/* Rubix ML settings */
			'dbscan_epsilon_inl'         => 60,
			'dbscan_minsamples_inl'      => 3,
			'balltree_maxleafsize_inl'   => 100,
			'dbscan_epsilon_evh'         => 60,
			'dbscan_minsamples_evh'      => 3,
			'balltree_maxleafsize_evh'   => 100,
			'knn_k_inl'                  => 30,
			'knn_k_evh'                  => 3,
			'knn_train_batch_size'       => 150,

			/* wp-cache expire time */
			'expire_secs'                => array(
				'inline_rows'   => 60, // 1 minute.
				'events_rows'   => 60,
				'external_rows' => 60,

			),
			/* misc */
			'clustering_time_limit'      => 600,
			'test_classifier_time_limit' => 600,
		);

		$opts = get_option( 'no-unsafe-inline-global-settings', $def_opts );
		if ( $opts && is_array( $opts ) ) {
			foreach ( $opts as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}
