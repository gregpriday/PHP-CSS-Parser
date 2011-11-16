/**
 * A basic color space converter for Javascript. Color conversion based on algorithms form EasyRGB <http://www.easyrgb.com/>.
 *
 * @copyright Copyright 2011, Greg Priday <greg@siteorigin.com>
 * @license GPL v2.0
 */

var originColor = {
	/**
	 * Convert a color from HEX to RGB.
	 */
	hex2rgb : function(hex) {
		if(hex[0] != '#') throw "Invalid hex color";
		hex = hex.replace(/[^0-9A-Fa-f]/, '');
		var rgb = [];
		
		if(hex.length == 6){
			// This is a full hex code
			var c = parseInt(hex, 16);
			rgb[0] = 0xFF & (c >> 0x10);
			rgb[1] = 0xFF & (c >> 0x8);
			rgb[2] = 0xFF & c;
		}
		else if(hex.length == 3){
			// This is shorthand
			rgb[0] = parseInt(Array(3).join(hex[0]) , 16);
			rgb[1] = parseInt(Array(3).join(hex[1]) , 16);
			rgb[2] = parseInt(Array(3).join(hex[2]) , 16);
		}
		
		return rgb;
	},
	
	/**
	 * Convert a color from RGB to HEX.
	 */
	rgb2hex : function(rgb) {
		var hex = '#';
		var hexVal;
		for(var i = 0; i < 3; i++){
			hexVal = parseInt(rgb[i]+'', 10).toString(16);
			if(hexVal.length == 1) hexVal = 0 + '' + hexVal;
			hex += hexVal;
		}
		
		return hex;
	},
	
	/**
	 * Convert a color from RGB to XYZ.
	 */
	rgb2xyz : function(rgb) {
		for(var i = 0; i < 3; i++) rgb[i] /= 255;
		
		for(var i = 0; i < 3; i++){
			if (rgb[i] > 0.04045){ rgb[i] = Math.pow((rgb[i] + 0.055) / 1.055, 2.4); }
			else { rgb[i] = rgb[i] / 12.92; }
			
			rgb[i] = rgb[i] * 100;
		}
		
		//Observer. = 2¡, Illuminant = D65
		var xyz = [];
		xyz[0] = rgb[0] * 0.4124 + rgb[1] * 0.3576 + rgb[2] * 0.1805;
		xyz[1] = rgb[0] * 0.2126 + rgb[1] * 0.7152 + rgb[2] * 0.0722;
		xyz[2] = rgb[0] * 0.0193 + rgb[1] * 0.1192 + rgb[2] * 0.9505;
		
		return xyz;
	},
	
	/**
	 * Convert a color from XYZ to RGB.
	 */
	xyz2rgb : function(xyz) {
		// (Observer = 2¡, Illuminant = D65)
		xyz[0] /= 100; //X from 0 to  95.047
		xyz[1] /= 100; //Y from 0 to 100.000
		xyz[2] /= 100; //Z from 0 to 108.883
		
		var rgb = [];
		
		rgb[0] = xyz[0] * 3.2406 + xyz[1] * -1.5372 + xyz[2] * -0.4986;
		rgb[1] = xyz[0] * -0.9689 + xyz[1] * 1.8758 + xyz[2] * 0.0415;
		rgb[2] = xyz[0] * 0.0557 + xyz[1] * -0.2040 + xyz[2] * 1.0570;
		
		for(var i = 0; i < 3; i ++){
			if ( rgb[i] > 0.0031308 ) { rgb[i] = 1.055 * Math.pow( rgb[i] , ( 1 / 2.4 ) ) - 0.055; }
			else { rgb[i] = 12.92 * rgb[i]; }
		}
		
		rgb[0] = Math.round(Math.min(Math.max(rgb[0],0),1) * 255);
		rgb[1] = Math.round(Math.min(Math.max(rgb[1],0),1) * 255);
		rgb[2] = Math.round(Math.min(Math.max(rgb[2],0),1) * 255);
		
		return rgb;
	},
	
	/**
	 * Convert a color from XYZ to LAB.
	 */
	xyz2lab : function(xyz) {
		// Observer= 2¡, Illuminant= D65
		xyz[0] = xyz[0] / 95.047;
		xyz[1] = xyz[1] / 100.000;
		xyz[2] = xyz[2] / 108.883;
		
		for(var i = 0; i < 3; i++){
			if (xyz[i] > 0.008856 ) { xyz[i] = Math.pow( xyz[i] , 1/3 ); }
			else { xyz[i] = ( 7.787 * xyz[i] ) + ( 16/116 ); }
		}
		
		var lab = [];
		lab[0] = ( 116 * xyz[1] ) - 16;
		lab[1] = 500 * ( xyz[0] - xyz[1] );
		lab[2] = 200 * ( xyz[1] - xyz[2] );
		
		for(var i = 0; i < 3; i++) lab[i] /= 100;
		
		return lab;
	},
	
	/**
	 * Convert a color from LAB to XYZ.
	 */
	lab2xyz : function(lab) {
		for(var i = 0; i < 3; i++) lab[i] *= 100;
		
		var xyz = [];
		
		xyz[1] = (lab[0] + 16) / 116;
		xyz[0] = lab[1] / 500 + xyz[1];
		xyz[2] = xyz[1] - lab[2] / 200;
		
		for(var i = 0; i < 3; i++){
			if ( Math.pow( xyz[i] , 3 ) > 0.008856 ) { xyz[i] = Math.pow( xyz[i] , 3 ); }
			else { xyz[i] = ( xyz[i] - 16 / 116 ) / 7.787; }
		}
		
		// Observer= 2¡, Illuminant= D65
		xyz[0] *= 95.047;
		xyz[1] *= 100.000;
		xyz[2] *= 108.883; 
		
		return xyz;
	},
	
	/**
	 * Convert a color from RGB to LAB.
	 */
	rgb2lab : function(rgb) {
		var xyz = this.rgb2xyz(rgb);
		return this.xyz2lab(xyz);
	},
	
	/**
	 * Convert a color from LAB to RGB.
	 */
	lab2rgb : function(lab) {
		var xyz = this.lab2xyz(lab);
		return this.xyz2rgb(xyz);
	},
	
	/**
	 * Convert a color from LAB to HEX
	 */
	lab2hex : function(lab) {
		var rgb = this.lab2rgb(lab);
		return this.rgb2hex(rgb);
	},
	
	/**
	 * Convert a color from HEX to LAB
	 */
	hex2lab : function(hex) {
		var rgb = this.hex2rgb(hex);
		return this.rgb2lab(rgb);
	}
};