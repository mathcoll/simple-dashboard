<?php
error_reporting(0);
ini_set("display_errors", 0);
require(dirname(__FILE__) . "/includes/mqtt.php");
require(dirname(__FILE__) . "/includes/config.php");
require(dirname(__FILE__) . "/includes/db.php");
require(dirname(__FILE__) . "/includes/trigger.php");

function output($data, $object=null, $actionSettings=null) {
	if ( $_GET["debug"] == "true" ) {
		if ( isset($actionSettings) ) {
			$data['name']			= $actionSettings['name'];
			$data['description']	= $actionSettings['description'];
			array_push($actionSettings['parameters'], "action", "debug");
			$data['parameters']		= $actionSettings['parameters'];
		}
		print_r($data);
	} else {
		echo json_encode($data, $object);
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
		if (preg_match_all("/-?-([^\s]+)=\"?([^\s]+)\"?/", $parameter, $match) ) {
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

$vars['db']					= new db(); //dirname(__FILE__) . "/../data/dashboard.db"
$vars['mqtt']				= new mqtt();
$vars['config']				= new config();
$vars['trigger']			= new trigger();

$vars['action']				= @$_GET["action"];
$functionName				= "ACTION_".$vars['action'];
$vars['data_type_id']		= @isset($_POST["data_type_id"])?intval($_POST["data_type_id"]):"";
$vars['enable']				= @isset($_POST["enable"])?intval($_POST["enable"]):"";
$vars['event']				= @isset($_POST["event"])?$_POST["event"]:"";
$vars['exitOnAlert']		= @isset($_POST["exitOnAlert"])?intval($_POST["exitOnAlert"]):"";
$vars['filter_enable']		= @isset($_POST["filter_enable"])?@$_POST["filter_enable"]:@$_GET["filter_enable"];
$vars['flow_id']			= @isset($_GET['flow_id'])?intval($_GET['flow_id']):null;
$vars['ipv4']				= @isset($_POST["ipv4"])?$_POST["ipv4"]:@$_GET["ipv4"];
$vars['ipv6']				= @isset($_POST["ipv6"])?$_POST["ipv6"]:@$_GET["ipv6"];
$vars['json_force_object']	= @isset($_POST["json_force_object"])?$_POST["json_force_object"]:@$_GET["json_force_object"];
$vars['limit']				= @isset($_GET['limit'])?intval($_GET['limit']):10;
$vars['logEventToFlow_id']	= @isset($_POST["logEventToFlow_id"])?intval($_POST["logEventToFlow_id"]):"";
$vars['maxthreshold']		= @isset($_POST["maxthreshold"])?$_POST["maxthreshold"]:@$_GET["maxthreshold"];
$vars['meta']				= @isset($_POST["meta"])?$_POST["meta"]:"";
$vars['minthreshold']		= @isset($_POST["minthreshold"])?$_POST["minthreshold"]:@$_GET["minthreshold"];
$vars['mqtt_topic']			= @isset($_POST["mqtt_topic"])?$_POST["mqtt_topic"]:"";
$vars['name']				= @isset($_POST["name"])?$_POST["name"]:@$_GET["name"];
$vars['password']			= @isset($_POST["password"])?$_POST["password"]:"";
$vars['position']			= @isset($_POST["position"])?$_POST["position"]:@$_GET["position"];
$vars['publish']			= @isset($_GET["publish"])?boolval($_GET["publish"]):false;
$vars['previousValue']		= @isset($_POST["previousValue"])?$_POST["previousValue"]:"";
$vars['save']				= @isset($_GET["save"])?boolval($_GET["save"]):false;
$vars['since']				= @isset($_GET["since"])?$_GET["since"]:"";
$vars['sort']				= @isset($_POST["sort"])?intval($_POST["sort"]):"";
$vars['timestamp']			= @isset($_POST["timestamp"])?intval($_POST["timestamp"]):null;
$vars['trigger_id']			= @isset($_POST['trigger_id'])?intval($_POST['trigger_id']):@$_GET["trigger_id"];
$vars['triggerAction']		= @isset($_POST["triggerAction"])?$_POST["triggerAction"]:"";
$vars['unit_id']			= @isset($_POST["unit_id"])?intval($_POST["unit_id"]):"";
$vars['username']			= @isset($_POST["username"])?$_POST["username"]:"";
$vars['value']				= @isset($_POST["value"])?$_POST["value"]:"";

$vars['actions'] = array(
		"help" => array(
				"name"			=> "help",
				"description"	=> "Display help message and list enabled functions",
				"parameters"	=> array(),
		),
		"test" => array(
				"name"			=> "test",
				"description"	=> "Test function ; do nothing",
				"parameters"	=> array(),
		),
		"triggerAction" => array(
				"name"			=> "triggerAction",
				"description"	=> "activate a trigger",
				"parameters"	=> array("trigger_id", "timestamp", "value", "previousValue"),
		),
		"addData" => array(
				"name"			=> "addData",
				"description"	=> "Add data to flow",
				"parameters"	=> array("timestamp", "value", "flow_id"),
		),
		"setData" => array(
				"name"			=> "setData",
				"description"	=> "Add data to flow - addData Alias.",
				"parameters"	=> array("timestamp", "value", "flow_id"),
		),
		"getDevices" => array(
				"name"			=> "getDevices",
				"description"	=> "Get device list from DB.",
				"parameters"	=> array(),
		),
		"addDevice" => array(
				"name"			=> "addDevice",
				"description"	=> "Add a new device to DB.",
				"parameters"	=> array("username", "password", "name", "position", "ipv6", "ipv4"),
		),
		"getFlows" => array(
				"name"			=> "getFlows",
				"description"	=> "Get Flows from DB. if flow_id is defined, get the required flow and returns data from DB.",
				"parameters"	=> array("flow_id"),
		),
		"addFlow" => array(
				"name"			=> "addFlow",
				"description"	=> "Add a new flow to DB.",
				"parameters"	=> array("name", "username", "password", "unit_id", "mqtt_topic", "data_type_id"),
		),
		"getDataTypes" => array(
				"name"			=> "getDataTypes",
				"description"	=> "Get Data Types from DB.",
				"parameters"	=> array(),
		),
		"getUnits" => array(
				"name"			=> "getUnits",
				"description"	=> "Get units from DB.",
				"parameters"	=> array(),
		),
		"getTriggers" => array(
				"name"			=> "getTriggers",
				"description"	=> "Get triggers from DB.",
				"parameters"	=> array(),
		),
		"addTrigger" => array(
				"name"			=> "addTrigger",
				"description"	=> "Add a new trigger to DB.",
				"parameters"	=> array("username", "password", "meta", "flow_id", "maxthreshold", "name", "event", "triggerAction", "exitOnAlert", "logEventToFlow_id", "sort", "enable"),
		),
		"removeTrigger" => array(
				"name"			=> "removeTrigger",
				"description"	=> "Remove a trigger from DB.",
				"parameters"	=> array("trigger_id"),
		),
		"getData" => array(
				"name"			=> "getData",
				"description"	=> "Get data from timeseries.",
				"parameters"	=> array("flow_id", "since"),
		),
		"getAverage" => array(
				"name"			=> "getAverage",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"getMin" => array(
				"name"			=> "getMin",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"getMax" => array(
				"name"			=> "getMax",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"getEatingCPU" => array(
				"name"			=> "getEatingCPU",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"getFreeSpace" => array(
				"name"			=> "getFreeSpace",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"guruplugStarted" => array(
				"name"			=> "guruplugStarted",
				"description"	=> "Not yet implemented",
				"parameters"	=> array(),
		),
		"getMemory" => array(
				"name"			=> "getMemory",
				"description"	=> "",
				"parameters"	=> array(),
		),
		"getVOC" => array(
				"name"			=> "getVOC",
				"description"	=> "Get information from YoctoVOC Module",
				"parameters"	=> array("serial"),
		),
		"getVirtualHub" => array(
				"name"			=> "getVirtualHub",
				"description"	=> "Get information from VirtualHub",
				"parameters"	=> array("serial"),
		),
		"getTemp" => array(
				"name"			=> "getTemp",
				"description"	=> "Get current temperature from Yahoo feed",
				"parameters"	=> array(),
		),
		"getFreebox" => array(
				"name"			=> "getFreebox",
				"description"	=> "Get data from Freebox device/rooter",
				"parameters"	=> array(),
		),
		"checkNetwork" => array(
				"name"			=> "checkNetwork",
				"description"	=> "Check if IPv4 was found on the Local Area Network.",
				"parameters"	=> array(),
		),
);

if ( $vars['json_force_object'] == "true" ) {
	$vars['json_force_object'] = JSON_FORCE_OBJECT;
} else {
	$vars['json_force_object'] = null;
}

if ( isset($vars['since']) ) {
	preg_match_all("/(\d+)(\w+)/", $vars['since'], $match);
	$vars['sinceVal']		= @intval($match[1][0]);
	$vars['sincePeriod']	= @$match[2][0];

	switch ( $vars['sincePeriod'] ) {
		case "Y":
			$vars['sinceTimestamp'] = mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y')-$vars['sinceVal']);
			break;

		case "m":
			$vars['sinceTimestamp'] = mktime(date("H"), date("i"), date("s"), date('m')-$vars['sinceVal'], date('d'), date('Y'));
			break;

		case "d":
			$vars['sinceTimestamp'] = mktime(date("H"), date("i"), date("s"), date('m'), date('d')-$vars['sinceVal'], date('Y'));
			break;

		case "h":
		default:
			$vars['sinceTimestamp'] = mktime(date("H")-$vars['sinceVal'], date("i"), date("s"), date('m'), date('d'), date('Y'));
			break;
	}
	//print $vars['sinceTimestamp'];
}

if( !$vars['action'] ) {
	$data = array("status" => "error", "message" => "Mandatory input (action) missing.");
	output($data, JSON_FORCE_OBJECT);
	ACTION_help($actionSettings, $vars);
	exit();
} else {
	$actionSettings = @$vars['actions'][$vars['action']];
	//print_r($actionSettings);
	if( !function_exists($functionName) ) {
		$data = array("status" => "error", "message" => sprintf("Action '%s' is not defined.", $vars['action']));
		output($data, JSON_FORCE_OBJECT);
		ACTION_help($actionSettings, $vars);
		exit();
	} else {
		$functionName = "ACTION_".$vars['action'];
		$functionName($actionSettings, $vars);
	}
}







/**
 * @param:
 * @return:
 */
function ACTION_help($actionSettings, $vars) {
	$action = $vars['action'];
	$data[$action] = array();

	foreach( $vars['actions'] as $function ) {
		array_push($data[$action], $function);
	}

	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_test($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_test.php");
	$action = $vars['action'];
	$flow_id = null;
	$sensor = new sensor_test($action, $flow_id);
	$data = $sensor->getCurrent();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish(time(), $data[$action]["value"], "testDevice", "testChannel");
		$data["published"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_triggerAction($actionSettings, $vars) {
	$data = $vars['trigger']->triggerAction($vars['trigger_id'], $vars['timestamp'], $vars['value'], $vars['previousValue']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_setData($actionSettings, $vars) {
	ACTION_addData($actionSettings, $vars);
}

/**
 * @param:
 * @return:
 */
function ACTION_addData($actionSettings, $vars) {
	$data = $vars['db']->setData($vars['timestamp'], $vars['value'], $vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getDevices($actionSettings, $vars) {
	$data = $vars['config']->getDevices();
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_addDevice($actionSettings, $vars) {
	$data = $vars['config']->addDevice($vars['username'], $vars['password'], $vars['name'], $vars['position'], $vars['ipv6'], $vars['ipv4']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getFlows($actionSettings, $vars) {
	$data = $vars['config']->getFlows($vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param: string $name
 * @param: string $username
 * @param: string $password
 * @param: integer $unit_id
 * @param: string $mqtt_topic
 * @param: integer $data_type_id
 * @return:
 */
function ACTION_addFlow($actionSettings, $vars) {
	$data = $vars['config']->addFlow(
			$vars['name'],
			$vars['username'],
			$vars['password'],
			$vars['unit_id'],
			$vars['mqtt_topic'],
			$vars['data_type_id']
	);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getDataTypes($actionSettings, $vars) {
	$data = $vars['config']->getDataTypes();
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getUnits($actionSettings, $vars) {
	$data = $vars['config']->getUnits();
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getTriggers($actionSettings, $vars) {
	$data = $vars['config']->getTriggers($vars['trigger_id'], $vars['filter_enable']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_addTrigger($actionSettings, $vars) {
	$data = $vars['config']->addTrigger(
			$vars['username'],
			$vars['password'],
			$vars['meta'],
			$vars['flow_id'],
			$vars['maxthreshold'],
			$vars['name'],
			$vars['event'],
			$vars['triggerAction'],
			$vars['exitOnAlert'],
			$vars['logEventToFlow_id'],
			$vars['sort'],
			$vars['enable']
	);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_removeTrigger($actionSettings, $vars) {
	$data = $vars['config']->removeTrigger($vars['trigger_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getData($actionSettings, $vars) {
	$data = $vars['db']->getData($vars['since'], $vars['sinceTimestamp'], $vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getAverage($actionSettings, $vars) { ## only for integer
	$data = $vars['db']->getAvg($vars['since'], $vars['sinceTimestamp'], $vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getMin($actionSettings, $vars) { ## only for integer
	$data = $vars['db']->getMin($vars['since'], $vars['sinceTimestamp'], $vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getMax($actionSettings, $vars) { ## only for integer
	$data = $vars['db']->getMax($vars['since'], $vars['sinceTimestamp'], $vars['flow_id']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getEatingCPU($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_cpu.php");
	$action = $vars['action'];
	$flow_id = null;
	$sensor = new sensor_cpu($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent($vars['limit']);
	$timestamp = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($timestamp, $data[$action]["value"], "guruplug", "cpu");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($timestamp, $data[$action]["value"], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getFreeSpace($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_freespace.php");
	$action = $vars['action'];
	$vars['flow_id'] = 2;
	$sensor = new sensor_freespace($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent();
	$timestamp = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($timestamp, $data[$action]["value"], "guruplug", "freespace");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($timestamp, $data[$action]["value"], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_guruplugStarted($actionSettings, $vars) {
	// TODO
	// TODO
	// TODO
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getMemory($actionSettings, $vars) {
	ACTION_memoryUsage($actionSettings, $vars);
}

/**
 * @param:
 * @return:
 */
function ACTION_memoryUsage($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_memoryusage.php");
	$action = $vars['action'];
	$vars['flow_id'] = 4;
	$sensor = new sensor_memoryusage($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent();
	$timestamp = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($timestamp, $data[$action]["value"], "guruplug", "memory_usage");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($timestamp, $data[$action]["value"], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getVOC($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_yoctovoc.php");
	$action = $vars['action'];
	$vars['flow_id'] = 5;
	$sensor = new sensor_yoctovoc($action, $vars['flow_id']);
	$data = $sensor->getCurrent();
	$vars['timestamp'] = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["currentValue"], "yoctovoc", "VOC");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($vars['timestamp'], $data[$action]["currentValue"], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getVirtualHub($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_virtualhub.php");
	$action = $vars['action'];
	$vars['flow_id'] = null;
	$sensor = new sensor_virtualhub($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent(isset($_GET['serial'])?$_GET['serial']:"");
	$vars['timestamp'] = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], "value", "virtualhub", "name");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($vars['timestamp'], $data[$action], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getTemp($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_meteodegrees.php");
	$action = $vars['action'];
	$vars['flow_id'] = 8;
	$sensor = new sensor_meteodegrees($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent();
	$vars['timestamp'] = $data[$action]["ts"];

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["temp"], "HTTP-get", "temperature");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($vars['timestamp'], $data[$action]["temp"], $vars['flow_id']);
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getFreebox($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_freebox.php");
	$action = $vars['action'];
	$vars['flow_id'] = array(10,11,12,13,14,15, 16, 17, 18, 19);
	$sensor = new sensor_freebox($action, $vars['flow_id']);
	$data = $sensor->getCurrent(isset($_GET['serial'])?$_GET['serial']:"");
	$vars['timestamp'] = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["upfec"], "Freebox", "FECup");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["downfec"], "Freebox", "FECdown");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["uphec"], "Freebox", "HECup");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["downhec"], "Freebox", "HECdown");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["upcrc"], "Freebox", "CRCup");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["downcrc"], "Freebox", "CRCdown");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["downrate"], "Freebox", "ATMup");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["uprate"], "Freebox", "ATMdown");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["upmargin"], "Freebox", "marginUp");
		$vars['mqtt']->publish($vars['timestamp'], $data[$action]["downmargin"], "Freebox", "marginDown");
		$data["published"] = true;
	}

	if ( $vars['save'] == true ) {
		$vars['db']->save($vars['timestamp'], $data[$action]["downfec"], $vars['flow_id'][0]); //10
		$vars['db']->save($vars['timestamp'], $data[$action]["upfec"], $vars['flow_id'][1]); //11
		$vars['db']->save($vars['timestamp'], $data[$action]["downhec"], $vars['flow_id'][2]); //12
		$vars['db']->save($vars['timestamp'], $data[$action]["uphec"], $vars['flow_id'][3]); //13
		$vars['db']->save($vars['timestamp'], $data[$action]["downcrc"], $vars['flow_id'][4]); //14
		$vars['db']->save($vars['timestamp'], $data[$action]["upcrc"], $vars['flow_id'][5]); //15
		$vars['db']->save($vars['timestamp'], $data[$action]["downrate"], $vars['flow_id'][6]); //16
		$vars['db']->save($vars['timestamp'], $data[$action]["uprate"], $vars['flow_id'][7]); //17
		$vars['db']->save($vars['timestamp'], $data[$action]["downmargin"], $vars['flow_id'][8]); //18
		$vars['db']->save($vars['timestamp'], $data[$action]["upmargin"], $vars['flow_id'][9]); //19
		$data["saved"] = true;
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_checkNetwork($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_networkcheck.php");
	$action = $vars['action'];
	$vars['flow_id'] = null;
	$sensor = new sensor_networkcheck($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent($vars['ipv4']);
	$vars['timestamp'] = time();

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], $vars['ipv4'].":".$data[$action]["value"], "guruplug", "checkNetwork");
		$data["published"] = true;
	}
	
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

exit();

?>