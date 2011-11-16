jQuery(function($){
	$('fieldset.type-color').each(function(){
		var $$ = $(this);
		$$.find('.current').ColorPicker({
			'color' : $$.find('input').val(),
			onChange: function (hsb, hex, rgb) {
				$$.find('.current').css('background-color', '#' + hex);
				$$.find('input').val('#' + hex);
				
				previewUpdate() // Update the preview
			}
		});
	});
});