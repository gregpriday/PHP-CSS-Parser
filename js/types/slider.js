jQuery(function($){
	// The sliders
	$('fieldset.type-slider .jquery-ui-slider').each(function(){
		var $$ = $(this);
		var formEl = $('#'+$$.attr('for'));
		
		var valInd = undefined;
		
		var handleChange = function(val){
			var range = Number(formEl.attr('data-max')) - Number(formEl.attr('data-min'));
			var formVal = ( val / Number(formEl.attr('data-segments')) * range ) + Number(formEl.attr('data-min'));
			formVal = Math.round(formVal*1000)/1000;
			
			
			// Set the value with 3 decimal points
			formEl.val(formVal);
			
			var indicatorText = Number(formVal);
			if(formEl.attr('data-unit-multiplier') != undefined) indicatorText *= Number(formEl.attr('data-unit-multiplier'));
			indicatorText = Math.round(indicatorText);
			if(formEl.attr('data-units') != undefined) indicatorText = indicatorText + ' ' + formEl.attr('data-units');
			
			valInd.find('.val span').html(indicatorText);
			valInd.css({'margin-left' : - Math.round(valInd.outerWidth()/2) + 1});
			
			// Update the preview to include the value
			previewUpdate();
		}
		
		$$.slider({
			max : formEl.attr('data-segments'),
			min : 0,
			value : Math.round(Number(formEl.val()) / Number(formEl.attr('data-max')) * Number(formEl.attr('data-segments')) ),
			step : 1,
			start : function(event, ui){
				if(valInd == undefined){
					$('<div class="rel"></div>').appendTo($$.find('.ui-slider-handle'));
					valInd = $('<div class="value-indicator"><div class="val"><span></span><div class="pointer"></div></div></div>').appendTo($$.find('.ui-slider-handle .rel')).hide();
					valInd.css({'opacity' : 0.9});
					// Update the value in the indicator
					handleChange(Number(ui.value));
				}
				valInd.fadeIn();
			},
			stop : function(){
				valInd.fadeOut();
			},
			slide: function(event, ui) {
				handleChange(Number(ui.value));
			}
		})
	});
});