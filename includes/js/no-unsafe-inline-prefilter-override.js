/**
 * Overrides jquery htmlPrefilter
 *
 * https://csplite.com/csp/test433/
 */
jQuery.htmlPrefilter = function( html ) {
	return ( html + '' ).replace( / style=/gi, ' data-style=' );
  // return ( html + '' ).replace( / (?<!<script.*)(?!<\/script>) style==/gim, ' data-style=' );
};
