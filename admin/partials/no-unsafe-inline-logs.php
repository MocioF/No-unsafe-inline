<div class="wrap" id="nunil-logs-list">
<form id="nunil-logs-form" method="post">
	<?php
	/** @var string|false $enabled_logs */
	$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	if ( ! empty( $message ) ) : ?>
		<div id="message" class="notice"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	endif;

	if ( $enabled_logs ) {
		printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $page ) ) );
		$no_unsafe_inline_sources_obj = new No_Unsafe_Inline_Admin_Logs_Table();
		$no_unsafe_inline_sources_obj->prepare_items();
		$no_unsafe_inline_sources_obj->display();
	}
	?>
</form>
</div>
