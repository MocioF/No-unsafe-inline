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
	<?php
	settings_fields( 'no-unsafe-inline_group' );
	do_settings_sections( 'no-unsafe-inline-options' );
	submit_button();
	?>
</form>
