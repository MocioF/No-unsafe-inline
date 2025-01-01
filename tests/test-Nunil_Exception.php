<?php declare(strict_types=1);

use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\log\Nunil_Lib_Log_Db as DBLog;

final class Nunil_ExceptionTest extends \WP_UnitTestCase {
	
	public function tear_down(): void {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	public function set_up(): void {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );

		$plugin = new \NUNIL\includes\No_Unsafe_Inline();
		$plugin->run();
		$dblog = new DBLog;
		Log::init( $dblog , 0 );
	}
	
	/**
	 * @dataProvider raisedExceptions
	 * @group exceptions
	 */
	public function test_logexception( $e, $expected_level, $expected_count) {
		global $wpdb;

		$customException = new NUNIL\Nunil_Exception( ...$e );
		$customException->logexception();

		$sql = 'SELECT * FROM `' . $wpdb->prefix . 'nunil_logs`';
		$res = $wpdb->get_results( $sql, OBJECT );

		$this->assertSame( $expected_count, count( $res ), "db res is:\n" . print_r( $res, true ) );

		$this->assertEquals( $expected_level, $res[0]->level );
	}

	public static function raisedExceptions() {
		return array(
			array( array(), 'debug', 1 ),
			array( array( 'only message' ), 'debug', 1 ),
			array( array( 'message and code', 0001 ), 'debug', 1 ),
			array( array( 'message and code', '0001' ), 'debug', 1 ),
			array( array( 'message, code and level debug', 0001, 0 ), 'debug', 1 ),
			array( array( 'message, code and level debug', '0001', 0 ), 'debug', 1 ),
			array( array( 'message, code and level info', 1001, 1 ), 'info', 1 ),
			array( array( 'message, code and level warning', 2001, 2 ), 'warning', 1 ),
			array( array( 'message, code and level error', 3001, 3 ), 'error', 1 ),
		);
	}
}