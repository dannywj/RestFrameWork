<?php

	define('CSS_JS_VERSION', '');
	define('TICKET_TIMEOUT',20);
	define('CLASS_DIR', __DIR__.'/class');

	require_once(CLASS_DIR.'/rest/client.php');
	echo 'begin test<br/>';
	global $cfg_aftership_api_key;
	$restServer = new RestClient();

	
	$url = 'http://stage/waimai/api/order/create';
	//$url = 'http://www.baidu.com';
	$headers = array('aftership-api-key' => '752a5958-dc45-4723-a3a3-fd9418ce094a',
	);
	$restServer->setHeaders($headers);
	$post_data = array(
		'slug' => 'aa',
		'tracking_number' =>'aabb',
	);
	$post_data_json = json_encode(array('tracking' => $post_data));
	$restServer->restPost($url);
	echo '<br/>Response info<br/>';
	var_dump($restServer);
	echo '<br/>Body info<br/>';
	var_dump($restServer->getResponseBody());

?>