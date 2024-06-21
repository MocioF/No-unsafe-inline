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

use NUNIL\Nunil_Knn_Trainer;
use NUNIL\Nunil_Lib_Utils;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\CrossValidation\Reports\AggregateReport;
use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;
use Rubix\ML\CrossValidation\Reports\MulticlassBreakdown;

/**
 * Classification class
 *
 * Class used when testing classificator.
 */
class Nunil_Classification {

	/**
	 * Performs advanced tests of classifiers
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function test_cases() {
		$gls = new Nunil_Global_Settings();
		set_time_limit( $gls->test_classifier_time_limit );

		$time_start_global = microtime( true );
		$result_string     = '<p><b> --- ' . esc_html__( 'TEST CLASSIFIER: ', 'no-unsafe-inline' ) . ' --- </b><p>' . PHP_EOL;
		$model_types       = array(
			'script',
			'style',
			'event',
		);
		foreach ( $model_types as $model ) {
			$result_string .= '<p>-- ' . sprintf(
					// translators: %s is the internal name of the classifier.
				esc_html__( 'Reports on %s classifier', 'no-unsafe-inline' ),
				'<b>' . $model . '</b>'
			)
					. ' --</p>' . PHP_EOL;

			$time_start = microtime( true );

			$classifier = new Nunil_Knn_Trainer( $model );
			$estimator  = $classifier->get_trained();

			$time_got_trained = ( microtime( true ) - $time_start );

			$samples = array();
			$labels  = array();
			switch ( $model ) {
				case 'script':
					$cache_key = 'inline_rows';
					$rows      = Nunil_Knn_Trainer::get_db_rows( $cache_key );
					if ( is_array( $rows ) ) {
						foreach ( $rows as $row ) {
							if ( 'script' === $row->tagname ) {
								$samples[] = array( $row->nilsimsa );
								$labels[]  = $row->clustername;
							}
						}
					}
					break;
				case 'style':
					$cache_key = 'inline_rows';
					$rows      = Nunil_Knn_Trainer::get_db_rows( $cache_key );
					if ( is_array( $rows ) ) {
						foreach ( $rows as $row ) {
							if ( 'style' === $row->tagname ) {
								$samples[] = array( $row->nilsimsa );
								$labels[]  = $row->clustername;
							}
						}
					}
					break;
				case 'event':
					$cache_key = 'events_rows';
					$rows      = Nunil_Knn_Trainer::get_db_rows( $cache_key );
					if ( is_array( $rows ) ) {
						foreach ( $rows as $row ) {
							$samples[] = array( $row->nilsimsa );
							$labels[]  = $row->event_attribute . '#' . $row->clustername;
						}
					}
					break;
			}
			$dataset = Labeled::build( $samples, $labels )->randomize()->take( 10000 );

			$time_dataset_build = ( microtime( true ) - $time_got_trained - $time_start );

			try {
				$predictions = $estimator->predict( $dataset );

				$time_predictions = ( microtime( true ) - $time_dataset_build - $time_got_trained - $time_start );

				$report = new AggregateReport(
					array(
						new MulticlassBreakdown(),
						new ConfusionMatrix(),
					)
				);

				$dataset_labels = $dataset->labels();
				if ( Nunil_Lib_Utils::is_one_dimensional_string_array( $dataset_labels ) ) {
					$result_string .= '<pre><code>';
					$result_string .= $report->generate( $predictions, $dataset_labels );
					$result_string .= '</code></pre>';
				}
				$time_generate_report = ( microtime( true ) - $time_predictions - $time_dataset_build - $time_got_trained - $time_start );

				$time_end = microtime( true );

				$time_execution = ( $time_end - $time_start );

				$result_string .= '<p><b>' . esc_html__( 'Execution time (sec): ', 'no-unsafe-inline' ) . $time_execution . '</b><br>' . PHP_EOL;
				$result_string .= esc_html__( 'Sec. to got trained: ', 'no-unsafe-inline' ) . $time_got_trained . '<br>' . PHP_EOL;
				$result_string .= esc_html__( 'Sec. to build dataset: ', 'no-unsafe-inline' ) . $time_dataset_build . '<br>' . PHP_EOL;
				$result_string .= esc_html__( 'Sec. to make predictions: ', 'no-unsafe-inline' ) . $time_predictions . '<br>' . PHP_EOL;
				$result_string .= esc_html__( 'Sec. to generate report: ', 'no-unsafe-inline' ) . $time_generate_report . '<br>' . PHP_EOL;
				$result_string .= '-- --</p>' . PHP_EOL;
			} catch ( \Rubix\ML\Exceptions\RuntimeException $rex ) {
				$result_string .= '<p><b>' . esc_html__( 'RubixML Runtime exception', 'no-unsafe-inline' ) . ':</b> ' . $rex->getMessage() . ' </p>' . PHP_EOL;
				$result_string .= '<p>-- --</p>' . PHP_EOL;
			}
		}
		$time_end_global       = microtime( true );
		$execution_time_global = ( $time_end_global - $time_start_global );
		$result_string        .= '<p><b> --- ' . esc_html__( 'Execution time Global (sec): ', 'no-unsafe-inline' ) . $execution_time_global . ' --- </b></p><br>' . PHP_EOL;

		return $result_string;
	}
}
