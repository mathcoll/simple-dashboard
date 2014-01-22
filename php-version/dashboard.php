<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width">

<title>Dashboard</title>

<!--script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>-->
<script src="http://gridster.net/demos/assets/jquery.js"></script>
<script src="http://anthonyterrien.com/js/jquery.knob.js"></script>
<script src="http://benpickles.github.io/peity/jquery.peity.min.js"></script>
<script src="/js/dashboard.js"></script>
<script src="http://gridster.net/dist/jquery.gridster.js"></script>
<link rel="stylesheet" href="/css/dashboard.css">
<link rel="stylesheet"
	href="http://gridster.net/dist/jquery.gridster.css">
</head>
<body>
	<h1>Dashboard</h1>
	<div class="gridster">
		<ul>
			<li data-row="1" data-col="1" data-sizex="1" data-sizey="2" class="currentVOC"></li>
			<li data-row="1" data-col="2" data-sizex="1" data-sizey="2" data-max-sizex="2" class="VOCs"></li>
			<li data-row="1" data-col="4" data-sizex="1" data-sizey="1" class="horloge"></li>
			<li data-row="1" data-col="3" data-sizex="1" data-sizey="2" class="LTC"></li>
			<li data-row="2" data-col="4" data-sizex="1" data-sizey="1" class="guruplugSpace"></li>
			<li data-row="3" data-col="1" data-sizex="1" data-sizey="2" class="RunningProcess"></li>
			<li data-row="3" data-col="2" data-sizex="2" data-sizey="2" class="guruplugRootAccess"></li>
			<li data-row="3" data-col="4" data-sizex="1" data-sizey="2" class="guruplugMemory"></li>
			<li data-row="5" data-col="1" data-sizex="1" data-sizey="2" class="memoryPie"></li>
			<li data-row="5" data-col="1" data-sizex="3" data-sizey="2" data-max-sizex="4" data-max-sizey="6" class="guruplugEatingCPU"></li>
		</ul>
	</div>
</body>
</html>
