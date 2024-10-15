<?php
/**
 * Manipulate DOM
 *
 * Class used to manipulate the DOM including hashes/nonce and creating an inline script.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use Beager\Nilsimsa;
use Rubix\ML\Datasets\Unlabeled;
use NUNIL\Nunil_Lib_Db as DB;
use NUNIL\Nunil_Lib_Log as Log;
use NUNIL\Nunil_Lib_Utils as Utils;
use NUNIL\Nunil_Knn_Trainer;
use NUNIL\Nunil_Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to manipulate the DOM
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Manipulate_DOM extends Nunil_Capture {

	/**
	 * The array of external scripts objects
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass>|null Array of external resources table rows.
	 */
	private $external_rows;

	/**
	 * The array of inline scripts objects
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass>|null Array of inline table rows
	 */
	private $inline_rows;

	/**
	 * The array of event handlers scripts table rows
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\stdClass>|null Array of event handlers rows.
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
	 * Array of managed html tag
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array<\NUNIL\Nunil_HTML_Tag> $managed_tags Array returned by Nunil_Captured_Tags::get_captured_tags()
	 */
	private $managed_tags;

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
	 * @var \Rubix\ML\Classifiers\KNearestNeighbors|null $inline_scripts_classifier The KNearestNeighbors Classifier for <script>
	 */
	private $inline_scripts_classifier;

	/**
	 * Classifier used for inline <style>
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Rubix\ML\Classifiers\KNearestNeighbors|null $internal_css_classifier The KNearestNeighbors Classifier for <style>
	 */
	private $internal_css_classifier;

	/**
	 * Classifier used for event handlers' scripts
	 *
	 * @since 1.0.0
	 * @access private
	 * @var \Rubix\ML\Classifiers\KNearestNeighbors|null $event_handlers_classifier The KNearestNeighbors Classifier for event handlers scripts.
	 */
	private $event_handlers_classifier;

	/**
	 * The Trainer used for inline scripts
	 *
	 * @since 1.2.0
	 * @var \NUNIL\Nunil_Knn_Trainer
	 */
	private $nunil_trainer_script;

	/**
	 * The Trainer used for inline styles
	 *
	 * @since 1.2.0
	 * @var \NUNIL\Nunil_Knn_Trainer
	 */
	private $nunil_trainer_style;

	/**
	 * The Trainer used for event handlers
	 *
	 * @since 1.2.0
	 * @var \NUNIL\Nunil_Knn_Trainer
	 */
	private $nunil_trainer_event;

	/**
	 * Nonce used for the page if $inline_scripts_mode is set to 'nonce'
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $page_nonce The page nonce.
	 */
	private $page_nonce;

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

		$plugin_options = (array) get_option( 'no-unsafe-inline' );
		$tools          = (array) get_option( 'no-unsafe-inline-tools' );

		$this->managed_tags = Nunil_Captured_Tags::get_captured_tags();

		// Set properties with db results.
		$cache_keys = array( 'inline_rows', 'events_rows', 'external_rows' );
		foreach ( $cache_keys as $cache_key ) {
			$db_rows = \NUNIL\Nunil_Knn_Trainer::get_db_rows( $cache_key );
			if ( is_array( $db_rows ) ) {
				$this->$cache_key = $db_rows;
			}
		}

		$this->inline_scripts_mode = strval( Utils::cast_strval( $plugin_options['inline_scripts_mode'] ) );

		$this->page_nonce = $this->generate_nonce();

		if ( 1 === $plugin_options['script-src_enabled'] ) {
			$this->nunil_trainer_script = new Nunil_Knn_Trainer( 'script' );

			/**
			 * When capturing is enabled with a protection policy enabled or in test, we need NOT to
			 * use cache to avoid not updating clusternames after recluster.
			 */
			if ( 1 === $tools['capture_enabled'] ) {
				$this->inline_scripts_classifier = $this->nunil_trainer_script->get_trained( false );
			} else {
				$this->inline_scripts_classifier = $this->nunil_trainer_script->get_trained();
			}
		}

		if ( 1 === $plugin_options['style-src_enabled'] ) {
			$this->nunil_trainer_style = new Nunil_Knn_Trainer( 'style' );
			if ( 1 === $tools['capture_enabled'] ) {
				$this->internal_css_classifier = $this->nunil_trainer_style->get_trained( false );
			} else {
				$this->internal_css_classifier = $this->nunil_trainer_style->get_trained();
			}
		}

		// Create classifier for event_handlers $event_handlers_classifier.
		if ( 0 === $plugin_options['use_unsafe-hashes'] ) {
			$this->nunil_trainer_event = new Nunil_Knn_Trainer( 'event' );
			if ( 1 === $tools['capture_enabled'] ) {
				$this->event_handlers_classifier = $this->nunil_trainer_event->get_trained( false );
			} else {
				$this->event_handlers_classifier = $this->nunil_trainer_event->get_trained();
			}
		}
	}

	/**
	 * Get the manipulated HTML
	 *
	 * Check if manipulation has been performed and if not, performs it,
	 * then returns the manipulated HTML.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string The manipulated HTML.
	 */
	public function get_manipulated() {
		set_time_limit( 300 );
		if ( ! $this->csp_local_whitelist ) {
			$this->manipulate_inline_scripts();
			$this->manipulate_external_scripts();
		}
		return $this->domdocument->saveHTML();
	}

	/**
	 * Get the array used to build CSP
	 *
	 * Check if manipulation has been performed and if not, performs it,
	 * then returns the array containing local sources for script-src and style-src.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array<array{directive: string, source: string}> The csp_local_whitelist [-src directive] [source].
	 */
	public function get_local_csp() {
		set_time_limit( 300 );
		if ( ! $this->csp_local_whitelist ) {
			$this->manipulate_inline_scripts();
			$this->manipulate_external_scripts();
		}
		return $this->csp_local_whitelist;
	}

	/**
	 * Manipulates inline scripts adding nonce or hash.
	 *
	 * Appending nonce or hash to $csp_local_whitelist-
	 * Manipulated HTML goes in $manipulated_html property.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function manipulate_inline_scripts(): void {
		$plugin_options = (array) get_option( 'no-unsafe-inline' );

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
	 * Manipulate external resources
	 *
	 * Proposed feature for implementing integrity in future SRI specs
	 *
	 * Adds integrity and cross origin attrs,when using SRI.
	 * Appends hashes to $csp_local_whitelist
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function manipulate_external_scripts(): void {
		if ( ! is_array( $this->external_rows ) ) {
			return;
		}
		$options = (array) get_option( 'no-unsafe-inline' );
		if (
			'none' !== $options['script-src_mode'] ||
			'none' !== $options['style-src_mode'] ||
			/**
			 * In CSP3 hashes are only allowed for inline script, inline styles and external script
			 * but support for external styles or imgs in the specification has not been announced
			 * https://www.w3.org/TR/CSP3/#external-hash
			 *
			 * 'none' !== $options['img-src_mode'] ||
			 */
			1 === $options['sri_script'] ||
			1 === $options['sri_link']
			) {
			foreach ( $this->managed_tags as $tag ) {
				$node_list = $this->get_external_nodelist( $tag );
				if ( $node_list ) {
					foreach ( $node_list as $node ) {
						if ( $node instanceof \DOMElement ) {
							$index = $this->check_external_whitelist( $node );
							$this->manipulate_external_node( $node, $tag->get_directive(), $index );
						}
					}
				}
			}
		}
	}

	/**
	 * Allow an inline script or style
	 *
	 * @since 1.0.0
	 * @access private
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
				if ( 'script' === $tagname || 'style' === $tagname ) {
					$utf8 = true;
				} else {
					$utf8 = false;
				}
				$hashes = $this->get_hashes( $content, $utf8 );

				$wl_index = $this->check_single_whitelist( $hashes, $tagname );
				if ( false !== $wl_index ) {
					$this->allow_whitelisted( $node, $hashes, $directive );
					// Devi aggiornare un lastseen ?
				} else {
					$lsh            = new Nilsimsa( $content );
					$lsh_hex_digest = $lsh->hexDigest();
					$wl_cluster     = $this->check_cluster_whitelist( $lsh_hex_digest, $tagname );

					if ( false !== $wl_cluster ) {
						$this->allow_whitelisted( $node, $hashes, $directive );

						$options = (array) get_option( 'no-unsafe-inline' );
						if ( 1 === $options['add_wl_by_cluster_to_db'] ) {
							if ( class_exists( '\\Fiber' ) ) {
								global $nunil_fibers;
								$nunil_fibers[] = new \Fiber(
									function () use ( $tagname, $content, $hashes, $lsh_hex_digest, $wl_cluster ) {
										$this->insert_new_inline_in_db( $tagname, $content, $hashes, $lsh_hex_digest, $wl_cluster );
									}
								);
								$nunil_fibers[] = new \Fiber(
									function () use ( $tagname, $lsh_hex_digest, $wl_cluster ) {
										if ( 'script' === $tagname ) {
											$this->nunil_trainer_script->update( $lsh_hex_digest, $wl_cluster );
										}
										if ( 'style' === $tagname ) {
											$this->nunil_trainer_style->update( $lsh_hex_digest, $wl_cluster );
										}
									}
								);
							} else {
								$this->insert_new_inline_in_db( $tagname, $content, $hashes, $lsh_hex_digest, $wl_cluster );
								if ( 'script' === $tagname && $this->nunil_trainer_script instanceof Nunil_Knn_Trainer ) {
									$this->nunil_trainer_script->update( $lsh_hex_digest, $wl_cluster );
								}
								if ( 'style' === $tagname && $this->nunil_trainer_style instanceof Nunil_Knn_Trainer ) {
									$this->nunil_trainer_style->update( $lsh_hex_digest, $wl_cluster );
								}
							}
						}
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
	 * Select all nodes matching a tag
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \NUNIL\Nunil_HTML_Tag $tag The NUNIL html tag to parse.
	 * @return \DOMNodeList<\DOMNode>|false
	 */
	private function get_external_nodelist( $tag ) {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = $this->build_xpath_query( $tag );

		return $x->query( $x_path_query );
	}

	/**
	 * Builds XPath query to get all nodes matching a Nunil_HTML_Tag.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \NUNIL\Nunil_HTML_Tag $tag The NUNIL html tag to parse.
	 * @return string
	 */
	public function build_xpath_query( $tag ) {
		$x_path_query = '//' . $tag->get_name();

		$storedattrs = $tag->get_storedattrs();

		$query_attrs = array();
		$stored      = array();

		if ( ! $tag->has_childs() && ! is_null( $storedattrs ) ) {
			foreach ( $storedattrs as $storedattr ) {
				$stored[] = '@' . $storedattr;
			}
			$len          = count( $stored );
			$stored_attrs = '';
			for ( $i = 0; $i < $len; $i++ ) {
				$stored_attrs = $stored_attrs . ' or ' . $stored[ $i ];
			}
			$stored_attrs = '(' . substr( $stored_attrs, 4 ) . ')';

			if ( ! is_null( $tag->get_neededattrs() ) ) {
				foreach ( $tag->get_neededattrs() as $needed_attr ) {
					foreach ( $needed_attr as $attr => $value ) {
						if ( '!' === substr( trim( $value ), 0, 1 ) ) {
							$query_attrs[] = 'not(@' . $attr . '=\'' . preg_replace( '/\s*\!\s*/', '', $value, 1 ) . '\')';
						} else {
							$query_attrs[] = '(@' . $attr . '=\'' . trim( $value ) . '\')';
						}
					}
				}
			}
			$attrlen = count( $query_attrs );
			$attrs   = '';
			for ( $i = 0; $i < $attrlen; $i++ ) {
				$attrs = $attrs . ' and ' . $query_attrs[ $i ];
			}

			$attrs = substr( $attrs, 5 );
			if ( ! empty( $attrs ) ) {
				$x_path_query = $x_path_query . '[' . $stored_attrs . ' and ' . $attrs . ']';
			} else {
				$x_path_query = $x_path_query . '[' . $stored_attrs . ']';
			}
		} else {
			$tmp_subquery = '';
			$childs       = $tag->get_childs();
			if ( is_array( $childs ) && ! is_null( $storedattrs ) ) {
				foreach ( $childs as $child ) {
					$query_attrs     = array();
					$x_path_subquery = './/' . $child;

					foreach ( $storedattrs as $storedattr ) {
						$query_attrs[] = '@' . $storedattr;
					}
					$len          = count( $query_attrs );
					$stored_attrs = '';
					for ( $i = 0; $i < $len; $i++ ) {
						$stored_attrs = $stored_attrs . ' or ' . $query_attrs[ $i ];
					}
					$stored_attrs = '(' . substr( $stored_attrs, 4 ) . ')';
					$query_attrs  = array();

					if ( ! is_null( $tag->get_neededattrs() ) ) {
						foreach ( $tag->get_neededattrs() as $needed_attr ) {
							foreach ( $needed_attr as $attr => $value ) {
								if ( '!' === substr( trim( $value ), 0, 1 ) ) {
									$query_attrs[] = 'not(@' . $attr . '=\'' . preg_replace( '/\s*\!\s*/', '', $value, 1 ) . '\')';
								} else {
									$query_attrs[] = '(@' . $attr . '=\'' . trim( $value ) . '\')';
								}
							}
						}
					}
					$attrlen = count( $query_attrs );
					$attrs   = '';
					for ( $i = 0; $i < $attrlen; $i++ ) {
						$attrs = $attrs . ' and ' . $query_attrs[ $i ];
					}

					$attrs = substr( $attrs, 5 );

					if ( ! empty( $attrs ) ) {
						$x_path_subquery = $x_path_subquery . '[' . $stored_attrs . ' and ' . $attrs . ']';
					} else {
						$x_path_subquery = $x_path_subquery . '[' . $stored_attrs . ']';
					}
					$tmp_subquery = $tmp_subquery . ' or ' . $x_path_subquery;
				}
				$tmp_subquery = substr( $tmp_subquery, 4 );
				if ( ! empty( $tmp_subquery ) ) {
					$x_path_query = $x_path_query . '[' . $tmp_subquery . ']';
				}
			}
		}
		return $x_path_query;
	}

	/**
	 * Generates a random nonce
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string
	 */
	private function generate_nonce() {
		$bytes = random_bytes( 32 );

		return bin2hex( $bytes );
	}

	/**
	 * Check if resource fetched by a node is whitelisted.
	 *
	 * Looks for sources in external_rows array.
	 * If the node asks for more sources returns an array.
	 *
	 * @access private
	 * @param \DOMElement $node The selected DOMNode.
	 * @return array<int|false> An array where each element is the index of external_rows array if resource is whitelisted, false if not.
	 */
	private function check_external_whitelist( $node ) {
		$tag_lists = $this->get_tags_by_tagname( $this->managed_tags );
		$node_name = $node->nodeName;

		// We need to process only the list where $tag_lists[index] === $mytagname.
		// The list is sorted placing first tag with childs.
		$my_tag_list = $tag_lists[ $node_name ];

		$int_result = array();

		foreach ( $my_tag_list as $tag ) {
			$tag_childs   = $tag->get_childs();
			$stored_attrs = $tag->get_storedattrs();

			if ( ! is_null( $stored_attrs ) ) {
				foreach ( $stored_attrs as $stored_attr ) {
					if ( is_null( $tag_childs ) ) {
						if ( is_string( $stored_attr ) && $node->hasAttribute( $stored_attr ) ) {
							$src_attrib = $node->getAttribute( $stored_attr );
							if ( 'srcset' === $stored_attr ) {
								$srcs = $this->get_srcs_from_srcset( $node->getAttribute( 'srcset' ) );
								foreach ( $srcs as $single_src ) {
									$res = $this->check_res_wl( $single_src );
									if ( false !== $res && ! in_array( $res, $int_result, true ) ) {
										$int_result[] = intval( $res );
									}
								}
							} else {
								$res = $this->check_res_wl( $src_attrib );
								if ( false !== $res && ! in_array( $res, $int_result, true ) ) {
									$int_result[] = intval( $res );
								}
							}
						}
					} else {
						foreach ( $tag_childs as $tag_child ) {
							$child_nodes = $node->getElementsByTagName( $tag_child );
							foreach ( $child_nodes as $child_node ) {
								if ( is_string( $stored_attr ) && $child_node->hasAttribute( $stored_attr ) ) {
									$src_attrib = $child_node->getAttribute( $stored_attr );
									if ( 'srcset' === $stored_attr ) {
										$srcs = $this->get_srcs_from_srcset( $child_node->getAttribute( 'srcset' ) );
										foreach ( $srcs as $single_src ) {
											$res = $this->check_res_wl( $single_src );
											if ( false !== $res && ! in_array( $res, $int_result, true ) ) {
												$int_result[] = intval( $res );
											}
										}
									} else {
										$res = $this->check_res_wl( $src_attrib );
										if ( false !== $res && ! in_array( $res, $int_result, true ) ) {
											$int_result[] = intval( $res );
										}
									}
								}
							}
						}
					}
				}
			} else {
				$int_result[] = false;
			}
		}
		return $int_result;
	}

	/**
	 * Check if a resource is in external whitelist
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $src_attrib  The resource URI as registered in external_scripts table.
	 * @return int|false
	 */
	private function check_res_wl( $src_attrib ) {
		$ext_wlist = $this->external_rows;
		if ( ! is_array( $ext_wlist ) ) {
			return false; // BL: no external whitelist present.
		} elseif ( 0 < count( $ext_wlist ) ) {
			try {
				$src_attrib = $this->clean_random_params( $src_attrib );
			} catch ( Nunil_Exception $e ) {
				$e->logexception();
				return false;
			}

			try {
				$src_attrib = $this->conv_to_absolute_url( $src_attrib );
			} catch ( Nunil_Exception $e ) {
				$e->logexception();
				return false;
			}

			foreach ( $ext_wlist as $index => $obj ) {
				if ( $src_attrib === $obj->src_attrib ) {
					if ( '1' === $obj->whitelist ) {
						return (int) $index; // WL.
					} else {
						return false; // BL.
					}
				}
			}
		}
		return false;
	}


	/**
	 * Insert WL external sources in queue for CSP
	 * Adds SRI attributes to whitelisted nodes with script or link tagname.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DOMElement                $node The DOMNode passed by reference.
	 * @param string                     $directive The -src directive.
	 * @param int|false|array<int|false> $input_index The index of the whitelisted source in $this->external_rows array.
	 * @return void
	 */
	private function manipulate_external_node( &$node, $directive, $input_index = null ): void {
		$options = (array) get_option( 'no-unsafe-inline' );
		$use256  = ( 1 === $options['sri_sha256'] ) ? true : false;
		$use384  = ( 1 === $options['sri_sha384'] ) ? true : false;
		$use512  = ( 1 === $options['sri_sha512'] ) ? true : false;

		if (
			/**
			 * In CSP3 hashes are only allowed for inline script, inline styles and external script
			 * but support for external styles or imgs in the specification has not been announced
			 * https://www.w3.org/TR/CSP3/#external-hash
			 * ( 'hash' === $options['script-src_mode'] && 'script-src' === $directive ) ||
			 * ( 'hash' === $options['img-src_mode'] && 'img-src' === $directive )
			 */
			( 'hash' === $options['script-src_mode'] && 'script-src' === $directive ) ||
			( 'hash' === $options['script-src_mode'] && 'script-src-elem' === $directive )
		) {
			$add_hashes = true;
		} else {
			$add_hashes = false;
		}

		if (
			( 'nonce' === $options['script-src_mode'] && 'script-src' === $directive && 'script' === $node->nodeName ) ||
			( 'nonce' === $options['script-src_mode'] && 'script-src-elem' === $directive && 'script' === $node->nodeName ) ||
			( 'nonce' === $options['style-src_mode'] && 'style-src' === $directive && 'link' === $node->nodeName ) ||
			( 'nonce' === $options['style-src_mode'] && 'style-src-elem' === $directive && 'link' === $node->nodeName )
		) {
			$add_nonce = true;
		} else {
			$add_nonce = false;
		}

		if ( ! is_null( $input_index ) ) { // If index has not been passed, don't do anything.

			// Convert single index to array to perform loop.
			// This if statement, should never been run, because index should be always an array.
			if ( ! is_array( $input_index ) ) {
				$run_index[] = $input_index;
				Log::debug( esc_html__( '$input_index was not array in manipulate_external_node()', 'no-unsafe-inline' ) );
			} else {
				$run_index = $input_index;
			}
			foreach ( $run_index as $index ) {
				if ( false !== $index && ! is_null( $this->external_rows ) ) { // The node is whitelisted.
					if ( ! $node->hasAttribute( 'integrity' ) ) { // We don't modify integrity attrs set by others plugins.
						$integrity_string = '';
						if ( $use256 && ! empty( $this->external_rows[ $index ]->sha256 ) ) {
							$hash_with_options = 'sha256-' . $this->external_rows[ $index ]->sha256;
							$integrity_string  = $integrity_string . $hash_with_options . ' ';
							if ( $add_hashes ) {
								$local_wl = array(
									'directive' => $directive,
									'source'    => $hash_with_options,
								);
								if ( ! in_array( $local_wl, $this->csp_local_whitelist, true ) ) {
									$this->csp_local_whitelist[] = $local_wl;
								}
							}
						}
						if ( $use384 && ! empty( $this->external_rows[ $index ]->sha384 ) ) {
							$hash_with_options = 'sha384-' . $this->external_rows[ $index ]->sha384;
							$integrity_string  = $integrity_string . $hash_with_options . ' ';
							if ( $add_hashes ) {
								$local_wl = array(
									'directive' => $directive,
									'source'    => $hash_with_options,
								);
								if ( ! in_array( $local_wl, $this->csp_local_whitelist, true ) ) {
									$this->csp_local_whitelist[] = $local_wl;
								}
							}
						}
						if ( $use512 && ! empty( $this->external_rows[ $index ]->sha512 ) ) {
							$hash_with_options = 'sha512-' . $this->external_rows[ $index ]->sha512;
							$integrity_string  = $integrity_string . $hash_with_options . ' ';
							if ( $add_hashes ) {
								$local_wl = array(
									'directive' => $directive,
									'source'    => $hash_with_options,
								);
								if ( ! in_array( $local_wl, $this->csp_local_whitelist, true ) ) {
									$this->csp_local_whitelist[] = $local_wl;
								}
							}
						}
						if ( ( 'script' === $node->nodeName && 1 === $options['sri_script'] ) ||
							( 'link' === $node->nodeName && 1 === $options['sri_link'] )
						) {
							// In some cases external API cannot support integrity in a consistent way.
							// i.e. the CSS returned by the googlefonts API is different for different browsers.
							// In this cases we don't add integrity to the resources.
							if ( 'script' === $node->nodeName ) {
								$sourcestr = $node->getAttribute( 'src' );
							} else {
								$sourcestr = $node->getAttribute( 'href' );
							}
							if ( '' !== $sourcestr && true === $this->api_support_integrity( $sourcestr ) ) {
								$node->setAttribute( 'integrity', trim( $integrity_string ) );
								if ( ! $node->hasAttribute( 'crossorigin' ) ) {
									$node->setAttribute( 'crossorigin', 'anonymous' );
								}
							}
						}
					} else { // The node has got integrity by other way. Just whitelist it.
						$integrity        = $node->getAttribute( 'integrity' );
						$crossorigin      = $node->getAttribute( 'crossorigin' );
						$integrity_values = preg_split( '/\s+/', $integrity, -1 );
						if ( $integrity_values && $add_hashes ) {
							foreach ( $integrity_values as $hash_with_options ) {
								$this->csp_local_whitelist[] = array(
									'directive' => $directive,
									'source'    => $hash_with_options,
								);
							}
						}
					}
					if ( $add_nonce ) {
						// Adding nonce to CSP.
						$local_wl = array(
							'directive' => $directive,
							'source'    => 'nonce-' . $this->page_nonce,
						);
						if ( ! in_array( $local_wl, $this->csp_local_whitelist, true ) ) {
							$this->csp_local_whitelist[] = $local_wl;
						}
						// Adding nonce to node.
						$node->setAttribute( 'nonce', $this->page_nonce );
					}
				} elseif ( (
					( 'script' === $node->nodeName && 1 === $options['sri_script'] ) ||
					( 'link' === $node->nodeName && 1 === $options['sri_link'] ) )
					&& ( ! $node->hasAttribute( 'integrity' ) )
					&& ( ! is_null( $this->external_rows ) )
					) { // The node is not whitelisted. Just add integrity if we know it and it doesn't have.

						$integrity_string = '';
					if ( $use256 && ! empty( $this->external_rows[ $index ]->sha256 ) ) {
						$hash_with_options = 'sha256-' . $this->external_rows[ $index ]->sha256;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}
					if ( $use384 && ! empty( $this->external_rows[ $index ]->sha384 ) ) {
						$hash_with_options = 'sha384-' . $this->external_rows[ $index ]->sha384;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}
					if ( $use512 && ! empty( $this->external_rows[ $index ]->sha512 ) ) {
						$hash_with_options = 'sha512-' . $this->external_rows[ $index ]->sha512;
						$integrity_string  = $integrity_string . $hash_with_options . ' ';
					}

					if ( 'script' === $node->nodeName ) {
						$sourcestr = $node->getAttribute( 'src' );
					} else {
						$sourcestr = $node->getAttribute( 'href' );
					}
					if ( '' !== $sourcestr && true === $this->api_support_integrity( $sourcestr ) ) {
						$node->setAttribute( 'integrity', trim( $integrity_string ) );
						if ( ! $node->hasAttribute( 'crossorigin' ) ) {
							$node->setAttribute( 'crossorigin', 'anonymous' );
						}
					}
				}
			}
		}
	}

	/**
	 * Check if a src origin can support integrity
	 *
	 * @since 1.1.0
	 * @param string $sourcestr The url of the external resource.
	 * @return bool TRUE if detected api supports integrity
	 */
	private function api_support_integrity( $sourcestr ) {
		if ( '' === $sourcestr ) {
			return false;
		}
		$not_sri_sources = array(
			'fonts.googleapis', // https://github.com/google/fonts/issues/473 .
			'consent.cookiebot.com', // https://support.cookiebot.com/hc/en-us/community/posts/360029353353-Subresource-Integrity-SRI-and-Cookiebot .
			'cookie-cdn.cookiepro.com', // https://wordpress.org/support/topic/cookie-pro-script-gets-blocked-from-time-to-time/ .
		);

		$not_sri_sources = apply_filters( 'no_unsafe_inline_not_sri_sources', $not_sri_sources );

		foreach ( $not_sri_sources as $source ) {
			if ( false !== strpos( $sourcestr, $source ) ) {
				// We found the not_sri_string in $sourcestr.
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if a inline script or style is directly whitelisted
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array<string> $hashes The sha hashes array.
	 * @param string        $tagname The tagname: script or style.
	 * @param string        $event The event_handler (used when checkin an hash).
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
		if ( ! is_null( $list ) ) {
			foreach ( $list as $index => $obj ) {
				if ( $fhash === $obj->$in_use && ( 'v-capt' === $obj->tagname || $tagname === $obj->tagname ) && ( null === $event || $event === $obj->event_attribute ) ) {
					if ( '1' === $obj->whitelist ) {
						return $index;
					} else {
						return false;
					}
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
	 * @param string $lsh_hex_digest Nilsimsa digest.
	 * @param string $tagname The tagname.
	 * @param string $event Event handlers html attribute.
	 * @return string|false The clustername if whitelisted, else false
	 */
	private function check_cluster_whitelist( $lsh_hex_digest, $tagname, $event = null ) {
		$samples   = array();
		$samples[] = $lsh_hex_digest;
		$dataset   = new Unlabeled( $samples );

		if ( is_null( $event ) && 'script' === $tagname && isset( $this->inline_scripts_classifier ) ) {
			$predicted_labels = $this->inline_scripts_classifier->predict( $dataset );
			$predicted_label  = $predicted_labels[0];
			$list             = $this->inline_rows;
		} elseif ( is_null( $event ) && 'script' !== $tagname && isset( $this->internal_css_classifier ) ) {
			$predicted_labels = $this->internal_css_classifier->predict( $dataset );
			$predicted_label  = $predicted_labels[0];
			$list             = $this->inline_rows;
		} elseif ( null !== $event && isset( $this->event_handlers_classifier ) ) {
			$predicted_labels         = $this->event_handlers_classifier->predict( $dataset );
			$combined_predicted_label = $predicted_labels[0];
			$label_parts              = explode( '#', $combined_predicted_label, 2 );
			$my_evh                   = $label_parts[0];
			$predicted_label          = $label_parts[1];
			$list                     = $this->events_rows;
		} else {
			return false;
		}
		if ( is_array( $list ) ) {
			if ( 'Unclustered' !== $predicted_label ) {
				if ( null === $event ) {
					foreach ( $list as $obj ) {
						if (
						( 'v-capt' === $obj->tagname || $tagname === $obj->tagname ) &&
						$predicted_label === $obj->clustername &&
						'1' === $obj->whitelist
						) {
							return $predicted_label;
						}
					}
					return false;
				} else {
					foreach ( $list as $obj ) {
						if (
						( 'v-capt' === $obj->tagname || $tagname === $obj->tagname ) &&
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
		}
		return false;
	}

	/**
	 * Insert a new inline script or style in database
	 *
	 * If a new inline_script is allowed, because it has been classified in a cluster, we can insert this one in the database.
	 * This should help in further classifications, because we cannot know clusters' shape.
	 * This behaviour can be enabled by option page.
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
		$cache_key   = 'inline_rows';
		$cache_group = 'no-unsafe-inline';
		$inline_rows = wp_cache_delete( $cache_key, $cache_group );

		$in_use = $hashes['in_use'];

		$pageurl = Nunil_Lib_Utils::get_page_url();
		/* Before inserting, check if the script is in db. */
		$script_id = DB::get_inl_id( $tagname, $hashes[ $in_use ] );
		if ( ! is_null( $script_id ) ) {
			// Cluster and whitelist if in DB.
			$affected = DB::upd_inl_cl_wl( $script_id, $predicted_label );

			$occ_id = DB::get_occ_id( $script_id, 'inline_scripts', $pageurl );

			if ( ! is_null( $occ_id ) ) {
				DB::update_lastseen( $occ_id );
			} else {
				$occ_id = DB::insert_occ_in_db( $script_id, 'inline_scripts', $pageurl );
			}
		} else {
			if ( 'script' === $tagname || 'style' === $tagname ) {
				$utf8 = true;
			} else {
				$utf8 = false;
			}
			$script_id = DB::insert_inl_in_db( $tagname . '-src', $tagname, $content, $sticky = false, $utf8, $nilsimsa );
			$affected  = DB::upd_inl_cl_wl( $script_id, $predicted_label );

			$occ_id = DB::insert_occ_in_db( $script_id, 'inline_scripts' );
		}
		Utils::set_last_modified( 'inline_scripts' );
	}

	/**
	 * Insert a new script triggered by an event handler in db
	 *
	 * If a new event script is allowed, because it has been classified in a cluster, we can insert this one in the database.
	 * This should help in further classifications, because we cannot know clusters' shape.
	 * This behaviour can be enabled by option page.
	 *
	 * @since 1.2.0
	 * @access private
	 * @param array{"tagname": string, "tagid": string, "event_attribute": string, "script": string} $row Array of event handlers attributes.
	 * @param array<string>                                                                          $hashes The sha hashes array.
	 * @param string                                                                                 $nilsimsa Nilsimsa hexDigest.
	 * @param string                                                                                 $predicted_label The current clustername.
	 * @return void
	 */
	private function insert_new_event_in_db( $row, $hashes, $nilsimsa, $predicted_label ) {
		$cache_key   = 'events_rows';
		$cache_group = 'no-unsafe-inline';
		$events_rows = wp_cache_delete( $cache_key, $cache_group );

		$in_use = $hashes['in_use'];

		$pageurl = Nunil_Lib_Utils::get_page_url();
		/* Before inserting, check if the script is in db. */
		$event_id = DB::get_evh_id( $row['tagname'], $row['tagid'], $row['event_attribute'], $hashes[ $in_use ] );
		if ( ! is_null( $event_id ) ) {
			// Cluster and whitelist if in DB.
			$affected = DB::upd_evh_cl_wl( $event_id, $predicted_label );

			$occ_id = DB::get_occ_id( $event_id, 'event_handlers', $pageurl );

			if ( ! is_null( $occ_id ) ) {
				DB::update_lastseen( $occ_id );
			} else {
				$occ_id = DB::insert_occ_in_db( $event_id, 'event_handlers', $pageurl );
			}
		} else {
			$row['nilsimsa'] = $nilsimsa;

			$event_id = DB::insert_evh_in_db( $row );
			$affected = DB::upd_evh_cl_wl( $event_id, $predicted_label );

			$occ_id = DB::insert_occ_in_db( $event_id, 'event_handlers' );
		}
		Utils::set_last_modified( 'event_handlers' );
	}

	/**
	 * Insert into csp_local_whitelist sources for a whitelisted script/style
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DOMElement   $node The DOMElement passed by reference.
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
	 * Removes event handlers
	 *
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
				if ( $node instanceof \DOMElement ) {
					$rows = $this->get_event_handlers_in_node( $node );
					foreach ( $rows as $row ) {
						$hashes = $this->get_hashes( $row['script'], $utf8 = true );

						$index = $this->check_single_whitelist( $hashes, $row['tagname'], $row['event_attribute'] );

						if ( false !== $index ) {
							$this->evh_allow_wl_hash( $node, $row['event_attribute'] );
						} else {
							$lsh            = new Nilsimsa( $row['script'] );
							$lsh_hex_digest = $lsh->hexDigest();
							$wl_cluster     = $this->check_cluster_whitelist( $lsh_hex_digest, $row['tagname'], $row['event_attribute'] );

							if ( false !== $wl_cluster ) {
								$this->evh_allow_wl_hash( $node, $row['event_attribute'] );

								$options = (array) get_option( 'no-unsafe-inline' );
								if ( 1 === $options['add_wl_by_cluster_to_db'] ) {
									if ( class_exists( '\\Fiber' ) ) {
										global $nunil_fibers;
										$nunil_fibers[] = new \Fiber(
											function () use ( $row, $hashes, $lsh_hex_digest, $wl_cluster ) {
												$this->insert_new_event_in_db( $row, $hashes, $lsh_hex_digest, $wl_cluster );
											}
										);
										$nunil_fibers[] = new \Fiber(
											function () use ( $lsh_hex_digest, $wl_cluster ) {
												$this->nunil_trainer_event->update( $lsh_hex_digest, $wl_cluster );
											}
										);
									} else {
										$this->insert_new_event_in_db( $row, $hashes, $lsh_hex_digest, $wl_cluster );
										$this->nunil_trainer_event->update( $lsh_hex_digest, $wl_cluster );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Removes inline styles
	 *
	 * Removes style attribute from html tags and
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
				if ( $node instanceof \DOMElement ) {
					$row = $this->get_inline_style_in_node( $node );

					if ( false !== $row ) {
						$hashes = $this->get_hashes( $row['script'], $utf8 = false );

						$index = $this->check_single_whitelist( $hashes, $row['tagname'] );

						if ( false !== $index ) {
							$class = 'nunil-fly-' . $this->generate_nonce();
							$this->ils_allow_wl_hash( $node, $class );
						} else {
							$lsh            = new Nilsimsa( $row['script'] );
							$lsh_hex_digest = $lsh->hexDigest();
							$wl_cluster     = $this->check_cluster_whitelist( $lsh_hex_digest, $row['tagname'] );

							if ( false !== $wl_cluster ) {
								$class = 'nunil-fly-' . $this->generate_nonce();
								$this->ils_allow_wl_hash( $node, $class );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Create the injected inline script
	 *
	 * Inserts event handlers whitelisted scripts in a string used
	 * to create an inline script.
	 * Removes the event handler attribute from the node.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DOMElement $node The current DOMNode.
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
	 * Creates the internal CSS
	 *
	 * Inserts classes for whitelisted inline styles in a string used
	 * to create an internal css with <style>.
	 * Removes the style attribute from the node and add the class to it.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param \DOMElement $node The DOMNode passed by reference.
	 * @param string      $cssclass The new class to be added.
	 * @return void
	 */
	private function ils_allow_wl_hash( &$node, $cssclass ): void {
		if ( $node->hasAttribute( 'class' ) ) {
			$old_class = $node->getAttribute( 'class' ) . ' ';
		} else {
			$old_class = '';
		}
		$new_class = $old_class . "$cssclass";
		$node->setAttribute( 'class', $new_class );

		$style = $node->getAttribute( 'style' );

		/**
		 * TODO: manage multybyte string.
		 */
		if ( ';' !== substr( trim( $style ), -1 ) ) {
			$style = trim( $style ) . ';';
		}

		$node->removeAttribute( 'style' );

		$line = '.' . $cssclass . '{' . $style . ' }' . PHP_EOL;

		$this->injected_inline_style = $this->injected_inline_style . $line;
	}

	/**
	 * Injects the created inline <script> and whitelists it
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function inject_inline_script(): void {
		if ( $this->injected_inline_script ) {
			$content = $this->injected_inline_script;

			$script_node = $this->domdocument->createElement( 'script' );
			if ( false !== $script_node ) {
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
	}

	/**
	 * Injects the created internal CSS and whitelists it
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function inject_inline_style(): void {
		if ( $this->injected_inline_style ) {
			$content = $this->injected_inline_style;

			$style_node = $this->domdocument->createElement( 'style' );
			if ( false !== $style_node ) {
				$style_node->setAttribute( 'type', 'text/css' );
				$style_node->setAttribute( 'id', 'nunil-internal-stylesheet' );
				$style_node->setAttribute( 'title', 'nunil-internal-stylesheet' );
				if ( 'nonce' === $this->inline_scripts_mode ) {
					$style_node->setAttribute( 'nonce', $this->page_nonce );
				}

				$style_node->appendChild( $this->domdocument->createTextNode( $content ) );

				if ( 'nonce' !== $this->inline_scripts_mode ) {
					$hashes = $this->get_hashes( $content, $utf8 = false );
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
