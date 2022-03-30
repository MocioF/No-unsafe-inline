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

use Phpml\Math\Distance;
use Beager\Nilsimsa;

/**
 * Hamming Distance for Clustering
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Hamming_Distance implements Distance {

	/**
	 * Calculate distance between 2 Nilsimsa array digests.
	 *
	 * @param array<int> $a Nilsimsa array digest.
	 * @param array<int> $b Nilsimsa array digest.
	 *
	 * @return float
	 */
	public function distance( array $a, array $b ) : float {
		$a_hex = Nunil_Clustering::convertArraytoHexDigest( $a );
		$b_hex = Nunil_Clustering::convertArraytoHexDigest( $b );

		$similarity = Nilsimsa::compareDigests( $a_hex, $b_hex, $is_hex_1 = true, $is_hex_2 = true );

		$distance = floatval( 128 - $similarity );

		return $distance;
	}
}

