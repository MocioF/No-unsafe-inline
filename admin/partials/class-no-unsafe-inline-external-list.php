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

use NUNIL\Nunil_Lib_Db As DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
class No_Unsafe_Inline_External_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'nunil-ext-script', 'no-unsafe-inline' ),
				'plural'   => __( 'nunil-ext-scripts', 'no-unsafe-inline' ),
				'ajax'     => false, // should this table support ajax?
			)
		);

	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item Query row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="ext-select[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Returns an associative array containing the bulk action.
	 *
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		return array(
			'whitelist-bulk' => __( 'WhiteList', 'no-unsafe-inline' ),
			'hash-bulk'      => __( 'Hash', 'no-unsafe-inline' ),
			'rehash-bulk'    => __( 'Rehash', 'no-unsafe-inline' ),
			'delete-bulk'    => __( 'Delete', 'no-unsafe-inline' ),
		);

	}

	/**
	 * Process bulk actions (and singolar action) during prepare_items.
	 *
	 * @since 1.0.0
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
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
			}
			$action = $this->current_action();

		} elseif ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
				$nonce  = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
				$action = $_GET['action'];
		} else {
				$action = '';
		}

		switch ( $action ) {

			case 'whitelist':
				if ( ! wp_verify_nonce( $nonce, 'whitelist_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id     = intval( $_GET['script_id'] );
					$affected      = DB::ext_whitelist( $script_id );
				}
				break;

			case 'whitelist-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected      = $_POST['ext-select'];
					$affected      = DB::ext_whitelist( $selected );
				}
				break;

			case 'blacklist':
				if ( ! wp_verify_nonce( $nonce, 'whitelist_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id     = intval( $_GET['script_id'] );
					$affected      = DB::ext_whitelist( $script_id, false );
				}
				break;

			case 'blacklist-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected      = $_POST['ext-select'];
					$affected      = DB::ext_whitelist( $selected, false );
				}
				break;

			case 'delete':
				if ( ! wp_verify_nonce( $nonce, 'delete_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id     = intval( $_GET['script_id'] );
					$affected      = DB::ext_delete( $script_id );
				}
				break;

			case 'delete-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected      = $_POST['ext-select'];
					$affected      = DB::ext_delete( $selected );
				}
				break;

			case 'hash':
				if ( ! wp_verify_nonce( $nonce, 'hash_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = intval( $_GET['script_id'] );
					$sri       = new \NUNIL\Nunil_SRI();
					$sri->put_hashes_in_db( $script_id, $overwrite = false );
				}
				break;

			case 'hash-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = $_POST['ext-select'];
					$sri      = new \NUNIL\Nunil_SRI();
					$sri->put_hashes_in_db( $selected, $overwrite = false );
				}
				break;

			case 'rehash':
				if ( ! wp_verify_nonce( $nonce, 'hash_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					if ( ! wp_verify_nonce( $nonce, 'hash_ext_script_nonce' ) ) {
						wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
					}
					$script_id = intval( $_GET['script_id'] );
					$sri       = new \NUNIL\Nunil_SRI();
					$sri->put_hashes_in_db( $script_id, $overwrite = true );
				}
				break;

			case 'rehash-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = $_POST['ext-select'];
					$sri      = new \NUNIL\Nunil_SRI();
					$sri->put_hashes_in_db( $selected, $overwrite = true );
				}
				break;
			default:
				// do nothing or something else
				return;
				break;
		}

		return;
	}


	/**
	 * Render the src_attrib column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_src_attrib( $item ) {

		$admin_page_url = admin_url( 'options-general.php' );

		$actions = array();

		$query_args_hash_ext_script = array(
			'page'      => 'no-unsafe-inline',
			'tab'       => 'external',
			'script_id' => absint( $item['ID'] ),
			'_wpnonce'  => wp_create_nonce( 'hash_ext_script_nonce' ),
		);

		$query_args_hash_ext_script['action'] = 'hash';
		$hash_ext_script_link                 = esc_url( add_query_arg( $query_args_hash_ext_script, $admin_page_url ) );
		$actions['hash']                      = '<a href="' . $hash_ext_script_link . '">' . __( 'Hash', 'no-unsafe-inline' ) . '</a>';

		$query_args_hash_ext_script['action'] = 'rehash';
		$hash_ext_script_link                 = esc_url( add_query_arg( $query_args_hash_ext_script, $admin_page_url ) );
		$actions['rehash']                    = '<a href="' . $hash_ext_script_link . '">' . __( 'Rehash', 'no-unsafe-inline' ) . '</a>';

		$query_args_delete_ext_script = array(
			'page'      => 'no-unsafe-inline',
			'tab'       => 'external',
			'script_id' => absint( $item['ID'] ),
			'_wpnonce'  => wp_create_nonce( 'delete_ext_script_nonce' ),
		);

		$query_args_delete_ext_script['action'] = 'delete';
		$delete_ext_script_link                 = esc_url( add_query_arg( $query_args_delete_ext_script, $admin_page_url ) );
		$actions['delete']                      = '<a href="' . $delete_ext_script_link . '">' . __( 'Delete', 'no-unsafe-inline' ) . '</a>';

		return sprintf( '%1$s %2$s', $item['src_attrib'], $this->row_actions( $actions ) );
	}

	/**
	 * Render the sha256 column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_sha256( $item ) {
		if ( 44 === strlen( $item['sha256'] ) && base64_decode( $item['sha256'] ) ) {
			return '<span class="dashicons dashicons-yes" title="$item[\'sha256\']"></span>';
		} else {
			return '<span class="dashicons dashicons-no-alt"></span>';
		}
	}

	/**
	 * Render the sha384 column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_sha384( $item ) {
		if ( 64 === strlen( $item['sha384'] ) && base64_decode( $item['sha384'] ) ) {
			return '<span class="dashicons dashicons-yes" title="$item[\'sha384\']"></span>';
		} else {
			return '<span class="dashicons dashicons-no-alt"></span>';
		}
	}

	/**
	 * Render the sha512 column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_sha512( $item ) {
		if ( 88 === strlen( $item['sha512'] ) && base64_decode( $item['sha512'] ) ) {
			return '<span class="dashicons dashicons-yes" title="$item[\'sha512\']"></span>';
		} else {
			return '<span class="dashicons dashicons-no-alt"></span>';
		}
	}

	/**
	 * Render the whitelist column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_whitelist( $item ) {

		$admin_page_url = admin_url( 'options-general.php' );

		$actions = array();

		$query_args_whitelist_ext_script = array(
			'page'      => 'no-unsafe-inline',
			'tab'       => 'external',
			'script_id' => absint( $item['ID'] ),
			'_wpnonce'  => wp_create_nonce( 'whitelist_ext_script_nonce' ),
		);

		if ( '0' === $item['whitelist'] ) {
			$query_args_whitelist_ext_script['action'] = 'whitelist';
			$whitelist_ext_script_link                 = esc_url( add_query_arg( $query_args_whitelist_ext_script, $admin_page_url ) );
			$actions['whitelist']                      = '<a href="' . $whitelist_ext_script_link . '">' . __( 'WhiteList', 'no-unsafe-inline' ) . '</a>';
			$wl_text                                   = '<p class="blacklist">' . __( 'BL', 'no-unsafe-inline' ) . '</p>';
		} else {
			$query_args_whitelist_ext_script['action'] = 'blacklist';
			$blacklist_ext_script_link                 = esc_url( add_query_arg( $query_args_whitelist_ext_script, $admin_page_url ) );
			$actions['blacklist']                      = '<a href="' . $blacklist_ext_script_link . '">' . __( 'BlackList', 'no-unsafe-inline' ) . '</a>';
			$wl_text                                   = '<p class="whitelist">' . __( 'WL', 'no-unsafe-inline' ) . '</p>';
		}

		return sprintf( '%1$s %2$s', $wl_text, $this->row_actions( $actions ) );
	}

	/**
	 * Process any column for which no special method is defined.
	 *
	 * @since 1.0.0
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'directive':
			case 'tagname':
			case 'src_attrib':
			case 'sha256':
			case 'sha384':
			case 'sha512':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'directive'  => array( 'directive', true ),
			'tagname'    => array( 'tagname', false ),
			'src_attrib' => array( 'src_attrib', false ),
			'whitelist'  => array( 'whitelist', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'directive'  => __( 'Directive', 'no-unsafe-inline' ),
			'tagname'    => __( 'TagName', 'no-unsafe-inline' ),
			'src_attrib' => __( 'Resource', 'no-unsafe-inline' ),
			'sha256'     => __( 'sha256', 'no-unsafe-inline' ),
			'sha384'     => __( 'sha384', 'no-unsafe-inline' ),
			'sha512'     => __( 'sha512', 'no-unsafe-inline' ),
			'whitelist'  => __( 'WhiteList', 'no-unsafe-inline' ),

			// Aggiungi i parametri calcolati per Frequenza e Attendibilità
		);

		return $columns;
	}

	/**
	 * Defines two arrays controlling the behaviour of the table.
	 *
	 * @since 1.0.0
	 */
	function prepare_items() {
		global $wpdb;

		$tbl_ext = NO_UNSAFE_INLINE_TABLE_PREFIX . 'external_scripts';

		$search    = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;
		$do_search = ( $search ) ? $wpdb->prepare( " AND `src_attrib` LIKE '%%%s%%' ", $search ) : '';

		$sql = "SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM $tbl_ext "
			 . "WHERE ( `tagname`='link' OR `tagname`='script' ) $do_search ";

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$per_page = 20;
		$paged    = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;

		$order = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'ASC', 'DESC', 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'ASC';

		$orderby = 'ORDER BY ';

		if ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) {

			switch ( $_REQUEST['orderby'] ) {
				case 'directive':
					$orderby .= "directive $order ";
					break;
				case 'tagname':
					$orderby .= "tagname $order ";
					break;
				case 'src_attrib':
					$orderby .= "src_attrib $order ";
					break;
				case 'whitelist':
					$orderby .= "whitelist $order ";
					break;
			}
		} else {
			$orderby .= 'whitelist ASC ';
		}

		$sql .= $orderby;

		$total_items = $wpdb->query( $sql );

		$limit    = 'LIMIT %d OFFSET %d;';
		$sql     .= $limit;
		$prepared = $wpdb->prepare( $sql, $per_page, $paged );

		$data = $wpdb->get_results( $prepared, ARRAY_A );

		$current_page = $this->get_pagenum();

		$this->items = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
