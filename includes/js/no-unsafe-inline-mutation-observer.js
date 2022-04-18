(function ($) {
    "use strict";

    function getStyleSheet(unique_title) {
        for (const sheet of document.styleSheets) {
            if (sheet.title === unique_title) {
                return sheet;
            }
        }
    }
    $(window).on(
        "load",
        function () {
            // The internal stylesheet created by Nunil_Manipulate_DOM::inject_inline_style()
            var nunilSheet = getStyleSheet("nunil-internal-stylesheet");

            $("[class^='nunil-fly-'],[class*=' nunil-fly-']").each(
                function (i, obj) {
                    let observer = new MutationObserver(callback),
                        observerOptions = {
                            attributes: true,
                            attributeFilter: ["style"],
                            attributeOldValue: false
                        };

                    function callback(mutations) {
                        let mutation;
                        for (mutation of mutations) {
                            if (mutation.type === "attributes") {
                                let newStyle = $(obj).attr("style");

                                let objClasses = $(obj).attr(
                                    "class").split(/\s+/);
                                for (let i = 0; i < objClasses
                                    .length; i++) {
                                    // These are classes created by Nunil_Manipulate_DOM::inject_inline_style()
                                    if (objClasses[i].indexOf(
                                            "nunil-fly-") === 0) {
                                        var myClass = objClasses[i];
                                        var mySelector = "." +
                                            myClass;
                                    }
                                }
                                let ruleList = nunilSheet.cssRules;
                                let newRule = mySelector + " { " +
                                    newStyle + " }";

                                for (let i = 0; i < ruleList
                                    .length; i++) {
                                    if (ruleList[i].selectorText
                                        .indexOf("." + myClass) == 0
                                    ) {
                                        nunilSheet.deleteRule(i);
                                        nunilSheet.insertRule(newRule);
                                    }
                                }
                            }
                        }
                    }
                    observer.observe(obj, observerOptions);
                }
            );
        }
    )
})(jQuery);
