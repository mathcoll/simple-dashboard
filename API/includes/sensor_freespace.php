<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_freespace extends sensor {
	
	function __construct($actionName, $flow_id) {
		// flow_id=2
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}
	
    public function getCurrent() {
		$exec = "df -t rootfs | tail -1 | cut -d: -f2 | awk '{ print $4}'";
		exec($exec, $out, $result);
		if ( $result == 0 ) {
			$this->result = isset($out[0]) ? $out[0] : 0;
			$data[$this->actionName]=array("status" => "ok", "value" => $this->result);
			
			return $data;
		}
    }
}

?>