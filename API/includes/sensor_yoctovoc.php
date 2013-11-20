<?php

require(dirname(__FILE__) . '/sensor.php');
require(dirname(__FILE__) . '/yoctolib/Sources/yocto_api.php');
require(dirname(__FILE__) . '/yoctolib/Sources/yocto_voc.php');

class sensor_yoctovoc extends sensor {

	function __construct($actionName, $flow_id) {
		// flow_id=5
		$this->actionName	= $actionName;
		$this->flow_id		= $flow_id;
	}

	public function getCurrent() {
		$serial = isset($_GET['serial'])?$_GET['serial']:""; #TODO, send as parameter
		$data=array();
		if (  !$serial ) {
			$data = array("status" => "error", "message" => "Mandatory input data missing.");
			if ( $_GET["debug"] != "true" ) return json_encode($data);
			else return $data;
			exit();
		}

		yDisableExceptions();

		// Setup the API to use the VirtualHub on local machine
		if(yRegisterHub('http://127.0.0.1:4444/', $errmsg) != YAPI_SUCCESS) {
			$data = array("status" => "error", "message" => "Cannot contact VirtualHub on 127.0.0.1");
			if ( $_GET["debug"] != "true" ) return json_encode($data);
			else return $data;
			exit();
		}
		$voc = yFindVoc("$serial.voc");
		if (!$voc->isOnline()) {
			$data = array("status" => "error", "message" => "Module not connected (check serial and USB cable)");
			if ( $_GET["debug"] != "true" ) return json_encode($data);
			else return $data;
			exit();
		}
		$module = yFirstModule();
		if( $module ) { // skip VirtualHub
			$module = $module->nextModule();
		}
		$data[$this->actionName] = array(
				'productName'       => $module->get_productName(),
				'serialNumber'      => $module->get_serialNumber(),
				'logicalName'       => $module->get_logicalName(),
				'productId'         => $module->get_productId(),
				'productRelease'    => $module->get_productRelease(),
				'firmwareRelease'   => $module->get_firmwareRelease(),
				'persistentSettings'=> $module->get_persistentSettings(),
				'luminosity'        => $module->get_luminosity(),
				'beacon'            => $module->get_beacon(),
				'upTime'            => $module->get_upTime(),
				'usbCurrent'        => $module->get_usbCurrent(),
				'rebootCountdown'   => $module->get_rebootCountdown(),
				'usbBandwidth'      => $module->get_usbBandwidth(),
				//'APIVersion'        => $module->yGetAPIVersion(),

				'logicalName'       => $voc->get_logicalName(),
				'advertisedValue'   => $voc->get_advertisedValue(),
				'unit'              => $voc->get_unit(),
				'currentValue'      => $voc->get_currentValue(),
				'lowestValue'       => $voc->get_lowestValue(),
				'highestValue'      => $voc->get_highestValue(),
				'currentRawValue'   => $voc->get_currentRawValue(),
				'resolution'        => $voc->get_resolution(),
				'calibrationParam'  => $voc->get_calibrationParam(),
		);

		return $data;
	}
}

?>