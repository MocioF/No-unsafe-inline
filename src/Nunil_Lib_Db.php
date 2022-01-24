<?php

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
		return $wpdb->prefix . $table;
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

		$db_version = get_option( 'nunil_db_version' );

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
				KEY sha512_ind (`sha512`),
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
				KEY sha256_ind (`sha256`)
				KEY sha384_ind (`sha384`)
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
			update_option( 'no_unsafe_inline_db_version', NO_UNSAFE_INLINE_DB_VERSION );
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

		$sql = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::occurences_table() . ' WHERE `dbtable`=%s AND `itemid`=%d AND `pageurl`=%s',
			$tbl_string,
			intval( $id ),
			// ~ Nunil_Lib_Utils::get_page_url()
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
	 * @param string $directive The -src csp directive.
	 * @param string $tagname The html tagname.
	 * @param string $hash The sha supported hash of the js event script.
	 * @return int|null
	 */
	public static function get_inl_id( $directive, $tagname, $hash ) {
		global $wpdb;
		$sql    = $wpdb->prepare(
			'SELECT `ID` FROM ' . self::inline_scripts_table() . ' WHERE `directive`=%s AND `tagname`=%s AND ' . self::src_hash( $hash ),
			$directive,
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
}

