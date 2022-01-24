<?php
/**
 * Managing installation and remotion of muplugin
 *
 * Class used to copy and delete mu-plugin
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

/**
 * Class with methods used to install and uninstall muplugin
 *
 * @package no-unsafe-inline
 * @since   1.0.0
 */
class Nunil_Manage_Muplugin {

	/**
	 * Checks if nunil mu-plugin is installed.
	 *
	 * @since 1.0.0
	 * @return bool Returns true if the mu-plugin is in the mu-plugin dir; false otherwise.
	 */
	public static function is_nunil_muplugin_installed() {
		$mu_dir    = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
		$mu_dir    = untrailingslashit( $mu_dir );
		$mu_plugin = $mu_dir . '/no-unsafe-inline-output-buffering.php';
		return file_exists( $mu_plugin );
	}

	/**
	 * Toggles muplugin installation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function toggle_nunil_muplugin_installation() {
		$mu_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
		$mu_dir = untrailingslashit( $mu_dir );
		$source = NO_UNSAFE_INLINE_PLUGIN_DIR . '/mu-plugins/no-unsafe-inline-output-buffering.php';
		$dest   = $mu_dir . '/no-unsafe-inline-output-buffering.php';

		$result = array(
			'status' => 'OK',
			'error'  => '',
		);
		if ( ! self::is_nunil_muplugin_installed() ) {
			// INSTALL
			if ( ! wp_mkdir_p( $mu_dir ) ) {
				$result['error']  = sprintf(
					__( 'Error! The following directory could not be created: %s.', 'no-unsafe-inline' ),
					$mu_dir
				);
				$result['status'] = 'ERROR';
				Nunil_Lib_Log::error( sprintf( __( 'Error in installing mu-plugin! The following directory could not be created: %s.', 'no-unsafe-inline' ), $mu_dir ) );
				throw new \Exception( sprintf( __( 'Error in installing mu-plugin! The following directory could not be created: %s.', 'no-unsafe-inline' ), $mu_dir ) );
			} if ( $result['status'] !== 'ERROR' && ! copy( $source, $dest ) ) {
				$result['error']  = sprintf( __( 'Error! Could not copy Nunil MU - Plugin from % 1$s to % 2$s . ', 'no-unsafe-inline' ), $source, $dest );
				$result['status'] = 'ERROR';
				Nunil_Lib_Log::error( sprintf( __( 'Error! Could not copy Nunil MU - Plugin from % 1$s to % 2$s . ', 'no-unsafe-inline' ), $source, $dest ) );
				throw new \Exception( sprintf( __( 'Error! Could not copy Nunil MU - Plugin from % 1$s to % 2$s . ', 'no-unsafe-inline' ), $source, $dest ) );
			}
		} else {
			if ( file_exists( $dest ) && ! unlink( $dest ) ) {
				$result['error']  = sprintf( __( 'Error ! Could not remove the Nunil MU - Plugin from % s . ', 'no-unsafe-inline' ), $dest );
				$result['status'] = 'ERROR';
				Nunil_Lib_Log::error( sprintf( __( 'Error ! Could not remove the Nunil MU - Plugin from % s . ', 'no-unsafe-inline' ), $dest ) );
				throw new \Exception( sprintf( __( 'Error ! Could not remove the Nunil MU - Plugin from % s . ', 'no-unsafe-inline' ), $dest ) );
			}
		}
		// ~ header( 'Content - Type: application / json' );
		// ~ echo json_encode( $result );
		// ~ die();
	}
}
