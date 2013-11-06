<?php
error_reporting(0);
ini_set("display_errors", 0);
require(dirname(__FILE__) . "/includes/mqtt.php");
require(dirname(__FILE__) . "/includes/config.php");
require(dirname(__FILE__) . "/includes/db.php");
require(dirname(__FILE__) . "/includes/trigger.php");

function output($data, $object=null) {
	if ( PHP_SAPI !== "cli" ) {
		if ( $_GET["debug"] != "true" ) {
			echo json_encode($data, $object);
		} else {
			print_r($data);
		}
	} else {
		if ( $_GET["debug"] != "true" ) echo json_encode($data, $object);
		else print_r($data);
	}
}

// Hack for old php versions to use boolval()
// (PHP 5 >= 5.5.0)
if (!function_exists('boolval')) {
	function boolval($var) {
		return $var=="true"?true:false;
	}
}

if ( isset($argv) ) {
	foreach($argv AS $id => $parameter) {
		if (preg_match_all("/--([^\s]+)=\"?([^\s]+)\"?/", $parameter, $match) ) {
			$pName = $match[1][0];
			$pVal = $match[2][0];
			$_GET[$pName] = $pVal;
			$_POST[$pName] = $pVal;
		}
	}
}

if ( $_GET["debug"] == "true" ) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	if ( isset($argv) ) {
		print_r($_GET);
	}
}

if ( PHP_SAPI !== "cli" ) {
	header("Content-type: application/json");
	if ( @$_GET["no-cache"] == "true" || !isset($_GET["no-cache"]) ) {
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
}

$db					= new db(dirname(__FILE__) . "/../data/dashboard.db");
$mqtt				= new mqtt();
$config				= new config();
$trigger			= new trigger();

$action				= @$_GET["action"];
$data_type_id		= @isset($_POST["data_type_id"])?intval($_POST["data_type_id"]):"";
$enable				= @isset($_POST["enable"])?intval($_POST["enable"]):"";
$event				= @isset($_POST["event"])?$_POST["event"]:"";
$exitOnAlert		= @isset($_POST["exitOnAlert"])?intval($_POST["exitOnAlert"]):"";
$filter_enable		= @isset($_POST["filter_enable"])?@$_POST["filter_enable"]:@$_GET["filter_enable"];
$flow_id			= @isset($_GET['flow_id'])?intval($_GET['flow_id']):null;
$ipv4				= @isset($_POST["ipv4"])?$_POST["ipv4"]:@$_GET["ipv4"];
$ipv6				= @isset($_POST["ipv6"])?$_POST["ipv6"]:@$_GET["ipv6"];
$json_force_object	= @isset($_POST["json_force_object"])?$_POST["json_force_object"]:@$_GET["json_force_object"];
$limit				= @isset($_GET['limit'])?intval($_GET['limit']):10;
$logEventToFlow_id	= @isset($_POST["logEventToFlow_id"])?intval($_POST["logEventToFlow_id"]):"";
$maxthreshold		= @isset($_POST["maxthreshold"])?$_POST["maxthreshold"]:@$_GET["maxthreshold"];
$meta				= @isset($_POST["meta"])?$_POST["meta"]:"";
$minthreshold		= @isset($_POST["minthreshold"])?$_POST["minthreshold"]:@$_GET["minthreshold"];
$mqtt_topic			= @isset($_POST["mqtt_topic"])?$_POST["mqtt_topic"]:"";
$name				= @isset($_POST["name"])?$_POST["name"]:@$_GET["name"];
$password			= @isset($_POST["password"])?$_POST["password"]:"";
$position			= @isset($_POST["position"])?$_POST["position"]:@$_GET["position"];
$publish			= @isset($_GET["publish"])?boolval($_GET["publish"]):false;
$previousValue		= @isset($_POST["previousValue"])?$_POST["previousValue"]:"";
$save				= @isset($_GET["save"])?boolval($_GET["save"]):false;
$since				= @isset($_GET["since"])?$_GET["since"]:"";
$sort				= @isset($_POST["sort"])?intval($_POST["sort"]):"";
$timestamp			= @isset($_POST["timestamp"])?intval($_POST["timestamp"]):null;
$trigger_id			= @isset($_POST['trigger_id'])?intval($_POST['trigger_id']):@$_GET["trigger_id"];
$triggerAction		= @isset($_POST["triggerAction"])?$_POST["triggerAction"]:"";
$unit_id			= @isset($_POST["unit_id"])?intval($_POST["unit_id"]):"";
$username			= @isset($_POST["username"])?$_POST["username"]:"";
$value				= @isset($_POST["value"])?$_POST["value"]:"";



if ( $json_force_object == "true" ) {
	$json_force_object = JSON_FORCE_OBJECT;
} else {
	$json_force_object = null;
}

if( !$action ) {
	$data = array("status" => "error", "message" => "Mandatory input data missing (action).");
	if ( $_GET["debug"] != "true" ) echo json_encode($data);
	else echo print_r($data);
	exit();
}

if ( isset($since) ) {
	preg_match_all("/(\d+)(\w+)/", $since, $match);
	$sinceVal = @intval($match[1][0]);
	$sincePeriod = @$match[2][0];

	//print $since."<br />";
	//print $sinceVal."<br />";
	//print $sincePeriod."<br />";

	switch ( $sincePeriod ) {
		case "Y":
			$sinceTimestamp = mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y')-$sinceVal);
			break;

		case "m":
			$sinceTimestamp = mktime(date("H"), date("i"), date("s"), date('m')-$sinceVal, date('d'), date('Y'));
			break;

		case "d":
			$sinceTimestamp = mktime(date("H"), date("i"), date("s"), date('m'), date('d')-$sinceVal, date('Y'));
			break;

		case "h":
		default:
			$sinceTimestamp = mktime(date("H")-$sinceVal, date("i"), date("s"), date('m'), date('d'), date('Y'));
			break;
	}
	//print $sinceTimestamp;
}

switch ( $action ) {
	case "test":
		require(dirname(__FILE__) . "/includes/sensor_test.php");
		$flow_id = null;
		$sensor = new sensor_test($action, $flow_id);
		$data = $sensor->getCurrent();
		$timestamp = time();
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, $data[$action]["value"], "testDevice", "testChannel");
		}
		output($data, JSON_FORCE_OBJECT);
	break;
	
	case "triggerAction":
		$data = $trigger->triggerAction($trigger_id, $timestamp, $value, $previousValue);
		output($data);
	break;
	
	
	case "setData":
	case "addData":
		$data = $db->setData($timestamp, $value, $flow_id);
		output($data);
	break;
	
	case "getDevices":
		$data = $config->getDevices();
		output($data);
	break;
	
	case "addDevice":
		$data = $config->addDevice($username, $password, $name, $position, $ipv6, $ipv4);
		output($data);
	break;

	case "getFlows":
		$data = $config->getFlows($flow_id);
		output($data);
	break;

	case "addFlow":
		$data = $config->addFlow($name, $username, $password, $unit_id, $mqtt_topic, $data_type_id);
		output($data);
	break;

	case "getDataTypes":
		$data = $config->getDataTypes();
		output($data);
	break;

	case "getUnits":
		$data = $config->getUnits();
		output($data);
	break;

	case "getTriggers":
		$data = $config->getTriggers($trigger_id, $filter_enable);
		output($data);
	break;
	
	case "addTrigger":
		$data = $config->addTrigger($username, $password, $meta, $flow_id, $maxthreshold, $name, $event, $triggerAction, $exitOnAlert, $logEventToFlow_id, $sort, $enable);
		output($data);
	break;
	
	case "removeTrigger":
		$data = $config->removeTrigger($trigger_id);
		output($data);
	break;

	case "getData":
		$data = $db->getData($since, $sinceTimestamp, $flow_id);
		output($data);
	break;

	case "getAverage": ## only for integer
	case "getAvg": ## only for integer
		$data = $db->getAvg($since, $sinceTimestamp, $flow_id);
		output($data);
	break;

	case "getMinimum": ## only for integer
	case "getMin": ## only for integer
		$data = $db->getMax($since, $sinceTimestamp, $flow_id);
		output($data);
	break;

	case "getMaximum": ## only for integer
	case "getMax": ## only for integer
		$data = $db->getMax($since, $sinceTimestamp, $flow_id);
		output($data);
	break;
	
	case "getEatingCPU":
	case "eating-cpu":
		require(dirname(__FILE__) . "/includes/sensor_cpu.php");
		$flow_id = null;
		$sensor = new sensor_cpu($action, $flow_id);
		$data = $sensor->getCurrent($limit);
		output($data);
	break;

	case "getFreeSpace":
	case "free-space-left":
		require(dirname(__FILE__) . "/includes/sensor_freespace.php");
		$flow_id = 2;
		$sensor = new sensor_freespace($action, $flow_id);
		$data = $sensor->getCurrent();
		$timestamp = time();
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, $data[$action]["value"], "guruplug", "freespace");
		}
		
		if ( $save == true ) {
			$db->save($timestamp, $data[$action]["value"], $flow_id);
		}
		output($data, JSON_FORCE_OBJECT);
	break;
	
	case "guruplug-started":
		// TODO
		// TODO
		// TODO
	break;

	case "getMemory":
	case "memory-usage":
		require(dirname(__FILE__) . "/includes/sensor_memoryusage.php");
		$flow_id = 4;
		$sensor = new sensor_memoryusage($action, $flow_id);
		$data = $sensor->getCurrent();
		$timestamp = time();
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, $data[$action]["value"], "guruplug", "memory_usage");
		}
		
		if ( $save == true ) {
			$db->save($timestamp, $data[$action]["value"], $flow_id);
		}
		output($data, JSON_FORCE_OBJECT);
	break;

	case "getVOC":
	case "yocto-voc":
		require(dirname(__FILE__) . "/includes/sensor_yoctovoc.php");
		$flow_id = 5;
		$sensor = new sensor_yoctovoc($action, $flow_id);
		$data = $sensor->getCurrent();
		$timestamp = time();
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, $data[$action]["currentValue"], "yoctovoc", "VOC");
		}
		
		if ( $save == true ) {
			$db->save($timestamp, $data[$action]["currentValue"], $flow_id);
		}
		output($data, JSON_FORCE_OBJECT);
	break;

	case "getVirtualHub":
		require(dirname(__FILE__) . "/includes/sensor_virtualhub.php");
		$flow_id = null;
		$sensor = new sensor_virtualhub($action, $flow_id);
		$data = $sensor->getCurrent(isset($_GET['serial'])?$_GET['serial']:"");
		$timestamp = time();
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, "value", "virtualhub", "name");
		}
		
		if ( $save == true ) {
			$db->save($timestamp, $data[$action], $flow_id);
		}
		output($data, JSON_FORCE_OBJECT);
	break;

	case "getTemp":
	case "meteo-degrees":
		require(dirname(__FILE__) . "/includes/sensor_meteodegrees.php");
		$flow_id = 8;
		$sensor = new sensor_meteodegrees("meteo-degrees", $flow_id);
		$data = $sensor->getCurrent();
		$timestamp = $data[$action]["ts"];
		
		if ( $publish == true ) {
			$mqtt->publish($timestamp, $data[$action]["temp"], "HTTP-get", "temperature");
		}
		
		if ( $save == true ) {
			$db->save($timestamp, $data[$action]["temp"], $flow_id);
		}
		output($data, JSON_FORCE_OBJECT);
	break;
}

exit();

?>