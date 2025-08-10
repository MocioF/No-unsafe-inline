<?php
/**
 * The file is used to render the logs tab.
 * It is required by class No_Unsafe_Inline_Admin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 *
 * @var string|false $enabled_logs
 */

namespace NUNIL\admin\partials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use NUNIL\Nunil_Lib_Utils as Utils;

if ( isset( $_GET['tab'] ) ) {
	$nunil_tab       = Utils::sanitize_text( $_GET['tab'] );
	$nunil_list_tabs = array(
		'logs',
	);
	if ( false === in_array( $nunil_tab, $nunil_list_tabs ) ) {
		$nunil_tab = null;
	}
} else {
	$nunil_tab = null;
}

if ( isset( $_GET['page'] ) ) {
	$nunil_page = Utils::sanitize_text( $_GET['page'], false );
	if ( 'no-unsafe-inline' !== $nunil_page ) {
		$nunil_page = null;
	}
} else {
	$nunil_page = null;
}

$nunil_paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

if ( isset( $_GET['orderby'] ) ) {
	$nunil_orderby = Utils::sanitize_text( $_GET['orderby'] );
	switch ( $nunil_tab ) {
		case 'logs':
			$nunil_adm_orderby = array(
				'created_at',
				'level',
			);
			break;
		default:
			$nunil_adm_orderby = array();
	}

	if ( ! in_array( $nunil_orderby, $nunil_adm_orderby ) ) {
		$nunil_orderby = null;
	}
} else {
	$nunil_orderby = null;
}

if ( isset( $_GET['order'] ) ) {
	$nunil_order  = Utils::sanitize_text( $_GET['order'] );
	$nunil_orders = array(
		'asc',
		'desc',
	);
	if ( ! in_array( $nunil_order, $nunil_orders ) ) {
		$nunil_order = null;
	}
} else {
	$nunil_order = null;
}

printf( '<div class="wrap" id="nunil-' . esc_html( strval( $nunil_tab ) ) . '-list">' );
if ( ! empty( $message ) ) :
	?>
	<div id="message" class="notice"><p><?php echo esc_html( $message ); ?></p></div>
	<?php
endif;

printf( '<form id="nunil-' . esc_html( strval( $nunil_tab ) ) . '-form" method="post">' );
printf( '<input type="hidden" name="tab" value="%s" />', esc_html( strval( $nunil_tab ) ) );
printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $nunil_page ) ) );
printf( '<input type="hidden" name="paged" value="%d" />', intval( esc_html( strval( $nunil_paged ) ) ) );
printf( '<input type="hidden" name="orderby" value="%s" />', esc_html( strval( $nunil_orderby ) ) );
printf( '<input type="hidden" name="order" value="%s" />', esc_html( strval( $nunil_order ) ) );


/**
 * Checked in the main class file.
 *
 * @var bool $enabled_logs
 */
if ( $enabled_logs ) {
	$no_unsafe_inline_sources_obj = new No_Unsafe_Inline_Admin_Logs_Table();
	$no_unsafe_inline_sources_obj->prepare_items();
	$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search Logs', 'no-unsafe-inline' ), 'message' );
	$no_unsafe_inline_sources_obj->display();
}
?>
</form>
</div>
