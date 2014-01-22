<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_memoryusage extends sensor {

	function __construct($actionName, $flow_id) {
		// flow_id=2
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}

	public function getCurrent($field="mem") {
		switch ($field) {
			case "cpu":
				$exec = "ps aux | awk '{sum +=$3}; END {print sum}'";
				break;
			default: 
			case "mem":
				$exec = "ps aux | awk '{sum +=$4}; END {print sum}'";
		}
		exec($exec, $out, $result);
		if ( $result == 0 ) {
			$this->result = isset($out[0])?$out[0]:0;
			$data[$this->actionName]=array("status" => "ok", "value" => $this->result);
				
			return $data;
		}
	}
}

?>