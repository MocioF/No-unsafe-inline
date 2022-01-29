<?php
/**
 * Classification class
 *
 * Class used on classification of new $hashes.
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */
namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Phpml\Classification\KNearestNeighbors;

class Nunil_Classification {

	/**
	 * Get samples array
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $tagname The tagname in inline_scripts rows we want to cluster
	 * @return array{samples: array<array<int>>, labels: array<string>}>
	 */
	public function get_samples( $tagname ) {
		global $wpdb;

		$table = NO_UNSAFE_INLINE_TABLE_PREFIX . 'inline_scripts';

		$sql_select = "SELECT `nilsimsa` AS 'hexDigest', `clustername`, `whitelist` FROM $table ";
		$sql_where  = "WHERE (`whitelist` = true AND `clustername` <> 'Unclustered' ) OR `clustername` = 'Unclustered' ";

		$sql = $sql_select . $sql_where;

		$cache_key   = 'training_inline_classifier';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = 10;

		$rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $rows ) {
			$rows = $wpdb->get_results( $sql );
			wp_cache_set( $cache_key, $rows, $cache_group, $expire_secs );
		}

		$samples = array();

		$labels = array();

		foreach ( $rows as $row ) {
			$samples[] = Nunil_Clustering::convertHexDigestToArray( $row->hexDigest );
			$labels[]  = $row->clustername;
		}
		$results            = array();
		$results['samples'] = $samples;
		$results['labels']  = $labels;

		return $results;

	}

	/**
	 * Get cases for test classifier
	 *
	 * @since 1.0.0
	 * @return array<array{exp_labels: string, nilsimsa: string}>>
	 */
	public function get_cases() {
		global $wpdb;

		$table = NO_UNSAFE_INLINE_TABLE_PREFIX . 'inline_scripts';

		$sql = "SELECT `clustername` AS 'exp_label', `nilsimsa` AS 'hexDvalue' from $table WHERE `whitelist` = false and `clustername` = 'Unclustered'";

		$cache_key   = 'test_cases';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = 10;

		$rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $rows ) {
			$rows = $wpdb->get_results( $sql, ARRAY_A );
		}
		foreach ( $rows as &$row ) {
			$row = (array) $row;
		}

		return $rows;
	}

	/**
	 * Performs a test of classifier
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function test_cases() {

		$result_string = '';

		$cases = array();

		for ( $i = 0; $i < 5; $i++ ) {
			$row     = Nunil_Lib_Db::get_random_cluster_data( 'inline_scripts' );
			$cases[] = $row;
		}

		$gls = new Nunil_Global_Settings();

		$start_time        = microtime( true );
		$start_time_global = $start_time;
		$result_string     = $result_string . '<br><b> --- ' . esc_html__( 'TEST CLASSIFIER: ', 'no-unsafe-inline' ) . ' --- </b><br>';
		$result_string     = $result_string . esc_html__( 'Start time DB GET: ', 'no-unsafe-inline' ) . $start_time . '<br>';

		$database      = $this->get_samples( 'script' );
		$nums          = count( $database['samples'] );
		$end_time      = microtime( true );
		$result_string = $result_string . esc_html__( 'End time DB GET: ', 'no-unsafe-inline' ) . $end_time . '<br>';
		$result_string = $result_string . esc_html__( 'Num of hashes: ', 'no-unsafe-inline' ) . "<b>$nums</b>" . '<br>';

		$execution_time = ( $end_time - $start_time );
		$result_string  = $result_string . esc_html__( 'Execution time DB GET (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';

		$start_time    = microtime( true );
		$result_string = $result_string . esc_html__( 'Start time Training: ', 'no-unsafe-inline' ) . $start_time . '<br>';

		$classifier = new KNearestNeighbors( $k = $gls->knn_k_inl, new Nunil_Hamming_Distance() );

		$classifier->train( $database['samples'], $database['labels'] );

		$end_time      = microtime( true );
		$result_string = $result_string . esc_html__( 'End time Training: ', 'no-unsafe-inline' ) . $end_time . '<br>';

		$execution_time = ( $end_time - $start_time );
		$result_string  = $result_string . esc_html__( 'Execution time Training (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';

		$start_time    = microtime( true );
		$result_string = $result_string . esc_html__( 'Start time Classifying: ', 'no-unsafe-inline' ) . $start_time . '<br>';
		$result_string = $result_string . '<br>';

		foreach ( $cases as $case ) {
			if ( is_array( $case ) ) {
				$test = Nunil_Clustering::convertHexDigestToArray( $case['hexDvalue'] );

				$calc_label = $classifier->predict( $test );

				$result_string = $result_string . $case['hexDvalue'] . '<br>';
				$result_string = $result_string . esc_html__( 'Expected:', 'no-unsafe-inline' ) . '     ' . $case['exp_label'] . '<br>';
				$result_string = $result_string . esc_html__( 'Returned:', 'no-unsafe-inline' ) . '     ' . $calc_label . '<br>';
				$result_string = $result_string . '<br>';
			}
		}

		$end_time      = microtime( true );
		$result_string = $result_string . esc_html__( 'End time Classifying: ', 'no-unsafe-inline' ) . $end_time . '<br>';

		$end_time_global = $end_time;
		$execution_time  = ( $end_time_global - $start_time_global );
		$result_string   = $result_string . esc_html__( 'Execution time Global (sec): ', 'no-unsafe-inline' ) . $execution_time . '<br>';

		return $result_string;
	}

}





