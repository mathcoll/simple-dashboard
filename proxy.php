<?php

function isValidURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

$_GET['url'] = urldecode($_GET['url']);

if(isValidURL($_GET['url'])){
	// Création d'un flux
	$opts = array(
			'http'=>array(
					'method'=>"GET",
					'header'=>"Accept-language: en\r\n" .
					"P3P: policyref=\"" . "http://".$_SERVER['HTTP_HOST'] ."/policy.xml\"\r\n"
			)
	);

	$context = stream_context_create($opts);
	if ( !$read = file_get_contents(urlencode($_GET['url']), false, $context) ) {
		echo $_GET['url'];
	} else {
		echo $read;
	}
}else{
	echo "Not valid url";
}

?>
