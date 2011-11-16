<?php

require_once(dirname(__FILE__).'/CSS_Executor.php');

/**
 * Generates CSS properties
 *
 * @copyright Copyright (c) 2011, Greg Priday <greg@siteorigin.com>
 * @license GPL v2.0
 */
class Origin_CSS {
	
	private $filename;
	
	/**
	 * @var CSSDocument The CSS document
	 */
	private $css_doc;
	
	function __construct($filename = null){
		if(empty($filename)) $filename = get_template_directory().'/origin/origin.css';
		
		$this->filename = $filename;
	}
	
	/**
	 * Parse a CSS file
	 */
	function parse(){
		if(!empty($this->css_doc)) return $this->css_doc;
		if(!class_exists('CSSParser')) require(dirname(__FILE__).'/../externals/css-parser/CSSParser.php');
		
		// Load the default file
		if($file === null) $file = get_template_directory().'/origin/origin.css';
		
		$parser = new CSSParser(file_get_contents($file));
		$this->css_doc = $parser->parse();
	}
	
	/**
	 *
	 */
	public function get_css_path(){
		$path = Origin::single()->get_storage_folder('css', true);
		return path_join($path, Origin::single()->theme_name.'.css');
	}
	
	/**
	 *
	 */
	public function get_css_url(){
		return Origin::single()->get_storage_url('css').'/'.Origin::single()->theme_name.'.css';
	}
	
	/**
	 * Return an array indicating which settings effect which CSS selectors
	 */
	function get_setting_selectors(){
		if (empty($this->css_doc)) $this->parse();
		
		return $this->css_doc->originGetEffects();
	}
	
	/**
	 * Gets all the selectors.
	 *
	 * @return array() A flat array of selectors.
	 */
	function get_selectors(){
		if (empty($this->css_doc)) $this->parse();
		$selectors = array();
		foreach($this->css_doc->getAllRuleSets() as $rule){
			foreach($rule->getSelectors() as $selector){
				$selectors[] = $selector->getSelector();
			}
		}
		array_unique($selectors);
		
		return $selectors;
	}
	
	/**
	 * Generate the CSS
	 */
	function get_css($values){
		if (empty($this->css_doc)) $this->parse();
		
		$this->css_doc->originProcess($values, 'Origin_CSS_Executor');
		return $this->css_doc->__toString();
	}
	
	/**
	 * Gets the JS code required to generate 
	 */
	function get_js_function(){
		if (empty($this->css_doc)) $this->parse();
		
		return $this->css_doc->originJavascriptFunction();
	}
}