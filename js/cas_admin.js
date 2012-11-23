/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

jQuery( "#cas-accordion" ).accordion({
	header: 'h4',
	autoHeight: false,
	collapsible: true,
	heightStyle: 'content'
});

jQuery(document).ready(function($) {
        
	handleSidebarHandle();
        
	/**
	 *
	 * Set tickers if at least one checkbox is checked
	 *
	 */
	$('.cas-rule-content :input').each( function() {
		toggleTick(this);
	});
	$('.cas-rule-content :input').change( function() {
		toggleTick(this);
	});
	
	/**
	 *
	 * Toggle specific checkboxes depending on "show with all" checkbox
	 *
	 */
	$('.cas-rule-content .cas-chk-all').each( function() {
		toggleAllSpecific(this);
	});
	$('.cas-rule-content .cas-chk-all').change( function() {
		toggleAllSpecific(this);
	});
	
	function toggleTick(checkbox) {
		$(checkbox).parents('.cas-rule-content').prev().toggleClass('cas-tick',$('#'+$(checkbox).parents('.cas-rule-content').attr('id')+' :input:checked').length > 0);
	}
	
	function toggleAllSpecific(checkbox) {
		var checkboxes = $(".cas-rule-content ."+$(checkbox).parents('.cas-rule-content').attr("id"));
		if($(checkbox).is(":checked")) {
			$(checkboxes).attr("disabled", true);   
		} else {
			$(checkboxes).removeAttr("disabled");  
		}
	}
        
	/**
	 *
	 * Handle the Handle selection
	 *
	 */
	function handleSidebarHandle() {
                
		var name = "select[name='handle']";
                
		// Execute on ready
		$(name).each(function(){
			endisableHostSidebars($(this));
		});
                
		// Execute on change
		$(name).change(function(){
			endisableHostSidebars($(this));
		});
	}
        
	/**
	 * The value of Handle selection will control the
	 * accessibility of the host sidebar selection
	 * If Handling is manual, selection of host sidebar will be disabled
	 */
	function endisableHostSidebars(select) {
		var name = "select[name='host']";
		if(select.val() == 2) {
			$(name).hide();
			$(name).attr("disabled", true);
                        
		} else {
			$(name).show();
			$(name).removeAttr("disabled");
		}
	}
        
});