<?php


class db {
	public $dbFile;
	public $dbh;
	public $data = array();

	function __construct($dbFile=null) {
		$this->dbFile = !is_null($dbFile)?$dbFile:dirname(__FILE__) . "/../../data/dashboard.db";
		//print "->".$this->dbFile."<-\n\n";
		try {
			file_exists($this->dbFile);
			$this->dbh = new PDO("sqlite:".$this->dbFile);
		} catch (Exception $e) {
			print $this->dbFile."\n";
			$this->data["__construct"] = array("status" => "error", "message" => $e);
		}
		return $this->data;
	}

	public function query($query=null) {
		foreach ($this->dbh->query($query) as $row) {
			$this->data[] = $row;
		}
		return $this->data;
	}

	public function exec($query=null) {
		if ( isset($query) ) {
			return $this->dbh->exec($query);
		} else {
			return false;
		}
	}

	public function lastInsertId() {
		return $this->dbh->lastInsertId();
	}

	public function errorInfo() {
		return $this->dbh->errorInfo();
	}

	public function save($timestamp, $value, $flow_id) {
		if ( $timestamp!=NULL && $value!=NULL && $flow_id!=NULL ) {
			$q = sprintf("INSERT INTO data (timestamp, value, flow_id) VALUES (%d, '%s', %d)", $timestamp, $value, $flow_id);
			//print $q;
			if ( $this->dbh->exec($q) ) {
				$this->data["save"] = array("status" => "ok");
			} else {
				$this->data["save"] = array("status" => "error", "message" => $this->dbh->errorInfo());
			}
		} else {
			$this->data["save"] = array("status" => "error", "message" => "Mandatory input data missing.");
		}
		return $this->data;
	}

	public function setData($timestamp=null, $value=null, $flow_id=null) {
		if ( $timestamp!=NULL && $value!=NULL && $flow_id!=NULL ) {
			$q = sprintf("INSERT INTO data (timestamp, value, flow_id) VALUES (%d, '%s', %d)", $timestamp, $value, $flow_id);
			//print $q;
			if ( $this->dbh->exec($q) ) {
				$this->data["setData"] = array("status" => "ok");
			} else {
				$this->data["setData"] = array("status" => "error", "message" => $this->dbh->errorInfo());
			}
		} else {
			$this->data["setData"] = array("status" => "error", "message" => "Mandatory input data missing.");
		}
		return $this->data;
	}

	public function getData($since=null, $sinceTimestamp=null, $flow_id=null, $limit=null) {
		$this->data["getData"] = array();
		if ( !$flow_id || !$sinceTimestamp || !$since ) {
			$this->data["getData"] = array("status" => "error", "message" => "Mandatory input data missing.");
		} else {
			
			$q = sprintf("SELECT data.timestamp, data.value, users.prenom, users.nom, flows.mqtt_topic, metas.value AS title
					FROM data 
					JOIN flows ON (data.flow_id = flows.flow_id)
					JOIN users ON (flows.user_id = users.user_id)
					JOIN metas ON (metas.element_id = flows.flow_id)
					WHERE data.flow_id=%d AND data.timestamp >= %d AND metas.name='flow_name'
					ORDER BY data.timestamp DESC", $flow_id, $sinceTimestamp);

			//$q = sprintf("SELECT timestamp, value FROM data WHERE flow_id=%d AND timestamp >= %d ORDER BY timestamp DESC", $flow_id, $sinceTimestamp);
			if( isset($limit) && intval($limit) > 0 ) $q .= " LIMIT ".$limit;
			//print $q;

			foreach ($this->dbh->query($q) as $row) {
				$this->data["getData"]["values"][]=array($row['timestamp']."000", $row['value']);
				$author = join(" ", array($row['prenom'], $row['nom']));
				$mqtt_topic = $row['mqtt_topic'];
				$title = $row['title'];
			}

			$this->data["getData"]["author"]		= @$author;
			$this->data["getData"]["mqtt_topic"]	= @$mqtt_topic;
			$this->data["getData"]["title"]			= @$title;
		}
		return $this->data;
	}

	public function getAvg($since=null, $sinceTimestamp=null, $flow_id=null) {
		if ( !$flow_id || !$sinceTimestamp || !$since ) {
			$this->data["getAvg"] = array("status" => "error", "message" => "Mandatory input data missing.");
		} else {
			//$q = sprintf("SELECT timestamp, avg(cast(value as integer)) as value FROM data WHERE flow_id=%d AND timestamp >= %d ORDER BY timestamp DESC", $flow_id, $sinceTimestamp);
			$q = sprintf("SELECT strftime('%%d-%%m-%%Y', datetime(timestamp, 'unixepoch', 'localtime')) as date, timestamp, strftime('%%j', datetime(timestamp, 'unixepoch', 'localtime')) as j, avg(value) as value FROM data WHERE flow_id = %d AND timestamp >= %d GROUP BY j ORDER BY timestamp ASC", $flow_id, $sinceTimestamp);
			//print $q;
			foreach ($this->dbh->query($q) as $row) {
				$this->data["getAvg"][]=array($row['timestamp']."000", $row['value']);
			}
		}
		return $this->data;
	}

	public function getMin($since=null, $sinceTimestamp=null, $flow_id=null) {
		if ( !$flow_id || !$sinceTimestamp || !$since ) {
			$this->data["getMin"] = array("status" => "error", "message" => "Mandatory input data missing.");
		} else {
			$q = sprintf("SELECT timestamp, min(cast(value as integer)) as value FROM data WHERE flow_id=%d AND timestamp >= %d ORDER BY timestamp DESC", $flow_id, $sinceTimestamp);
			//print $q;
			foreach ($this->dbh->query($q) as $row) {
				$this->data["getMin"][]=array($row['timestamp']."000", $row['value']);
			}
		}
		return $this->data;
	}

	public function getMax($since=null, $sinceTimestamp=null, $flow_id=null) {
		if ( !$flow_id || !$sinceTimestamp || !$since ) {
			$this->data["getMax"] = array("status" => "error", "message" => "Mandatory input data missing.");
		} else {
			$q = sprintf("SELECT timestamp, max(cast(value as integer)) as value FROM data WHERE flow_id=%d AND timestamp >= %d ORDER BY timestamp DESC", $flow_id, $sinceTimestamp);
			//print $q;
			foreach ($this->dbh->query($q) as $row) {
				$this->data["getMax"][]=array($row['timestamp']."000", $row['value']);
			}
		}
		return $this->data;
	}
}

?>