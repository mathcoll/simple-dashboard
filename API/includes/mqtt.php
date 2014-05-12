<?php


class mqtt {
	public $mosquitto_pub			= "/usr/bin/mosquitto_pub";
	public $mosquitto_port			= 1883;
	public $mosquitto_host			= "guru";
	public $mosquitto_identifier	= "mosquitto_client";
	public $mosquitto_topic			= "couleurs/%s/%s";
	public $device					= "defaultDevice";
	public $channel					= "defaultChannel";

	function __construct() {
	}

	public function publish($ts, $value, $device, $channel) {
		//print join(" ", array($ts, $value));
		$mosquitto_topic = sprintf($this->mosquitto_topic, isset($device)?$device:$this->device, isset($channel)?$channel:$this->channel);
		
		$exec = sprintf(
				"%s --retain --port %d --host %s --id %s --topic %s --message \"%s\"",
				$this->mosquitto_pub,
				$this->mosquitto_port,
				$this->mosquitto_host,
				$this->mosquitto_identifier,
				$mosquitto_topic,
				$value
		);
		//print "\n".$exec."\n";
		//print "\n".$value."\n";
		//print "\nEXEC\n";
		exec($exec);
		//print "\nEND\n";
	}
}

?>
