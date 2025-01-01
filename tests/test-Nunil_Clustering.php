<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class Nunil_Nunil_ClusteringTest extends \WP_UnitTestCase
{	
	
	private $nunil_clustering;
	private $gls;
	
	public function tear_down(): void {
		\NUNIL\no_unsafe_inline_deactivate( false );
		parent::tear_down();
	}

	public function set_up(): void {
		parent::set_up();
		\NUNIL\no_unsafe_inline_activate( false );

		$plugin = new \NUNIL\includes\No_Unsafe_Inline();
		$plugin->run();
		$this->nunil_clustering = new \NUNIL\Nunil_Clustering();
		$this->gls = new \NUNIL\Nunil_Global_Settings();
	}
	
	protected static function getMethod( $name ) {
		$class  = new ReflectionClass( '\NUNIL\Nunil_Clustering' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}
	
	/**
	 * @dataProvider provideObjHashes
	 * @group clustering
	 */
	public function test_make_db_scan( $obj_hashes, $table, $numClusters ){
		$nunil_make_db_scan = self::getMethod( 'make_db_scan' );
		$args                     = array(
			$obj_hashes,
			$table,
		);
		$results         = $nunil_make_db_scan->invokeArgs( $this->nunil_clustering, $args );
		
		$this->assertGreaterThanOrEqual( $numClusters, max( $results ) );
		
		return $results;
	}
	
	private static function convertRowsToObjHashes( $array_rows ) {
		$obj_hashes_inl = array();
		foreach ( $array_rows as $key => $value ) {
			$local_arr = [
				'ID' => $key,
				'nilsimsa' => $value,
			];
				
			$obj_hashes_inl[] = ( object ) $local_arr;
		}
		return $obj_hashes_inl;
	}
	
	public static function provideObjHashes() {
		$source = 'nilsimsa.list';
		$samples_raw_inline = file( __DIR__ . '/test-Clustering/' . $source );
		$obj_hashes_inl = self::convertRowsToObjHashes( $samples_raw_inline );
		
		return [
			[
				$obj_hashes_inl,
				'inline_scripts',
				14,
			]
		];
	}
}