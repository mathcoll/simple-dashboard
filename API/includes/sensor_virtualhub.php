<?php

require(dirname(__FILE__) . '/sensor.php');
require(dirname(__FILE__) . '/yoctolib/Sources/yocto_api.php');

class sensor_virtualhub extends sensor {
	public $serial;
	
	function __construct($actionName, $flow_id) {
		// flow_id=null
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}
	
    public function getCurrent($serial=null) {
		$this->serial		= $serial;
		$data=array();
		yDisableExceptions();

		// Setup the API to use the VirtualHub on local machine
		if(yRegisterHub('http://127.0.0.1:4444/', $errmsg) != YAPI_SUCCESS) {
			$data = array("status" => "error", "message" => "Cannot contact VirtualHub on 127.0.0.1");
			return $data;
			exit();
		}
		
		if ( !$serial ) {
			$module = yFirstModule();
			$data[$this->actionName]["modules"] = array();
			while (!is_null($module)) {
				array_push($data[$this->actionName]["modules"], $this->getModule($module));
				$module = $module->nextModule();
			}
			return $data;
			exit();
			
		} else {
			$module = yFindModule("$serial");
			if ( !$module->isOnline() ) {
				$data = array("status" => "error", "message" => "Module not connected (check serial and USB cable)");
				if ( $_GET["debug"] != "true" ) return json_encode($data);
				else return $data;
				exit();
			}
			
			$data[$this->actionName] = $this->getModule($module);
			return $data;
		}
    }
    
    public function getModule($module=null) {
    	if( $module ) {
    		return array(
				'firmwareRelease'		=> $module->get_firmwareRelease(),
				'luminosity'			=> $module->get_luminosity(),
				'beacon'				=> $module->get_beacon(),
				'upTime'				=> $module->get_upTime(),
				'productName'			=> $module->get_productName(),
				'serialNumber'			=> $module->get_serialNumber(),
				'logicalName'			=> $module->get_logicalName(),
				'productId'				=> $module->get_productId(),
				'productRelease'		=> $module->get_productRelease(),
				'persistentSettings'	=> $module->get_persistentSettings(),
				'usbBandwidth'			=> $module->get_usbBandwidth(),
				'usbCurrent'			=> $module->get_usbCurrent(),
				'rebootCountdown'		=> $module->get_rebootCountdown(),
				//'icon2d'				=> $module->get_icon2d(),
				'yGetAPIVersion'		=> yGetAPIVersion(),
				'inventory'				=> yGetAPIVersion(),
			);
			//$fp = fopen("/tmp/test.png", "w+");
			//fwrite($fp, $module->get_icon2d());
			//fclose($fp);
    	}
    }
}

?>