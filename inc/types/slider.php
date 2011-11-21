<?php

require_once(dirname(__FILE__).'/type.php');

/**
 * A toggle type.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 */
class Origin_Type_Slider extends Origin_Type{
	function __construct($section, $name, $settings){
		if(empty($settings['segments'])){
			$settings['segments'] = $settings['max'] - $settings['min'];
		}
		parent::__construct($section, $name, $settings);
	}
	
	/**
	 * Enqueue any JS and CSS that we require
	 */
	static function enqueue(){
		wp_enqueue_script('origin.slider.color', ORIGIN_BASE_URL.'/js/types/slider.js', array('jquery'));
	}
	
	/**
	 * Render the form for the slider
	 */
	function render_form(){
		extract($this->settings);
		
		if(empty($segments)) $segments = round($max) - round($min);
		
		?>
		<input
			type="hidden"
			id="<?php esc_attr_e($this->form_id) ?>"
			name="<?php esc_attr_e($this->form_name) ?>"
			value="<?php print esc_attr_e($this->value()) ?>"
			
			data-segments="<?php print intval($segments) ?>"
			<?php if(!empty($units)) : ?>data-units="<?php print esc_attr_e($units) ?>"<?php endif ?>
			<?php if(!empty($unit_multiplier)) : ?>data-unit-multiplier="<?php print esc_attr_e($unit_multiplier) ?>"<?php endif ?>
			data-min="<?php esc_attr_e($min) ?>"
			data-max="<?php esc_attr_e($max) ?>"
			class="preview-field"
			data-name="<?php esc_attr_e($this->name) ?>" />
			
		<div id="<?php esc_attr_e($this->form_id.'-slider') ?>" class="jquery-ui-slider" for="<?php esc_attr_e($this->form_id) ?>"></div>
		<?php
	}
	
	function process_input($input){
		// WordPress doesn't seem to like storing booleans, so we use strings.
		if(!empty($this->settings['is_int'])) $this->value = intval($input[$this->form_name]);
		else $this->value = floatval($_POST[$this->form_name]);
		
	}
	
	function validate_settings(){
		return true;
	}
}