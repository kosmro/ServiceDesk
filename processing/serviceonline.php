<?php
include_once 'common.php';
$urls_config = PROC_PATH.'urlmonitor.cnf';



try{
	$read_out = read_from_file($urls_config);
	$read_out = json_decode($read_out, true);
}catch(Exception $err){
	$read_out = "";
}
$output_arr = array('status' => 0, 'response' => array());


if( !isset($read_out) || $read_out == "" ){
	// No ZoHo config found, generate the base required structure
	$read_out = array(
		'enabled' => true,
        'servers' => array(
        	array('name' => 'Example Server',
				  'url' => 'https://google.com/',
				  'timeout_sec' => '2',
            	  'enabled' => true
			),),
		'LastSave' => '',
		);

	//Write out to file
	write_to_file($urls_config, json_encode($read_out) );

}


//Cycle through and check servers
foreach($read_out['servers'] as $url){
	if($url['enabled']){
		$response_url = url_serverstatus($url['name'], $url['url'], $url['timeout_sec']);
		array_push($output_arr['response'], json_decode($response_url,true) );
	}
	$output_arr['status'] = 1;
}

echo json_encode($output_arr);





function url_serverstatus($name, $url, $timeout_sec){

	$ch = curl_init(); //get url http://www.xxxx.com/cru.php?url=http://www.example.com
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout_sec);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$ch_result = curl_exec($ch);


	if($ch_result){
		$info = curl_getinfo($ch);
		//print_r($info);

		$response_set = array(
			'disp_name'		=> $name,
			'url' 			=> $url,
			'resolved_url'	=> $info['url'],
			'http_code'		=> $info['http_code'],
			'response_time'	=> $info['total_time'],
		);
	}else{
		$response_set = array(
			'disp_name'		=> $name,
			'url' 			=> $url,
			'http_code'		=> -1,
			'response_time'	=> 0,
		);
	}

	curl_close($ch);


	return json_encode($response_set);
}





?>