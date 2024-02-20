<?php
/**
 * HTML Tags
 *
 * Class used to create a tag object used for capturing and managing html
 *
 * @package No_unsafe-inline
 * @link    https://wordpress.org/plugins/no-unsafe-inline/
 * @since   1.0.0
 */

namespace NUNIL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with methods used to create base -src rules for external content
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_HTML_Tag {

	/**
	 * The CPS -src directive the tag is used for
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var string A string that is one of managed CSP -src directives.
	 */
	private $directive;

	/**
	 * The HTML tag searched in the DOM.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var string A string that is the tag name of an HTML tag.
	 */
	private $tag;

	/**
	 * The attr of the HTML tag
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var array<string>|string|null The attribute of the HTML tag which content will be stored in the db (if the tag loads an external content).
	 */
	private $stored_attrs;

	/**
	 * The childs of the HTML tag
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var array<string>|string|null The childs tagnames of the HTML tagname where to look for $stored_attr, if any
	 */
	private $childs;

	/**
	 * The needed attrs of the HTML tag
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var array<array<string>>|null The needed attrs of the HTML tag searched in the DOM, if any
	 */
	private $needed_attrs;

	/**
	 * True if tag can be captured for inline (textContent)
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var bool False for tag that never have textContent we need to capture, else true.
	 */
	private $inline;

	/**
	 * The class constructor.
	 *
	 * Set the properties to default value if not specified.
	 *
	 * @param    string                    $directive     The CPS -src directive the tag is used for.
	 * @param    string                    $tag           The HTML tag searched in the DOM.
	 * @param    array<string>|string|null $stored_attrs  The stored attrs of the HTML tag; null is only inline (<style>).
	 * @param    array<array<string>>|null $needed_attrs  The needed attrs of the HTML tag.
	 * @param    array<string>|string|null $childs        The childs of the HTML tag.
	 * @param    bool                      $inline        True if the tag can have inline content to capture.
	 * @since    1.0.0
	 */
	public function __construct( string $directive, string $tag, $stored_attrs = null, $needed_attrs = null, $childs = null, $inline = true ) {
		if ( ! $directive ) {
			$this->directive = 'script-src';
		} else {
			$this->directive = $directive;
		}

		if ( ! $tag ) {
			$this->tag = 'script';
		} else {
			$this->tag = $tag;
		}

		if ( ! $stored_attrs ) {
			$this->stored_attrs = 'src';
		} else {
			$this->stored_attrs = $stored_attrs;
		}

		if ( is_null( $needed_attrs ) ) {
			$this->needed_attrs = null;
		} elseif ( is_array( $needed_attrs ) ) {
			if ( empty( $needed_attrs ) ) {
				$this->needed_attrs = null;
			} else {
				$this->needed_attrs = $needed_attrs;
			}
		}

		if ( is_null( $childs ) ) {
			$this->childs = null;
		} else {
			if ( is_array( $childs ) ) {
				$this->childs = $childs;
			}
			if ( is_string( $childs ) ) {
				$this->childs = array();
				$this->childs = array_map( 'trim', explode( ',', $childs ) );
			}
		}
		if ( $inline ) {
			$this->inline = true;
		} else {
			$this->inline = $inline;
		}
	}

	/**
	 * Check if $tag has childs
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_childs() {
		return ( ! is_null( $this->childs ) );
	}

	/**
	 * Return HTML tag name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name() {
		return $this->tag;
	}

	/**
	 * Return CSP -src directive
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_directive() {
		return $this->directive;
	}

	/**
	 * Return stored_attrs
	 *
	 * @since 1.0.0
	 * @return array<string>|null
	 */
	public function get_storedattrs() {
		if ( $this->stored_attrs ) {
			if ( is_string( $this->stored_attrs ) ) {
				$exploded = explode( ',', $this->stored_attrs );
				return array_map( 'trim', $exploded );
			} else {
				return $this->stored_attrs;
			}
		} else {
			return null;
		}
	}

	/**
	 * Return needed_attrs
	 *
	 * @since 1.0.0
	 * @return array<array<string>>|null
	 */
	public function get_neededattrs() {
		if ( $this->needed_attrs ) {
			return $this->needed_attrs;
		} else {
			return null;
		}
	}

	/**
	 * Return childs
	 *
	 * @since 1.0.0
	 * @return array<string>|null
	 */
	public function get_childs() {
		if ( $this->childs ) {
			if ( is_string( $this->childs ) ) {
				$exploded = explode( ',', $this->childs );
				return array_map( 'trim', $exploded );
			} else {
				return $this->childs;
			}
		} else {
			return null;
		}
	}

	/**
	 * Check if the tag can have inline content we need to capture.
	 *
	 * If true, the textContent will be captured only if the tag hasn't
	 * the $stored_attr and hasn't child.
	 * This is a fix for some particulars tags that we never want to
	 * capture if they don't have the $stored_attr in it, as:
	 * 'link' and 'a'.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function capture_inline() {
		if ( $this->inline ) {
			return true;
		} else {
			return false;
		}
	}
}
