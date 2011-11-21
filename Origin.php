<?php

require_once(dirname(__FILE__).'/inc/Color.php');

require_once(dirname(__FILE__).'/inc/Settings.php');
require_once(dirname(__FILE__).'/inc/CSS.php');
require_once(dirname(__FILE__).'/inc/Grid.php');
require_once(dirname(__FILE__).'/inc/Image.php');
require_once(dirname(__FILE__).'/inc/Cache.php');

/**
 * The Origin framework controller
 *
 * @copyright Copyright (2011), Greg Priday <greg@siteorigin.com>
 * @license GPL v2.0
 */

class Origin {
	// This is where we'll interact with
	const ENDPOINT = 'http://wp.localhost:8888/theme';
	
	// $_REQUEST arg used as a method
	const METHOD_ARG = 'om';
	
	/**
	 * @var Singleton instance
	 */
	protected static $_instance;
	
	/**
	 * @var string The name of this theme
	 */
	public $theme_name;
	
	////////////////////////////////////////////////////////////////////////////
	// Support classes
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * @var Origin_Settings
	 */
	public $settings;
	
	/**
	 * @var Origin_Image
	 */
	public $image;
	
	/**
	 * @var Origin_Grid
	 */
	public $grid;
	
	/**
	 * @var Origin_CSS
	 */
	public $css;
	
	/**
	 * @var Origin_Cache
	 */
	public $cache;
	
	////////////////////////////////////////////////////////////////////////////
	// Settings Member Variables
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * @var stdClass Settings loaded from the settings file.
	 */
	private $_config;
	
	/**
	 * @var array Methods that'll be called with ?mo=xxx requests
	 */
	private $_methods = array();
	
	////////////////////////////////////////////////////////////////////////////
	// Cache variables
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * @var array Temporary cache files.
	 */
	private $_temp_cache = array();
	
	/**
	 * Create an instance of the controller. This shouldn't be called directly, rather through the Origin::single() method.
	 */
	function __construct(){
		if(!isset(self::$_instance)) self::$_instance = $this;
		else throw new Exception("Don't double contruct Origin.");
		
		$this->theme_name = basename(get_template_directory());
		
		if(file_exists(WP_CONTENT_DIR.'/origin-settings.php'))
			$this->_config = (object) include(WP_CONTENT_DIR.'/origin-settings.php');
		else $this->_config = (object) include(dirname(__FILE__).'/config.php');
		
		$methods = get_class_methods($this);
		foreach($methods as $method){
			switch(substr($method,0,6)) {
				case 'action' : add_action(substr($method,7), array($this, $method)); break;
				case 'filter' : add_filter(substr($method,7), array($this, $method)); break;
			}
		}
		
		// Create the support objects
		$this->image = new Origin_Image();
		$this->grid = new Origin_Grid();
		$this->css = new Origin_CSS();
		$this->cache = new Origin_Cache();
		
		// Create settings last, so we can access the other support objects
		$this->settings = new Origin_Settings();
		$this->settings->load_files();
		
		// These are some definitions we need
		if(!defined('ORIGIN_BASE_URL')) define('ORIGIN_BASE_URL', get_template_directory_uri().'/'.$this->_config->path);
		add_action('template_redirect', array($this, '_method_handler'));
	}
	
	/**
	 * Used as a usort function to see which origin file should be loaded first.
	 */
	static function load_order_compare($filename1, $filename2){
		list($v1, $n1) = explode('-', basename($filename1));
		list($v2, $n2) = explode('-', basename($filename2));
		
		if(empty($n1) || empty($n2)) return strcasecmp($v1, $v2);
		else return version_compare($v1, $v2);
	}
	
	/**
	 * Get the singleton of the controller
	 */
	public static function single()
    {
        if (!isset(self::$_instance)) {
            $className = __CLASS__;
            new $className;
        }
		
        return self::$_instance;
    }
	
	/**
	 * Initialize the Method
	 */
	function _method_handler(){
		global $wp_query, $wp_rewrite;
		
		$method = $wp_query->get(self::METHOD_ARG);
		
		if(!empty($method)){
			if(in_array($method, array_keys($this->_methods))){
				call_user_func($this->_methods[$method], $this, $this->_config);
				return;
			}
			
			$method = 'method_'.$method;
			if(method_exists($this, $method)){
				$this->{$method}();
				return;
				// It's up to the method to execute exit();
			}
		}
	}
	
	/**
	 * Allows other components of Origin to add url methods.
	 */
	function register_method($method, $handler){
		$this->_methods[$method] = $handler;
	}
	
	/**
	 * Render the Origin design page
	 */
	function render_page(){
		if (!current_user_can('edit_themes')) wp_die( __('You do not have sufficient permissions to access this page.', 'origin') );
		
		// Get CSS stuff
		$all_selectors = $this->css->get_selectors();
		$selectors = $this->css->get_setting_selectors();
		$css_js_function = $this->css->get_js_function();
		
		$updated = (@$_POST['action'] == 'save');
		
		// Get the current filters
		$user = wp_get_current_user();
		$user_filters = get_user_meta($user->ID, 'origin_filters', false);
		
		if(empty($user_filters) || @$user_filters['theme'] != $this->theme_name){
			$user_filters['section'] = null;
			$user_filters['tag'] = null;
			$user_filters['selector'] = null;
		}
		
		// Start URL is the page we'll start the preview from
		if(!empty($_GET['ref'])) $start_url = $_GET['ref'];
		else $start_url = home_url();
		
		include(dirname(__FILE__).'/tpl/admin.phtml');
	}
	
	/**
	 * Get the origin storage folder
	 *
	 * @param string $sub The sub folder.
	 * @param bool $create Should we create the folder too?
	 */
	function get_storage_folder($sub = '', $create = false){
		$upload_dir = wp_upload_dir();
		$base_folder = $upload_dir['basedir'].'/origin';
		
		$folder = path_join($base_folder, $sub);
		if(!is_dir($folder) && $create) mkdir($folder, 0777, true);
		
		return $folder;
	}
	
	/**
	 * Get the URL of the storage.
	 *
	 * @param string $sub The sub folder.
	 */
	function get_storage_url($sub = ''){
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'].'/origin'.(!empty($sub) ? '/'.$sub : '');
	}
	
	public function get_config(){
		return $this->_config;
	}
	
	////////////////////////////////////////////////////////////////////////////
	// Action functions (add_action)
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Initialize Origin
	 */
	function action_init(){
		// Trigger an update of the themes
		if (is_admin()) $current = get_transient('update_themes');
		
		// Create the CSS if it doesn't exist
		$css_file = $this->css->get_css_path();
		
		if(!file_exists($css_file)){
			file_put_contents($css_file,$this->css->get_css($this->settings->get_values()));
		}
		
		$this->cache->clear_cache();
	}
	
	/**
	 * Enqueue Origin stuff.
	 */
	function action_admin_enqueue_scripts(){
		if(@$_REQUEST['page'] != 'origin') return;
		
		// jQuery UI
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-google', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js', array('jquery'));
		wp_enqueue_style('jquery-ui-google', ORIGIN_BASE_URL.'/externals/jquery-ui/origin-theme/jquery-ui-1.8-origin.css');
		
		// General admin stuff
		wp_enqueue_script('origin.admin', ORIGIN_BASE_URL.'/js/origin.js', array('jquery'));
		wp_enqueue_style('origin.admin', ORIGIN_BASE_URL.'/css/admin.css');
		
		// Chosen
		wp_enqueue_script('jquery.chosen', ORIGIN_BASE_URL.'/externals/chosen/chosen.jquery.js', array('jquery'));
		wp_enqueue_style('jquery.chosen', ORIGIN_BASE_URL.'/externals/chosen/chosen.css');
		
		// Everything that we need for the dynamic previews
		wp_enqueue_script('origin.preview', ORIGIN_BASE_URL.'/js/preview/preview.js', array('jquery'));
		wp_enqueue_script('origin.color', ORIGIN_BASE_URL.'/js/preview/color.js', array('jquery'));
		wp_enqueue_script('origin.executor', ORIGIN_BASE_URL.'/js/preview/executor.js', array('jquery'));
		
		wp_localize_script('origin.preview', 'originSettings', array(
			'templateUrl' => get_template_directory_uri()
		));
		
		// Now, give all the settings fields a chance to enqueue stuff
		$types = array();
		foreach($this->settings->get_settings() as $section_id => $section){
			foreach($section['settings'] as $setting_id => $setting){
				$types[] = $setting['type'];
			}
		}
		$types = array_unique($types);
		foreach($types as $type){
			$class = 'Origin_Type_'.ucfirst(strtolower($type));
			if(!class_exists($class)) require_once(dirname(__FILE__).'/inc/types/'.$type.'.php');
			if(method_exists($class, 'enqueue')) call_user_func(array($class, 'enqueue'));
		}
	}
	
	/**
	 * Initialize the Origin design page
	 */
	function action_admin_menu(){
		if(@$_GET['page'] == 'origin' && @$_POST['action'] == 'save') {
			// Persist the filters
			$filers = array(
				'section' => $_REQUEST['filter_section'],
				'tag' => $_REQUEST['filter_tag'],
				'selector' => $_REQUEST['filter_selector'],
				'theme' => $this->theme_name
			);
			$user = wp_get_current_user();
			if(!empty($user->ID)){
				delete_user_meta($user->ID, 'origin_filters');
				update_user_meta($user->ID, 'origin_filters', $filers);
			}
			
			// Save the settings
			$this->settings->save($_POST);
		}
		add_theme_page('Theme Design', 'Design', 'edit_theme_options', 'origin', array($this, 'render_page'));
	}
	
	/**
	 * Enqueue scripts in the admin section
	 */
	function action_wp_enqueue_scripts(){
		global $pagenow;
		if(is_admin() || $pagenow == 'wp-login.php') return;
		
		wp_enqueue_style('origin', $this->css->get_css_url(), array(), get_option('origin_'.$this->theme_name.'_md5', substr(md5(rand()),0,8)));
		
	}
	
	/**
	 * Add a design button to the menu bar.
	 */
	function action_admin_bar_menu(){
		if (!is_super_admin() || !is_admin_bar_showing() || is_admin()) return;
		
		$href= add_query_arg('page', 'origin', admin_url('/themes.php'));
		
		if(!is_admin() && !is_404()){
			$href = add_query_arg(array(
				'ref' => urlencode('http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"])
			), $href);
		}
		
		global $wp_admin_bar;
		global $wp_version;
		
		list($v) = explode('-', $wp_version, 2);
		
		$wp_admin_bar->add_menu(array(
			// Parent is dependent on WordPress version
			'parent' => version_compare($v, '3.3', '>=') ? 'site-name' : 'appearance',
			'title' => 'Design',
			'id' => 'origin-design',
			'href' => $href
		));
		
		return $wp_admin_bar;
	}
	
	////////////////////////////////////////////////////////////////////////////
	// Filter functions (add_filter)
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Check for theme updates.
	 */
	function filter_pre_set_site_transient_update_themes($checked_data){
		global $wp_version;
		
		if (empty($checked_data->checked)) return $checked_data;
		
		$send_for_check = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'body' => array(
				'action' => 'theme_update', 
				'current' => $checked_data->checked[$this->theme_name],
				'api-key' => md5(get_bloginfo('url'))
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
		);
		
		$raw_response = wp_remote_post($this->_config->endpoint.'/'.$this->theme_name.'/', $send_for_check);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)){
			$response = unserialize($raw_response['body']);
			$checked_data->response[$this->theme_name] = $response;
		}
		
		return $checked_data;
	}
	
	/**
	 * Adds the origin method handler as a WP query variable
	 */
	function filter_query_vars($query_args){
		$query_args[] = self::METHOD_ARG;
		return $query_args;
	}
}

// Initialize the singleton
Origin::single();