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
	<?php
	if ( ! empty( $message ) ) :
		?>
		<div id="message" class="notice"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	endif;

	if ( $enabled_logs ) {
		$no_unsafe_inline_sources_obj = new No_Unsafe_Inline_Admin_Logs_Table();
		$no_unsafe_inline_sources_obj->prepare_items();
		$no_unsafe_inline_sources_obj->display();
	}
	?>
</div>
