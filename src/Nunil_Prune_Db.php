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
		$end_message            = '<b>' . esc_html__( 'Starting pruning big clusters', 'no-unsafe-inline' ) . '</b><br>';
		$internale_tables_names = array( 'inline_scripts', 'event_handlers' );
		foreach ( $internale_tables_names as $table_name ) {
			// translators: %s is the internal tablename.
			$end_message  = $end_message . sprintf( esc_html__( 'Pruning big clusters in %s', 'no-unsafe-inline' ), $table_name ) . '<br>';
			$big_clusters = DB::get_big_clusters( $table_name, self::$cluster_limit );
			if ( ! is_null( $big_clusters ) ) {
				foreach ( $big_clusters as $cluster ) {
					$n_del = 0;
					$ids   = DB::get_oldest_scripts_id( $table_name, $cluster->clustername, self::$cluster_limit );
					if ( ! is_null( $ids ) ) {
						switch ( $table_name ) {
							case 'inline_scripts':
								foreach ( $ids as $id ) {
									$n_del = $n_del + DB::inl_delete( $id->ID );
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
									$n_del = $n_del + DB::evh_delete( $id->ID );
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
			}
		}
		$end_message = $end_message . '<br>';
		return $end_message;
	}

	/**
	 * Prune the database.
	 *
	 * This function is triggered by a button in admin area
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function prune_database(): void {
		$this->delete_orphan_occurences();
		$this->prune_big_clusters();
	}


}
