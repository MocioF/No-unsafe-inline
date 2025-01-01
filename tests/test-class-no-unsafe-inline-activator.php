<?php

final class No_Unsafe_Inline_ActivatorTest extends \WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );
	}

	public function tear_down() {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	function test_reset_tools() {
		$tools = get_option( 'no-unsafe-inline-tools' );

		$expected = array(
			'capture_enabled'   => 0,
			'test_policy'       => 0,
			'enable_protection' => 0,
		);

		$this->assertSame( $tools, $expected );
	}

	function test_default_options() {
		$default = get_option( 'no-unsafe-inline' );

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
		$plugin_options['script-src_mode']                   = 'nonce';
		$plugin_options['style-src_mode']                    = 'nonce';
		// $plugin_options['img-src_mode']                      = 'none';
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

		$this->assertSame( $default, $plugin_options );
	}
}
