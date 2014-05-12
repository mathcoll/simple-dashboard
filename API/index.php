<?php
error_reporting(0);
ini_set("display_errors", 0);
require(dirname(__FILE__) . "/includes/mqtt.php");
require(dirname(__FILE__) . "/includes/config.php");
require(dirname(__FILE__) . "/includes/db.php");
require(dirname(__FILE__) . "/includes/trigger.php");
use Gregwar\GnuPlot\GnuPlot;
global $vars;

function output($data, $object=null, $actionSettings=null) {
	global $vars;
	if ( $_GET["debug"] == "true" ) {
		if ( isset($actionSettings) ) {
			$data['name']			= $actionSettings['name'];
			$data['description']	= $actionSettings['description'];
			array_push($actionSettings['parameters'], "action", "debug");
			$data['required-parameters']		= $actionSettings['parameters'];
		}
		//$data['required-parameters'] = $vars['actions'][$vars['action']]["parameters"];
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
$vars['channel']			= @isset($_POST["channel"])?$_POST["channel"]:@isset($_GET["channel"])?$_GET["channel"]:"channel";
$vars['data_type_id']		= @isset($_POST["data_type_id"])?intval($_POST["data_type_id"]):"";
$vars['device']				= @isset($_POST["device"])?$_POST["device"]:@isset($_GET["device"])?$_GET["device"]:"device";
$vars['enable']				= @isset($_POST["enable"])?intval($_POST["enable"]):"";
$vars['event']				= @isset($_POST["event"])?$_POST["event"]:"";
$vars['exitOnAlert']		= @isset($_POST["exitOnAlert"])?intval($_POST["exitOnAlert"]):"";
$vars['filter_enable']		= @isset($_POST["filter_enable"])?@$_POST["filter_enable"]:@$_GET["filter_enable"];
$vars['formatdate']			= @isset($_POST["formatdate"])?$_POST["formatdate"]:@isset($_GET["formatdate"])?$_GET["formatdate"]:"unixepoch";
//print "P".$_POST["flow_id"];
//print "G".$_GET["flow_id"];
$vars['flow_id']			= @isset($_POST["flow_id"])?$_POST["flow_id"]:null;
if ( !isset($vars['flow_id']) ) {
	$vars['flow_id'] = @isset($_GET['flow_id'])?$_GET['flow_id']:null;
}
//print "V".$vars['flow_id'];

$vars['height']				= @isset($_POST["height"])?$_POST["height"]:"";
$vars['ipv4']				= @isset($_POST["ipv4"])?$_POST["ipv4"]:@$_GET["ipv4"];
$vars['ipv6']				= @isset($_POST["ipv6"])?$_POST["ipv6"]:@$_GET["ipv6"];
$vars['json_force_object']	= @isset($_POST["json_force_object"])?$_POST["json_force_object"]:@$_GET["json_force_object"];
$vars['limit']				= @isset($_GET['limit'])?intval($_GET['limit']):null;
$vars['logEventToFlow_id']	= @isset($_POST["logEventToFlow_id"])?intval($_POST["logEventToFlow_id"]):"";
$vars['maxthreshold']		= @isset($_POST["maxthreshold"])?$_POST["maxthreshold"]:@$_GET["maxthreshold"];
$vars['meta']				= @isset($_POST["meta"])?$_POST["meta"]:"";
$vars['minthreshold']		= @isset($_POST["minthreshold"])?$_POST["minthreshold"]:@$_GET["minthreshold"];
$vars['mqtt_topic']			= @isset($_POST["mqtt_topic"])?$_POST["mqtt_topic"]:"";
$vars['name']				= @isset($_POST["name"])?$_POST["name"]:@$_GET["name"];
$vars['password']			= @isset($_POST["password"])?$_POST["password"]:"";
$vars['period']				= @isset($_POST["period"])?$_POST["period"]:@isset($_GET["period"])?$_GET["period"]:"@daily";
$vars['process']			= @isset($_POST["process"])?$_POST["process"]:@$_GET["process"];
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
$vars['width']				= @isset($_POST["width"])?$_POST["width"]:"";

//print_r($vars);

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
		"addData" => array(
				"name"			=> "addData",
				"description"	=> "Add data to flow",
				"parameters"	=> array("timestamp", "value", "flow_id"),
		),
		"addTrigger" => array(
				"name"			=> "addTrigger",
				"description"	=> "Add a new trigger to DB.",
				"parameters"	=> array("username", "password", "meta", "flow_id", "maxthreshold", "name", "event", "triggerAction", "exitOnAlert", "logEventToFlow_id", "sort", "enable"),
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
		"getRunningProcess" => array(
				"name"			=> "getRunningProcess",
				"description"	=> "List running processes",
				"parameters"	=> array("process"),
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
		"getData" => array(
				"name"			=> "getData",
				"description"	=> "Get data from timeseries.",
				"parameters"	=> array("flow_id", "since", "limit"),
		),
		"getAverage" => array(
				"name"			=> "getAverage",
				"description"	=> "Get average data for a period",
				"parameters"	=> array("flow_id", "since", "period"),
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
				"description"	=> "get current Memory Usage using 'ps aux'",
				"parameters"	=> array(),
		),
		"getCPU" => array(
				"name"			=> "getCPU",
				"description"	=> "get current CPU Usage using 'ps aux'",
				"parameters"	=> array(),
		),
		"ACTION_publish" => array(
				"name"			=> "ACTION_publish",
				"description"	=> "",
				"parameters"	=> array("device", "channel", "value"),
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
		"getImage" => array(
				"name"			=> "getImage",
				"description"	=> "Get gnuplot image from a flow.",
				"parameters"	=> array("flow_id", "since", "width", "height"),
		),
		"removeTrigger" => array(
				"name"			=> "removeTrigger",
				"description"	=> "Remove a trigger from DB.",
				"parameters"	=> array("trigger_id"),
		),
		"triggerAction" => array(
				"name"			=> "triggerAction",
				"description"	=> "activate a trigger",
				"parameters"	=> array("trigger_id", "timestamp", "value", "previousValue"),
		),
		"updateTrigger" => array(
				"name"			=> "updateTrigger",
				"description"	=> "activate or disable a trigger",
				"parameters"	=> array("trigger_id"),
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
	$data = array("status" => "error", "message" => "Action is missing.");
	output($data, JSON_FORCE_OBJECT);
	ACTION_help($actionSettings, $vars);
	exit();
} else {
	$actionSettings = @$vars['actions'][$vars['action']];
	//print_r($actionSettings);
	//print_r($vars);
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

	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish(time(), json_encode($d), "testDevice", "testChannel") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_publish($actionSettings, $vars) { 
	$d				= array();
	$d["dtepoch"]	= time();
	$d["value"]		= $vars["value"];

	if ( $vars['mqtt']->publish($d["dtepoch"], json_encode($d), $vars['device'], $vars['channel']) ) {
		$data["published"] = true;
	} else {
		$data["published"] = "error";
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
function ACTION_getRunningProcess($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_process.php");
	$action = $vars['action'];
	$flow_id = 21;
	$sensor = new sensor_process($action, $flow_id);
	$data = $sensor->getCurrent($vars['process']);

	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish(time(), json_encode($d), "guruplug", "runningProcess") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
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
	$data = $vars['db']->getData($vars['since'], $vars['sinceTimestamp'], $vars['flow_id'], $vars['limit'], $vars['formatdate']);
	output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getImage($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/GnuPlot/GnuPlot.php");

	$plot = new GnuPlot;
	$plot
		->setXLabel('Time')
		->setYLabel('Temperature')
		->setY2Label('ppm')
		->setWidth($vars['width'])
		->setHeight($vars['height'])
	;

	$flows = explode(",", $vars['flow_id']);
	$index=0;
	$data["datas"] = array();
	foreach( $flows as $flow ) {
		$data = $vars['db']->getData($vars['since'], $vars['sinceTimestamp'], intval($flow), $vars['limit']);

		$plot->setGraphTitle($data["getData"]["title"]);
		if ( @is_array($data["getData"]["values"]) ) {
			$data["datas"][$flow] = $data["getData"]["values"];
			$plot->setTitle($index, 'Flow '.$flow);
			foreach( $data["datas"][$flow] AS $line ) {
				$plot->push($line[0]/1000, $line[1], $index);
			}
			unset($data["getData"]);
		} else {
			//$plot->setTitle($index, 'Flow '.$flow);
			//$plot->push(0, 0, $index);
		}
		$index++;
	}

	$plot->set('set border 3');
	$plot->set('set xtics nomirror');
	$plot->set('set ytics nomirror');
	$plot->set('set xtic rotate by -45 scale 0 font ",8"');

	$plot->set('set style fill   pattern 0 border');
	$plot->set('set autoscale y');
	$plot->set('set y2r [-1:12]');
	$plot->set('set y2tics');
	$plot->set('set xdata time');
	$plot->set('set timefmt "%s"');
	$plot->set('set format x "%d/%m/%Y\n%H:%M"');
	$plot->set('set key left top'); //Set the legend to the top left corner.
	$plot->set('set label "© http://app.internetcollaboratif.info" at graph 0.01, 0.05');
	$file = '/tmp/'.$vars['flow_id'].'.png';
	//$fp = fopen($file, "w+");
	$plot->writePng($file);
	$data["getImage"]["image"] = file_get_contents($file);
	
	header("Content-type:image/png");

	unset($data["datas"]); // for debug
	unset($data["getData"]); // for debug
	
	print $data["getImage"]["image"];
	//output($data, null, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_getAverage($actionSettings, $vars) { ## only for integer
	$data = $vars['db']->getAvg($vars['since'], $vars['sinceTimestamp'], $vars['period'], $vars['flow_id']);
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

	$d				= array();
	$d["dtepoch"]	= $timestamp;
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($timestamp, json_encode($d), "guruplug", "cpu") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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

	$d				= array();
	$d["dtepoch"]	= $timestamp;
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($timestamp, json_encode($d), "guruplug", "freespace") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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
	$data = $sensor->getCurrent("mem");
	$timestamp = time();

	$d				= array();
	$d["dtepoch"]	= $timestamp;
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($timestamp, json_encode($d), "guruplug", "memory_usage") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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
function ACTION_getCPU($actionSettings, $vars) {
	require(dirname(__FILE__) . "/includes/sensor_memoryusage.php");
	$action = $vars['action'];
	$vars['flow_id'] = null;
	$sensor = new sensor_memoryusage($vars['action'], $vars['flow_id']);
	$data = $sensor->getCurrent("cpu");
	$timestamp = time();

	$d				= array();
	$d["dtepoch"]	= $timestamp;
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($timestamp, json_encode($d), "guruplug", "cpu_usage") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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
	
	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["value"]		= $data[$action]["currentValue"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($vars['timestamp'], json_encode($d), "yoctovoc", "voc") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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

	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["value"]		= "value";

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($vars['timestamp'], json_encode($d), "virtualhub", "name") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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

	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["value"]		= $data[$action]["temp"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($vars['timestamp'], json_encode($d), "yahoo", "temperature") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
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
	$vars['flow_id'] = array(10, 11, 12, 13, 14, 15, 16, 17, 18, 19);
	$sensor = new sensor_freebox($action, $vars['flow_id']);
	$data = $sensor->getCurrent(isset($_GET['serial'])?$_GET['serial']:"");
	$vars['timestamp'] = time();
	$errorCount = 0;

	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["upfec"])), "Freebox", "FECup");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["downfec"])), "Freebox", "FECdown");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["uphec"])), "Freebox", "HECup");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["downhec"])), "Freebox", "HECdown");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["upcrc"])), "Freebox", "CRCup");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["downcrc"])), "Freebox", "CRCdown");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["downrate"])), "Freebox", "ATMup");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["uprate"])), "Freebox", "ATMdown");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["upmargin"])), "Freebox", "marginUp");
	} else { $errorCount++; }
	if ( $vars['publish'] == true ) {
		$vars['mqtt']->publish($vars['timestamp'], json_encode(array("dtepoch" => $vars['timestamp'], "value" => $data[$action]["downmargin"])), "Freebox", "marginDown");
	} else { $errorCount++; }
	
	if ( $errorCount == 0 ) {
		$data["published"] = true;
	} else {
		$data["published"] = sprintf("%d error(s)", $errorCount);
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

	$d				= array();
	$d["dtepoch"]	= $vars['timestamp'];
	$d["ipv4"]		= $vars['ipv4'];
	$d["value"]		= $data[$action]["value"];

	if ( $vars['publish'] == true ) {
		if ( $vars['mqtt']->publish($vars['timestamp'], json_encode($d), "guruplug", "checkNetwork") ) {
			$data["published"] = true;
		} else {
			$data["published"] = "error";
		}
	}
	
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

/**
 * @param:
 * @return:
 */
function ACTION_updateTrigger($actionSettings, $vars) {
	$vars['value'] = $vars['value']==1?1:0;
	if ( isset($vars['trigger_id']) ) {
		if ( $vars['value'] == 1 ) {
			$data = $vars['trigger']->enable($vars['trigger_id']);
		} else {
			$data = $vars['trigger']->disable($vars['trigger_id']);
		}
	} else {
		$this->data = array("status" => "error", "message" => "trigger_id cannot be updated due to wrong 'value'.");
	}
	output($data, JSON_FORCE_OBJECT, $actionSettings);
	exit();
}

exit();

?>
