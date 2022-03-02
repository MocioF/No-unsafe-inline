<?php
/**
 * Database Lib Class
 *
 * Class has static methods to operate DB operations.
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

/**
 * Class with static methods called while operating on db
 *
 * @package no-unsafe-inline
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
				src_attrib VARCHAR(255) NOT NULL,
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
		$sql = $wpdb->prepare(
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
		$data   = array(
			'lastseen' => wp_date( 'Y-m-d H:i:s' ),
		);
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
		$sql    = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::inline_scripts_table() . ' WHERE `tagname`=%s AND ' . self::src_hash( $hash ),
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
	 * @param  bool   $sticky  True if the script is sticky to the pages.
	 *  Sticky (whitelisted) scripts will always be
	 *  inserted in CSP for the page.
	 * @return int The ID of inserted row
	 */
	public static function insert_inl_in_db( $directive, $tagname, $content, $sticky ) {
		global $wpdb;

		$lsh = new \Beager\Nilsimsa( $content );

		$data = array(
			'directive' => $directive,
			'tagname'   => $tagname,
			'script'    => $content,
			'sha256'    => Nunil_Capture::calculate_hash( 'sha256', $content, $utf8 = true ),
			'sha384'    => Nunil_Capture::calculate_hash( 'sha384', $content, $utf8 = true ),
			'sha512'    => Nunil_Capture::calculate_hash( 'sha512', $content, $utf8 = true ),
			'nilsimsa'  => $lsh->hexDigest(),
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
			'dbtable'  => $tbl_string,
			'itemid'   => intval( $id ),
			'pageurl'  => $page_url,
			'lastseen' => wp_date( 'Y-m-d H:i:s' ),
		);

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
	 * @throws \Exception If $order_by parameter is invalid.
	 */
	public static function get_logs( $offset, $size, $order_by = 'created_at', $order_asc = 'desc', $mode = OBJECT ) {
		global $wpdb;

		if ( strpos( self::$allowed_logs_fields, $order_by ) === false ) {
			throw new \Exception( "The order by [$order_by] field is not allowed" );
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
	 * Get a random ID from $table
	 *
	 * Get a (not truly) random ID from $table assuming that
	 * distribution of ids is equal, and that there can be gaps in
	 * the ID list.
	 * See: http://jan.kneschke.de/projects/mysql/order-by-rand/
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $table The internal table name.
	 * @return array<string>|null
	 * @throws \Exception If called method is not found.
	 */
	public static function get_random_cluster_data( $table ) {
		global $wpdb;
		$callable = array( __CLASS__, $table . '_table' );

		if ( is_callable( $callable ) ) {
			$my_table = call_user_func( $callable );
		} else {
			throw new \Exception( 'Method not found' );
		}

		$sql = 'SELECT `clustername` AS \'exp_label\', `nilsimsa` AS \'hexDvalue\'
			FROM ' . $my_table . ' AS r1 JOIN
				(SELECT CEIL(RAND() *
                     (SELECT MAX(ID)
                        FROM ' . $my_table . ')) AS id)
				AS r2
			WHERE r1.id >= r2.id
			ORDER BY r1.id ASC
			LIMIT 1';
		$row = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_null( $row ) ) {
			return $row[0];
		} else {
			return null;
		}
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
	 * @param string|array<string> $id The inline script id or an ARRAY_N of script_id.
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
	public static function delete( $table, $id, $delete_occurences ) {
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
							$in_str = $in_str . "('" . substr( $table, strlen( $wpdb->prefix . 'nunil_' ) ) . "', $rid->id), ";
						}
						$in_str = substr( $in_str, 0, strlen( $in_str ) - 2 );
						$in_str = $in_str . ')';

						$del_cl_occur = 'DELETE FROM ' . $table . ' WHERE (`dbtable`,`itemid`) IN $in_str';
						$del_occur    = $wpdb->query( $del_cl_occur );
					}
				}
			} else {
				$del_sc   = 'DELETE FROM ' . $table . ' WHERE `ID` = %d';
				$affected = $affected + $wpdb->query(
					$wpdb->prepare(
						$del_sc,
						$id
					)
				);
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
		$result = json_encode( array() );
		switch ( $table ) {
			case 'global':
			case 'nunil_global':
				$result = $wpdb->get_results(
					'SELECT \'External Scripts\' AS \'type\', COUNT(`ID`) AS \'num\', '
					. '`whitelist`, \'--\' AS \'clusters\' FROM ' . self::external_scripts_table() . ' '
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
					. 'WHEN `tagname` =\'script\' THEN \'' . esc_html__( 'Yes', 'no-unsafe-inline' ) . '\' '
					. 'WHEN `tagname` =\'link\' THEN \'' . esc_html__( 'Yes', 'no-unsafe-inline' ) . '\' '
					. 'ELSE \'' . esc_html__( 'No', 'no-unsafe-inline' ) . '\' '
					. 'END AS \'nonceable\', '
					. 'CASE '
					. 'WHEN `tagname` =\'script\' THEN `whitelist` '
					. 'WHEN `tagname` =\'link\' THEN `whitelist` '
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
		$sql = 'SELECT sha256, sha384, sha512, nilsimsa, clustername, whitelist, tagname FROM ' . self::inline_scripts_table();
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
		$sql = 'SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM `' . self::external_scripts_table() . '`'
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
	 * Get samples for classification test.
	 *
	 * @since 1.0.0
	 * @param string $tagname The HTML tag to get samples for.
	 * @return array<\stdClass>
	 */
	public static function get_classification_samples( $tagname = '' ) {
		global $wpdb;
		$sql = 'SELECT `nilsimsa` AS \'hexDigest\', `clustername`, `whitelist` FROM ' . self::inline_scripts_table()
		. ' WHERE (`whitelist` = true AND `clustername` <> \'Unclustered\' ) OR `clustername` = \'Unclustered\'';

		if ( '' !== $tagname ) {
			$sql = $sql . ' AND `tagname` = %s';
			$sql = $wpdb->prepare( $sql, $tagname );
		}

		return $wpdb->get_results( $sql );
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
		$do_search = ( '' !== $search ) ? $wpdb->prepare( ' WHERE inl.`script` LIKE \'%%%s%%\' ', $search ) : '';

		return 'SELECT inl.`ID`, inl.`directive`, inl.`tagname`, inl.`script`, inl.`clustername`, inl.`whitelist`, '
			. '(CASE WHEN `clustername` = \'Unclustered\' THEN occ.pageurls ELSE '
			. ' GROUP_CONCAT(DISTINCT occ.pageurls ORDER BY occ.pageurls ASC SEPARATOR \'\\n\') END) AS \'pages\', '
			. ' occ.lastseen AS \'lastseen\', COUNT(inl.`id`) AS \'occurences\' '
			. 'FROM `' . self::inline_scripts_table() . '` AS inl LEFT JOIN '
			. '    (SELECT `itemid`, GROUP_CONCAT(DISTINCT `' . self::occurences_table() . '`.`pageurl` ORDER BY `pageurl` ASC SEPARATOR \'\\n\') AS \'pageurls\', '
			. '    MAX(`lastseen`) as lastseen'
			. '    FROM `' . self::occurences_table() . '` '
			. '    WHERE '
			. '    `' . self::occurences_table() . '`.`dbtable` = \'inline_scripts\' '
			. '    GROUP BY itemid) AS occ '
			. 'ON inl.id = occ.itemid '
			. $do_search
			. 'GROUP BY (CASE WHEN `clustername` <> \'Unclustered\' THEN `clustername` ELSE `id` END) ';
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
		$do_search = ( $search ) ? $wpdb->prepare( ' AND `src_attrib` LIKE \'%%%s%%\' ', $search ) : '';

		return 'SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM ' . self::external_scripts_table()
			 . ' WHERE 1 ' . $do_search;
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
		$do_search = ( $search ) ? $wpdb->prepare( ' WHERE evh.`script` LIKE \'%%%s%%\' ', $search ) : '';

		return 'SELECT evh.`ID`, evh.`tagname`, evh.`tagid`, evh.`event_attribute`, evh.`script`, evh.`clustername`, evh.`whitelist`, '
			 . '(CASE WHEN `clustername` = \'Unclustered\' THEN occ.pageurls ELSE '
			 . ' GROUP_CONCAT(DISTINCT occ.pageurls ORDER BY occ.pageurls ASC SEPARATOR \'\\n\') END) AS \'pages\', '
			 . ' occ.lastseen AS \'lastseen\', COUNT(evh.`ID`) AS \'occurences\' '
			 . 'FROM `' . self::event_handlers_table() . '` AS evh LEFT JOIN '
			 . '    (SELECT `itemid`, GROUP_CONCAT(DISTINCT `' . self::occurences_table() . '`.`pageurl` ORDER BY `pageurl` ASC SEPARATOR \'\\n\') AS \'pageurls\', '
			 . '    MAX(`lastseen`) as lastseen'
			 . '    FROM `' . self::occurences_table() . '` '
			 . '    WHERE '
			 . '    `' . self::occurences_table() . '`.`dbtable` = \'event_handlers\' '
			 . '    GROUP BY itemid) AS occ '
			 . 'ON evh.ID = occ.itemid '
			 . $do_search
			 . 'GROUP BY (CASE WHEN `clustername` <> \'Unclustered\' THEN `clustername` ELSE `ID` END) ';
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

}
