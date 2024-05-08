<?php
/**
 * The class used to render the table in external whitelist tab.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;
use NUNIL\Nunil_SRI;
use NUNIL\Nunil_Exception;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The class used to render the table in external whitelist tab.
 *
 * Extends WP_List_Table to select external scripts.
 *
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */
class No_Unsafe_Inline_External_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'nunil-ext-script',
				'plural'   => 'nunil-ext-scripts',
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
			'<input type="checkbox" name="ext-select[]" value="%s" />',
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
			'hash-bulk'      => __( 'Hash', 'no-unsafe-inline' ),
			'rehash-bulk'    => __( 'Rehash', 'no-unsafe-inline' ),
			'delete-bulk'    => __( 'Delete', 'no-unsafe-inline' ),
		);
	}

	/**
	 * Process bulk actions (and singolar action) during prepare_items.
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
				if ( ! wp_verify_nonce( $nonce, 'whitelist_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::ext_whitelist( $script_id );
				}
				break;

			case 'whitelist-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['ext-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::ext_whitelist( $selected );
					}
				}
				break;

			case 'blacklist':
				if ( ! wp_verify_nonce( $nonce, 'whitelist_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::ext_whitelist( $script_id, false );
				}
				break;

			case 'blacklist-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['ext-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::ext_whitelist( $selected, false );
					}
				}
				break;

			case 'delete':
				if ( ! wp_verify_nonce( $nonce, 'delete_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					$affected  = DB::ext_delete( $script_id );
				}
				break;

			case 'delete-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['ext-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						$affected = DB::ext_delete( $selected );
					}
				}
				break;

			case 'hash':
				if ( ! wp_verify_nonce( $nonce, 'hash_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					try {
						$sri = new Nunil_SRI();
						$sri->put_hashes_in_db( $script_id, $overwrite = false );
					} catch ( Nunil_Exception $ex ) {
						$ex->logexception();
					}
				}
				break;

			case 'hash-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['ext-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						try {
							$sri = new Nunil_SRI();
							$sri->put_hashes_in_db( $selected, $overwrite = false );
						} catch ( Nunil_Exception $ex ) {
							$ex->logexception();
						}
					}
				}
				break;

			case 'rehash':
				if ( ! wp_verify_nonce( $nonce, 'hash_ext_script_nonce' ) ) {
					wp_die( esc_html__( 'Nope! Security check failed!', 'no-unsafe-inline' ) );
				}
				if ( isset( $_GET['script_id'] ) ) {
					$script_id = sanitize_text_field( wp_unslash( $_GET['script_id'] ) );
					try {
						$sri = new Nunil_SRI();
						$sri->put_hashes_in_db( $script_id, $overwrite = true );
					} catch ( Nunil_Exception $ex ) {
						$ex->logexception();
					}
				}
				break;

			case 'rehash-bulk':
				if ( isset( $_POST['ext-select'] ) ) {
					$selected = map_deep( wp_unslash( $_POST['ext-select'] ), 'sanitize_text_field' );
					if ( is_array( $selected ) && Utils::is_array_of_integer_strings( $selected ) ) {
						try {
							$sri = new Nunil_SRI();
							$sri->put_hashes_in_db( $selected, $overwrite = true );
						} catch ( Nunil_Exception $ex ) {
							$ex->logexception();
						}
					}
				}
				break;
			default:
				break;
		}
		Utils::set_last_modified( 'inline_scripts' );
	}


	/**
	 * Render the src_attrib column
	 *
	 * @param array<string> $item Item with row's data.
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

		if ( Utils::is_resource_hash_needed( $item['directive'], $item['tagname'] ) ) {
			$query_args_hash_ext_script['action'] = 'hash';
			$hash_ext_script_link                 = esc_url( add_query_arg( $query_args_hash_ext_script, $admin_page_url ) );
			$actions['hash']                      = '<a href="' . $hash_ext_script_link . '">' . __( 'Hash', 'no-unsafe-inline' ) . '</a>';

			$query_args_hash_ext_script['action'] = 'rehash';
			$hash_ext_script_link                 = esc_url( add_query_arg( $query_args_hash_ext_script, $admin_page_url ) );
			$actions['rehash']                    = '<a href="' . $hash_ext_script_link . '">' . __( 'Rehash', 'no-unsafe-inline' ) . '</a>';
		}

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
	 * @param array{ID: int, directive:string, tagname:string, src_attrib:string, sha256:string|null, sha384:string|null, sha512:string|null, whitelist:int} $item Item with row's data.
	 *
	 * @return string
	 */
	public function column_sha256( $item ) {
		if ( Utils::is_resource_hash_needed( $item['directive'], $item['tagname'] ) ) {
			if ( is_null( $item['sha256'] ) ) {
				return '<span class="dashicons dashicons-minus"></span>';
			}
			if ( 44 === strlen( $item['sha256'] ) && base64_decode( $item['sha256'] ) ) {
				return '<span class="dashicons dashicons-yes" title="$item[\'sha256\']"></span>';
			} else {
				return '<span class="dashicons dashicons-no-alt"></span>';
			}
		} else {
			return '<span class="dashicons dashicons-minus"></span>';
		}
	}

	/**
	 * Render the sha384 column
	 *
	 * @param array{ID: int, directive:string, tagname:string, src_attrib:string, sha256:string|null, sha384:string|null, sha512:string|null, whitelist:int} $item Item with row's data.
	 *
	 * @return string
	 */
	public function column_sha384( $item ) {
		if ( Utils::is_resource_hash_needed( $item['directive'], $item['tagname'] ) ) {
			if ( is_null( $item['sha384'] ) ) {
				return '<span class="dashicons dashicons-minus"></span>';
			}
			if ( 64 === strlen( $item['sha384'] ) && base64_decode( $item['sha384'] ) ) {
				return '<span class="dashicons dashicons-yes" title="$item[\'sha384\']"></span>';
			} else {
				return '<span class="dashicons dashicons-no-alt"></span>';
			}
		} else {
			return '<span class="dashicons dashicons-minus"></span>';
		}
	}

	/**
	 * Render the sha512 column
	 *
	 * @param array{ID: int, directive:string, tagname:string, src_attrib:string, sha256:string|null, sha384:string|null, sha512:string|null, whitelist:int} $item Item with row's data.
	 *
	 * @return string
	 */
	public function column_sha512( $item ) {
		if ( Utils::is_resource_hash_needed( $item['directive'], $item['tagname'] ) ) {
			if ( is_null( $item['sha512'] ) ) {
				return '<span class="dashicons dashicons-minus"></span>';
			}
			if ( 88 === strlen( $item['sha512'] ) && base64_decode( $item['sha512'] ) ) {
				return '<span class="dashicons dashicons-yes" title="$item[\'sha512\']"></span>';
			} else {
				return '<span class="dashicons dashicons-no-alt"></span>';
			}
		} else {
			return '<span class="dashicons dashicons-minus"></span>';
		}
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
	 * @param array<string> $item Data in row.
	 * @param string        $column_name Column name.
	 * @return string|void
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
			'directive'  => array( 'directive', true ),
			'tagname'    => array( 'tagname', false ),
			'src_attrib' => array( 'src_attrib', false ),
			'whitelist'  => array( 'whitelist', true ),
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
			'cb'         => '<input type="checkbox" />',
			'directive'  => __( 'Directive', 'no-unsafe-inline' ),
			'tagname'    => __( 'TagName', 'no-unsafe-inline' ),
			'src_attrib' => __( 'Resource', 'no-unsafe-inline' ),
			'sha256'     => __( 'sha256', 'no-unsafe-inline' ),
			'sha384'     => __( 'sha384', 'no-unsafe-inline' ),
			'sha512'     => __( 'sha512', 'no-unsafe-inline' ),
			'whitelist'  => __( 'WhiteList', 'no-unsafe-inline' ),

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

		$total_items = DB::get_external_total_num( $search );

		$data = DB::get_external_list( $orderby, $per_page, $paged, $search );

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
