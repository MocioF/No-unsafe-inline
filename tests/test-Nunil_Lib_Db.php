<?php declare(strict_types=1);

final class Nunil_Lib_DbTest extends \WP_UnitTestCase {

	public function tear_down(): void {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	public function set_up(): void {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );

		$plugin = new \NUNIL\includes\No_Unsafe_Inline();
		$plugin->run();
	}
	
	/**
	 * @group db
	 * @dataProvider provide_external
	 */
	public function test_insert_ext_in_db( $input, $unexpected ) {
		global $wpdb;
		
		$id = NUNIL\Nunil_Lib_Db::insert_ext_in_db( $input['directive'], $input['tagname'], $input['src_attrib'] );
		$this->assertIsInt( $id, $wpdb->last_error );
		$this->assertGreaterThan( $unexpected, $id, $wpdb->last_error );
	}
	
	public function provide_external(){
		return [
			[	array(
					'directive' => 'style-src',
					'tagname' => 'link',
					'src_attrib' => 'https://www.wordpress.org/wp-admin/load-styles.php?c=0&dir=ltr&load%5Bchunk_0%5D=dashicons,admin-bar,common,forms,admin-menu,dashboard,list-tables,edit,revisions,media,themes,about,nav-menus,wp-pointer,widgets&load%5Bchunk_1%5D=,site-icon,l10n,buttons,wp-auth-check,media-views&ver=6.4.3'
				),
				0
			]
		];
	}
	
	/**
	 * @group db1
	 * @global type $wpdb
	 */
	public function test_extend_ext_src_attrib_size(){
		global $wpdb;
		NUNIL\Nunil_Lib_Db::extend_ext_src_attrib_size();
		$table = $wpdb->prefix . 'nunil_' . 'external_scripts';
		$column_info = $wpdb->get_col_length( $table, 'src_attrib' );
		$expected_column_info = array(
			'type' => 'char',
			'length' => 768
		);
		$this->assertSame( $expected_column_info, $column_info );
	}
}