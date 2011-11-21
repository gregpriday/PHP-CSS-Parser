<?php

/**
 * Handles Origin values and settings
 */
class Origin_Settings {
	
	private $_objects;
	
	private $_settings;
	
	private $_values;
	
	function __construct(){
		// Load the current design values
		$this->_values = get_option('origin_'.Origin::single()->theme_name.'_values', array());
	}
	
	function __destruct(){
		
	}
	
	/**
	 * Load all the design files.
	 */
	function load_files(){
		// Load Design files from the parent and child templates
		$files = glob(get_template_directory().'/origin/*.php');
		foreach($files as $file) include($file);
		
		$this->load_defaults();
		
		// Load the image files
		$files = glob(get_template_directory().'/origin/image/*.php');
		foreach($files as $file) include($file);
	}
	
	/**
	 * Get a value
	 *
	 * @param string $section The section name.
	 * @param string $name The setting name.
	 */
	function get_value($section, $name){
		return $this->_values[$section][$name];
	}
	
	/**
	 * @return array A copy of the values array.
	 */
	function get_values(){
		return $this->_values;
	}
	
	/**
	 * @return array A copy of the settings array.
	 */
	function get_settings(){
		return $this->_settings;
	}
	
	/**
	 * Get all the values that are considered design sense values.
	 *
	 * @return array()
	 */
	function get_sense_values(){
		$values = array();
		foreach($this->_settings as $section_id => $section){
			foreach($section['settings'] as $field_id => $field){
				if(!empty($field['sense'])) $values[$section_id][$field_id] = $this->_values[$section_id][$field_id];
			}
		}
		
		return $values;
	}
	
	/**
	 * Gets an object for a setting
	 * 
	 * @param string $section Section name.
	 * @param string $setting Setting name.
	 * 
	 * @return Origin_Type The class setting.
	 */
	function get_object($section, $setting){
		if(empty($this->_objects[$section][$setting])){
			// Instantiate the settings class
			$type = $this->_settings[$section]['settings'][$setting]['type'];
			$class = 'Origin_Type_'.ucfirst(strtolower($type));
			if(!class_exists($class)) require_once(dirname(__FILE__).'/types/'.$type.'.php');
			
			$this->_objects[$section][$setting] = new $class($section, $setting, $this->_settings[$section]['settings'][$setting]);
			
			// Initialize the object
			if(isset($this->_values[$section][$setting]))
				$this->_objects[$section][$setting]->set_value($this->_values[$section][$setting]);
		}
		
		return $this->_objects[$section][$setting];
	}
	
	/**
	 * Set a value.
	 *
	 * @param string $section The section name.
	 * @param string $name The setting name.
	 * @param mixed $value The new value.
	 */
	function set($section, $name, $value){
		$this->_values[$section][$name] = $value;
	}
	
	/**
	 * Saves Origin's state based on the POST variables. Also creates the CSS file.
	 */
	function save($input = null){
		if($input == null) $input = $_POST;
		
		// Process input to get the new values
		foreach($this->_settings as $section_id => $section){
			foreach($section['settings'] as $field_id => $field){
				$object = $this->get_object($section_id, $field_id);
				
				// Process input is what takes form values and processes them
				$object->process_input($input);
				$this->_values[$section_id][$field_id] = $object->value();
			}
		}
		
		// Create the folder if it doesn't already exist
		$filename = Origin::single()->css->get_css_path();;
		
		// Save the CSS
		file_put_contents($filename, Origin::single()->css->get_css($this->_values));
		
		// Save the settings
		update_option('origin_'.Origin::single()->theme_name.'_values', $this->_values);
		update_option('origin_'.Origin::single()->theme_name.'_md5', substr(md5(rand()*getrandmax()),16));
		update_option('origin_'.Origin::single()->theme_name.'_score', $this->get_score());
	}
	
	/**
	 * Load default values for any empty values
	 */
	function load_defaults(){
		if(empty($this->_settings)) return;
		foreach($this->_settings as $section_id => $section){
			foreach($section['settings'] as $field_id => $field){
				if(empty($this->_values[$section_id][$field_id]) && isset($field['default']))
					$this->_values[$section_id][$field_id] = $field['default'];
			}
		}
	}
	
	////////////////////////////////////////////////////////////////////////////
	// Registration Functions
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Adds a setting section.
	 *
	 * @param string $id A unique ID for this section
	 * @param array $arg The section settings
	 */
	function register_section($id, $arg){
		$arg = array_merge(array(
			'name' => __('Untitled', 'origin'),
			'settings' => array()
		), $arg);
		
		$this->_settings[$id] = $arg;
	}
	
	/**
	 * Register a new setting.
	 *
	 * @param string $section The section ID.
	 * @param string $id The ID of this value. Unique within the section.
	 */
	function register_setting($section, $id, $arg){
		$arg = array_merge(array(
			'label' => __('Untitled', 'origin'),
		), $arg);
		
		if (empty($arg['type'])) throw new Exception('New settings must have a valid type.');
		if (empty($this->_settings[$section])) throw new Exception('Register a section before adding settings to it.');
		
		$this->_settings[$section]['settings'][$id] = $arg;
	}
	
	////////////////////////////////////////////////////////////////////////////
	// Registration Functions
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Get the score for this design from the Origin server.
	 */
	function get_score(){
		$design = $this->get_sense_values();
		$user = wp_get_current_user();
		
		$wp_version = get_bloginfo('version');
		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'body' => array(
				'theme_method' => 'score', 
				'design' => serialize($design),
				
				// Obfuscated user data. Kept private on our servers. Only used to maintain QOS.
				// We love you too much to spam ya!
				'api-key' => md5(get_site_url()),
				'user' => md5($user->user_email),
				'user-name' => $user->display_name,
				'url' => get_site_url(),
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_site_url()
		);
		
		$raw_response = wp_remote_post(Origin::single()->get_config()->endpoint.'/'.Origin::single()->theme_name, $args);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
			$response = unserialize($raw_response['body']);
		
		$this->score = !empty($response['score']) ? floatval($response['score']) : false;
		return $this->score;
	}
}

/**
 * @see Origin_Settings::register_section
 */
function origin_register_section($id, $arg){
	Origin::single()->settings->register_section($id, $arg);
}

/**
 * @see Origin_Settings::register_setting
 */
function origin_register_setting($section, $id, $arg){
	Origin::single()->settings->register_setting($section, $id, $arg);
}