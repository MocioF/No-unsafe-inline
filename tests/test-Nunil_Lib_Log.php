<?php declare(strict_types=1);

use NUNIL\Nunil_Lib_Log as Log;

final class Nunil_Lib_LogTest extends \WP_UnitTestCase
{
	public function tear_down(): void {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}
	
    public function set_up(): void {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );
		
		// setting log level to debug
		$options = get_option( 'no-unsafe-inline');
		$options['log_level'] = 'debug';
		$options['log_driver'] = 'db';
		$success = update_option( 'no-unsafe-inline', $options );
		$this->assertEquals(
			true, $success
        );
        $plugin = new \NUNIL\includes\No_Unsafe_Inline;
        $plugin->load_logger();
	}
	
	public function verify_log_enabled(): void {
		$options = get_option( 'no-unsafe-inline');
		$log_level = $options['log_level'];
		$log_driver = $options['log_driver'];
		
		$this->assertEquals(
			$log_level, 'error'
        );
		
		$this->assertEquals(
			$log_driver, 'db'
        );
	}
	
	/**
	 * @dataProvider logsInputArray
	 */
	public function testLogWriter( string $method, int $level, string $message ) {
		global $wpdb;

		Log::$method( $message );
		
		$sql = 'SELECT `level`, `message` FROM ' . $wpdb->prefix . 'nunil_logs';
		$logged = $wpdb->get_results( $sql, OBJECT );

		$obj=new stdClass;
		$obj->level=$method;
		$obj->message=$message;
		
		$expected = array( $obj );
		
		$this->assertEquals(
			$expected, $logged
        );
	}
	
	public function logsInputArray(): array {
		return [
			'debug'=> [ 'debug', 0, 'This is a debug log' ],
			'info' => [ 'info', 1, 'This is an info log' ],
			'warning'=> [ 'warning', 2, 'This is a warning log' ],
			'error'=> [ 'error', 3, 'This is an error log' ]
		];
	}
}
