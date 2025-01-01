<?php

final class No_Unsafe_Inline_AdminTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );
	}

	public function tear_down() {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	public function test_nunil_upgrade() {
		$real_file              = 'mu-plugin/no-unsafe-inline-output-buffering.php';
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

		// sets old options
		$plugin_options                                      = array();
		$plugin_options['default-src_enabled']               = 1;
		$plugin_options['script-src_enabled']                = 1;
		$plugin_options['style-src_enabled']                 = 1;
		$plugin_options['img-src_enabled']                   = 1;
		$plugin_options['font-src_enabled']                  = 1;
		$plugin_options['connect-src_enabled']               = 1;
		$plugin_options['media-src_enabled']                 = 1;
		$plugin_options['object-src_enabled']                = 1;
		$plugin_options['prefetch-src_enabled']              = 0;
		$plugin_options['child-src_enabled']                 = 1;
		$plugin_options['frame-src_enabled']                 = 1;
		$plugin_options['worker-src_enabled']                = 1;
		$plugin_options['manifest-src_enabled']              = 1;
		$plugin_options['base-uri_enabled']                  = 1;
		$plugin_options['form-action_enabled']               = 1;
		$plugin_options['frame-ancestors_enabled']           = 1;
		$plugin_options['external_host_mode']                = 'sch-host';
		$plugin_options['hash_in_script-src']                = 1;
		$plugin_options['hash_in_style-src']                 = 1;
		$plugin_options['hash_in_img-src']                   = 0;
		$plugin_options['hash_in_all']                       = 0;
		$plugin_options['sri_sha256']                        = 1;
		$plugin_options['sri_sha384']                        = 0;
		$plugin_options['sri_sha512']                        = 0;
		$plugin_options['sri_script']                        = 1;
		$plugin_options['sri_link']                          = 1;
		$plugin_options['inline_scripts_mode']               = 'nonce';
		$plugin_options['use_strict-dynamic']                = 0;
		$plugin_options['no-unsafe-inline_upgrade_insecure'] = 1;
		$plugin_options['protect_admin']                     = 1;
		$plugin_options['use_unsafe-hashes']                 = 0;
		$plugin_options['fix_setattribute_style']            = 1;
		$plugin_options['add_wl_by_cluster_to_db']           = 1;
		$plugin_options['log_level']                         = 'error';
		$plugin_options['log_driver']                        = 'db';
		$plugin_options['remove_tables']                     = 0;
		$plugin_options['remove_options']                    = 0;
		$plugin_options['use_reports']                       = 0;
		$plugin_options['group_name']                        = 'csp-endpoint';
		$plugin_options['max_age']                           = 10886400;
		$plugin_options['max_response_header_size']          = 8192;

		update_option( 'no-unsafe-inline', $plugin_options );

		$options = get_option( 'no-unsafe-inline' );

		$this->assertSame(
			$options,
			$plugin_options
		);

		$assertvalue = array_key_exists( 'hash_in_script-src', $options );
		$this->assertTrue(
			$assertvalue
		);
		$assertvalue = array_key_exists( 'hash_in_style-src', $options );
		$this->assertTrue(
			$assertvalue
		);

		$assertvalue = array_key_exists( 'hash_in_img-src', $options );
		$this->assertTrue(
			$assertvalue
		);

		$assertvalue = array_key_exists( 'hash_in_all', $options );
		$this->assertTrue(
			$assertvalue
		);

		$plugin = new \NUNIL\includes\No_Unsafe_Inline();

		$admin = new \NUNIL\admin\No_Unsafe_Inline_Admin( $plugin->get_plugin_name(), $plugin->get_version(), $plugin->get_managed_directives() );
		$admin->nunil_upgrade();

		$expected = $new_ver_mu_plugin_hash;
		$new_file = WPMU_PLUGIN_DIR . '/no-unsafe-inline-output-buffering.php';
		$response = md5_file( $new_file );

		$this->assertSame(
			$response,
			$expected
		);

		$options = get_option( 'no-unsafe-inline' );

		$assertvalue = ! array_key_exists( 'hash_in_script-src', $options );
		$this->assertTrue(
			$assertvalue
		);

		$assertvalue = ! array_key_exists( 'hash_in_style-src', $options );
		$this->assertTrue(
			$assertvalue
		);

		$assertvalue = ! array_key_exists( 'hash_in_img-src', $options );
		$this->assertTrue(
			$assertvalue
		);

		$assertvalue = ! array_key_exists( 'hash_in_all', $options );
		$this->assertTrue(
			$assertvalue
		);

		$this->assertSame(
			$options['script-src_mode'],
			'hash'
		);

		$this->assertSame(
			$options['style-src_mode'],
			'none'
		);

		/**
		 * $this->assertSame(
		 *	$options['img-src_mode'],
		 *	'none'
		 *);
		 *
		 */
	}

}
