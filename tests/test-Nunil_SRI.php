<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class Nunil_SRITest extends WP_UnitTestCase {

	public function testisLocalResource(): void {
		$Nunil_SRI = new NUNIL\Nunil_SRI();
		$this->assertSame(
			$Nunil_SRI->isLocalResource( '//fonts.googleapis.com/css?family=Lora%3A400%2C400italic%2C700%2C700italic%7CMontserrat%3A400%2C700%7CMaven+Pro%3A400%2C700&ver=5.9' ),
			false
		);
		$this->assertSame(
			$Nunil_SRI->isLocalResource( 'https://fonts.googleapis.com/css?family=Lora%3A400%2C400italic%2C700%2C700italic%7CMontserrat%3A400%2C700%7CMaven+Pro%3A400%2C700&ver=5.9' ),
			false
		);
		$this->assertSame(
			$Nunil_SRI->isLocalResource( 'http://fonts.googleapis.com/css?family=Lora%3A400%2C400italic%2C700%2C700italic%7CMontserrat%3A400%2C700%7CMaven+Pro%3A400%2C700&ver=5.9' ),
			false
		);
		//~ $this->assertSame(
			//~ $Nunil_SRI->isLocalResource( 'wp-includes/js/backbone.js' ),
			//~ true
		//~ );
	}
}
