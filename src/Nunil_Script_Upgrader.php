<?php
/**
 * Upgrade external script and styles when a plugin is upgraded
 *
 * This class is used to upgrade scripts and style in external_scripts table
 * when a plugin or a theme is upgraded
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.1.5
 */

namespace NUNIL;

use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;

/**
 * Class with methods used to upgrade external_scripts entries on plugin/theme/wp upgrade
 *
 * @package No_unsafe-inline
 * @since   1.1.5
 */
class Nunil_Script_Upgrader {

	/**
	 * The transient name
	 *
	 * @var string The name of the transient used on wp plugin/themes update.
	 */
	private static $transient_name = 'nunil_upgrading_assets';

	/**
	 * Upgrades external_scripts entries after upgrade.
	 *
	 * This function is hooked on upgrader_process_complete.
	 * The hook is caller when WordPress completes its upgrade process.
	 *
	 * @since 1.1.5
	 * @access public
	 * @param \WP_Upgrader|\Theme_Upgrader|\Plugin_Upgrader                                                              $upgrader_object WP_Upgrader instance.
	 * @param array{'action': string, 'type': string, 'bulk': bool, 'plugins'?: array<string>, 'themes'?: array<string>} $options Array of bulk item update data.
	 * @return void
	 */
	public static function update_external_script_entries( $upgrader_object, $options ): void {
		$transient_value = get_transient( self::$transient_name );
		if ( false === $transient_value ) {
			return;
		}
		if ( 'update' === $options['action'] ) {
			if ( $upgrader_object instanceof \Theme_Upgrader &&
				array_key_exists( 'themes', $options ) &&
				'theme' === $options['type']
				) {
				foreach ( $options['themes'] as $theme ) {
					$info = $upgrader_object->theme_info( $theme );
					if ( false !== $info ) {
						$newver = $info->get( 'Version' );
						$oldver = self::get_old_asset_version( $options['type'], $theme );
						self::update_wp_asset_scripts( $options['type'], $theme, $oldver, $newver );
					}
				}
			}
			if ( $upgrader_object instanceof \Plugin_Upgrader &&
				array_key_exists( 'plugins', $options ) &&
				'plugin' === $options['type']
				) {
				foreach ( $options['plugins'] as $plugin ) {
					$data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
					$newver = $data['Version'];
					$slug   = dirname( $plugin );
					$oldver = self::get_old_asset_version( $options['type'], $slug );
					self::update_wp_asset_scripts( $options['type'], $slug, $oldver, $newver );
				}
			}
			if ( 'core' === $options['type'] ) {
				require ABSPATH . '/wp-includes/version.php';
				$newver = isset( $wp_version ) ? $wp_version : '';
				$slug   = '';
				$oldver = self::get_old_asset_version( $options['type'], $slug );
				self::update_wp_asset_scripts( $options['type'], $slug, $oldver, $newver );
			}
		}
		delete_transient( self::$transient_name );
	}

	/**
	 * Sets/Updates a transient with data of the upgraded plugin/theme
	 *
	 * This function is hooked on upgrader_pre_install hook.
	 *
	 * @since 1.1.5
	 * @access public
	 * @param  bool|\WP_Error                                                                                                 $response Installation response.
	 * @param array{'plugin'?: string, 'theme'?: string, 'temp_backup': array{'slug': string, 'src': string, 'dir': string}} $hook_extra Array of bulk item update data.
	 * @return void
	 */
	public static function set_info_from_upgrader_pre_install( $response, $hook_extra ) {
		if ( is_wp_error( $response ) ) {
			return;
		}

		$transient_value = get_transient( self::$transient_name );

		$type   = '';
		$slug   = '';
		$oldver = '';

		if ( false === $transient_value ) {
			$transient_value = array();
		}
		if ( array_key_exists( 'plugin', $hook_extra ) ) {
			$type   = 'plugin';
			$slug   = $hook_extra['temp_backup']['slug'];
			$data   = get_plugin_data( WP_PLUGIN_DIR . '/' . $hook_extra['plugin'] );
			$oldver = $data['Version'];
		}

		if ( array_key_exists( 'theme', $hook_extra ) ) {
			$type = 'theme';
			$slug = $hook_extra['temp_backup']['slug'];

			$theme  = wp_get_theme( $slug );
			$oldver = $theme->get( 'Version' );
		}

		if ( 'plugin' === $type || 'theme' === $type ) {
			$row = array(
				'type'   => $type,
				'slug'   => $slug,
				'oldver' => $oldver,
			);
			if ( is_array( $transient_value ) ) {
				$transient_value[] = $row;
				set_transient( self::$transient_name, $transient_value, 0 );
			}
		}
	}

	/**
	 * Sets/Updates a transient with data on core upgrade
	 *
	 * This function is hooked on update_feedback hook.
	 *
	 * @since 1.1.5
	 * @access public
	 * @return void
	 */
	public static function set_info_from_core_upgrader(): void {
		global $wp_version;
		$transient_value = get_transient( self::$transient_name );

		if ( ! is_array( $transient_value ) ) {
			$transient_value = array();
		}
		foreach ( $transient_value as $row ) {
			if ( 'core' === $row['type'] ) {
				return;
			}
		}
		$row               = array(
			'type'   => 'core',
			'slug'   => '',
			'oldver' => $wp_version,
		);
		$transient_value[] = $row;
		set_transient( self::$transient_name, $transient_value, 0 );
	}

	/**
	 * Retrieves the old version value from the transient
	 *
	 * @param string $type The type of the wp asset.
	 * @param string $slug The slug of the plugin or theme.
	 * @return string
	 */
	private static function get_old_asset_version( $type, $slug ) {
		$transient_value = get_transient( self::$transient_name );
		if ( is_array( $transient_value ) ) {
			foreach ( $transient_value as $row ) {
				if ( $type === $row['type'] &&
					$slug === $row['slug']
				) {
					return $row['oldver'];
				}
			}
		}
		return '';
	}

	/**
	 * Gets an array of scripts to be upgraded
	 *
	 * @param string $slug The slug of the upgraded plugin or theme.
	 * @return array<object{'ID': string, 'directive': string, 'tagname': string, 'src_attrib': string}>|null
	 */
	private static function get_external_scripts_by_slug( $slug = '' ) {
		if ( '' !== $slug ) {
			$ids = DB::get_external_with_slug( $slug );
		} else {
			$ids1 = DB::get_external_with_slug( 'wp-admin' );
			$ids2 = DB::get_external_with_slug( 'wp-includes' );
			$ids  = array();
			if ( is_array( $ids1 ) ) {
				array_push( $ids, ...$ids1 );
			}
			if ( is_array( $ids2 ) ) {
				array_push( $ids, ...$ids2 );
			}
		}
		if ( ! is_null( $ids ) && 0 < count( $ids ) ) {
			return $ids;
		}
		return null;
	}

	/**
	 * Updates the src_attrib of external script and rehashes the new ones.
	 *
	 * @param string $type The type of wp asset (theme or plugin).
	 * @param string $slug The slug.
	 * @param string $oldver The old version of the wp asset.
	 * @param string $newver The new version of the wp_asset.
	 * @return void
	 */
	private static function update_wp_asset_scripts( $type, $slug, $oldver, $newver ) {
		$msg_upd = sprintf(
			// translators: %1$s is the type of wp asset (plugin, theme), %2$s is the slug of the wp asset, %3$s is the old version, %4$s is the new version.
			esc_html__(
				'The following script has been updated while upgrading %1$s %2$s from version %3$s to version %4$s: ',
				'no-unsafe-inline'
			),
			$type,
			$slug,
			$oldver,
			$newver
		);
		$msg_del = sprintf(
			// translators: %1$s is the type of wp asset (plugin, theme), %2$s is the slug of the wp asset, %3$s is the old version, %4$s is the new version.
			esc_html__(
				'The following script has been deleted while upgrading %1$s %2$s from version %3$s to version %4$s: ',
				'no-unsafe-inline'
			),
			$type,
			$slug,
			$oldver,
			$newver
		);

		$scripts = self::get_external_scripts_by_slug( $slug );
		if ( is_array( $scripts ) ) {
			foreach ( $scripts as $script ) {
				$print_src     = ( strlen( $script->src_attrib ) > 200 ) ? '<br>...' . substr( $script->src_attrib, -200 ) : $script->src_attrib;
				$affected_rows = self::update_src_attrib( $script, $oldver, $newver );
				if ( false !== $affected_rows ) {
					$sri = new \NUNIL\Nunil_SRI();
					if ( Utils::is_resource_hash_needed( $script->directive, $script->tagname ) ) {
						try {
							$rehashed = $sri->put_hashes_in_db( $script->ID, $overwrite = true );
							if ( false !== $rehashed ) {
								Log::info( $msg_upd . PHP_EOL . $print_src );
							} else {
								DB::ext_delete( $script->ID, true );
								Log::info( $msg_del . PHP_EOL . $print_src );
							}
						} catch ( Nunil_Exception $ex ) {
							$ex->logexception();
						}
					} else {
						$response = $sri->fetch_resource( $script->src_attrib );
						if ( is_wp_error( $response ) ) {
							DB::ext_delete( $script->ID, true );
							Log::info( $msg_del . PHP_EOL . $print_src );
						}
					}
				}
			}
		}
	}

	/**
	 * Updates the src_attrib of an external script.
	 *
	 * The new src_attrib is the old one with the string versionreplaced with
	 * the new one.
	 *
	 * @param object{'ID': string, 'src_attrib': string} $script A stdClass obj with ID and src_attrib properties.
	 * @param string                                     $oldver The old version of the wp asset.
	 * @param string                                     $newver The new version of the wp_asset.
	 * @return int|bool
	 */
	private static function update_src_attrib( $script, $oldver, $newver ) {
		$src_attrib     = $script->src_attrib;
		$new_src_attrib = str_replace( $oldver, $newver, $src_attrib );
		return DB::update_src_attrib( $script->ID, $new_src_attrib );
	}
}
