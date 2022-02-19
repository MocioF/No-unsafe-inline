<?php
/**
 * Sub Subresource Integrity class
 *
 * Class used to create integrity values for external scripts and styles.
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use League\Uri\UriString;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to create base -src rules for external content
 *
 * @package No unsafe inline
 * @since   1.0.0
 */
class Nunil_SRI {

	/**
	 * Parses url in array
	 *
	 * Parese url using League\Uri\UriString
	 *
	 * @since 1.0.0
	 * @param string $url The URL to be parsed.
	 * @return array<string, int|string|null>
	 */
	public function parse_url( $url ) {
		return UriString::parse( $url );
	}

	/**
	 * Check it uri is a local resource
	 *
	 * Checks a URL to determine whether or not the resource is "remote"
	 * (served by a third-party) or whether the resource is local (and
	 * is being served by the same webserver as this plugin is run on.)
	 *
	 * @since 1.0.0
	 * @param string $uri The URI of the resource to inspect.
	 * @return bool True if the resource is local, false if the resource is remote.
	 */
	public static function isLocalResource( $uri ) {
		$rsrc_host = UriString::parse( $uri )['host'];
		$this_host = UriString::parse( get_site_url() )['host'];
		if ( is_string( $rsrc_host ) && is_string( $this_host ) ) {
			return ( 0 === strpos( $rsrc_host, $this_host ) ) ? true : false;
		} else {
			return false;
		}
	}

	/**
	 * Fetch resource
	 *
	 * Fetch a resource using wp_remote_get
	 *
	 * @since 1.0.0
	 * @link https://plugins.trac.wordpress.org/browser/wp-sri/trunk/wp-sri.php#L138
	 * @param string $rsrc_url The resourse URL to fetch.
	 * @return array<mixed>|\WP_Error
	 */
	public function fetch_resource( $rsrc_url ) {
		$url          = ( 0 === strpos( $rsrc_url, '//' ) )
			? ( ( is_ssl() ) ? "https:$rsrc_url" : "http:$rsrc_url" )
			: $rsrc_url;
			$response = wp_remote_get( $url );
		return $response;
	}

	/**
	 * Insert calculated hashes in database
	 *
	 * @since 1.0.0
	 * @param int|array<int|string> $id The id of the _external_script record.
	 * @param bool                  $overwrite True to overwrite existing hashes.
	 * @return void
	 */
	public function put_hashes_in_db( $id, $overwrite = false ): void {
		if ( ! is_array( $id ) ) {
			$my_ids   = array();
			$my_ids[] = $id;
		} else {
			$my_ids = $id;
		}

		foreach ( $my_ids as $id ) {

			// $wpdb->get_var always return a string.
			$id = intval( $id );

			$data = DB::get_ext_hashes_from_id( $id );
			if ( ! is_null( $data ) ) {
				$response = $this->fetch_resource( $data->src_attrib );

				if ( ! is_wp_error( $response ) ) {
					$body = wp_remote_retrieve_body( $response );
					if ( true === $overwrite ) {
						$data->sha256 = Nunil_Capture::calculate_hash( 'sha256', $body, $utf8 = false );
						$data->sha384 = Nunil_Capture::calculate_hash( 'sha384', $body, $utf8 = false );
						$data->sha512 = Nunil_Capture::calculate_hash( 'sha512', $body, $utf8 = false );
					} else {
						$data->sha256 = ( is_null( $data->sha256 ) ) ? Nunil_Capture::calculate_hash( 'sha256', $body, $utf8 = false ) : $data->sha256;
						$data->sha384 = ( is_null( $data->sha384 ) ) ? Nunil_Capture::calculate_hash( 'sha384', $body, $utf8 = false ) : $data->sha384;
						$data->sha512 = ( is_null( $data->sha512 ) ) ? Nunil_Capture::calculate_hash( 'sha512', $body, $utf8 = false ) : $data->sha512;
					}

					$whitelist = DB::get_ext_wl( $data );

					$format = array( '%s', '%s', '%s' );

					if ( 1 === $whitelist ) {
						$data->whitelist = $whitelist;
						array_push( $format, '%d' );
					}

					$data = (array) $data;

					unset( $data['src_attrib'] );

					$affected = DB::update_ext_hashes( $data, $id, $format );

				} else {
					Log::warning( 'Unable to fetch ' . $data->src_attrib );
				}
			} else {
				Log::warning( 'Unable to get hashes of script with ID: ' . $id );
			}
		}
	}
}
