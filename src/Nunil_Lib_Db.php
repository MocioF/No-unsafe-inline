<?php
/**
 * Database Lib Class
 *
 * Class has static methods to operate DB operations.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use NUNIL\Nunil_Exception;

/**
 * Class with static methods called while operating on db
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Lib_Db {

	/**
	 * Fields in log table
	 *
	 * @var string Comma separated log fields to order_by select query results.
	 */
	private static $allowed_logs_fields = 'level,message,created_at';

	/**
	 * Prefix tables
	 *
	 * @since 1.0.0
	 * @param string $table The internal table name.
	 * @return string
	 */
	private static function with_prefix( $table ): string {
		global $wpdb;
		if ( 0 === strpos( $table, 'nunil_', 0 ) ) {
			return $wpdb->prefix . $table;
		} else {
			return $wpdb->prefix . 'nunil_' . $table;
		}
	}

	/**
	 * Create inline scripts table name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function inline_scripts_table() {
		return self::with_prefix( 'nunil_inline_scripts' );
	}

	/**
	 * Create external scripts table name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function external_scripts_table() {
		return self::with_prefix( 'nunil_external_scripts' );
	}

	/**
	 * Create event handlers scripts table name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function event_handlers_table() {
		return self::with_prefix( 'nunil_event_handlers' );
	}

	/**
	 * Create occurences table name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function occurences_table() {
		return self::with_prefix( 'nunil_occurences' );
	}

	/**
	 * Create logs table name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function logs_table() {
		return self::with_prefix( 'nunil_logs' );
	}

	/**
	 * Create SQL snippet based on hash length
	 *
	 * @since 1.0.0
	 * @param string $hash An sha supported hash digest.
	 * @return string
	 */
	private static function src_hash( $hash ) {
		switch ( strlen( $hash ) ) {
			case 44:
				return '`sha256` = %s';
			case 64:
				return '`sha384` = %s';
			case 88:
				return '`sha512` = %s';
			default:
				return '1';
		}
	}

	/**
	 * Creates the tables in the WordPress DB and register the DB version
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function db_create() {
		global $wpdb;

		$db_version = get_option( 'no-unsafe-inline_db_version' );

		// We need this for dbDelta() to work.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate        = $wpdb->get_charset_collate();
		$inline_scripts_table   = self::inline_scripts_table();
		$external_scripts_table = self::external_scripts_table();
		$event_handlers_table   = self::event_handlers_table();
		$occurences_table       = self::occurences_table();
		$logs_table             = self::logs_table();

		$sql = "CREATE TABLE $inline_scripts_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				directive VARCHAR(20) NOT NULL,
				tagname VARCHAR(50) NOT NULL,
				script TEXT NOT NULL,
				sha256 CHAR(44) COLLATE ascii_bin,
				sha384 CHAR(64) COLLATE ascii_bin,
				sha512 CHAR(88) COLLATE ascii_bin,
				nilsimsa CHAR(64) COLLATE ascii_bin,
				clustername VARCHAR(64) COLLATE ascii_bin NOT NULL DEFAULT 'Unclustered',
				whitelist TINYINT(1) NOT NULL DEFAULT '0',
				sticky TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (ID),
				KEY sha256_ind (`sha256`),
				KEY sha384_ind (`sha384`),
				KEY sha512_ind (`sha512`)
				) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE $external_scripts_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				directive VARCHAR(20) NOT NULL,
				tagname VARCHAR(50) NOT NULL,
				src_attrib VARCHAR(768) NOT NULL,
				sha256 CHAR(44) COLLATE ascii_bin,
				sha384 CHAR(64) COLLATE ascii_bin,
				sha512 CHAR(88) COLLATE ascii_bin,
				whitelist TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (ID),
				KEY src_attrib_ind (`src_attrib`)
				) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE $event_handlers_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				tagname VARCHAR(255),
				tagid VARCHAR(255),
				event_attribute VARCHAR(255),
				script TEXT NOT NULL,
				sha256 CHAR(44) COLLATE ascii_bin,
				sha384 CHAR(64) COLLATE ascii_bin,
				sha512 CHAR(88) COLLATE ascii_bin,
				nilsimsa CHAR(64) COLLATE ascii_bin,
				clustername VARCHAR(64) COLLATE ascii_bin NOT NULL DEFAULT 'Unclustered',
				whitelist TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (ID),
				KEY sha256_ind (`sha256`),
				KEY sha384_ind (`sha384`),
				KEY sha512_ind (`sha512`)
				) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE $occurences_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				dbtable VARCHAR(50),
				itemid INT(11) NOT NULL,
				pageurl VARCHAR(255),
				lastseen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (ID)
				) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE $logs_table (
				level varchar(10) DEFAULT 'debug',
				message varchar(2000) NOT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL
				) $charset_collate;";
		dbDelta( $sql );

		if ( NO_UNSAFE_INLINE_DB_VERSION !== $db_version ) {
			/**
			 * Manage future changes on different DB Versions not
			 * managed by dbDelta.
			 *
			 * //   if ( $db_version == 1 ) {
			 * //   }
			 */
			update_option( 'no-unsafe-inline_db_version', NO_UNSAFE_INLINE_DB_VERSION );
		}
	}

	/**
	 * Retrieves array of -src directive in external table.
	 * Used in \NUNIL\Nunil_Base_Src_Rules
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array<string>
	 */
	public static function get_directive_in_ext() {
		global $wpdb;
		$sql = 'SELECT DISTINCT `directive` FROM ' . self::external_scripts_table();
		return $wpdb->get_results( $sql, ARRAY_N );
	}

	/**
	 * Select src attrib from external table for a directive
	 * Used in \NUNIL\Nunil_Base_Src_Rules
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $directive A -src directive.
	 * @return array<\stdClass>|null
	 */
	public static function get_attrs_for_dir_in_ext( $directive ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT src_attrib FROM ' . self::external_scripts_table() . ' WHERE directive = %s',
			$directive
		);

		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Select ID of an event handlers script.
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $tagname The html tagname.
	 * @param string $tagid The id attribute of the html tag.
	 * @param string $event_attribute A event attribute of the html tag.
	 * @param string $hash The sha supported hash of the js event script.
	 * @return int|null
	 */
	public static function get_evh_id( $tagname, $tagid, $event_attribute, $hash ) {
		global $wpdb;
		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			'SELECT `ID` FROM ' . self::event_handlers_table() . ' WHERE `tagname`=%s AND `tagid`=%s AND `event_attribute`=%s AND ' . self::src_hash( $hash ),
			$tagname,
			$tagid,
			$event_attribute,
			$hash
		);

		$result = $wpdb->get_var( $sql );

		if ( ! is_null( $result ) ) {
			return intval( $result );
		} else {
			return null;
		}
	}

	/**
	 * Get the occurence id of an event handler or inline style/script in page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int    $id The ID of the row in event_handler table.
	 * @param string $tbl_string One table identifier between 'event_handlers', 'inline_scripts', 'external_scripts'.
	 * @param string $page_url The URL to be logged in db (used when capturing violations).
	 * @return int|null The ID of the row in occurences
	 */
	public static function get_occ_id( $id, $tbl_string, $page_url = null ) {
		global $wpdb;

		$page_url = is_null( $page_url ) ? Nunil_Lib_Utils::get_page_url() : $page_url;

		$sql    = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::occurences_table() . ' WHERE `dbtable`=%s AND `itemid`=%d AND `pageurl`=%s',
			$tbl_string,
			intval( $id ),
			$page_url
		);
		$result = $wpdb->get_var( $sql );
		if ( ! is_null( $result ) ) {
			return intval( $result );
		} else {
			return null;
		}
	}

	/**
	 * Update lastseen field in occurences for an occurence ID
	 *
	 * @since 1.0.0
	 * @param int $occurrence_id The occurence id.
	 * @return void
	 */
	public static function update_lastseen( $occurrence_id ) {
		global $wpdb;
		if ( function_exists( 'wp_date' ) ) {
			$data = array(
				'lastseen' => wp_date( 'Y-m-d H:i:s' ),
			);
		} else {
			$data = array(
				'lastseen' => gmdate( 'Y-m-d H:i:s' ),
			);
		}
		$where  = array(
			'ID' => intval( $occurrence_id ),
		);
		$format = array( '%s' );
		$wpdb->update( self::occurences_table(), $data, $where, $format );
	}

	/**
	 * Select ID of an inline style attr.
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $tagname The html tagname.
	 * @param string $hash The sha supported hash of the js event script.
	 * @return int|null
	 */
	public static function get_inl_id( $tagname, $hash ) {
		global $wpdb;
		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			'SELECT `ID` FROM ' . self::inline_scripts_table() . ' WHERE `tagname`=%s AND ' . self::src_hash( $hash ) . ' LIMIT 1',
			$tagname,
			$hash
		);

		$result = $wpdb->get_var( $sql );

		if ( ! is_null( $result ) ) {
			return intval( $result );
		} else {
			return null;
		}
	}

	/**
	 * Select ID of an external style/script
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $directive The -src csp directive.
	 * @param string $tagname The html tagname.
	 * @param string $src_attrib The tag attrib in src_attrib.
	 * @return int|null
	 */
	public static function get_ext_id( $directive, $tagname, $src_attrib ) {
		global $wpdb;
		$sql    = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::external_scripts_table() . ' WHERE `directive`=%s AND `tagname`=%s AND `src_attrib`=%s',
			$directive,
			$tagname,
			$src_attrib
		);
		$result = $wpdb->get_var( $sql );

		if ( ! is_null( $result ) ) {
			return intval( $result );
		} else {
			return null;
		}
	}

	/**
	 * Insert a new inline script or style in inline_scripts table.
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $directive The CSP -src directive.
	 * @param string $tagname The HTML tagname.
	 * @param string $content The inline content.
	 * @param bool   $sticky  True if the script is sticky to the pages.
	 *                        Sticky (whitelisted) scripts will always be
	 *                        inserted in CSP for the page.
	 * @param bool   $utf8 Set to true only for inline js scripts.
	 * @param string $nilsimsa The Nilsimsa hexDigest.
	 * @return int The ID of inserted row
	 */
	public static function insert_inl_in_db( $directive, $tagname, $content, $sticky, $utf8, $nilsimsa = '' ) {
		global $wpdb;

		if ( '' === $nilsimsa ) {
			$lsh      = new \Beager\Nilsimsa( $content );
			$nilsimsa = $lsh->hexDigest();
		}

		$data   = array(
			'directive' => $directive,
			'tagname'   => $tagname,
			'script'    => $content,
			'sha256'    => Nunil_Capture::calculate_hash( 'sha256', $content, $utf8 ),
			'sha384'    => Nunil_Capture::calculate_hash( 'sha384', $content, $utf8 ),
			'sha512'    => Nunil_Capture::calculate_hash( 'sha512', $content, $utf8 ),
			'nilsimsa'  => $nilsimsa,
			'sticky'    => $sticky,
		);
		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );

		$wpdb->insert( self::inline_scripts_table(), $data, $format );

		return $wpdb->insert_id;
	}

	/**
	 * Insert a new external resource in external_scripts table.
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $directive The CSP -src directive.
	 * @param string $tagname The HTML tagname.
	 * @param string $src_attrib The content of the src or href HTML attrib.
	 * @return int The ID of inserted row
	 */
	public static function insert_ext_in_db( $directive, $tagname, $src_attrib ) {
		global $wpdb;

		$data   = array(
			'directive'  => $directive,
			'tagname'    => $tagname,
			'src_attrib' => $src_attrib,
		);
		$format = array( '%s', '%s', '%s' );

		$wpdb->insert( self::external_scripts_table(), $data, $format );

		return $wpdb->insert_id;
	}

	/**
	 * Insert a new event handlers in table.
	 * Used in \NUNIL\Nunil_Capture
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array<string> $row The event handlers properties.
	 * @return int The ID of inserted row
	 */
	public static function insert_evh_in_db( $row ) {
		global $wpdb;

		$lsh = new \Beager\Nilsimsa( $row['script'] );

		$data = array(
			'tagname'         => $row['tagname'],
			'tagid'           => $row['tagid'],
			'event_attribute' => $row['event_attribute'],
			'script'          => $row['script'],
			'sha256'          => Nunil_Capture::calculate_hash( 'sha256', $row['script'], $utf8 = true ),
			'sha384'          => Nunil_Capture::calculate_hash( 'sha384', $row['script'], $utf8 = true ),
			'sha512'          => Nunil_Capture::calculate_hash( 'sha512', $row['script'], $utf8 = true ),
			'nilsimsa'        => $lsh->hexDigest(),
		);

		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		$wpdb->insert( self::event_handlers_table(), $data, $format );

		return $wpdb->insert_id;
	}

	/**
	 * Insert a newly found event handlers in occurences
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int    $id The ID of the row in event_handler table.
	 * @param string $tbl_string One table identifier between 'event_handlers', 'inline_scripts'.
	 * @param string $page_url The URL to be logged in db (used when capturing violations).
	 * @return int The ID of the row in occurences
	 */
	public static function insert_occ_in_db( $id, $tbl_string, $page_url = null ) {
		global $wpdb;

		$page_url = is_null( $page_url ) ? Nunil_Lib_Utils::get_page_url() : $page_url;

		$data = array(
			'dbtable' => $tbl_string,
			'itemid'  => intval( $id ),
			'pageurl' => $page_url,
		);
		if ( function_exists( 'wp_date' ) ) {
			$data['lastseen'] = wp_date( 'Y-m-d H:i:s' );
		} else {
			$data['lastseen'] = gmdate( 'Y-m-d H:i:s' );
		}

		$format = array( '%s', '%d', '%s', '%s' );

		$wpdb->insert( self::occurences_table(), $data, $format );

		return $wpdb->insert_id;
	}



	/**
	 * Inserts a log entry in the DB
	 *
	 * @param string $level One of debug, info, warning, error.
	 * @param string $message The message to log.
	 *
	 * @return int|false Inserted Id or false if error
	 */
	public static function insert_log( $level, $message ) {
		global $wpdb;

		$result = $wpdb->insert(
			self::logs_table(),
			array(
				'level'   => $level,
				'message' => $message,
			)
		);

		if ( false === $result ) {
			error_log( 'Could not insert the log: ' . $level . ' ' . $message );
			return $result;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Return the total log entries
	 *
	 * @return int Total log entries
	 */
	public static function get_total_logs() {
		global $wpdb;

		$total = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::logs_table() );
		return is_null( $total ) ? 0 : intval( $total );
	}

	/**
	 * Get the logs
	 *
	 * @param int    $offset Offset.
	 * @param int    $size Limit.
	 * @param string $order_by Ordering field.
	 * @param string $order_asc Sort: 'asc' or 'desc'.
	 * @param string $mode Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 * @return array<array<\stdClass>>|array<array<string>>|array<\stdClass> The result with the logs
	 * @throws \NUNIL\Nunil_Exception If $order_by parameter is invalid.
	 */
	public static function get_logs( $offset, $size, $order_by = 'created_at', $order_asc = 'desc', $mode = OBJECT ) {
		global $wpdb;

		if ( strpos( self::$allowed_logs_fields, $order_by ) === false ) {
			throw new Nunil_Exception(
				sprintf(
					// translators: %s is the order_by parameter.
					esc_html__( 'The order by [%s] field is not allowed', 'no-unsafe-inline' ),
					esc_html( $order_by )
				),
				3020,
				3
			);
		}
		$order_asc = 'asc' === $order_asc ? 'asc' : 'desc';

		$sql     = $wpdb->prepare(
			'SELECT ' . self::$allowed_logs_fields .
			' FROM ' . self::logs_table() .
			' ORDER BY ' . $order_by . ' ' . $order_asc .
			' LIMIT %d OFFSET %d',
			$size,
			$offset
		);
		$results = $wpdb->get_results( $sql, $mode );
		return $results;
	}

	/**
	 * WhiteList a inline script
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function inl_whitelist( $id, $wl = true ) {
		$affected = self::whitelist( self::inline_scripts_table(), $id, $wl );
		return $affected;
	}

	/**
	 * WhiteList an external script
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function ext_whitelist( $id, $wl = true ) {
		$affected = self::whitelist( self::external_scripts_table(), $id, $wl );
		return $affected;
	}

	/**
	 * WhiteList an external script
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function evh_whitelist( $id, $wl = true ) {
		$affected = self::whitelist( self::event_handlers_table(), $id, $wl );
		return $affected;
	}

	/**
	 * WhiteList/BlackList a script
	 *
	 * @param string               $table The script table.
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $wl (true will Whiteliste, false to blacklist).
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function whitelist( $table, $id, $wl ) {
		global $wpdb;
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {
			if ( self::external_scripts_table() !== $table ) {
				$sel_cluster = 'SELECT `clustername`, `tagname` FROM ' . $table . ' WHERE `ID` = %d';
				$row         = $wpdb->get_row(
					$wpdb->prepare(
						$sel_cluster,
						$id
					)
				);
				$clustername = $row->clustername;
				$tagname     = $row->tagname;

				if ( 'Unclustered' === $clustername ) {
					$upd_wl   = 'UPDATE ' . $table . ' SET `whitelist` = %d WHERE `ID` = %d';
					$affected = $affected + $wpdb->query(
						$wpdb->prepare(
							$upd_wl,
							$wl,
							$id
						)
					);
				} else {
					$upd_wl   = 'UPDATE ' . $table . ' SET `whitelist` = %d WHERE `clustername` = %s AND `tagname` = %s';
					$affected = $affected + $wpdb->query(
						$wpdb->prepare(
							$upd_wl,
							$wl,
							$clustername,
							$tagname
						)
					);
				}
			} else {
				$upd_wl   = 'UPDATE ' . $table . ' SET `whitelist` = %d WHERE `ID` = %d';
				$affected = $affected + $wpdb->query(
					$wpdb->prepare(
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
	 * @param string $id The inline script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function inl_uncluster( $id ) {
		$affected = self::uncluster( self::inline_scripts_table(), $id );
		return $affected;
	}

	/**
	 * Uncluster a event_handler script cluster
	 *
	 * @param string $id The event script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function evh_uncluster( $id ) {
		$affected = self::uncluster( self::event_handlers_table(), $id );
		return $affected;
	}

	/**
	 * Removes a cluster from database, setting it to 'Uncluster'
	 * in previously clustered script
	 *
	 * @param string               $table The script table (one of $this->tbl_inl $this->tbl_evh).
	 * @param string|array<string> $id The script id or an ARRAY_N of script_id.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	public static function uncluster( $table, $id ) {
		global $wpdb;
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {
			$sel_cluster = 'SELECT `clustername`, `tagname` FROM ' . $table . ' WHERE `ID` = %d';
			$row         = $wpdb->get_row(
				$wpdb->prepare(
					$sel_cluster,
					$id
				)
			);
			$clustername = $row->clustername;
			$tagname     = $row->tagname;
			if ( 'Unclustered' !== $clustername ) {
				$upd_cl   = 'UPDATE ' . $table . ' SET `clustername` = \'Unclustered\' WHERE `clustername` = %s AND `tagname` = %s';
				$affected = $affected + $wpdb->query(
					$wpdb->prepare(
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
	 * @since 1.0.0
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function inl_delete( $id, $delete_occurences = true ) {
		$affected = self::delete( self::inline_scripts_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script or a cluster of scripts from event_handlers table
	 *
	 * @since 1.0.0
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function evh_delete( $id, $delete_occurences = true ) {
		$affected = self::delete( self::event_handlers_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script from external_scripts table
	 *
	 * @since 1.0.0
	 * @param string|array<string> $id The external script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function ext_delete( $id, $delete_occurences = false ) {
		$affected = self::delete( self::external_scripts_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script or a cluster of scripts
	 * from the database
	 *
	 * @param string               $table The script full table name.
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to remove entryes from occurences table.
	 * @since 1.0.0
	 *
	 * @return int The number of affected rows
	 */
	private static function delete( $table, $id, $delete_occurences ) {
		global $wpdb;
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;

		foreach ( $my_ids as $id ) {
			if ( self::external_scripts_table() !== $table ) {
				$sel_cluster = 'SELECT `clustername`, `tagname` FROM ' . $table . ' WHERE `ID` = %d';
				$row         = $wpdb->get_row(
					$wpdb->prepare(
						$sel_cluster,
						$id
					)
				);
				$clustername = $row->clustername;
				$tagname     = $row->tagname;

				if ( 'Unclustered' === $clustername ) {
					$del_sc   = 'DELETE FROM ' . $table . ' WHERE `ID` = %d';
					$affected = $affected + $wpdb->query(
						$wpdb->prepare(
							$del_sc,
							$id
						)
					);
					if ( true === $delete_occurences ) {
						$del_sc_occ = 'DELETE FROM ' . self::occurences_table() . ' WHERE `itemid` = %d AND dbtable = %s';
						$del_occur  = $wpdb->query(
							$wpdb->prepare(
								$del_sc_occ,
								$id,
								substr( $table, strlen( $wpdb->prefix . 'nunil_' ) )
							)
						);
					}
				} else {
					$sel_ids = 'SELECT `ID` FROM ' . $table . ' WHERE `clustername` = %s AND `tagname` = %s';
					$ids     = $wpdb->get_results(
						$wpdb->prepare(
							$sel_ids,
							$clustername,
							$tagname
						)
					);

					$del_cl   = 'DELETE FROM ' . $table . ' WHERE `clustername` = %s AND `tagname` = %s';
					$affected = $affected + $wpdb->query(
						$wpdb->prepare(
							$del_cl,
							$clustername,
							$tagname
						)
					);
					if ( true === $delete_occurences ) {
						$in_str = '(';
						foreach ( $ids as $rid ) {
							$in_str = $in_str . "('" . substr( $table, strlen( $wpdb->prefix . 'nunil_' ) ) . "', $rid->ID), ";
						}
						$in_str = substr( $in_str, 0, strlen( $in_str ) - 2 );
						$in_str = $in_str . ')';

						$del_cl_occur = 'DELETE FROM ' . self::occurences_table() . ' WHERE (`dbtable`,`itemid`) IN ' . $in_str;
						$del_occur    = $wpdb->query( $del_cl_occur );
					}
				}
			} else {
				$affected = self::ext_single_delete( $id, $delete_occurences );
			}
		}
		return $affected;
	}

	/**
	 * Removes a script from inline_scripts table
	 *
	 * @since 1.1.0
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function inl_single_delete( $id, $delete_occurences = true ) {
		$affected = self::single_delete( self::inline_scripts_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script from event_handlers table
	 *
	 * @since 1.1.0
	 *
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function evh_single_delete( $id, $delete_occurences = true ) {
		$affected = self::single_delete( self::event_handlers_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script from external_scripts table
	 *
	 * @since 1.1.0
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to delete occurences records of the script.
	 * @return int The number of affected rows
	 */
	public static function ext_single_delete( $id, $delete_occurences = false ) {
		$affected = self::single_delete( self::external_scripts_table(), $id, $delete_occurences );
		return $affected;
	}

	/**
	 * Removes a script or a cluster of scripts
	 * from the database
	 *
	 * @param string               $table The script full table name.
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
	 * @param bool                 $delete_occurences True to remove entryes from occurences table.
	 * @since 1.1.0
	 *
	 * @return int The number of affected rows
	 */
	private static function single_delete( $table, $id, $delete_occurences ) {
		global $wpdb;
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}
		$affected = 0;
		foreach ( $my_ids as $id ) {
			$del_sc   = 'DELETE FROM ' . $table . ' WHERE `ID` = %d';
			$affected = $affected + $wpdb->query(
				$wpdb->prepare(
					$del_sc,
					$id
				)
			);

			if ( true === $delete_occurences ) {
				self::delete_asset_occurences( $table, $id );
			}
		}
		return $affected;
	}

	/**
	 * Truncate table
	 *
	 * @since 1.0.0
	 * @param string $table Internal table name.
	 * @return bool
	 */
	public static function truncate_table( $table ) {
		global $wpdb;
		$truncate = $wpdb->query( 'TRUNCATE TABLE ' . self::with_prefix( $table ) );
		return (bool) $truncate;
	}

	/**
	 * Performs query for database summary tables, showed in tools tab
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $table The table name.
	 * @return array<\stdClass>|null
	 */
	public static function get_database_summary_data( $table ) {
		global $wpdb;
		$result = wp_json_encode( array() );
		switch ( $table ) {
			case 'global':
			case 'nunil_global':
				$result = $wpdb->get_results(
					'SELECT \'External Scripts\' AS \'type\', COUNT(`ID`) AS \'num\', '
					. '`whitelist`, \'--\' AS \'clusters\' FROM ' . self::external_scripts_table() . ' '
					. 'WHERE `directive` = \'script-src\' OR `directive` = \'style-src\' OR `directive` = \'worker-src\' '
					. 'GROUP BY `whitelist` '
					. 'UNION ALL '
					. 'SELECT \'Inline Scripts\' AS \'Type\', COUNT(`ID`) AS \'Num\', `whitelist`, '
					. 'COUNT(DISTINCT CASE WHEN `clustername` != \'Unclustered\' THEN `clustername` END) AS \'Clusters\' FROM ' . self::inline_scripts_table() . ' '
					. 'GROUP BY `whitelist` '
					. 'UNION ALL '
					. 'SELECT \'Events\' AS \'Type\', COUNT(`ID`) AS \'Num\', `whitelist`, '
					. 'COUNT(DISTINCT CASE WHEN `clustername` != \'Unclustered\' THEN `clustername` END) AS \'Clusters\' FROM ' . self::event_handlers_table() . ' '
					. 'GROUP BY `whitelist`;'
				);

				break;

			case 'external_scripts':
			case 'nunil_external_scripts':
				$result = $wpdb->get_results(
					'SELECT `directive`, `tagname`, '
					. 'CASE '
					. 'WHEN `tagname` =\'script\' THEN \'Yes\' '
					. 'WHEN `tagname` =\'styles\' THEN \'Yes\' '
					. 'ELSE \'No\' '
					. 'END AS \'nonceable\', '
					. 'CASE '
					. 'WHEN `directive` =\'script-src\' THEN `whitelist` '
					. 'WHEN `directive` =\'style-src\' THEN `whitelist` '
					. 'WHEN `directive` =\'worker-src\' THEN `whitelist` '
					. 'ELSE \'--\' '
					. 'END AS \'whitelist\', '
					. 'COUNT(`ID`) AS \'num\' FROM ' . self::with_prefix( $table ) . ' '
					. 'GROUP BY `whitelist`, `tagname`, `directive` '
					. 'ORDER BY `nonceable` DESC, `directive` ASC, `tagname` ASC, `num` ASC, `whitelist` ASC;'
				);
				break;

			case 'inline_scripts':
			case 'nunil_inline_scripts':
				$result = $wpdb->get_results(
					'SELECT `directive`, `tagname`, `clustername`, `whitelist`, COUNT(*) as \'num\' FROM ' . self::with_prefix( $table ) . ' '
					. 'GROUP BY `whitelist`, `clustername`, `tagname`, `directive` '
					. 'ORDER BY `directive` ASC, `tagname` ASC, `clustername` ASC, `num` ASC, `whitelist` ASC;'
				);
				break;

			case 'event_handlers':
			case 'nunil_event_handlers':
				$result = $wpdb->get_results(
					'SELECT `tagname`, `event_attribute`, `clustername`, `whitelist`, COUNT(`ID`) as \'num\' FROM ' . self::with_prefix( $table ) . ' '
					. 'GROUP BY `whitelist`, `clustername`, `tagname`, `event_attribute` '
					. 'ORDER BY `tagname` ASC, `event_attribute` ASC, `clustername` ASC, `num` ASC, `whitelist` ASC;'
				);
				break;
		}

		return $result;
	}

	/**
	 * Returns an array of rows of inline whitelisted entries
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array<\stdClass>|null
	 */
	public static function get_inline_rows() {
		global $wpdb;
		$sql = 'SELECT sha256, sha384, sha512, nilsimsa, clustername, whitelist, tagname, directive FROM ' . self::inline_scripts_table();
		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Returns an array of rows of event_handlers whitelisted entries
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array<\stdClass>|null
	 */
	public static function get_events_rows() {
		global $wpdb;
		$sql = 'SELECT sha256, sha384, sha512, nilsimsa, clustername, whitelist, event_attribute, tagname, tagid FROM ' . self::event_handlers_table();
		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Returns an array of rows of external whitelisted entries
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array<\stdClass>|null
	 */
	public static function get_external_rows() {
		global $wpdb;
		$sql = 'SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM `'
			. self::external_scripts_table() . '`'
			. ' WHERE `whitelist`= 1 ';
		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Cluster and whitelist an inline script
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int    $id The script ID.
	 * @param string $clustername The clustername.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function upd_inl_cl_wl( $id, $clustername ) {
		global $wpdb;
		$data = array(
			'clustername' => $clustername,
			'whitelist'   => 1,
		);
		return $wpdb->update( self::inline_scripts_table(), $data, array( 'ID' => $id ), array( '%s', '%d' ), array( '%d' ) );
	}

	/**
	 * Cluster and whitelist an event of an event handler
	 *
	 * @since 1.2.0
	 * @access public
	 * @param int    $id The script ID.
	 * @param string $clustername The clustername.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function upd_evh_cl_wl( $id, $clustername ) {
		global $wpdb;
		$data = array(
			'clustername' => $clustername,
			'whitelist'   => 1,
		);
		return $wpdb->update( self::event_handlers_table(), $data, array( 'ID' => $id ), array( '%s', '%d' ), array( '%d' ) );
	}

	/**
	 * Get scrattrib and hashes from external_script id
	 *
	 * @since 1.0.0
	 * @param int $id The script ID.
	 * @return \stdClass|null
	 */
	public static function get_ext_hashes_from_id( $id ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT `src_attrib`, `sha256`, `sha384`, `sha512`  FROM ' . self::external_scripts_table() . ' WHERE `ID` = %s',
			$id
		);

		return $wpdb->get_row( $sql, OBJECT );
	}

	/**
	 * Get whitelist status of an external script, identified by hashes
	 *
	 * @param \stdClass $data Object with ext script hashes.
	 * @return int|null
	 */
	public static function get_ext_wl( $data ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT `whitelist` FROM ' . self::external_scripts_table() . ' WHERE `sha256` = %s AND `sha384` = %s AND `sha512` = %s LIMIT 1',
			$data->sha256,
			$data->sha384,
			$data->sha512
		);
		$val = $wpdb->get_var( $sql );
		if ( ! is_null( $val ) ) {
			return intval( $val );
		} else {
			return null;
		}
	}

	/**
	 * Update hashes of an external script
	 *
	 * @since 1.0.0
	 * @param array<string|int> $data  Array with ext script hashes and whitelist status.
	 * @param int               $id The ID of script in DB table.
	 * @param array<string>     $format An array of formats to be mapped to each of the values in $data.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function update_ext_hashes( $data, $id, $format ) {
		global $wpdb;
		return $wpdb->update( self::external_scripts_table(), $data, array( 'ID' => $id ), $format, array( '%d' ) );
	}

	/**
	 * Remove data tables from database
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function remove_data_tables() {
		global $wpdb;
		$structure = 'DROP TABLE IF EXISTS ' . self::inline_scripts_table();
		$wpdb->query( $structure );

		$structure = 'DROP TABLE IF EXISTS ' . self::external_scripts_table();
		$wpdb->query( $structure );

		$structure = 'DROP TABLE IF EXISTS ' . self::event_handlers_table();
		$wpdb->query( $structure );

		$structure = 'DROP TABLE IF EXISTS ' . self::occurences_table();
		$wpdb->query( $structure );

		$structure = 'DROP TABLE IF EXISTS ' . self::logs_table();
		$wpdb->query( $structure );
	}

	/**
	 * Unprepared sql for inline_list table
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $search String to be searched.
	 * @return string $sql The unprepared sql for get_inline_list and get_inline_total_num
	 */
	private static function get_inline_sql( $search = '' ) {
		global $wpdb;
		$wild      = '%';
		$do_search = ( $search ) ? $wpdb->prepare(
			' WHERE inl.`script` LIKE %s ',
			$wild . $wpdb->esc_like( $search ) . $wild
		) : '';

		return '' .
			'SELECT ' .
				'inl.`ID`, ' .
				'inl.`directive`, ' .
				'inl.`tagname`, ' .
				'inl.`script`, ' .
				'inl.`clustername`, ' .
				'inl.`whitelist`, ' .
				'case ' .
					'WHEN `clustername` = \'Unclustered\' THEN occ.pageurls ' .
					'ELSE GROUP_CONCAT(DISTINCT occ.pageurls SEPARATOR \'\\n\') ' . // (DISTINCT occ.pageurls ORDER BY occ.pageurls ASC SEPARATOR \'\\n\')
				'END AS pages, ' .
				'occ.lastseen AS lastseen, ' .
				'COUNT(inl.`id`) AS occurences ' .
			'FROM `' . self::inline_scripts_table() . '` AS inl ' .
				'LEFT OUTER JOIN ( ' .
					'SELECT ' .
						'`itemid`, ' .
						'GROUP_CONCAT(DISTINCT `' . self::occurences_table() . '`.`pageurl` SEPARATOR \'\\n\') AS pageurls, ' . // (DISTINCT `' . self::occurences_table() . '`.`pageurl` ORDER BY `pageurl` ASC SEPARATOR \'\\n\')
						'MAX(`lastseen`) as lastseen ' .
					'FROM `' . self::occurences_table() . '` ' .
					'WHERE `' . self::occurences_table() . '`.`dbtable` = \'inline_scripts\' ' .
					'GROUP BY itemid ' .
				') AS occ ' .
					'ON inl.id = occ.itemid ' .
					$do_search .
			'GROUP BY CASE ' .
				'WHEN clustername <> \'Unclustered\' THEN clustername ' .
				'ELSE `id` ' .
			'END ';
	}

	/**
	 * Get data of inline scripts for admin list table
	 *
	 * @since 1.0.0
	 * @param string $orderby ORDER BY statement.
	 * @param int    $per_page Number of results to retrieve.
	 * @param int    $paged Number of results for OFFSET.
	 * @param string $search String to be searched.
	 * @return array{'ID': int, 'directive': string, 'tagname': string, 'script': string, 'clustername': string,
	 *               'whitelist': int, 'pages': string, 'occurences': int, 'lastseen': int}|null
	 */
	public static function get_inline_list( $orderby, $per_page, $paged, $search = '' ) {
		global $wpdb;

		$sql = self::get_inline_sql( $search );
		$sql = $sql . $orderby;

		$limit = 'LIMIT %d OFFSET %d;';
		$sql   = $sql . $limit;

		$sql_prepared = $wpdb->prepare(
			$sql,
			$per_page,
			$paged
		);

		return $wpdb->get_results( $sql_prepared, ARRAY_A );
	}

	/**
	 * Get num of total query results.
	 *
	 * @since 1.0.0
	 * @param string $search String to be searched.
	 * @return int
	 */
	public static function get_inline_total_num( $search = '' ) {
		global $wpdb;

		$sql = self::get_inline_sql( $search );

		return $wpdb->query( $sql );
	}

	/**
	 * Unprepared sql for external_list table
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $search String to be searched.
	 * @return string $sql The unprepared sql for get_external_list and get_external_total_num
	 */
	private static function get_external_sql( $search = '' ) {
		global $wpdb;
		$wild      = '%';
		$do_search = ( $search ) ? $wpdb->prepare(
			' AND `src_attrib` LIKE %s ',
			$wild . $wpdb->esc_like( $search ) . $wild
		) : '';

		$sql = 'SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM '
			. self::external_scripts_table()
			. ' WHERE ( directive="script-src" or directive="style-src" or directive="worker-src" ) ' . $do_search;
		return $sql;
	}

	/**
	 * Get data of external scripts for admin list table
	 *
	 * @since 1.0.0
	 * @param string $orderby ORDER BY statement.
	 * @param int    $per_page Number of results to retrieve.
	 * @param int    $paged Number of results for OFFSET.
	 * @param string $search String to be searched.
	 * @return array{'ID': int, 'directive': string, 'tagname': string, 'src_attrib': string,
	 *               'sha256': string, 'sha384': string, 'sha512': string, 'whitelist': int}|null
	 */
	public static function get_external_list( $orderby, $per_page, $paged, $search = '' ) {
		global $wpdb;

		$sql = self::get_external_sql( $search );
		$sql = $sql . $orderby;

		$limit = 'LIMIT %d OFFSET %d;';
		$sql   = $sql . $limit;

		$sql_prepared = $wpdb->prepare(
			$sql,
			$per_page,
			$paged
		);

		return $wpdb->get_results( $sql_prepared, ARRAY_A );
	}

	/**
	 * Get num of total query results.
	 *
	 * @since 1.0.0
	 * @param string $search String to be searched.
	 * @return int
	 */
	public static function get_external_total_num( $search = '' ) {
		global $wpdb;

		$sql = self::get_external_sql( $search );

		return $wpdb->query( $sql );
	}

	/**
	 * Unprepared sql for events_list table
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $search String to be searched.
	 * @return string $sql The unprepared sql for get_events_list and get_events_total_num
	 */
	private static function get_events_sql( $search = '' ) {
		global $wpdb;
		$wild      = '%';
		$do_search = ( $search ) ? $wpdb->prepare(
			' WHERE evh.`script` LIKE %s ',
			$wild . $wpdb->esc_like( $search ) . $wild
		) : '';

		return '' .
			'SELECT ' .
				'evh.`ID`, ' .
				'evh.`tagname`, ' .
				'evh.`tagid`, ' .
				'evh.`event_attribute`, ' .
				'evh.`script`, ' .
				'evh.`clustername`, ' .
				'evh.`whitelist`, ' .
				'CASE ' .
					'WHEN `clustername` = \'Unclustered\' THEN occ.pageurls ' .
					'ELSE GROUP_CONCAT(DISTINCT occ.pageurls ORDER BY occ.pageurls ASC SEPARATOR \'\\n\') ' .
				'END AS \'pages\', ' .
				'occ.lastseen AS \'lastseen\', ' .
				'COUNT(evh.`ID`) AS \'occurences\' ' .
			'FROM `' . self::event_handlers_table() . '` AS evh ' .
				'LEFT OUTER JOIN (' .
					'SELECT ' .
						'`itemid`, ' .
						'GROUP_CONCAT(DISTINCT `' . self::occurences_table() . '`.`pageurl` ORDER BY `pageurl` ASC SEPARATOR \'\\n\') AS \'pageurls\', ' .
						'MAX(`lastseen`) as lastseen ' .
					'FROM `' . self::occurences_table() . '` ' .
					'WHERE `' . self::occurences_table() . '`.`dbtable` = \'event_handlers\' ' .
					'GROUP BY itemid ' .
				') AS occ ' .
					'ON evh.ID = occ.itemid ' .
					$do_search .
			'GROUP BY CASE ' .
				'WHEN `clustername` <> \'Unclustered\' THEN `clustername` ' .
				'ELSE `ID` ' .
			'END ';
	}

	/**
	 * Get data of events scripts for admin list table
	 *
	 * @since 1.0.0
	 * @param string $orderby ORDER BY statement.
	 * @param int    $per_page Number of results to retrieve.
	 * @param int    $paged Number of results for OFFSET.
	 * @param string $search String to be searched.
	 * @return array{'ID': int, 'tagname': string, 'tagid': string, 'event_attribute': string, 'script': string,
	 *               'clustername': string, 'whitelist': int, 'pages': string, 'occurences': int, 'lastseen': int}|null
	 */
	public static function get_events_list( $orderby, $per_page, $paged, $search = '' ) {
		global $wpdb;

		$sql = self::get_events_sql( $search );
		$sql = $sql . $orderby;

		$limit = 'LIMIT %d OFFSET %d;';
		$sql   = $sql . $limit;

		$sql_prepared = $wpdb->prepare(
			$sql,
			$per_page,
			$paged
		);

		return $wpdb->get_results( $sql_prepared, ARRAY_A );
	}

	/**
	 * Get num of total query results.
	 *
	 * @since 1.0.0
	 * @param string $search String to be searched.
	 * @return int
	 */
	public static function get_events_total_num( $search = '' ) {
		global $wpdb;

		$sql = self::get_events_sql( $search );

		return $wpdb->query( $sql );
	}

	/**
	 * Delete the entries older than the given number of days.
	 * It has a LIMIT of 1000 to avoid impacting the DB
	 *
	 * @param int $days Number of days.
	 * @return int|bool Number of rows updated or false
	 */
	public static function delete_old_logs( $days ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'DELETE FROM `' . self::logs_table() . '` WHERE `created_at` < DATE_SUB( NOW(), INTERVAL %d DAY) LIMIT 1000', $days );
		return $wpdb->query( $sql );
	}

	/**
	 * Function to get an array of obj from inline_scripts made of
	 * ID and nilsimsa hexDigest
	 *
	 * @since 1.0.0
	 *
	 * @param string $table The scripts table to be clustered: one of inline_scripts or event_handlers.
	 * @param string $segmentation_field Optional: the field used to segment clustering.
	 * @param string $segmentation_value Optional: the value of the field used to segment clustering.
	 * @param string $tagname     Optional: the tagname we want to cluster.
	 * @param string $clustername Optional: the clustername we want to cluster.
	 * @return array<\stdClass>
	 */
	public static function get_nilsimsa_hashes( $table, $segmentation_field = null, $segmentation_value = null, $tagname = null, $clustername = null ) {
		global $wpdb;

		$where = '';

		$limit = 100000;

		$args = array();

		if ( func_num_args() > 0 ) {
			if ( $segmentation_field ) {
				$where  = $where . " $segmentation_field = %s AND";
				$args[] = $segmentation_value;
			}

			if ( $tagname ) {
				$where  = $where . ' tagname = %s AND';
				$args[] = $tagname;
			}

			if ( $clustername ) {
				$where  = $where . ' clustername = %s AND';
				$args[] = $clustername;
			}
		}

		if ( '' !== $where ) {
			$where = substr( $where, 0, strlen( $where ) - 4 );
			$where = ' WHERE ' . $where;
		}
		$args[] = $limit;

		$hashes = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ID, nilsimsa FROM ' . self::with_prefix( $table ) . $where . ' LIMIT %d',
				$args
			),
			OBJECT
		);

		return $hashes;
	}

	/**
	 * Get array of segmentation values in table
	 *
	 * @since 1.0.0
	 *
	 * @param string $segmentation_field The table field where to look for distinct values (directive or event_attribute).
	 * @param string $table The scripts table to be clustered: one of inline_scripts or event_handlers.
	 * @return array<array<string>>
	 */
	public static function get_segmentation_values( $segmentation_field, $table ) {
		global $wpdb;
		return $wpdb->get_results(
			'SELECT DISTINCT `' . $segmentation_field . '` FROM ' . self::with_prefix( $table ),
			ARRAY_A
		);
	}

	/**
	 * Get array of tagnames
	 *
	 * @since 1.0.0
	 *
	 * @param string $segmentation_field The table field where to look for distinct values (directive or event_attribute).
	 * @param string $segment The table field where to look for distinct values .
	 * @param string $table The scripts table to be clustered: one of inline_scripts or event_handlers.
	 * @return array<array{'tagname': string}>
	 */
	public static function get_tagnames( $segmentation_field, $segment, $table ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT DISTINCT `tagname` FROM '
			. self::with_prefix( $table )
			. ' WHERE `' . $segmentation_field . '` = %s',
			$segment
		);
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Update clustername or whitelist in database
	 *
	 * @param string                    $table The scripts table to be clustered: one of inline_scripts or event_handlers.
	 * @param array<string, string|int> $data Data to be update (clustername or whitelist).
	 * @param array<string, int>        $where Where (ID) to update clustername.
	 * @return void
	 */
	public static function update_cluster( $table, $data, $where ) {
		global $wpdb;
		$wpdb->update( self::with_prefix( $table ), $data, $where );
	}

	/**
	 * Get all clusters in table
	 *
	 * @since 1.0.0
	 * @param string $table The table name: one of inline_scripts or event_handlers.
	 * @return array<\stdClass>
	 */
	public static function get_clusters_in_table( $table ) {
		global $wpdb;
		return $wpdb->get_results(
			'SELECT DISTINCT `clustername` FROM '
			. self::with_prefix( $table )
			. ' WHERE `clustername` <> \'Unclustered\''
		);
	}

	/**
	 * Get max (1 or 0) whitelist in cluster
	 *
	 * @param string $table The table name: one of inline_scripts or event_handlers.
	 * @param string $clustername The name of the cluster.
	 * @return string
	 */
	public static function get_max_wl_in_cluster( $table, $clustername ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT MAX(`whitelist`) FROM '
			. self::with_prefix( $table )
			. ' WHERE `clustername` = %s',
			$clustername
		);
		return $wpdb->get_var( $sql );
	}


	/**
	 * Get orphaned occurences ID for table's scripts
	 *
	 * @param string $table The internal table name.
	 * @since 1.0.0
	 * @return array<\stdClass>|null
	 */
	public static function get_orpaned_occurences( $table ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			' SELECT inlineids.occ_id FROM ( '
			. 'SELECT ' . self::occurences_table() . '.`ID` AS \'occ_id\', '
			. self::with_prefix( $table ) . ' .`ID` AS \'scrID\' FROM ' . self::occurences_table()
			. ' LEFT JOIN ' . self::with_prefix( $table ) . ' ON ' . self::occurences_table() . '.`itemid` = ' . self::with_prefix( $table ) . '.`ID` '
			. 'WHERE ' . self::occurences_table() . '.`dbtable` = %s ) AS inlineids WHERE inlineids.scrID IS NULL;',
			$table
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Delete single occurence
	 *
	 * @param string $id The occurence ID.
	 * @since 1.0.0
	 * @return int|false
	 */
	public static function delete_occurence( $id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			'DELETE FROM ' . self::occurences_table() . ' WHERE `ID` = %d',
			$id
		);
		return $wpdb->query( $sql );
	}

	/**
	 * Delete occurences older than n days
	 *
	 * @param int    $days How many days old are occurences to be deleted.
	 * @param string $table The internal table name.
	 * @return int|false
	 */
	public static function delete_old_occurence( $days, $table = '' ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			'DELETE FROM ' . self::occurences_table() . ' ' .
			'WHERE `lastseen` < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))',
			$days
		);
		if ( '' !== $table ) {
			$sql .= $wpdb->prepare(
				' AND `dbtable` = %s',
				$table
			);
		}
		return $wpdb->query( $sql );
	}


	/**
	 * Delete occurence of a single asset
	 *
	 * @param string $table The script full table name.
	 * @param string $asset_id The asset ID.
	 * @since 1.1.3
	 * @return int|false
	 */
	private static function delete_asset_occurences( $table, $asset_id ) {
		global $wpdb;
		$del_sc_occ = 'DELETE FROM ' . self::occurences_table() . ' WHERE `itemid` = %d AND dbtable = %s';
		$del_occur  = $wpdb->query(
			$wpdb->prepare(
				$del_sc_occ,
				$asset_id,
				substr( $table, strlen( $wpdb->prefix . 'nunil_' ) )
			)
		);
		return $del_occur;
	}

	/**
	 * Update the asset ID of an entry in occurences table
	 *
	 * @param string $table The script full table name.
	 * @param string $old_asset_id The old asset ID.
	 * @param string $new_asset_id The new asset ID.
	 * @since 1.1.5
	 * @return int|false
	 */
	private static function update_asset_occurences( $table, $old_asset_id, $new_asset_id ) {
		global $wpdb;
		$upd_sc_occ = 'UPDATE ' . self::occurences_table() . ' SET `itemid` = %d WHERE `itemid` = %d AND dbtable = %s';
		$upd_occur  = $wpdb->query(
			$wpdb->prepare(
				$upd_sc_occ,
				$new_asset_id,
				$old_asset_id,
				substr( $table, strlen( $wpdb->prefix . 'nunil_' ) )
			)
		);
		return $upd_occur;
	}

	/**
	 * Delete occurence of a single external asset
	 *
	 * @param string $asset_id The asset ID.
	 * @since 1.1.3
	 * @return int|false
	 */
	public static function ext_occurences_delete( $asset_id ) {
		$affected = self::delete_asset_occurences( self::external_scripts_table(), $asset_id );
		return $affected;
	}

	/**
	 * Delete occurence of a single inline asset
	 *
	 * @param string $asset_id The asset ID.
	 * @since 1.1.3
	 * @return int|false
	 */
	public static function inl_occurences_delete( $asset_id ) {
		$affected = self::delete_asset_occurences( self::inline_scripts_table(), $asset_id );
		return $affected;
	}

	/**
	 * Delete occurence of a single event handler
	 *
	 * @param string $asset_id The asset ID.
	 * @since 1.1.3
	 * @return int|false
	 */
	public static function evh_occurences_delete( $asset_id ) {
		$affected = self::delete_asset_occurences( self::event_handlers_table(), $asset_id );
		return $affected;
	}

	/**
	 * Update the asset id of an occurence of a single external asset
	 *
	 * @param string $old_asset_id The old asset ID.
	 * @param string $new_asset_id The new asset ID.
	 * @since 1.1.5
	 * @return int|false
	 */
	public static function ext_occurences_update( $old_asset_id, $new_asset_id ) {
		$affected = self::update_asset_occurences( self::external_scripts_table(), $old_asset_id, $new_asset_id );
		return $affected;
	}

	/**
	 * Update the asset id of an occurence of a single inline asset
	 *
	 * @param string $old_asset_id The old asset ID.
	 * @param string $new_asset_id The new asset ID.
	 * @since 1.1.5
	 * @return int|false
	 */
	public static function inl_occurences_update( $old_asset_id, $new_asset_id ) {
		$affected = self::update_asset_occurences( self::inline_scripts_table(), $old_asset_id, $new_asset_id );
		return $affected;
	}

	/**
	 * Update the asset id of an occurence of a single event handler
	 *
	 * @param string $old_asset_id The old asset ID.
	 * @param string $new_asset_id The new asset ID.
	 * @since 1.1.5
	 * @return int|false
	 */
	public static function evh_occurences_update( $old_asset_id, $new_asset_id ) {
		$affected = self::update_asset_occurences( self::event_handlers_table(), $old_asset_id, $new_asset_id );
		return $affected;
	}


	/**
	 * Select clustername in table with numerosity bigger than limit
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $table The internal table name.
	 * @param int    $limit The max allowed numerosity.
	 * @return array<\stdClass>|null
	 */
	public static function get_big_clusters( $table, $limit = 150 ) {
		$tables = array( 'inline_scripts', 'event_handlers' );
		if ( ! in_array( $table, $tables, true ) ) {
			return null;
		}
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT `clustername`, COUNT(`ID`) AS num FROM ' . self::with_prefix( $table ) .
			' GROUP BY `clustername` HAVING num > %d AND `clustername` <> \'Unclustered\';',
			$limit
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Returns cluster numerosity
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $table The internal table name.
	 * @param string $clustername The name of the cluster.
	 * @return int
	 */
	private static function count_cluster_num( $table, $clustername ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::with_prefix( $table ) . ' WHERE `clustername` = %s;',
			$clustername
		);
		return intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * Retuns a list of scripts id to be pruned from database
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $table The internal table name.
	 * @param string $clustername The name of the cluster.
	 * @param int    $maxnum The number of ids not included (the $maxnum lastseen scripts of the cluster).
	 * @return array<\stdClass>|null
	 */
	public static function get_oldest_scripts_id( $table, $clustername, $maxnum = 150 ) {
		global $wpdb;

		$cl_numerosity = self::count_cluster_num( $table, $clustername );
		$limit         = max( 0, $cl_numerosity - $maxnum );

		$sql = $wpdb->prepare(
			'SELECT ' . self::with_prefix( $table ) . '.`ID`, `clustername`, MaxLastseen, `pageurl` FROM '
			. self::with_prefix( $table ) . ' LEFT JOIN ('
			. 'SELECT `dbtable`, `itemid`, `pageurl`, MAX(`lastseen`) AS MaxLastseen FROM ' . self::occurences_table()
			. ' WHERE `dbtable` = %s GROUP BY `itemid` ) AS occurences '
			. 'ON ' . self::with_prefix( $table ) . '.`ID` = occurences.`itemid` WHERE `clustername` = %s ORDER BY MaxLastseen ASC '
			. 'LIMIT %d;',
			$table,
			$clustername,
			$limit
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Returns a list of IDs of assets of plugin's last old version
	 *
	 * @since 1.1.0
	 * @access public
	 * @param string $ver The old version of the plugin.
	 * @return array<\stdClass>|null
	 */
	public static function get_last_nunil_ids( $ver ) {
		global $wpdb;
		$suffix = wp_scripts_get_suffix();
		$wild   = '%';
		$sql    = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::external_scripts_table() . ' WHERE '
			. '`src_attrib` LIKE %s OR '
			. '`src_attrib` LIKE %s OR '
			. '`src_attrib` LIKE %s OR '
			. '`src_attrib` LIKE %s OR '
			. '`src_attrib` LIKE %s;',
			$wild . $wpdb->esc_like( "no-unsafe-inline-fix-style$suffix.js?ver=" . $ver ),
			$wild . $wpdb->esc_like( "no-unsafe-inline-prefilter-override$suffix.js?ver=" . $ver ),
			$wild . $wpdb->esc_like( "no-unsafe-inline-admin$suffix.css?ver=" . $ver ),
			$wild . $wpdb->esc_like( "no-unsafe-inline-admin$suffix.js?ver=" . $ver ),
			$wild . $wpdb->esc_like( "no-unsafe-inline-mutation-observer$suffix.js?ver=" . $ver )
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Set current plugin version in plugin's asset strings in external_scripts
	 *
	 * @since 1.1.0
	 * @access public
	 * @param int    $id The ID of the script in external_scripts.
	 * @param string $old_ver The old version of the plugin.
	 * @param string $new_ver The current version of the plugin.
	 * @return void
	 */
	public static function update_nunil_version( $id, $old_ver, $new_ver ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::external_scripts_table() . ' '
				. 'SET '
				. '`src_attrib` = REPLACE(`src_attrib`, %s, %s ) '
				. 'WHERE (`ID` = %d );',
				$old_ver,
				$new_ver,
				$id
			)
		);
	}

	/**
	 * Deletes old versions nunil assets
	 *
	 * @since 1.1.0
	 * @access public
	 * @param string $ver The old version of the plugin.
	 * @return void
	 */
	public static function delete_legacy_nunil_assets( $ver ): void {
		global $wpdb;
		$suffix = wp_scripts_get_suffix();
		$wild   = '%';
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::external_scripts_table() . ' WHERE ('
				. '`src_attrib` LIKE %s OR '
				. '`src_attrib` LIKE %s OR '
				. '`src_attrib` LIKE %s OR '
				. '`src_attrib` LIKE %s ) AND '
				. '`src_attrib` NOT LIKE %s;',
				$wild . $wpdb->esc_like( "no-unsafe-inline-fix-style$suffix.js" ) . $wild,
				$wild . $wpdb->esc_like( "no-unsafe-inline-prefilter-override$suffix.js" ) . $wild,
				$wild . $wpdb->esc_like( "no-unsafe-inline-admin$suffix.css" ) . $wild,
				$wild . $wpdb->esc_like( "no-unsafe-inline-admin$suffix.js" ) . $wild,
				$wild . $wpdb->esc_like( 'ver=' . $ver )
			)
		);
	}

	/**
	 * Retuns the list of asset id from external table(s)
	 *
	 * @since 1.1.2
	 * @access public
	 * @param string $table The external table name.
	 * @return array<\stdClass>|null
	 */
	public static function get_external_assets_id( $table ) {
		global $wpdb;

		$sql = 'SELECT ' . self::with_prefix( $table ) . '.`ID`, ' . self::with_prefix( $table ) . '.`src_attrib` FROM '
			. self::with_prefix( $table )
			. ' WHERE 1'
			. ' ORDER BY `src_attrib` ASC;';

		return $wpdb->get_results( $sql );
	}

	/**
	 * Resize the src_attrib columns in external_scripts table
	 *
	 * Untill v1.1.3 src_attrib was set to a VARCHAR(255).
	 * MySQL after 5.0.3 support a VARCHAR to be max 64kb; this means that in a VARCHAR can be stored
	 * up to 65535 chars if the charset uses 1 byte per char,
	 * or less if a multi-byte character is in use:
	 *  up to 21844 chars for utf8 (3 bytes per char)
	 *  up to 16383 chars for utf8mb4 (max 4 bytes per char).
	 * However, the whole record has to respect the max row size that in MySQL is 64KB.
	 * WP can generate URLs longer than 255 characters as in wp-admin/load-styles.php?...
	 * The "true" max supported length of an URL is discussed here:
	 * https://stackoverflow.com/questions/417142/what-is-the-maximum-length-of-a-url-in-different-browsers
	 * Using the suggested value of 2000 chars, we colud set src_attrib to VARCHAR(8000) from v1.1.4
	 * One more issue is that src_attrib is indexed:
	 * 767 bytes in MySQL version 5.6 (and prior versions), is the stated prefix limitation for InnoDB tables.
	 * It's 1,000 bytes long for MyISAM tables.
	 * This limit has been increased to 3072 bytes In MySQL version 5.7 (and upwards).
	 * Actual WP recommends MySQL version 8.0 or greater OR MariaDB version 10.4 or greater.
	 * We set `src_attrib` VARCHAR(768) (3072/4).
	 *
	 * @since 1.1.4
	 * @access public
	 * @return void
	 */
	public static function extend_ext_src_attrib_size() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;
		$charset_collate        = $wpdb->get_charset_collate();
		$external_scripts_table = self::external_scripts_table();

		$sql = "CREATE TABLE $external_scripts_table (
				ID INT(11) NOT NULL AUTO_INCREMENT,
				directive VARCHAR(20) NOT NULL,
				tagname VARCHAR(50) NOT NULL,
				src_attrib VARCHAR(768) NOT NULL,
				sha256 CHAR(44) COLLATE ascii_bin,
				sha384 CHAR(64) COLLATE ascii_bin,
				sha512 CHAR(88) COLLATE ascii_bin,
				whitelist TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (ID),
				KEY src_attrib_ind (`src_attrib`)
				) $charset_collate;";
		dbDelta( $sql );
	}

	/**
	 * Returns a list of IDs of external assets of a plugin or theme
	 *
	 * Used to renew external assets in plugins or themes that have
	 * been upgraded.
	 *
	 * @since 1.1.5
	 * @access public
	 * @param string $slug The slug of the plugin or theme.
	 * @return array<object{'ID': string, 'directive': string, 'tagname': string, 'src_attrib': string}>|null
	 */
	public static function get_external_with_slug( $slug ) {
		global $wpdb;
		$wild = '%';
		$sql  = $wpdb->prepare(
			'SELECT `ID`, `directive`, `tagname`, `src_attrib` FROM ' . self::external_scripts_table() . ' WHERE '
			. '`src_attrib` LIKE %s '
			. 'ORDER BY `ID` ASC;',
			site_url() . $wild . $slug . $wild
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Updates the src_attrib of a script in external_scripts table
	 *
	 * @since 1.1.5
	 * @param string $id The id of the script to update.
	 * @param string $new_src_attrib The new src_attrib to set.
	 * @return int|bool
	 */
	public static function update_src_attrib( $id, $new_src_attrib ) {
		global $wpdb;
		return $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::external_scripts_table() .
				' SET `src_attrib` = %s WHERE `ID` = %d',
				$new_src_attrib,
				$id
			)
		);
	}
}
