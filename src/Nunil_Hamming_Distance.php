<?php
/**
 * Clustering distance function
 *
 * This class provides Custom Distance Function used for DBSCAN Clustering
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Rubix\ML\DataType;
use Beager\Nilsimsa;
use Rubix\ML\Kernels\Distance\Distance;

/**
 * Hamming Distance for Clustering
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Hamming_Distance implements Distance {
	/**
	 * Return the data types that this kernel is compatible with.
	 *
	 * @internal
	 *
	 * @return list<\Rubix\ML\DataType>
	 */
	public function compatibility(): array {
		return array(
			DataType::categorical(),
		);
	}

	/**
	 * Calculate distance between 2 Nilsimsa hex digests.
	 *
	 * @param array<int, string> $a Nilsimsa array digest.
	 * @param array<int, string> $b Nilsimsa array digest.
	 *
	 * @return float
	 */
	public function compute( array $a, array $b ): float {
		$similarity = Nilsimsa::compareDigests( $a[0], $b[0], $is_hex_1 = true, $is_hex_2 = true );

		$distance = floatval( 128 - $similarity );

		return $distance;
	}

	/**
	 * Return the string representation of the object.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public function __toString(): string {
		return 'Nilsimsa Hamming';
	}
}
