<?php
/**
 * Admin help tabs.
 *
 * Class used to add help tabs on the screen
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.2.4
 */

namespace NUNIL;

use dekor\ArrayToTextTable;
use NUNIL\Nunil_Lib_Utils as Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to write help tabs content.
 *
 * @package No_unsafe-inline
 * @since   1.2.4
 */
class Nunil_Admin_Support_Box {

	/**
	 * Render the support box content.
	 *
	 * @return void
	 */
	public static function render() {
		?>
		<div class="nunil-support-box">
			<div class="nunil-tools-support-container">
				<div id="nunil-support-box-accordion">
					<h3><?php esc_html_e( 'Software version', 'no-unsafe-inline' ); ?></h3>
					<div>
						<table class="rwd-table nunil-software-versions-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Software', 'no-unsafe-inline' ); ?></th>
									<th><?php esc_html_e( 'Version', 'no-unsafe-inline' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Operating System', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( php_uname( 's' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Host name', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( php_uname( 'n' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Release Name', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( php_uname( 'r' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Version information', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( php_uname( 'v' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Machine Type', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( php_uname( 'm' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'Web Server', 'no-unsafe-inline' ) . '</td>';
								if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
									echo '<td data-th="Version">' . esc_html( sanitize_text_field( strval( Utils::cast_strval( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) ) ) . '</td>';
								} else {
									echo '<td data-th="Version">' . esc_html__( 'Unknown', 'no-unsafe-inline' ) . '</td>';
								}
								echo '</tr>';
								if ( isset( $_SERVER['SERVER_SIGNATURE'] ) ) {
									echo '<tr>';
										echo '<td data-th="Software">' . esc_html__( 'Server Signature', 'no-unsafe-inline' ) . '</td>';
										echo '<td data-th="Version">' . esc_html( sanitize_text_field( strval( Utils::cast_strval( wp_unslash( $_SERVER['SERVER_SIGNATURE'] ) ) ) ) ) . '</td>';
									echo '</tr>';
								}

								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'PHP Version', 'no-unsafe-inline' ) . '</td>';
								if ( phpversion() > NO_UNSAFE_INLINE_MINIMUM_PHP_VERSION ) {
									$class = 'nunil_req_ok';
								} else {
									$class = 'nunil_req_fail';
								}
									echo '<td data-th="Version" class="' . esc_attr( $class ) . '">' . esc_html( PHP_VERSION ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html__( 'libxml Version', 'no-unsafe-inline' ) . '</td>';
									echo '<td data-th="Version">' . esc_html( strval( LIBXML_VERSION ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html( 'WordPress' ) . '</td>';
								if ( get_bloginfo( 'version' ) > NO_UNSAFE_INLINE_MINIMUM_WP_VERSION ) {
									$class = 'nunil_req_ok';
								} else {
									$class = 'nunil_req_fail';
								}
									echo '<td data-th="Version" class="' . esc_attr( $class ) . '">' . esc_html( get_bloginfo( 'version' ) ) . '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td data-th="Software">' . esc_html( 'MySQL' ) . '</td>';
									global $wpdb;
									$mysql_version = $wpdb->db_version();
									echo '<td data-th="Version">' . esc_html( $mysql_version ) . '</td>';
								echo '</tr>';

								echo '<tr>';
									echo '<td data-th="Software">' . esc_html( 'No unsafe-inline' ) . '</td>';
									$latest_stable_version = self::get_latest_plugin_version();
								if ( version_compare( NO_UNSAFE_INLINE_VERSION, $latest_stable_version, '>=' ) ) {
									$class = 'nunil_req_ok';
								} else {
									$class = 'nunil_req_unknown';
								}
									echo '<td data-th="Version" class="' . esc_attr( $class ) . '">' . esc_html( NO_UNSAFE_INLINE_VERSION ) . '</td>';
								echo '</tr>';
								?>
							</tbody>
						</table>
					</div>
					<h3><?php esc_html_e( 'PHP Extensions Status', 'no-unsafe-inline' ); ?></h3>
					<div>
						<table class="rwd-table nunil-extensions-status-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Extension', 'no-unsafe-inline' ); ?></th>
									<th><?php esc_html_e( 'Version', 'no-unsafe-inline' ); ?></th>
									<th><?php esc_html_e( 'Status', 'no-unsafe-inline' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$extensions = self::get_required_extensions();
								foreach ( $extensions as $ext ) {
									switch ( $ext['status'] ) {
										case 'installed':
											$status_class = 'nunil_req_ok';
											break;
										case 'not installed':
											$status_class = 'nunil_req_fail';
											break;
										case 'not needed':
											$status_class = 'nunil_req_not_needed';
											break;
										default:
											$status_class = 'nunil_req_unknown';
											break;
									}
									echo '<tr>';
									echo '<td data-th="Extension">' . esc_html( $ext['extension'] ) . '</td>';
									echo '<td data-th="Version">' . esc_html( $ext['version'] ) . '</td>';
									echo '<td data-th="Status" class="' . esc_attr( $status_class ) . '">' . esc_html( ucfirst( $ext['status'] ) ) . '</td>';
									echo '</tr>';
								}
								?>
							</tbody>
						</table>
					</div>
					<h3><?php esc_html_e( 'Report an issue', 'no-unsafe-inline' ); ?></h3>
					<div>
						<p><?php esc_html_e( 'If you encounter any issues while using the No unsafe-inline plugin, please report them on our GitHub issue tracker:', 'no-unsafe-inline' ); ?></p>
						<p><a href="https://github.com/MocioF/No-unsafe-inline/issues/new?template=BUG-REPORT.yml" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub Issue Tracker', 'no-unsafe-inline' ); ?></a></p>
						<p><?php esc_html_e( 'When reporting an issue, please include the following system information to help us diagnose the problem:', 'no-unsafe-inline' ); ?></p>
						<div id="nunil_sys_info_box_container" class="nunil_sys_info_box_container">
							<?php
							echo '<pre id="nunil_sys_info_box" class="nunil_sys_info_box">' . esc_html( self::format_system_informations() ) . '</pre>';
							?>
							<div id="nunil_admin_support_box_buttons" class="nunil_admin_support_box_buttons" style="opacity: 1; display: none;">
								<button id="nunil_admin_support_box_button_clipboard" class="nunil-btn nunil_admin_support_box_button_clipboard" title="<?php esc_attr_e( 'Copy the report to clipboard', 'no-unsafe-inline' ); ?>" data-notification="<?php esc_attr_e( 'Copied to clipboard', 'no-unsafe-inline' ); ?>">
								<span class="dashicons dashicons-clipboard"> </span>
								</button>
							</div>
						</div>
					</div>
					<h3><?php esc_html_e( 'Get support', 'no-unsafe-inline' ); ?></h3>
					<div>
						<p><?php esc_html_e( 'For support and assistance with the No unsafe-inline plugin, please visit our support forum on WordPress.org:', 'no-unsafe-inline' ); ?></p>
						<p><a href="https://wordpress.org/support/plugin/no-unsafe-inline/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WordPress.org Support Forum', 'no-unsafe-inline' ); ?></a></p>
						<p><?php esc_html_e( 'We and community members will try to help you with any questions or issues you may have.', 'no-unsafe-inline' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the required PHP extensions and their status.
	 *
	 * @return list<array{extension: string, version: string, status: string}>
	 */
	private static function get_required_extensions() {
		require_once dirname( NO_UNSAFE_INLINE_PLUGIN ) . '/includes/class-no-unsafe-inline-activator.php';
		$required = \NUNIL\includes\No_Unsafe_Inline_Activator::get_required_extensions();
		$results  = array();
		foreach ( $required as $ext ) {
			if ( is_array( $ext ) ) {
				$alternatives      = array();
				$found_alternative = false;
				foreach ( $ext as $alt ) {
					if ( extension_loaded( $alt ) ) {
						$alternatives[]    = array(
							'extension' => $alt,
							'version'   => strval( phpversion( $alt ) ),
							'status'    => 'installed',
						);
						$found_alternative = true;
					} else {
						$alternatives[] = array(
							'extension' => $alt,
							'version'   => '-',
							'status'    => 'not installed',
						);
					}
				}
				if ( $found_alternative ) {
					foreach ( $alternatives as $alt ) {
						if ( 'not installed' === $alt['status'] ) {
							$alt['status'] = 'not needed';
						}
					}
				}
				$results = array_merge( $results, $alternatives );
			} elseif ( extension_loaded( $ext ) ) {
					$results[] = array(
						'extension' => $ext,
						'version'   => strval( phpversion( $ext ) ),
						'status'    => 'installed',
					);
			} else {
				$results[] = array(
					'extension' => $ext,
					'version'   => '-',
					'status'    => 'not installed',
				);
			}
		}
		return $results;
	}

	/**
	 * Get the latest stable version of the plugin from WordPress.org.
	 *
	 * @return string
	 */
	private static function get_latest_plugin_version() {
		$plugin_slug = 'no-unsafe-inline';

		$url      = 'https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json';
		$response = wp_safe_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return 'Error fetching plugin data: ' . $response->get_error_message();
		}
		$plugin_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( $plugin_data instanceof \stdClass && isset( $plugin_data->version ) ) {
			return $plugin_data->version;
		} else {
			return 'No version data found';
		}
	}

	/**
	 * Format system informations for issue reporting.
	 *
	 * @return string
	 */
	private static function format_system_informations() {
		$info  = '';
		$info .= '- ' . __( 'Operating System', 'no-unsafe-inline' ) . ': ' . php_uname( 's' ) . "\n";
		$info .= '- ' . __( 'Web Server', 'no-unsafe-inline' ) . ': ';
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$info .= sanitize_text_field( strval( Utils::cast_strval( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) );
		} else {
			$info .= __( 'Unknown', 'no-unsafe-inline' );
		}
		$info .= "\n";
		$info .= '- ' . __( 'WordPress Version', 'no-unsafe-inline' ) . ': ' . get_bloginfo( 'version' ) . "\n";
		$info .= '- ' . __( 'PHP Version', 'no-unsafe-inline' ) . ': ' . phpversion() . "\n";
		$info .= '- ' . __( 'No unsafe-inline Version', 'no-unsafe-inline' ) . ': ' . NO_UNSAFE_INLINE_VERSION . "\n";
		$info .= '- ' . __( 'libxml Version', 'no-unsafe-inline' ) . ': ' . LIBXML_VERSION . "\n";
		$info .= '- ' . __( 'PHP extensions', 'no-unsafe-inline' ) . ': ' . "\n";

		$table = new ArrayToTextTable( self::get_required_extensions() );
		$info .= $table->render() . "\n";
		return $info;
	}
}