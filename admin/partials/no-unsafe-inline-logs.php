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

printf(
	'
<div id="nunil-spinner-blocks" class="nunil-spinner-blocks hidden"><div class="nunil-spinner-tools">
<div class="nunil-spinner-box-1"></div><div class="nunil-spinner-box-2"></div><div class="nunil-spinner-box-3"></div><div class="nunil-spinner-box-4"></div><div class="nunil-spinner-box-5"></div><div class="nunil-spinner-box-6"></div><div class="nunil-spinner-box-7"></div><div class="nunil-spinner-box-8"></div>
</div></div>'
);
printf( '<div class="wrap" id="nunil-logs-list">' );
if ( ! empty( $message ) ) :
	?>
	<div id="message" class="notice"><p><?php echo esc_html( $message ); ?></p></div>
	<?php
endif;

printf( '<form id="nunil-logs-form" method="post">' );
printf( '<div id="nunil-log-table" style="">' );
wp_nonce_field( 'ajax-nunil-logs-nonce', '_ajax_nunil_logs_nonce' );
printf( '</div>' );
?>
</form>
</div>
