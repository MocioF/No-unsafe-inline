/**
 * Adding data-style parser for jquery and let script to use setAttribute('style') without breaking CSP
 *
 * https://csplite.com/csp/test433/
 * https://csplite.com/csp/test343/
 */
var tags = document.querySelectorAll( '[data-style]' );
for (var tag of tags) {
	var attr = tag.getAttribute( 'data-style' );
	var arr  = attr.split( ';' ).map( (el, index) => el.trim() );
	for (var i = 0, tmp; i < arr.length; ++i) {
		if ( ! /:/.test( arr[i] ) ) {
			continue;
		}
		tmp = arr[i].split( ':' ).map( (el, index) => el.trim() );
		tag.style[ camelize( tmp[0] ) ] = tmp[1];
	}
}

class MyRegExp extends RegExp {
	[Symbol.split]( str, limit ) {
		let result = RegExp.prototype[Symbol.split].call( this, str, limit );
		return result.map( x => '(' + x + ')' );
	}
}

var setAttribute_              = Element.prototype.setAttribute;
Element.prototype.setAttribute = function (attr, val) {
	if (attr.toLowerCase() !== 'style') {
		// console.log("set " + attr + "=`" + val + "` natively");
		setAttribute_.apply( this, [attr, val] );
	} else {

		// This is tricky and we should find a better expression.
		// The problem is to correct split something as:
		// style="a_property: a_value; background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTc5MiIgaGVpZ2h0PSIxNzkyIiB2aWV3Qm94PSIwIDAgMTc5MiAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGZpbGw9IiNhN2FhYWQiIGQ9Ik02NDMgOTExdjEyOGgtMjUydi0xMjhoMjUyem0wLTI1NXYxMjdoLTI1MnYtMTI3aDI1MnptNzU4IDUxMXYxMjhoLTM0MXYtMTI4aDM0MXptMC0yNTZ2MTI4aC02NzJ2LTEyOGg2NzJ6bTAtMjU1djEyN2gtNjcydi0xMjdoNjcyem0xMzUgODYwdi0xMjQwcTAtOC02LTE0dC0xNC02aC0zMmwtMzc4IDI1Ni0yMTAtMTcxLTIxMCAxNzEtMzc4LTI1NmgtMzJxLTggMC0xNCA2dC02IDE0djEyNDBxMCA4IDYgMTR0MTQgNmgxMjQwcTggMCAxNC02dDYtMTR6bS04NTUtMTExMGwxODUtMTUwaC00MDZ6bTQzMCAwbDIyMS0xNTBoLTQwNnptNTUzLTEzMHYxMjQwcTAgNjItNDMgMTA1dC0xMDUgNDNoLTEyNDBxLTYyIDAtMTA1LTQzdC00My0xMDV2LTEyNDBxMC02MiA0My0xMDV0MTA1LTQzaDEyNDBxNjIgMCAxMDUgNDN0NDMgMTA1eiIvPjwvc3ZnPg==") !important;"
		let re1 = new MyRegExp( /(?<!\&quot);(?!base64)/gm ); // TODO: All semicolon not surrended by &quot; now, it is all semicolon not followed by base64 and not preceded by &quot
		let re2 = new MyRegExp( /(?<!(data,blob)):/gm ); // All colon not preceded by data
		// console.log("set " + attr + "=`" + val + "` via setAttribute('style') polyfill");
		var arr = val.split( re1 ).map( (el, index) => el.trim() );
		for (var i = 0, tmp; i < arr.length; ++i) {
			if ( ! /:/.test( arr[i] ) ) {
				continue;		// Empty or wrong
			}
			tmp = arr[i].split( re2 ).map( (el, index) => el.trim() );
			this.style[ camelize( tmp[0] ) ] = tmp[1];
			// console.log(camelize(tmp[0]) + ' = '+ tmp[1]);
		}
	}
};

function camelize( str ) {
	return str
	.split( '-' )
	.map(
		( word, index ) => index === 0 ? word : word[0].toUpperCase() + word.slice( 1 )
	)
	.join( '' );
}
