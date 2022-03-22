<?php
/**
 * HTML Tags
 *
 * Class used to return an array of NUNIL\Nunil_HTML_tag object to be captured
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
 * Class with method used to return the array of captured tags object
 *
 * @package No_unsafe-inline
 * @since   1.0.0
 */
class Nunil_Captured_Tags {

	/**
	 * Static method used in class-no-unsafe-public.php
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array<\NUNIL\Nunil_HTML_Tag>
	 */
	public static function get_captured_tags() {
		$options = (array) get_option( 'no-unsafe-inline' );

		$tags = array();

		if ( 1 === $options['script-src_enabled'] ) {
			$directive = 'script-src';

			$tag          = 'script';
			$stored_attrs = 'src';
			$needed_attrs = array(
				array( 'type' => '! text/html' ),
				array( 'type' => '! text/template' ),
			);
			$inline       = true;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'script' ),
			);
			$inline       = false;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'script' ),
			);
			$inline       = false;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		if ( 1 === $options['style-src_enabled'] ) {
			$directive = 'style-src';

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'stylesheet' ),
			);
			$inline       = true;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'style' ),
			);
			$inline       = true;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'style' ),
			);
			$inline       = true;
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag    = 'style';
			$inline = true;
			$tags[] = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs = null, $needed_attrs = null, $childs = null, $inline );
		}

		$inline = false;

		if ( 1 === $options['img-src_enabled'] ) {
			$directive = 'img-src';

			$tag          = 'img';
			$stored_attrs = array( 'src', 'srcset' );
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'icon' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'apple-touch-icon' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'image' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'image' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'picture';
			$stored_attrs = array( 'src', 'srcset' );
			$childs       = array( 'source', 'img' );
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs, $inline );
		}

		if ( 1 === $options['frame-src_enabled'] ) {
			$directive = 'frame-src';

			$tag          = 'frame';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'iframe';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );
		}

		if ( 1 === $options['child-src_enabled'] ) {
			$directive = 'child-src';

			$tag          = 'frame';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'iframe';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );
		}

		if ( 1 === $options['media-src_enabled'] ) {
			$directive = 'media-src';

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'video' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'video' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'audio' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'audio' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'track' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'track' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$tag          = 'audio';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'audio';
			$stored_attrs = 'src';
			$childs       = 'source, track';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs, $inline );

			$tag          = 'video';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'video';
			$stored_attrs = 'src';
			$childs       = 'source, track';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs, $inline );

			$tag          = 'track';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );
		}

		if ( 1 === $options['object-src_enabled'] ) {
			$directive = 'object-src';

			$tag          = 'object';
			$stored_attrs = 'data';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'embed';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'applet';
			$stored_attrs = 'archive';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'applet';
			$stored_attrs = 'code';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'applet';
			$stored_attrs = 'codebase';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'applet';
			$stored_attrs = 'src';
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs = null, $childs = null, $inline );

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'embed' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'embed' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'object' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'object' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		if ( 1 === $options['connect-src_enabled'] ) {
			$directive = 'connect-src';

			$tag          = 'a';
			$stored_attrs = 'ping';
			$needed_attrs = array(
				array( 'ping' => '*' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		if ( 1 === $options['font-src_enabled'] ) {
			$directive = 'font-src';

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'preload' ),
				array( 'as' => 'font' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
				array( 'as' => 'font' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		if ( 1 === $options['manifest-src_enabled'] ) {
			$directive = 'manifest-src';

			$tag          = 'link';
			$stored_attrs = 'href';
			$needed_attrs = array(
				array( 'rel' => 'manifest' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		if ( 1 === $options['prefetch-src_enabled'] ) {
			$directive = 'prefetch-src';

			$tag          = 'link';
			$stored_attrs = 'src';
			$needed_attrs = array(
				array( 'rel' => 'prefetch' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );

			$needed_attrs = array(
				array( 'rel' => 'prerender' ),
			);
			$tags[]       = new Nunil_HTML_Tag( $directive, $tag, $stored_attrs, $needed_attrs, $childs = null, $inline );
		}

		return $tags;
	}
}
