(function ($) {
	'use strict';
	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	const { __ } = wp.i18n;

	jQuery.fn.extend(
		{
			clearText: function () {
				return this.clone() // clone the element
				.children() // select all the children
				.remove() // remove all the children
				.end() // again go back to selected element
				.text();
			}
		}
	);
	
	/**
	 * Used to order strings in base-src rules
	 */
	function UniqueOrdered(string) {
		const categories = string.split( ' ' );
		const unique     = Array.from( new Set( categories ) );
		const ordered    = new Array();
		var i;
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === "'self'") {
				ordered.push( "'self'" );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'http:') {
				ordered.push( 'http:' );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'https:') {
				ordered.push( 'https:' );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'data:') {
				ordered.push( 'data:' );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'mediastream:') {
				ordered.push( 'mediastream:' );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'blob:') {
				ordered.push( 'blob:' );
				unique.splice( i, 1 );
			}
		}
		for (i = 0; i < unique.length; i += 1) {
			if (unique[i] === 'filesystem:') {
				ordered.push( 'filesystem:' );
				unique.splice( i, 1 );
			}
		}
		unique.sort();
		for (i = 0; i < unique.length; i += 1) {
			ordered.push( unique[i] );
		}
		return ordered.join( ' ' );
	}

	var getUrlParameter = function getUrlParameter(sParam) {
		var sPageURL      = window.location.search.substring( 1 ),
			sURLVariables = sPageURL.split( '&' ),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split( '=' );

			if (sParameterName[0] === sParam) {
				return typeof sParameterName[1] === undefined ? true : decodeURIComponent( sParameterName[1] );
			}
		}
		return false;
	};

	function updateSummaryTablesWorker(once) {
		$.ajax(
			{
				type: "post",
				dataType: "json",
				url: nunil_object.ajax_url,
				
				data: {
					action: "nunil_update_summary_tables",
				},
				success: function (res) {
					//~ console.log( res );
					var nunil_db_summary_data = '';
					var nunil_external_table_summary_data = '';
					var nunil_inline_table_summary_data = '';
					var nunil_eventhandlers_table_summary_data = '';
					$.each(
						res,
						function (index, label) {
							if ( 'global' === index ) {
								$.each(
								label,
								function (index, value) {
									nunil_db_summary_data += '<tr>';
									nunil_db_summary_data += '<td data-th="' + __( 'Type', 'no-unsafe-inline' ) + '">' + value.Type + '</td>';
									var wlText = '';
									if ( 1 == value.whitelist) {
										wlText = __( 'WL', 'no-unsafe-inline' );
									} else {
										wlText = __( 'BL', 'no-unsafe-inline' );
									}
									nunil_db_summary_data += '<td data-th="' + __( 'Whitelist', 'no-unsafe-inline' ) + '">' + wlText + '</td>';
									nunil_db_summary_data += '<td data-th="' + __( 'Num.', 'no-unsafe-inline' ) + '">' + value.Num + '</td>';
									nunil_db_summary_data += '<td data-th="' + __( 'Num. Clusters', 'no-unsafe-inline' ) + '">' + value.Clusters + '</td>';
									nunil_db_summary_data += '</tr>';
								}
							)}
							if ( 'inline' === index ) {
								$.each(
								label,
								function (index, value) {
									nunil_inline_table_summary_data += '<tr>';
									nunil_inline_table_summary_data += '<td data-th="' + __( 'Directive', 'no-unsafe-inline' ) + '">' + value.directive + '</td>';
									nunil_inline_table_summary_data += '<td data-th="' + __( 'Tagname', 'no-unsafe-inline' ) + '">' + value.tagname + '</td>';
									nunil_inline_table_summary_data += '<td data-th="' + __( 'Cluster', 'no-unsafe-inline' ) + '">' + value.clustername + '</td>';
									var wlText = '';
									if ( 1 == value.whitelist) {
										wlText = __( 'WL', 'no-unsafe-inline' );
									} else {
										wlText = __( 'BL', 'no-unsafe-inline' );
									}
									nunil_inline_table_summary_data += '<td data-th="' + __( 'Whitelist', 'no-unsafe-inline' ) + '">' + wlText + '</td>';
									nunil_inline_table_summary_data += '<td data-th="' + __( 'Num.', 'no-unsafe-inline' ) + '">' + value.num + '</td>';
									nunil_inline_table_summary_data += '</tr>';
								}
							)}
							if ( 'external' === index ) {
								$.each(
								label,
								function (index, value) {
									nunil_external_table_summary_data += '<tr>';
									nunil_external_table_summary_data += '<td data-th="' + __( 'Directive', 'no-unsafe-inline' ) + '">' + value.directive + '</td>';
									nunil_external_table_summary_data += '<td data-th="' + __( 'Tagname', 'no-unsafe-inline' ) + '">' + value.tagname + '</td>';
									var wlText = '';
									if ( 1 == value.whitelist) {
										wlText = __( 'WL', 'no-unsafe-inline' );
									} else {
										wlText = __( 'BL', 'no-unsafe-inline' );
									}
									nunil_external_table_summary_data += '<td data-th="' + __( 'Whitelist', 'no-unsafe-inline' ) + '">' + wlText + '</td>';
									nunil_external_table_summary_data += '<td data-th="' + __( 'Num.', 'no-unsafe-inline' ) + '">' + value.num + '</td>';
									nunil_external_table_summary_data += '</tr>';
								}
							)}
							if ( 'events' === index ) {
								$.each(
								label,
								function (index, value) {
									nunil_eventhandlers_table_summary_data += '<tr>';
									nunil_eventhandlers_table_summary_data += '<td data-th="' + __( 'Tagname', 'no-unsafe-inline' ) + '">' + value.tagname + '</td>';
									nunil_eventhandlers_table_summary_data += '<td data-th="' + __( 'Event Attribute', 'no-unsafe-inline' ) + '">' + value.event_attribute + '</td>';
									nunil_eventhandlers_table_summary_data += '<td data-th="' + __( 'Cluster', 'no-unsafe-inline' ) + '">' + value.clustername + '</td>';
									var wlText = '';
									if ( 1 == value.whitelist) {
										wlText = __( 'WL', 'no-unsafe-inline' );
									} else {
										wlText = __( 'BL', 'no-unsafe-inline' );
									}
									nunil_eventhandlers_table_summary_data += '<td data-th="' + __( 'Whitelist', 'no-unsafe-inline' ) + '">' + wlText + '</td>';
									nunil_eventhandlers_table_summary_data += '<td data-th="' + __( 'Num.', 'no-unsafe-inline' ) + '">' + value.num + '</td>';
									nunil_eventhandlers_table_summary_data += '</tr>';
								}
							)}
						}
					);

					$( "#nunil_db_summary_body" ).html( nunil_db_summary_data );
					$( "#nunil_external_table_summary_body" ).html( nunil_external_table_summary_data );
					$( "#nunil_inline_table_summary_body" ).html( nunil_inline_table_summary_data );
					$( "#nunil_eventhandlers_table_summary_body" ).html( nunil_eventhandlers_table_summary_data );
					
				},
				complete: function () {
					// Schedule the next request when the current one's complete
					if ($( "input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']" ).prop( "checked" )) {
						if (once === undefined) {
							setTimeout( updateSummaryTablesWorker, 25000 );
						}
					} else {
						clearTimeout( updateSummaryTablesWorker );
					}
				}
			}
		);
	};

	$( window ).on(
		'load',
		function () {
			// Open inline help from link
			$( "#nunil-help-link" ).on(
				"click",
				function() {
					$("#contextual-help-link").click();
				}
			);
		
			// Trigger checkboxes check on select-all click.
			$( "#cb-select-all-1" ).on(
				"click",
				function (e) {
					$( ":checkbox[name*='bulk-select[]']" )
					.prop( "checked", this.checked )
					.change();
				}
			);

			// START base-src main tab.
			var mypage = getUrlParameter( 'page' );
			var mytab  = getUrlParameter( 'tab' );

			if ('no-unsafe-inline' === mypage && 'base-src' === mytab) {
				const matrix = new Array();
				const row    = new Array();

				// Populated -src input field on checkbox check.
				$( "input[type='checkbox'][name*='bulk-select[]']" ).change(
					function () {
						var $chk      = $( this );
						var directive = $chk
						.parent()
						.parent()
						.find( "td:nth-child(2)" )
						.clearText();
						var source    = $chk.parent().parent().find( "td:nth-child(3)" ).clearText();
						var textboxId =
						"no-unsafe-inline-base-src\\[" + directive + "_base_source\\]";
						if ($chk.prop( "checked" )) {
							$( "#" + textboxId ).val( $( "#" + textboxId ).val() + " " + source );
							$( "#" + textboxId ).val(
								UniqueOrdered(
									$( "#" + textboxId )
									.val()
									.trim()
								)
							);
						} else {
							$( "#" + textboxId ).val(
								$( "#" + textboxId )
								.val()
								.replace( source, "" )
							);
							$( "#" + textboxId ).val(
								UniqueOrdered(
									$( "#" + textboxId )
									.val()
									.trim()
								)
							);
						}
					}
				);

				var tb   = $( "table.nunil-ext-sources tbody" );
				var size = tb.find( "tr" ).length;
				tb.find( "tr" ).each(
					function (index, element) {
						var colSize = $( element ).find( "td" ).length;
						$( element )
						.find( "td" )
						.each(
							function (index, element) {
								var colVal = $( element ).clearText();
								row.push( colVal );
							}
						);
						matrix.push( [row[0], row[1]] );
						row.length = 0;
					}
				);

				// Per ciascuna delle opzioni prelevate dalla stringa, devo fare il check della box.
				const managedDirectives = [
					'base-uri',
					'default-src',
					'script-src',
					'style-src',
					'font-src',
					'img-src',
					'frame-src',
					'media-src',
					'object-src',
					'child-src',
					'connect-src',
					'manifest-src',
					'worker-src',
					'form-action',
				];
				var srcDirectiveId;
				var srcString;
				var SetSources = new Array();

				managedDirectives.forEach(
					function (directive) {
						srcDirectiveId   =
						"no-unsafe-inline-base-src\\[" + directive + "_base_source\\]";
						const SetSources = $( "#" + srcDirectiveId )
						.val()
						.split( " " );
						SetSources.forEach(
							function (source) {
								matrix.forEach(
									function (element, index) {
										if (element[0] === directive && element[1] === source) {
											$(
												"input[type='checkbox'][name*='bulk-select[]'][value='" +
												(index + 1) +
												"']"
											).prop( "checked", true );
										}
									}
								);
							}
						);
					}
				);
			}
			// END base-src main tab.
			
			// START inline main tab.
			if ('no-unsafe-inline' === mypage && 'inline' === mytab) {

				var acc_options = { active: false, collapsible: true, animation: 200, heightStyle: "content", autoHeight: false, classes: {"ui-accordion-content": "hljs"} };
				$( "div[class^='code-accordion-']" ).each( function() { $( this ).accordion( acc_options ) } );
				$( "div[class^='pages-accordion-']" ).each( function() { $( this ).accordion( acc_options ) } );
			}

			if ('no-unsafe-inline' === mypage && 'events' === mytab) {

				var acc_options = { active: false, collapsible: true, animation: 200, heightStyle: "content", autoHeight: false, classes: {"ui-accordion-content": "hljs"} };
				$( "div[class^='code-accordion-']" ).each( function() { $( this ).accordion( acc_options ) } );
				$( "div[class^='pages-accordion-']" ).each( function() { $( this ).accordion( acc_options ) } );
			}
			
			// START settings main tab.
			// Handle SRI options in settings tab.
			if ('no-unsafe-inline' === mypage && 'settings' === mytab) {
				$( "input[type='checkbox'][name='no-unsafe-inline[sri_script]']" ).change(
					function () {
						var $chk = $( this );
						if ($chk.prop( "checked" )) {
							if ( $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']" ).prop( "checked" ) == false &&
							 $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha384]']" ).prop( "checked" ) == false &&
							 $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha512]']" ).prop( "checked" ) == false
							) {
								$( "input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']" ).prop( "checked", true );
							}
						}
					}
				);
				$( "input[type='checkbox'][name='no-unsafe-inline[sri_link]']" ).change(
					function () {
						var $chk = $( this );
						if ($chk.prop( "checked" )) {
							if ( $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']" ).prop( "checked" ) == false &&
							 $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha384]']" ).prop( "checked" ) == false &&
							 $( "input[type='checkbox'][name='no-unsafe-inline[sri_sha512]']" ).prop( "checked" ) == false
							) {
								$( "input[type='checkbox'][name='no-unsafe-inline[sri_sha256]']" ).prop( "checked", true );
							}
						}
					}
				);

			}
			// End SRI options in External script tab.
			
			// START tools main tab.
			$( "#nunil-db-sum-tabs" ).tabs({
				active: false,
				collapsible: true,
				classes: {
					"ui-tabs": "ui-corner-none",
					"ui-tabs-nav": "ui-corner-none",
					"ui-tabs-tab": "ui-corner-none",
					"ui-tabs-panel": "ui-corner-none"
				}
			});
			// AJAX for Clustering button.
			$( "#nunil_trigger_clustering" ).click(
				function (e) {
					e.preventDefault();
					$( "#nunil_trigger_clustering" ).prop( 'disabled', true );
					var clustering_nonce = jQuery( "#clustering_nonce" ).val();
					jQuery.ajax(
						{
							type: "post",
							dataType: "json",
							url: nunil_object.ajax_url,
							data: {
								action: "nunil_trigger_clustering",
								nonce: clustering_nonce
							},
							success: function (res) {
								if (res.type == "success") {
									$( "div#nunil_tools_operation_report" ).append( res.report );
									$( "#nunil_trigger_clustering" ).prop( 'disabled', false );
									updateSummaryTablesWorker( "once" );
								} else {
									alert( "Error in clustering scripts." )
								}
							},
						}
					);
				}
			);

			// AJAX for Test Classifier Button.
			$( "#nunil_test_classifier" ).click(
				function (e) {
					e.preventDefault();
					$( "#nunil_test_classifier" ).prop( 'disabled', true );
					var clustering_nonce = jQuery( "#test_clussifier_nonce" ).val();
					jQuery.ajax(
						{
							type: "post",
							dataType: "json",
							url: nunil_object.ajax_url,
							data: {
								action: "nunil_test_classifier",
								nonce: clustering_nonce
							},
							success: function (res) {
								if (res.type == "success") {
									$( "div#nunil_tools_operation_report" ).append( res.report );
									$( "#nunil_test_classifier" ).prop( 'disabled', false );
								} else {
									alert( "Error in test classifier scripts." )
								}
							},
						}
					);
				}
			);

			$( "#nunil_clean_database" ).click(
				function (e) {
					if (confirm( __( 'Are you sure you want to clean db data?\n(This will not clear your base-src rules)', 'no-unsafe-inline' ) )) {
						e.preventDefault();
						var db_clean_nonce = jQuery( "#clean_db_nonce" ).val();
						jQuery.ajax(
							{
								type: "post",
								dataType: "json",
								url: nunil_object.ajax_url,
								data: {
									action: "nunil_clean_database",
									nonce: db_clean_nonce
								},
								success: function (res) {
									if (res.type == "success") {
										$( "div#nunil_tools_operation_report" ).append( res.report );
										$( "#nunil_clean_database" ).prop( 'disabled', false );
										updateSummaryTablesWorker( "once" );
									} else {
										alert( "Error in cleaning tables." )
									}
								},
							}
						);
					} else {
						return false;
					}
				}
			);
			if ("no-unsafe-inline" === mypage &&
			("tools" === mytab || null === mytab || false === mytab)) {
				$( "input#submit" ).prop( "disabled", true );
				if ($( "input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']" ).prop( "checked" )) {
					updateSummaryTablesWorker();
				}
			}

			$( "input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']" ).change(
				function () {
					$( "input#submit" ).prop( "disabled", false );
					var $chk = $( this );
					if ($chk.prop( "checked" )) {
						updateSummaryTablesWorker();
					}
				}
			);

			$( "input[type='checkbox'][name='no-unsafe-inline-tools[test_policy]']" ).change(
				function () {
					$( "input#submit" ).prop( "disabled", false );
					var $chk = $( this );
					if ($chk.prop( "checked" )) {
						$( "input[type='checkbox'][name='no-unsafe-inline-tools[enable_protection]']" ).prop( "checked", false );
					}
				}
			);

			$( "input[type='checkbox'][name='no-unsafe-inline-tools[enable_protection]']" ).change(
				function () {
					$( "input#submit" ).prop( "disabled", false );
					var $chk = $( this );
					if ($chk.prop( "checked" )) {
						$( "input[type='checkbox'][name='no-unsafe-inline-tools[test_policy]']" ).prop( "checked", false );
					}
				}
			);

		}
	);
})( jQuery );
