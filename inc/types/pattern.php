<?php

require_once(dirname(__FILE__).'/select.php');

class Origin_Type_Pattern extends Origin_Type_Select{
	function __construct($section, $name, $settings){
		parent::__construct($section, $name, $settings);
		
		// Load the pattern options
		$this->settings['options'] = array();
		
		// TODO check if none is enabled
		$this->settings['options']['::none'] = false;
		
		$dir = opendir($this->settings['folder']);
		while (($file = readdir($dir)) !== false) {
			if(substr($file,-3,3) == 'png'){
				$name = substr($file, 0, strlen($file)-4);
				$this->settings['options'][$name] = array(
					'name' => ucwords(str_replace('_', ' ', $name)),
					'filename' => $file,
				);
			}
		}
		
		asort($this->settings['options']);
	}
	
	/**
	 * Enqueue any JS and CSS that we require
	 */
	static function enqueue(){
		wp_enqueue_script('jquery.chosen', ORIGIN_BASE_URL.'/externals/chosen/chosen.jquery.js', array('jquery'));
		wp_enqueue_style('jquery.chosen', ORIGIN_BASE_URL.'/externals/chosen/chosen.css');
		wp_enqueue_script('origin.pattern.color', ORIGIN_BASE_URL.'/js/types/pattern.js', array('jquery'));
	}
	
	function render_form(){
		$value = $this->value;
		
		?>
		<div class="container">
			<div class="current"></div>
		</div>
		<div class="clear"></div>
		
		<select name="<?php esc_attr_e($this->form_name) ?>" class="current preview-field" data-name="<?php esc_attr_e($this->name) ?>" id="<?php esc_attr_e($this->form_id) ?>" value="<?php esc_attr_e($this->value()) ?>" style="width:350px">
			<?php if($this->settings['none']) : ?>
				<option value="::none" <?php selected($value, '::none') ?>>None</option>
			<?php endif ?>
			
			<?php foreach($this->settings['options'] as $option_id => $option) : if($option_id != '::none') : ?>
				<option value="<?php print $option_id ?>" data-image="<?php print $this->settings['folder_url'].'/'.$option['filename'] ?>" <?php selected($value, $option_id) ?> ><?php print $option['name']; ?></option>
			<?php endif; endforeach; ?>
		</select>
		<?php
	}
}