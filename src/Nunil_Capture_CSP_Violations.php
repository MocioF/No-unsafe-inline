<?php
/**
 * Capturing csp violations
 *
 * Class used on capturing CSP violations.
 * Contains functions to populate the db tables.
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to capture CSP violations
 *
 * @package No unsafe inline
 * @since   1.0.0
 */
class Nunil_Capture_CSP_Violations extends Nunil_Capture {

	/**
	 * Capture some csp violations from report-uri and records them in db
	 *
	 * @since 1.0.0
	 * @access public
	 * @return \WP_REST_Response
	 */
	public function capture_violations( \WP_REST_Request $request ) {

		$options = get_option( 'no-unsafe-inline' );

		if ( ! is_array( $options ) ) {
			Nunil_Lib_Log::error( 'The option no-unsafe-inline has to be an array' );
			throw new \Exception( 'The option no-unsafe-inline has to be an array' );
		}

		if (
			1 === $options['font-src_enabled'] ||
			1 === $options['child-src_enabled'] ||
			1 === $options['worker-src_enabled'] ||
			1 === $options['connect-src_enabled'] ||
			1 === $options['manifest-src_enabled'] ||
			1 === $options['form-action_enabled'] ||
			1 === $options['img-src_enabled']
		) {
			$body = $request->get_body();
			$body = json_decode( $body, true );

			if ( is_array( $body ) && is_array( $body['csp-report'] ) ) {

				$csp_report = $body['csp-report'];

				$capture = new Nunil_Capture();

				if ( in_array(
					$csp_report['violated-directive'],
					array(
						'font-src',
						'child-src',
						'worker-src',
						'connect-src',
						'manifest-src',
						'form-action',
						'img-src',
					),
					true
				) ) {
					$capture->insert_external_tag_in_db(
						$csp_report['violated-directive'],
						$csp_report['violated-directive'],
						$csp_report['blocked-uri'],
						$csp_report['document-uri']
					);
				}

				if ( in_array(
					$csp_report['violated-directive'],
					array(
						'script-src',
						'style-src',
					),
					true
				) ) {
					if ( 'inline' === $csp_report['blocked-uri'] && isset( $csp_report['source-file'] ) ) {
						// ~ $this->report_log( $csp_report );

						/**
						 * Qui il problema è che script-sample è limitato a 40 chars

						$capture->put_inline_content_in_database(
							$csp_report['violated-directive'],
							substr( $csp_report['violated-directive'], 0, strpos( $csp_report['violated-directive'], '-') ),
							$csp_report['script-sample'],
							$csp_report['document-uri']
						);
						*/
					}
					if ( 'eval' === $csp_report['blocked-uri'] ) {
						$capture->insert_external_tag_in_db(
							$csp_report['violated-directive'],
							$csp_report['violated-directive'],
							'\'unsafe-eval\'',
							$csp_report['document-uri']
						);
						// ~ $capture->put_external_tag_in_database(
						// ~ $csp_report['violated-directive'], $csp_report['violated-directive'], '\'strict-dynamic\'', $csp_report['document-uri'] );
					}
				}
			}
		}
		$data     = array( 'time' => time() );
		$response = new \WP_REST_Response( $data );
		$response->set_status( 204 ); // HTTP 204 No Content.
		return $response;
	}
}
