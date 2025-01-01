<?php declare(strict_types=1);

final class Nunil_Lib_UtilsTest extends \WP_UnitTestCase
{
	/**
	 * @dataProvider provide_vars
	 */
	public function testis_array_of_integer_strings( $var, bool $expected ) {
		$check = NUNIL\Nunil_Lib_Utils::is_array_of_integer_strings( $var );
		
		$this->assertEquals(
			$expected, $check
        );
	}
	
	public function provide_vars(): array {
		return [
		[ array( 1, 2, 3 ), false ],
		[ array( 0 ), false ],
		[ array( '0', '1', '120', '23' ), true ],
		[ 1, false ],
		[ 0, false ],
		[ true, false ],
		[ false, false ],
		['100', false ],
		[ array( '1' ), true ],
		];
	}

	
}
