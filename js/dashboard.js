var margin = 7;
var cols = 4;
var dim = Math.round((($(window).width()/cols)-((cols-1)*margin)));
var gridster;
var days = new Array("D", "L", "M", "M", "J", "V", "S");
var months = new Array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
var s_months = new Array("Jan.", "Fév.", "Mar.", "Avr.", "Mai", "Jui.", "Juil.", "Aoû.", "Sept.", "Oct.", "Nov.", "Déc.");

$(function(){
	gridster = $(".gridster > ul").gridster({
		widget_margins: [margin, margin],
		widget_base_dimensions: [dim, dim/2],
		helper: 'clone',
		resize: {
			enabled: true,
			max_size: [4, 4]
		}
	}).data('gridster');
	
	//$.fn.peity.defaults.pie = {
		//colours: ["#cecece", "#fff4dd", "#ffd592", "#c6d9fd", "#4d89f9"],
		//spacing: devicePixelRatio || 1,
		//strokeWidth: 1
	//}
	//$.fn.peity.defaults.bar = {}

	currentVOC();
	VOCs();
	guruplugMemory();
	guruplugRootAccess();
	guruplugSpace();
	guruplugEatingCPU();
	LTC();
	horloge();
	RunningProcess();
});


function currentVOC() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getVOC&serial=YVOCMK01-09DE3", function(getVOC) {
		$("li.currentVOC h1, li.currentVOC h2, li.currentVOC div, li.currentVOC p").remove();
		$("li.currentVOC").append("<h1>Current VOC</h1>");
		$("li.currentVOC").append("<input class='knobCurrentVOC' data-min='"+getVOC['getVOC']['lowestValue']+"' data-max='"+getVOC['getVOC']['highestValue']+"' data-width='170' data-height='190' data-angleOffset='-150' data-angleArc='300' data-fgColor='#cecece' data-thickness='.4' data-readOnly='true' value='"+getVOC['getVOC']['currentValue']+"' />");
		$("li.currentVOC").append("<h2>ppm</h2>");
		$("li.currentVOC").append("<p class='details'>min: "+getVOC['getVOC']['lowestValue']+" / max: "+getVOC['getVOC']['highestValue']+"</p>");
		$("li.currentVOC").append("<p class='updated-at'>"+displayTime()+"</p>");
		$(".knobCurrentVOC").knob({});
	});
	setTimeout("currentVOC()", 60*1000);
}
function VOCs() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=5&since=1h", function(getVOCs) {
		$("li.VOCs h1, li.VOCs h2, li.VOCs h3, li.VOCs h4, li.VOCs h5, li.VOCs div, li.VOCs p").remove();
		$("li.VOCs").append("<h1>Yocto VOC Module</h1>");
		var n=2;
		getVOCs = getVOCs.getData.values;
		//console.log(getVOCs);
		$.each(getVOCs, function(key, value){
			$("li.VOCs").append("<h"+n+" class='value'>"+getVOCs[n-2][1]+"<span>ppm</span></h"+n+">");
			n++;
		});
	});
	setTimeout("VOCs()", 7*60*1000);
}
function guruplugMemory() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=4&since=1h&limit=1", function(getMemory) {
		getMemory = getMemory.getData.values;
		$("li.guruplugMemory h1, li.guruplugMemory h2, li.guruplugMemory div, li.guruplugMemory p").remove();
		$("li.guruplugMemory").append("<h1>GuruPlug Memory Usage</h1>");
		$("li.guruplugMemory").append("<input class='knobMemory' data-min='0' data-max='120' data-width='180' data-fgColor='#cecece' data-angleOffset='-150' data-angleArc='300' data-thickness='.4' data-readOnly='true' value='"+getMemory[0][1]+"' />");
		$("li.guruplugMemory").append("<h2>%</h2>");
		$("li.guruplugMemory").append("<p class='updated-at'>"+displayTime(parseInt(getMemory[0][0]))+"</p>");
		$(".knobMemory").knob({});
	});
	setTimeout("guruplugMemory()", 20*60*1000);
}

function guruplugSpace() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=2&since=1h", function(getSpace) {
		getSpace = getSpace.getData.values;
		$("li.guruplugSpace h1, li.guruplugSpace h2, li.guruplugSpace h3, li.guruplugSpace div, li.guruplugSpace p").remove();
		$("li.guruplugSpace").append("<h1>Free space left on Nand</h1>");
		$("li.guruplugSpace").append("<h3 class='value'>"+Math.round(getSpace[0][1]/1024)+" <span>Mo</span></h3>");
		$("li.guruplugSpace").append("<p class='updated-at'>"+displayTime()+"</p>");
	});
	setTimeout("guruplugSpace()", 60*1000);
}

function guruplugRootAccess() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=1&since=20d", function(getFailedRoot) {
		getFailedRoot = getFailedRoot.getData.values.reverse();
		$("li.guruplugRootAccess h1, li.guruplugRootAccess h2, li.guruplugRootAccess div, li.guruplugRootAccess p").remove();
		$("li.guruplugRootAccess").append("<h1>Failed Root Access</h1>");
		$("li.guruplugRootAccess").append("<h2>Number of Failed Root Attempts</h2>");
		var rootAccess = "";
		var sum = 0;
		var dayOfWeeks = "";
		$.each(getFailedRoot, function(key, value){
			rootAccess += getFailedRoot[key][1] + ",";
			sum += parseInt(getFailedRoot[key][1]);
			var d = new Date(parseInt(getFailedRoot[key][0]));
			dayOfWeeks += "<span title='"+getFailedRoot[key][1]+"'>"+days[d.getDay()]+"</span>";
		});
		$("li.guruplugRootAccess").append("<span class='bar'>"+rootAccess.substring(0, (rootAccess.length)-1)+"</span></h3>");

		$("li.guruplugRootAccess").append("<p class='dayOfWeeks'>"+dayOfWeeks+"</p>");
		$("li.guruplugRootAccess").append("<h3>Sum: "+sum+" (in the last 20 days)</h3>");
		$("li.guruplugRootAccess").append("<p class='updated-at'></p>");
		$("li.guruplugRootAccess span.bar").peity("bar", {
			width: 2*(dim-margin),
			height: dim-100,
			spacing: margin,
			colours: ["#cecece"],
			strokeColour: "#cecece",
			fill: ["#cecece"],
			strokeWidth: 1
		});
	});
	setTimeout("guruplugRootAccess()", 24*60*60000);
}

function horloge() {
	$("li.horloge").append("<h1>Current Time</h1>");
	var currentTime = new Date();
	var d = currentTime.getDate();
	if(d < 10){d = "0" + d}
	var m = s_months[currentTime.getMonth()];
	$("li.horloge").append("<span class='date'><span class='jour'>"+d+"</span><span class='mois'>"+m+"</span></span>");
	
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getTemp", function(getTemp) {
		getTemp = getTemp["getTemp"];
		$("li.horloge").append("<div id='meteo-degrees'>"+getTemp.temp+"°"+getTemp.unit+"<br />"+getTemp.text+"</div>");
	});
	
	$("li.horloge").append("<span id='spa'>:</span>");
	var _i=true;
	setInterval(function(){
		var currentTime = new Date();
		var h = currentTime.getHours();
		var m = currentTime.getMinutes();
		var s = currentTime.getSeconds();
		if(h < 10){h = "0" + h}
		if(m < 10){m = "0" + m}
		if(s < 10){s = "0" + s}
		$("li.horloge span#spa").html(h + "<span class='blink'>:</span>" + m + "'<small style='font-size:.6em;'>" + s + "</small>");
	}, 1000);
}

function guruplugEatingCPU() {
	var limit = 8;
	$.get("http://app.internetcollaboratif.info/API/?action=getEatingCPU&limit="+limit, function(eatingCPU) {
		eatingCPU = eatingCPU.getEatingCPU.value;
		
		$("li.memoryPie canvas, li.memoryPie span, li.memoryPie h1, li.memoryPie h2, li.memoryPie div, li.memoryPie p, li.memoryPie svg").remove();
		$("li.guruplugEatingCPU h1, li.guruplugEatingCPU h2, li.guruplugEatingCPU div, li.guruplugEatingCPU p, li.guruplugEatingCPU ul").remove();

		$("li.memoryPie").append("<h1>GuruPlug Memory</h1>");
		$("li.guruplugEatingCPU").append("<h1>GuruPlug Eating CPU</h1>");
		
		var memory = "";
		var liste = "";
		$.each(eatingCPU, function(key, value){
			var mem = eatingCPU[key].split(" ");
			memory += mem[0] + ",";
			liste += "<li>" + eatingCPU[key] + "</li>\n";
		});

		$("li.memoryPie").append("<span class='pie' data-diameter='200'>"+memory.substring(0, (memory.length)-1)+"</span></h3>");
		$("li.memoryPie").append("<h2>%</h2>");
		$("li.memoryPie").append("<p class='updated-at'>"+displayTime()+"</p>");
		$("li.guruplugEatingCPU").append("<p class='updated-at'>"+displayTime()+"</p>");
		
		$("li.guruplugEatingCPU").append("<ul>"+liste+"</ul>");
		$("li.memoryPie span.pie").peity("pie");
	});
	
	setTimeout("guruplugEatingCPU()", 2*60*1000);
}

function LTC() {
	$("li.LTC h1, li.LTC h2, li.LTC p").remove();
	$("li.LTC").append("<h1>SMP-FR LTC pool</h1>");
	$.getJSON("/proxy.php?url=http://ltc.ouranos.fr/api2?api_key=4cd138df7a81c1d6336326b67503433e3609edc3c190edd05720c848c4c84126", function(ltc) {
		$("li.LTC").append("<h2>"+ltc.username+"</h2>");
		$("li.LTC").append("<p>Rewards: "+Math.round(ltc.confirmed_rewards*1000000000)/1000000000+" LTC</p>");
		$("li.LTC").append("<p>Round Shares: "+ltc.round_shares+"</p>");
		$.each(ltc.workers, function(key, value){
			$("li.LTC").append("<h1>"+key+"</h1>");
			$("li.LTC").append("<p>Alive: "+value.alive+"</p>");
			$("li.LTC").append("<p>Hashrate: "+value.hashrate+"</p>");
		});
	});
	$("li.LTC").append("<p class='updated-at'>"+displayTime()+"</p>");
	setTimeout("LTC()", 2*60*1000);
}

function RunningProcess() {
	$("li.RunningProcess h1, li.RunningProcess div, li.RunningProcess p").remove();
	$("li.RunningProcess").append("<h1>Running Process</h1>");
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getRunningProcess", function(getRunningProcess) {
		$.each(getRunningProcess.getRunningProcess.process, function(key, value){
			$("li.RunningProcess").append("<div style='font-size:.8em;clear: both;' title='proc="+value.proc+"'><span style='float:left'>"+value.name+"</span><span style='float:right'>"+value.pid+"</span></div>");
		});
	});
	$("li.RunningProcess").append("<p class='updated-at'>"+displayTime()+"</p>");
	setTimeout("RunningProcess()", 10*60*1000);
}

function secondstotime(secs) {
	var t = new Date(1970,0,1);
	t.setSeconds(secs);
	var s = t.toTimeString().substr(0,8);
	if(secs > 86399) {
	    s = Math.floor((t - Date.parse("1/1/70")) / 3600000) + s.substr(2);
	}
	return s;
}
function secondsToTime(ms) {
	var sec = Math.floor(ms/1000)
	ms = ms % 1000
	t = three(ms)

	var min = Math.floor(sec/60)
	sec = sec % 60
	t = two(sec) + ":" + t

	var hr = Math.floor(min/60)
	min = min % 60
	t = two(min) + ":" + t

	var day = Math.floor(hr/24)
	hr = hr % 24
	t = two(hr) + ":" + t
	t = day + ":" + t

	return t;
}
function two(x) {return ((x>9)?"":"0")+x}
function three(x) {return ((x>99)?"":"0")+((x>9)?"":"0")+x}


function displayTime(ts) {
	if ( ts > 0 ) {
		var currentTime	= new Date(ts);
	} else {
		var currentTime	= new Date();
	}
	
	var day			= currentTime.getDate();
	var month		= currentTime.getMonth() + 1;
	var year		= currentTime.getFullYear();
	var hours		= currentTime.getHours();
	var minutes		= currentTime.getMinutes();
	if ( day < 10 ) {
		day	= "0"+day;
	}
	if ( month < 10 ) {
		month	= "0"+month;
	}
	if ( minutes < 10 ) {
		minutes	= "0"+minutes;
	}
	return day + "/" + month + "/" + year + " " + hours + "h" + minutes;
}
