<?php
/*
	
*/

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);	// PROD: Only Fatal errors

$url=$_SERVER["REQUEST_URI"];
$url=preg_replace("#^/#","",$url);

if($url){
	//$headers=getallheaders();
	$headers[]="User-Agent: EspBuddy";;
	$opts = array(
		"http" => array(
			"method" => $_SERVER['REQUEST_METHOD'],
			"header" => $headers,
		//	"content" => file_get_contents('php://input')
		)
	);
	$context = stream_context_create($opts);
	echo file_get_contents($url,false,$context);
}
else{
	return false;
}
?>