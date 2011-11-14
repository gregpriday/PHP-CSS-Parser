<?php

abstract class CSSValueList extends CSSValue {
	protected $aComponents;
	protected $sSeparator;
	
	public function __construct($aComponents = array(), $sSeparator = ',') {
		if($aComponents instanceof CSSValueList && $aComponents->getListSeparator() === $sSeparator) {
			$aComponents = $aComponents->getListComponents();
		} else if(!is_array($aComponents)) {
			$aComponents = array($aComponents);
		}
		$this->aComponents = $aComponents;
		$this->sSeparator = $sSeparator;
	}

	public function addListComponent($mComponent) {
		$this->aComponents[] = $mComponent;
	}

	public function getListComponents() {
		return $this->aComponents;
	}

	public function setListComponents($aComponents) {
		$this->aComponents = $aComponents;
	}
	
	public function getListSeparator() {
		return $this->sSeparator;
	}

	public function setListSeparator($sSeparator) {
		$this->sSeparator = $sSeparator;
	}

	function __toString() {
		return implode($this->sSeparator, $this->aComponents);
	}
}

class CSSRuleValueList extends CSSValueList {
	public function __construct($sSeparator = ',') {
		parent::__construct(array(), $sSeparator);
	}
}

class CSSFunction extends CSSValueList {
	private $sName;
	public function __construct($sName, $aArguments) {
		$this->sName = $sName;
		parent::__construct($aArguments);
	}

	public function getName() {
		return $this->sName;
	}

	public function setName($sName) {
		$this->sName = $sName;
	}

	public function getArguments() {
		return $this->aComponents;
	}

	public function __toString() {
		$aArguments = parent::__toString();
		return "{$this->sName}({$aArguments})";
	}
}

class CSSColor extends CSSFunction {
	public function __construct($aColor) {
		parent::__construct(implode('', array_keys($aColor)), $aColor);
	}
	
	public function getColor() {
		return $this->aComponents;
	}

	public function setColor($aColor) {
		$this->setName(implode('', array_keys($aColor)));
		$this->aComponents = $aColor;
	}
	
	public function getColorDescription() {
		return $this->getName();
	}
}

class CSSOriginFunction extends CSSValueList {
	private $function;
	
	private $output = null;
	
	/**
	 * The result of executing the function
	 */
	private $result;
	
	/**
	 * @var bool JavaScript mode.
	 */
	private $jsmode;
	
	private $exitString = true;
	
	public function __construct($function, $aArguments) {
		$this->function = $function;
		parent::__construct($aArguments);
	}
	
	/**
	 * Executes an origin function
	 */
	public function execute($executor) {
		if(!method_exists($executor, $this->function))
			throw new Exception('Invalid executor function. Could not find '.(is_string($executor) ? $executor : get_class($executor)).'::'.$this->sName);
		
		$args = array();
		foreach($this->aComponents as $component){
			$args[] = (string) $component;
		}
		
		$this->output = call_user_func_array(array($executor, $this->function), $args);
	}

	public function getName() {
		return $this->sName;
	}

	public function setName($sName) {
		$this->sName = $sName;
	}

	public function getArguments() {
		return $this->aComponents;
	}
	
	/**
	 * Makes the function not exit from a string when we're creating the javascript code.
	 */
	public function noExitString(){
		$this->exitString = false;
	}
	
	/**
	 * @param bool $mode If set to true, __toString() will return Javascript code.
	 */
	public function setJSMode($jsmode = true){
		$this->jsmode = $jsmode;
		foreach($this->aComponents as $c){
			if($c instanceof CSSOriginFunction) $c->noExitString();
		}
	}

	public function __toString() {
		if($this->jsmode){
			$aArguments = parent::__toString();
			$return = '';
			if($this->exitString) $return .= '" + ';
			$return .= "e['{$this->function}']({$aArguments})";
			if($this->exitString)  $return .= ' + "';
			return $return;
		}
		else return (string) $this->output;
	}
}


