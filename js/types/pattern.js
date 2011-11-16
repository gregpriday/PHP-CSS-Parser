jQuery(function($){
	$('fieldset.type-pattern select').chosen({})
		.change(function(){
			var $$ = $(this);
			var option = $$.find('option[value="'+$$.val()+'"]');
			
			if(option.attr('data-image') != undefined){
				$$.parent().find('.current').css({
					'background-image' : 'url('+option.attr('data-image')+')'
				});
			}
			else{
				$$.parent().find('.current').css({
					'background-image' : 'none'
				});
			}
			
			// Update the preview to include the new texture
			previewUpdate();
		})
		.trigger('change');
});