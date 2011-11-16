jQuery(function($){
	$('fieldset input[type=text]').add('fieldset textarea').keyup(function(){
		previewUpdate();
	});
});