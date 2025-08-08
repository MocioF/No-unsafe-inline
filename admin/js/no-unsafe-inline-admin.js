/*global
 nunil_object, jQuery, wp
 */
 jQuery(document).ready(function($) {
  "use strict";

  var a; // Used for endpoints list.
  var acc_options;
  var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split("&");
    var sParameterName;
    var i;

    for (i = 0; i < sURLVariables.length; i += 1) {
      sParameterName = sURLVariables[i].split("=");
      if (sParameterName[0] === sParam) {
        return (sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]));
      }
    }
    return false;
  };
  var mypage = getUrlParameter("page");
  var mytab = getUrlParameter("tab");
  var tb = $("table.nunil-ext-sources tbody");

  const { __ } = wp.i18n;

  $.fn.extend({
    clearText: function() {
      return this.clone() // clone the element
        .children() // select all the children
        .remove() // remove all the children
        .end() // again go back to selected element
        .text();
    }
  });

/*!
 * jQuery serialtabs
 * https://github.com/kevinmeunier/jquery-serialtabs
 *
 * Copyright 2022 Meunier Kévin
 * https://www.meunierkevin.com
 *
 * Released under the MIT license
 */
  $.fn.serialtabs = function(options){
    const settings = $.extend({}, $.fn.serialtabs.defaults, options);
    const base = this;

    $.extend(this, {
      init: function(){
        const event = settings.event+".serialtabs";
        const fxIn = settings.fxIn;
        settings.fxIn = "show";
        let $lists = $(this);

        this.each(function(){
          const $list = $(this);
          const $trigger = settings.getTrigger($list);
          const $triggerCurrent = $trigger.filter(".is-current");

          // Responsive design management
          if( settings.mode == "auto" ){
            let delay = false;
            let speed;
            $(window).on("resize.serialtabs", function(event, speed){
              if( delay !== false ) {
                clearTimeout(delay);
              }
              delay = setTimeout(function(){
                base.handleResize($list);
              }, 25);
            });

            // Manual trigger on load
            base.handleResize($list);
          } else {
            $list.attr("data-serialtabs-mode", settings.mode);
          }

          // Show the first element, or the current element
          if( $list.attr("data-serialtabs-mode") == "tabs" && $triggerCurrent.length == 0 ){
            $trigger.first().addClass("is-current");
          }

          // Hide all elements on page load
          $trigger.not(".is-current").each(function(){
            const $this = $(this);
            const $target = settings.getTarget($this);
            $target.hide();
          });

          // Bind event
          $trigger.on(event, function(event){
            const $this = $(this);
            base.handleEvent($this, $list);
            if( event.target.tagName == "A" ) {
              return true;
            }
          });
        });

        // Restore the initial setting
        settings.fxIn = fxIn;
      },

      handleEvent: function( $trigger, $list ){
        const $target = settings.getTarget($trigger);
        const $triggers = settings.getTrigger($list);
        const $prevTrigger =  $triggers.filter(".is-current");
        const $prevTarget = settings.getTarget($prevTrigger);

        // Avoid triggering the event for already displayed elements
        if( $list.attr("data-serialtabs-mode") == "tabs" && $trigger.is($prevTrigger) )
          return;

        // Hide the previous element
        if( $prevTrigger )
          base.display($prevTrigger, $prevTarget, false);

        // Show the new element
        if( !$trigger.is($prevTrigger) )
          base.display($trigger, $target, true);
      },

      display: function( $trigger, $target, action ){
        // Management of the current class
        $trigger[action ? "addClass" : "removeClass"]("is-current");

        // Management of the display state
        $target[action ? settings.fxIn : settings.fxOut]();

        // Check the radio button if existing
        if( action ){
          const $radio = $trigger.find("[type=radio]");
          if( $radio.length )
            $radio.prop("checked", true);
        }
      },

      getTrigger: function( $list ){
        let trigger;

        if( typeof settings.trigger  == "string" ){
          trigger = $list.find( settings.trigger );
        } else if( typeof settings.trigger  == "function" ){
          trigger = settings.trigger( $list );
        }

        return $(trigger);
      },

      getTarget: function( $trigger ){
        let target;

        if( typeof settings.target  == "string" ){
          target = $trigger.attr( settings.target );
        } else if( typeof settings.target  == "function" ){
          target = settings.target( $trigger );
        }

        return $(target);
      },

      handleResize: function( $list ){
        const isResponsive = base.isResponsive($list);

        // Update the display mode
        $list.attr("data-serialtabs-mode", (isResponsive ? "accordion" : "tabs"));

        // Move the content
        base.moveItems($list, isResponsive);
      },

      isResponsive: function( $list ){
        let breakpoint = $list.data("serialtabs-breakpoint") ? $list.data("serialtabs-breakpoint") : 50;

        // Breakpoint calculation
        if( breakpoint == 50 )
          $list.children().each(function(){
            breakpoint += $(this).outerWidth();
          });

        // Store the breakpoint (essential)
        $list.data("serialtabs-breakpoint", breakpoint);

        return ( $list.parent().width() < breakpoint );
      },

      moveItems: function( $list, isResponsive ){
        const $trigger = settings.getTrigger($list);

        $trigger.each(function(){
          const $this = $(this);
          const $target = settings.getTarget($this);

          if( isResponsive ){
            $target.insertAfter($this);
          } else {
            $target.insertAfter($list);
          }
        });
      }
    });

    // Initialisation
    this.init();
    return this;
  };

  $.fn.serialtabs.defaults = {
    mode: "auto", // 'auto', 'accordion', 'tabs'
    event: "click",
    getTrigger: function($list){
      return $list.find("[data-serialtabs]");
    },
    getTarget: function($trigger){
      return $($trigger.data("serialtabs"));
    },
    fxIn: "slideDown",
    fxOut: "slideUp"
  };
  /** END jQuery serialtabs */

  /**
   * Used to check at least one checkbox in the array is checked.
   *
   * If it is not, the first checkbox in the array is checked.
   */
  $.fn.checkOneAtLeast = function ( targets ){
    var checked = false;
    $(targets).each(function() {
      if ($(this).prop("checked")) {
        checked = true;
      }
    });
    if ( false === checked ) {
      $(targets)[0].prop("checked", true);
      $(targets)[0].trigger("change");
    }
    return this;
  }

  // Used to order strings in base rules
  function uniqueOrdered(string) {
    const categories = string.split(" ");
    const unique = Array.from(new Set(categories));
    const ordered = [];
    var i;
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "'self'") {
        ordered.push("'self'");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "http:") {
        ordered.push("http:");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "https:") {
        ordered.push("https:");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "data:") {
        ordered.push("data:");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "mediastream:") {
        ordered.push("mediastream:");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "blob:") {
        ordered.push("blob:");
        unique.splice(i, 1);
      }
    }
    for (i = 0; i < unique.length; i += 1) {
      if (unique[i] === "filesystem:") {
        ordered.push("filesystem:");
        unique.splice(i, 1);
      }
    }
    unique.sort();
    for (i = 0; i < unique.length; i += 1) {
      ordered.push(unique[i]);
    }
    return ordered.join(" ");
  }

  // Get microsecond resolutions using High Resolution Time API
  function microtime(getAsFloat) {
    var s;
    var now;
    var multiplier;

    if (performance !== undefined && performance.now) {
      now = (performance.now() + performance.timing.navigationStart) / 1000;
      multiplier = 1e6; // 1,000,000 for microseconds
    } else {
      now = (Date.now ? Date.now() : new Date().getTime()) / 1000;
      multiplier = 1e3; // 1,000
    }

    // Getting microtime as a float is easy
    if (getAsFloat) {
      return now;
    }

    // Dirty trick to only get the integer part
    s = now | 0;

    return (Math.round((now - s) * multiplier) / multiplier) + " " + s;
  }

  function updateSummaryTablesWorker(once) {
    $.ajax({
      complete: function() {
        // Schedule the next request when the current one"s complete
        if ($("input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']").prop("checked")) {
          if (once === undefined) {
            setTimeout(updateSummaryTablesWorker, 25000);
          }
        } else {
          clearTimeout(updateSummaryTablesWorker);
        }
      },
      data: {
        action: "nunil_update_summary_tables"
      },
      dataType: "json",
      success: function(res) {
        var nunil_db_summary_data = "";
        var nunil_external_table_summary_data = "";
        var nunil_inline_table_summary_data = "";
        var nunil_eventhandlers_table_summary_data = "";
        $.each(
          res,
          function(index, label) {
            if ("global" === index) {
              $.each(
                label,
                function(index, value) {
                  var wlText = "";
                  nunil_db_summary_data += "<tr>";
                  nunil_db_summary_data += "<td data-th=\"" + __("Type", "no-unsafe-inline") + "\">" + value.type + "</td>";
                  if ("1" === value.whitelist) {
                    wlText = __("WL", "no-unsafe-inline");
                  } else {
                    wlText = __("BL", "no-unsafe-inline");
                  }
                  nunil_db_summary_data += "<td data-th=\"" + __("Whitelist", "no-unsafe-inline") + "\">" + wlText + "</td>";
                  nunil_db_summary_data += "<td data-th=\"" + __("Num.", "no-unsafe-inline") + "\">" + value.num + "</td>";
                  nunil_db_summary_data += "<td data-th=\"" + __("Num. Clusters", "no-unsafe-inline") + "\">" + value.clusters + "</td>";
                  nunil_db_summary_data += "</tr>";
                }
              );
            }
            if ("inline" === index) {
              $.each(
                label,
                function(index, value) {
                  var wlText = "";
                  nunil_inline_table_summary_data += "<tr>";
                  nunil_inline_table_summary_data += "<td data-th=\"" + __("Directive", "no-unsafe-inline") + "\">" + value.directive + "</td>";
                  nunil_inline_table_summary_data += "<td data-th=\"" + __("Tagname", "no-unsafe-inline") + "\">" + value.tagname + "</td>";
                  nunil_inline_table_summary_data += "<td data-th=\"" + __("Cluster", "no-unsafe-inline") + "\">" + value.clustername + "</td>";
                  if ("1" === value.whitelist) {
                    wlText = __("WL", "no-unsafe-inline");
                  } else {
                    wlText = __("BL", "no-unsafe-inline");
                  }
                  nunil_inline_table_summary_data += "<td data-th=\"" + __("Whitelist", "no-unsafe-inline") + "\">" + wlText + "</td>";
                  nunil_inline_table_summary_data += "<td data-th=\"" + __("Num.", "no-unsafe-inline") + "\">" + value.num + "</td>";
                  nunil_inline_table_summary_data += "</tr>";
                }
              );
            }
            if ("external" === index) {
              $.each(
                label,
                function(index, value) {
                  var wlText = "";
                  nunil_external_table_summary_data += "<tr>";
                  nunil_external_table_summary_data += "<td data-th=\"" + __("Directive", "no-unsafe-inline") + "\">" + value.directive + "</td>";
                  nunil_external_table_summary_data += "<td data-th=\"" + __("Tagname", "no-unsafe-inline") + "\">" + value.tagname + "</td>";
                  nunil_external_table_summary_data += "<td data-th=\"" + __("Nonceable", "no-unsafe-inline") + "\">" + value.nonceable + "</td>";
                  switch (value.whitelist) {
                    case "1":
                      wlText = __("WL", "no-unsafe-inline");
                      break;
                    case "0":
                      wlText = __("BL", "no-unsafe-inline");
                      break;
                    default:
                      wlText = "--";
                  }
                  nunil_external_table_summary_data += "<td data-th=\"" + __("Whitelist", "no-unsafe-inline") + "\">" + wlText + "</td>";
                  nunil_external_table_summary_data += "<td data-th=\"" + __("Num.", "no-unsafe-inline") + "\">" + value.num + "</td>";
                  nunil_external_table_summary_data += "</tr>";
                }
              );
            }
            if ("events" === index) {
              $.each(
                label,
                function(index, value) {
                  var wlText = "";
                  nunil_eventhandlers_table_summary_data += "<tr>";
                  nunil_eventhandlers_table_summary_data += "<td data-th=\"" + __("Tagname", "no-unsafe-inline") + "\">" + value.tagname + "</td>";
                  nunil_eventhandlers_table_summary_data += "<td data-th=\"" + __("Event Attribute", "no-unsafe-inline") + "\">" + value.event_attribute + "</td>";
                  nunil_eventhandlers_table_summary_data += "<td data-th=\"" + __("Cluster", "no-unsafe-inline") + "\">" + value.clustername + "</td>";
                  if ("1" === value.whitelist) {
                    wlText = __("WL", "no-unsafe-inline");
                  } else {
                    wlText = __("BL", "no-unsafe-inline");
                  }
                  nunil_eventhandlers_table_summary_data += "<td data-th=\"" + __("Whitelist", "no-unsafe-inline") + "\">" + wlText + "</td>";
                  nunil_eventhandlers_table_summary_data += "<td data-th=\"" + __("Num.", "no-unsafe-inline") + "\">" + value.num + "</td>";
                  nunil_eventhandlers_table_summary_data += "</tr>";
                }
              );
            }
          }
        );
        $("#nunil_db_summary_body").html(nunil_db_summary_data);
        $("#nunil_external_table_summary_body").html(nunil_external_table_summary_data);
        $("#nunil_inline_table_summary_body").html(nunil_inline_table_summary_data);
        $("#nunil_eventhandlers_table_summary_body").html(nunil_eventhandlers_table_summary_data);
      },
      type: "post",
      url: nunil_object.ajax_url
    });
  }

  /**
   * Since trustworthy depends on user agent, we shouldnt' limit to https only.
   * However almost all browsers consider only https as trustworthy to deploy a report.
   *
   * https://w3c.github.io/reporting/#header
   * https://w3c.github.io/webappsec-secure-contexts/#is-origin-trustworthy
   */
  function isTrustworthyUrl(url_string) {
    if (!url_string || url_string === '') {
        return false;
    }

    const url = new URL(url_string);

    // Check if the protocol is https
    if (url.protocol === "https:") {
        return true;
    }

    // Check if the host matches 127.0.0.0/8 or ::1/128
    const host = url.hostname;
    if (host === 'localhost' || host === '127.0.0.1' || host === '::1') {
        return true;
    }

    return false;
  }

  function isValidHttpToken(_token) {
    if (_token === '') {
      return false;
    }
    const tokenRegex = /^[!#$%&'*+\-.^_`|~A-Za-z0-9]+$/;
    return typeof _token === 'string' && tokenRegex.test(_token);
  }

  function isUniqueNewEndpointKey(_newvalue) {
    var isUnique = true;
    $("input[type='hidden'][name^='no-unsafe-inline[endpoints]'][name$='[name]']").each(function() {
      if (_newvalue === $(this).val()) {
        isUnique = false;
        //$(this).effect("highlight", {}, 3000);
        $(this).prev().effect( "highlight", "slow" );
        return false;
      }
    });
    return isUnique;
  }

  // Open inline help from link
  $("#nunil-help-link").on(
    "click",
    function() {
      $("#contextual-help-link").click();
    }
  );

  // Trigger checkboxes check on select-all click.
  $("#cb-select-all-1").on(
    "click",
    function() {
      $(":checkbox[name*='bulk-select[]']")
        .prop("checked", this.checked)
        .change();
    }
  );
  // Buttons on operation report.
  $("#nunil-db-sum-tabs-5").on(
    "mouseenter",
    function() {
      $("#nunil_tools_operation_report_buttons").stop(true).fadeIn(600);
    }
  );

  $("#nunil-db-sum-tabs-5").on(
    "mouseleave",
    function() {
      $("#nunil_tools_operation_report_buttons").stop(true).fadeOut(600);
    }
  );
  $("#nunil_tools_operation_report_button_clipboard").tooltip({
    position: {
      at: "right center",
      my: "left+15 center"
    }
  });
  $("#nunil_tools_operation_report_button_clear").tooltip({
    position: {
      at: "right center",
      my: "left+15 center"
    }
  });

  $("#nunil_tools_operation_report_button_clipboard").on(
    "click",
    function(event) {
      event.preventDefault();
      CopyToClipboard($("#nunil_tools_operation_report").text(), true, $(this).data("notification"));
    }
  );

  function CopyToClipboard(value, showNotification, notificationText) {
    var temparea = $("<textarea>");
    $("body").append(temparea);
    temparea
      .val(value)
      .trigger("select");
    document
      .execCommand("copy");
    temparea
      .remove();

    if (typeof showNotification === "undefined") {
      showNotification = true;
    }
    if (typeof notificationText === "undefined") {
      notificationText = "Copied to clipboard";
    }
    if (showNotification) {
      new NunilOperationNotify(notificationText);
    }
  }

  function NunilOperationNotify(notificationText) {
    var notificationTag = $("div.copy-notification");
    if (notificationTag.length == 0) {
      notificationTag = $("<div/>", {
        "class": "ui-tooltip ui-corner-all ui-widget-shadow ui-widget ui-widget-content copy-notification",
        text: notificationText
      });
      $("#nunil_tools_operation_report_container").append(notificationTag);

      notificationTag.fadeIn("slow", function() {
        setTimeout(function() {
          notificationTag.fadeOut("slow", function() {
            notificationTag.remove();
          });
        }, 1000);
      });
    }
  }
  $("#nunil_tools_operation_report_button_clear").on(
    "click",
    function(event, notificationText = $(this).data("notification"), mytext = $(this).data("dialog-message")) {
      event.preventDefault();
      var dialog = $("div.nunil-tools-dialog");
      dialog = $("<div/>", {
        "class": "nunil-tools-dialog",
        text: mytext
      });
      $("#nunil_tools_operation_report_container").append(dialog);
      $(".nunil-tools-dialog").dialog({
        buttons: {
          Ok: function() {
            $(this).dialog("close");
            dialog.remove();
            $("#nunil_tools_operation_report").text('');
            NunilOperationNotify(notificationText);
          },
          Cancel: function() {
            $(this).dialog("close");
            dialog.remove();
          }
        },
        dialogClass: "no-titlebar",
        height: "auto",
        modal: true,
        resizable: false
      });
    }
  );
  // START base rules main tab.
  mypage = getUrlParameter("page");
  mytab = getUrlParameter("tab");
  tb = $("table.nunil-ext-sources tbody");
  if ("no-unsafe-inline" === mypage && "base-rule" === mytab) {
    const matrix = [];
    const row = [];

    // Populate -src input field on checkbox check.
    $("input[type='checkbox'][name*='bulk-select[]']").on("change",
      function() {
        var $chk = $(this);
        var directive = $chk
          .parent()
          .parent()
          .find("td:nth-child(2)")
          .clearText();
        var source = $chk.parent().parent().find("td:nth-child(3)").clearText();
        var textboxId =
          "no-unsafe-inline-base-rule\\[" + directive + "_base_rule\\]";
        if ($chk.prop("checked")) {
          $("#" + textboxId).val($("#" + textboxId).val() + " " + source);
          $("#" + textboxId).val(
            uniqueOrdered(
              $("#" + textboxId)
              .val()
              .trim()
            )
          );
        } else {
          $("#" + textboxId).val(
            $("#" + textboxId)
            .val()
            .replace(source, "")
          );
          $("#" + textboxId).val(
            uniqueOrdered(
              $("#" + textboxId)
              .val()
              .trim()
            )
          );
        }
      }
    );

    tb.find("tr").each(
      function(index, element) {
        $(element)
          .find("td")
          .each(
            function(index, element) {
              var colVal = $(element).clearText();
              row.push(colVal);
            }
          );
        matrix.push([row[0], row[1]]);
        row.length = 0;
      }
    );

    const managedDirectives = [
      "base-uri",
      "default-src",
      "script-src",
      "style-src",
      "font-src",
      "img-src",
      "frame-src",
      "media-src",
      "object-src",
      "connect-src",
      "manifest-src",
      "worker-src",
      "form-action"
    ];
    var srcDirectiveId;
    managedDirectives.forEach(
      function(directive) {
        srcDirectiveId =
          "no-unsafe-inline-base-rule\\[" + directive + "_base_rule\\]";
        const SetSources = $("#" + srcDirectiveId)
          .val()
          .split(" ");
        SetSources.forEach(
          function(source) {
            matrix.forEach(
              function(element, index) {
                if (element[0] === directive && element[1] === source) {
                  $(
                    "input[type='checkbox'][name*='bulk-select[]'][value='" +
                    (index + 1) +
                    "']"
                  ).prop("checked", true);
                }
              }
            );
          }
        );
      }
    );
  }
  // END base rules main tab.

  // START inline main tab.
  if ("no-unsafe-inline" === mypage && ("inline" === mytab || "events" === mytab)) {

    acc_options = {
      active: false,
      animation: 200,
      autoHeight: false,
      classes: {
        "ui-accordion-content": "hljs"
      },
      collapsible: true,
      heightStyle: "content"
    };
    $("div[class^='code-accordion-']").each(function() {
      $(this).accordion(acc_options);
    });
    $("div[class^='pages-accordion-']").each(function() {
      $(this).accordion(acc_options);
    });
  }

  // START settings main tab.
  a = $("#nunil-endpoints-list li").length;

  // jquery spinner for max-response-header-size
  $("input[type='text'][name='no-unsafe-inline[max_response_header_size]']").spinner({
    classes: {
      "ui-spinner": "highlight"
    },
    max: 24576,
    min: 512,
    numberFormat: "n",
    page: 5,
    step: 512
  });

  // Create tabs in options page.
  $("[id^=nunil-options-tabs-").addClass( "serialtabs-nav-content" );
  $(".serialtabs-nav > li").addClass( "sub-link-1" );
  $('.serialtabs-nav').serialtabs();

  // Handle SRI options in settings tab.
  if ("no-unsafe-inline" === mypage && "settings" === mytab) {
    $("input[type='checkbox'][name='no-unsafe-inline[sri_script]']").on("change",
      function() {
        var chk = $(this);
        if (chk.prop("checked")) {
          if ($("input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']").prop("checked") === false &&
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha384]']").prop("checked") === false &&
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha512]']").prop("checked") === false
          ) {
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']").prop("checked", true);
          }
        }
      }
    );
    $("input[type='checkbox'][name='no-unsafe-inline[sri_link]']").on("change",
      function() {
        var chk = $(this);
        if (chk.prop("checked")) {
          if ($("input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']").prop("checked") === false &&
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha384]']").prop("checked") === false &&
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha512]']").prop("checked") === false
          ) {
            $("input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']").prop("checked", true);
          }
        }
      }
    );
    // END SRI options in External script tab.

    // Enable and disable endpoints fields on use_reports toggle.
    if (!$("input[type='checkbox'][name='no-unsafe-inline[use_reports]']").prop("checked")) {
      $("input[type='checkbox'][name='no-unsafe-inline[use_report-uri]']").prop("disabled", true);
      $("input[type='checkbox'][name='no-unsafe-inline[use_report-to]']").prop("disabled", true);
      $("input[type='checkbox'][name='no-unsafe-inline[add_Report-To]']").prop("disabled", true);
      $("input[type='checkbox'][name='no-unsafe-inline[add_Reporting-Endpoints]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", true);
      $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", true);
      $(".nunil-btn-del-endpoint").prop("disabled", true);
      $(".nunil-hidden-endpoint").prop("disabled", true);
      $(".nunil-endpoint-string").removeClass("txt-active");
      $(".nunil-endpoint-string-unsaved").removeClass("txt-active-unsaved");
      $(".nunil-endpoint-string").addClass("txt-inactive");
      $(".nunil-endpoint-string-unsaved").addClass("txt-inactive");
    }
    $("input[type='checkbox'][name='no-unsafe-inline[use_reports]']").on("change",
      function() {
        var chk = $(this);
        if (chk.prop("checked")) {
          $("input[type='checkbox'][name='no-unsafe-inline[use_report-uri]']").prop("disabled", false);
          $("input[type='checkbox'][name='no-unsafe-inline[use_report-to]']").prop("disabled", false);
          $("input[type='checkbox'][name='no-unsafe-inline[add_Report-To]']").prop("disabled", false);
          $("input[type='checkbox'][name='no-unsafe-inline[add_Reporting-Endpoints]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", false);
          $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", false);
          $(".nunil-btn-del-endpoint").prop("disabled", false);
          $(".nunil-hidden-endpoint").prop("disabled", false);
          $(".nunil-endpoint-string").removeClass("txt-inactive");
          $(".nunil-endpoint-string-unsaved").removeClass("txt-inactive");
          $(".nunil-endpoint-string").addClass("txt-active");
          $(".nunil-endpoint-string-unsaved").addClass("txt-active-unsaved");
          $.fn.checkOneAtLeast([
            $("input[type='checkbox'][name='no-unsafe-inline[use_report-to]']"),
            $("input[type='checkbox'][name='no-unsafe-inline[use_report-uri]']")
          ]);
        } else {
          $("input[type='checkbox'][name='no-unsafe-inline[use_report-uri]']").prop("disabled", true);
          $("input[type='checkbox'][name='no-unsafe-inline[use_report-to]']").prop("disabled", true);
          $("input[type='checkbox'][name='no-unsafe-inline[add_Report-To]']").prop("disabled", true);
          $("input[type='checkbox'][name='no-unsafe-inline[add_Reporting-Endpoints]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", true);
          $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", true);
          $(".nunil-btn-del-endpoint").prop("disabled", true);
          $(".nunil-hidden-endpoint").prop("disabled", true);
          $(".nunil-endpoint-string").removeClass("txt-active");
          $(".nunil-endpoint-string-unsaved").removeClass("txt-active-unsaved");
          $(".nunil-endpoint-string").addClass("txt-inactive");
        }
      }
    );
    $("input[type='checkbox'][name='no-unsafe-inline[use_report-to]']").on("change",
      function() {
        var chk = $(this);
        if (chk.prop("checked")) {
          $.fn.checkOneAtLeast([
            $("input[type='checkbox'][name='no-unsafe-inline[add_Reporting-Endpoints]']"),
            $("input[type='checkbox'][name='no-unsafe-inline[add_Report-To]']")
          ]);
        }
      }
    );

    // needed for event delegation.
    // https://stackoverflow.com/questions/203198/event-binding-on-dynamically-created-elements
    $("#nunil-endpoints-list").on("click", ".nunil-btn-del-endpoint", function() {
      var cloned;
      cloned = $(this).closest("li").clone( true );
      cloned.children("button").removeClass("nunil-btn-del-endpoint").addClass("nunil-btn-restore-endpoint");
      cloned.children("button").children("span.dashicons-remove").addClass("dashicons-plus-alt").removeClass("dashicons-remove");
      cloned.children("button span").addClass("dashicons-plus-alt").removeClass("dashicons-remove");
      cloned.children("span.txt-active-unsaved").addClass("txt-inactive-unsaved").removeClass("txt-active-unsaved");
      cloned.children("span.txt-active").addClass("txt-inactive").removeClass("txt-active");
      cloned.children("input[type='hidden']").each(function() {
        var oldname = $(this).attr("name");
        $(this).attr("name", oldname.replace(/\[endpoints\]/, '[endpoints_deleted]'));
        var oldid = $(this).attr("id");
        $(this).attr("id", oldid.replace(/\[endpoints\]/, '[endpoints_deleted]'));
      });
      $(this).closest("li").remove();
      $("#nunil-endpoints-list").append(cloned);
    });
    $("#nunil-endpoints-list").on("click", ".nunil-btn-restore-endpoint", function() {
      var cloned;
      cloned = $(this).closest("li").clone( true );
      cloned.children("button").removeClass("nunil-btn-restore-endpoint").addClass("nunil-btn-del-endpoint");
      cloned.children("button").children("span.dashicons-plus-alt").addClass("dashicons-remove").removeClass("dashicons-plus-alt");
      cloned.children("button span.dashicons-plus-alt").addClass("dashicons-remove").removeClass("dashicons-plus-alt");
      cloned.children("span.txt-inactive-unsaved").addClass("txt-active-unsaved").removeClass("txt-inactive-unsaved");
      cloned.children("span.txt-inactive").addClass("txt-active").removeClass("txt-inactive");
      cloned.children("input[type='hidden']").each(function() {
        var oldname = $(this).attr("name");
        $(this).attr("name", oldname.replace(/\[endpoints_deleted\]/, '[endpoints]'));
         var oldid = $(this).attr("id");
        $(this).attr("id", oldid.replace(/\[endpoints_deleted\]/, '[endpoints]'));
      });
      $(this).closest("li").remove();
      $("#nunil-endpoints-list").append(cloned);
    });

    $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").on("focus",
      function() {
        $(this).removeClass("nunil-error-input");
      }
    );
    $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").on("focus",
      function() {
        $(this).removeClass("nunil-error-input");
      }
    );
    $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").on("click",
      function() {
        var new_endpoint;
        var new_endpoint_name;
        var endpoint_name_tag;

        if ( ! isTrustworthyUrl($("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val()) ) {
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").addClass("nunil-error-input");
          return;
        }
        // Se è il primo endpoint, il nome del gruppo è il nome del nuovo endpoint.
        // Disabilito il campo nome endpoint.
        if (a === 0) {
          $("input[type='text'][name='no-unsafe-inline[group_name]']").effect( "highlight", "slow" );
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").effect( "highlight", "slow" );
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val($("input[type='text'][name='no-unsafe-inline[group_name]']").val().trim());
        } else {
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", false);
        }
        if ( ! isValidHttpToken($("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val())) {
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").addClass("nunil-error-input");
          return;
        }
        if ( ! isUniqueNewEndpointKey($("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val()) ) {
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").addClass("nunil-error-input");
          return;
        };

        if (isTrustworthyUrl($("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val()) &&
        isValidHttpToken($("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val())) {
          if ( $("#nunil-endpoints-list li").length == 0) {
            $("#nunil-endpoints-list").append(
              "<li><span class=\"nunil-btn nunil-btn-endpoint-list\"><span class=\"dashicons dashicons-editor-ul\"></span></span>" +
				      "<span class=\"nunil-endpoint-string\"><b>" + __( 'endpoint URL', 'no-unsafe-inline' ) + "</b></span>" +
				      "<span class= \"nunil-endpoint-string\"><b>"+ __( 'endpoint name', 'no-unsafe-inline' ) + "</b></span></li>"
            );
          };

          new_endpoint = $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val().trim();
          new_endpoint_name = $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val().trim();

          if (a===0) {
            endpoint_name_tag = "<span class=\"nunil-endpoint-string-unsaved-disabled txt-active-unsaved txt-newly\">" + $("input[type='text'][name='no-unsafe-inline[group_name]']").val().trim() + "</span>" +
            "<input class=\"nunil-hidden-endpoint\" type=\"hidden\" id=\"no-unsafe-inline[endpoints][" + a + "][name]\"" +
            "name=\"no-unsafe-inline[endpoints][" + a + "][name]\" value=\"" + $("input[type='text'][name='no-unsafe-inline[group_name]']").val().trim() + "\" />";
          } else {
            endpoint_name_tag = "<span class=\"nunil-endpoint-string-unsaved-disabled txt-active-unsaved txt-newly\">" + new_endpoint_name + "</span>" +
            "<input class=\"nunil-hidden-endpoint\" type=\"hidden\" id=\"no-unsafe-inline[endpoints][" + a + "][name]\"" +
            "name=\"no-unsafe-inline[endpoints][" + a + "][name]\" value=\"" + new_endpoint_name + "\" />";
          }

          $("#nunil-endpoints-list").append(
            "<li>" +
            "<button  class=\"nunil-btn nunil-btn-del-endpoint\" id=\"no-unsafe-inline[del-endpoint][" + a + "]\" " +
            "name=\"no-unsafe-inline[del-endpoint][" + a + "]\" value=\"&#x2425;\">" +
            "<span class=\"dashicons dashicons-remove\"> </span></button>" +
            "<span class=\"nunil-endpoint-string-unsaved txt-active-unsaved txt-newly\">" + new_endpoint + "</span>" +
            "<input class=\"nunil-hidden-endpoint\" type=\"hidden\" id=\"no-unsafe-inline[endpoints][" + a + "][url]\"" +
            "name=\"no-unsafe-inline[endpoints][" + a + "][url]\" value=\"" + new_endpoint + "\" />" +
            endpoint_name_tag +
            "</li>");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val("");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").val("");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").removeClass("nunil-error-input");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint_name]']").removeClass("nunil-error-input");
          a = a + 1;
        }
      }
    );
  }
  // END endpoint section.

  // START tools main tab.
  $("#nunil-db-sum-tabs").tabs({
    active: false,
    classes: {
      "ui-tabs": "ui-corner-none",
      "ui-tabs-nav": "ui-corner-none",
      "ui-tabs-panel": "ui-corner-none",
      "ui-tabs-tab": "ui-corner-none"
    },
    collapsible: true
  });
  // AJAX for Clustering button.
  $("#nunil_trigger_clustering").on("click",
    function(e) {
      var clustering_nonce = $("#clustering_nonce").val();
      e.preventDefault();
      $("#nunil_trigger_clustering").prop("disabled", true);
      $.ajax({
        beforeSend: function() { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
          $("#nunil-spinner-blocks").removeClass("hidden");
        },
        complete: function() { // Set our complete callback, adding the .hidden class and hiding the spinner.
          $("#nunil-spinner-blocks").addClass("hidden");
        },
        data: {
          action: "nunil_trigger_clustering",
          nonce: clustering_nonce
        },
        dataType: "json",
        success: function(res) {
          if (res.type === "success") {
            $("div#nunil_tools_operation_report").append(res.report);
            $("#nunil_trigger_clustering").prop("disabled", false);
            updateSummaryTablesWorker("once");
          } else {
            $("div#nunil_tools_operation_report").append(
              microtime(true) + __("Error in clustering scripts.", "no-unsafe-inline") + "<br>"
            );
            $("#nunil_trigger_clustering").prop("disabled", false);
          }
          $("#nunil_tools_operation_report").animate({
            scrollTop: $("#nunil_tools_operation_report").prop("scrollHeight")
          }, 1000);
        },
        type: "post",
        url: nunil_object.ajax_url
      });
    }
  );

  // AJAX for Test Classifier Button.
  $("#nunil_test_classifier").on("click",
    function(e) {
      var clustering_nonce = $("#test_clussifier_nonce").val();
      e.preventDefault();
      $("#nunil_test_classifier").prop("disabled", true);
      $.ajax({
        beforeSend: function() { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
          $("#nunil-spinner-blocks").removeClass("hidden");
        },
        complete: function() { // Set our complete callback, adding the .hidden class and hiding the spinner.
          $("#nunil-spinner-blocks").addClass("hidden");
        },
        data: {
          action: "nunil_test_classifier",
          nonce: clustering_nonce
        },
        dataType: "json",
        success: function(res) {
          if (res.type === "success") {
            $("div#nunil_tools_operation_report").append(res.report);
            $("#nunil_test_classifier").prop("disabled", false);
          } else {
            $("div#nunil_tools_operation_report").append(
              microtime(true) + __("Error in test classifier scripts.", "no-unsafe-inline") + "<br>"
            );
            $("#nunil_test_classifier").prop("disabled", false);
          }
          $("#nunil_tools_operation_report").animate({
            scrollTop: $("#nunil_tools_operation_report").prop("scrollHeight")
          }, 1000);
        },
        type: "post",
        url: nunil_object.ajax_url
      });
    }
  );

  // AJAX for Clean Database Button.
  $("#nunil_clean_database").on("click",
    function(e) {
      var db_clean_nonce = $("#clean_db_nonce").val();
      if (window.confirm(__("Are you sure you want to clean db data?\n(This will not clear your base rules)", "no-unsafe-inline"))) {
        e.preventDefault();
        $("#nunil_clean_database").prop("disabled", true);
        $.ajax({
          beforeSend: function() { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
            $("#nunil-spinner-blocks").removeClass("hidden");
          },
          complete: function() { // Set our complete callback, adding the .hidden class and hiding the spinner.
            $("#nunil-spinner-blocks").addClass("hidden");
          },
          data: {
            action: "nunil_clean_database",
            nonce: db_clean_nonce
          },
          dataType: "json",
          success: function(res) {
            if (res.type === "success") {
              $("div#nunil_tools_operation_report").append(res.report);
              $("#nunil_clean_database").prop("disabled", false);
              updateSummaryTablesWorker("once");
            } else {
              $("div#nunil_tools_operation_report").append(
                microtime(true) + __("Error in cleaning tables.", "no-unsafe-inline") + "<br>"
              );
              $("#nunil_clean_database").prop("disabled", false);
            }
            $("#nunil_tools_operation_report").animate({
              scrollTop: $("#nunil_tools_operation_report").prop("scrollHeight")
            }, 1000);
          },
          type: "post",
          url: nunil_object.ajax_url
        });
      } else {
        return false;
      }
    }
  );

  // AJAX for Prune Database Button.
  $("#nunil_prune_database").on("click",
    function(e) {
      var db_prune_nonce = $("#prune_db_nonce").val();
      if (window.confirm(__("Are you sure you want to prune db data?\n(This will reduce cluster size and remove orphans entries from occurences table)", "no-unsafe-inline"))) {
        e.preventDefault();
        $("#nunil_prune_database").prop("disabled", true);
        $.ajax({
          beforeSend: function() { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
            $("#nunil-spinner-blocks").removeClass("hidden");
          },
          complete: function() { // Set our complete callback, adding the .hidden class and hiding the spinner.
            $("#nunil-spinner-blocks").addClass("hidden");
          },
          data: {
            action: "nunil_prune_database",
            nonce: db_prune_nonce
          },
          dataType: "json",
          success: function(res) {
            if (res.type === "success") {
              $("div#nunil_tools_operation_report").append(res.report);
              $("#nunil_prune_database").prop("disabled", false);
              updateSummaryTablesWorker("once");
            } else {
              $("div#nunil_tools_operation_report").append(
                microtime(true) + __("Error in pruning tables.", "no-unsafe-inline") + "<br>"
              );
              $("#nunil_prune_database").prop("disabled", false);
            }
            $("#nunil_tools_operation_report").animate({
              scrollTop: $("#nunil_tools_operation_report").prop("scrollHeight")
            }, 1000);
          },
          type: "post",
          url: nunil_object.ajax_url
        });
      } else {
        return false;
      }
    }
  );

  if ("no-unsafe-inline" === mypage &&
    ("tools" === mytab || null === mytab || false === mytab)) {
    $("input#submit").prop("disabled", true);
    if ($("input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']").prop("checked")) {
      updateSummaryTablesWorker();
    }
  }

  $("input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']").on("change",
    function() {
      var chk = $(this);
      $("input#submit").prop("disabled", false);
      if (chk.prop("checked")) {
        updateSummaryTablesWorker();
      }
    }
  );

  $("input[type='checkbox'][name='no-unsafe-inline-tools[test_policy]']").on("change",
    function() {
      var chk = $(this);
      $("input#submit").prop("disabled", false);
      if (chk.prop("checked")) {
        $("input[type='checkbox'][name='no-unsafe-inline-tools[enable_protection]']").prop("checked", false);
      }
    }
  );

  $("input[type='checkbox'][name='no-unsafe-inline-tools[enable_protection]']").on("change",
    function() {
      var chk = $(this);
      $("input#submit").prop("disabled", false);
      if (chk.prop("checked")) {
        $("input[type='checkbox'][name='no-unsafe-inline-tools[test_policy]']").prop("checked", false);
      }
    }
  );
});