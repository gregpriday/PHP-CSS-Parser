<?php

require_once(dirname(__FILE__).'/type.php');

/**
 * A select type.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 */
class Origin_Type_Textarea extends Origin_Type{
	
	/**
	 * Enqueue any JS and CSS that we require
	 */
	static function enqueue(){
		// The color picker
		wp_enqueue_script('origin.type.text', ORIGIN_BASE_URL.'/js/types/text.js', array('jquery'));
	}
	
	/**
	 * Render the select input
	 */
	function render_form(){
		?><textarea
			rows="<?php print $this->get_setting('rows', 6) ?>"
			cols="<?php print $this->get_setting('cols', 65) ?>"
			name="<?php esc_attr_e($this->form_name) ?>"
			id="<?php esc_attr_e($this->form_id) ?>"
			class="preview-field"
			data-name="<?php esc_attr_e($this->name) ?>"><?php esc_attr_e($this->value()) ?></textarea><?php
	}
	
	/**
	 * Process the select input
	 */
	function process_input($input){
		$this->value = @$input[$this->form_name];
		
		if(get_magic_quotes_gpc()){
			$this->value = stripslashes($this->value);
		}
	}
	
	function validate_settings(){}
}