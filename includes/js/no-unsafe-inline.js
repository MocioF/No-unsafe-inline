MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

var restRoute = WPURLS.restRoute;

var observer = new MutationObserver(
	function(mutations, observer) {
		// fired when a mutation occurs
		mutations.forEach(
			(mutation) => {
				if (mutation.attributeName === 'style') {
					var tag     = mutation.target.tagName;
					var style   = mutation.target.getAttribute( 'style' );
					var baseURI = mutation.target.baseURI;
					console.log( mutation );
					console.log( "OLD: " + mutation.oldValue );
					console.log( "NEW: " + style );

					var jsonData        = {};
					jsonData["tag"]     = tag.toLowerCase();
					jsonData["style"]   = style;
					jsonData["baseURI"] = baseURI;
					jsonData            = JSON.stringify( jsonData );

					// ~ var xhttp = new XMLHttpRequest();
					// ~ xhttp.open("POST", restRoute, true);
					// ~ xhttp.send(jsonData);
				}
			}
		)
		// ...
	}
);

// define what element should be observed by the observer
// and what types of mutations trigger the callback
observer.observe(
	document,
	{
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: ["style"],
		attributeOldValue: true,
		characterData: true,
		characterDataOldValue: false
	}
);
