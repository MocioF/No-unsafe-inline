<?php
/**
 * The file is used to render the admin base rules tab.
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
<form method="post" action="options.php" class="no-unsafe-inline-base-rule-form">
	<?php
	settings_fields( 'no-unsafe-inline_base_rule_group' );
	do_settings_sections( 'no-unsafe-inline-base-rule-page' );
	submit_button();
	?>
</form>
<?php
	$no_unsafe_inline_sources_obj = new No_Unsafe_Inline_Base_Rule_List();
	$no_unsafe_inline_sources_obj->prepare_items();
	$no_unsafe_inline_sources_obj->display();
?>
