<form method="post" action="options.php" class="no-unsafe-inline-base-src-form">
	<?php
	settings_fields( 'no-unsafe-inline_base_src_group' );
	do_settings_sections( 'no-unsafe-inline-base-src-page' );
	submit_button();
	?>
</form>
<?php
	$sources_obj = new No_Unsafe_Inline_Base_Src_List();
	$sources_obj->prepare_items();
	$sources_obj->display();
?>
