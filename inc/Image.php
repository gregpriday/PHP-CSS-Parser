<?php

/**
 * A secure image resizing class. It works entirely inside WordPress, which improves security.
 *
 * @copyright Copyright (c) 2011, Greg Priday
 */
class Origin_Image {
	const TESTING = false;
	
	/**
	 * @var Origin The origin class
	 */
	public $origin;
	
	/**
	 * @var array Resizing presets
	 */
	private $_presets = array();
	
	/**
	 * @var array Image effects
	 */
	private $_effects = array();
	
	private static $_url_rules = array(
		'oim/(.+?)/(\d+?)/(.+?)/?$' => 'index.php?om=im_preset&resize_preset=$matches[1]&attachment_id=$matches[2]',
	);
	
	/**
	 * Create an instance of the image resizer class.
	 *
	 * @param
	 */
	function __construct(){
		Origin::single()->register_method('im_preset', array($this, 'method_preset'));
		
		add_action('admin_init', array($this, 'flush_rewrite_rules'));
		add_action('generate_rewrite_rules', array($this, 'url_rules'));
		add_filter('query_vars', array($this, 'add_query_vars'));
	}
	
	////////////////////////////////////////////////////////////////////////////
	// The Register Functions
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Register an image preset.
	 *
	 * @param string $name A machine readable name for the preset.
	 * @param int $width The width of the preset.
	 * @param int $height The height of the preset.
	 * @param bool $crop Should the image be cropped, or fitted?
	 * @param string $effect The name of a registered effect.
	 * @param mixed $args Arguments to pass to the effect handler
	 */
	function register_preset($name, $width, $height, $crop = true, $effect = null, $args = null){
		$this->_presets[$name] = array(
			'name' => $name,
			'width' => $width,
			'height' => $height,
			'crop' => $crop,
			'effect' => $effect,
			'args' => $args,
		);
	}
	
	/**
	 * Register an image manipulation effect. The effect will probably use image magick command line.
	 */
	function register_effect($name, $callback){
		$this->_effects[$name] = $callback;
	}
	
	/**
	 * Get the URL of an attachment preset
	 */
	function get_preset_url($name, $id, $filename){
		global $wp_rewrite;
		
		if ($wp_rewrite->using_permalinks()){
			// Return a pretty version
			return site_url().'/oim/'.urlencode($name).'/'.intval($id).'/'.$filename.'/';
		}
		else{
			// TODO Return an ugly version
		}
	}
	
	/**
	* Flush the rewrite rules if the images rules aren't in the rewrite_rules setting array.
	*/
	function flush_rewrite_rules(){
		$rules = get_option( 'rewrite_rules' );
		// Flush the rules
		if(count(array_intersect(array_keys(self::$_url_rules) , array_keys($rules))) != count(self::$_url_rules)){
			// Flush the rules
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}
	
	/**
	* Create the URL rules
	*/
	function url_rules( $wp_rewrite ) {
		$wp_rewrite->rules = array_merge(self::$_url_rules, $wp_rewrite->rules);
		return $wp_rewrite;
	}
	
	/**
	* Add the custom query variables
	*/
	function add_query_vars( $query_vars ) {
		$query_vars[] = 'resize_preset';
		return $query_vars;
	}
	
	/**
	 * Perform the resize
	 */
	function method_preset(){
		global $wp_query, $post;
		
		// Check that this is a valid attachment image
		if($post->post_type != 'attachment' || substr($post->post_mime_type,0,5) != 'image'){
			return;
		}
		
		$preset = $this->_presets[$wp_query->get('resize_preset')];
		
		if (($convert = get_site_transient('imagemagick_location')) === false) {
			// Get the location of Imagemagick
			$convert = shell_exec('which convert');
			if(empty($convert)){
				switch(php_uname('s')){
					case 'Darwin':
						$convert = '/opt/local/bin/convert';
						break;
					default :
						$convert = '/usr/bin/convert';
						break;
				}
			}
			
			// Test if the location is valid
			exec("$convert -version", $output);
			preg_match('/'.preg_quote('Version: ImageMagick ').'([0-9.-]+)/', $output[0], $matches);
			
			// We need at least image magick 6.5
			if(isset($matches[1]) && version_compare($matches[1], '6', '>='))
				set_site_transient('imagemagick_location', $convert, 86400);
			else $convert = null;
		}
		$convert = trim($convert);
		
		// Get the base folder of the file
		$attachment = wp_get_attachment_metadata($post->ID);
		$upload_dir = wp_upload_dir();
		$original = $upload_dir['basedir'].'/'.$attachment['file'];
		
		$tosign = array(
			$post->ID,
			$preset['name']
		);
		$file = Origin::single()->cache->get_file('images', $tosign,'jpg');
		
		if(self::TESTING) @unlink($file);
		
		if(!file_exists($file)){
			if(!empty($convert)) $this->preset_imagick($convert, $original, $preset, $file);
			else $this->preset_gd($original, $preset, $file);
		}
		
		if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && file_exists($file) && !self::TESTING){
			$iftime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			$mtime = filemtime($file);
			
			if($mtime <= $iftime){
				header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified', true, 304);
				exit();
			}
		}
		
		header('Content-Type: image/jpeg');
		header('Content-Length: '.filesize($file));
		
		// Cache control stuff
		header('ETag: "'.md5(file_get_contents($file)).'"');
		header('Last-Modified: '.gmdate('r', filemtime($file)));
		header('Cache-Control: public, max-age='.(7*86400)); // Cache for up to 7 days
		header("Expires: ".gmdate('r', time() + 7*86400 )); // Expires in 7 days
		header('Pragma: no-cache');
		
		// Ensure there's nothing waiting in the buffer
		ob_clean();
		flush();
		
		// Display the image
		readfile($file);
		exit();
	}
	
	/**
	 * Resize using Image Magick.
	 */
	function preset_imagick($convert, $input, $preset, $output){
		extract($preset);
		if(empty($crop)) exec("{$convert} {$input} -resize {$width}x{$height}^ -colorspace RGB -format jpg {$output}", $matches);
		else exec("{$convert} {$input} -gravity center -resize {$width}x{$height}^ -extent {$width}x{$height} -format jpg {$output}", $matches);
		
		// We can also execute effects on this image
		if(!empty($preset['effect']) && isset($this->_effects[$preset['effect']])){
			call_user_func($this->_effects[$preset['effect']], $convert, $output, $args);
		}
	}
	
	/**
	 * This is a fallback. We'll resize using GD if we cant find imagemagick.
	 */
	function preset_gd($input, $preset, $output){
		extract($preset);
		
		list($width_orig, $height_orig, $type) = getimagesize($input);
		
		// Open the image
		$im = null;
		switch($type){
			case IMAGETYPE_JPEG : 
				$img = imagecreatefromjpeg($input);
				break;
			
			case IMAGETYPE_GIF:
				$img = imagecreatefromgif($input);
				break;
			
			case IMAGETYPE_PNG:
				$img = imagecreatefrompng($input);
				break;
		}
		
		// resize
		if($crop){
			$resized = imagecreatetruecolor($width, $height);
			
			// Calculate the original ratio
			$ratio = $width_orig/$height_orig;
    
			if ($width/$height > $ratio) {
			   $new_height = $width/$ratio;
			   $new_width = $width;
			}
			else {
			   $new_width = $height*$ratio;
			   $new_height = $height;
			}
			
			$x_mid = round($new_width / 2);  //horizontal middle
			$y_mid = round($new_height / 2); //vertical middle
			
			// This is an intermediate, resized image
			$process = imagecreatetruecolor(round($new_width), round($new_height));
			imagecopyresampled($process, $img, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
			
			// Create the final output
			imagecopyresampled($resized, $process, 0, 0, ($x_mid-($width/2)), ($y_mid-($height/2)), $width, $height, $width, $height);
		}
		else{
			$ratio = min($width/$width_orig, $height/$height_orig);
			$out_width = $width_orig * $ratio;
			$out_height = $height_orig * $ratio;
			
			$resized = imagecreatetruecolor($out_width, $out_height);
			imagecopyresampled($resized, $img, 0, 0, 0, 0, $out_width, $out_height, $width_orig, $height_orig);
		}
		
		imagejpeg($resized, $output);
	}
}

/**
 * @see Origin_Resize::register_preset()
 */
function origin_image_register_preset($name, $width, $height, $crop = true, $effect = null, $args = null){
	Origin::single()->image->register_preset($name, $width, $height, $crop, $effect, $args);
}

function origin_image_register_effect($name, $callback){
	Origin::single()->image->register_effect($name, $callback);
}