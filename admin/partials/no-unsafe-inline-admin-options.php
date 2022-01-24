<form method="post" action="options.php" class="no-unsafe-inline-settings-form">
	<?php
	settings_fields( 'no-unsafe-inline_group' );
	do_settings_sections( 'no-unsafe-inline-options' );
	submit_button();
	?>
</form>
