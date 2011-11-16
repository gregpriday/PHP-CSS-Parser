/**
 * Executors runs functions 
 */
var originExecutor = {
	
	e: function(){
		var parts = [];
		for(var i = 0; i < arguments.length; i++) parts[i] = arguments[i];
		
		return parts.join('');
	},
	
	rgba: function(color, opacity){
		var rgb = originColor.hex2rgb(color);
		return 'rgba(' + rgb.join(', ') + ', '+opacity+')';
	},
	
	////////////////////////////////////////////////////////////////////////////
	// Color Functions
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Add the arg value to lum
	 */
	color_lum : function(val, lum, m){
		
		var lab = originColor.hex2lab(val);
		
		lum = Number(lum);
		if(m != undefined) lum *= Number(m);
		lab[0] += lum;
		
		return originColor.lab2hex(lab);
	},
	
	color_grey : function (val){
		var g = Math.round(Number(val)*255);
		if(g >= 255) return '#FFFFFF';
		if(g <= 0) return '#000000';
		
		var str = g.toString(16);
		if (str.length == 1) str = '0'+str;
		
		return '#'+Array(4).join(str);
	},
	
	////////////////////////////////////////////////////////////////////////////
	// CSS Functions
	////////////////////////////////////////////////////////////////////////////
	
	css_texture : function(name, level) {
		return 'url('+originSettings.templateUrl+'/images/textures/levels/'+name+'_l'+level+'.png)';
	},
	
	/**
	 * Create a CSS3 gradient
	 */
	css_gradient : function(color, v){
		var b = originColor.hex2lab(color);
		v = Number(v);
		
		var startColor = originColor.lab2hex([b[0] - v/2, b[1], b[2]]);
		var endColor = originColor.lab2hex([b[0] + v/2, b[1], b[2]]);
		
		return [
			'background: '+color,
			'background: -moz-linear-gradient(top, '+startColor+' 0%, '+endColor+' 100%)',
			'background: -webkit-linear-gradient(top, '+startColor+' 0%, '+endColor+' 100%)',
			'background: -o-linear-gradient(top, '+startColor+' 0%, '+endColor+' 100%)',
			'background: -ms-linear-gradient(top, '+startColor+' 0%, '+endColor+' 100%)',
			'background: linear-gradient(top, '+startColor+' 0%, '+endColor+' 100%)',
			'filter: progid:DXImageTransform.Microsoft.gradient( startColorstr="'+startColor+'", endColorstr="'+endColor+'",GradientType=0 )',
		].join('; ')+'; ';
	}
}