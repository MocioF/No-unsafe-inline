<?php declare(strict_types=1);
use NUNIL\Nunil_Lib_Db as Db;

final class Nunil_CaptureTest extends WP_UnitTestCase {

	public function tear_down(): void {
		deactivate_no_unsafe_inline( false );
		parent::tear_down();
	}

	protected function setUp(): void {
		parent::set_up();
		activate_no_unsafe_inline( false );

		$plugin = new No_Unsafe_Inline();
		$plugin->run();

	}

	/**
	 * @dataProvider tagListProvider
	 */
	public function testcapture_tag( string $tagname, array $taglist, int $num_ext, int $num_inl, string $source ): void {

		global $wpdb;

		$capture = new NUNIL\Nunil_Capture();

		$test_input_page = file_get_contents( __DIR__ . '/test-Nunil_Capture/' . $source );
		$capture->load_html( $test_input_page );

		for ( $i = 0; $i < 100; $i++ ) {
			$capture->capture_tags( $taglist );
		}

		$sql       = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'nunil_inline_scripts WHERE tagname = \'' . $tagname . '\'';
		$count_inl = intval( $wpdb->get_var( $sql ) );

		$sql       = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'nunil_external_scripts WHERE tagname = \'' . $tagname . '\'';
		$count_ext = intval( $wpdb->get_var( $sql ) );

		$this->assertEquals(
			$num_inl,
			$count_inl
		);

		$this->assertEquals(
			$num_ext,
			$count_ext
		);
	}

	public function tagListProvider(): array {

		$directive     = 'script-src';
		$tag           = 'script';
		$stored_attrs  = 'src';
		$needed_attrs  = array(
			array( 'type' => '! text/html' ),
			array( 'type' => '! text/template' ),
		);
		$inline        = true;
		$tags_script[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$tag           = 'link';
		$stored_attrs  = 'href';
		$needed_attrs  = array(
			array( 'rel' => 'preload' ),
			array( 'as' => 'script' ),
		);
		$inline        = false;
		$tags_script[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$tag           = 'link';
		$stored_attrs  = 'href';
		$needed_attrs  = array(
			array( 'rel' => 'prefetch' ),
			array( 'as' => 'script' ),
		);
		$inline        = false;
		$tags_script[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$directive = 'style-src';

		$tag          = 'link';
		$stored_attrs = 'href';
		$needed_attrs = array(
			array( 'rel' => 'stylesheet' ),
		);
		$inline       = true;
		$tags_style[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$tag          = 'link';
		$stored_attrs = 'href';
		$needed_attrs = array(
			array( 'rel' => 'preload' ),
			array( 'as' => 'style' ),
		);
		$inline       = true;
		$tags_style[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$tag          = 'link';
		$stored_attrs = 'href';
		$needed_attrs = array(
			array( 'rel' => 'prefetch' ),
			array( 'as' => 'style' ),
		);
		$inline       = true;
		$tags_style[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		$tag          = 'style';
		$inline       = true;
		$tags_style[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs = null, $needed_attrs = null, $childs = null, $inline );

		return array(
			/* string $tagname, array $taglist, int $num_ext, int $num_inl, string $source ) */
			'script_1' => array( 'script', $tags_script, 1, 2, 'index_1.html' ),
			'style_1'  => array( 'style', $tags_style, 0, 32, 'index_1.html' ),
			'script_2' => array( 'script', $tags_script, 6, 6, 'index_2.html' ),
			'style_2'  => array( 'style', $tags_style, 0, 21, 'index_2.html' ),
			'link_2'   => array( 'link', $tags_style, 9, 0, 'index_2.html' ),
			'script_3' => array( 'script', $tags_script, 3, 6, 'index_3.html' ),
			'link_3'   => array( 'link', $tags_style, 1, 0, 'index_3.html' ),
		);
	}
}
