<?php
/**
 * The class used to render the logs table.
 * This code is from https://github.com/perfectyorg/perfecty-push-wp
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The class used to render the the logs table.
 *
 * Extends WP_List_Table to show plugin logs.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */
class No_Unsafe_Inline_Admin_Logs_Table extends WP_List_Table {
	private const MAX_LENGTH = 1500;

	/** Class constructor */
	public function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'log',
				'plural'   => 'logs',
			)
		);
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
		$content = $item['created_at'] . ' | ' . strtoupper( $item['level'] ) . ' | ' . $item['message'];

		if ( is_string( $content ) && strlen( $content ) > self::MAX_LENGTH ) {
			return substr( $content, 0, self::MAX_LENGTH ) . '...';
		} else {
			return $content;
		}
	}

	/**
	 * Associative array of columns
	 *
	 * @return array<string>
	 */
	public function get_columns() {
		$columns = array(
			'entry' => esc_html__( 'Log entries', 'no-unsafe-inline' ),
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
			'entry' => array( 'created_at', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Defines two arrays controlling the behaviour of the table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 500;

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$total_items = \NUNIL\Nunil_Lib_Db::get_total_logs();

		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		try {
			$logs        = NUNIL\Nunil_Lib_Db::get_logs( $paged * $per_page, $per_page, $orderby, $order, ARRAY_A );
			$this->items = (array) $logs;
		} catch ( NUNIL\Nunil_Exception $ex ) {
			$ex->logexception();
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => intval( ceil( $total_items / $per_page ) ),
			)
		);
	}
}
