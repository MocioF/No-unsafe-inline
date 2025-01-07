<?php
/**
 * The file is used to render the admin options tab.
 * It is required by class No_Unsafe_Inline_Admin.
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 * @package    No_unsafe-inline
 * @subpackage No_unsafe-inline/admin
 */

namespace NUNIL\admin\partials;

?>
<form method="post" action="options.php" class="no-unsafe-inline-settings-form">
	<div id="nunil-options-tabs">
		<ul class="serialtabs-nav">
			<li data-serialtabs="#nunil-options-tabs-1"><?php printf( esc_html__( 'Directives managed', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-2"><?php printf( esc_html__( 'External sources', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-3"><?php printf( esc_html__( 'Inline scripts', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-4"><?php printf( esc_html__( 'Misc options', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-5"><?php printf( esc_html__( 'Violations Reports', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-6"><?php printf( esc_html__( 'Logs', 'no-unsafe-inline' ) ); ?></li>
			<li data-serialtabs="#nunil-options-tabs-7"><?php printf( esc_html__( 'Deactivation options', 'no-unsafe-inline' ) ); ?></li>
		</ul>
		<?php
		settings_fields( 'no-unsafe-inline_group' );
		do_settings_sections( 'no-unsafe-inline-options' );
		?>
	</div>
	<?php
	submit_button();
	?>
</form>
