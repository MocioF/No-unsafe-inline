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
	 * @param \WP_REST_Request $request The rest api request.
	 * @return \WP_REST_Response|void
	 */
	public function capture_violations( \WP_REST_Request $request ) {

		$options = (array) get_option( 'no-unsafe-inline' );

		if ( empty( $options ) ) {
			Nunil_Lib_Log::error( 'The option no-unsafe-inline has to be an array' );
			exit( 'The option no-unsafe-inline has to be an array' );
		}

		// Only continue if it's valid JSON that is not just `null`, `0`, `false` or an empty string, i.e. if it could be a CSP violation report.
		if ( $report = json_decode( $request->get_body(), true ) ) {
			if (
			1 === $options['font-src_enabled'] ||
			1 === $options['child-src_enabled'] ||
			1 === $options['worker-src_enabled'] ||
			1 === $options['connect-src_enabled'] ||
			1 === $options['manifest-src_enabled'] ||
			1 === $options['form-action_enabled'] ||
			1 === $options['img-src_enabled']
			) {
				$csp_violation = $this->get_csp_report_body( $report );

				if ( $csp_violation ) {

					$capture = new Nunil_Capture();

					// effective-directory has had a delaied (and inconsistent) implementation.
					// In CSP3 violated-directive = effective-directive and it is more does not contain violated policy rules.
					if ( array_key_exists( 'effective-directive', $csp_violation ) ) {
						$violated_directive = $csp_violation['effective-directive'];
					} elseif ( array_key_exists( 'effectiveDirective', $csp_violation ) ) {
						$violated_directive = $csp_violation['effectiveDirective'];
					} elseif ( array_key_exists( 'violated-directive', $csp_violation ) ) {
						$violated_directive = explode( ' ', $csp_violation['violated-directive'] )[0];
					} elseif ( array_key_exists( 'violatedDirective', $csp_violation ) ) {
						$violated_directive = explode( ' ', $csp_violation['violatedDirective'] )[0];
					} else {
						$violated_directive = '';
					}

					// Based on CSP2 this should be empty for inline but
					// read https://csplite.com/csp66/#blocked-uri for other values.
					$blocked_url = $csp_violation['blocked-uri'] ? $csp_violation['blocked-uri'] : $csp_violation['blockedURL'];

					$document_uri = $csp_violation['document-uri'] ? $csp_violation['document-uri'] : $csp_violation['documentURL'];

					// to be checked if needed.
					// https://csplite.com/csp66/#sample-violation-report .
					$source_file = $csp_violation['source-file'] ? $csp_violation['source-file'] : $csp_violation['sourceFile'];

					if ( in_array(
						$violated_directive,
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
							$violated_directive,
							'v-capt',
							strval( $blocked_url ),
							strval( $document_uri )
						);
					}

					if ( in_array(
						$violated_directive,
						array(
							'script-src',
							'style-src',
						),
						true
					) ) {
						error_log( 'BLOCKED URL:' . $blocked_url );
						if ( 'inline' === $blocked_url && isset( $source_file ) ) {
							error_log( print_r( $csp_violation, true ) );
							// ~ $this->report_log( $csp_report );
							// ~
							// ~ Qui il problema è che script-sample è limitato a 40 chars
								// ~ $capture->put_inline_content_in_database(
								// ~ $csp_report['violated-directive'],
								// ~ substr( $csp_report['violated-directive'], 0, strpos( $csp_report['violated-directive'], '-') ),
								// ~ $csp_report['script-sample'],
								// ~ $csp_report['document-uri']
							// ~ );
							// ~
						}
						if ( 'eval' === $blocked_url || 'empty' === $blocked_url ) {
							$capture->insert_external_tag_in_db(
								$violated_directive,
								'v-capt', // tag.
								'\'unsafe-eval\'',
								strval( $document_uri )
							);
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

	/**
	 * Get CSP violation body
	 *
	 * We set both report-to and report-uri for browser compatibility.
	 * Here we detect the type of report and return report body if it is
	 * a real csp violation report.
	 *
	 * @since 1.0.0
	 * @param mixed $report The report decoded.
	 * @return array<mixed>|false
	 */
	private function get_csp_report_body( $report ) {
		if ( is_array( $report ) && isset( $report['csp-report'] ) ) {
			return $report['csp-report'];
		} elseif ( is_array( $report ) && isset( $report[0]['type'] ) && 'csp-violation' === $report[0]['type'] ) {
			return $report[0]['body'];
		} else {
			return false;
		}
	}
}
