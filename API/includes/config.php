<?php

class config {
	public $db;
	public $data;

	function __construct() {
		$this->db = new db(dirname(__FILE__) . "/../../data/dashboard.db");
	}

	public function getDevices() {
		$q = "SELECT devices.*, users.prenom, users.nom FROM devices LEFT JOIN users ON (devices.user_id = users.user_id) ORDER BY device_id ASC";
		//print $q;
		foreach ($this->db->query($q) as $row) {
			$this->data["getDevices"][] = array(
					'prenom'	=> $row['prenom'],
					'nom'		=> $row['nom'],
					'device_id'	=> $row['device_id'],
					'name'		=> $row['name'],
					'position'	=> $row['position'],
					'ipv4'		=> $row['ipv4'],
					'ipv6'		=> $row['ipv6']
			);
		}
		return $this->data;
	}

	public function getFlows($flow_id=null) {
		if ( !isset($flow_id) ) {
			$q = "SELECT data_types.name as data_type_name, users.prenom, users.nom, flows.*, units.name AS unit_name, units.format AS unit_format, metas.value as flow_name FROM flows
					LEFT JOIN metas ON metas.element_id=flows.flow_id
					LEFT JOIN units ON flows.unit_id = units.unit_id
					LEFT JOIN users ON flows.user_id = users.user_id
					LEFT JOIN data_types ON data_types.data_type_id = flows.data_type_id
					WHERE (metas.element='flows') AND (metas.name='flow_name')";
		} else {
			$q = sprintf("SELECT data_types.name as data_type_name, users.prenom, users.nom, flows.*, units.name AS unit_name, units.format AS unit_format, metas.value as flow_name FROM flows
					LEFT JOIN units ON flows.unit_id = units.unit_id
					LEFT JOIN users ON flows.user_id = users.user_id
					LEFT JOIN data_types ON data_types.data_type_id = flows.data_type_id
					WHERE (metas.element='flows') AND (metas.name='flow_name') AND flows.flow_id=%d", $flow_id);
		}
		//print $q;
		foreach ($this->db->query($q) as $row) {
			$this->data["getFlows"][]=array(
					'prenom'			=> $row['prenom'],
					'nom'				=> $row['nom'],
					'flow_id'			=> $row['flow_id'],
					'unit_id'			=> $row['unit_id'],
					'unit_format'		=> $row['unit_format'],
					'user_id'			=> $row['user_id'],
					'data_type_id'		=> $row['data_type_id'],
					'unit_name'			=> $row['unit_name'],
					'flow_name'			=> $row['flow_name'],
					'data_type_name'	=> $row['data_type_name'],
					'mqtt_topic'		=> $row['mqtt_topic']
			);
		}
		return $this->data;
	}

	public function getDataTypes() {
		$q = "SELECT * FROM data_types ORDER BY name ASC";
		//print $q;
		foreach ($this->db->query($q) as $row) {
			$this->data["getDataTypes"][] = array(
					'data_type_id'	=> $row['data_type_id'],
					'name'			=> $row['name']
			);
		}
		return $this->data;
	}

	public function getUnits() {
		$q = "SELECT units.unit_id, units.name, units.format, metas.value as type FROM units LEFT JOIN metas ON metas.element_id=units.unit_id WHERE metas.element='units' AND metas.name = 'type' ORDER BY units.name ASC";
		//print $q;
		foreach ($this->db->query($q) as $row) {
			$this->data["getUnits"][] = array(
					'unit_id'	=> $row['unit_id'],
					'name'		=> $row['name'],
					'format'	=> $row['format'],
					'type'		=> $row['type']
			);
		}
		return $this->data;
	}

	public function getTriggers($trigger_id, $filter_enable) {
		if ( isset($trigger_id) && isset($filter_enable) ) {
			$where = " WHERE t.trigger_id = ".$trigger_id." AND t.enable=1";
		} else if ( isset($filter_enable) ) {
			$where = " WHERE t.enable=1";
		} else if ( isset($trigger_id) ) {
			$where = " WHERE t.trigger_id = ".$trigger_id;
		} else {
			$where = "";
		}
		$q = "SELECT f.mqtt_topic as topic, t.*, users.prenom, users.nom FROM triggers t LEFT JOIN users ON (t.user_id = users.user_id) LEFT JOIN flows f ON(t.flow_id = f.flow_id) ".$where." ORDER BY topic, t.sort ASC";
		//print $q;
		foreach ($this->db->query($q) as $row) {
			$this->data["getTriggers"][] = array(
					'prenom'			=> $row['prenom'],
					'nom'				=> $row['nom'],
					'trigger_id'		=> $row['trigger_id'],
					'event'				=> $row['event'],
					'action'			=> $row['action'],
					'flow_id'			=> $row['flow_id'],
					'topic'				=> $row['topic'], // from flow
					'exitOnAlert'		=> $row['exitOnAlert'],
					'logEventToFlow_id'	=> $row['logEventToFlow_id'],
					'sort'				=> $row['sort'],
					'meta'				=> $row['meta'],
					'enable'			=> isset($row['enable'])?$row['enable']:null,
					'maxthreshold'		=> isset($row['maxthreshold'])?$row['maxthreshold']:null,
					'minthreshold'		=> isset($row['minthreshold'])?$row['minthreshold']:null
			);
		}
		return $this->data;
	}

	public function addDevice($username, $password, $name, $position, $ipv6, $ipv4) {
		if ( !$name || !$username || !$password ) {
			$this->data = array("status" => "error", "message" => "Mandatory input data missing.");
			return $this->data;
		}

		// check username & password
		$q = sprintf("SELECT user_id FROM users WHERE login='%s' AND password='%s' LIMIT 1", $username, md5($password));
		foreach ($this->db->query($q) as $row) {
			$user_id = $row['user_id'];
		}
		if ( !$user_id || $user_id < 0 ) {
			$this->data = array("status" => "error", "message" => "Login/password is invalid.");
			return $this->data;
		}

		if ( isset($name) ) {
			$q = sprintf("INSERT INTO devices ('name', 'user_id', 'position', 'ipv6', 'ipv4') VALUES('%s', '%d', '%s', '%s', '%s')", $name, $user_id, $position, $ipv6, $ipv4);
			if ( $this->db->exec($q) ) {
				$device_id = $this->db->lastInsertId();
				$this->data = array("status" => "ok", "device_id" => $device_id);
			} else {
				$this->data = array("status" => "error", "message" => $this->db->errorInfo());
			}
		} else {
			$this->data = array("status" => "error", "message" => "error adding device: empty device 'name'");
		}
		return $this->data;
	}

	public function addTrigger($username, $password, $meta, $flow_id, $maxthreshold, $name, $event, $triggerAction, $exitOnAlert, $logEventToFlow_id, $sort, $enable) {
		if ( !$username || !$password || !$event || !$triggerAction ) {
			$this->data = array("status" => "error", "message" => "Mandatory input data missing.");
			return $this->data;
		}

		$q = sprintf("INSERT INTO triggers ('event', 'action', 'user_id', 'maxthreshold', 'minthreshold', 'flow_id', 'exitOnAlert', 'logEventToFlow_id', 'sort', 'meta', 'enable') VALUES('%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d')", $event, $triggerAction, $user_id, $maxthreshold, $minthreshold, $flow_id, $exitOnAlert, $logEventToFlow_id, $sort, $meta, $enable);
		if ( $this->db->exec($q) ) {
			$trigger_id = $this->db->lastInsertId();
			$this->data = array("status" => "ok", "trigger_id" => $trigger_id);
		} else {
			$this->data = array("status" => "error", "message" => $this->db->errorInfo());
		}
		return $this->data;
	}

	public function removeTrigger($trigger_id) {
		$q = sprintf("DELETE FROM triggers WHERE trigger_id = %d", $trigger_id);
		if ( $this->db->exec($q) ) {
			$this->data = array("status" => "ok");
		} else {
			$this->data = array("status" => "error", "message" => $this->db->errorInfo());
		}
		return $this->data;
	}

	public function addFlow($name, $username, $password, $unit_id, $mqtt_topic, $data_type_id) {
		if ( !$name || !$username || !$password || !$unit_id || !$data_type_id ) {
			$this->data = array("status" => "error", "message" => "Mandatory input data missing.");
			return $this->data;
		}

		// check username & password
		$q = sprintf("SELECT user_id FROM users WHERE login='%s' AND password='%s' LIMIT 1", $username, md5($password));
		foreach ($this->db->query($q) as $row) {
			$user_id = $row['user_id'];
		}
		if ( !$user_id || $user_id < 0 ) {
			$this->data = array("status" => "error", "message" => "Login/password is invalid.");
			return $this->data;
		}

		// INSERT flows
		$q = sprintf("INSERT INTO flows ('unit_id', 'user_id', 'data_type_id', 'mqtt_topic') VALUES('%d', '%d', '%d', '%s')", $unit_id, $user_id, $data_type_id, $mqtt_topic);
		if ( $this->db->exec($q) ) {
			$flow_id = $this->db->lastInsertId();
		} else {
			$this->data = array("status" => "error", "message" => $this->db->errorInfo());
			return $this->data;
		}

		// INSERT metas
		$q = sprintf("INSERT INTO metas ('element', 'element_id', 'name', 'value') VALUES ('flows', '%d', 'flow_name', '%s')", $flow_id, $name);
		if ( $this->db->exec($q) ) {
			$metas_id = $this->db->lastInsertId();
		} else {
			$this->data = array("status" => "error", "message" => $this->db->errorInfo());
			return $this->data;
		}

		return array("status" => "ok", "flow_id" => $flow_id);
	}
}

?>