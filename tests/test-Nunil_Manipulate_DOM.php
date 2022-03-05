<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class Nunil_Manipulate_DOMTest extends WP_UnitTestCase
{	
	
	protected $nunil_tag1;
	protected $nunil_tag2;

	public function tear_down() {
		deactivate_no_unsafe_inline( false );
		parent::tear_down();
	}
    protected function setUp(): void
    {
		parent::set_up();
		activate_no_unsafe_inline( false );
        $directive    = 'script-src';
		$tag          = 'script';
		$stored_attr  = 'src';
		$needed_attrs = array(
			array( 'type' => '! text/html' ),
			array( 'type' => '! text/template' ),
		);
		$inline = true;
		$this->nunil_tag_1 = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attr, $needed_attrs, $childs = null, $inline );
		
		$directive    = 'style-src';
		$tag          = 'link';
		$stored_attr  = 'href';
		$needed_attrs = array(
			array( 'rel' => 'preload' ),
			array( 'as' => 'script' ),
		);
		$inline       = false;
		$this->nunil_tag_2 = new NUNIL\Nunil_HTML_Tag( $directive, $tag, $stored_attr, $needed_attrs, $childs = null, $inline );
    }

	
	/**
     * @dataProvider tagProvider_1
     */
    public function testXQueryCanBeCreatedFromTag_1( string $expected ): void
    {
		$Nunil_Manipulate_DOM = new NUNIL\Nunil_Manipulate_DOM();
        $this->assertEquals(
			$expected, $Nunil_Manipulate_DOM->build_xpath_query( $this->nunil_tag_1 )
        );
    }
	
	public function tagProvider_1(): array {
		$data = [
			'script-src' => [ "//script[(@src) and not(@type='text/html') and not(@type='text/template')]" ],
		];
		
		return $data;
	}

	/**
     * @dataProvider tagProvider_2
     */
    public function testXQueryCanBeCreatedFromTag_2( string $expected ): void
    {
		$Nunil_Manipulate_DOM = new NUNIL\Nunil_Manipulate_DOM();
        $this->assertEquals(
			$expected, $Nunil_Manipulate_DOM->build_xpath_query( $this->nunil_tag_2 )
        );
    }
	
	public function tagProvider_2(): array {
		$data = [
			'style-src'  => [ "//link[(@href) and (@rel='preload') and (@as='script')]" ]
		];
		
		return $data;
	}

		
}
