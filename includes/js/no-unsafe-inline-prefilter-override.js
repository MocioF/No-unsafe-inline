/**
 * Overrides jquery htmlPrefilter
 *
 * https://csplite.com/csp/test433/
 */
//jQuery.htmlPrefilter = function( html ) {
	// return ( html + '' ).replace( / style=/gi, ' data-style=' );
  // return ( html + '' ).replace( /(?<=<[^>]*?\s)\s*style\s*=\s*/gi, ' data-style=' );
//};

(function() {
    // Test per supporto lookbehind
    let supportsLookbehind = false;
    try {
        new RegExp("(?<=a)b"); // Se non lancia errore, lookbehind è supportato
        supportsLookbehind = true;
    } catch (e) {
        supportsLookbehind = false;
    }

    // Definisci la regex in base al supporto
    const regex = supportsLookbehind
        ? /(?<=<[^>]*?\s)\s*style\s*=\s*/gi // Lookbehind: sicuro e preciso
        : /(<[^>]*?\s)\s*style\s*=\s*/gi;   // Fallback: usa gruppo di cattura

    // Override di jQuery.htmlPrefilter
    jQuery.htmlPrefilter = function(html) {
        if (supportsLookbehind) {
            return (html + '').replace(regex, ' data-style=');
        } else {
            return (html + '').replace(regex, '$1data-style=');
        }
    };
})();
