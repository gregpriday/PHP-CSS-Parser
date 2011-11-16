<?php

/**
 * A holder class handlers of classes in the origin CSS file.
 */
class Origin_CSS_Executor{
	public static function e(){
		$args = func_get_args();
		return implode('', $args);
	}
	
	public static function template_url(){
		return get_template_directory_uri();
	}
	
	public static function site_url(){
		return get_site_url();
	}
	
	////////////////////////////////////////////////////////////////////////////
	// Color Functions
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Varies the luminance of the argument color
	 */
	public static function color_lum($color, $lum, $m = null){
		$base = new Origin_Color($color);
		if(!empty($m)) $lum *= $m;
		
		$base->lum += $lum;
		return $base->hex;
	}
	
	public static function color_grey($float){
		return Origin_Color_Base::float2hex($float);
	}
	
	/**
	 * Displays an RGBA color
	 */
	public static function rgba($color, $opacity){
		$rgb = Origin_Color_Base::hex2rgb($color);
		return 'rgba('.implode(',', $rgb).','.$opacity.')';
	}
	
	////////////////////////////////////////////////////////////////////////////
	// CSS Functions
	////////////////////////////////////////////////////////////////////////////
	
	public static function css_texture($name, $level){
		return 'url('.get_template_directory_uri().'/images/textures/levels/'.$name.'_l'.$level.'.png)';
	}
	
	/**
	 * Creates the CSS for a gradient.
	 *
	 * @param string $color The hex of the center color.
	 * @param float $var The lum amount
	 */
	public static function css_gradient($color, $var){
		$base = new Origin_Color($color);
		
		$start = clone $base;
		$start->lum -= $var/2;
		
		$end = clone $base;
		$end->lum += $var/2;
		
		return implode('; ', array(
			"background: #ffffff",
			"background: -moz-linear-gradient(top, {$start->hex} 0%, {$end->hex} 100%)",
			"background: -webkit-linear-gradient(top, {$start->hex} 0%, {$end->hex} 100%)",
			"background: -o-linear-gradient(top, {$start->hex} 0%, {$end->hex} 100%)",
			"background: -ms-linear-gradient(top, {$start->hex} 0%, {$end->hex} 100%)",
			"background: linear-gradient(top, {$start->hex} 0%, {$end->hex} 100%)",
			"filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='{$start->hex}', endColorstr='{$end->hex}',GradientType=0 )",
		)).'; ';
	}
}