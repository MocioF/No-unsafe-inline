<?php
/**
 * The class used to perform actions on DB.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 */

defined( 'ABSPATH' ) || die( 'you do not have acces to this page!' );

/**
 * The edit db functionality called by wp_list_table actions.
 *
 * Runs query on DB triggered by the user
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Db_Queries {
	/**
	 * Global $wpdb stdClass obj
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var $wpdb The globa $wpdb object.
	 */
	private $wpdb;

	/**
	 * Table name for occurences
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var $tbl_occ a string with table name for occurences.
	 */
	private $tbl_occ;

	/**
	 * Table name for inline scripts
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var $tbl_inl a string with table name for inline scripts.
	 */
	private $tbl_inl;

	/**
	 * Table name for external scripts
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var $tbl_ext a string with table name for external scripts.
	 */
	private $tbl_ext;

	/**
	 * Table name for inline event handlers
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var $tbl_occ a string with table name for event handlers.
	 */
	private $tbl_han;

	/**
	 * The class contructor
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb    = $wpdb;
		$this->tbl_occ = NO_UNSAFE_INLINE_TABLE_PREFIX . 'occurences';
		$this->tbl_inl = NO_UNSAFE_INLINE_TABLE_PREFIX . 'inline_scripts';
		$this->tbl_ext = NO_UNSAFE_INLINE_TABLE_PREFIX . 'external_scripts';
		$this->tbl_evh = NO_UNSAFE_INLINE_TABLE_PREFIX . 'event_handlers';
	}

	/**
	 * WhiteList a inline script
	 *
	 * @param mixed $id The inline script id or an ARRAY_N of script_id.
	 * @param bool  $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function inl_whitelist( $id, $wl = true ) {
		$affected = $this->whitelist( $this->tbl_inl, $id, $wl );

		return $affected;
	}

	/**
	 * WhiteList an external script
	 *
	 * @param mixed $id The inline script id or an ARRAY_N of script_id.
	 * @param bool  $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function ext_whitelist( $id, $wl = true ) {
		$affected = $this->whitelist( $this->tbl_ext, $id, $wl );

		return $affected;
	}

	/**
	 * WhiteList an external script
	 *
	 * @param mixed $id The external script id or an ARRAY_N of script_id.
	 * @param bool  $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function evh_whitelist( $id, $wl = true ) {
		$affected = $this->whitelist( $this->tbl_evh, $id, $wl );

		return $affected;
	}

	/**
	 * WhiteList/BlackList a script
	 *
	 * @param string $table The script table (one of $this->tbl_inl $this->tbl_evh).
	 * @param mixed  $id The inline script id or an ARRAY_N of script_id.
	 * @param bool   $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function whitelist( $table, $id, $wl ) {
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {

			if ( $this->tbl_ext !== $table ) {

				$sel_cluster = "SELECT `clustername`, `tagname` from $table WHERE `id` = %d";
				$row         = $this->wpdb->get_row(
					$this->wpdb->prepare(
						$sel_cluster,
						$id
					)
				);
				$clustername = $row->clustername;
				$tagname     = $row->tagname;

				if ( 'Unclustered' === $clustername ) {
					$upd_wl   = "UPDATE $table SET `whitelist` = %d WHERE `id` = %d";
					$affected = $affected + $this->wpdb->query(
						$this->wpdb->prepare(
							$upd_wl,
							$wl,
							$id
						)
					);
				} else {
					$upd_wl   = "UPDATE $table SET `whitelist` = %d WHERE `clustername` = %s AND `tagname` = %s";
					$affected = $affected + $this->wpdb->query(
						$this->wpdb->prepare(
							$upd_wl,
							$wl,
							$clustername,
							$tagname
						)
					);
				}
			} else {
				$upd_wl   = "UPDATE $table SET `whitelist` = %d WHERE `id` = %d";
				$affected = $affected + $this->wpdb->query(
					$this->wpdb->prepare(
						$upd_wl,
						$wl,
						$id
					)
				);
			}
		}
		return $affected;
	}

	/**
	 * Uncluster an inline event cluster
	 *
	 * @param mixed $id The inline script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function inl_uncluster( $id ) {
		$affected = $this->uncluster( $this->tbl_inl, $id );

		return $affected;
	}

	/**
	 * Uncluster a event_handler script cluster
	 *
	 * @param mixed $id The event script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function evh_uncluster( $id ) {
		$affected = $this->uncluster( $this->tbl_evh, $id );

		return $affected;
	}

	/**
	 * Removes a cluster from database, setting it to 'Uncluster'
	 * in previously clustered script
	 *
	 * @param string $table The script table (one of $this->tbl_inl $this->tbl_evh).
	 * @param mixed  $id The script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function uncluster( $table, $id ) {
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {
			$sel_cluster = "SELECT `clustername`, `tagname` FROM $table WHERE `id` = %d";
			$row         = $this->wpdb->get_row(
				$this->wpdb->prepare(
					$sel_cluster,
					$id
				)
			);
			$clustername = $row->clustername;
			$tagname     = $row->tagname;
			if ( 'Unclustered' !== $clustername ) {
				$upd_cl   = "UPDATE $table SET `clustername` = 'Unclustered' WHERE `clustername` = %s AND `tagname` = %s";
				$affected = $affected + $this->wpdb->query(
					$this->wpdb->prepare(
						$upd_cl,
						$clustername,
						$tagname
					)
				);
			}
		}
		return $affected;
	}


	/**
	 * Removes a script or a cluster of scripts from inline_scripts table
	 *
	 * @param mixed $id The inline script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function inl_delete( $id, $delete_occurences = true ) {
		$affected = $this->delete( $this->tbl_inl, $id, $delete_occurences );

		return $affected;
	}

	/**
	 * Removes a script or a cluster of scripts from event_handlers table
	 *
	 * @param mixed $id The handler script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function evh_delete( $id, $delete_occurences = true ) {
		$affected = $this->delete( $this->tbl_evh, $id, $delete_occurences );

		return $affected;
	}

	/**
	 * Removes a script from external_scripts table
	 *
	 * @param mixed $id The external script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function ext_delete( $id, $delete_occurences = false ) {
		$affected = $this->delete( $this->tbl_ext, $id, $delete_occurences );

		return $affected;
	}

	/**
	 * Removes a script or a cluster of scripts
	 * from the database
	 *
	 * @param string  $table The script table (one of $this->tbl_inl $this->tbl_evh).
	 * @param mixed   $id The inline script id or an ARRAY_N of script_id.
	 * @param boolval $delete_occurences True to remove entryes from occurences table.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public function delete( $table, $id, $delete_occurences ) {
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {

			if ( $this->tbl_ext !== $table ) {

				$sel_cluster = "SELECT `clustername`, `tagname` FROM $table WHERE `id` = %d";
				$row         = $this->wpdb->get_row(
					$this->wpdb->prepare(
						$sel_cluster,
						$id
					)
				);
				$clustername = $row->clustername;
				$tagname     = $row->tagname;

				if ( 'Unclustered' === $clustername ) {
					$del_sc   = "DELETE FROM $table WHERE `id` = %d";
					$affected = $affected + $this->wpdb->query(
						$this->wpdb->prepare(
							$del_sc,
							$id
						)
					);
					if ( true === $delete_occurences ) {
						$del_sc_occ = "DELETE FROM $this->tbl_occ WHERE `itemid` = %d AND dbtable = %s";
						$del_occur  = $this->wpdb->query(
							$this->wpdb->prepare(
								$del_sc_occ,
								$id,
								substr( $table, strlen( NO_UNSAFE_INLINE_TABLE_PREFIX ) )
							)
						);
					}
				} else {
					$sel_ids = "SELECT `id` FROM $table WHERE `clustername` = %s AND `tagname` = %s";
					$ids     = $this->wpdb->get_results(
						$this->wpdb->prepare(
							$sel_ids,
							$clustername,
							$tagname
						)
					);

					$del_cl   = "DELETE FROM $table WHERE `clustername` = %s AND `tagname` = %s";
					$affected = $affected + $this->wpdb->query(
						$this->wpdb->prepare(
							$del_cl,
							$clustername,
							$tagname
						)
					);
					if ( true === $delete_occurences ) {
						$in_str = '(';
						foreach ( $ids as $rid ) {
							$in_str = $in_str . "('" . substr( $table, strlen( NO_UNSAFE_INLINE_TABLE_PREFIX ) ) . "', $rid->id), ";
						}
						$in_str = substr( $in_str, 0, strlen( $in_str ) - 2 );
						$in_str = $in_str . ')';

						$del_cl_occur = "DELETE FROM $table WHERE (`dbtable`,`itemid`) IN $in_str";
						$del_occur    = $this->wpdb->query( $del_cl_occur );
					}
				}
			} else {
				$del_sc   = "DELETE FROM $table WHERE `id` = %d";
				$affected = $affected + $this->wpdb->query(
					$this->wpdb->prepare(
						$del_sc,
						$id
					)
				);
			}
		}
		return $affected;
	}

}
