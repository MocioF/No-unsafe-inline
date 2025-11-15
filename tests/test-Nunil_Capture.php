<?php declare(strict_types=1);

final class Nunil_CaptureTest extends \WP_UnitTestCase {

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
	 * @dataProvider tagListProvider
	 * @group capture
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

		$directive     = 'img-src';
		$tag           = 'img';
		$stored_attrs = array( 'src', 'srcset' );
		$needed_attrs  = array();

		$inline        = false;
		$tags_img[] = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

		return array(
			// string $tagname, array $taglist, int $num_ext, int $num_inl, string $source ) .
			'script_1' => array( 'script', $tags_script, 1, 2, 'index_1.html' ),
			'style_1'  => array( 'style', $tags_style, 0, 32, 'index_1.html' ),
			'script_2' => array( 'script', $tags_script, 6, 6, 'index_2.html' ),
			'style_2'  => array( 'style', $tags_style, 0, 21, 'index_2.html' ),
			'link_2'   => array( 'link', $tags_style, 9, 0, 'index_2.html' ),
			'script_3' => array( 'script', $tags_script, 3, 6, 'index_3.html' ),
			'link_3'   => array( 'link', $tags_style, 1, 0, 'index_3.html' ),
			'img_1'    => array( 'img', $tags_img, 1, 0, 'index_1.html' ),
			'img_2'    => array( 'img', $tags_img, 8, 0, 'index_2.html' ),
			'img_3'    => array( 'img', $tags_img, 65, 0, 'index_3.html' ),
		);
	}

	/**
	 * @dataProvider srcListProvider
	 * @group capture
	 */
	public function testconv_to_absolute_url ( string $base_url, string $src_input, string $exp_output ) {
		$capture = new \ReflectionClass( 'NUNIL\Nunil_Capture' );
		$my_conv_to_absolute_url = $capture->getMethod('conv_to_absolute_url');
		$my_conv_to_absolute_url->setAccessible( true );
		$myInstance = new  NUNIL\Nunil_Capture();

		$src_output = $my_conv_to_absolute_url->invokeArgs($myInstance, [$src_input, $base_url]);

		$this->assertEquals(
			$exp_output,
			$src_output
		);

	}

	public function srcListProvider(): array {
		return array (
			'absolute_1' => array( 'https://wp.org', 'https://wp.org/images/image1.png', 'https://wp.org/images/image1.png' ),
			'absolute_2' => array( 'https://wp.org', '//wp.org/images/image1.png', 'https://wp.org/images/image1.png' ),
			'absolute_3' => array( '', 'https://wp.org/wp-content/themes/twenty-twentytwo/screenshot.png?ver=4.4.8', 'https://wp.org/wp-content/themes/twenty-twentytwo/screenshot.png?ver=4.4.8' ),
			'relative_1' => array( 'https://wp.org/page1/', 'images/image1.png', 'https://wp.org/page1/images/image1.png' ),
			'relative_2' => array( 'https://wp.org/page1', '/images/image1.png', 'https://wp.org/images/image1.png' ),
			'relative_3' => array( 'https://wp.org/page1.php', '/images/image1.png', 'https://wp.org/images/image1.png' ),
			'relative_4' => array( 'https://wp.org/section/page1.php', 'images/image1.png', 'https://wp.org/section/images/image1.png' ),
			'relative_5' => array( 'https://wp.org/section/page1.php', '/images/image1.png', 'https://wp.org/images/image1.png' ),
			'relative_6' => array( 'https://wp.org/page1.php', 'images/image1.png', 'https://wp.org/images/image1.png' ),
			'relative_7' => array( 'https://wp.org/page1', 'images/image1.png', 'https://wp.org/images/image1.png' ),
		);
	}

	public function testBuildXpathQueryProducesExpectedParts(): void {
        $tagStub = new class {
            public function get_name() { return 'img'; }
            public function get_storedattrs() { return ['src']; }
            public function get_neededattrs() { return [['alt' => '']]; }
            public function has_childs() { return false; }
            public function get_childs() { return null; }
        };

        $ref = new ReflectionClass(\NUNIL\Nunil_Manipulate_DOM::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $query = $instance->build_xpath_query( $tagStub );

        $this->assertStringContainsString('//img', $query);
        $this->assertStringContainsString('@src', $query);
    }

    public function testGetHashesReturnsExpectedStructure(): void {
        $ref = new ReflectionClass(\NUNIL\Nunil_Manipulate_DOM::class);
        $instance = $ref->newInstanceWithoutConstructor();

        // set inline_scripts_mode to sha384 to check 'in_use' result
        $rp = $ref->getProperty('inline_scripts_mode');
        $rp->setAccessible(true);
        $rp->setValue($instance, 'sha384');

        $rm = $ref->getMethod('get_hashes');
        $rm->setAccessible(true);

        $result = $rm->invoke($instance, 'console.log("x");', true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sha256', $result);
        $this->assertArrayHasKey('sha384', $result);
        $this->assertArrayHasKey('sha512', $result);
        $this->assertArrayHasKey('in_use', $result);
        $this->assertEquals('sha384', $result['in_use']);
        $this->assertIsString($result['sha384']);
    }

    public function testInjectInlineScriptAndStyleAppendsToDomAndWhitelist(): void {
        $ref = new ReflectionClass(\NUNIL\Nunil_Manipulate_DOM::class);
        $instance = $ref->newInstanceWithoutConstructor();

        // prepare DOMDocument with head and body
        $dom = new \DOMDocument();
        $dom->loadHTML('<!doctype html><html><head></head><body></body></html>');

        // set domdocument
        $rp = $ref->getProperty('domdocument');
        $rp->setAccessible(true);
        $rp->setValue($instance, $dom);

        // prepare csp_local_whitelist
        $rp_whitelist = $ref->getProperty('csp_local_whitelist');
        $rp_whitelist->setAccessible(true);
        $rp_whitelist->setValue($instance, []);

        // set page_nonce
        $rp_nonce = $ref->getProperty('page_nonce');
        $rp_nonce->setAccessible(true);
        $rp_nonce->setValue($instance, 'testnonce');

        // 1) inject inline script
        $rp_inj_script = $ref->getProperty('injected_inline_script');
        $rp_inj_script->setAccessible(true);
        $rp_inj_script->setValue($instance, 'console.log("hi");');

        $rp_mode = $ref->getProperty('inline_scripts_mode');
        $rp_mode->setAccessible(true);
        $rp_mode->setValue($instance, 'sha256');

        $rm_script = $ref->getMethod('inject_inline_script');
        $rm_script->setAccessible(true);
        $rm_script->invoke($instance);

        $scriptNodes = $dom->getElementsByTagName('script');
        $this->assertGreaterThanOrEqual(1, $scriptNodes->length);
        $found = false;
        foreach ($scriptNodes as $sn) {
            if (strpos($sn->textContent, 'console.log("hi")') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Injected inline script content not found in DOM');

        $whitelist = $rp_whitelist->getValue($instance);
        $this->assertNotEmpty($whitelist, 'csp_local_whitelist should contain an entry after injecting script');
        $this->assertArrayHasKey('0', $whitelist);
        $this->assertArrayHasKey('directive', $whitelist[0]);
        $this->assertEquals('script-src', $whitelist[0]['directive']);

        // 2) inject inline style
        $rp_inj_style = $ref->getProperty('injected_inline_style');
        $rp_inj_style->setAccessible(true);
        $rp_inj_style->setValue($instance, '.foo { color: red; }');

        $rm_style = $ref->getMethod('inject_inline_style');
        $rm_style->setAccessible(true);
        $rm_style->invoke($instance);

        $styleNodes = $dom->getElementsByTagName('style');
        $this->assertGreaterThanOrEqual(1, $styleNodes->length);
        $foundStyle = false;
        foreach ($styleNodes as $sn) {
            if (strpos($sn->textContent, '.foo') !== false) {
                $foundStyle = true;
                break;
            }
        }
        $this->assertTrue($foundStyle, 'Injected inline style content not found in DOM');

        $whitelist = $rp_whitelist->getValue($instance);
        // there should be at least one style-src entry somewhere
        $hasStyleEntry = false;
        foreach ($whitelist as $entry) {
            if (isset($entry['directive']) && 'style-src' === $entry['directive']) {
                $hasStyleEntry = true;
                break;
            }
        }
        $this->assertTrue($hasStyleEntry, 'csp_local_whitelist should contain a style-src entry after injecting style');
    }
}
