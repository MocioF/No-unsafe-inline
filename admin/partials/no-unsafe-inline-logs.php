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

?>
<div class="wrap" id="nunil-logs-list">
<form id="nunil-logs-form" method="post">
	<?php
	$nunil_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRIPPED );
	if ( ! empty( $message ) ) :
		?>
		<div id="message" class="notice"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	endif;

	if ( $enabled_logs ) {
		printf( '<input type="hidden" name="page" value="%s" />', esc_html( strval( $nunil_page ) ) );
		$no_unsafe_inline_sources_obj = new No_Unsafe_Inline_Admin_Logs_Table();
		$no_unsafe_inline_sources_obj->prepare_items();
		$no_unsafe_inline_sources_obj->display();
	}
	?>
</form>
</div>
