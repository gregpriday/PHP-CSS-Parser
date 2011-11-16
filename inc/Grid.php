<?php

/**
 * Creates nestable, responsive, accurate grids.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 */
class Origin_Grid {
	
	static $_specialResolutions = array(
		'mobile' => 420, // A generic mobile resolution
		'iphone' => 320,
		'iphone-landscape' => 640,
		'vga' => 640,
		'ipad' => 768,
		'ipad-landscape' => 1024,
		'svga' => 800,
		'xga' => 1024,
		'full' => 1920,
	);
	
	static $_grid_defaults = array(
		'cell' => array(
			'weight' => null,
		),
		'grid' => array(
			'responsive' => 'mobile=1&iphone-landscape=50%',
			'neg' => true,
			'cols' => null,
			'margin' => null,
			'cell-margin' => null,
		)
	);
	
	private $_config;
	
	/**
	 * Must we go ahead with processing the grids
	 */
	private $_do;
	
	/**
	 * @param DOMDocument $dom The dom document.
	 */
	function __construct(){
		$this->_do = false;
		add_action('template_redirect', array($this, 'start_buffer'), 1000);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_style'));
		add_filter('the_content', array($this, 'dataizer'));
		add_action('wp_footer', array($this, 'enable'));
	}
	
	/**
	 * @todo ensure that we DON'T parse the html if we exit before reaching the action 'shutdown'
	 */
	function __destruct(){
	}
	
	function enqueue_style(){
		wp_enqueue_style('grid-engine', ORIGIN_BASE_URL.'/css/grid.css');
	}
	
	/**
	 * Start the buffer
	 */
	public function start_buffer(){
		ob_start(array($this, 'process'));
	}
	
	/**
	 * Enable the grid engine. This is to stop exited queries from being run.
	 */
	public function enable(){
		// We assume if we've made it all the way to wp_footer, then we'll have valid html to grid
		$this->_do = true;
	}
	
	/**
	 * Parses a post's content to create grids
	 */
	public function dataizer($content){
		$content = preg_replace_callback('#\[ *(cell)([^\]]*)\]#i', array($this, 'shortcode_dataizer'), $content);
		$content = preg_replace('#\[ */\ *cell([^\]])*\]#i', "\n\n</div>", $content);
		
		$content = preg_replace_callback('#\[ *(grid)([^\]]*)\]#i', array($this, 'shortcode_dataizer'), $content);
		$content = preg_replace('#\[ */\ *grid([^\]])*\]#i', "</div>", $content);
		
		return $content;
	}
	
	/**
	 * Turns a shortcode into a tag with the name being the class and the attributes being data-* fields.
	 */
	public function shortcode_dataizer($matches = array()){
		$matches[2] = trim($matches[2]);
		preg_match_all('/([a-zA-Z-]+)\s*=\s*(\"??)([^"]*?)\\2/siU', $matches[2], $tag_matches);
		
		$atts = array();
		foreach($tag_matches[1] as $i => $tag){
			$atts[$tag] = $tag_matches[3][$i];
		}
		
		if(empty(self::$_grid_defaults[$matches[1]])) return '';
		
		$atts = shortcode_atts(self::$_grid_defaults[$matches[1]], $atts);
		
		$classes = array();
		$classes[] = $matches[1];
		if(isset($atts['neg'])){
			$classes[] = ($atts['neg'] ? 'withneg' : 'noneg');
		}
		
		$return = '<div class="'.implode(' ', $classes).'" ';
		foreach($atts as $tag => $val){
			if(!empty($val)) $return .= 'data-'.$tag.'="'.esc_attr($val).'" ';
		}
		$return .= ">\n\n";
		return  $return;
	}
	
	/**
	 * Columnize the dom
	 */
	public function process($html){
		if(!$this->_do) return $html;
		if(empty($html)) return $html;
		
		// TODO check that get_bloginfo isn't causing problems. Was getting a fatal error from $wp_cache
		$dom = new DOMDocument('1.0', get_bloginfo('charset'));
		@$dom->loadHTML($html);
		$xpath = new DOMXPath($dom);
		
		// Configure the HTML and create a signature that defines the grids
		$tosign = array();
		foreach($xpath->query('//div[contains(@class, "grid")]') as $gi => $grid_container){
			if(!$grid_container->hasAttribute('id'))
				$grid_container->setAttribute('id', 'grid-'.$gi);
			
			// Add the clearing div
			$clear = $dom->createElement('div');
			$clear->setAttribute('class', 'clear');
			$grid_container->appendChild($clear);
			
			$tosign[$gi] = array(
				'id' => $grid_container->getAttribute('id'),
				'class' => $grid_container->getAttribute('class'),
				'cells' => array()
			);
			foreach($grid_container->attributes as $attribute){
				if(substr($attribute->name,0,5) == 'data-'){
					$tosign[$gi][$attribute->name] = $attribute->value;
				}
			}
			
			foreach($xpath->query('./div[contains(@class, "cell")]', $grid_container) as $ci => $cell){
				$cell->setAttribute('class', $cell->getAttribute('class').' '.'cell-'.$ci);
				
				$tosign[$gi]['cells'][$ci] = array(
					'id' => $cell->getAttribute('id'),
					'class' => $cell->getAttribute('class')
				);
				foreach($cell->attributes as $attribute){
					if(substr($attribute->name,0,5) == 'data-'){
						$tosign[$gi]['cells'][$ci][$attribute->name] = $attribute->value;
					}
				}
			}
		}
		
		$output_file = Origin::single()->cache->get_file('grid', $tosign, 'css');
		
		// Check if there's a valid cache file we can use
		if($output_file !== false && file_exists($output_file)){
			// Add the style link
			$head = $xpath->query('head')->item(0);
			if(empty($head)) return $dom->saveHTML();
			
			$style = $dom->createElement('link');
			$style->setAttribute('rel', 'stylesheet');
			$style->setAttribute('type', 'text/css');
			$style->setAttribute('media', 'screen');
			$style->setAttribute('href', Origin::single()->cache->get_last_url() );
			$head->appendChild($style);
			
			return $dom->saveHTML();
		}
		
		$css = array();
		
		$grids = array(); // Groups have shared parents
		$grid_attributes = array();
		
		// Row and group indexes
		$gi = 0;
		
		$grid_containers = $xpath->query('//div[contains(@class, "grid")]');
		
		foreach($grid_containers as $gi => $grid_container){
			// Select content columns of a given depth
			$cells = $xpath->query('./div[contains(@class, "cell")]', $grid_container);
			
			if($grid_container->hasAttribute('data-margin')){
				$css[1920]['margin-bottom:'.$grid_container->getAttribute('data-margin').';'][] = '#'.$grid_container->getAttribute('id');
			}
		}
		
		// Size the grid
		foreach($grid_containers as $gi => $grid_container){
			$cells = $xpath->query('./div[contains(@class, "cell")]', $grid_container);
			
			$cols = intval($grid_container->getAttribute('data-cols'));
			if(empty($cols)){
				$cols = $cells->length;
			}
			
			// We'll consider 1920 the maximum width
			$responsive_reses = array(1920);
			$responsive = array(
				1920 => $cols
			);
			
			// Get all the responsive settings
			if($grid_container->hasAttribute('data-responsive')){
				parse_str($grid_container->getAttribute('data-responsive'), $responsive_settings);
				array_map('trim', $responsive_settings);
				
				foreach($responsive_settings as $res => $scale){
					$res = isset(self::$_specialResolutions[$res]) ? self::$_specialResolutions[$res] : $res;
					$responsive_reses[] = intval($res);
					
					if(substr($scale,-1,1) == '%'){
						$responsive[$res] = ceil($cols / 100 * substr($scale,0,strlen($scale)-1));
					}
					else{
						$responsive[$res] = intval($scale);
					}
				}
			}
			
			foreach($responsive_reses as $i => $res){
				$cols = $responsive[$res];
				$weight_sum = 0;
				
				$next = $responsive_reses[$i+1];
				
				// Calculate the maximum weight sum
				$max_weight_sum = $cols;
				foreach($cells as $ci => $cell){
					$weight = $cell->getAttribute('data-weight');
					if(empty($weight)) $weight = 1;
					
					$weight_sum += $weight;
					if($ci % $cols == $cols - 1){
						$max_weight_sum = max($max_weight_sum, $weight_sum);
						$weight_sum = 0;
					}
				}
				$max_weight_sum = max($max_weight_sum, $weight_sum);
				
				// Add the default cell stuff
				$css[$res]['clear:none;'][] = '#'.$grid_container->getAttribute('id').' > .cell';
				if($cols == 1){
					$css[$res]['width:100%;'][] = '#'.$grid_container->getAttribute('id').' > .cell';
				}
				
				foreach($cells as $ci => $cell){
					$weight = $cell->getAttribute('data-weight');
					if(empty($weight)) $weight = 1;
					
					if($cols != 1){
						if($ci % $cols == 0) $css[$res]['clear:left;'][] = '#'.$grid_container->getAttribute('id').' > .cell-'.$ci;
						
						$width_rule = "width:". round(100 / $max_weight_sum * $weight,4)  ."%;";
						$css[$res][$width_rule][] = '#'.$grid_container->getAttribute('id').' > .cell-'.$ci;
					}
					
					if(floor($ci/$cols) == ceil($cells->length/$cols)-1){
						$css[$res]['margin-bottom:0px'][] = '#'.$grid_container->getAttribute('id').' > .cell-'.$ci;
					}
					else{
						$margin = $grid_container->getAttribute('data-cell-margin');
						if(empty($margin)) $margin = '15px';
						$css[$res]['margin-bottom:'.$margin.';'][] = '#'.$grid_container->getAttribute('id').' > .cell-'.$ci;
					}
				}
			}
		}
		
		// Build the CSS
		$css_text = '';
		krsort($css);
		foreach($css as $res => $def){
			if($res < 1920){
				$css_text .= '@media (max-width:'.$res.'px)';
				$css_text .= ' { ';
			}
			
			foreach($def as $property => $selector){
				$selector = array_unique($selector);
				$css_text .= implode(' , ', $selector).' { '.$property.' } ';
			}
			
			if($res < 1920) $css_text .= ' } ';
		}
		
		$head = $xpath->query('head')->item(0);
		if(empty($head)) return $dom->saveHTML();
		
		if($output_file === false){
			// Add the CSS to the header
			$style = $dom->createElement('style');
			$style->setAttribute('type', 'text/css');
			$head->appendChild($style);
			
			// Add the CSS text
			$text = $dom->createTextNode($css_text);
			$style->appendChild($text);
		}
		else{
			// Save the CSS text to a file
			file_put_contents($output_file, $css_text);
			
			$style = $dom->createElement('link');
			$style->setAttribute('rel', 'stylesheet');
			$style->setAttribute('type', 'text/css');
			$style->setAttribute('media', 'screen');
			$style->setAttribute('href', Origin::single()->cache->get_last_url() );
			$head->appendChild($style);
		}
		
		return $dom->saveHTML();
	}
}