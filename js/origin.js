/**
 * Code for the Origin admin page.
 */
jQuery(function($) {
	$('#filters select').each(function(){
		var $$ = $(this);
		$$.chosen({
			allow_single_deselect : true,
			disable_search_threshold : 8
		});
	});
	
	$('#filters select').change(function(){filterSettings()});
	
	// Execute the filters
	var filterSettings = function(){
		$('fieldset.origin').each(function(){
			var $$ = $(this);
			var parent = $$.closest('tr');
			var show = true;
			
			if($('#section-filter').val() != '' && $$.attr('data-section') != $('#section-filter').val())
				show = false;
			
			if($('#tag-filter').val() != '' && (' , ' + $$.attr('data-tags') + ' , ').indexOf(' , ' + $('#tag-filter').val() + ' , ') == -1)
				show = false;
			
			if($('#selector-filter').val() != '' && (' , ' + $$.attr('data-selectors')).indexOf(' , ' + $('#selector-filter').val()) == -1)
				show = false;
				
			if(!show){
				$$.closest('tr').hide();
				return;
			}
			
			parent.show();
			
			if($('#selector-filter').val() != ''){
				var selectors = $$.attr('data-selectors').split(' , ');
				var activeSelectors = [];
				
				for(var i = 0; i < selectors.length; i++){
					if(selectors[i].indexOf($('#selector-filter').val()) == 0){
						activeSelectors.push(selectors[i]);
					}
				}
				
				parent.find('.info').html(activeSelectors.join(', '));
			}
			else{
				parent.find('.info').html($$.attr('data-section'));
			}
		});
	};
	filterSettings();
});