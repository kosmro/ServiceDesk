<?php
include_once 'common.php';

/**************************
 * 
 *  ZOHO ONE Setup Processing
 * 
 * */

if( isset($_POST['request']) && $_POST['request'] == 'regRefresh' ){

    //write_to_file(PROC_PATH."zoho.txt", "*******************");
	$response = generateZohoAccessToken($_POST['code'], $_POST['id'], $_POST['secret'], $_POST['server_local']);

    if( isset($response['token_type']) && $response['token_type'] == "Bearer" ){
         //write_to_file("zoho.txt", $response['access_token']);
        echo update_ZoHoConfig($_POST['status'], $_POST['workspace'], $_POST['id'], $_POST['secret'], $_POST['code'], $response['refresh_token'], $response['access_token'], date('U', strtotime('+'.$response['expires_in'].' seconds')), $_POST['api_OrgID'], $_POST['api_DeskID'], $_POST['server_local'], $_POST['ticket_openstat'] );
    }

}


if( isset($_POST['request']) && $_POST['request'] == 'tokenRefresh' ){

    $existing_zoho = read_ZoHoConfig();
    $response = refreshZohoAccessToken($existing_zoho);
    print_r( $response );

    if( isset($response['token_type']) && $response['token_type'] == "Bearer" ){
        echo update_ZoHoConfig($existing_zoho['enabled'], $existing_zoho['workspace_name'], $existing_zoho['OAuth']['OAuth_ClientID'], $existing_zoho['OAuth']['OAuth_ClientSecret'], $existing_zoho['OAuth']['OAuth_InitCode'], $existing_zoho['OAuth']['OAuth_RefreshToken'], $response['access_token'], date('U', strtotime('+'.$response['expires_in'].' seconds')), $existing_zoho['api_OrgID'], $existing_zoho['api_DeskDepartment'], $existing_zoho['ServerLocal'], $_POST['ticket_openstat'] );
    }

}






function read_ZoHoConfig(){
	$read_out = read_from_config();
	$config = json_decode($read_out, true);

	if( !isset($config['ZoHo']) ){
		// No ZoHo config found, generate the base required structure
		$zoho_new = array(
			'enabled' => 0,
            'workspace_name' => '',
            'api_DeskDepartment' => '',
            'api_OrgID' => '',
			'OAuth' => array(
				'OAuth_ClientID' => '',
				'OAuth_ClientSecret' => '',
				'OAuth_InitCode' => '',
				'OAuth_RefreshToken' => '',
                'OAuth_AccessToken' => '',
                'OAuth_Expire' => 0 //In Unix/EPoch time
				),
			'LastSave' => '',
            'ServerLocal' => 'AU',
            'tickets_openstatus' => 'Open,Pending,In Progress',
			);

		//Write out to file
		$config['ZoHo'] = $zoho_new;
		write_to_config( json_encode($config) );

	}

	return $config['ZoHo'];
}

function update_ZoHoConfig($status, $workspace, $oa_clientid, $oa_secret, $oa_authcode, $oa_refreshtoken, $oa_accesstoken, $oa_expiry, $orgid, $deptid, $serverlocal, $ticket_openstat){
    $read_out = read_from_config();
    $config = json_decode($read_out, true);

    // No ZoHo config found, generate the base required structure
    $zoho_new = array(
        'enabled' => $status,
        'workspace_name' => $workspace,
        'api_DeskDepartment' => $deptid,
        'api_OrgID' => $orgid,
        'OAuth' => array(
            'OAuth_ClientID' => $oa_clientid,
            'OAuth_ClientSecret' => $oa_secret,
            'OAuth_InitCode' => $oa_authcode,
            'OAuth_RefreshToken' => $oa_refreshtoken,
            'OAuth_AccessToken' => $oa_accesstoken,
            'OAuth_Expire' => $oa_expiry //In Unix/EPoch time
            ),
        'LastSave' => date('U'),
        'ServerLocal' => $serverlocal,
        'tickets_openstatus' => $ticket_openstat,
        );

    //Write out to file
    $config['ZoHo'] = $zoho_new;
    write_to_config( json_encode($config) );

    
    return true;
}



/** Initial registration - Create the RefreshToken */
function generateZohoAccessToken($authcode, $clientId, $clientSecret, $server_local){

    //Set call URL extension
    $server_url_local = ".com";
    switch( $server_local ){
        case "AU":
            $server_url_local = ".com.au";
            break;
        default:
            $server_url_local = ".com";
            break;
    }

	$queryParams = http_build_query([
        'code' => $authcode,
        'redirect_uri' => 'http://example.com/callbackurl',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code'
    ]);



    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://accounts.zoho".$server_url_local."/oauth/v2/token");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));


    // Execute the cURL request
    $response = curl_exec($ch);
    $response = json_decode($response, true);

    // Close cURL session
    curl_close($ch);

    return $response;
}


/** Refresh the OAuth Token */
function refreshZohoAccessToken($ZoHo_Config){

    //Set call URL extension
    $server_url_local = ".com";
    switch( $ZoHo_Config['ServerLocal'] ){
        case "AU":
            $server_url_local = ".com.au";
            break;
        default:
            $server_url_local = ".com";
            break;
    }

    $queryParams = http_build_query([
        'refresh_token' => $ZoHo_Config['OAuth']['OAuth_RefreshToken'],
        'client_id' => $ZoHo_Config['OAuth']['OAuth_ClientID'],
        'client_secret' => $ZoHo_Config['OAuth']['OAuth_ClientSecret'],
        'scope' => 'Desk.search.READ,Desk.tickets.READ,Desk.contacts.READ,Desk.tasks.READ',
        'redirect_uri' => 'http://example.com/callbackurl',
        'grant_type' => 'refresh_token'
    ]);



    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://accounts.zoho".$server_url_local."/oauth/v2/token");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));


    // Execute the cURL request
    $response = curl_exec($ch);
    $response = json_decode($response, true);

    // Close cURL session
    curl_close($ch);

    return $response;
}

?>