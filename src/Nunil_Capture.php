<?php
/**
 * Capturing class
 *
 * Class used on capturing relevant tags.
 * Contains functions to populate the db tables.
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

use IvoPetkov\HTML5DOMDocument;
use NUNIL\Nunil_Lib_Utils as Utils;
use NUNIL\Nunil_Exception;

use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\Contracts\UriException;
use League\Uri\UriModifier;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class with methods used on tags capturing
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Capture {


	/**
	 * The array of tag matches.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    \DOMNodeList<\DOMNode> The DOMNodeList.
	 */
	private $matches;

	/**
	 * The page html source.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string The HTML source of the page.
	 */
	public $htmlsource;

	/**
	 * The HTML5DOMDocument object.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    \IvoPetkov\HTML5DOMDocument Istance of IvoPetkov\HTML5DOMDocument object.
	 */
	public $domdocument;

	/**
	 * A temporary counter to avoid double processing tags
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array<bool> An associative array of bool to keep track if a node has been processed
	 */
	private $processed;

	/**
	 * SHA algo used to find scripts
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $hash_in_use One of 'sha256', 'sha384', 'sha512'
	 */
	private $hash_in_use;

	/**
	 * The output displayed if a debug info is shown when WP_DISPLAY_DEBUG is true
	 *
	 * @since 1.2.1
	 * @access public
	 * @var string $debug_preamble Output displayed before <!DOCTYPE
	 */
	public $debug_preamble = '';

	/**
	 * The class constructor.
	 *
	 * Set the htmlsource attribute.
	 * Set the domdocument attribute.
	 * Load HTML source of the page.
	 * Set $hash_in_use to SHA algo used to search for scripts in db.
	 *
	 * @since 1.0.0
	 * @throws Nunil_Exception Exception raised when the plugin options are not set.
	 */
	public function __construct() {
		$this->domdocument                     = new HTML5DOMDocument();
		$this->domdocument->preserveWhiteSpace = true;

		$plugin_options = (array) get_option( 'no-unsafe-inline' );
		if ( ! empty( $plugin_options ) ) {
			$inline_scripts_mode = strval( Utils::cast_strval( $plugin_options['inline_scripts_mode'] ) );
			if ( 'nonce' === $inline_scripts_mode ) {
				$this->hash_in_use = 'sha256';
			} else {
				$this->hash_in_use = $inline_scripts_mode;
			}
		} else {
			throw new Nunil_Exception( 'The option no-unsafe-inline has to be an array', 3010, 3 );
		}
	}

	/**
	 * Load the HTML into domdocument
	 *
	 * @since 1.0.0
	 * @param string $htmlsource The HTML source of the page.
	 * @return void
	 */
	public function load_html( $htmlsource ): void {
		if ( '' !== $htmlsource ) {
			$htmlsource = $this->remove_debug_display( $htmlsource );
			$this->domdocument->loadHTML( $htmlsource, HTML5DOMDocument::ALLOW_DUPLICATE_IDS | HTML5DOMDocument::OPTIMIZE_HEAD | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_BIGLINES );
		}
	}
	
	/**
	 * Removes the output displayed before <!DOCTYPE
	 *
	 * @since 1.2.1
	 * @param string $mystring The string parsed (html output of the WP Process).
	 * @return string
	 */
	private function remove_debug_display( $mystring ): string {
		$mypos = strpos( $mystring, '<!DOCTYPE' );
		if ( false !== $mypos && 0 !== $mypos ) {
			$preamble             = substr( $mystring, 0, $mypos );
			$this->debug_preamble = $preamble;
			$mystring             = substr( $mystring, $mypos );
		}
		return $mystring;
	}
	
	/**
	 * Perform capture for each tag in array
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array<Nunil_HTML_Tag> $tags An array of $tag istances of NUNIL\Nunil_HTML_Tag object.
	 * @return void
	 */
	public function capture_tags( $tags ): void {
		$tagnames = $this->get_tags_by_tagname( $tags );

		foreach ( $tagnames as $tagname => $taglist ) {
			$this->capture_tag( $tagname, $taglist );
		}
	}

	/**
	 * Capture tags with event handlers
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function capture_handlers(): void {
		$rows = $this->get_event_handlers_in_page();
		$this->put_handlers_in_db( $rows );
		Utils::set_last_modified( 'event_handlers' );
	}

	/**
	 * Capture tags with inline sytle
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function capture_inline_style(): void {
		$rows = $this->get_inline_style_in_page();
		$this->put_styles_in_db( $rows );
		Utils::set_last_modified( 'inline_scripts' );
	}

	/**
	 * Get event handlers to be inserted in db
	 *
	 * @since  1.0.0
	 * @access public
	 * @param \DOMNodeList<\DOMNode> $nodelist A DOMNodeList.
	 * @return array<array<string>>
	 */
	public function get_event_handlers_in_page( $nodelist = null ) {
		if ( ! isset( $nodelist ) ) {
			$nodelist = $this->get_nodes_w_events();
		}
		$all_rows = array();
		if ( $nodelist ) {
			foreach ( $nodelist as $node ) {
				if ( $node instanceof \DOMElement ) {
					$rows     = $this->get_event_handlers_in_node( $node );
					$all_rows = array_merge( $all_rows, $rows );
				}
			}
		}
		return $all_rows;
	}

	/**
	 * Get inline styles to be inserted in db
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \DOMNodeList<\DOMNode> $nodelist A DOMNodeList.
	 * @return array<array<string>>
	 */
	public function get_inline_style_in_page( $nodelist = null ) {
		if ( ! isset( $nodelist ) ) {
			$nodelist = $this->get_nodes_w_inline_style();
		}
		$all_rows = array();
		if ( $nodelist ) {
			foreach ( $nodelist as $node ) {
				if ( $node instanceof \DOMElement ) {
					$row = $this->get_inline_style_in_node( $node );
					if ( false !== $row ) {
						$all_rows[] = $row;
					}
				}
			}
		}
		return $all_rows;
	}

	/**
	 * Loops for all rows to be inserted in event_handlers table
	 * and insert them.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param array<array<string>> $rows An array of row array.
	 * @return void
	 */
	private function put_handlers_in_db( $rows ): void {
		foreach ( $rows as $row ) {
			$this->insert_handler_in_db( $row );
		}
	}

	/**
	 * Loops for all rows to be inserted in inline table for inline styles
	 * and insert them.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param array<array<string>> $rows An array of row array.
	 * @return void
	 */
	private function put_styles_in_db( $rows ): void {
		foreach ( $rows as $row ) {
			$this->insert_inline_content_in_db( 'style-src', $row['tagname'], $row['script'] );
		}
	}

	/**
	 * Insert an event handlers in event_handlers table.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param array<string> $row Associative array of values to be inserted in db.
	 * @return void
	 */
	private function insert_handler_in_db( $row ): void {
		switch ( $this->hash_in_use ) {
			case 'sha256':
				$hash = self::calculate_hash( 'sha256', $row['script'], $utf8 = true );
				break;
			case 'sha384':
				$hash = self::calculate_hash( 'sha384', $row['script'], $utf8 = true );
				break;
			case 'sha512':
				$hash = self::calculate_hash( 'sha512', $row['script'], $utf8 = true );
				break;
			default:
				$hash = self::calculate_hash( 'sha256', $row['script'], $utf8 = true );
				break;
		}
		if ( $hash ) {
			$event_script_id = Nunil_Lib_Db::get_evh_id( $row['tagname'], $row['tagid'], $row['event_attribute'], $hash );
		} else {
			$event_script_id = null;
		}

		if ( is_null( $event_script_id ) ) {// The script is not in the db.

			$event_script_id = Nunil_Lib_Db::insert_evh_in_db( $row );

			$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $event_script_id, 'event_handlers' );
		} else {
			// The script is already in the db.
			// Now check if there is an occurence for the script in the page.
			$occurrence_id = Nunil_Lib_Db::get_occ_id( $event_script_id, 'event_handlers' );

			if ( is_null( $occurrence_id ) ) {
				$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $event_script_id, 'event_handlers' );
			} else {

				// We have alredy recorded the occurence of the script in the page, so just update the timestamp.
				Nunil_Lib_Db::update_lastseen( $occurrence_id );
			}
		}
	}

	/**
	 * Get event in node
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  \DOMElement $node The element we are processing.
	 * @return array<array{"tagname": string, "tagid": string, "event_attribute": string, "script": string}> An array of associative arrays: [tag], [id], [event_attribute], [script].
	 */
	public function get_event_handlers_in_node( $node ) {
		$event_attributes = new Nunil_Event_Attributes();
		$rows             = array();
		$attributes       = $event_attributes->get_attributes();

		foreach ( $attributes as $attribute ) {
			if ( $node->getAttribute( $attribute['attr'] ) ) {
				$row = array(
					'tagname'         => $node->nodeName,
					'tagid'           => $node->getAttribute( 'id' ),
					'event_attribute' => $attribute['attr'],
					'script'          => $node->getAttribute( $attribute['attr'] ),
				);

				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Get inline style in node
	 *
	 * @since 1.0.0
	 * @access public
	 * @param \DOMElement $node The node we are processing.
	 * @return array{tagname: string, script: string }|false An array of associative arrays: [tag], [id], [attribute], [script], [sha-256].
	 */
	public function get_inline_style_in_node( $node ) {
		$attribute = 'style';
		if ( $node->hasAttribute( $attribute ) ) {
			$row = array(
				'tagname' => $node->nodeName,
				'script'  => $node->getAttribute( $attribute ),
			);
			return $row;
		}
		return false;
	}

	/**
	 * Get nodes with event attributes
	 *
	 * @since  1.0.0
	 * @access public
	 * @return \DOMNodeList<\DOMNode>|false A DOMNodeList
	 */
	public function get_nodes_w_events() {
		$event_attributes = new Nunil_Event_Attributes();
		$attributes       = $event_attributes->get_attributes();

		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = '//*[';

		foreach ( $attributes as $attribute ) {
			$event = $attribute['attr'];

			$x_path_query = $x_path_query . '@' . $event . ' or ';
		}
		$x_path_query = substr( $x_path_query, 0, strlen( $x_path_query ) - 4 );
		$x_path_query = $x_path_query . ']';

		$nodelist = $x->query( $x_path_query );

		return $nodelist;
	}

	/**
	 * Get nodes with style attributes
	 *
	 * @since  1.0.0
	 * @access public
	 * @return \DOMNodeList<\DOMNode>|false A DOMNodeList
	 */
	public function get_nodes_w_inline_style() {
		$x            = new \DOMXPath( $this->domdocument );
		$x_path_query = '//*[@style]';

		$nodelist = $x->query( $x_path_query );

		return $nodelist;
	}

	/**
	 * Returns an array of array key=tagname, values = array_n of obj $tags
	 *
	 * This is useful to speed up capturing avoiding to parse tags if
	 * yet processed.
	 * By processing for external first and then for inline, we could
	 * not avoid to double entry a node that has both $stored_attr and
	 * TextContent.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  array<Nunil_HTML_Tag> $tags An array of obj $tag istances
	 * of Nunil_HTML_Tag object.
	 * @return array<array<Nunil_HTML_Tag>>
	 */
	protected function get_tags_by_tagname( $tags ) {
		$tagnames = array();
		foreach ( $tags as $tag ) {
			$name                = $tag->get_name();
			$tagnames[ $name ][] = $tag;
		}
		/**
		 * To avoid inserting in inline db tags with childs and
		 * textcontent we process tag with childs, first.
		 */
		foreach ( $tagnames as &$tagname ) {
			usort( $tagname, array( 'NUNIL\Nunil_Capture', 'sort_tag_by_child_first' ) );
		}
		return $tagnames;
	}

	/**
	 * Order tags by has_child()
	 *
	 * @param  Nunil_HTML_Tag $a An obj $tag istances of NUNIL\HTML_Tag object.
	 * @param  Nunil_HTML_Tag $b An obj $tag istances of NUNIL\HTML_Tag object.
	 * @return int
	 */
	private function sort_tag_by_child_first( $a, $b ) {
		$a_has_childs = $a->has_childs();
		$b_has_childs = $b->has_childs();
		if ( $a_has_childs === $b_has_childs ) {
			return 0;
		}

		if ( true === $a_has_childs && false === $b_has_childs ) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Searchs HTML tag in string
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  string                $tagname An HTML tagname.
	 * @param  array<Nunil_HTML_Tag> $taglist An array of $tag obj, with the same tagname.
	 * @return void
	 */
	private function capture_tag( $tagname, $taglist ): void {
		unset( $this->matches );
		$nodelist      = $this->domdocument->getElementsByTagName( $tagname );
		$this->matches = $nodelist;

		unset( $this->processed );
		$this->processed = array();

		foreach ( $taglist as $tag ) {
			$this->put_tags_in_database( $tag );
		}
	}

	/**
	 * Calculate base64 encoded hash for string
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string $algo    One of CSP supported algo: sha256, sha384, sha512.
	 * @param  string $content String to be hashed (the inline tag content).
	 * @param  bool   $utf8    Convert (true) the string to UTF8, before calculating hash.
	 * @return string|false    The hased string.
	 */
	public static function calculate_hash( $algo, $content, $utf8 = false ) {
		$haystack = array( 'sha256', 'sha384', 'sha512' );
		if ( in_array( $algo, $haystack, true ) ) {
			/**
			 * See https://w3c.github.io/webappsec-csp/#external-hash
			 *
			 * Note: The CSP spec specifies that the contents of an inline script element or event handler
			 * needs to be encoded using UTF-8 encode before computing its hash. [SRI] computes the hash
			 * on the raw resource that is being fetched instead. This means that it is possible for the hash
			 * needed to allow an inline script block to be different that the hash needed to allow an external
			 * script even if they have identical contents.
			 */
			if ( $utf8 ) {
				/**
				 * Converting newlines to \n.
				 * It seems what FF and Chrome do.
				 * More evidence needed.
				 */
				$content = preg_replace( '~\R~u', "\n", $content );

				if ( is_null( $content ) ) {
					return false;
				}
				if ( ! mb_check_encoding( $content, 'utf8' ) ) {
					// Fix PHP8.2 deprecation: https://php.watch/versions/8.2/utf8_encode-utf8_decode-deprecated#utf8_encode-replace .
					$content = mb_convert_encoding( $content, 'UTF-8', mb_list_encodings() );
				}
			}
			$base64 = base64_encode( hash( $algo, $content, true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return $base64;
		} else {
			return false;
		}
	}

	/**
	 * Checks if node has the attrs and values in $attrs array.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  array<array<string>>|null  $attrs An array of [attr_name] => value.
	 * @param  \IvoPetkov\HTML5DOMElement $node  An HTML5DOMElement.
	 * @return bool True if the node has all attrs and values or if $attrs is empty.
	 */
	private function check_attrs( $attrs, $node ) {
		if ( is_null( $attrs ) ) {
			return true;
		}

		$check_attrs = true;

		foreach ( $attrs as $attr ) {
			foreach ( $attr as $key => $value ) {
				// The tag asks to exclude HTML tags with this attribute.
				if ( '!' === substr( trim( $value ), 0, 1 ) ) {
					$excluded_value = trim( substr( trim( $value ), 1 ) );
					if ( $node->hasAttribute( $key ) ) {
						if ( $excluded_value === $node->getAttribute( $key ) ) {
							$check_attrs = false;
						}
					}
				} elseif ( ! $node->hasAttribute( $key ) ) {
						$check_attrs = false;
				} elseif ( $node->getAttribute( $key ) !== $value && '*' !== $value ) {
						$check_attrs = false;
				}
			}
		}

		return $check_attrs;
	}

	/**
	 * Insert tag content in database
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  Nunil_HTML_Tag $tag a NUNIL\HTML_Tag object.
	 * @return void
	 */
	private function put_tags_in_database( $tag ): void {
		$nodelist  = $this->matches;
		$processed = $this->processed;

		$node_index = 0;

		foreach ( $nodelist as $node ) {
			// Forcing array key as a string.
			$node_key        = 'key_' . sprintf( '%d', $node_index );
			$index_processed = array_key_exists( $node_key, $processed );

			if ( ! $index_processed && ( $node instanceof \IvoPetkov\HTML5DOMElement ) ) {
				$inline = false;

				if ( $this->check_attrs( $tag->get_neededattrs(), $node ) ) {
					$stored_attrs = $tag->get_storedattrs();
					$directive    = $tag->get_directive();
					$tagname      = $tag->get_name();

					if ( ! $tag->has_childs() && is_array( $stored_attrs ) ) { // Tag hasn't childs.
						foreach ( $stored_attrs as $stored_attr ) {
							if ( $node->hasAttribute( $stored_attr ) ) {
								if ( 'srcset' === $stored_attr ) {
									$srcset = $node->getAttribute( $stored_attr );
									$srcs   = $this->get_srcs_from_srcset( $srcset );
									foreach ( $srcs as $src_attrib ) {
										$external_script_id     = $this->insert_external_tag_in_db( $directive, $tagname, $src_attrib );
										$processed[ $node_key ] = true;
									}
								} else { // $stored_attr is not 'srcset'.
									$src_attrib             = $node->getAttribute( $stored_attr );
									$external_script_id     = $this->insert_external_tag_in_db( $directive, $tagname, $src_attrib );
									$processed[ $node_key ] = true;
								}
							} elseif ( $tag->capture_inline() ) {
									$inline = true;
							}
						}
					} else { // Tag HAS childs.
						$children_tags = $tag->get_childs();
						if ( is_array( $children_tags ) && is_array( $stored_attrs ) ) {
							foreach ( $children_tags as $child_tag ) {
								$child_nodes = $node->getElementsByTagName( $child_tag );
								foreach ( $stored_attrs as $stored_attr ) {
									foreach ( $child_nodes as $child_node ) {
										if ( $child_node->hasAttribute( $stored_attr ) ) {
											if ( 'srcset' === $stored_attr ) {
												$srcset = $child_node->getAttribute( $stored_attr );
												$srcs   = $this->get_srcs_from_srcset( $srcset );
												foreach ( $srcs as $src_attrib ) {
													$external_script_id     = $this->insert_external_tag_in_db( $directive, $tagname, $src_attrib );
													$processed[ $node_key ] = true;
												}
											} else {
												$src_attrib             = $child_node->getAttribute( $stored_attr );
												$external_script_id     = $this->insert_external_tag_in_db( $directive, $tagname, $src_attrib );
												$processed[ $node_key ] = true;
											}
										}
									}
								}
							}
						}
					}
				}

				if ( $inline ) {
					$directive = $tag->get_directive();
					$tagname   = $tag->get_name();

					$content = $node->textContent;

					$content = $this->clean_text_content( $content );

					if ( '' !== $content ) {
						$this->insert_inline_content_in_db( $directive, $tagname, $content );

						$processed[ $node_key ] = true;
					}
				}
			}
			++$node_index;
		}
		$this->processed = array_merge( $this->processed, $processed );
		Utils::set_last_modified( 'external_scripts' );
		Utils::set_last_modified( 'inline_scripts' );
	}

	/**
	 * Clean textContent from HTML5DOMDocument adds
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $text Output of $Node->textContent.
	 * @return string
	 */
	public function clean_text_content( $text ) {
		/**
		 * Remove code as in HTML5DOMDocument->saveHTML()
		 * https://github.com/ivopetkov/html5-dom-document-php/blob/5a6bcb7c5399be1a5e589ae7daa3759c3b58bd6f/src/HTML5DOMDocument.php#L370
		 */
		if ( strpos( $text, 'html5-dom-document-internal-entity' ) !== false ) {
				$text = preg_replace( '/html5-dom-document-internal-entity1-(.*?)-end/', '&$1;', $text );
			if ( ! is_null( $text ) ) {
				$text = preg_replace( '/html5-dom-document-internal-entity2-(.*?)-end/', '&#$1;', $text );
			}
		}

		$code_to_remove = array(
			'html5-dom-document-internal-content',
			'<meta data-html5-dom-document-internal-attribute="charset-meta" http-equiv="content-type" content="text/html; charset=utf-8">',
			'</area>',
			'</base>',
			'</br>',
			'</col>',
			'</command>',
			'</embed>',
			'</hr>',
			'</img>',
			'</input>',
			'</keygen>',
			'</link>',
			'</meta>',
			'</param>',
			'</source>',
			'</track>',
			'</wbr>',
			'<![CDATA[-html5-dom-document-internal-cdata',
			'-html5-dom-document-internal-cdata]]>',
			'-html5-dom-document-internal-cdata-endtagfix',
		);
		if ( ! is_null( $text ) ) {
			$text = str_replace( $code_to_remove, '', $text );
		}
		if ( is_null( $text ) ) {
			$text = '';
		}
		return $text;
	}

	/**
	 * Insert inline references in database
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string $directive The -src CSP directive.
	 * @param  string $tagname   The HTML tag name.
	 * @param  string $content   The content of the html tag.
	 * @param  bool   $sticky    True if the script is sticky to the pages;
	 *                           sticky (whitelisted) scripts will always
	 *                           be inserted in CSP for the page.
	 * @param  string $page_url  The URL to be logged in db.
	 * @return void
	 */
	protected function insert_inline_content_in_db( $directive, $tagname, $content, $sticky = false, $page_url = null ): void {
		if ( 'script' === $tagname || 'style' === $tagname ) {
			$utf8 = true;
		} else {
			$utf8 = false;
		}
		switch ( $this->hash_in_use ) {
			case 'sha256':
				$hash = self::calculate_hash( 'sha256', $content, $utf8 );
				break;
			case 'sha384':
				$hash = self::calculate_hash( 'sha384', $content, $utf8 );
				break;
			case 'sha512':
				$hash = self::calculate_hash( 'sha512', $content, $utf8 );
				break;
			default:
				$hash = self::calculate_hash( 'sha256', $content, $utf8 );
				break;
		}

		if ( $hash ) {
			$inline_script_id = Nunil_Lib_Db::get_inl_id( $tagname, $hash );
		} else {
			$inline_script_id = null;
		}

		if ( is_null( $inline_script_id ) ) { // The script is not in the db.

			// Insert row in inline_scripts.
			$inline_script_id = Nunil_Lib_Db::insert_inl_in_db( $directive, $tagname, $content, $sticky, $utf8 );

			// Insert row in occurences.
			$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $inline_script_id, 'inline_scripts', $page_url );
		} else {
			// The script is in already in the db.
			// Now check if there is an occurence for the script in the page.
			$occurrence_id = Nunil_Lib_Db::get_occ_id( $inline_script_id, 'inline_scripts', $page_url );

			if ( is_null( $occurrence_id ) ) {
				$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $inline_script_id, 'inline_scripts', $page_url );
			} else {
				Nunil_Lib_Db::update_lastseen( $occurrence_id );
			}
		}
	}

	/**
	 * Insert external references in database
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string $directive  A string containing the CSP -src directive.
	 * @param  string $tagname    A string containing the HTML tag name.
	 * @param  string $src_attrib The content of src attr.
	 * @param  string $this_page_url (optional): The page where the element has been seen.
	 * @return false|int $external_script_id the ID (as int) of the inserted tag or false if not new tag to insert
	 */
	protected function insert_external_tag_in_db( $directive, $tagname, $src_attrib, $this_page_url = null ) {
		$returned_id = false;

		if ( '' !== $src_attrib ) {
			if ( '\'unsafe-eval\'' !== $src_attrib ) {
				try {
					$src_attrib = $this->clean_random_params( $src_attrib );
				} catch ( Nunil_Exception $e ) {
					$e->logexception();
					return $returned_id;
				}

				try {
					$src_attrib = $this->conv_to_absolute_url( $src_attrib );
				} catch ( Nunil_Exception $e ) {
					$e->logexception();
					return $returned_id;
				}
			}

			$external_script_id = Nunil_Lib_Db::get_ext_id( $directive, $tagname, $src_attrib );

			$add_hashes_and_occurences = Nunil_Lib_Utils::is_resource_hash_needed( $directive, $tagname );

			if ( is_null( $external_script_id ) ) { // The script is not in the db.

				// Insert row in external_scripts.
				$external_script_id = Nunil_Lib_Db::insert_ext_in_db( $directive, $tagname, $src_attrib );

				if ( true === $add_hashes_and_occurences ) {
					$this->insert_hashes_in_db( $external_script_id );
				}

				$returned_id = $external_script_id;

				// Insert row in occurences.
				// since 1.1.3: we need occurences only for hashed content.
				if ( true === $add_hashes_and_occurences ) {
					$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $external_script_id, 'external_scripts', $this_page_url );
				}
			} elseif ( true === $add_hashes_and_occurences ) {
				// The script is already in the db.
				// Now check if there is an occurence for the script in the page.
				// since 1.1.3: we need occurences only for hashed content.

				$occurrence_id = Nunil_Lib_Db::get_occ_id( $external_script_id, 'external_scripts', $this_page_url );

				if ( is_null( $occurrence_id ) ) {
					$occurrence_id = Nunil_Lib_Db::insert_occ_in_db( $external_script_id, 'external_scripts', $this_page_url );
				} else {
					// We have alredy recorded the occurence of the script in the page, so just update the timestamp.
					Nunil_Lib_Db::update_lastseen( $occurrence_id );
				}
			}
		}

		return $returned_id;
	}

	/**
	 * Insert hashes in DB for external resources
	 *
	 * @since 1.1.2
	 * @param int $external_script_id The id of the external resource in db.
	 * @throws Nunil_Exception Error in writing hashes to the db.
	 * @return void
	 */
	protected function insert_hashes_in_db( $external_script_id ): void {
		try {
			$sri = new Nunil_SRI();
			$sri->put_hashes_in_db( $external_script_id, $overwrite = false );
		} catch ( Nunil_Exception $ex ) {
			$ex->logexception();
		}
	}

	/**
	 * Get an array of src from srcset value
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string $srcset The string found in srcset value.
	 * @return array<string>
	 */
	protected function get_srcs_from_srcset( $srcset ) {
		$srcs         = array();
		$srcset_lines = array_map( 'trim', explode( ',', $srcset ) );
		foreach ( $srcset_lines as $line ) {
			$items  = array_map( 'trim', explode( ' ', $line ) );
			$srcs[] = $items[0];
		}
		return $srcs;
	}

	/**
	 * Removes some params from URI
	 *
	 * Used to remove random params from URI, before inserting external scripts in DB
	 * or checking for whitelist external script
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $uri_string The original URI.
	 * @throws Nunil_Exception Invalid url exception.
	 * @return string
	 */
	protected function clean_random_params( $uri_string ) {
		$removed_params = array(
			'rand',
			'random',
		);
		try {
			$uri = Uri::createFromString( $uri_string );
		} catch ( UriException $e ) {
			throw new Nunil_Exception( 'Invalid url in DOM: ' . esc_html( $uri_string ) . PHP_EOL . esc_html( $e ), 2001, 2 );
		}

		foreach ( $removed_params as $param ) {
			$new_uri = UriModifier::removeParams( $uri, $param );
		}
		return $new_uri->__toString();
	}

	/**
	 * Returns resource url
	 *
	 * Returns the absolute url of the resource
	 *
	 * @since 1.1.1
	 * @access protected
	 *
	 * @param string $src_string The search string.
	 * @param string $base_url The url of the page where links are found.
	 * @throws Nunil_Exception Invalid url exception.
	 * @return string
	 */
	protected function conv_to_absolute_url( $src_string, $base_url = '' ) {
		global $wp;
		if ( '' === $base_url ) {
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
		} else {
			$current_url = $base_url;
		}

		$home_url = Uri::createFromString( $current_url );

		try {
			$uri_object = Uri::createFromString( $src_string );
		} catch ( UriException $e ) {
			throw new Nunil_Exception( 'Invalid url in DOM: ' . esc_html( $src_string ) . PHP_EOL . esc_html( $e ), 2002, 2 );
		}

		if ( is_null( $uri_object->getScheme() ) ) {
			$scheme = $home_url->getScheme();
		} else {
			$scheme = $uri_object->getScheme();
		}

		if ( is_null( $uri_object->getHost() ) ) {
			$new_uri_object = Http::createFromBaseUri( $src_string, $current_url );
		}

		if ( ! isset( $new_uri_object ) ) {
			$new_uri_object = Uri::createFromString( $src_string )
			->withScheme( $scheme );
		}
		return $new_uri_object->__toString();
	}
}
