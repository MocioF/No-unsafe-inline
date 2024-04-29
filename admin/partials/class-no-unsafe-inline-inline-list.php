<?php
/**
 * The class used to render the table in inline whitelist tab.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

use Highlight\Highlighter;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The class used to render the table in inline whitelist tab.
 *
 * Extends WP_List_Table to select inline scripts.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */
class No_Unsafe_Inline_Inline_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'nunil-inl-script',
				'plural'   => 'nunil-inl-scripts',
				'ajax'     => false, // should this table support ajax?
			)
		);
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array<string> $item Query row.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="inl-select[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Returns an associative array containing the bulk action.
	 *
	 * @since 1.0.0
	 * @return array<string>
	 */
	public function get_bulk_actions() {
		return array(
			'whitelist-bulk' => __( 'WhiteList', 'no-unsafe-inline' ),
			'blacklist-bulk' => __( 'BlackList', 'no-unsafe-inline' ),
			'delete-bulk'    => __( 'Delete', 'no-unsafe-inline' ),
		);
	}

	/**
	 * Process bulk actions (and singolar aciont) during prepare_items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_bulk_action() {
		/**
		 * Security check for bulk actions.
		 * For single actions, I will check nonce insede switch because
		 * it isn't in the post array.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'User is not allowed to perform this action', 'no-unsafe-inline' ) );
		}
		if ( isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: We are not processing form information; $nonce is used only for wp_verify_nonce
			$nonce  = wp_unslash( $_POST['_wpnonce'] );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
			}
			$action = $this->current_action();
		} elseif ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: We are not processing form information; $nonce is used only for wp_verify_nonce
				$nonce  = wp_unslash( $_GET['_wpnonce'] );
				$action = ( isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '' );
		} else {
				$action = '';
				$nonce  = '';
		}

		switch ( $action ) {
			case 'whitelist':
				if ( ! wp_verify_nonce( $nonce, 'whitelist_inl_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::inl_whitelist( $script_id );
				}
				break;

			case 'whitelist-bulk':
				if ( isset( $_POST['inl-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['inl-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::inl_whitelist( $selected );
					}
				}
				break;

			case 'blacklist':
				if ( ! wp_verify_nonce( $nonce, 'whitelist_inl_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::inl_whitelist( $script_id, false );
				}
				break;

			case 'blacklist-bulk':
				if ( isset( $_POST['inl-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['inl-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::inl_whitelist( $selected, false );
					}
				}
				break;

			case 'delete':
				if ( ! wp_verify_nonce( $nonce, 'delete_inl_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::inl_delete( $script_id );
				}
				break;

			case 'delete-bulk':
				if ( isset( $_POST['inl-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['inl-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::inl_delete( $selected );
					}
				}
				break;

			case 'uncluster':
				if ( ! wp_verify_nonce( $nonce, 'uncluster_inl_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::inl_uncluster( $script_id );
				}
				break;

			default:
				break;
		}
	}

	/**
	 * Render the script column
	 *
	 * @param array<string> $item Item with row's data.
	 * @return string
	 */
	public function column_script( $item ) {
		$admin_page_url = admin_url( 'options-general.php' );

		$hl = new Highlighter();
		$hl->setAutodetectLanguages( array( 'javascript', 'css', 'json', 'wasm' ) );
		$highlighted = $hl->highlightAuto( $item['script'] );
		$code        = '<div class="nunil-code-wrapper-' . $item['ID'] . '"><div class="code-accordion-' . $item['ID'] . '"><h4>' . esc_html__( 'View code', 'no-unsafe-inline' ) . '</h4>';
		$code       .= "<div><pre class=\"nunil-script-code\"><code class=\"hljs {$highlighted->language}\">";
		$code       .= $highlighted->value;
		$code       .= '</code></pre></div></div></div>';

		$actions = array();
		// row action to delete inline script.

		$query_args_delete_inl_script = array(
			'page'      => 'no-unsafe-inline',
			'tab'       => 'inline',
			'action'    => 'delete',
			'script_id' => absint( $item['ID'] ),
			'_wpnonce'  => wp_create_nonce( 'delete_inl_script_nonce' ),
		);

		$delete_inl_script_link = esc_url( add_query_arg( $query_args_delete_inl_script, $admin_page_url ) );

		$actions['delete'] = '<a href="' . $delete_inl_script_link . '">' . __( 'Delete', 'no-unsafe-inline' ) . '</a>';

		return sprintf( '%1$s %2$s', $code, $this->row_actions( $actions ) );
	}

	/**
	 * Render the whitelist column
	 *
	 * @param array<string> $item Item with row's data.
	 * @return string
	 */
	public function column_whitelist( $item ) {
		$admin_page_url = admin_url( 'options-general.php' );

		$actions = array();

		$query_args_whitelist_inl_script = array(
			'page'      => 'no-unsafe-inline',
			'tab'       => 'inline',
			'script_id' => absint( $item['ID'] ),
			'_wpnonce'  => wp_create_nonce( 'whitelist_inl_script_nonce' ),
		);

		if ( '0' === $item['whitelist'] ) {
			$query_args_whitelist_inl_script['action'] = 'whitelist';
			$whitelist_inl_script_link                 = esc_url( add_query_arg( $query_args_whitelist_inl_script, $admin_page_url ) );
			$actions['whitelist']                      = '<a href="' . $whitelist_inl_script_link . '">' . __( 'WhiteList', 'no-unsafe-inline' ) . '</a>';
			$wl_text                                   = '<p class="blacklist">' . __( 'BL', 'no-unsafe-inline' ) . '</p>';
		} else {
			$query_args_whitelist_inl_script['action'] = 'blacklist';
			$blacklist_inl_script_link                 = esc_url( add_query_arg( $query_args_whitelist_inl_script, $admin_page_url ) );
			$actions['blacklist']                      = '<a href="' . $blacklist_inl_script_link . '">' . __( 'BlackList', 'no-unsafe-inline' ) . '</a>';
			$wl_text                                   = '<p class="whitelist">' . __( 'WL', 'no-unsafe-inline' ) . '</p>';
		}

		return sprintf( '%1$s %2$s', $wl_text, $this->row_actions( $actions ) );
	}

	/**
	 * Render the clustername column
	 *
	 * @param array<string> $item Item with row's data.
	 * @return string
	 */
	public function column_clustername( $item ) {
		$admin_page_url = admin_url( 'options-general.php' );

		if ( 'Unclustered' !== $item['clustername'] ) {
			$actions = array();

			$query_args_uncluster_inl_script = array(
				'page'      => 'no-unsafe-inline',
				'tab'       => 'inline',
				'action'    => 'uncluster',
				'script_id' => absint( $item['ID'] ),
				'_wpnonce'  => wp_create_nonce( 'uncluster_inl_script_nonce' ),
			);

			$uncluster_inl_script_link = esc_url( add_query_arg( $query_args_uncluster_inl_script, $admin_page_url ) );

			$actions['uncluster'] = '<a href="' . $uncluster_inl_script_link . '">' . __( 'Uncluster', 'no-unsafe-inline' ) . '</a>';

			return sprintf( '%1$s %2$s', $item['clustername'], $this->row_actions( $actions ) );
		} else {
			return $item['clustername'];
		}
	}

	/**
	 * Render the pages column
	 *
	 * @param array<string> $item Item with row's data.
	 * @return string
	 */
	public function column_pages( $item ) {
		$hl          = new Highlighter();
		$highlighted = $hl->highlightAuto( $item['pages'] );
		$code        = '<div class="pages-wrapper-' . $item['ID'] . '"><div class="pages-accordion-' . $item['ID'] . '"><h5>' . esc_html__( 'View pages', 'no-unsafe-inline' ) . '</h5>';
		$code       .= "<div><pre class=\"nunil-pages-code\"><code class=\"hljs {$highlighted->language}\">";
		$code       .= $highlighted->value;
		$code       .= '</code></pre></div></div></div>';

		return $code;
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
			case 'directive':
			case 'tagname':
			case 'clustername':
			case 'whitelist':
			case 'lastseen':
			case 'occurences':
				return $item[ $column_name ];
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
	 * Columns to make sortable.
	 *
	 * @return array<string, array<int, bool|string>>
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'directive'   => array( 'directive', true ),
			'tagname'     => array( 'tagname', false ),
			'clustername' => array( 'clustername', false ),
			'whitelist'   => array( 'whitelist', false ),
			'occurences'  => array( 'occurences', true ),
			'lastseen'    => array( 'lastseen', true ),

		);

		return $sortable_columns;
	}

	/**
	 * Associative array of columns
	 *
	 * @return array<string>
	 */
	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'script'      => __( 'Script', 'no-unsafe-inline' ),
			'directive'   => __( 'Directive', 'no-unsafe-inline' ),
			'tagname'     => __( 'TagName', 'no-unsafe-inline' ),
			'clustername' => __( 'Cluster', 'no-unsafe-inline' ),
			'occurences'  => __( 'Cl.\'s Numerosity', 'no-unsafe-inline' ),
			'whitelist'   => __( 'WhiteList', 'no-unsafe-inline' ),
			'pages'       => __( 'Pages', 'no-unsafe-inline' ),
			'lastseen'    => __( 'Last Seen', 'no-unsafe-inline' ),
		);

		return $columns;
	}

	/**
	 * Defines two arrays controlling the behaviour of the table.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prepare_items() {
		if ( isset( $_REQUEST['s'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		} else {
			$search = '';
		}

		$this->_column_headers = $this->get_column_info();

		$this->process_bulk_action();

		$user   = get_current_user_id();
		$screen = get_current_screen();
		if ( ! is_null( $screen ) ) {
			$screen_option = $screen->get_option( 'per_page', 'option' );
			$per_page      = intval( Utils::cast_intval( get_user_meta( $user, $screen_option, true ) ) );
			if ( $per_page < 1 ) {
				$per_page = intval( $screen->get_option( 'per_page', 'default' ) );
			}
		} else {
			$per_page = 20;
		}

		$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;

		$order = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'ASC', 'DESC', 'asc', 'desc' ), true ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'ASC';

		$orderby = 'ORDER BY ';

		if ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ) {
			switch ( $_REQUEST['orderby'] ) {
				case 'directive':
					$orderby .= "directive $order ";
					break;
				case 'tagname':
					$orderby .= "tagname $order ";
					break;
				case 'clustername':
					$orderby .= "clustername $order ";
					break;
				case 'whitelist':
					$orderby .= "whitelist $order ";
					break;
				case 'occurences':
					$orderby .= "occurences $order ";
					break;
				case 'lastseen':
					$orderby .= "lastseen $order ";
					break;
				default:
					$orderby .= "directive $order ";
			}
		} else {
			$orderby .= 'whitelist ASC ';
		}

		$total_items = DB::get_inline_total_num( $search );

		$data = DB::get_inline_list( $orderby, $per_page, $paged, $search );

		$current_page = $this->get_pagenum();

		if ( ! is_null( $data ) ) {
			$this->items = $data;
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
