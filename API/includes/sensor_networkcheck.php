<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_networkcheck extends sensor {
	public $ip;
	
	function __construct($actionName, $flow_id) {
		// flow_id=2
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}
/*

 */
	public function getCurrent($ip="192.168.0.100") {
		$this->ip = $ip;
		$exec = sprintf("ping %s -c 1", $this->ip);
		exec($exec, $out, $result);
		if ( $result == 0 ) {
			if ( !preg_match("/Destination Host Unreachable/", $out[0]) ) {
				$data[$this->actionName]=array("status" => "ok", "ipv4" => $this->ip, "value" => "active");
			} else {
				$data[$this->actionName]=array("status" => "ok", "ipv4" => $this->ip, "value" => "inactive");
			}
		} else {
			$data[$this->actionName]=array("status" => "ok", "ipv4" => $this->ip, "value" => "inactive");
		}
		return $data;
	}
}

?>