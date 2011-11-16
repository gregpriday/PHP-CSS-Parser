jQuery(function($){
	// The image selector
	$('.image_select .option')
		.css('display', 'block')
		.each(function(i){
			var $$ = $(this);
			if(!$$.parent().find('.current option').eq(i).is(':selected')){
				$$.css('opacity', 0.4);
			}
			
		})
		.click(function(){
			$$ = $(this);
			$('.image_select .option').css('opacity', 0.4);
			$$.css('opacity', 1);
			$$.parent().find('.current option').eq($$.index()-1).attr('selected', 'selected');
		});
});