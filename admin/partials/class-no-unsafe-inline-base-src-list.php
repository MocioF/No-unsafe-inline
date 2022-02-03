<?php
/**
 * The class used to render the table in external whitelist tab.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 */

defined( 'ABSPATH' ) || die( 'you do not have acces to this page!' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    No_Unsafe_Inline
 * @subpackage No_Unsafe_Inline/admin
 * @author     Giuseppe Foti <foti.giuseppe@gmail.com>
 */
class No_Unsafe_Inline_Base_Src_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'nunil-ext-source', 'no-unsafe-inline' ),
				'plural'   => __( 'nunil-ext-sources', 'no-unsafe-inline' ),
				'ajax'     => false, // should this table support ajax?
			)
		);

	}

	/**
	 * Retrieve external sources from the database
	 *
	 * @since @1.0.0
	 *
	 * @return mixed
	 */
	public static function get_sources() {

		$basesrc = new NUNIL\Nunil_Base_Src_Rules();

		$result = $basesrc->get_db_entries();
		return $result;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param object|array $item
	 * @param string       $column_name
	 *
	 * @return mixed
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
	 * @param array $item The current item.
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
	 * @return array
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
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = self::get_sources();

	}
}
