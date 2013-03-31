/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

var casAdmin;

(function($) {

	$( "#cas-accordion" ).accordion({
		header: 'h4',
		autoHeight: false,
		collapsible: true,
		heightStyle: 'content'
	});
	$( ".cas-tabs" ).tabs({
		heightStyle:'auto'
	});

	var api = casAdmin = {

		init: function() {
			this.addTickListener();
			this.addSelectAllListener();
			this.addHandleListener();
			this.addQuickSearchListener();
		},

		/**
		 * Set tickers if at least one checkbox is checked
		 */
		addTickListener: function() {
			$('.cas-rule-content :input').each( function() {
				api.toggleTick(this);
			});
			$('.cas-rule-content :input').change( function() {
				api.toggleTick(this);
			});
		},
		toggleTick: function(checkbox) {
			//Toggle on any selected checkbox
			$(checkbox).parents('.cas-rule-content').prev().toggleClass('cas-tick',$('#'+$(checkbox).parents('.cas-rule-content').attr('id')+' :input:checked').length > 0);
		},
		/**
		 * Toggle specific checkboxes depending on "show with all" checkbox
		 */
		addSelectAllListener: function() {
			$('.cas-rule-content .cas-chk-all').each( function() {
				api.toggleSelectAll(this);
			});
			$('.cas-rule-content .cas-chk-all').change( function() {
				api.toggleSelectAll(this);
			});
		},
		toggleSelectAll: function(checkbox) {
			var checkboxes = $(".cas-rule-content ."+$(checkbox).parents('.cas-rule-content').attr("id"));
			if($(checkbox).is(":checked")) {
				$(checkboxes).attr("disabled", true);   
			} else {
				$(checkboxes).removeAttr("disabled");  
			}
		},
		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 */
		addHandleListener: function() {
			var name = "select[name='handle']";
                
			// Execute on ready
			$(name).each(function(){
				api.toggleHostOption($(this));
			});
	                
			// Execute on change
			$(name).change(function(){
				api.toggleHostOption($(this));
			});
		},
		toggleHostOption: function(handle) {
			var name = "select[name='host']";
			if(handle.val() == 2) {
				$(name).hide();
				$(name).attr("disabled", true);
	                        
			} else {
				$(name).show();
				$(name).removeAttr("disabled");
			}
		},
		addQuickSearchListener: function() {
			var searchTimer;

			$('.cas-quick-search').keypress(function(e){
				var t = $(this);

				//13 is Enter
				if( 13 == e.which ) {
					api.updateQuickSearchResults( t );
					return false;
				}

				if( searchTimer ) clearTimeout(searchTimer);

				searchTimer = setTimeout(function(){
					api.updateQuickSearchResults( t );
				}, 400);
			}).attr('autocomplete','off');
		},
		updateQuickSearchResults : function(input) {
			var panel, params,
			minSearchLength = 2,
			q = input.val();

			if( q.length < minSearchLength ) return;

			panel = input.parents('.tabs-panel');
			params = {
				'action': 'cas_posts_quick_search',
				'response-format': 'markup',
				'menu': $('#menu').val(),
				'menu-settings-column-nonce': $('#menu-settings-column-nonce').val(),
				'q': q,
				'type': input.attr('name')
			};

			$('.spinner', panel).show();

			$.post( ajaxurl, params, function(response) {
				console.log(response);
				api.processQuickSearchQueryResponse(response, params, panel);
			});
		},
		/**
		 * Process the quick search response into a search result
		 *
		 * @param string resp The server response to the query.
		 * @param object req The request arguments.
		 * @param jQuery panel The tabs panel we're searching in.
		 */
		processQuickSearchQueryResponse : function(resp, req, panel) {
			/*var matched, newID,
			takenIDs = {},
			form = document.getElementById('nav-menu-meta'),
			pattern = new RegExp('menu-item\\[(\[^\\]\]*)', 'g'),
			$items = $('<div>').html(resp).find('li'),
			$item;

			if( ! $items.length ) {
				$('.categorychecklist', panel).html( '<li><p>' + navMenuL10n.noResultsFound + '</p></li>' );
				$('.spinner', panel).hide();
				return;
			}

			$items.each(function(){
				$item = $(this);

				// make a unique DB ID number
				matched = pattern.exec($item.html());

				if ( matched && matched[1] ) {
					newID = matched[1];
					while( form.elements['menu-item[' + newID + '][menu-item-type]'] || takenIDs[ newID ] ) {
						newID--;
					}

					takenIDs[newID] = true;
					if ( newID != matched[1] ) {
						$item.html( $item.html().replace(new RegExp(
							'menu-item\\[' + matched[1] + '\\]', 'g'),
							'menu-item[' + newID + ']'
						) );
					}
				}
			});

			$('.categorychecklist', panel).html( $items );*/
			$('.spinner', panel).hide();
		}
	}

	$(document).ready(function(){ casAdmin.init(); });

	var selItem = null;

		//NOTE:: when something is entered, look for it, select something, when Add is pressed, it should be put in ui and input cleared.
	$("#cas_add_url").click( function() {
		if(selItem != null) {
			console.log(selItem);
			$( ".cas-url" ).val('');
			selItem = null;
		}
	});

	$('input.cas-autocomplete').each(function(i, el) {
	    el = $(el);
	    el.autocomplete({
	        source: ajaxurl+"?action="+el.attr('id')+"&nonce="+$('#_ca-sidebar-nonce').val()+"&type="+el.attr('class'),
			minLength: 2,
			delay: 500,
			select: function(e, ui) {
				selItem = ui.item;
				console.log(selItem);
			}
	    });
	});

})(jQuery);
