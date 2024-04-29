<?php
/**
 * Base src Rules
 *
 * Class used to create "base" rules for external scripts in db.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use League\Uri\UriString;
use NUNIL\Nunil_Lib_Utils as Utils;

/**
 * Class with methods used to create base -src rules for external content
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Base_Src_Rules {

	/**
	 * The CPS -src directives.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var array<string> An ARRAY_N of strings of managed CSP -src directives.
	 */
	private $directives;

	/**
	 * An array of URSL parsed by UriString::parse.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var array<array{
	 *     scheme:?string,
	 *     user:?string,
	 *     pass:?string,
	 *     host:?string,
	 *     port:?int,
	 *     path:string,
	 *     query:?string,
	 *     fragment:?string
	 * }> Array of League\Uri\UriString::parse results.
	 */
	private $parsed_urls;

	/**
	 * The class constructor.
	 *
	 * Set directives.
	 * Calls the WP-List child class to print the table.
	 *
	 * @since    1.0.0
	 * @throws \NUNIL\Nunil_Exception The directives var is not an array.
	 */
	public function __construct() {
		$cache_key   = 'external_scripts_directives';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = 10;

		$directives = wp_cache_get( $cache_key, $cache_group );
		if ( false === $directives ) {
			$directives = Nunil_Lib_Db::get_directive_in_ext();

			wp_cache_set( $cache_key, $directives, $cache_group, $expire_secs );
		}
		if ( is_array( $directives ) ) {
			$this->directives = $directives;
		} else {
			$message = __( 'Error retriving base src rules: $directives should be an array', 'no-unsafe-inline' );
			throw new Nunil_Exception( esc_html( $message ), 3004, 3 );
		}
	}

	/**
	 * Returns an array of ARRAY_A with keys: [ID] [directive] [source]
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array<array{ID: int, directive: string, source: string}>>
	 */
	public function get_db_entries() {
		$results = array();
		$id      = 1;
		foreach ( $this->directives as $directive ) {
			$ext_scripts       = $this->get_db_entry( $directive[0] );
			$this->parsed_urls = $this->parse_db_entry( $ext_scripts );
			$host_sources      = $this->get_host_source( $this->parsed_urls );
			$scheme_sources    = $this->get_scheme_source( $this->parsed_urls );

			asort( $scheme_sources );
			asort( $host_sources );

			foreach ( $scheme_sources as $scheme ) {
				$results[] = ( array(
					'ID'        => $id,
					'directive' => strval( $directive[0] ),
					'source'    => $scheme,
				) );
				++$id;
			}

			foreach ( $host_sources as $source ) {
				$results[] = ( array(
					'ID'        => $id,
					'directive' => strval( $directive[0] ),
					'source'    => $source,
				) );
				++$id;
			}
		}
		return $results;
	}

	/**
	 * Get all db's entries for a CSP -src directive for external content.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param string $directive The CPS -src directive.
	 *
	 * @return array<\stdClass>|null Array of OBJECT returned by wpdb::get_results
	 */
	private function get_db_entry( $directive ) {
		$cache_key   = 'external_scripts_src-attr_' . $directive;
		$cache_group = 'no-unsafe-inline';
		$expire_secs = 10;

		$ext_scripts = wp_cache_get( $cache_key, $cache_group );
		if ( false === $ext_scripts ) {
			$ext_scripts = Nunil_Lib_Db::get_attrs_for_dir_in_ext( $directive );

			wp_cache_set( $cache_key, $ext_scripts, $cache_group, $expire_secs );
		}
		if ( is_array( $ext_scripts ) ) {
			return $ext_scripts;
		} else {
			return null;
		}
	}


	/**
	 * Return an array of parsed URI.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @param array<\stdClass>|null $ext_scripts Array of OBJECT returned by wpdb::get_results .
	 * @return array<array{
	 *     scheme:?string,
	 *     user:?string,
	 *     pass:?string,
	 *     host:?string,
	 *     port:?int,
	 *     path:string,
	 *     query:?string,
	 *     fragment:?string
	 * }> Array of League\Uri\UriString::parse results.
	 */
	private function parse_db_entry( $ext_scripts ) {
		$parsed_urls = array();
		if ( ! is_null( $ext_scripts ) ) {
			foreach ( $ext_scripts as $script ) {
				$parsed_urls[] = UriString::parse( stripslashes( $script->src_attrib ) );
			}
		}
		return $parsed_urls;
	}

	/**
	 * Return an array of external host/domain in external table.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @param array<array{ scheme:?string, user:?string, pass:?string, host:?string, port:?int, path:string, query:?string, fragment:?string }> $parsed_urls Array of League\Uri\UriString::parse results.
	 * @return array<string>
	 */
	private function get_host_source( $parsed_urls ) {
		$mode         = $this->get_nunil_external_host_mode_option();
		$host_sources = array();

		if ( true === is_multisite() ) {
			$local_home_url = get_bloginfo( 'url' );
		} else {
			$local_home_url = get_home_url();
		}
		foreach ( $parsed_urls as $parsed_url ) {
			$site_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

			if ( $local_home_url === $site_url ) {
				$host = '\'self\'';
			} elseif ( ! isset( $parsed_url['host'] ) ) {
				switch ( $parsed_url['path'] ) {
					case '\'unsafe-eval\'':
						$host = '\'unsafe-eval\'';
						break;
					case '\'strict-dynamic\'':
						$host = '\'strict-dynamic\'';
						break;
					case 'data':
						$host = 'data:';
						break;
					default:
						$host = '\'self\'';
				}
			} else {
				switch ( $mode ) {
					case 'host':
						$host = $parsed_url['host'];
						break;
					case 'domain':
						$host = '*.' . $this->get_domain_from_host( $parsed_url['host'] );
						break;
					case 'sch-host':
						if ( isset( $parsed_url['scheme'] ) ) {
							$host = $site_url;
						} else {
							$host = $parsed_url['host'];
						}
						break;
					case 'resource':
						$host = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
						break;
				}
			}
			if ( empty( $host ) ) {
				$host = '\'self\'';
			}
			if ( ! in_array( $host, $host_sources, true ) ) {
				array_push( $host_sources, $host );
			}
		}
		return $host_sources;
	}


	/**
	 * Return a domain name from hostname
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @param string $host An hostname.
	 * @return string
	 */
	private function get_domain_from_host( $host ) {
		$host_names       = explode( '.', $host );
		$bottom_host_name = $host_names[ count( $host_names ) - 2 ] . '.' . $host_names[ count( $host_names ) - 1 ];

		return $bottom_host_name;
	}

	/**
	 * Return an array of sheme sources.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @param array<array{ scheme:?string, user:?string, pass:?string, host:?string, port:?int, path:string, query:?string, fragment:?string }> $parsed_urls Array of League\Uri\UriString::parse results.
	 * @return array<string>
	 */
	public function get_scheme_source( $parsed_urls ) {
		$scheme_sources = array();
		foreach ( $parsed_urls as $parsed_url ) {
			if ( isset( $parsed_url['scheme'] ) ) {
				$scheme = $parsed_url['scheme'] . ':';
				if ( ! in_array( $scheme, $scheme_sources, true ) ) {
					array_push( $scheme_sources, $scheme );
				}
			}
		}
		return $scheme_sources;
	}

	/**
	 * Return the external_host_mode settings option.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @return string
	 */
	private function get_nunil_external_host_mode_option() {
		$options = (array) get_option( 'no-unsafe-inline' );
		if ( isset( $options['external_host_mode'] ) ) {
			return strval( Utils::cast_strval( $options['external_host_mode'] ) );
		} else {
			return 'sch-host';
		}
	}
}
