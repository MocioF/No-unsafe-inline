<?php
/**
 * Manipulate DOM
 *
 * Class used to manipulate the DOM including hashes/nonce and creating an inline script.
 *
 * @package No unsafe inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use IvoPetkov\HTML5DOMDocument;
use Beager\Nilsimsa;
use Phpml\Classification\KNearestNeighbors;
use Spatie\Async\Pool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to manipulate the DOM
 *
 * @package No unsafe inline
 * @since   1.0.0
 */
class Nunil_Manipulate_DOM extends Nunil_Capture {
	/**
	 * The array of external scripts objects
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass> Array of event handlers rows.
	 */
	private $external_rows;

	/**
	 * The array of inline scripts objects
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass> Array of inline table rows
	 */
	private $inline_rows;

	/**
	 * The array of event handlers scripts objects
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass> Array of event handlers rows.
	 */
	private $events_rows;

	/**
	 * Inline scripts csp mode from user preferences
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $inline_scripts_mode One of 'nonce', 'sha256', 'sha384', 'sha512'
	 */
	private $inline_scripts_mode;

	/**
	 * Array of [src-directive] [hash or nonce] for whitelisted inline scripts
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<array{directive: string, source: string}> $csp_local_whitelist returned by get method.
	 */
	private $csp_local_whitelist;

	/**
	 * String with manipulated HTML.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $manipulated_html HTML returned by get method.
	 */
	private $manipulated_html;

	/**
	 * Inline script injected to substitute event handlers.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $injected_inline_script Script tag injected in the page.
	 */
	private $injected_inline_script;

	/**
	 * Inline style injected to substitute inline style attributes.
	 * CSP3 browser will not allow inline style= withouth 'unsafe-hashes' source list.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $injected_inline_style Style tag injected in the page.
	 */
	private $injected_inline_style;

	/**
	 * Classifier used for inline <script>
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Phpml\Classification\KNearestNeighbors|null $inline_scripts_classifier The KNearestNeighbors Classifier for <script>
	 */
	private $inline_scripts_classifier;

	/**
	 * Classifier used for inline <style>
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Phpml\Classification\KNearestNeighbors|null $internal_css_classifier The KNearestNeighbors Classifier for <style>
	 */
	private $internal_css_classifier;

	/**
	 * Classifier used for event handlers' scripts
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Phpml\Classification\KNearestNeighbors|null $event_handlers_classifier The KNearestNeighbors Classifier for event handlers scripts.
	 */
	private $event_handlers_classifier;

	/**
	 * Nonce used for the page if $inline_scripts_mode is set to 'nonce'
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $page_nonce The page nonce.
	 */
	private $page_nonce;

	/**
	 * Making asyncronous insert in Database
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Spatie\Async\Pool $insert_pool The pool used for insert in DB labelled scripts
	 */
	private $insert_pool;

	/**
	 * The class constructor.
	 *
	 * Set the domdocument attribute.
	 * Set the inl_scripts array.
	 * Set the evh_scripts array.
	 * Set the inline_scripts_mode string.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct();

		global $wpdb;

		$plugin_options = get_option( 'no-unsafe-inline' );
		$tools          = get_option( 'no-unsafe-inline-tools' );

		if ( ! is_array( $plugin_options ) ) {
			throw new \Exception( 'get_option( \'no-unsafe-inline\' ) did not return an array.' );
		}

		if ( ! is_array( $tools ) ) {
			throw new \Exception( 'get_option( \'no-unsafe-inline-tools\' ) did not return an array.' );
		}

		$gls = new Nunil_Global_Settings();

		/* performs DB queries and set them in the private properties */
		$cache_key   = 'inline_rows';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = $gls->expire_secs[ $cache_key ];
		/**
		 * When capturing is enabled with a protection policy enabled or in test, we need NOT to
		 * use cache to avoid not upgrading clusternames dinamically
		 */
		if ( 1 === $tools['capture_enabled'] ) {
			wp_cache_delete( $cache_key, $cache_group );
		}

		$inline_rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $inline_rows ) {
			$sql         = 'SELECT sha256, sha384, sha512, nilsimsa, clustername, whitelist, tagname FROM `'
			. NO_UNSAFE_INLINE_TABLE_PREFIX . 'inline_scripts`;';
			$inline_rows = $wpdb->get_results( $sql, 'OBJECT' );
			wp_cache_set( $cache_key, $inline_rows, $cache_group, $expire_secs );
		}
		$this->inline_rows = $inline_rows;

		$cache_key   = 'events_rows';
		$cache_group = 'no-unsafe-inline';
		$expire_secs = $gls->expire_secs[ $cache_key ];
		/**
		 * When capturing is enabled with a protection policy enabled or in test, we need NOT to
		 * use cache to avoid not updating clusternames after recluster.
		 */
		if ( 1 === $tools['capture_enabled'] ) {
			wp_cache_delete( $cache_key, $cache_group );
		}

		$events_rows = wp_cache_get( $cache_key, $cache_group );
		if ( false === $events_rows ) {
			$sql = 'SELECT sha256, sha384, sha512, nilsimsa, clustername, whitelist, event_attribute, tagname, tagid FROM `'
			. NO_UNSAFE_INLINE_TABLE_PREFIX . 'event_handlers`;';

			$events_rows = $wpdb->get_results( $sql, 'OBJECT' );
			wp_cache_set( $cache_key, $events_rows, $cache_group, $expire_secs );
		}
		$this->events_rows = $events_rows;

		if ( 1 === $plugin_options['sri_script'] || 1 === $plugin_options['sri_link'] ) {
			$cache_key   = 'external_rows';
			$cache_group = 'no-unsafe-inline';
			$expire_secs = $gls->expire_secs[ $cache_key ];

			$external_rows = wp_cache_get( $cache_key, $cache_group );
			if ( false === $external_rows ) {
				$sql = 'SELECT `ID`, `directive`, `tagname`, `src_attrib`, `sha256`, `sha384`, `sha512`, `whitelist` FROM `'
				. NO_UNSAFE_INLINE_TABLE_PREFIX . 'external_scripts`'
				. 'WHERE `tagname`=\'link\' OR `tagname`=\'script\';';

				$external_rows = $wpdb->get_results( $sql, 'OBJECT' );
				wp_cache_set( $cache_key, $external_rows, $cache_group, $expire_secs );
			}
			$this->external_rows = $external_rows;
		}

		$this->inline_scripts_mode = strval( $plugin_options['inline_scripts_mode'] );

		$this->page_nonce = $this->generate_nonce();

		if ( 1 === $plugin_options['script-src_enabled'] ) {
			$inline_scripts_samples = array();
			$inline_scripts_labels  = array();
			foreach ( $inline_rows as $row ) {
				if ( 'script' === $row->tagname ) {
					$inline_scripts_samples[] = Nunil_Clustering::convertHexDigestToArray( $row->nilsimsa );
					$inline_scripts_labels[]  = $row->clustername;
				}
			}
			if ( $inline_scripts_samples && $inline_scripts_labels ) {
				$this->inline_scripts_classifier = new KNearestNeighbors( $gls->knn_k_inl, new Nunil_Hamming_Distance() );
				$this->inline_scripts_classifier->train( $inline_scripts_samples, $inline_scripts_labels );
			}
		}

		if ( 1 === $plugin_options['style-src_enabled'] ) {
			$internal_css_samples = array();
			$internal_css_labels  = array();
			foreach ( $inline_rows as $row ) {
				if ( 'style' === $row->tagname ) {
					$internal_css_samples[] = Nunil_Clustering::convertHexDigestToArray( $row->nilsimsa );
					$internal_css_labels[]  = $row->clustername;
				}
			}
			if ( $internal_css_samples && $internal_css_labels ) {
				$this->internal_css_classifier = new KNearestNeighbors( $gls->knn_k_inl, new Nunil_Hamming_Distance() );
				$this->internal_css_classifier->train( $internal_css_samples, $internal_css_labels );
			}
		}

		// Create classifier for event_handlers $event_handlers_classifier.
		if ( 0 === $plugin_options['use_unsafe-hashes'] ) {
			$evh_samples = array();
			$evh_labels  = array();
			foreach ( $events_rows as $row ) {
				$evh_samples[] = Nunil_Clustering::convertHexDigestToArray( $row->nilsimsa );
				$evh_labels[]  = $row->event_attribute . '#' . $row->clustername;
			}
			if ( $evh_samples && $evh_labels ) {
				$this->event_handlers_classifier = new KNearestNeighbors( $gls->knn_k_evh, new Nunil_Hamming_Distance() );
				$this->event_handlers_classifier->train( $evh_samples, $evh_labels );
			}
		}

		// Used to make asyncronous insert in DB.
		$this->insert_pool = Pool::create()
			->concurrency( 20 )
			->timeout( 15 )
			->sleepTime( 50000 );
	}

	/**
	 * Check if manipulation has been performed and if not, performs it,
	 * then returns the manipulated HTML.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string The manipulated HTML.
	 */
	public function get_manipulated() {
		if ( ! $this->csp_local_whitelist ) {
			$this->manipulate_inline_scripts();
			$this->manipulate_external_scripts();
		}
		return $this->domdocument->saveHTML();
	}

	/**
	 * Check if manipulation has been performed and if not, performs it,
	 * then returns the array containing local sources for script-src and style-src.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array<array{directive: string, source: string}> The csp_local_whitelist [-src directive] [source].
	 */
	public function get_local_csp() {
		if ( ! $this->csp_local_whitelist ) {
			$this->manipulate_inline_scripts();
			$this->manipulate_external_scripts();
		}
		return $this->csp_local_whitelist;
	}

	/**
	 * Manipulates inline scripts adding nonce or hash.
	 * Appending nonce or hash to $csp_local_whitelist-
	 * Manipulated HTML goes in $manipulated_html property.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function manipulate_inline_scripts(): void {

		// ~ try {
			$plugin_options = (array) get_option( 'no-unsafe-inline' );
			// ~ if ( ! is_array( $plugin_options ) ) {
				// ~ throw new \Exception('get_option( \'no-unsafe-inline\' ) did not return an array.');
			// ~ } catch ( Exception $e ) {

		// ~ }

		if ( 1 === $plugin_options['script-src_enabled'] ) {
			if ( 'nonce' === $this->inline_scripts_mode ) {
				$this->csp_local_whitelist[] = array(
					'directive' => 'script-src',
					'source'    => 'nonce-' . $this->page_nonce,
				);
			}
			$inline_scripts_node_list = $this->get_inline_scripts();
			if ( $inline_scripts_node_list && 0 < $inline_scripts_node_list->length ) {
				// Allows whitelist inline scripts.
				$this->allow_inline( $inline_scripts_node_list, 'script' );
			}
			if ( 0 === $plugin_options['use_unsafe-hashes'] ) {
				// Creates custom inline javascript for eventhandlers.
				$this->script_clean_unsafe_hashes();
				$this->inject_inline_script();
			} else {
				// If explicitly asked: use unsafe-hashes (discouraged).
				$this->csp_local_whitelist[] = array(
					'directive' => 'script-src',
					'source'    => 'unsafe-hashes',
				);
			}
		}

		if ( 1 === $plugin_options['style-src_enabled'] ) {
			if ( 'nonce' === $this->inline_scripts_mode ) {
				$this->csp_local_whitelist[] = array(
					'directive' => 'style-src',
					'source'    => 'nonce-' . $this->page_nonce,
				);
			}
			$internal_css_node_list = $this->get_internal_css();
			if ( $internal_css_node_list && 0 < $internal_css_node_list->length ) {
				// Allows whitelist inline styles (internal css).
				$this->allow_inline( $internal_css_node_list, 'style' );
			}
			if ( 0 === $plugin_options['use_unsafe-hashes'] ) {
				// Creates custom internal css for inline styles.
				$this->style_clean_unsafe_hashes();
				$this->inject_inline_style();
			} else {
				// If explicitly asked: use unsafe-hashes (discouraged).
				$this->csp_local_whitelist[] = array(
					'directive' => 'style-src',
					'source'    => 'unsafe-hashes',
				);
			}
		}
	}

	/**
	 * Manipulates external scripts and style adding integrity and cross origin attrs,
	 * when using SRI.
	 * Appending hashes to $csp_local_whitelist
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function manipulate_external_scripts(): void {
		$plugin_options = (array) get_option( 'no-unsafe-inline' );

		if ( 1 === $plugin_options['script-src_enabled'] && 1 === $plugin_options['sri_script'] ) {
			$external_scripts_node_list = $this->get_external_js();
			if ( $external_scripts_node_list ) {
				foreach ( $external_scripts_node_list as $node ) {
					$index = $this->check_external_whitelist( $node );

					if ( ! is_null( $index ) ) {
						$this->manipulate_external_node( $node, $index, 'script-src' );
					}
				}
			}
			if ( 1 === $plugin_options['use_strict-dynamic'] ) {
				$this->csp_local_whitelist[] = array(
					'directive' => 'script-src',
					'source'    => 'strict-dynamic',
				);
			}
		}

		if ( 1 === $plugin_options['style-src_enabled'] && 1 === $plugin_options['sri_link'] ) {
			$external_style_node_list = $this->get_external_css();
			if ( $external_style_node_list ) {
				foreach ( $external_style_node_list as $node ) {
					$index = $this->check_external_whitelist( $node );
					if ( ! is_null( $index ) ) {
						$this->manipulate_external_node( $node, $index, 'style-src' );
					}
				}
			}
		}

	}

	/**
	 * Allow an inline script or style
	 *
	 * @since 1.0.0
	 * @access private;
	 * @param \DOMNodeList<\DOMNode> $inline_list The DOMNodeList.
	 * @param string                 $tagname One of script or style.
	 * @retunr void
	 */
	private function allow_inline( $inline_list, $tagname ): void {
		if ( 0 < $inline_list->length ) {
			$directive = $tagname . '-src';
			foreach ( $inline_list as $node ) {

				$content = $node->textContent;
				$content = $this->clean_text_content( $content );
				$hashes  = $this->get_hashes( $content, $utf8 = true );

				$wl_index = $this->check_single_whitelist( $hashes, $tagname );
				if ( $wl_index ) {

					$this->allow_whitelisted( $node, $hashes, $directive );
					// Devi aggiornare un lastseen ?
				} else {
					$lsh        = new Nilsimsa( $content );
					$lsh_digest = $lsh->digest();
					$wl_cluster = $this->check_cluster_whitelist( $lsh_digest, $tagname );

					if ( $wl_cluster ) {
						$this->allow_whitelisted( $node, $hashes, $directive );

						$nilsimsa = $lsh->hexDigest();
						/* Start async block. */
						$this->insert_new_inline_in_db( $tagname, $content, $hashes, $nilsimsa, $wl_cluster );
						/* End async block. */
					}
				}
			}
		}
	}

	/**
	 * Select all script element without src attribute and with type != text/html
	 *
	 * @since 1.0.0
	 * @access private
	 * @return \DOMNodeList<\DOMNode>|false
	 */
	private function get_inline_scripts() {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = "//script[not(@src) and not(@type='text/html') and not(@type='text/template')]";
		$nodelist     = $x->query( $x_path_query );
		return $nodelist;
	}

	/**
	 * Select all <style> tags (internal CSS)
	 *
	 * @since 1.0.0
	 * @access private
	 * @return \DOMNodeList<\DOMNode>|false
	 */
	private function get_internal_css() {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = '//style[not(@src)]';
		$nodelist     = $x->query( $x_path_query );
		return $nodelist;
	}

	/**
	 * Select all <link> tags with rel="stylesheet" (external CSS)
	 *
	 * @since 1.0.0
	 * @access private
	 * @return \DOMNodeList<\DOMNode>|false
	 */
	private function get_external_css() {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = "//link[@rel='stylesheet']";
		$nodelist     = $x->query( $x_path_query );
		return $nodelist;
	}

	/**
	 * Select all <script> tags with src and not type='text/html' or 'text/template'
	 *
	 * @since 1.0.0
	 * @access private
	 * @return \DOMNodeList<\DOMNode>|false
	 */
	private function get_external_js() {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = "//script[@src and not(@type='text/html') and not(@type='text/template')]";
		$nodelist     = $x->query( $x_path_query );
		return $nodelist;
	}

	/**
	 * Generates a random nonce
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string
	 */
	private function generate_nonce() {
		$bytes = random_bytes( 6 );

		return bin2hex( $bytes );
	}

	/**
	 * Check if a single external script or style is whitelisted
	 *
	 * @access private
	 * @param \DomElement $node The selected DomNode.
	 * @return int|false|null The Index of array if whitelisted, false if not whitelist, null if not found.
	 */
	private function check_external_whitelist( $node ) {
		switch ( $node->nodeName ) {
			case 'link':
				$src_attrib = $node->getAttribute( 'href' );
				break;
			case 'script':
				$src_attrib = $node->getAttribute( 'src' );
				break;
			default:
				return false;
		}

		$list = $this->external_rows;
		/**
		 * TODO: See Nunil_Capture.php:730
		 */
		if ( 0 < count( $list ) ) {
			foreach ( $list as $index => $obj ) {
				if ( $src_attrib === $obj->src_attrib ) {
					if ( '1' === $obj->whitelist ) {
						return (int) $index;
					} else {
						return false;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Adds integrity attributes to whitelisted nodes with script or link tagname.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DomElement $node The DomNode passed by reference.
	 * @param int|false   $index The index of the whitelisted node in $this->external_rows array.
	 * @param string      $directive The -src directive.
	 * @return void
	 */
	private function manipulate_external_node( &$node, $index = null, $directive ): void {
		$plugin_options = (array) get_option( 'no-unsafe-inline' );
		$use256         = ( 1 === $plugin_options['sri_sha256'] ) ? true : false;
		$use384         = ( 1 === $plugin_options['sri_sha384'] ) ? true : false;
		$use512         = ( 1 === $plugin_options['sri_sha512'] ) ? true : false;

		if ( ! is_null( $index ) ) { // The src_attrib is in the DB, but it could be whitelisted or not.
			if ( false !== $index ) { // The node is whitelisted.
				if ( ! $node->hasAttribute( 'integrity' ) ) { // We don't modify integrity attrs setted by others plugins.
					$integrity_string = '';
					if ( $use256 && '' !== $this->external_rows[ $index ]->sha256 ) {
						$hash_with_options           = 'sha256-' . $this->external_rows[ $index ]->sha256;
						$integrity_string            = $integrity_string . $hash_with_options . ' ';
						$this->csp_local_whitelist[] = array(
							'directive' => $directive,
							'source'    => $hash_with_options,
						);
					}
					if ( $use384 && '' !== $this->external_rows[ $index ]->sha384 ) {
						$hash_with_options           = 'sha384-' . $this->external_rows[ $index ]->sha384;
						$integrity_string            = $integrity_string . $hash_with_options . ' ';
						$this->csp_local_whitelist[] = array(
							'directive' => $directive,
							'source'    => $hash_with_options,
						);
					}
					if ( $use512 && '' !== $this->external_rows[ $index ]->sha512 ) {
						$hash_with_options           = 'sha512-' . $this->external_rows[ $index ]->sha512;
						$integrity_string            = $integrity_string . $hash_with_options . ' ';
						$this->csp_local_whitelist[] = array(
							'directive' => $directive,
							'source'    => $hash_with_options,
						);
					}
					$node->setAttribute( 'integrity', trim( $integrity_string ) );
					if ( ! $node->hasAttribute( 'crossorigin' ) ) {
						$node->setAttribute( 'crossorigin', 'anonymous' );
					}
				} else { // The node has got integrity by other way. Just whitelist it.
					$integrity        = $node->getAttribute( 'integrity' );
					$crossorigin      = $node->getAttribute( 'crossorigin' );
					$integrity_values = preg_split( '/\s+/', $integrity, -1 );
					if ( $integrity_values ) {
						foreach ( $integrity_values as $hash_with_options ) {
							$this->csp_local_whitelist[] = array(
								'directive' => $directive,
								'source'    => $hash_with_options,
							);
						}
					}
				}
			} else { // The node is not whitelisted. Just add integrity if we know it and it doesn't have.
				if ( ! $node->hasAttribute( 'integrity' ) ) {
					$integrity_string = '';
					if ( $use256 && '' !== $this->external_rows[ $index ]->sha256 ) {
						$hash_with_options = 'sha256-' . $this->external_rows[ $index ]->sha256;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}
					if ( $use384 && '' !== $this->external_rows[ $index ]->sha384 ) {
						$hash_with_options = 'sha384-' . $this->external_rows[ $index ]->sha384;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}
					if ( $use512 && '' !== $this->external_rows[ $index ]->sha512 ) {
						$hash_with_options = 'sha512-' . $this->external_rows[ $index ]->sha512;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}
					$node->setAttribute( 'integrity', trim( $integrity_string ) );
				}
				if ( ! $node->hasAttribute( 'crossorigin' ) ) {
					$node->setAttribute( 'crossorigin', 'anonymous' );
				}
			}
		}
	}

	/**
	 * Check if a inline script or style is directly whitelisted
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array<string> $hashes The sha hashes array.
	 * @param string        $tagname The tagname: script or style.
	 * @param string        $event The event_handler (used when checkin an hash)
	 * @return mixed The Index of array if whitelisted, else false
	 */
	private function check_single_whitelist( $hashes, $tagname, $event = null ) {
		$in_use = $hashes['in_use'];
		$fhash  = $hashes[ $in_use ];

		if ( ! is_null( $event ) ) {
			$list = $this->events_rows;
		} else {
			$list = $this->inline_rows;
		}
		foreach ( $list as $index => $obj ) {
			if ( $fhash === $obj->$in_use && $tagname === $obj->tagname && ( null === $event || $event === $obj->event_attribute ) ) {
				if ( '1' === $obj->whitelist ) {
					return $index;
				} else {
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Check if a inline script or style is classified in a whitelist cluster
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array<int> $lsh_digest Nilsimsa digest.
	 * @param string     $tagname The tagname.
	 * @param string     $event Event handlers html attribute.
	 * @return string|false The clustername if whitelisted, else false
	 */
	private function check_cluster_whitelist( $lsh_digest, $tagname, $event = null ) {
		if ( 'style' === $tagname && isset( $this->internal_css_classifier ) ) {
			$predicted_label = strval( $this->internal_css_classifier->predict( $lsh_digest ) );
			$list            = $this->inline_rows;
		} elseif ( isset( $this->inline_scripts_classifier ) ) {
			$predicted_label = strval( $this->inline_scripts_classifier->predict( $lsh_digest ) );
			$list            = $this->inline_rows;
		} elseif ( null !== $event && isset( $this->event_handlers_classifier ) ) {
			$combined_predicted_label = strval( $this->event_handlers_classifier->predict( $lsh_digest ) );

			$label_parts     = explode( '#', $combined_predicted_label, 2 );
			$my_evh          = $label_parts[0];
			$predicted_label = $label_parts[1];

			$list = $this->events_rows;
		} else {
			return false;
		}

		if ( 'Unclustered' !== $predicted_label ) {
			if ( null === $event ) {
				foreach ( $list as $obj ) {
					if (
					$tagname === $obj->tagname &&
					$predicted_label === $obj->clustername &&
					'1' === $obj->whitelist
					) {
						return $predicted_label;

					}
				}
				return false;
			} elseif ( isset( $my_evh ) ) {
				foreach ( $list as $obj ) {
					if (
					$tagname === $obj->tagname &&
					$my_evh === $obj->event_attribute &&
					$predicted_label === $obj->clustername &&
					'1' === $obj->whitelist
					) {
						return $predicted_label;
					}
				}
				return false;
			}
		}
		return false;
	}

	/**
	 * If a new inline_script is allowed, because it has been classified in a cluster, we insert this one in the database.
	 * This should help in further classifications, because we cannot know clusters' shape.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string        $tagname It is script or style.
	 * @param string        $content The script content inserted in DB.
	 * @param array<string> $hashes The sha hashes array.
	 * @param string        $nilsimsa Nilsimsa hexDigest.
	 * @param string        $predicted_label The current clustername.
	 * @return void
	 */
	private function insert_new_inline_in_db( $tagname, $content, $hashes, $nilsimsa, $predicted_label ): void {
		global $wpdb;

		$cache_key   = 'inline_rows';
		$cache_group = 'no-unsafe-inline';
		$inline_rows = wp_cache_delete( $cache_key, $cache_group );

		$this->insert_pool->add(
			function () use ( $tagname, $content, $hashes, $nilsimsa, $predicted_label, $wpdb ) {

				// NUOVO CODICE
				$script_id = Nunil_Lib_Db::get_inl_id( $tagname . '-src', $tagname, $hashes['sha256'] );
				// FINE NUOVO CODICE

				$table   = NO_UNSAFE_INLINE_TABLE_PREFIX . 'inline_scripts';
				$tbl_occ = NO_UNSAFE_INLINE_TABLE_PREFIX . 'occurences';

				$lastseen = wp_date( 'Y-m-d H:i:s' );
				$pageurl  = Nunil_Lib_Utils::get_page_url();
				/* Before inserting, check if the script is in db. */
				$in_use    = $hashes['in_use'];
				$script_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT `id` from $table WHERE `tagname` = %s AND `$in_use` = %s",
						$tagname,
						$hashes[ $in_use ]
					)
				);

				if ( ! is_null( $script_id ) ) {
					$data = array(
						'clustername' => $predicted_label,
						'whitelist'   => 1,
					);
					// Cluster and whitelist if in DB.
					$wpdb->update( $table, $data, array( 'id' => $script_id ), array( '%s', '%d' ), array( '%d' ) );

					$occ_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT `id` FROM $tbl_occ WHERE `dbtable` = %s AND `itemid` = %d AND `pageurl` = %s",
							array(
								'inline_scripts',
								$script_id,
								$pageurl,
							)
						)
					);

					if ( ! is_null( $occ_id ) ) {
						$wpdb->update( $tbl_occ, array( 'lastseen' => $lastseen ), array( 'id' => $occ_id ), array( '%s' ), array( '%d' ) );
					} else {
						$wpdb->insert(
							$tbl_occ,
							array(
								'dbtable'  => 'inline_scripts',
								'itemid'   => $script_id,
								'pageurl'  => $pageurl,
								'lastseen' => $lastseen,
							),
							array( '%s', '%d', '%s', '%s' )
						);
					}
				} else {

					$data = array(
						'directive'   => $tagname . '-src',
						'tagname'     => $tagname,
						'script'      => $content,
						'sha256'      => $hashes['sha256'],
						'sha384'      => $hashes['sha384'],
						'sha512'      => $hashes['sha512'],
						'nilsimsa'    => $nilsimsa,
						'clustername' => $predicted_label,
						'whitelist'   => 1,
					);

					$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );

					$affected = $wpdb->insert( $table, $data, $format );

					/**
					 * I'm not sure it is safe to use $wpdb->insert_id in async script sharing
					 * the global $wpdb with other async scripts.
					 */
					$script_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT `id` FROM $table WHERE `tagname` = %s AND `$in_use` = %s",
							array( $tagname, $hashes[ $in_use ] )
						)
					);
					// Insert row in occurences.
					$data   = array(
						'dbtable'  => 'inline_scripts',
						'itemid'   => $script_id,
						'pageurl'  => Nunil_Lib_Utils::get_page_url(),
						'lastseen' => wp_date( 'Y-m-d H:i:s' ),
					);
					$format = array( '%s', '%d', '%s', '%s' );
					$wpdb->insert( $tbl_occ, $data, $format );
				}

			}
		);
	}

	/**
	 * Insert into csp_local_whitelist sources for a whitelisted script/style
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DomElement   $node The DOMElement passed by reference.
	 * @param array<string> $hashes Array of sha hashes.
	 * @param string        $directive The csp -src directive.
	 * @return void
	 */
	private function allow_whitelisted( &$node, $hashes, $directive ): void {
		switch ( $this->inline_scripts_mode ) {
			case 'nonce':
				/* Adding nonce to script in DOM */
				$node->setAttribute( 'nonce', $this->page_nonce );
				break;

			case 'sha256':
				$this->csp_local_whitelist[] = array(
					'directive' => $directive,
					'source'    => 'sha256-' . $hashes['sha256'],
				);
				break;

			case 'sha384':
				$this->csp_local_whitelist[] = array(
					'directive' => $directive,
					'source'    => 'sha384-' . $hashes['sha384'],
				);
				break;

			case 'sha512':
				$this->csp_local_whitelist[] = array(
					'directive' => $directive,
					'source'    => 'sha512-' . $hashes['sha512'],
				);
				break;
		}
	}

	/**
	 * Removes event handlers attribute from html tags and
	 * prepares code to be output in a injected inline script
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function script_clean_unsafe_hashes(): void {

		$nodelist = $this->get_nodes_w_events();
		if ( $nodelist ) {
			foreach ( $nodelist as $node ) {
				$rows = $this->get_event_handlers_in_node( $node );
				foreach ( $rows as $row ) {
					$hashes = $this->get_hashes( $row['script'], $utf8 = true );

					$index = $this->check_single_whitelist( $hashes, $row['tagname'], $row['event_attribute'] );

					if ( false !== $index ) {
						$this->evh_allow_wl_hash( $node, $row['event_attribute'] );
					} else {

						$lsh        = new Nilsimsa( $row['script'] );
						$lsh_digest = $lsh->digest();

						$wl_cluster = $this->check_cluster_whitelist( $lsh_digest, $row['tagname'], $row['event_attribute'] );
						if ( false !== $wl_cluster ) {
							$this->evh_allow_wl_hash( $node, $row['event_attribute'] );
						}
					}
				}
			}
		}
	}

	/**
	 * Removes script attribute from html tags and
	 * prepares code to be output in a injected inline <style>
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function style_clean_unsafe_hashes(): void {

		$nodelist = $this->get_nodes_w_inline_style();
		if ( $nodelist ) {
			foreach ( $nodelist as $node ) {

				$row = $this->get_inline_style_in_node( $node );

				if ( false !== $row ) {
					$hashes = $this->get_hashes( $row['script'], $utf8 = true );

					$index = $this->check_single_whitelist( $hashes, $row['tagname'] );

					if ( false !== $index ) {
						$class = 'nunil-' . $this->generate_nonce();
						$this->ils_allow_wl_hash( $node, $class );
					} else {

						$lsh        = new Nilsimsa( $row['script'] );
						$lsh_digest = $lsh->digest();

						$wl_cluster = $this->check_cluster_whitelist( $lsh_digest, $row['tagname'] );
						if ( false !== $wl_cluster ) {
							$class = 'nunil-' . $this->generate_nonce();
							$this->ils_allow_wl_hash( $node, $class );
						}
					}
				}
			}
		}
	}

	/**
	 * Inserts event handlers whitelisted scripts in a string used
	 * to create an inline script.
	 * Removes the event handler attribute from the node.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DomElement $node The currente DomNode.
	 * @param string      $evh The event handler attribute name.
	 * @return void
	 */
	private function evh_allow_wl_hash( &$node, $evh ): void {
		if ( $node->hasAttribute( 'id' ) ) {
			$tag_id = $node->getAttribute( 'id' );
		} else {
			$tag_id = 'nunil-' . $this->generate_nonce();
			$node->setAttribute( 'id', $tag_id );
		}

		$script = $node->getAttribute( $evh );
		$node->removeAttribute( $evh );
		$event_listener = substr( $evh, 2 );
		$line           = "document.getElementById(\"$tag_id\").addEventListener(\"$event_listener\", function() {\n\t$script;\n});\n";

		$this->injected_inline_script = $this->injected_inline_script . $line;

	}

	/**
	 * Inserts classes for whitelisted inline styles in a string used
	 * to create an inline css with <script>.
	 * Removes the style attribute from the node and add the class to it.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DomElement $node The DomNode passed by reference.
	 * @param string      $class The new class to be added.
	 * @return void
	 */
	private function ils_allow_wl_hash( &$node, $class ): void {
		if ( $node->hasAttribute( 'class' ) ) {
			$old_class = $node->getAttribute( 'class' ) . ' ';
		} else {
			$old_class = '';
		}
		$new_class = $old_class . "$class";
		$node->setAttribute( 'class', $new_class );

		$style = $node->getAttribute( 'style' );

		/**
		 * TODO: manage multybyte string.
		 */
		if ( ';' !== substr( trim( $style ), -1 ) ) {
			$style = trim( $style ) . ';';
		}

		$node->removeAttribute( 'style' );

		$line = '.' . $class . '{' . $style . ' }' . PHP_EOL;

		$this->injected_inline_style = $this->injected_inline_style . $line;
	}

	/**
	 * Injected the created inline <script> and whitelists it
	 *
	 * @since 1.0.0
	 * @access private;
	 * @return void
	 */
	private function inject_inline_script(): void {
		if ( $this->injected_inline_script ) {

			$content = $this->injected_inline_script;

			$script_node = $this->domdocument->createElement( 'script' );
			$script_node->setAttribute( 'type', 'text/javascript' );
			if ( 'nonce' === $this->inline_scripts_mode ) {
				$script_node->setAttribute( 'nonce', $this->page_nonce );
			}

			$script_node->appendChild( $this->domdocument->createTextNode( $content ) );

			if ( 'nonce' !== $this->inline_scripts_mode ) {
				$hashes = $this->get_hashes( $content, $utf8 = true );
				$in_use = $hashes['in_use'];

				$this->csp_local_whitelist[] = array(
					'directive' => 'script-src',
					'source'    => $in_use . '-' . $hashes[ $in_use ],
				);

			}
			$body_node = $this->domdocument->getElementsByTagName( 'body' );
			if ( 0 < $body_node->length ) {
				$body_node = $body_node->item( 0 );
			}
			if ( ! is_null( $body_node ) ) {
				if ( is_a( $body_node, '\DOMElement' ) ) {
					$body_node->appendChild( $script_node );
				}
			}
		}
	}

	/**
	 * Injected the created inline <style> and whitelists it
	 *
	 * @since 1.0.0
	 * @access private;
	 * @return void
	 */
	private function inject_inline_style(): void {
		if ( $this->injected_inline_style ) {

			$content = $this->injected_inline_style;

			$style_node = $this->domdocument->createElement( 'style' );
			$style_node->setAttribute( 'type', 'text/css' );
			if ( 'nonce' === $this->inline_scripts_mode ) {
				$style_node->setAttribute( 'nonce', $this->page_nonce );
			}

			$style_node->appendChild( $this->domdocument->createTextNode( $content ) );

			if ( 'nonce' !== $this->inline_scripts_mode ) {
				$hashes = $this->get_hashes( $content, $utf8 = true );
				$in_use = $hashes['in_use'];

				$this->csp_local_whitelist[] = array(
					'directive' => 'style-src',
					'source'    => $in_use . '-' . $hashes[ $in_use ],
				);

			}
			$head_node = $this->domdocument->getElementsByTagName( 'head' );
			if ( 0 < $head_node->length ) {
				$head_node = $head_node->item( 0 );
				if ( ! is_null( $head_node ) ) {
					if ( is_a( $head_node, '\DOMElement' ) ) {
						$head_node->appendChild( $style_node );
					}
				}
			}
		}
	}

	/**
	 * Calculates relevant hash based on user's settings
	 *
	 * @since 1.0.0
	 * @param string $content The content to hash.
	 * @param bool   $utf8 Convert (true) the string to UTF8, before calculating hash.
	 * @return array{sha256: string, sha384: string, sha512: string, in_use: string}
	 */
	private function get_hashes( $content, $utf8 = false ) {
		$fhash = $this->inline_scripts_mode;
		if ( 'nonce' === $fhash ) {
			$fhash = 'sha256';
		}
		$sha256 = self::calculate_hash( 'sha256', $content, $utf8 );
		$sha384 = self::calculate_hash( 'sha384', $content, $utf8 );
		$sha512 = self::calculate_hash( 'sha512', $content, $utf8 );

		$hashes = array(
			'sha256' => $sha256 ? $sha256 : '',
			'sha384' => $sha384 ? $sha384 : '',
			'sha512' => $sha512 ? $sha512 : '',
			'in_use' => $fhash,
		);
		return $hashes;
	}
}
