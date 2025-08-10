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

namespace NUNIL\admin\partials;

use NUNIL\Nunil_Lib_Utils as Utils;
use NUNIL\Nunil_Lib_Log as Log;

if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Requires a core wp file.
	 *
	 * @phpstan-ignore requireOnce.fileNotFound
	 */
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
class No_Unsafe_Inline_Admin_Logs_Table extends \WP_List_Table {
	private const MAX_LENGTH = 1500;

	/** Class constructor */
	public function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'nunil-log',
				'plural'   => 'nunil-logs',
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
		switch ( $column_name ) {
			case 'created_at':
			case 'level':
				return $item[ $column_name ];
			case 'message':
				if ( strlen( $item[ $column_name ] ) > self::MAX_LENGTH ) {
					return substr( $item[ $column_name ], 0, self::MAX_LENGTH ) . '...';
				} else {
					return $item[ $column_name ];
				}
			default:
				Log::debug(
					sprintf(
						// translators: %s is a dumped variable content.
						esc_html__( 'Error in column_default( $item, $column_name ). $item is: %s', 'no-unsafe-inline' ),
						'<pre><code>' . esc_html( print_r( $item, true ) ) . '</code></pre>'
					)
				);
				return;
		}
	}

	/**
	 * Associative array of columns
	 *
	 * @return array<string>
	 */
	public function get_columns() {
		$columns = array(
			'created_at' => esc_html__( 'Created At', 'no-unsafe-inline' ),
			'level'      => esc_html__( 'Level', 'no-unsafe-inline' ),
			'message'    => esc_html__( 'Message', 'no-unsafe-inline' ),
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
			'created_at' => array( 'created_at', true ),
			'level'      => array( 'level', true ),
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
		if ( isset( $_REQUEST['s'] ) ) {
			$search = Utils::sanitize_text( $_REQUEST['s'], false );
		} else {
			$search = '';
		}

		$user   = get_current_user_id();
		$screen = get_current_screen();
		if ( ! is_null( $screen ) ) {
			$screen_option = $screen->get_option( 'per_page', 'option' );
			$per_page      = intval( Utils::cast_intval( get_user_meta( $user, $screen_option, true ) ) );
			if ( $per_page < 1 ) {
				$per_page = intval( $screen->get_option( 'per_page', 'default' ) );
			}
		} else {
			$per_page = 50; // Default value if screen is not set.
		}

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$total_items = \NUNIL\Nunil_Lib_Db::get_total_logs( $search );

		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, intval( Utils::cast_intval( $_REQUEST['paged'] ) ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? Utils::sanitize_text( $_REQUEST['order'], false ) : 'desc';

		try {
			$logs        = \NUNIL\Nunil_Lib_Db::get_logs( $paged * $per_page, $per_page, $orderby, $order, $search, ARRAY_A );
			$this->items = (array) $logs;
		} catch ( \NUNIL\Nunil_Exception $ex ) {
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
