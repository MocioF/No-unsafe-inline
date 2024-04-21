<?php
/**
 * Prune database class
 *
 * Class used to periodically prune the database.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;
use League\Uri\Components\Query;
use League\Uri\Uri;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to create base -src rules for external content
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Prune_Db {

	/**
	 * Max clusters' size
	 *
	 * @var int Maximum number of entries in cluster.
	 */
	private static $cluster_limit = 150;

	/**
	 * Max occurence kept days
	 *
	 * @var int Maximum number of days spent since the last time an occurrence has been seen to be kept.
	 */
	private static $max_occ_days = 180;

	/**
	 * Delete orphans in _nunil_occurences table
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function delete_orphan_occurences() {
		$end_message            = '<b>' . esc_html__( 'Starting deleting orphans in occurences table', 'no-unsafe-inline' ) . '</b><br>';
		$internale_tables_names = array( 'inline_scripts', 'external_scripts', 'event_handlers' );
		foreach ( $internale_tables_names as $table_name ) {
			$total = 0;
			$ids   = DB::get_orpaned_occurences( $table_name );
			if ( ! is_null( $ids ) ) {
				foreach ( $ids as $row ) {
					$del = DB::delete_occurence( $row->occ_id );
					if ( false !== $del ) {
						$total = $total + $del;
					}
				}
			}
			$message = sprintf(
				// translators: %1$d is the number of lines deleted from occurences table; %2$s is the internal table name.
				esc_html__( 'Deleted %1$d orphaned lines from occurences table linked to deleted entries in %2$s', 'no-unsafe-inline' ),
				$total,
				$table_name
			);
			$end_message = $end_message . $message . '<br>';
			Log::info( $message );
		}
		$end_message = $end_message . '<br>';
		if ( $total > 0 ) {
			Utils::set_last_modified( 'occurences' );
		}
		return $end_message;
	}

	/**
	 * Pruning big clusters in inline_scripts and event_handlers table.
	 *
	 * This function will delete old scripts in big clusters,
	 * leaving at max self::cluster_limit scripts in each cluster
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function prune_big_clusters() {
		$end_message           = '<b>' . esc_html__( 'Starting pruning big clusters', 'no-unsafe-inline' ) . '</b><br>';
		$internal_tables_names = array( 'inline_scripts', 'event_handlers' );
		foreach ( $internal_tables_names as $table_name ) {
			// translators: %s is the internal tablename.
			$end_message  = $end_message . sprintf( esc_html__( 'Pruning big clusters in %s', 'no-unsafe-inline' ), $table_name ) . '<br>';
			$big_clusters = DB::get_big_clusters( $table_name, self::$cluster_limit );
			if ( ! is_null( $big_clusters ) && 0 < count( $big_clusters ) ) {
				foreach ( $big_clusters as $cluster ) {
					$n_del = 0;
					$ids   = DB::get_oldest_scripts_id( $table_name, $cluster->clustername, self::$cluster_limit );
					if ( ! is_null( $ids ) ) {
						switch ( $table_name ) {
							case 'inline_scripts':
								foreach ( $ids as $id ) {
									$n_del = $n_del + DB::inl_single_delete( $id->ID );
								}
								$message = sprintf(
									// translators: %1$d is the number of scripts deleted; %2$s is the clustername; %3$s is the internal table name.
									esc_html__( 'Deleted %1$d scripts from cluster %2$s in %3$s table', 'no-unsafe-inline' ),
									$n_del,
									$cluster->clustername,
									$table_name
								);
								$end_message = $end_message . $message . '<br>';
								Log::info( $message );
								break;
							case 'event_handlers':
								foreach ( $ids as $id ) {
									$n_del = $n_del + DB::evh_single_delete( $id->ID );
								}
								$message = sprintf(
									// translators: %1$d is the number of scripts deleted; %2$s is the clustername; %3$s is the internal table name.
									esc_html__( 'Deleted %1$d scripts from cluster %2$s in %3$s table', 'no-unsafe-inline' ),
									$n_del,
									$cluster->clustername,
									$table_name
								);
								$end_message = $end_message . $message . '<br>';
								Log::info( $message );
								break;
							default:
								break;
						}
					}
					// translators: %s is the clustername.
					$end_message = $end_message . sprintf( esc_html__( 'Pruned cluster %s', 'no-unsafe-inline' ), $cluster->clustername ) . '<br>';
				}
				Utils::set_last_modified( $table_name );
			}
		}
		$end_message = $end_message . '<br>';
		return $end_message;
	}

	/**
	 * Removes legacy external resources.
	 *
	 * This function will delete old external resources from database
	 *
	 * @since 1.1.2
	 * @access public
	 * @return string
	 */
	public function prune_external_assets() {
		$end_message           = '<b>' . esc_html__( 'Starting pruning old core resources', 'no-unsafe-inline' ) . '</b><br>';
		$external_tables_names = array( 'external_scripts' );

		foreach ( $external_tables_names as $table_name ) {
			$assets_to_check = DB::get_external_assets_id( $table_name );

			if ( ! is_null( $assets_to_check ) && 0 < count( $assets_to_check ) ) {
				$assets_to_prune = array();

				foreach ( $assets_to_check as $asset ) {
					try {
						$uri       = Uri::createFromString( $asset->src_attrib );
						$host      = $uri->getHost();
						$path      = $uri->getPath();
						$query     = Query::createFromUri( $uri );
						$asset_ver = $query->get( 'ver' );
					} catch ( \League\Uri\Exceptions\SyntaxError $e ) {
						$asset_ver = null;
					}
					if ( ! is_null( $asset_ver ) ) {
						if ( isset( $host ) && isset( $path ) ) {
							$index                       = $host . '-' . $path;
							$assets_to_prune[ $index ][] = array(
								'ID'         => $asset->ID,
								'ver'        => $asset_ver,
								'src_attrib' => $asset->src_attrib,
							);
						}
					}
				}

				foreach ( $assets_to_prune as $atp ) {
					$num = count( $atp );
					if ( 1 !== $num ) {

						// Put the newest version in the first element of array [0].
						usort( $atp, array( $this, 'ver_compare' ) );

						$latest_version = $atp[0]['ver'];
						$src_attrib     = $atp[0]['src_attrib'];
						$id             = $atp[0]['ID'];

						foreach ( $atp as $k => $value ) {
							if ( $k > 0 && $atp[ $k ]['ver'] !== $latest_version ) {
								$message = sprintf(
									// translators: %1$s is the asset url; %2$s is the internal table name.
									esc_html__( 'Deleted asset: %1$s from table %2$s', 'no-unsafe-inline' ),
									$value['src_attrib'],
									$table_name
								);

								$deleted = DB::ext_delete( $value['ID'], true );
								if ( $deleted > 0 ) {
									$end_message = $end_message . $message . '<br>';
									Log::info( $message );
								}
							} elseif ( $k > 0 && $atp[ $k ]['src_attrib'] === $src_attrib ) {
								$message = sprintf(
									// translators: %1$s is the asset url; %2$s is the internal table name.
									esc_html__( 'Deleted duplicated asset: %1$s from table %2$s', 'no-unsafe-inline' ),
									$value['src_attrib'],
									$table_name
								);
								$deleted = DB::ext_delete( $value['ID'], true );
								if ( $deleted > 0 ) {
									$end_message = $end_message . $message . '<br>';
									Log::info( $message );
								}
								$upd_occurences = DB::ext_occurences_update( $id, $value['ID'] );
							} elseif ( 0 === $k ) {
								$message = sprintf(
									// translators: %1$s is the asset url..
									esc_html__( 'Found multiple asset: %1$s', 'no-unsafe-inline' ),
									$value['src_attrib']
								);
								$end_message = $end_message . '<i>' . $message . '</i><br>';
							} else {
								$message = sprintf(
									// translators: %1$s is the asset url..
									esc_html__( ' asset with different query not removed: %1$s', 'no-unsafe-inline' ),
									$value['src_attrib']
								);
								$end_message = $end_message . $message . '<br>';
							}
						}
						$end_message = $end_message . '<br>';
					}
				}
			}
			Utils::set_last_modified( 'external_scripts' );
		}
		$end_message = $end_message . '<br>';
		return $end_message;
	}

	/**
	 * Delete old entries in occurences table
	 *
	 * @since 1.1.5
	 * @access public
	 * @return string
	 */
	public function prune_old_occurences() {
		$end_message = '<b>' . esc_html__( 'Starting pruning old occurences', 'no-unsafe-inline' ) . '</b><br>';
		$deleted     = DB::delete_old_occurence( self::$max_occ_days );
		if ( false === $deleted ) {
			$end_message = $end_message . sprintf(
				// translators: $d is a number of days.
				esc_html__( 'No occurence found older than %d days', 'no-unsafe-inline' ),
				self::$max_occ_days
			)
			. '<br>';
		} else {
			$end_message = $end_message . sprintf(
				// translators: %1$d is the number of entries deleted; %2$d is a number of days.
				esc_html__( 'Deleted %1$d occurrences older than %2$d days', 'no-unsafe-inline' ),
				$deleted,
				self::$max_occ_days
			)
			. '<br>';
			Utils::set_last_modified( 'occurences' );
		}
		return $end_message;
	}

	/**
	 * Sorting function for array of (ID, ver, src_attrib)
	 *
	 * @since 1.1.2
	 * @access private
	 * @param array{ID?: int, ver: string, src_attrib?: string} $a an array with a 'ver' key.
	 * @param array{ID?: int, ver: string, src_attrib?: string} $b an array with a 'ver' key.
	 * @return int
	 */
	private function ver_compare( $a, $b ) {
		// jetpack uses filemtime( "$dir/$path" ) when no version is specified and adds it as ver in URI query.
		if ( ( version_compare( $a['ver'], '0.0.1', '>=' ) || version_compare( $b['ver'], '0.0.1', '>=' ) ) &&
				ctype_xdigit( $a['ver'] ) &&
				ctype_xdigit( $b['ver'] )
			) {
				$a_ver = hexdec( $a['ver'] );
				$b_ver = hexdec( $b['ver'] );

				$diff = $b_ver - $a_ver;

			if ( 0 === $diff ) {
				return 0;
			}
			if ( $diff < 0 ) {
				return -1;
			}
			if ( $diff > 0 ) {
				return 1;
			}
		}
		return ( version_compare( $b['ver'], $a['ver'] ) );
	}
}
