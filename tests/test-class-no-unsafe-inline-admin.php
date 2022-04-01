<?php

final class No_Unsafe_Inline_AdminTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		no_unsafe_inline_activate( false );
	}

	public function tear_down() {
		no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	public function test_nunil_upgrade() {
		$real_file              = 'mu-plugins/no-unsafe-inline-output-buffering.php';
		$new_ver_mu_plugin_hash = md5_file( $real_file );

		$fake_file = fopen( WPMU_PLUGIN_DIR . '/no-unsafe-inline-output-buffering.php', 'w' );
		$txt       = "Just a line of text\n";
		fwrite( $fake_file, $txt );
		fclose( $fake_file );
		$fake_file           = WPMU_PLUGIN_DIR . '/no-unsafe-inline-output-buffering.php';
		$fake_mu_plugin_hash = md5_file( $fake_file );

		$this->assertSame(
			'e774ee31595929248c0cffdc1c5d6765',
			$fake_mu_plugin_hash
		);

		$this->assertFalse( $new_ver_mu_plugin_hash === $fake_mu_plugin_hash );

		$version_option = get_option( 'no-unsafe-inline_version' );

		$fake_version = '0.0.1';

		update_option( 'no-unsafe-inline_version', '0.0.1' );

		$plugin = new No_Unsafe_Inline();

		$admin = new No_Unsafe_Inline_Admin( $plugin->get_plugin_name(), $plugin->get_version(), $plugin->get_managed_directives() );
		$admin->nunil_upgrade();

		$expected = $new_ver_mu_plugin_hash;
		$new_file = WPMU_PLUGIN_DIR . '/no-unsafe-inline-output-buffering.php';
		$response = md5_file( $new_file );

		$this->assertSame(
			$response,
			$expected
		);
	}

}
