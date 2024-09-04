<?php
include_once 'common.php';

/**************************
 * 
 *  RingCentral Phone System Setup Processing
 * 
 * */


if( isset($_POST['request']) && $_POST['request'] == 'SaveConfig' ){

        echo update_RingCentralConfig($_POST['status'], $_POST['queue_name'], $_POST['client_id'], $_POST['client_secret'], $_POST['oauth_jwt'] );
}



function read_RingCentralConfig(){
	$read_out = read_from_config();
	$config = json_decode($read_out, true);

	if( !isset($config['RingCentral']) ){
		// No RingCentral config found, generate the base required structure
		$rc_new = array(
            'enabled' => 0,
            'Phone_Queue' => '',
			'OAuth' => array(
				'OAuth_ClientID' => '',
				'OAuth_ClientSecret' => '',
				'OAuth_jwt' => '',
				),
			'LastSave' => '',
			);

		//Write out to file
		$config['RingCentral'] = $rc_new;
		write_to_config( json_encode($config) );

	}

	return $config['RingCentral'];
}

function update_RingCentralConfig($status, $queue_name, $client_id, $client_secret, $oauth_jwt){
    $read_out = read_from_config();
    $config = json_decode($read_out, true);

    // No RingCentral config found, generate the base required structure
    $zoho_new = array(
            'enabled' => $status,
            'Phone_Queue' => $queue_name,
            'OAuth' => array(
                    'OAuth_ClientID' => $client_id,
                    'OAuth_ClientSecret' => $client_secret,
                    'OAuth_jwt' => $oauth_jwt,
                    ),
            'LastSave' => date('H:ia j m Y'),
        );

    //Write out to file
    $config['RingCentral'] = $zoho_new;
    write_to_config( json_encode($config) );

    
    return true;
}


?>