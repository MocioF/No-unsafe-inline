window.addEventListener("securitypolicyviolation", function (e) {
  /**
   * serialize current DOM-Tree incl. changes/edits to ss-variable
   *
   * This file is here for future implementations in the management
   * of CSP violations, client side.
   */
  // if (e.lineNumber && e.columnNumber) {
  //   var ns = new XMLSerializer();
  //   var ss = ns.serializeToString(document);
  //   var spvColumnNumber = e.columnNumber;
  //   var spvLineNumber = e.lineNumber;
  //   var txtArr = ss.split("\n");
  //   console.log("CSP Violation");
  //   console.log("Line: " + spvLineNumber + " - Column: " + spvColumnNumber );
  //   var LineCode1 = txtArr[spvLineNumber - 1];
  //   var LineCode2 = txtArr[spvLineNumber];
  //   console.log("Previous line: " + LineCode1);
  //   console.log("Error Line: " + LineCode2);
  //   console.log(e.explicitOriginalTarget);
  // }
  return;
});


