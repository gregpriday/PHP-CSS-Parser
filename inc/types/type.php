<?php

/**
 * An Origin field type
 */
abstract class Origin_Type{
	/**
	 * @var The section this field came from
	 */
	protected $section;
	
	/**
	 * @var The name of this field
	 */
	protected $name;
	
	/**
	 * @var The name of this type as it should appear in a form.
	 */
	protected $form_name;
	
	/**
	 * @var The ID of this type as it should appear in a form.
	 */
	protected $form_id;
	
	/**
	 * @var The current value
	 */
	protected $value;
	
	/**
	 * An error message
	 */
	public $error;
	
	/**
	 * @var The settings for this type
	 */
	public $settings;
	
	function __construct($section, $name, $settings){
		$this->section = $section;
		$this->name = $name;
		$this->form_name = $section.':'.$name;
		$this->form_id = 'field-'.$section.'-'.$name;
		
		if(isset($settings['default'])) $this->value = $settings['default'];
		
		$this->settings = $settings;
		$this->validate_settings();
	}
	
	/**
	 * Gets a setting if it's available, or returns a default.
	 */
	function get_setting($name, $default = false){
		return isset($this->settings[$name]) ? $this->settings[$name] : $default;
	}
	
	function value(){
		return isset($this->value) ? $this->value : $this->settings['default'];
	}
	
	function set_value($value){
		$this->value = $value;
	}
	
	/**
	 * Validate the settings
	 */
	abstract function validate_settings();
	
	/**
	 * Renders the form element for this type.
	 */
	abstract function render_form();
	
	/**
	 * Process the input.
	 *
	 * @param array $input Input from either a form or file.
	 */
	abstract function process_input($input);
}