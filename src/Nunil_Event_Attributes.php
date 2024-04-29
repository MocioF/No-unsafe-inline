<?php
/**
 * HTML Tags
 *
 * Class used to manage event attributes
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
class Nunil_Event_Attributes {

	/**
	 * Returned array of event attributes
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array<array<string>> An array of array of [tag] [attributes].
	 */
	private $attributes;


	/**
	 * The class constructor.
	 *
	 * Set the full array of event attributes.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->attributes = array();

		$body_events = array(
			'onafterprint',
			'onbeforeprint',
			'onbeforeunload',
			'onerror',
			'onhashchange',
			'onload',
			'onmessage',
			'onoffline',
			'ononline',
			'onpagehide',
			'onpageshow',
			'onpopstate',
			'onresize',
			'onstorage',
			'onunload',
		);

		$form_events = array(
			'onblur',
			'onchange',
			'oncontextmenu',
			'onfocus',
			'oninput',
			'oninvalid',
			'onreset',
			'onsearch',
			'onselect',
			'onsubmit',
		);

		$keyb_events = array(
			'onkeydown',
			'onkeypress',
			'onkeyup',
		);

		$mouse_events = array(
			'onclick',
			'ondblclick',
			'onmousedown',
			'onmousemove',
			'onmouseout',
			'onmouseover',
			'onmouseup',
			'onmousewheel',
			'onwheel',
		);

		$drag_events = array(
			'ondrag',
			'ondragend',
			'ondragenter',
			'ondragleave',
			'ondragover',
			'ondragstart',
			'ondrop',
			'onscroll',
		);

		$clip_events = array(
			'oncopy',
			'oncut',
			'onpaste',
		);

		$media_events = array(
			'onabort',
			'oncanplay',
			'oncanplaythrough',
			'oncuechange',
			'ondurationchange',
			'onemptied',
			'onended',
			'onerror',
			'onloadeddata',
			'onloadedmetadata',
			'onloadstart',
			'onpause',
			'onplay',
			'onplaying',
			'onprogress',
			'onratechange',
			'onseeked',
			'onseeking',
			'onstalled',
			'onsuspend',
			'ontimeupdate',
			'onvolumechange',
			'onwaiting',
		);

		$misc_events = array(
			'ontoggle',
		);

		foreach ( $body_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'body',
				'attr'   => $attribute,
			);
		}

		foreach ( $form_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'form',
				'attr'   => $attribute,
			);
		}

		foreach ( $keyb_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'keyboard',
				'attr'   => $attribute,
			);
		}

		foreach ( $mouse_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'mouse',
				'attr'   => $attribute,
			);
		}

		foreach ( $drag_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'drag',
				'attr'   => $attribute,
			);
		}

		foreach ( $clip_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'clipboard',
				'attr'   => $attribute,
			);
		}

		foreach ( $media_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'media',
				'attr'   => $attribute,
			);
		}

		foreach ( $misc_events as $attribute ) {
			$this->attributes[] = array(
				'object' => 'misc',
				'attr'   => $attribute,
			);
		}
	}

	/**
	 * Get array of event attributes, optionaly filtered by type of object
	 * https://www.w3schools.com/tags/ref_eventattributes.asp
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $filter_by One of 'body', 'form', 'keyboard', 'mouse', 'drag', 'clipboard', 'media', 'misc'.
	 * @return array<array<string>>;
	 */
	public function get_attributes( $filter_by = null ) {
		if ( ! $filter_by ) {
			return $this->attributes;
		} else {
			$new = array_filter(
				$this->attributes,
				function ( $myvar ) use ( $filter_by ) {
					return ( $myvar['object'] === $filter_by );
				}
			);
			return $new;
		}
	}
}
