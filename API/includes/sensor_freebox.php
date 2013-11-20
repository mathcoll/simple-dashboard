<?php

require(dirname(__FILE__) . '/sensor.php');

class sensor_freebox extends sensor {
	public $uptime;
	public $downrate;
	public $uprate;
	public $downmargin;
	public $upmargin;
	public $downattn;
	public $upattn;
	public $downfec;
	public $upfec;
	public $downcrc;
	public $upcrc;
	public $downhec;
	public $uphec;

	function __construct($actionName, $flow_id) {
		$this->flow_id		= $flow_id;
		$this->actionName	= $actionName;
	}

	public function getCurrent() {
		$data=array();
		
		$rh = fopen("http://mafreebox.freebox.fr/pub/fbx_info.txt", "rb");
		if ($rh===false) {
			$data = array("status" => "error", "message" => "Empty ressource");
			return $data;
		} else {
			while (($buffer = fgets($rh, 4096)) !== false) {
				if( preg_match("/Temps.*route +(.*)/", $buffer, $m) ) {
					$this->uptime	= 0;
					if( preg_match("/([0-9]+) minutes?/", $m[1], $n) ) {
						$this->uptime	+= $n[1];
					}
					if( preg_match("/([0-9]+) heures?/", $m[1], $n) ) {
						$this->uptime	+= $n[1]*60;
					}
					if( preg_match("/([0-9]+) jours?/", $m[1], $n) ) {
						$this->uptime	+= $n[1]*24*60;
					}
					$this->uptime = time() - $this->uptime * 60;
				} else if( preg_match("/bit ATM +([0-9]+) kb\/s +([0-9]+) kb\/s/", $buffer, $m) ) {
					$this->downrate		= $m[1];
					$this->uprate		= $m[2];
				} else if( preg_match("/Marge.*bruit +([0-9.]+) dB +([0-9.]+) dB/", $buffer, $m) ) {
					$this->downmargin	= $m[1];
					$this->upmargin		= $m[2];
				} else if( preg_match("/nuation +([0-9.]+) dB +([0-9.]+) dB/", $buffer, $m) ) {
					$this->downattn		= $m[1];
					$this->upattn		= $m[2];
				} else if( preg_match("/FEC +([0-9]+) +([0-9]+)/", $buffer, $m) ) {
					$this->downfec		= $m[1];
					$this->upfec		= $m[2];
				} else if( preg_match("/CRC +([0-9]+) +([0-9]+)/", $buffer, $m) ) {
					$this->downcrc		= $m[1];
					$this->upcrc		= $m[2];
				} else if( preg_match("/HEC +([0-9]+) +([0-9]+)/", $buffer, $m) ) {
					$this->downhec		= $m[1];
					$this->uphec		= $m[2];
				}
			}
			fclose($rh);
		}

		$data[$this->actionName] = array(
			"uptime"		=> $this->uptime,
			"downrate"		=> $this->downrate,
			"uprate"		=> $this->uprate,
			"downmargin"	=> $this->downmargin,
			"upmargin"		=> $this->upmargin,
			"downattn"		=> $this->downattn,
			"upattn"		=> $this->upattn,
			"downfec"		=> $this->downfec,
			"upfec"			=> $this->upfec,
			"downcrc"		=> $this->downcrc,
			"upcrc"			=> $this->upcrc,
			"downhec"		=> $this->downhec,
			"uphec"			=> $this->uphec,
		);
		return $data;
	}
}

?>