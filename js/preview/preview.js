/**
 * Handles previewing a design
 */

var previewPopup;

jQuery(function($){
	
	// Create the preview container (which is a jQuery UI Dialog)
	$('<div id="preview-container"><iframe id="preview-iframe" /></div>')
		.dialog({
			'title' : 'Design Preview',
			'width' : 1200,
			'height' : 600,
			'autoOpen' : false,
			'dialogClass': 'theme-preview',
			'open' : function(){
				previewUpdate();
			},
			resizeStart : function(){
				$('#preview-iframe').hide();
			},
			resizeStop : function(){
				$('#preview-iframe').fadeIn('fast');
			},
			dragStart : function(){
				$('#preview-iframe').hide();
			},
			dragStop : function(){
				$('#preview-iframe').fadeIn('fast');
			}
		});
	
	
	// Set up the preview iFrame
	$('#preview-iframe')
		.attr('src', $('#origin-buttons .preview button:first').attr('data-href'))
		.css({
			'width' : '100%',
			'height' : '100%',
			'margin' : 0
		}).load(function(){
			// Remove the admin bar and any inline styles (probably associated with the admin bar)
			$(this).contents().find('#wpadminbar').remove();
			$(this).contents().find('head style').remove();
			$(this).contents().find('body').removeClass('admin-bar');
			previewUpdate();
		}) ;
	
	$('#origin-buttons .preview button:first')
		.button()
		.click(function(e){
			$('#preview-container').dialog('open');
			e.stopPropagation();
			return false;
		})
		.parent().buttonset();
		
	$('#origin-buttons .save input')
		.button();
});

/**
 * This function should be called every time a value is changed
 */
var previewUpdate = function(){
	var $ = jQuery;
	
	// If there are no head in the iframe, then we know something is wrong
	if($("#preview-iframe").contents().find("head") == undefined) return;
	
	// To start, lets generate some new CSS shall we.
	var input = {};
	$('#origin-page .preview-field').each(function(){
		var $$ = $(this);
		var name = $$.attr('name').split(':');
		if(input[name[0]] == undefined) input[name[0]] = {};
		input[name[0]][name[1]] = $$.val();
	});
	
	var css = originGenerateCss(input, originExecutor);
	var $head;
	
	// Remove the old stylesheet, if it's there
	$head = $("#preview-iframe").contents().find("head");
	$head.find('#origin-css').remove();
	$head.append($('<style />', { id: "origin-css", rel: "stylesheet", type: "text/css" }).html(css));
	
	// Remove the admin menu and any inline styles
	var iframe = $("#preview-iframe").contents();
}