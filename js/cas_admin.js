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
			this.addTickListener();
			this.addSelectAllListener();
			this.addHandleListener();
			this.addSearchListener();
		},

		/**
		 * Set tickers if at least one checkbox is checked
		 */
		addTickListener: function() {
			$('.cas-rule-content :input').each( function() {
				api.toggleTick(this);
			}).change( function() {
				api.toggleTick(this);
			});
		},
		toggleTick: function(checkbox) {
			//Toggle on any selected checkbox
			var parent = $(checkbox).parents('.cas-rule-content');
			parent.prev().toggleClass('cas-tick',parent.find('input:checked').length > 0);
		},
		/**
		 * Toggle specific checkboxes depending on "show with all" checkbox
		 */
		addSelectAllListener: function() {
			$('.cas-rule-content .cas-chk-all').each( function() {
				api.toggleSelectAll(this);
			}).change( function() {
				api.toggleSelectAll(this);
			});
		},
		toggleSelectAll: function(checkbox) {
			var parent = $(checkbox).parents('.cas-rule-content');
			var checkboxes = $(".cas-rule-content ."+parent.attr("id"));
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
			$("select[name='handle']").each(function(){
				api.toggleHostOption($(this));
			}).change(function(){
				api.toggleHostOption($(this));
			});
		},
		toggleHostOption: function(handle) {
			var name = "select[name='host']";
			if(handle.val() == 2) {
				$(name).hide().attr("disabled", true);
			} else {
				$(name).show().removeAttr("disabled");
			}
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
			// Check if an element is found
			if(ui.item != null) {
				$("input#cas-add-"+ui.item.id2).click( function() {
					// Check if element already exists
					if($("#"+ui.item.elem).length == 0) {

						var elem = $('<li id="'+ui.item.elem+'"><label class="selectit"><input class="cas-'+ui.item.module+'-'+ui.item.id+' cas-'+ui.item.id2+'" value="'+ui.item.id+'" type="checkbox" name="'+ui.item.name+'[]" /> '+ui.item.label+'</label>').change( function() {
							api.toggleTick(this);
						});

						//Add element and clean up
						$("#cas-list-"+ui.item.id2).append(elem);
						$( "input#cas-autocomplete-"+ui.item.id2 ).val('');
						$(this).unbind('click');
					}
				});
			}
		}
	}

	$(document).ready(function(){ api.init(); });

})(jQuery);
