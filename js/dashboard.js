var margin = 7;
var cols = 4;
var dim = Math.round((($(window).width()/cols)-((cols-1)*margin)));
var gridster;

$(function(){
	gridster = $(".gridster > ul").gridster({
		widget_margins: [margin, margin],
		widget_base_dimensions: [dim, dim/2],
		helper: 'clone',
		resize: {
			enabled: true,
			max_size: [3, 3]
		}
	}).data('gridster');
	
	$.fn.peity.defaults.pie = {
		colours: ["#ff9900", "#fff4dd", "#ffd592", "#c6d9fd", "#4d89f9"],
		spacing: devicePixelRatio || 1,
		strokeWidth: 1
	}

	currentVOC();
	VOCs();
	guruplugMemory();
	guruplugRootAccess();
	guruplugSpace();
	guruplugEatingCPU();
	guruplugEvents();
});


function currentVOC() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getVOC&serial=YVOCMK01-09DE3", function(getVOC) {
		$("li.currentVOC h1, li.currentVOC h2, li.currentVOC div, li.currentVOC p").remove();
		$("li.currentVOC").append("<h1>Current VOC</h1>");
		$("li.currentVOC").append("<input class='knobCurrentVOC' data-min='"+getVOC['getVOC']['lowestValue']+"' data-max='"+getVOC['getVOC']['highestValue']+"' data-width='150' data-height='170' data-fgColor='#fff' data-thickness='.4' data-readOnly='true' value='"+getVOC['getVOC']['currentValue']+"' />");
		$("li.currentVOC").append("<h2>ppm</h2>");
		$("li.currentVOC").append("<p class='details'>min: "+getVOC['getVOC']['lowestValue']+" / max: "+getVOC['getVOC']['highestValue']+"</p>");
		$("li.currentVOC").append("<p class='updated-at'>"+displayTime()+"</p>");
		$(".knobCurrentVOC").knob({});
	});
	setTimeout("currentVOC()", 60*1000);
}
function VOCs() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=5&since=1h", function(getVOCs) {
		$("li.VOCs h1, li.VOCs h2, li.VOCs div, li.VOCs p").remove();
		$("li.VOCs").append("<h1>Yocto VOC Module (ppm)</h1>");
		var n=2;
		getVOCs = getVOCs.getData;
		//console.log(getVOCs);
		$.each(getVOCs, function(key, value){
			$("li.VOCs").append("<h"+n+" class='value'>"+getVOCs[n-2][1]+"<span>ppm</span></h"+n+">");
			n++;
		});
	});
	setTimeout("VOCs()", 7*60*1000);
}
function guruplugMemory() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=4&since=1h", function(getMemory) {
		getMemory = getMemory.getData;
		$("li.guruplugMemory h1, li.guruplugMemory h2, li.guruplugMemory div, li.guruplugMemory p").remove();
		$("li.guruplugMemory").append("<h1>GuruPlug Memory Usage</h1>");
		$("li.guruplugMemory").append("<input class='knobMemory' data-min='0' data-max='100' data-width='160' data-fgColor='#fff' data-angleOffset='-125' data-angleArc='250' data-thickness='.4' data-readOnly='true' value='"+getMemory[0][1]+"' />");
		$("li.guruplugMemory").append("<h2>%</h2>");
		$("li.guruplugMemory").append("<p class='updated-at'>"+displayTime(parseInt(getMemory[0][0]))+"</p>");
		$(".knobMemory").knob({});
	});
	setTimeout("guruplugMemory()", 20*60*1000);
}

function guruplugSpace() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=2&since=1h", function(getSpace) {
		getSpace = getSpace.getData;
		$("li.guruplugSpace h1, li.guruplugSpace h2, li.guruplugSpace h3, li.guruplugSpace div, li.guruplugSpace p").remove();
		$("li.guruplugSpace").append("<h1>Free space left on Nand</h1>");
		$("li.guruplugSpace").append("<h3 class='value'>"+Math.round(getSpace[0][1]/1024)+" <span>Mo</span></h3>");
		$("li.guruplugSpace").append("<p class='updated-at'>"+displayTime()+"</p>");
	});
	setTimeout("guruplugSpace()", 60*1000);
}

function guruplugRootAccess() {
	$.getJSON("http://app.internetcollaboratif.info/API/?action=getData&flow_id=1&since=20d", function(getFailedRoot) {
		getFailedRoot = getFailedRoot.getData;
		$("li.guruplugRootAccess h1, li.guruplugRootAccess h2, li.guruplugRootAccess div, li.guruplugRootAccess p").remove();
		$("li.guruplugRootAccess").append("<h1>Failed Root Access</h1>");
		$("li.guruplugRootAccess").append("<h2>List 20 last days</h2>");
		var rootAccess = "";
		$.each(getFailedRoot, function(key, value){
			rootAccess += getFailedRoot[key][1] + ",";
		});
		$("li.guruplugRootAccess").append("<span class='bar'>"+rootAccess.substring(0, (rootAccess.length)-1)+"</span></h3>");
		$("li.guruplugRootAccess").append("<h2>Number of Failed Root Attempts</h2>");
		$("li.guruplugRootAccess").append("<p class='updated-at'></p>");
		$(".bar").peity("bar", { width: 2*(dim-margin), height: dim-110, spacing: margin, colour: "#ff9900", strokeColour: "#ffd592" });
	});
	setTimeout("guruplugRootAccess()", 24*60*60000);
}

function guruplugEatingCPU() {
	var limit = 8;
	$.get("http://app.internetcollaboratif.info/API/?action=getEatingCPU&limit="+limit, function(eatingCPU) {
		eatingCPU = eatingCPU.getEatingCPU.value;
		
		$("li.memoryPie canvas, li.memoryPie h1, li.memoryPie h2, li.memoryPie div, li.memoryPie p, li.memoryPie span.pie").remove();
		$("li.guruplugEatingCPU h1, li.guruplugEatingCPU h2, li.guruplugEatingCPU div, li.guruplugEatingCPU p").remove();

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

function guruplugEvents() {
	$("li.guruplugEvents").append("<h1>GuruPlug Events</h1>");
	$("li.guruplugEvents").append("<p class='updated-at'>"+displayTime()+"</p>");
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


function displayTime(ts=null) {
	if ( ts ) {
		var currentTime	= new Date(ts);
	} else {
		var currentTime	= new Date();
	}
	
	var month		= currentTime.getMonth() + 1;
	var day			= currentTime.getDate();
	var year		= currentTime.getFullYear();
	var hours		= currentTime.getHours();
	var minutes		= currentTime.getMinutes();
	
	if ( minutes < 10 ) {
		minutes	= "0"+minutes;
	}
	return day + "/" + month + "/" + year + " " + hours + "h" + minutes;
}
