<?php
/**
 * The file is used to render the whitelist lists.
 * It is required by class No_Unsafe_Inline_Admin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.1.1
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 *
 * @var \No_Unsafe_Inline_Admin $this
 */

if ( isset( $_GET['tab'] ) ) {
	$nunil_tab       = strtolower( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) );
	$nunil_list_tabs = array(
		'external',
		'inline',
		'events',
	);
	if ( false === in_array( $nunil_tab, $nunil_list_tabs ) ) {
		$nunil_tab = null;
	}
} else {
	$nunil_tab = null;
}

if ( isset( $_GET['page'] ) ) {
	$nunil_page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
	if ( 'no-unsafe-inline' !== $nunil_page ) {
		$nunil_page = null;
	}
} else {
	$nunil_page = null;
}

	$nunil_paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

if ( isset( $_GET['orderby'] ) ) {
	$nunil_orderby = strtolower( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) );
	switch ( $nunil_tab ) {
		case 'external':
			$nunil_adm_orderby = array(
				'directive',
				'tagname',
				'src_attrib',
				'whitelist',
			);
			break;
		case 'inline':
			$nunil_adm_orderby = array(
				'directive',
				'tagname',
				'clustername',
				'occurences',
				'whitelist',
				'lastseen',
			);
			break;
		case 'events':
			$nunil_adm_orderby = array(
				'tagname',
				'tagid',
				'event_attribute',
				'clustername',
				'occurences',
				'whitelist',
				'lastseen',
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
	$nunil_order  = strtolower( sanitize_text_field( wp_unslash( $_GET['order'] ) ) );
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
printf( '<form id="nunil-inline-' . esc_html( strval( $nunil_tab ) ) . '-form" method="post">' );
printf( '<input type="hidden" name="tab" value="%s" />', esc_html( strval( $nunil_tab ) ) );
printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $nunil_page ) ) );
printf( '<input type="hidden" name="paged" value="%d" />', intval( esc_html( strval( $nunil_paged ) ) ) );
printf( '<input type="hidden" name="orderby" value="%s" />', esc_html( strval( $nunil_orderby ) ) );
printf( '<input type="hidden" name="order" value="%s" />', esc_html( strval( $nunil_order ) ) );

$no_unsafe_inline_sources_obj = $this->show_table;
$no_unsafe_inline_sources_obj->prepare_items();

switch ( $nunil_tab ) {
	case 'external':
		$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search resource', 'no-unsafe-inline' ), 'src_attrib' );
		break;
	case 'inline':
		$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search script', 'no-unsafe-inline' ), 'script' );
		break;
	case 'events':
		$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search script', 'no-unsafe-inline' ), 'script' );
		break;
}
	$no_unsafe_inline_sources_obj->display();
?>
</form>
</div>
