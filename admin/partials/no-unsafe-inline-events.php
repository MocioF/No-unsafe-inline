<?php
/**
 * The file is used to render the events whitelist tab.
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
<div class="wrap" id="nunil-events-list">
<form id="nunil-inline-events-form" method="post">
<?php
	$nunil_page    = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	$nunil_paged   = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
	$nunil_orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
	$nunil_order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
	$nunil_search  = filter_input( INPUT_GET, 's', FILTER_SANITIZE_STRING );

	printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $nunil_page ) ) );
	printf( '<input type="hidden" name="paged" value="%d" />', intval( esc_html( strval( $nunil_paged ) ) ) );
	printf( '<input type="hidden" name="orderby" value="%s" />', esc_html( strval( $nunil_orderby ) ) );
	printf( '<input type="hidden" name="order" value="%s" />', esc_html( strval( $nunil_order ) ) );
	printf( '<input type="hidden" name="s" value="%s" />', esc_html( strval( $nunil_search ) ) );

	$no_unsafe_inline_sources_obj = $this->show_table;
	$no_unsafe_inline_sources_obj->prepare_items();
	$no_unsafe_inline_sources_obj->search_box( esc_html__( 'Search script', 'no-unsafe-inline' ), 'script' );
	$no_unsafe_inline_sources_obj->display();
?>
</form>
</div>
