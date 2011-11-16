<?php

require_once(dirname(__FILE__).'/type.php');

/**
 * A select type.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 *
 * @todo Make this use chosen.js
 */
class Origin_Type_Select extends Origin_Type{
	/**
	 * Render the select input
	 */
	function render_form(){
		?>
		<select name="<?php esc_attr_e($this->form_name) ?>" id="<?php esc_attr_e($this->form_id) ?>" class="preview-field" data-name="<?php esc_attr_e($this->name) ?>">
			<?php foreach($this->settings['options'] as $value => $label) : ?>
				<option value="<?php esc_attr_e($value) ?>" <?php if($value == $this->value()) print 'selected="true"' ?>><?php print $label ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}
	
	/**
	 * Process the select input
	 */
	function process_input($input){
		if(isset($input[$this->form_name])){
			if(!in_array(
					$input[$this->form_name],
					array_keys($this->settings['options'])
				)){
				
				$this->error = __('Invalid option.', 'rocket');
				if(empty($this->value)){
					// If there isn't a value, then reset to default
					$this->value = $this->settings['default'];
				}
			}
			else{
				// The value is valid
				$this->value = $input[$this->form_name];
			}
		}
	}
	
	function validate_settings(){
		if(empty($this->settings['options'])) return false;
	}
}