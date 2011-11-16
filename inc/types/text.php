<?php

require_once(dirname(__FILE__).'/type.php');

/**
 * A select type.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 */
class Origin_Type_Text extends Origin_Type{
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
		?><input type="text" size="<?php print $this->get_setting('width', 65) ?>" name="<?php esc_attr_e($this->form_name) ?>" id="<?php esc_attr_e($this->form_id) ?>" class="big-text preview-field"  data-name="<?php esc_attr_e($this->name) ?>" value="<?php esc_attr_e($this->value()) ?>" /><?php
	}
	
	/**
	 * Process the select input
	 */
	function process_input($input){
		$this->value = @$input[$this->form_name];
	}
	
	function validate_settings(){}
}