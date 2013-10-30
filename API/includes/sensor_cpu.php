<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_cpu extends sensor {
	
	function __construct($actionName, $flow_id) {
		// flow_id=n
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}
	
    public function getCurrent($limit) {
		$exec = sprintf("ps --no-heading -eo pcpu,pid,user,args | sort -k 1 -r | head -%d", $limit);
		$data[$this->actionName] = array(
			"status"	=> "ok"
		);
		exec($exec, $data[$this->actionName]["value"], $result);
		$data[$this->actionName]["value"] = array_map("ltrim", $data[$this->actionName]["value"]);
		return $data;
    }
}

?>