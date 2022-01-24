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

	function updateInlineTableWorker(once) {
		$.ajax(
			{
				type: "post",
				dataType: "json",
				url: nunil_object.ajax_url,
				data: {
					action: "nunil_update_summary_inline_table",
				},
				success: function (res) {
					var nunil_inline_table_summary_data = '';
					$.each(
						res,
						function (index, value) {
							// ~ $.each(res, function(key, value){
							nunil_inline_table_summary_data += '<tr>';
							nunil_inline_table_summary_data += '<td data-th="' + __( 'Directive', 'no-unsafe-inline' ) + '">' + value.directive + '</td>';
							nunil_inline_table_summary_data += '<td data-th="' + __( 'Tagname', 'no-unsafe-inline' ) + '">' + value.tagname + '</td>';
							nunil_inline_table_summary_data += '<td data-th="' + __( 'Cluster', 'no-unsafe-inline' ) + '">' + value.clustername + '</td>';
							// ~ if (value.clustername.length > 11) {
							// ~ nunil_inline_table_summary_data += '<td data-th="' + __('Cluster', 'no-unsafe-inline') + '">' + value.clustername.substring(0, 8) + '...</td>';
							// ~ } else {
							// ~ nunil_inline_table_summary_data += '<td data-th="' + __('Cluster', 'no-unsafe-inline') + '">' + value.clustername + '</td>';
							// ~ }
							var wlText = '';
							if ( 1 == value.whitelist) {
								wlText = __( 'WL', 'no-unsafe-inline' );
							} else {
								wlText = __( 'BL', 'no-unsafe-inline' );
							}
							nunil_inline_table_summary_data += '<td data-th="' + __( 'Whitelist', 'no-unsafe-inline' ) + '">' + wlText + '</td>';
							nunil_inline_table_summary_data += '<td data-th="' + __( 'Num.', 'no-unsafe-inline' ) + '">' + value.num + '</td>';
							nunil_inline_table_summary_data += '</tr>';
							// ~ });
						}
					);

					$( "#nunil_inline_table_summary_body" ).html( nunil_inline_table_summary_data );
				},
				complete: function () {
					// Schedule the next request when the current one's complete
					if ($( "input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']" ).prop( "checked" )) {
						if (once === undefined) {
							setTimeout( updateInlineTableWorker, 25000 );
						}
					} else {
						clearTimeout( updateInlineTableWorker );
					}
				}
			}
		);
	};

	$( window ).on(
		'load',
		function () {
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
			// Trigger checkboxes check on select-all click.
			$( "#cb-select-all-1" ).on(
				"click",
				function (e) {
					$( ":checkbox[name*='bulk-select[]']" )
					.prop( "checked", this.checked )
					.change();
				}
			);

			// QUESTA ROBA VA SOLO SE LA SELECTED TAB è base-src script
			var mypage = getUrlParameter( 'page' );
			var mytab  = getUrlParameter( 'tab' );

			if ('no-unsafe-inline' === mypage && 'base-src' === mytab) {

				const matrix = new Array();
				const row    = new Array();

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
			// FINE QUESTA ROBA VA SOLO SE LA SELECTED TAB è base-src script
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
									$( "div#trigger_clustering_result" ).hide();
									$( "div#trigger_clustering_result" ).addClass( ['nunil_temp_div', 'type3'] );
									$( "div#trigger_clustering_result" ).html( res.report );
									$( "div#trigger_clustering_result" ).fadeIn( 400 );
									setTimeout(
										function () {
											$( "div#trigger_clustering_result" ).fadeOut( 4000 );
											$( "#nunil_trigger_clustering" ).prop( 'disabled', false );
										},
										4000
									);
									updateInlineTableWorker( "once" );
								} else {
									alert( "Error in clustering scripts." )
								}
							},
						}
					);
				}
			);

			// AJAX for Testing Button.
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
									$( "div#test_classifier_result" ).hide();
									$( "div#test_classifier_result" ).addClass( ['nunil_temp_div', 'type3'] );
									$( "div#test_classifier_result" ).html( res.report );
									$( "div#test_classifier_result" ).fadeIn( 400 );
									setTimeout(
										function () {
											$( "div#test_classifier_result" ).fadeOut( 16000 );
											$( "#nunil_test_classifier" ).prop( 'disabled', false );
										},
										16000
									);
									// ~ updateInlineTableWorker("once");
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
										$( "div#clean_database_result" ).hide();
										$( "div#clean_database_result" ).addClass( ['nunil_temp_div', 'type3'] );
										$( "div#clean_database_result" ).html( res.report );
										$( "div#clean_database_result" ).fadeIn( 400 );
										setTimeout(
											function () {
												$( "div#clean_database_result" ).fadeOut( 4000 )
											},
											4000
										);
										updateInlineTableWorker( "once" );
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
					updateInlineTableWorker();
				}
			}

			$( "input[type='checkbox'][name='no-unsafe-inline-tools[capture_enabled]']" ).change(
				function () {
					$( "input#submit" ).prop( "disabled", false );
					var $chk = $( this );
					if ($chk.prop( "checked" )) {
						updateInlineTableWorker();
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
