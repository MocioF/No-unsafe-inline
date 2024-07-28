/**
 * Adding data-style parser for jquery and let script to use setAttribute('style') without breaking CSP
 *
 * https://csplite.com/csp/test433/
 * https://csplite.com/csp/test343/
 */

/**
 * This class extends RegExp to customize the behavior of
 * String.prototype.split method
 */
class nunilCustomRegExp extends RegExp {
  /**
   * Overrides the default behavior of String.prototype.split
   *
   * @param {String} str - The string to split
   * @param {Number} limit - The limit of splits
   * @returns {Array} - An array of split strings
   */
  [Symbol.split](str, limit) {
    let result = RegExp.prototype[Symbol.split].call(this, str, limit);
    return result.map(x => "(" + x + ")");
  }
}

/**
 * Select all elements with data-style attribute
 *
 * @type {NodeList}
 */
var nunilElementsWithDataStyle = document.querySelectorAll("[data-style]");

/**
 * Regular expression to match all semicolons not followed by base64
 * and not preceded by &quot
 *
 * This is tricky and we should find a better expression because it should be:
 * all semicolon not surrended by &quot; ,
 * The problem is to correct split something as:
 * style="a_property: a_value; background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTc5MiIgaGVpZ2h0PSIxNzkyIiB2aWV3Qm94PSIwIDAgMTc5MiAxNzkyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGZpbGw9IiNhN2FhYWQiIGQ9Ik02NDMgOTExdjEyOGgtMjUydi0xMjhoMjUyem0wLTI1NXYxMjdoLTI1MnYtMTI3aDI1MnptNzU4IDUxMXYxMjhoLTM0MXYtMTI4aDM0MXptMC0yNTZ2MTI4aC02NzJ2LTEyOGg2NzJ6bTAtMjU1djEyN2gtNjcydi0xMjdoNjcyem0xMzUgODYwdi0xMjQwcTAtOC02LTE0dC0xNC02aC0zMmwtMzc4IDI1Ni0yMTAtMTcxLTIxMCAxNzEtMzc4LTI1NmgtMzJxLTggMC0xNCA2dC02IDE0djEyNDBxMCA4IDYgMTR0MTQgNmgxMjQwcTggMCAxNC02dDYtMTR6bS04NTUtMTExMGwxODUtMTUwaC00MDZ6bTQzMCAwbDIyMS0xNTBoLTQwNnptNTUzLTEzMHYxMjQwcTAgNjItNDMgMTA1dC0xMDUgNDNoLTEyNDBxLTYyIDAtMTA1LTQzdC00My0xMDV2LTEyNDBxMC02MiA0My0xMDV0MTA1LTQzaDEyNDBxNjIgMCAxMDUgNDN0NDMgMTA1eiIvPjwvc3ZnPg==") !important;"
 *
 * @type {nunilCustomRegExp}
 */
var nunilSemiColonRegex = new nunilCustomRegExp(/(?<!&quot);(?!base64)/gm);

/**
 * Regular expression to match all colons not preceded by data or blob
 *
 * @type {nunilCustomRegExp}
 */
var nunilColonRegex = new nunilCustomRegExp(/(?<![data,blob]):/gm);

for (var element of nunilElementsWithDataStyle) {
  var styleAttribute = element.getAttribute("data-style");
  var styleArray = styleAttribute.split(nunilSemiColonRegex).map((el) => el.trim());
  for (var i = 0, tmp; i < styleArray.length; ++i) {
    if (!(/:/).test(styleArray[i])) {
      continue;
    }
    tmp = styleArray[i].split(nunilColonRegex).map((el) => el.trim());
    element.style[toCamelCase(tmp[0])] = tmp[1];
  }
}

/**
 * RexExps used in HTML functions
 */
const nunilStyleReg = /<style ([^<>]+)?>(.+?)<\/style>/gims;
const nunilScriptReg = /<script ([^<>]+)?>(.+?)<\/script>/gims;
const nunilLinkReg = /<link ([^<>]+)?>/gim;
const nunilNonceReg = /\s*nonce\s*=\s*((?:'|")(.+?)(?:'|"))/sim;
const nunilIntegrityReg = /\s*integrity\s*=\s*((?:'|")(.+?)(?:'|"))/sim;
const nunilSrcReg = /\s*src\s*=\s*((?:'|")(.+?)(?:'|"))/sim;
const nunilStylesheetReg = /stylesheet/sim;
const nunilHrefReg = /\s*href\s*=\s*((?:'|")(.+?)(?:'|"))/sim;

/**
 * Convert a CSS property to camel case
 *
 * @param {String} str - The string to convert
 * @returns {String} - The camel case string
 */
function toCamelCase(str) {
  return str.replace(/-([a-z])/g, function (match, letter) {
    return letter.toUpperCase();
  });
}

/**
 * Store a reference to the native setAttribute method
 */
var nativeSetAttribute = Element.prototype.setAttribute;

/**
 * Override the setAttribute method to handle "style" attribute
 *
 * @param {string} attr - The attribute to set
 * @param {string} val - The value to assign to the attribute
 * @returns {undefined}
 */
Element.prototype.setAttribute = function (attr, val) {
  if (attr.toLowerCase() !== "style") {
    // console.log("set " + attr + "=`" + val + "` natively");
    nativeSetAttribute.apply(this, [attr, val]);
  } else {
    var arr = val.split(nunilSemiColonRegex).map((el) => el.trim());
    for (var i = 0, tmp; i < arr.length; ++i) {
      if (!(/:/).test(arr[i])) {
        continue; // Empty or wrong
      }
      tmp = arr[i].split(nunilColonRegex).map((el) => el.trim());
      this.style[toCamelCase(tmp[0])] = tmp[1];
      // console.log(toCamelCase(tmp[0]) + ' = '+ tmp[1]);
    }
  }
};

/**
 * True if Nonce is used for external <script src="">
 *
 * @type {boolean}
 */
const nunilUseExternalScriptNonce = nunilCheckExternalScriptNonce();

/**
 * True if Nonce is used for inline <script>inline</script>
 *
 * @type {boolean}
 */
const nunilUseInlineScriptNonce = nunilCheckInlineScriptNonce();

/**
 * True if Nonce is used for <link>
 *
 * @type {boolean}
 */
const nunilUseLinkNonce = nunilCheckLinkNonce();

/**
 * True if Nonce is used for <style>
 *
 * @type {boolean}
 */
const nunilUseStyleNonce = nunilCheckStyleNonce();

/**
 * The nonce used for <script>, or false if unused
 *
 * @type {(string | false)}
 */
const nunilScriptNonce = nunilGetScriptNonce();


/**
 * The nonce used for <link>, or false if unused
 *
 * @type {(string | false)}
 */
const nunilStyleNonce = nunilGetStyleNonce();

/**
 * True if integrity is used for <link> tags.
 *
 * @type {boolean}
 */
const nunilUseLinkIntegrity = nunilCheckLinkIntegrity();

/**
 * True if integrity is used for <script> tags.
 *
 * @type {boolean}
 */
const nunilUseScriptIntegrity = nunilCheckScriptIntegrity();

/**
 * Manipulates arguments of Node: appendChild() method
 * and Node: insertBefore() method
 */
function nunilNodeFunction() {
  // These should be non-"parser-inserted" script elements and should work without a nonce
  // when strict-dynamic is set in script-src (or default-src)
  const handleScriptElement = async (element) => {
    if (nunilUseExternalScriptNonce && nunilScriptNonce && element.hasAttribute("src")) {
      element.setAttribute("nonce", nunilScriptNonce);
    }
    if (nunilUseInlineScriptNonce && nunilScriptNonce && !(element.hasAttribute("src"))) {
      element.setAttribute("nonce", nunilScriptNonce);
    }
    if (element.hasAttribute("src") && nunilUseScriptIntegrity) {
      try {
        let scriptChild;
        scriptChild = await nunilRemoteDigest(element);
        return scriptChild;
      } catch (error) {
        console.log(error);
      }
    };
    // here returns only if not returned in the try/catch block
    return element;
  }

  const handleDocumentFragment = (fragment) => {
    let nodeList = fragment.querySelectorAll("[style]");
    nodeList.forEach((currentValue) => {
      let style = currentValue.getAttribute("style");
      if (style.trim() !== "") {
        currentValue.removeAttribute("style");
        currentValue.setAttribute("data-style-nunil-ac", style);
      }
    });

    nodeList = fragment.querySelectorAll("script");
    nodeList.forEach((currentValue) => {
      if (nunilUseExternalScriptNonce && nunilScriptNonce && currentValue.hasAttribute("src")) {
        currentValue.setAttribute("nonce", nunilScriptNonce);
      }
      if (nunilUseInlineScriptNonce && nunilScriptNonce && !(currentValue.hasAttribute("src"))) {
        currentValue.setAttribute("nonce", nunilScriptNonce);
      }
    });

    if (nunilUseScriptIntegrity) {
      nodeList.forEach(async (currentValue) => {
        if (currentValue.hasAttribute("src")) {
          try {
            let scriptChild;
            scriptChild = await nunilRemoteDigest(currentValue);
            return scriptChild;
          } catch (error) {
            console.log(error);
          }
        }
      });
    }

    if (nunilUseStyleNonce && nunilStyleNonce) {
      nodeList = fragment.querySelectorAll("style");
      nodeList.forEach((currentValue) => {
        currentValue.setAttribute("nonce", nunilStyleNonce);
      });
    }
    if (nunilUseLinkNonce && nunilStyleNonce) {
      nodeList = fragment.querySelectorAll("link");
      nodeList.forEach((currentValue) => {
        currentValue.setAttribute("nonce", nunilStyleNonce);
      });
    }
    if (nunilUseLinkIntegrity) {
      nodeList = fragment.querySelectorAll("link");
      nodeList.forEach(async (currentValue) => {
        if (currentValue.hasAttribute("href")) {
          try {
            let linkChild;
            linkChild = await nunilRemoteDigest(currentValue);
            return linkChild;
          } catch (error) {
            console.log(error);
          }
        }
      });
    }
  };

  const handleElement = async (element) => {
    if (element.getAttribute("style")) {
      let style = element.getAttribute("style");
      element.removeAttribute("style");
      element.setAttribute("data-style-nunil-ac", style);
    }

    if (element.nodeName === "STYLE ") {
      if (nunilUseStyleNonce && nunilStyleNonce) {
        element.setAttribute("nonce", nunilStyleNonce);
      }
    }

    if (element.nodeName === "LINK") {
      if (nunilUseLinkNonce && nunilStyleNonce) {
        element.setAttribute("nonce", nunilStyleNonce);
      }
      if (nunilUseLinkIntegrity) {
        try {
          let scriptChild;
          scriptChild = await nunilRemoteDigest(element);
          return scriptChild;
        } catch (error) {
          console.log(error);
        }
      }
      return element;
    }
  };
  if (arguments[0] instanceof HTMLScriptElement) {
    handleScriptElement(arguments[0]);
  } else if (Object.prototype.isPrototypeOf.call(
    DocumentFragment.prototype, arguments[0])) {
    handleDocumentFragment(arguments[0]);
  } else if (Object.prototype.isPrototypeOf.call(
    Element.prototype, arguments[0])) {
    handleElement(arguments[0]);
  } else {
    // Handle other cases if needed
    // console.log("ELSE?");
  }
};

/**
 * Store a reference to the native appendChild method
 */
var nativeAppendChild = Element.prototype.appendChild;

/**
 * Overriding Node: appendChild() method
 *
 * @returns {Element.prototype.appendChild}
 */
Element.prototype.appendChild = function () {
  nunilNodeFunction.apply(this, arguments);
  var child = nativeAppendChild.apply(this, arguments);
  nunilParseArgToStyle("data-style-nunil-ac");
  return child;
};

/**
 * Store a reference to the native insertBefore method
 */
var nativeInsertBefore = Element.prototype.insertBefore;

/**
 * Overriding Node: insertBefore() method
 *
 * @returns {Element.prototype.appendChild}
 */
Element.prototype.insertBefore = function () {
  nunilNodeFunction.apply(this, arguments);
  var child = nativeInsertBefore.apply(this, arguments);
  nunilParseArgToStyle("data-style-nunil-ac");
  return child;
};

/**
 * Store a reference to the native InsertAdjacentHTML method
 */
var nativeInsertAdjacentHTML = Element.prototype.insertAdjacentHTML;

/**
 * Overriding insertAdjacentHTML()
 *
 * @returns {undefined}
 */
Element.prototype.insertAdjacentHTML = function () {
  var position = arguments[0];
  var html = arguments[1];
  var replaced = html

  replaced = nunilMaybeSetStyleNonce(replaced);
  replaced = nunilMaybeSetLinkNonce(replaced);
  replaced = nunilMaybeSetScriptNonce(replaced);
  replaced = nunilMaybeSetScriptIntegrity(replaced);
  replaced = nunilMaybeSetLinkIntegrity(replaced);
  replaced.replace(/\bstyle=/gim, "data-style-nunil-iah=");

  nativeInsertAdjacentHTML.apply(this, [position, replaced]);
  nunilParseArgToStyle("data-style-nunil-iah");
};


/**
 * Element.innerHTML setter
 */
var innerHTMLSetter_ = Object.getOwnPropertyDescriptor(Element.prototype, "innerHTML").set;

Object.defineProperty(Element.prototype, "innerHTML", {
  set: function (value) {
    var replaced = value;
    replaced = nunilMaybeSetStyleNonce(replaced);
    replaced = nunilMaybeSetLinkNonce(replaced);
    replaced = nunilMaybeSetScriptNonce(replaced);
    replaced = nunilMaybeSetScriptIntegrity(replaced);
    replaced = nunilMaybeSetLinkIntegrity(replaced);
    replaced = replaced.replace(/\bstyle=/gim, "data-style-nunil-inh=");

    //Call the original setter
    innerHTMLSetter_.call(this, replaced);

    requestAnimationFrame(() => {
      nunilParseArgToStyle("data-style-nunil-inh");
    });
  }
});

/**
 * Retrieve the value of 'argString' attribute and set it to style attribute.
 *
 * @param {String} argString
 */
function nunilParseArgToStyle(argString) {
  var NodeList = document.querySelectorAll("[" + argString + "]");
  NodeList.forEach(function (currentValue) {
    var myStyle = currentValue.getAttribute(argString);
    currentValue.removeAttribute(argString);
    currentValue.setAttribute("style", myStyle);
    currentValue.style.cssText = myStyle;
  });
}

/**
 * Get the nonce used for <style> tags in the Document.
 *
 * @returns {(String | false)}
 */
function nunilGetStyleNonce() {
  var nunilStylesheet;
  if (document.getElementById("nunil-internal-stylesheet")) {
    nunilStylesheet = document.getElementById("nunil-internal-stylesheet");
  } else {
    nunilStylesheet = document.querySelector("style");
  }
  if (nunilStylesheet.hasAttribute("nonce")) {
    var styleNonce = nunilStylesheet.getAttribute("nonce");
    if ("" === styleNonce) {
      styleNonce = nunilStylesheet.nonce;
    }
    return styleNonce;
  }
  return false;
}

/**
 * Check if integrity is used for <link> tags
 *
 * @returns {boolean}
 */
function nunilCheckLinkIntegrity() {
  var nunilCss;
  if (document.getElementById("no-unsafe-inline-css")) { // only in admin pages
    nunilCss = document.getElementById("no-unsafe-inline-css");
  } else {
    nunilCss = document.querySelector("link");
  }
  if (nunilCss.hasAttribute("integrity")) {
    return true;
  }
  return false;
}

/**
 * Check if integrity is used for <script> tags
 *
 * @returns {boolean}
 */
function nunilCheckScriptIntegrity() {
  if (document.currentScript.hasAttribute("integrity")) {
    return true;
  }
  return false;
}

/**
 * Check if nonce is used for external <script>
 *
 * @returns {boolean}
 */
function nunilCheckExternalScriptNonce() {
  if (document.currentScript.hasAttribute("nonce")) {
    return true;
  }
  return false;
}

/**
 * Check if nonce is used for inline <script>
 *
 * @returns {boolean}
 */
function nunilCheckInlineScriptNonce() {
  var scriptList = document.querySelectorAll("script:not([src])");
  return [...scriptList].every(currentValue => {
    if (!currentValue.hasAttribute("nonce")) {
      return false;
    }
    return true;
  });
}

/**
 * Check if nonce is used for <link>
 *
 * @returns {boolean}
 */
function nunilCheckLinkNonce() {
  var linkList = document.querySelectorAll("link");
  return [...linkList].every(currentValue => {
    if (!currentValue.hasAttribute("nonce")) {
      return false;
    }
    return true;
  });
}

/**
 * Check if nonce is used for <style>
 *
 * @returns {boolean}
 */
function nunilCheckStyleNonce() {
  var styleList = document.querySelectorAll("style");
  return [...styleList].every(currentValue => {
    if (!currentValue.hasAttribute("nonce")) {
      return false;
    }
    return true;
  });
}

/**
 * Get the nonce used for <script> tags in the Document.
 *
 * @returns {(String | false)}
 */
function nunilGetScriptNonce() {
  var scriptNonce = "";
  if (document.currentScript.hasAttribute("nonce")) {
    scriptNonce = document.currentScript.getAttribute("nonce");
    if ("" === scriptNonce) {
      scriptNonce = document.currentScript.nonce;
    }
  } else {
    var scriptList = document.querySelectorAll("script");
    scriptList.forEach(function (currentValue) {
      if (currentValue.nonce) {
        if ("" !== currentValue.nonce) {
          scriptNonce = currentValue.nonce;
        }
      }
    });
  }
  if ("" !== scriptNonce) {
    return scriptNonce;
  }
  return false;
}


/**
 * Adds nonce used for <style> and append it to <style> tags
 * if nonce is used for <style> tags in document
 * 
 * @param {String} html
 * @returns {String}
 */
function nunilMaybeSetStyleNonce(html) {
  if (nunilStyleNonce && nunilUseStyleNonce) {
    var replacedStyle = html.replace(nunilStyleReg, function (match, args, content) {
      if (false === nunilNonceReg.test(args)) {
        return "<style nonce=\"" + nunilStyleNonce + "\"" + args + ">" + content + "</style>";
      } else {
        return "<style " + args + ">" + content + "</style>";
      }
    })
    return replacedStyle;
  }
  return html;
}


/**
 * Adds nonce used for <link> and append it to <link> tags
 * if nonce is used for <link> tags in document
 * 
 * @param {String} html
 * @returns {String}
 */
function nunilMaybeSetLinkNonce(html) {
  if (nunilStyleNonce && nunilUseLinkNonce) {
    var replacedLink = html.replace(nunilLinkReg, function (match, args) {
      if (false === nunilNonceReg.test(args)) {
        return "<link nonce=\"" + nunilStyleNonce + "\"" + args + ">";
      } else {
        return "<link " + args + ">";
      }
    })
    return replacedLink;
  }
  return html;
}

/**
 * Appends nonce to <script> tags if nonce is used
 * for <script> tags in document
 *
 * @param {String} html
 * @returns {String}
 */
function nunilMaybeSetScriptNonce(html) {
  if (nunilScriptNonce) {
    var replacedScript = html.replace(nunilScriptReg, function (match, args, content) {
      if (false === nunilNonceReg.test(args)) { // se non c'è già il nonce tra gli args
        if (false === nunilSrcReg.test(args) && nunilUseInlineScriptNonce) {
          return "<script nonce=\"" + nunilScriptNonce + "\"" + args + ">" + content + "</script>";
        }
        if (nunilSrcReg.test(args) && nunilUseExternalScriptNonce) {
          return "<script nonce=\"" + nunilScriptNonce + "\"" + args + ">" + content + "</script>";
        }
      }
      return "<script " + args + ">" + content + "</script>";
    })
    return replacedScript;
  }
  return html;
}

/**
 * Adds integrity attribute to <script> tags
 * if integrity is used for <script> tags in document
 *
 * @param {String} html
 * @returns {String}
 */
function nunilMaybeSetScriptIntegrity(html) {
  if (nunilUseScriptIntegrity) {
    var replacedScript = html.replace(nunilScriptReg, async function (match, args, content) {
      if (false === nunilIntegrityReg.test(args)) { // se non c'è già l'att integrity tra gli args
        if (false === nunilSrcReg.test(args)) { // non c'è src
          try {
            let sha256 = await nunilDigestMessage(content);
            return "<script " + args + "integrity=\"sha256-" + sha256 + " crossorigin=\"anonymous\">" + content + "</script>";
          } catch (e) {
            console.log(e);
            throw e;
          }
        } else {
          try {
            let matchUrl = args.match(nunilSrcReg);
            let url = matchUrl[2];
            let responseText = await nunilRemoteFetch(url);
            //console.log(responseText );
            let sha256 = await nunilDigestMessage(responseText);
            return "<script " + args + "integrity=\"sha256-" + sha256 + " crossorigin=\"anonymous\">" + content + "</script>";
          } catch (e) {
            console.log(e);
            throw e;
          }
        }
      }
    });
    return replacedScript;
  }
  return html;
}

/**
 * Adds integrity attribute to <link> tags
 * if integrity is used for <link> tags in document
 *
 * @param {String} html
 * @returns {String}
 */
function nunilMaybeSetLinkIntegrity(html) {
  if (nunilUseLinkIntegrity) {
    var replacedLink = html.replace(nunilLinkReg, async function (match, args) {
      if (false === nunilIntegrityReg.test(args) && // se non c'è già l'att integrity tra gli args 
        true === nunilStylesheetReg.test(args) &&  // è uno StyleSheet
        true === nunilHrefReg.test(args)) // ha l'attributo HREF
      {
        try {
          let matchUrl = args.match(nunilHrefReg);
          let url = matchUrl[2];
          let responseText = await nunilRemoteFetch(url);
          let sha256 = await nunilDigestMessage(responseText);
          return "<link " + args + "integrity=\"sha256-" + sha256 + " crossorigin=\"anonymous\"/>";
        } catch (e) {
          console.log(e);
          throw e;
        }
      }
    });
    return replacedLink;
  }
  return html;
}

/**
 * Calculates a base64 sha256 hash of message
 *
 * @async
 * @param {String} message
 * @returns {Promise}
 */
async function nunilDigestMessage(message) {
  const encoder = new TextEncoder();
  const msgUint8 = encoder.encode(message); // encode as (utf-8) Uint8Array
  const hashBuffer = await window.crypto.subtle.digest("SHA-256", msgUint8);
  const hashB64 = btoa(String.fromCharCode(... new Uint8Array(hashBuffer)));
  return hashB64;
}

/**
 * Fetch a remote resource
 *
 * @async
 * @param {String} url
 * @returns {Promise} resolves to string
 */
async function nunilRemoteFetch(url) {
  let response = await fetch(url);
  let text = await response.text(); // read response body as text
  return text;
}

/**
 * Adds integrity hash to appended Child Node
 *
 * @async
 * @param {Node} node
 * @returns {Promise} Resolves to node.
 */
async function nunilRemoteDigest(node) {
  if (node.tagName === "SCRIPT") {
    if (node.src) {
      let url = node.src;
      try {
        let responseText = await nunilRemoteFetch(url);
        //console.log(responseText );
        let sha256 = await nunilDigestMessage(responseText);
        //console.log(sha256);
        node.setAttribute("integrity", "sha256-" + sha256);
        node.setAttribute("crossorigin", "anonymous");
        return node;
      } catch (e) {
        console.log(e);
        throw e;
      }
    } else {
      try {
        let sha256 = await nunilDigestMessage(node.innerText);
        node.setAttribute("integrity", "sha256-" + sha256);
        node.setAttribute("crossorigin", "anonymous");
        return node;
      } catch (e) {
        console.log(e);
        throw e;
      }
    }
  } else {
    if (node.tagName === "LINK") {
      if (node.href && node.rel && nunilStylesheetReg.test(node.rel)) {
        let url = node.href;
        try {
          let responseText = await nunilRemoteFetch(url);
          let sha256 = await nunilDigestMessage(responseText);
          node.setAttribute("integrity", "sha256-" + sha256);
          node.setAttribute("crossorigin", "anonymous");
          return node;
        } catch (e) {
          console.log(e);
          throw e;
        }
      }
    }
  }
}