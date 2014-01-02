/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	function GroupHandler() {
		this._currentIndex = 0;
		this._stack = [];
		this._activeClass = 'cas-group-active';
		
		this.push = function(obj) {
			this._stack.push(obj);
			this.setCurrentGroup(this._stack.length-1);
			//console.log("adding group. now has "+this._stack.length+" groups.");
		};
		this.remove = function(obj) {
			var index = obj.index();
				
			this._stack.splice(index,1);
			obj.remove();
			//console.log("removing group. now has "+this._stack.length+" groups.");

			if(index == this._currentIndex) {
					//console.log("removing current index");
					//If we are the first element, set current to next,
					//otherwise set current to prev
					this.setCurrentGroup((index == 0) ? 0 : --index);
				} else if(index < this._currentIndex) {
					//if we are removing an element before current,
					//update current silently
					this._currentIndex--;
					//console.log("current group now " + this._currentIndex);
				}

		};
		this.getArray = function() {
			return this._stack;
		};
		this.setArray = function(array) {
			this._stack = array;
		};
		this.setCurrentGroup = function(index) {
			if(index < this._stack.length) {
				this.resetCurrentGroup();
				$('.js-cas-condition-add').attr('disabled',false);
				this._currentIndex = index;
				this.getCurrentGroup().addClass(this._activeClass);
				$("input:checkbox",this.getCurrentGroup()).attr('disabled',false).attr('checked',true);
				//console.log("current group now "+this._currentIndex);
			}
		};
		this.resetCurrentGroup = function() {
			if(this._currentIndex != null && this._currentIndex < this._stack.length) {

				$("li.cas-new",this.getCurrentGroup()).remove();

				$(".cas-condition",this.getCurrentGroup()).each( function() {
					if($(this).find('input').length == 0) {
						$(this).remove();
					}
				});
				
				$("input:checkbox",this.getCurrentGroup()).attr('disabled',true);
				this.getCurrentGroup().removeClass(this._activeClass);
			}
			$('.js-cas-condition-add').attr('disabled',true);
			this._currentIndex = null;
		}
		this.getCurrentGroup = function() {
			if(this._currentIndex != null && this._currentIndex < this._stack.length) {
				return $(this._stack[this._currentIndex]);
			}
			return null;
		}
	}

	var cas_admin = {

		groups:new GroupHandler(),
		nonce: $('#_ca-sidebar-nonce').val(),
		sidebarID: $('#current_sidebar').val(),
		message: $('<div></div>'),

		init: function() {

			$('.cas-contentlist').on('click','.page-numbers', function(e) {
				e.preventDefault();

				var link = $(this);
				var action = link.closest('.cas-rule-content');

				$.ajax({
					url: ajaxurl,
					data:link.attr('href').split('?')[1]+'&action=cas-module-'+action.attr('data-cas-module'),
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
						link.closest('.cas-contentlist').html(data);						
					},
					error: function(xhr, desc, e) {
						console.log(xhr.responseText);
					}
				});			

			});

			cas_admin.groups.setArray($("#cas-groups .cas-group-single"));	

			this.addTabListener();	

			//this.addCheckboxListener();
			this.addHandleListener();
			this.addSearchListener();

			this.addNewGroupListener();
			this.addSetGroupListener();

			this.addAddContentListener();


		},

		/**
		 * Listen to and handle adding
		 * content to current group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addAddContentListener: function() {
			$("#cas-accordion").on("click",".js-cas-condition-add", function(e) {

				e.preventDefault();

				if(cas_admin.groups.getCurrentGroup() != null) {

					var button = $(this);

					var old_checkboxes = $("input:checkbox:checked", button.closest('.cas-rule-content'));
					var condition_elem = $('.cas-condition-'+button.attr('data-cas-condition'), cas_admin.groups.getCurrentGroup());
					var data = [];

					if(condition_elem.length == 0) {
						condition_elem = $('<div class="cas-condition cas-condition-'+button.attr('data-cas-condition')+'"><strong>'+button.closest('.accordion-section').find('.accordion-section-title').text()+'</strong><ul></ul></div>');
						cas_admin.groups.getCurrentGroup().find('.cas-content').append(condition_elem);
					}
					
					//Check if checkbox with value already exists
					old_checkboxes.each( function() {
						var elem = $(this);
						if(condition_elem.find("input[value='"+elem.val()+"']").length == 0) {
							var temp = elem.closest('li').clone().addClass('cas-new');
							//jQuery 1.7 fix
							data.push(temp[0]);
						}
					});
					old_checkboxes.attr('checked',false);
					
					$('ul',condition_elem).append(data);					
				}
				
			});
		},

		/**
		 * Listen to and handle Add New Group clicks
		 * Uses AJAX to create a new group
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addNewGroupListener: function() {
			var groupContainer = $('#cas-groups');
			groupContainer.on('click', '.js-cas-group-new', function(e) {

				e.preventDefault();

				var input = $('input', groupContainer);
				
				input.attr('disabled',true);
				cas_admin.message.removeClass().html();
				cas_admin.message.remove();

				$.ajax({
					url: ajaxurl,
					data:{
						action: 'cas_add_group',
						token: cas_admin.nonce,
						current_id: cas_admin.sidebarID
					},
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
						cas_admin.message.addClass('success').text(data.message);
						$('.cas-groups-body',groupContainer).prepend(cas_admin.message);

						var group = $('<li>', {class: 'cas-group-single', html: '<span class="cas-group-control cas-group-control-active">'+
							'<input type="button" class="button button-primary js-cas-group-save" value="'+CASAdmin.save+'" /> | <a class="js-cas-group-cancel" href="#">'+CASAdmin.cancel+'</a>'+
							'</span>'+
							'<span class="cas-group-control">'+
							'<input type="button" class="js-cas-group-edit button" value="'+CASAdmin.edit+'" /> | <a class="submitdelete trash js-cas-group-remove" href="#">'+CASAdmin.remove+'</a>'+
							'</span>'+
							'<div class="cas-content"></div>'+
							'<input type="hidden" class="cas_group_id" value="'+data['group']+'" name="cas_group_id" />'});

						$('ul', groupContainer).first().append(group);
						cas_admin.groups.push(group[0]); //object vs node reference?

						input.attr('disabled',false);
						
					},
					error: function(xhr, desc, e) {
						cas_admin.message.addClass('error').text(xhr.responseText);
						$('.cas-groups-body',groupContainer).prepend(cas_admin.message);
						input.attr('disabled',false);
					}
				});
			});
		},

		/**
		 * Listen to and manage
		 * group saving, editing, removal and cancelling
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addSetGroupListener: function() {
			var groupContainer = $("#cas-groups");
			groupContainer.on("click", ".js-cas-group-save", function(e){
				e.preventDefault();

				cas_admin.message.removeClass().html();
				cas_admin.message.remove();

				var data = cas_admin.groups.getCurrentGroup().find("input").serializeArray();
				data.push({name:"action",value:"cas_add_rule"});
				data.push({name:"token",value:cas_admin.nonce});
				data.push({name:"current_id",value:cas_admin.sidebarID});

				$.ajax({
					url: ajaxurl,
					data:$.param(data),
					dataType: 'JSON',
					type: 'POST',
					success:function(data){
						cas_admin.message.addClass('success').text(data.message);
						$('.cas-groups-body',groupContainer).prepend(cas_admin.message);

						var content = $("input:checkbox",cas_admin.groups.getCurrentGroup()).closest('li');
						if(content.length > 0) {
							$("input:checkbox:not(:checked)",cas_admin.groups.getCurrentGroup()).closest('li').remove();
							content.removeClass('cas-new');
						}

						$(".cas-condition",cas_admin.groups.getCurrentGroup()).each( function() {
							if($(this).find('input').length == 0) {
								$(this).remove();
							}
						});
						
					},
					error: function(xhr, desc, e) {
						cas_admin.message.addClass('error').text(xhr.responseText);
						$('.cas-groups-body',groupContainer).prepend(cas_admin.message);
					}
				});		
			});
			groupContainer.on("click", ".js-cas-group-cancel", function(e){	
				e.preventDefault();
				cas_admin.groups.resetCurrentGroup();
			});
			groupContainer.on("click", ".js-cas-group-edit", function(e){
				e.preventDefault();
				cas_admin.groups.setCurrentGroup($(this).parents('.cas-group-single').index());
			});
			groupContainer.on("click", ".js-cas-group-remove", function(e){
				e.preventDefault();

				if(confirm(CASAdmin.confirmRemove) == true) {

					if(cas_admin.groups.getArray().length > 1) {

						cas_admin.message.removeClass().html();
						cas_admin.message.remove();

						var button = $(this);
						button.attr('disabled',true);
						var group = $(this).closest('.cas-group-single');
						group.css('background','red');
						$.ajax({
							url: ajaxurl,
							data:{
								action: 'cas_remove_group',
								token: cas_admin.nonce,
								cas_group_id: group.find('.cas_group_id').val(),
								current_id: cas_admin.sidebarID
							},
							dataType: 'JSON',
							type: 'POST',
							success:function(data){
								cas_admin.message.addClass('success').text(data.message);
								$('.cas-groups-body',groupContainer).prepend(cas_admin.message);

								group.fadeOut('slow', function() { 
									cas_admin.groups.remove($(this)); 
								})

								button.attr('disabled',false);
							},
							error: function(xhr, desc, e) {
								cas_admin.message.addClass('error').text(xhr.responseText);
								$('.cas-groups-body',groupContainer).prepend(cas_admin.message);
								button.attr('disabled',false);
							}
						});	
					}
				}	
			});
		},

		/**
		 * Listen to and manage tab clicks
		 * Based on code from WordPress Core
		 * @author Joachim Jensen <jv@intox.dk>
		 * @since  2.0
		 */
		addTabListener: function() {
			var class_active = 'tabs-panel-active',
			class_inactive = 'tabs-panel-inactive';

			$("#cas-accordion .accordion-section").first().addClass('open');

			$('.nav-tab-link').on('click', function(e) {
				e.preventDefault();

				panelId = $(this).data( 'type' );

				wrapper = $(this).closest('.accordion-section-content');

				// upon changing tabs, we want to uncheck all checkboxes
				$('input', wrapper).removeAttr('checked');

				//Change active tab panel
				$('.' + class_active, wrapper).removeClass(class_active).addClass(class_inactive);
				$('#' + panelId, wrapper).removeClass(class_inactive).addClass(class_active);

				$('.tabs', wrapper).removeClass('tabs');
				$(this).parent().addClass('tabs');

				// select the search bar
				$('.quick-search', wrapper).focus();

					
			});
		},
		
		/**
		 * Set tickers if at least one checkbox is checked
		 */
		addCheckboxListener: function() {
			$('.cas-rule-content :input').change( function() {
				var parent = $(this).parents('.cas-rule-content'); 
				cas_admin.toggleTick(parent);
				if($(this).attr('class') == 'cas-chk-all')
					cas_admin.toggleSelectAll(this, parent);
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
			var checkboxes = parent.find("input[type=checkbox]").not(checkbox);
			checkboxes.attr("disabled", $(checkbox).is(":checked"));
		},
		/**
		 * The value of Handle selection will control the
		 * accessibility of the host sidebar selection
		 * If Handling is manual, selection of host sidebar will be disabled
		 */
		addHandleListener: function() {
			$("select[name='handle']").change(function(){
				cas_admin.toggleHostOption($(this));
			}).change(); //fire change event on page load
		},
		toggleHostOption: function(handle) {
			var host = $("select[name='host']");
			host.attr("disabled", handle.val() == 2);
			if(handle.val() == 2)
				host.hide();
			else
				host.show();	
		},
		/**
		 * Use AJAX to search for content from a specific module
		 */
		addSearchListener: function() {
			var searchTimer;

			$('.cas-autocomplete').keypress(function(e){
				var t = $(this);

				//If Enter (13) is pressed, search immediately
				if( 13 == e.which ) {
					cas_admin.updateSearchResults( t );
					return false;
				}

				if( searchTimer ) clearTimeout(searchTimer);

				searchTimer = setTimeout(function(){
					cas_admin.updateSearchResults( t );
				}, 400);
			}).attr('autocomplete','off');

		},
		updateSearchResults: function(input) {
			var panel,
			minSearchLength = 2,
			q = input.val();

			if( q.length < minSearchLength ) return;

			panel = input.parents('.tabs-panel');
			var spinner = $('.spinner', panel);

			spinner.show();

			$.ajax({
				url: ajaxurl,
				data:{
					'action': input.attr('class').split(' ')[0],
					'response-format': 'JSON',
					'nonce': cas_admin.nonce,
					'sidebar_id': cas_admin.sidebarID,
					'type': input.attr('id'),
					'q': q
				},
				dataType: 'JSON',
				type: 'POST',
				success:function(response){
					var elements = "";
					if(response.length > 0) {
						$.each(response, function(i,item) {
							elements += '<li><label class="selectit"><input value="'+item.value+'" type="checkbox" name="'+item.name+'[]"/> '+item.label+'</label></li>';	
						});
					} else {
						elements = '<li><p>'+CASAdmin.noResults+'</p></li>';
					}
					
					$('.categorychecklist', panel).html(elements);
					spinner.hide();
				},
				error: function(xhr, desc, e) {
					console.log(xhr.responseText);
				}
			});

		},
	}

	$(document).ready(function(){ cas_admin.init(); });

})(jQuery);
