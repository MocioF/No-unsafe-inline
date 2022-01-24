<div class="wrap" id="nunil-external-list">
<form id="nunil-external-list-form" method="post">
<?php
	$page    = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	$paged   = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
	$orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
	$order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
	$search  = filter_input( INPUT_GET, 's', FILTER_SANITIZE_STRING );

	printf( '<input type="hidden" name="page" value="%s" />', $page );
	printf( '<input type="hidden" name="paged" value="%d" />', $paged );
	printf( '<input type="hidden" name="orderby" value="%d" />', $orderby );
	printf( '<input type="hidden" name="order" value="%d" />', $order );
	printf( '<input type="hidden" name="s" value="%s" />', $search );

	$sources_obj = new No_Unsafe_Inline_External_List();
	$sources_obj->prepare_items();
	$sources_obj->search_box( esc_html__( 'Search resource', 'no-unsafe-inline' ), 'src_attrib' );
	$sources_obj->display();
?>
</form>
</div>
