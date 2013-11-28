/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	$( "#cas-accordion" ).accordion({
		header: 'h4',
		autoHeight: false,
		collapsible: true,
		heightStyle: 'content'
	});

	var api = {
		
		init: function() {
			this.addCheckboxListener();
			this.addHandleListener();
			this.addSearchListener();
		},

		/**
		 * Set tickers if at least one checkbox is checked
		 */
		addCheckboxListener: function() {
			$('.cas-rule-content :input').change( function() {
				var parent = $(this).parents('.cas-rule-content'); 
				api.toggleTick(parent);
				if($(this).attr('class') == 'cas-chk-all')
					api.toggleSelectAll(this, parent);
			}).change(); //fire change event on page load
		},
		toggleTick: function(parent) {
			//Toggle on any selected checkbox
			parent.prev().toggleClass('cas-tick',parent.find('input:checked').length > 0);
		},
		/**
		 * Toggle specific input depending on "show with all" checkbox
		 */
		toggleSelectAll: function(checkbox, parent) {
			var checkboxes = parent.find("input").not(checkbox);
			checkboxes.attr("disabled", $(checkbox).is(":checked"));
		},
		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 */
		addHandleListener: function() {
			$("select[name='handle']").change(function(){
				api.toggleHostOption($(this));
			}).change(); //fire change event on page load
		},
		toggleHostOption: function(handle) {
			var name = "select[name='host']";
			$(name).attr("disabled", handle.val() == 2);
			if(handle.val() == 2)
				$(name).hide();
			else
				$(name).show();	
		},
		/**
		 * Use AJAX to search for content from a specific module
		 */
		addSearchListener: function() {
			$('input.cas-autocomplete').each(function(i, el) {
			    $(el).keypress(function(e){
					//If Enter (13) is pressed, disregard
					if( 13 == e.which ) {
						return false;
					}
				}).autocomplete({
			        source: ajaxurl+"?action="+$(el).attr('class').split(' ')[0]+"&nonce="+$('#_ca-sidebar-nonce').val()+"&type="+$(el).attr('id'),
					minLength: 2,
					delay: 500,
					select: function(e,ui) {
						api.clickToAddSearchResult(e,ui);
					}
			    });
			});

		},
		clickToAddSearchResult: function(e, ui) {
			// Check if data is found
			if(ui.item != null) {
				// Check if element already exists
				if($("#"+ui.item.elem).length == 0) {

					var elem = $('<li id="'+ui.item.elem+'"><label class="selectit"><input class="cas-'+ui.item.module+'-'+ui.item.id+' cas-'+ui.item.id2+'" value="'+ui.item.id+'" type="checkbox" name="'+ui.item.name+'[]" checked="checked" /> '+ui.item.label+'</label></li>').change( function() {
						var parent = $(this).parents('.cas-rule-content'); 
						api.toggleTick(parent);
					});

					//Add element and clean up
					$("#cas-list-"+ui.item.id2).prepend(elem);
					elem.change(); // fire change event
					
				} else {
					//Move to top and check it
					$("#"+ui.item.elem).prependTo("#cas-list-"+ui.item.id2).find('input').attr("checked", true).change();
				}

				$( "input#cas-autocomplete-"+ui.item.id2 ).val('');
				e.preventDefault(); //clear field properly
			}
		}
	}

	$(document).ready(function(){ api.init(); });

})(jQuery);
