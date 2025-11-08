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
use NUNIL\Nunil_Lib_Db as Db;

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
				'ajax'     => true,
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
				switch ( $item[ $column_name ] ) {
					case 'debug':
						return '<span class="nunil-log-debug">' . $item[ $column_name ] . '</span>';
					case 'info':
						return '<span class="nunil-log-info">' . $item[ $column_name ] . '</span>';
					case 'warning':
						return '<span class="nunil-log-warning">' . $item[ $column_name ] . '</span>';
					case 'error':
						return '<span class="nunil-log-error">' . $item[ $column_name ] . '</span>';
					default:
						return $item[ $column_name ];
				}
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
						'<pre><code>' . esc_html( print_r( $item, true ) ) . '</code></pre>' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
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
	 * Sorts an array based on a predefined key order.
	 *
	 * @param string $a First value to compare.
	 * @param string $b Second value to compare.
	 * @return int Comparison result.
	 */
	private function sort_array_on_key( $a, $b ) {
		$arr_key = array(
			0 => 'debug',
			1 => 'info',
			2 => 'warning',
			3 => 'error',
		);
		return array_search( $a, $arr_key ) <=> array_search( $b, $arr_key );
	}

	/**
	 * Renders the search box and the dropdowns.
	 *
	 * @param string $text The text to display in the search box.
	 * @param string $input_id The ID to assign to the search input.
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		$dates  = Db::get_logs_dates();
		$levels = Db::get_logs_levels();

		usort( $levels, array( $this, 'sort_array_on_key' ) );

		$extra = '<div class="nunil-logs-search-container">'
		. '<div class="nunil-logs-search-created_at-div">'
		. '<select name="nunil-log-date" id="nunil-log-date">'
		. sprintf(
			'<option value="" %s>' . esc_html__( 'All dates', 'no-unsafe-inline' ) . '</option>',
			isset( $_REQUEST['nunil-log-date'] ) ? '' : ' selected'
		);

		$operands = array( 'on', 'until', 'since' );

		$posted_date = isset( $_REQUEST['nunil-log-date'] ) ? Utils::sanitize_text( $_REQUEST['nunil-log-date'] ) : '';

		foreach ( $operands as $operand ) {
			switch ( $operand ) {
				case 'on':
					$extra     .= '<optgroup label="' . esc_attr__( 'Logs Dates on', 'no-unsafe-inline' ) . '">';
					$short_html = '=';
					break;
				case 'until':
					$extra     .= '<optgroup label="' . esc_attr__( 'Logs Dates until', 'no-unsafe-inline' ) . '">';
					$short_html = '&le;';
					break;
				case 'since':
					$extra     .= '<optgroup label="' . esc_attr__( 'Logs Dates since', 'no-unsafe-inline' ) . '">';
					$short_html = '&ge;';
					break;
			}

			foreach ( $dates as $date ) {
				if ( $posted_date === $operand . '§' . $date ) {
					$selected_date = ' selected';
				} else {
					$selected_date = '';
				}

				$extra .= sprintf(
					'<option value="' . $operand . '§%s" %s>%s</option>',
					esc_attr( $date ),
					$selected_date,
					" $short_html " . esc_html( $date )
				);
			}
		}

		$extra .= '</select></div>'
		. '<div class="nunil-logs-search-log-level-div">'
		. '<select name="nunil-log-level" id="nunil-log-level">'
		. '<optgroup label="' . esc_attr__( 'Logs Levels', 'no-unsafe-inline' ) . '">';

		$extra .= sprintf(
			'<option value="" %s>' . esc_html__( 'All Levels', 'no-unsafe-inline' ) . '</option>',
			isset( $_REQUEST['nunil-log-level'] ) ? '' : ' selected'
		);

		$posted_level = isset( $_REQUEST['nunil-log-level'] ) ? Utils::sanitize_text( $_REQUEST['nunil-log-level'] ) : '';

		foreach ( $levels as $level ) {
			if ( $posted_level === $level ) {
				$selected_level = ' selected';
			} else {
				$selected_level = '';
			}
			$extra .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $level ),
				$selected_level,
				strtoupper( esc_html( $level ) )
			);
		}
		$extra .= '</select></div>';
		$extra .= '<div class="nunil-logs-searchbox-div">';

		ob_start();
		parent::search_box( $text, $input_id );
		$extra .= ob_get_clean();
		$extra .= '</div></div>';

		echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Prepares the list of items for displaying.
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

		$per_page = isset( $_REQUEST['per_page'] ) ? intval( Utils::cast_intval( Utils::sanitize_text( $_REQUEST['per_page'] ) ) ) : 50;
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$level = isset( $_REQUEST['nunil-log-level'] ) ? Utils::sanitize_text( $_REQUEST['nunil-log-level'] ) : '';
		$date  = isset( $_REQUEST['nunil-log-date'] ) ? Utils::sanitize_text( $_REQUEST['nunil-log-date'] ) : '';

		$total_items = Db::get_total_logs( $search, $level, $date );

		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, intval( Utils::cast_intval( $_REQUEST['paged'] ) ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? Utils::sanitize_text( $_REQUEST['order'], false ) : 'desc';

		try {
			$logs        = Db::get_logs( $paged * $per_page, $per_page, $orderby, $order, $search, $level, $date );
			$this->items = (array) $logs;
		} catch ( \NUNIL\Nunil_Exception $ex ) {
			$ex->logexception();
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => intval( ceil( $total_items / $per_page ) ),
				'orderby'     => $orderby,
				'order'       => $order,
			)
		);
	}

	/**
	 * Displays the table.
	 *
	 * @Override of display method,  and adds a nonce field.
	 *
	 * @since 1.2.4
	 * @return void
	 */
	public function display() {
		/**
		 * Adds a nonce field
		 */
		wp_nonce_field( 'ajax-nunil-logs-nonce', '_ajax_nunil_logs_nonce' );

		/**
		 * Adds field order and orderby
		 */
		echo '<input type="hidden" id="order" name="order" value="' . esc_attr( $this->_pagination_args['order'] ) . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . esc_attr( $this->_pagination_args['orderby'] ) . '" />';
		echo '<input type="hidden" id="per_page" name="per_page" value="' . esc_attr( $this->_pagination_args['per_page'] ) . '" />';

		parent::display();
	}

	/**
	 * Handles an incoming ajax request (called from admin-ajax.php).
	 *
	 * @return never
	 */
	public function ajax_response() {

		check_ajax_referer( 'ajax-nunil-logs-nonce', '_ajax_nunil_logs_nonce' );

		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );


		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();

		$response                         = array( 'rows' => $rows );
		$response['pagination']['top']    = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers']       = $headers;

		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		$encoded = wp_json_encode( $response );

		if ( false === $encoded ) {
			$code         = json_last_error();
			$string_error = esc_html__( 'JSON encoding failed in ', 'no-unsafe-inline' ) . __METHOD__ . ' - json_last_error(): ' . strval( $code );
			Log::error( $string_error );

			$encoded_error = wp_json_encode(
				array(
					'error' => $string_error,
					'code'  => $code,
				)
			);
			wp_send_json_error( $encoded_error, 500 );
		} else {
			wp_send_json_success( $encoded, 200 );
		}
	}
}
