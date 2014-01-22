<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_process extends sensor {
	public $process;
	
	function __construct($actionName, $flow_id) {
		// flow_id=21
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
		$this->process	= array(
				"[m]inerd"						=> "minerd",
				"[p]erl mosquitto_client.pl"	=> "mosquitto Triggers",
				"[m]osquitto -c /etc/mosquitto/mosquitto.conf"				=> "mosquitto Server",
				"[y]VirtualHub/VirtualHub -d -g /var/log/virtualhub.log"	=> "virtualhub",
				"[m]eteord daemon"				=> "meteor Server",
				"[s]shd"						=> "SSH Server",
				"[a]pache2 -k start"			=> "apache2 Server"
			);
	}
	
	function setProcess($process=null) {
		if ( isset($process) ) {
			$this->process	= array($process);
			return true;
		} else {
			return false;
		}
	}
/*

 */
	public function getCurrent($process=null) {
		if ( isset($process) ) {
			$this->setProcess($process);
		}
		$data[$this->actionName]=array("status" => "ok");
		$data[$this->actionName]["process"] = array();
		foreach( $this->process as $proc => $name ) {
			$exec = sprintf("ps -A -o pid,cmd|grep '%s'", $proc);
			exec($exec, $out, $result);
			if ( $result == 0 ) {
				if ( preg_match("/([0-9]+) (.*)/i", $out[0], $matches) ) {
					$pid = $matches[1];
				} else {
					$pid = "null";
				}
			} else {
				$pid = "null";
			}
			$data[$this->actionName]["process"][]=array("proc" => $proc, "pid" => $pid, "name" => $name);
			unset($out);
			unset($pid);
			unset($name);
			unset($result);
		}
		return $data;
	}
}

?>