/**
 * Overrides jquery htmlPrefilter
 *
 * https://csplite.com/csp/test433/
 */
var originalHtmlPrefilter = jQuery.htmlPrefilter;
jQuery.htmlPrefilter      = function( html ) {
	return ( html + '' ).replace( / style=/gi, ' data-style=' );
};
