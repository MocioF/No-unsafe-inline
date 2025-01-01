<?php declare(strict_types=1);

final class Nunil_SRITest extends \WP_UnitTestCase {

	/**
	 * @dataProvider getResources
	 */
	public function testFetchResource( string $resource ): void {
		$Nunil_SRI         = new NUNIL\Nunil_SRI();
		$attended_response = array(
			'code'    => 200,
			'message' => 'OK',
		);

		$response = $Nunil_SRI->fetch_resource( $resource )['response'];

		$this->assertSame(
			$response,
			$attended_response
		);
	}

	public function getResources(): array {
		return array(
			array( '//fonts.googleapis.com/css?family=Lora%3A400%2C400italic%2C700%2C700italic%7CMontserrat%3A400%2C700%7CMaven+Pro%3A400%2C700&ver=5.9' ),
			array( 'https://fonts.googleapis.com/css?family=Lora%3A400%2C400italic%2C700%2C700italic%7CMontserrat%3A400%2C700%7CMaven+Pro%3A400%2C700&ver=5.9' ),
			array( 'http://www.example.org' )
		);
	}
}
