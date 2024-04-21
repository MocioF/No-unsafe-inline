<?php
/**
 * Classification class
 *
 * Class used when testing classificator.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use NUNIL\Nunil_Lib_Db as DB;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;

/**
 * Classification class
 *
 * Class used when testing classificator.
 */
class Nunil_Classification {

	/**
	 * Get samples array
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $tagname The tagname in inline_scripts rows we want to cluster.
	 * @return \Rubix\ML\Datasets\Labeled|false
	 */
	public function get_samples( $tagname = '' ) {
		$cache_key   = 'training_inline_classifier';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = 10;

		$rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $rows ) {
			$rows = DB::get_classification_samples( $tagname );
			wp_cache_set( $cache_key, $rows, $cache_group, $expire_secs );
		}

		$samples = array();

		$labels = array();
		if ( is_array( $rows ) && count( $rows ) > 0 ) {
			foreach ( $rows as $row ) {
				$samples[] = $row->hexDigest;
				$labels[]  = $row->clustername;
			}
			$dataset = new Labeled( $samples, $labels );
		} else {
			$dataset = false;
		}

		return $dataset;
	}

	/**
	 * Performs a test of classifier
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function test_cases() {
		$result_string = '';

		$num_tests = 5;

		$cases = array();

		for ( $i = 0; $i < $num_tests; $i++ ) {
			$row     = DB::get_random_cluster_data( 'inline_scripts' );
			$cases[] = $row;
		}

		$gls = new Nunil_Global_Settings();

		$start_time        = microtime( true );
		$start_time_global = $start_time;
		$result_string     = $result_string . '<br><b> --- ' . esc_html__( 'TEST CLASSIFIER: ', 'no-unsafe-inline' ) . ' --- </b><br>';
		$result_string     = $result_string . esc_html__( 'Start time DB GET: ', 'no-unsafe-inline' ) . $start_time . '<br>';

		$suitable_tags = array( 'script', 'style' );
		$rd_key        = array_rand( $suitable_tags, 1 );

		$table_dataset = $this->get_samples( $suitable_tags[ $rd_key ] );
		if ( false !== $table_dataset ) {
			$nums          = $table_dataset->numSamples();
			$end_time      = microtime( true );
			$result_string = $result_string . esc_html__( 'End time DB GET: ', 'no-unsafe-inline' ) . $end_time . '<br>';
			$result_string = $result_string . esc_html__( 'Tag: ', 'no-unsafe-inline' ) . "<b>$suitable_tags[$rd_key]</b>" . '<br>';
			$result_string = $result_string . esc_html__( 'Num of hashes: ', 'no-unsafe-inline' ) . "<b>$nums</b>" . '<br>';

			$execution_time = ( $end_time - $start_time );
			$result_string  = $result_string . esc_html__( 'Execution time DB GET (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';

			$start_time    = microtime( true );
			$result_string = $result_string . esc_html__( 'Start time Training: ', 'no-unsafe-inline' ) . $start_time . '<br>';

			$classifier = new KNearestNeighbors( $gls->knn_k_inl, true, new Nunil_Hamming_Distance() );
			$classifier->train( $table_dataset );

			$end_time      = microtime( true );
			$result_string = $result_string . esc_html__( 'End time Training: ', 'no-unsafe-inline' ) . $end_time . '<br>';

			$execution_time = ( $end_time - $start_time );
			$result_string  = $result_string . esc_html__( 'Execution time Training (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';

			$start_time    = microtime( true );
			$result_string = $result_string . esc_html__( 'Start time Classifying: ', 'no-unsafe-inline' ) . $start_time . '<br>';
			$result_string = $result_string . '<br>';

			$samples = array();
			foreach ( $cases as $case ) {
				if ( is_array( $case ) ) {
					$samples[] = $case['hexDvalue'];
				}
			}
			$dataset     = new Unlabeled( $samples );
			$calc_labels = $classifier->predict( $dataset );

			for ( $i = 0; $i < $num_tests; $i++ ) {
				if ( is_array( $cases[ $i ] ) ) {
					$result_string = $result_string . $cases[ $i ]['hexDvalue'] . '<br>';
					$result_string = $result_string . esc_html__( 'Expected:', 'no-unsafe-inline' ) . '     ' . $cases[ $i ]['exp_label'] . '<br>';
					$result_string = $result_string . esc_html__( 'Returned:', 'no-unsafe-inline' ) . '     ' . $calc_labels[ $i ] . '<br>';
					$result_string = $result_string . '<br>';
				}
			}
			$end_time      = microtime( true );
			$result_string = $result_string . esc_html__( 'End time Classifying: ', 'no-unsafe-inline' ) . $end_time . '<br>';
		} else {
			$end_time      = microtime( true );
			$result_string = $result_string . esc_html__( 'Table is empty. No test performed.', 'no-unsafe-inline' ) . '<br>';
		}
		$end_time_global = $end_time;
		$execution_time  = ( $end_time_global - $start_time_global );
		$result_string   = $result_string . esc_html__( 'Execution time Global (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';
		return $result_string;
	}
}
