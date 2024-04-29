<?php
/**
 * The class used to render the helper table in base rules tab.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

defined( 'ABSPATH' ) || die( 'you do not have acces to this page!' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The class used to render the helper table in base rules tab.
 *
 * Extends WP_List_Table to show plugin logs.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */
class No_Unsafe_Inline_Base_Rule_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'nunil-ext-source',
				'plural'   => 'nunil-ext-sources',
				'ajax'     => false, // should this table support ajax?
			)
		);
	}

	/**
	 * Retrieve external sources from the database
	 *
	 * @since @1.0.0
	 *
	 * @return array<array{ID: int, directive: string, source: string}>>
	 */
	public static function get_sources() {
		$basesrc = new NUNIL\Nunil_Base_Src_Rules();

		$result = $basesrc->get_db_entries();
		return $result;
	}

	/**
	 * Process any column for which no special method is defined.
	 *
	 * @since 1.0.0
	 * @param array<string> $item Data in row.
	 * @param string        $column_name Column name.
	 * @return string|void
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'nunil-ext-directive':
				return $item['directive'];
			case 'nunil-ext-source':
				return $item['source'];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes.
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array<string> $item Query row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-select[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Associative array of columns
	 *
	 * @return array<string>
	 */
	public function get_columns() {
		$columns = array(
			'cb'                  => '<input type="checkbox" />',
			'nunil-ext-directive' => __( 'directive', 'no-unsafe-inline' ),
			'nunil-ext-source'    => __( 'source', 'no-unsafe-inline' ),
		);

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array<string, array<int, bool|string>>
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'nunil-ext-directive' => array( 'directive', true ),
			'nunil-ext-source'    => array( 'source', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		try {
			$this->items = self::get_sources();
		} catch ( NUNIL\Nunil_Exception $ex ) {
			$ex->logexception();
		}
	}
}
