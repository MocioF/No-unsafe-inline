<?php
/**
 * Managing installation and remotion of muplugin
 *
 * Class used to copy and delete mu-plugin
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to install and uninstall muplugin
 *
 * @package No_unsafe-inline
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
	 * @throws \Exception Error managing mu-plugin.
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
			// INSTALL.
			if ( ! wp_mkdir_p( $mu_dir ) ) {
				$result['error'] = sprintf(
					// translators: %s is the path to mu-plugin dir.
					esc_html__( 'Error! The following directory could not be created: %s.', 'no-unsafe-inline' ),
					$mu_dir
				);
				$result['status'] = 'ERROR';
				// translators: %s is the path to mu-plugin dir.
				Nunil_Lib_Log::error( sprintf( esc_html__( 'Error in installing mu-plugin! The following directory could not be created: %s.', 'no-unsafe-inline' ), $mu_dir ) );
				// translators: %s is the path to mu-plugin dir.
				throw new \Exception( sprintf( esc_html__( 'Error in installing mu-plugin! The following directory could not be created: %s.', 'no-unsafe-inline' ), $mu_dir ) );
			} if ( 'ERROR' !== $result['status'] && ! copy( $source, $dest ) ) {
				// translators: %1$s is the source directory;  %2$s is the dest directory.
				$result['error']  = sprintf( esc_html__( 'Error! Could not copy the No unsafe-inline\'s mu-plugin from %1$s to %2$s.', 'no-unsafe-inline' ), $source, $dest );
				$result['status'] = 'ERROR';
				// translators: %1$s is the source directory;  %2$s is the dest directory.
				Nunil_Lib_Log::error( sprintf( esc_html__( 'Error! Could not copy the No unsafe-inline\'s mu-plugin from %1$s to %2$s.', 'no-unsafe-inline' ), $source, $dest ) );
				// translators: %1$s is the source directory;  %2$s is the dest directory.
				throw new \Exception( sprintf( esc_html__( 'Error! Could not copy the No unsafe-inline\'s mu-plugin from %1$s to %2$s.', 'no-unsafe-inline' ), $source, $dest ) );
			}
		} elseif ( file_exists( $dest ) && ! unlink( $dest ) ) {
				// translators: %s is the path to mu-plugin dir.
				$result['error']  = sprintf( esc_html__( 'Error! Could not remove the No unsafe-inline\'s mu-plugin from %s.', 'no-unsafe-inline' ), $dest );
				$result['status'] = 'ERROR';
				// translators: %s is the path to mu-plugin dir.
				Nunil_Lib_Log::error( sprintf( esc_html__( 'Error ! Could not remove the No unsafe-inline\'s mu-plugin from %s.', 'no-unsafe-inline' ), $dest ) );
				// translators: %s is the path to mu-plugin dir.
				throw new \Exception( sprintf( esc_html__( 'Error ! Could not remove the No unsafe-inline\'s mu-plugin from %s.', 'no-unsafe-inline' ), $dest ) );
		}
	}
}
