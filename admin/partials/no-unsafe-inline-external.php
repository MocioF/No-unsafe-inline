<?php
/**
 * The file is used to render the admin external whitelist tab.
 * It is required by class No_Unsafe_Inline_Admin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 *
 * @var \No_Unsafe_Inline_Admin $this
 */

?>
<div class="wrap" id="nunil-external-list">
<form id="nunil-external-list-form" method="post">
<?php
	$page    = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	$paged   = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
	$orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
	$order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
	$search  = filter_input( INPUT_GET, 's', FILTER_SANITIZE_STRING );

	printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $page ) ) );
	printf( '<input type="hidden" name="paged" value="%d" />', intval( esc_html( strval( $paged ) ) ) );
	printf( '<input type="hidden" name="orderby" value="%s" />', esc_html( strval( $orderby ) ) );
	printf( '<input type="hidden" name="order" value="%s" />', esc_html( strval( $order ) ) );
	printf( '<input type="hidden" name="s" value="%s" />', esc_html( strval( $search ) ) );

	$no_unsafe_inline_sources_obj = $this->show_table;
	$no_unsafe_inline_sources_obj->prepare_items();
	$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search resource', 'no-unsafe-inline' ), 'src_attrib' );
	$no_unsafe_inline_sources_obj->display();
?>
</form>
</div>
