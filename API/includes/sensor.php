<?php
class sensor {
	public $actionName;
	public $errorType;
	public $result;
	public $data;
	
	function __construct() {
		$this->errorType = 0;
		$this->result = 0;
		$this->data = "";
		$this->actionName = "";
	}
}

?>