<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_test extends sensor {
	public $varName = "default";
	
	function __construct($actionName, $flow_id) {
		// flow_id=n
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}
	
    public function getCurrent() {
		$data[$this->actionName] = array(
			"status"	=> "ok",
			"message"	=> "Test ongoing.",
			"value"		=> "testValue"
		);
		
		return $data;
    }
}

?>