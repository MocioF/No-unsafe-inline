<form method="post" action="options.php" class="no-unsafe-inline-base-rule-form">
	<?php
	settings_fields( 'no-unsafe-inline_base_rule_group' );
	do_settings_sections( 'no-unsafe-inline-base-rule-page' );
	submit_button();
	?>
</form>
<?php
	$sources_obj = new No_Unsafe_Inline_Base_Rule_List();
	$sources_obj->prepare_items();
	$sources_obj->display();
?>
