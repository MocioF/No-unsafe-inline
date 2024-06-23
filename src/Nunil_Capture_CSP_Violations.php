<?php
/**
 * Capturing csp violations
 *
 * Class used on capturing CSP violations.
 * Contains functions to populate the db tables.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use NUNIL\Nunil_Lib_Log as Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to capture CSP violations
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Capture_CSP_Violations extends Nunil_Capture {

	/**
	 * Capture some csp violations from report-uri and records them in db
	 *
	 * @since 1.0.0
	 * @access public
	 * @template T of array
	 * @param \WP_REST_Request<T> $request The rest api request.
	 * @return \WP_REST_Response|void
	 */
	public function capture_violations( $request ) {
		$options = (array) get_option( 'no-unsafe-inline' );

		if ( empty( $options ) ) {
			$message = __( 'The option no-unsafe-inline has to be an array', 'no-unsafe-inline' );
			Log::error( $message );
			exit( esc_html( $message ) );
		}

		// Only continue if it's valid JSON that is not just `null`, `0`, `false` or an empty string, i.e. if it could be a CSP violation report.
		$report = json_decode( $request->get_body(), true );
		if ( ! is_null( $report ) ) {
			if (
			1 === $options['font-src_enabled'] ||
			1 === $options['child-src_enabled'] ||
			1 === $options['worker-src_enabled'] ||
			1 === $options['connect-src_enabled'] ||
			1 === $options['frame-src_enabled'] ||
			1 === $options['manifest-src_enabled'] ||
			1 === $options['form-action_enabled'] ||
			1 === $options['img-src_enabled'] ||
			1 === $options['script-src_enabled'] ||
			1 === $options['style-src_enabled']
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
					$blocked_url = array_key_exists( 'blocked-uri', $csp_violation ) ? $csp_violation['blocked-uri'] : ( array_key_exists( 'blockedURL', $csp_violation ) ? $csp_violation['blockedURL'] : '' );

					$document_uri = array_key_exists( 'document-uri', $csp_violation ) ? $csp_violation['document-uri'] : ( array_key_exists( 'documentURL', $csp_violation ) ? $csp_violation['documentURL'] : '' );

					// to be checked if needed.
					// https://csplite.com/csp66/#sample-violation-report .
					$source_file = array_key_exists( 'source-file', $csp_violation ) ? $csp_violation['source-file'] : ( array_key_exists( 'sourceFile', $csp_violation ) ? $csp_violation['sourceFile'] : '' );

					if ( in_array(
						$violated_directive,
						array(
							'font-src',
							'child-src',
							'worker-src',
							'connect-src',
							'frame-src',
							'manifest-src',
							'form-action',
							'img-src',
						),
						true
					) ) {
						if ( 'blob' === $document_uri ) {
							$capture->insert_external_tag_in_db(
								$violated_directive,
								'v-capt', // tag.
								'blob:',
								strval( $document_uri )
							);
						} else {
							$capture->insert_external_tag_in_db(
								$violated_directive,
								'v-capt',
								strval( $blocked_url ),
								strval( $document_uri )
							);
						}
					}

					if ( in_array(
						$violated_directive,
						array(
							'script-src',
							'style-src',
							'script-src-elem',
							'style-src-attr',
							'style-src-elem',
						),
						true
					) ) {
						if ( 'inline' === $blocked_url && isset( $source_file ) && '' !== $source_file ) {
							if ( isset( $csp_violation['column-number'] ) ) {
								$column_number = $csp_violation['column-number'];
							} elseif ( isset( $csp_violation['columnNumber'] ) ) {
								$column_number = $csp_violation['columnNumber'];
							} else {
								$column_number = '';
							}

							if ( isset( $csp_violation['line-number'] ) ) {
								$line_number = $csp_violation['line-number'];
							} elseif ( isset( $csp_violation['lineNumber'] ) ) {
								$line_number = $csp_violation['lineNumber'];
							} else {
								$line_number = '';
							}

							$partial = 'document-uri: ' . $document_uri;
							if ( '' !== $column_number ) {
								$partial .= ' column-number: ' . $column_number . ' ';
							}
							if ( '' !== $line_number ) {
								$partial .= ' line-number: ' . $line_number;
							}

							Log::warning(
								sprintf(
									// translators: %s is a string with document-uri, column-number and line number.
									esc_html__( 'CSP blocked inline script while capture is enabled: %s', 'no-unsafe-inline' ),
									$partial
								)
							);

							$sample = '';
							if ( isset( $csp_violation['sample'] ) ) {
								$sample = $csp_violation['sample'];
							}
							if ( isset( $csp_violation['script-sample'] ) ) {
								$sample = $csp_violation['script-sample'];
							}
							$sample_len = strlen( $sample );

							switch ( $violated_directive ) {
								case 'style-src-attr':
									$vdm = 'style-src';
									break;
								case 'style-src-elem':
									$vdm = 'style-src';
									break;
								case 'script-src-elem':
									$vdm = 'script-src';
									break;
								case 'script-src-attrib':
									$vdm = 'script-src';
									break;
								default:
									$vdm = $violated_directive;
							}
							// Solo se il sample è completo.
							if ( $sample_len > 0 && $sample_len < 40 ) {
								if ( 'script-src-attr' !== $violated_directive ) {
									$capture->insert_inline_content_in_db(
										$vdm,
										'v-capt',
										$sample,
										false,
										strval( $document_uri )
									);
								} // else this is an event handler
							}
							if ( 'blob' === $document_uri ) {
								$capture->insert_external_tag_in_db(
									$vdm,
									'v-capt', // tag.
									'blob:',
									strval( $document_uri )
								);
							}
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
