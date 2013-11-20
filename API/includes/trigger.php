<?php

class trigger {
	public $db;
	public $data;
	public $previousValue;

	function __construct() {
		$this->db = new db(dirname(__FILE__) . "/../../data/dashboard.db");
	}

	public function triggerAction($trigger_id, $timestamp, $value, $previousValue) {
		$this->value = $value;
		$this->previousValue = $previousValue;
		if ( isset($trigger_id) ) {
			$q = sprintf("SELECT u.*, f.mqtt_topic as topic, t.* FROM triggers t LEFT JOIN users u ON (t.user_id = u.user_id) LEFT JOIN flows f ON(t.flow_id = f.flow_id) WHERE t.trigger_id = %d LIMIT 1", $trigger_id);
			//print $q;
			global $data;
			foreach ($this->db->query($q) as $row) {
				$meta = json_decode($row["meta"]);
				//print_r($meta);
				$this->data[]=array(
						'trigger_id'		=> $row['trigger_id'],
						'minthreshold'		=> $row['minthreshold'],
						'maxthreshold'		=> isset($row['maxthreshold'])?$row['maxthreshold']:"",
						'flow_id'			=> $row['flow_id'],
						'user_id'			=> $row['user_id'],
						'topic'				=> $row['topic'],
						'email'				=> $row['email'],
						'prenom'			=> $row['prenom'],
						'nom'				=> $row['nom'],
						'event'				=> $row['event'],
						'exitOnAlert'		=> isset($row['exitOnAlert'])?$row['exitOnAlert']:"",
						'logEventToFlow_id'	=> isset($row['logEventToFlow_id'])?$row['logEventToFlow_id']:"",
						'sort'				=> isset($row['sort'])?$row['sort']:"",
						'meta'				=> $row["meta"],
						'action'			=> $row['action'],
						'date'				=> date("d/m/Y H:i", $timestamp),
						'bcc'				=> @$meta->{"bcc"},
						'value'				=> $this->value,
						'trend'				=> ""
								);
				if( $this->previousValue < $this->value ) $this->data[0]["trend"] = "increasing";
				elseif( $this->previousValue > $this->value ) $this->data[0]["trend"] = "decreasing";
				else $this->data[0]["trend"] = "linear";
			}
			if ( isset($this->data[0]) ) {
				$event = $this->data[0]["event"];
				switch( $this->data[0]["action"] ) {
					case "twitter":
						#######
						break;

					case "sms":
						global $data;
						$data = $this->data;
						$data[0]["meta"] = preg_replace_callback("/%(.*?)%/", function($varname) {
							$val = $varname[1];
							global $data;
							if( isset($data[0][$val])) {
								return $data[0][$val];
							} else {
								return "$".$varname[1];
							}
						}, $data[0]["meta"]);

							$data[0]["meta"] = json_decode($data[0]["meta"]);
							$meta = $data[0]["meta"];

							$rootDirGGLAlert = dirname(__FILE__) . "/googalert/";
							$command = sprintf(
									"%sgoogalert %s -c %sgoogalert.conf -l \"%s\" -p \"%s\" -m \"%s\" -a \"%s\" \"%s\" \"%s\"",
									$rootDirGGLAlert,
									$_GET["debug"]==true?"-D":"",
									$rootDirGGLAlert,
									$meta->{"calendar_login"},
									$meta->{"calendar_password"},
									$meta->{"author_email"},
									$meta->{"calendar"},
										
									$meta->{"subject"},
									$meta->{"body"}
							);
							exec($command, $out, $result);
							if ( $result == 0 ) {
								$this->data = array("status" => "ok", "message" => $command);
								return $this->data;
							} else {
								$this->data = array("status" => "error", "message" => $command);
								return $this->data;
							}

							break;

					case "mail":
					default:
						global $data;
						$data = $this->data;
						$data[0]["meta"] = preg_replace_callback("/%(.*?)%/", function($varname) {
							$val = $varname[1];
							global $data;
							if( isset($data[0][$val])) {
								return $data[0][$val];
							} else {
								return "$".$varname[1];
							}
						}, $data[0]["meta"]);

							$data[0]["meta"] = json_decode($data[0]["meta"]);
							$meta = $data[0]["meta"];

							$headers  = "MIME-Version: 1.0" . "\r\n";
							$headers .= "Content-type: text/plain; charset=utf-8" . "\r\n";
							$headers .= "To: ".$this->data[0]["prenom"]." ".$this->data[0]["nom"]." <".$this->data[0]["email"].">" . "\r\n";
							$headers .= "From: Mathieu L. <mathieu@internetcollaboratif.info>" . "\r\n";
							if ( $this->data[0]["bcc"] != null ) {
								$headers .= "Bcc: ".$this->data[0]["bcc"] . "\r\n";
							}

							//print $meta->{"subject"};
							//exit;
							if ( mail($this->data[0]["email"], $meta->{"subject"}, $meta->{"body"}, $headers) ) {
								$this->data[0]["status"] = "ok";
							} else {
								$this->data[0]["status"] = "error";
								$this->data[0]["message"] = "email not sent !!" . $meta->{"body"};
							}
							break;
				}
				$this->data = $data;
				return $this->data;
			} else {
				$this->data = array("status" => "error", "message" => "trigger_id '".$trigger_id."' was not found");
				return $this->data;
			}
		} else {
			$this->data = array("status" => "error", "message" => "trigger_id is unefined");
			return $this->data;
		}
		return $this->data;
	}
}

?>