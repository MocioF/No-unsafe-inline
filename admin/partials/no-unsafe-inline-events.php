<div class="wrap" id="nunil-events-list">
<form id="nunil-inline-events-form" method="post">
<?php
	/** @var \No_Unsafe_Inline_Admin $this */
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

	$sources_obj = $this->show_table;
	$sources_obj->prepare_items();
	$sources_obj->search_box( esc_html__( 'Search script', 'no-unsafe-inline' ), 'script' );
	$sources_obj->display();
?>
</form>
</div>
