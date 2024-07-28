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

  const {
    __
  } = wp.i18n;

  $.fn.extend({
    clearText: function() {
      return this.clone() // clone the element
        .children() // select all the children
        .remove() // remove all the children
        .end() // again go back to selected element
        .text();
    }
  });

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

  function isValidReportUrl(_string) {
    var url_string;
    try {
      url_string = new URL(_string);
    } catch (_) {
      return false;
    }
    return url_string.protocol === "http:" || url_string.protocol === "https:";
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
      $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", true);
      $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", true);
      $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", true);
      $(".nunil-btn-del-endpoint").prop("disabled", true);
      $(".nunil-hidden-endpoint").prop("disabled", true);
      $(".nunil-endpoint-string").removeClass("txt-active");
      $(".nunil-endpoint-string").addClass("txt-inactive");
    }
    $("input[type='checkbox'][name='no-unsafe-inline[use_reports]']").on("change",
      function() {
        var chk = $(this);
        if (chk.prop("checked")) {
          $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", false);
          $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", false);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", false);
          $(".nunil-btn-del-endpoint").prop("disabled", false);
          $(".nunil-hidden-endpoint").prop("disabled", false);
          $(".nunil-endpoint-string").removeClass("txt-inactive");
          $(".nunil-endpoint-string").addClass("txt-active");
        } else {
          $("input[type='text'][name='no-unsafe-inline[group_name]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[max_age]']").prop("disabled", true);
          $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").prop("disabled", true);
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").prop("disabled", true);
          $(".nunil-btn-del-endpoint").prop("disabled", true);
          $(".nunil-hidden-endpoint").prop("disabled", true);
          $(".nunil-endpoint-string").removeClass("txt-active");
          $(".nunil-endpoint-string").addClass("txt-inactive");
        }
      }
    );
    // needed for event delegation.
    // https://stackoverflow.com/questions/203198/event-binding-on-dynamically-created-elements
    $("#nunil-endpoints-list").on("click", ".nunil-btn-del-endpoint", function() {
      $(this).closest("li").remove();
    });
    $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").on("focus",
      function() {
        $(this).removeClass("nunil-error-input");
      }
    );
    $("input[type='button'][name='no-unsafe-inline[add_new_endpoint]']").on("click",
      function() {
        var new_endpoint;
        if (isValidReportUrl($("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val())) {
          new_endpoint = $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val().trim();
          $("#nunil-endpoints-list").append(
            "<li>" +
            "<input  class=\"nunil-btn nunil-btn-del-endpoint\" type=\"button\" id=\"no-unsafe-inline[del-endpoint][" + a + "]\" " +
            "name=\"no-unsafe-inline[del-endpoint][" + a + "]\" value=\"&#x2425;\">" +
            "<span class=\"nunil-endpoint-string txt-active txt-newly\">" + new_endpoint + "</span>" +
            "<input class=\"nunil-hidden-endpoint\" type=\"hidden\" id=\"no-unsafe-inline[endpoints][" + a + "]\"" +
            "name=\"no-unsafe-inline[endpoints][" + a + "]\" value=\"" + new_endpoint + "\" />" +
            "</li>");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").val("");
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").removeClass("nunil-error-input");
          a = a + 1;
        } else {
          $("input[type='text'][name='no-unsafe-inline[new_endpoint]']").addClass("nunil-error-input");
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