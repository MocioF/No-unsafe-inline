<?php
/**
 * K Nearest Neighbors Trainer
 *
 * This class is used to train models used by No unsafe-inline.
 * This implements model persistence and online (with partial method) training
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.2.0
 */

namespace NUNIL;

use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Serializers\RBX;
use Rubix\ML\Serializers\Serializer;
use Rubix\ML\Datasets\Labeled;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;

/**
 * Class used to train AI models used by No unsafe-inline
 *
 * @package No_unsafe-inline
 * @since   1.2.0
 */
class Nunil_Knn_Trainer {

	/**
	 * The model type.
	 *
	 * @since  1.2.0
	 * @access private
	 * @var    string A string identificates the model.
	 */
	private $model_type;

	/**
	 * The full file name to save persistent model
	 *
	 * @since 1.2.0
	 * @access private
	 * @var string The file name
	 */
	private $rbx_file_name;

	/**
	 * Possible model types used
	 *
	 * @var array<string>
	 */
	private $model_types = array(
		'script',
		'style',
		'event',
	);

	/**
	 * The size of the chunk of lines used for each partial train
	 *
	 * @since 1.2.0
	 * @access private
	 * @var int<1, max>
	 */
	private $array_page_size = 50;

	/**
	 * Results retrived from Database
	 *
	 * @since 1.2.0
	 * @access private
	 * @var array<\stdClass>|null
	 */
	private $raw_db_results;

	/**
	 * Filtered DB rows used to train models
	 *
	 * @since 1.2.0
	 * @access private
	 * @var array<\stdClass>|null
	 */
	private $filtered_db_results;

	/**
	 * Global Settings
	 *
	 * @var \NUNIL\Nunil_Global_Settings
	 */
	private $gls;

	/**
	 * True if WP can write to fylesystem and use persistent for AI classifier
	 *
	 * @var bool
	 */
	private bool $can_use_persistent;

	/**
	 * Classifier
	 *
	 * @since 1.2.0
	 * @access private
	 * @var \Rubix\ML\Classifiers\KNearestNeighbors $classifier The KNearestNeighbors Classifier trained.
	 */
	public KNearestNeighbors $classifier;

	/**
	 * The object serializer.
	 *
	 * @var Serializer
	 */
	private Serializer $serializer;

	/**
	 * The persister object
	 *
	 * @var \Rubix\ML\Persisters\Filesystem
	 */
	private $persister;

	/**
	 * Class constructor
	 *
	 * @param string $model_type The model type.
	 */
	public function __construct( $model_type = 'script' ) {
		if ( in_array( $model_type, $this->model_types, true ) ) {
			$this->model_type = $model_type;
		} else {
			$model_type = 'script';
		}
		$this->get_gls();
		$this->set_page_size( $this->gls->knn_train_batch_size );
		$this->can_use_persistent = $this->check_write_permission();
		$this->set_persister();
	}

	/**
	 * Set the persister
	 *
	 * @return void
	 */
	private function set_persister() {
		$this->set_rbx_file_name();
		$this->persister = new Filesystem( $this->rbx_file_name, false );
	}

	/**
	 * Sets the max numerosity of each partial dataset used in training extimators
	 *
	 * @param int $page_size The size of each chunk of the rows array used in array_chunk.
	 * @return void
	 */
	public function set_page_size( $page_size = 150 ) {
		if ( is_int( $page_size ) && 1 < $page_size ) {
			$this->array_page_size = $page_size;
		}
	}

	/**
	 * Check if WP can write to filesystem
	 *
	 * @return bool
	 */
	private function check_write_permission() {
		$write_permission = $this->create_path() && wp_is_writable( $this->persistent_files_basename() );
		if ( false === $write_permission ) {
			$message = sprintf(
				// translators: %s is a directory path.
				esc_html__( 'AI estimators cannot be persistent because %s is unwritable by PHP.', 'no-unsafe-inline' ),
				$this->persistent_files_basename()
			);
			Log::info( $message );
		}
		return $write_permission;
	}


	/**
	 * Creates the path and returns true if it is created
	 *
	 * @return bool
	 */
	private function create_path() {
		return wp_mkdir_p( $this->persistent_files_basename() );
	}

	/**
	 * Returns basename of persistent files
	 *
	 * @return string
	 */
	private function persistent_files_basename() {
		$persistents_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'no-unsafe-inline';
		if ( is_multisite() ) {
			$persistents_dir .= '/' . strval( get_current_blog_id() );
		}
		return $persistents_dir;
	}

	/**
	 * Sets the name of the file used to store serialized results
	 *
	 * @return void
	 */
	private function set_rbx_file_name() {
		$this->rbx_file_name = $this->persistent_files_basename() . '/nunil_' . $this->model_type . '.rbx';
	}

	/**
	 * Sets the gls property with global settings values.
	 *
	 * @return void
	 */
	private function get_gls() {
		$this->gls = new Nunil_Global_Settings();
	}

	/**
	 * Check for cached results and sets $raw_db_results
	 *
	 * @return void
	 */
	private function set_raw_db_results() {
		switch ( $this->model_type ) {
			case 'event':
				$db_rows = self::get_db_rows( 'events_rows' );
				break;
			default:
				$db_rows = self::get_db_rows( 'inline_rows' );
		}
		if ( is_array( $db_rows ) ) {
			$this->raw_db_results = $db_rows;
		}
	}

	/**
	 * Sets filtered db results
	 * Values of style and script tags are in the same table.
	 *
	 * @return void
	 */
	private function set_filtered_db_results() {
		if ( 'event' === $this->model_type ) {
			$this->filtered_db_results = $this->raw_db_results;
		} else {
			$this->filtered_db_results = array();
			if ( is_array( $this->raw_db_results ) ) {
				foreach ( $this->raw_db_results as $row ) {
					if ( str_replace( '-src', '', $row->directive ) === $this->model_type ) {
						$this->filtered_db_results[] = $row;
					}
				}
			}
		}
	}

	/**
	 * Sets the $classifier property
	 *
	 * @return void
	 */
	private function set_classifier() {
		$k_param    = 'event' === $this->model_type ? $this->gls->knn_k_evh : $this->gls->knn_k_inl;
		$classifier = new KNearestNeighbors( $k_param, true, new Nunil_Hamming_Distance() );

		$this->classifier = $classifier;
	}

	/**
	 * Saves a trained classifier on filesystem
	 *
	 * @return void
	 */
	public function save_trained() {
		$this->train_classifier();
		$this->serializer = new RBX();

		$encoding = $this->serializer->serialize( $this->classifier );
		$this->persister->save( $encoding );
	}

	/**
	 * Train a non persistent classifier
	 *
	 * @return void
	 */
	private function train_classifier() {
		$this->set_raw_db_results();
		$this->set_filtered_db_results();
		$this->set_classifier();

		if ( ! is_null( $this->filtered_db_results ) ) {
			$chunked_array = array_chunk( $this->filtered_db_results, $this->array_page_size, false );

			foreach ( $chunked_array as $key => $chunk ) {
				$samples = array();
				$labels  = array();
				foreach ( $chunk as $row ) {
					$samples[] = array( $row->nilsimsa );
					if ( 'event' === $this->model_type ) {
						$labels[] = $row->event_attribute . '#' . $row->clustername;
					} else {
						$labels[] = $row->clustername;
					}
				}
				$partial_dataset = new Labeled( $samples, $labels );
				if ( 0 === $key ) {
					$this->classifier->train( $partial_dataset );
				} else {
					$this->classifier->partial( $partial_dataset );
				}
			}
		}
	}

	/**
	 * Returns a trained estimator loading a persistable
	 *
	 * @return \Rubix\ML\Classifiers\KNearestNeighbors|false
	 */
	private function get_persistent() {
		$encoding         = $this->persister->load();
		$this->serializer = new RBX();
		$persistable      = $this->serializer->deserialize( $encoding );
		if ( $persistable instanceof \Rubix\ML\Classifiers\KNearestNeighbors ) {
			return $persistable;
		} else {
			return false;
		}
	}

	/**
	 * Return a trained classifier
	 *
	 * @param bool $try_persistent If set to false, trains a non persistent classifier.
	 * @return \Rubix\ML\Classifiers\KNearestNeighbors
	 */
	public function get_trained( $try_persistent = true ) {
		if ( true === $try_persistent && $this->can_use_persistent ) {
			if (
				! file_exists( $this->rbx_file_name ) ||
				$this->get_persister_time() < $this->get_last_update_table_time()
				) {
				$this->save_trained();
			}
			$persistable = $this->get_persistent();
			if ( false !== $persistable ) {
				return $persistable;
			}
		}
		$this->train_classifier();
		return $this->classifier;
	}

	/**
	 * Updates the classifier adding a new labeled sample.
	 *
	 * @param string $sample The new sample to add.
	 * @param string $label The new label to add.
	 * @return void
	 */
	public function update( $sample, $label ) {
		$knn             = $this->get_trained();
		$samples         = array();
		$labels          = array();
		$samples[]       = array( $sample );
		$labels[]        = $label;
		$partial_dataset = new Labeled( $samples, $labels );
		$knn->partial( $partial_dataset );
		if ( $this->can_use_persistent ) {
			$this->save_trained();
		}
	}

	/**
	 * Returns the time when the content of the persister file was changed
	 *
	 * This returns false on failure.
	 *
	 * @return int|false
	 */
	private function get_persister_time() {
		return filemtime( $this->rbx_file_name );
	}

	/**
	 * Returns the time when the db table was last updated
	 *
	 * Returns false if the option is not set.
	 *
	 * @return int|bool
	 */
	private function get_last_update_table_time() {
		$nunil_lm_opt = 'no-unsafe-inline-dbtables-mtime';
		if ( 'event' === $this->model_type ) {
			$table = 'event_handlers';
		} else {
			$table = 'inline_scripts';
		}
		$wp_option = get_option( $nunil_lm_opt );
		if ( is_array( $wp_option ) && array_key_exists( $table, $wp_option ) ) {
			return intval( $wp_option[ $table ] );
		} else {
			return false;
		}
	}

	/**
	 * Sets DB results in cache and returns DB results
	 *
	 * @param string $cache_key The cache key used to store db results.
	 * @return array<\stdClass>|null
	 */
	public static function get_db_rows( $cache_key ) {
		$gls         = new Nunil_Global_Settings();
		$tools       = (array) get_option( 'no-unsafe-inline-tools' );
		$cache_group = 'no-unsafe-inline';
		$expire_secs = $gls->expire_secs[ $cache_key ];
		/**
		 * When capturing is enabled with a protection policy enabled or in test, we need NOT to
		 * use cache to avoid not upgrading clusternames dinamically
		 */
		if ( 1 === $tools['capture_enabled'] ) {
			wp_cache_delete( $cache_key, $cache_group );
		}
		$db_rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $db_rows ) {
			$method  = 'get_' . $cache_key;
			$db_rows = DB::$method();
			wp_cache_set( $cache_key, $db_rows, $cache_group, $expire_secs );
		}
		return $db_rows;
	}
}
