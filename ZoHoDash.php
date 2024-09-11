<html lang=en-AU>
  <head>
    <title>ZoHo Stats</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta name="author" content="Robert Kosmac">
    <script src="assets/js/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){

            $("#ZohoSettings").on('click', function(){
                window.location.href = "setup.php";
            });

        });
        
        /** If checkbox unchecked, reload the page every 30 seconds */
        window.setTimeout( function() {
            if(!document.getElementById('ReloadEnable').checked){
                window.location.reload();
            }
        }, 60000);
        
    </script>

    <style>
        html{
            width: calc(100% - 2px);
            height: fit-content;
            font-family: "Gill Sans", sans-serif;
        }
        div.sidebyside{
            width: calc(50% - 2em);
            padding: 1em;
            margin: none;
            display: inline-block;
            vertical-align: text-top;
        }
        table.primarystats{
            width: 100%;
            font-size: 14pt;
            background-color: rgba(150,150,150,0.8);
            border: 1px solid rgba(150,150,150,0.8);
            border-radius: 10px;
            padding: 0.2em;
        }
        table.substats{
            width: 100%;
            font-size: 14pt;
            background-color: rgba(150,150,150,0.5);
            border: 1px solid rgba(150,150,150,0.5);
            border-radius: 5px;
            padding: 0.2em;
        }
        table thead{
            font-weight: bold;
            background-color: rgba(150,150,150,0.8);
        }
        table.substats thead{
            font-weight: bold;
            background-color: rgba(150,150,150,0.5);
        }
        table tbody{
            font-weight: 100;
            border-radius: 5px;
        }
        table th,table td{
            padding: 1em 2em;
            text-align: center;
            vertical-align: middle;
        }
        table.substats th,table td{
            padding: 0.5em 1em;
            text-align: center;
            vertical-align: middle;
        }

        td.non_status{
            background-color: rgba(200,230,175,0.8); /** light grass green as the non-function colour */
        }
        td.non_statusgrey{
            background-color: rgba(170,170,170,0.8); /** just a grey */
        }
        table.substats td.non_statusgrey{
            background-color: rgba(170,170,170,0.8); /** just a grey */
        }
        td.count_low{
            background-color: rgba(145,190,230,0.8); /** blue, because it's soothing colour */
        }
        td.count_medium{
            background-color: rgba(230,170,115,0.8); /** light warm colour, because it's concerning */
        }
        td.count_high{
            background-color: rgba(230,60,60,0.8); /** Flashing bright warning */
            animation: blinker 5s linear infinite;
        }

        @keyframes blinker {
          50% {
            background-color: rgba(230,60,60,0.3);
          }
        }

    </style>
  </head>
  <body>








<?php
/**************************
 * 
 *  ZOHO ONE Processing
 * 
 * https://www.youtube.com/watch?v=ToCP_MwORAw&list=PLe5vb16RLcC5Hj-wjgSjKnd5biMc8XCr6
 * 
 *  Setup:
 *      1. Head to https://api-console.zoho.com.au/ and start setup of new connection
 *      2. Choose type "Self Client"
 *      3. Copy-Paste the Secret and Client ID into the below variables
 *      4. Set the SCOPE of "Desk.tickets.ALL", and input the scope description (not sure if this matters)
 *      5. Select the Portal and an authorised Desk platform
 *      6. When you select Generate, it will generate the REFRESH token - default is 3 minutes.
 * 
 * ************************/

include 'processing/ZoHo_Setup.php';
$zoho_config = read_ZoHoConfig();


if( date('U') >= $zoho_config['OAuth']['OAuth_Expire'] ){
    $response = refreshZohoAccessToken($zoho_config);

    if( isset($response['token_type']) && $response['token_type'] == "Bearer" ){
        update_ZoHoConfig($zoho_config['enabled'], $zoho_config['workspace_name'], $zoho_config['OAuth']['OAuth_ClientID'], $zoho_config['OAuth']['OAuth_ClientSecret'], $zoho_config['OAuth']['OAuth_InitCode'], $zoho_config['OAuth']['OAuth_RefreshToken'], $response['access_token'], date('U', strtotime('+'.$response['expires_in'].' seconds')), $zoho_config['api_OrgID'], $zoho_config['api_DeskDepartment'], $zoho_config['ServerLocal'] );


        //Update in memory for use below in the calls
        $zoho_config['OAuth']['OAuth_AccessToken'] = $response['access_token'];

        $all_zoho_tickets = openTickets_filterProcessing($zoho_config);
        $todays_zoho_tickets = createdToday_filterProcessing($zoho_config);
        $todayclosed_zoho_tickets = closedTickets_filterProcessing($zoho_config);
        $ranking_info = rankingFetch_processResults($zoho_config);

        displayZoHoTicketStats($all_zoho_tickets, $todays_zoho_tickets, $todayclosed_zoho_tickets, $ranking_info);
    }

}else{

    $all_zoho_tickets = openTickets_filterProcessing($zoho_config);
    $todays_zoho_tickets = createdToday_filterProcessing($zoho_config);
    $todayclosed_zoho_tickets = closedTickets_filterProcessing($zoho_config);
    $ranking_info = rankingFetch_processResults($zoho_config);

    //print_r( $ranking_info );

    displayZoHoTicketStats($all_zoho_tickets, $todays_zoho_tickets, $todayclosed_zoho_tickets, $ranking_info);
}





/***********************************
 *     TICKETS CREATED TODAY
 ***********************************/

function getAllZohoTickets_CreatedToday($ZoHoConfig){

    //Set call URL extension
    $server_local = ".com";
    switch( $ZoHoConfig['ServerLocal'] ){
        case "AU":
            $server_local = ".com.au";
            break;
        default:
            $server_local = ".com";
            break;
    }


    $start_day = converToTz(date('Y-m-d 00:00:00'),'UTC', date_default_timezone_get());
    $now_time = converToTz(date('Y-m-d H:i:s'),'UTC', date_default_timezone_get());
    
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    $headerParams = array("Content-Type: application/x-www-form-urlencoded",
                    "orgId: ".$ZoHoConfig['api_OrgID'],
                    "Authorization: Zoho-oauthtoken ".$ZoHoConfig['OAuth']['OAuth_AccessToken']);

    $dataParams = http_build_query([
            'createdTimeRange' => $start_day.",".$now_time,
            'from' => '0',
            'limit' => '100',
            'sortBy' => 'modifiedTime'
            ]);

    curl_setopt($ch, CURLOPT_URL, "https://desk.zoho".$server_local."/api/v1/tickets/search?".$dataParams); //I DON'T know why, but the ZoHo API is INSISTING that it be taken this way....I probs missed something
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerParams);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);

    // Execute the cURL request
    $response = curl_exec($ch);
    if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
        $response = json_encode( array('data' => array(), 'count' => 0) );
    }
    curl_close($ch);

    return $response;
}

function createdToday_filterProcessing($ZoHoConfig){

    $start_filter_count = 0;
    $process_flag = 1;
    $output_response_array = array('data' => array(), 'count' => 0); //start empty


    while($process_flag){
        $fetch_json = getAllZohoTickets_CreatedToday($ZoHoConfig, $start_filter_count);
        $data_process = json_decode($fetch_json, true);

        foreach ($data_process['data'] as $value) {
                array_push($output_response_array['data'], $value);
                $output_response_array['count'] += 1;
        }

        if($data_process['count'] > $start_filter_count ){
            $process_flag = 1; //keep going
            $start_filter_count += 100; //increment for the next page of 100 tickets to check
        }else{
            $process_flag = 0; //halt this
        }
    }
    
    return json_encode($output_response_array);
}













function getAllZohoTickets_ThisMonth($ZoHoConfig, $offset){

    //Set call URL extension
    $server_local = ".com";
    switch( $ZoHoConfig['ServerLocal'] ){
        case "AU":
            $server_local = ".com.au";
            break;
        default:
            $server_local = ".com";
            break;
    }


    $start_month = converToTz(date('Y-m-d 00:00:00', strtotime('first day of this month')),'UTC', date_default_timezone_get());
    $end_month = converToTz(date('Y-m-d 23:59:59', strtotime('last day of this month')),'UTC', date_default_timezone_get());
    
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    $headerParams = array("Content-Type: application/x-www-form-urlencoded",
                    "orgId: ".$ZoHoConfig['api_OrgID'],
                    "Authorization: Zoho-oauthtoken ".$ZoHoConfig['OAuth']['OAuth_AccessToken']);

    $dataParams = http_build_query([
            'createdTimeRange' => $start_month.",".$end_month,
            'from' => $offset,
            'limit' => '100',
            ]);

    curl_setopt($ch, CURLOPT_URL, "https://desk.zoho".$server_local."/api/v1/tickets/search?".$dataParams); //I DON'T know why, but the ZoHo API is INSISTING that it be taken this way....I probs missed something
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerParams);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);

    // Execute the cURL request
    $response = curl_exec($ch);
    if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
        $response = json_encode( array('data' => array(), 'count' => 0) );
    }
    curl_close($ch);

    return $response;
}

function rankingFetch_filterProcessing($ZoHoConfig){

    $start_filter_count = 0;
    $process_flag = 1;
    $output_response_array = array('data' => array(), 'count' => 0); //start empty


    while($process_flag){
        $fetch_json = getAllZohoTickets_ThisMonth($ZoHoConfig, $start_filter_count);
        $data_process = json_decode($fetch_json, true);

        foreach ($data_process['data'] as $value) {
                array_push($output_response_array['data'], $value);
                $output_response_array['count'] += 1;
        }

        if($data_process['count'] > $start_filter_count ){
            $process_flag = 1; //keep going
            $start_filter_count += 100; //increment for the next page of 100 tickets to check
        }else{
            $process_flag = 0; //halt this
        }
    }
    
    return json_encode($output_response_array);
}

function rankingFetch_processResults($ZoHoConfig){

    $tickets_thismonth = rankingFetch_filterProcessing($ZoHoConfig);
    $tickets_thismonth = json_decode($tickets_thismonth, true);
    $tickets_thismonth = $tickets_thismonth['data'];
    $customer_accounts = array();
    $staff_close_account = array();

    if(count($tickets_thismonth) > 0){

        foreach($tickets_thismonth as $arr_item) {            
            $acct_name = 'Unknown';
            $acct_id = '0';
            $ticket_assigID = '0';
            $ticket_assigName = 'Unassigned';
            
            if( isset($arr_item['contact']['account']['name']) ){ $acct_name = $arr_item['contact']['account']['name']; }
                else if( isset($arr_item['contact']['account']['accountName']) ){ $acct_name = $arr_item['contact']['account']['accountName']; }
            if( isset($arr_item['contact']['account']['id']) ){ $acct_id = $arr_item['contact']['account']['id']; }


            if( isset($arr_item['assignee']['firstName']) && isset($arr_item['assignee']['lastName']) ){
                $ticket_assigName = $arr_item['assignee']['firstName'] . ' ' . $arr_item['assignee']['lastName'];
            }else if( isset($arr_item['assignee']['firstName']) && !isset($arr_item['assignee']['lastName']) ){
                $ticket_assigName = $arr_item['assignee']['firstName'];
            }else if( !isset($arr_item['assignee']['firstName']) && isset($arr_item['assignee']['lastName']) ){
                $ticket_assigName = $arr_item['assignee']['lastName'];
            }
            if( isset($arr_item['assignee']['id']) ){ $ticket_assigID = $arr_item['assignee']['id']; }


            

            if( !isset($customer_accounts[$acct_id]) ){
                $customer_accounts[$acct_id] = [ 'id' => $acct_id, 'name' => $acct_name, 'count' => 1, ];
            }else{
                $customer_accounts[$acct_id]['count'] += 1;
            }

            if($arr_item['statusType'] == "Closed" && $arr_item['closedTime'] != "" && !is_null($arr_item['closedTime']) ){
                if( !isset($staff_close_account[$ticket_assigID]) ){
                    $staff_close_account[$ticket_assigID] = [ 'id' => $ticket_assigID, 'name' => $ticket_assigName, 'count' => 1, ];
                }else{
                    $staff_close_account[$ticket_assigID]['count'] += 1;
                }
            }
        }

        
    }
    


    //print_r( $customer_accounts );

    //NEED TO ADD FILTER TO ONLY SHOW CLOSE OWNERS
    return json_encode( ['customer_ranking' => $customer_accounts, 'staff_ownership' => $staff_close_account] );
}






function converToTz($time="",$toTz='',$fromTz=''){   
    // timezone by php friendly values
    $date = new DateTime($time, new DateTimeZone($fromTz));
    $date->setTimezone(new DateTimeZone($toTz));
    $time= $date->format('Y-m-d\TH:i:s.000\Z');

    return $time;
}

















/**********************************************************************
 *                      ALL OPEN TICKETS
 **********************************************************************/

function getAllOpenZohoTickets($ZoHoConfig, $offset){

    //Set call URL extension
    $server_local = ".com";
    switch( $ZoHoConfig['ServerLocal'] ){
        case "AU":
            $server_local = ".com.au";
            break;
        default:
            $server_local = ".com";
            break;
    }
    
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    $headerParams = array("Content-Type: application/x-www-form-urlencoded",
                    "orgId: ".$ZoHoConfig['api_OrgID'],
                    "Authorization: Zoho-oauthtoken ".$ZoHoConfig['OAuth']['OAuth_AccessToken']);

    $dataParams = http_build_query([
            'from' => $offset,
            'limit' => '100',
            'sortBy' => 'modifiedTime'
            ]);

    curl_setopt($ch, CURLOPT_URL, "https://desk.zoho".$server_local."/api/v1/tickets/search?".$dataParams); //I DON'T know why, but the ZoHo API is INSISTING that it be taken this way....I probs missed something
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerParams);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);

    // Execute the cURL request
    $response = curl_exec($ch);

    if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
        $response = json_encode( array('data' => array(), 'count' => 0) );
    }

    curl_close($ch);

    return $response;
}

function openTickets_filterProcessing($ZoHoConfig){

    $start_filter_count = 0;
    $process_flag = 1;
    $output_response_array = array('data' => array(), 'count' => 0); //start empty


    while($process_flag){
        $fetch_json = getAllOpenZohoTickets($ZoHoConfig, $start_filter_count);
        $data_process = json_decode($fetch_json, true);

        foreach ($data_process['data'] as $value) {
                array_push($output_response_array['data'], $value);
                $output_response_array['count'] += 1;
        }

        if($data_process['count'] > $start_filter_count ){
            $process_flag = 1; //keep going
            $start_filter_count += 100; //increment for the next page of 100 tickets to check
        }else{
            $process_flag = 0; //halt this
        }
    }
    
    return json_encode($output_response_array);
}









/**********************************************************************
 *                     TICKETS CLOSED TODAY
 **********************************************************************/

function getAllZohoTickets_ClosedToday($ZoHoConfig, $offset){

    //Set call URL extension
    $server_local = ".com";
    switch( $ZoHoConfig['ServerLocal'] ){
        case "AU":
            $server_local = ".com.au";
            break;
        default:
            $server_local = ".com";
            break;
    }


    $start_day = converToTz(date('Y-m-d 00:00:00', strtotime('-2weeks')),'UTC', date_default_timezone_get()); //starting from 2 weeks ago
    $now_time = converToTz(date('Y-m-d H:i:s'),'UTC', date_default_timezone_get());
    
    
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    $headerParams = array("Content-Type: application/x-www-form-urlencoded",
                    "orgId: ".$ZoHoConfig['api_OrgID'],
                    "Authorization: Zoho-oauthtoken ".$ZoHoConfig['OAuth']['OAuth_AccessToken']);

    $dataParams = http_build_query([
            'status' => "\${CLOSED}",
            'from' => $offset,
            'limit' => '100',
            'modifiedTimeRange' => $start_day.",".$now_time,
            'sortBy' => 'modifiedTime'
            ]);
    
    curl_setopt($ch, CURLOPT_URL, "https://desk.zoho".$server_local."/api/v1/tickets/search?".$dataParams); //I DON'T know why, but the ZoHo API is INSISTING that it be taken this way....I probs missed something
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerParams);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);

    // Execute the cURL request
    $response = curl_exec($ch);
    if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
        $response = json_encode( array('data' => array(), 'count' => 0) );
    }

    curl_close($ch);

    return $response;
}

function closedTickets_filterProcessing($ZoHoConfig){
    $day_start_unix = date('U', strtotime("midnight"));
    $start_filter_count = 0;
    $process_flag = 1;
    $output_response_array = array('data' => array(), 'count' => 0); //start empty


    while($process_flag){
        $fetch_json = getAllZohoTickets_ClosedToday($ZoHoConfig, $start_filter_count);
        $data_process = json_decode($fetch_json, true);

        foreach ($data_process['data'] as $value) {
            if( date("U", strtotime($value['closedTime'])) >= $day_start_unix ){
                array_push($output_response_array['data'], $value);
                $output_response_array['count'] += 1;
            }
        }

        if($data_process['count'] > $start_filter_count ){
            $process_flag = 1; //keep going
            $start_filter_count += 100; //increment for the next page of 100 tickets to check
        }else{
            $process_flag = 0; //halt this
        }
    }
    
    return json_encode($output_response_array);
}










/**********************************************************************
 *                 OUTPUT DISPLAY HEADER TABLE
 **********************************************************************/

/** Output the active call count for each queue */
function displayZoHoTicketStats($tickets, $today_tickets, $today_closed, $ticket_rankings){
    $avgwait_timehigh_seconds = 86400; //24 hours

    $ticket_rankings = json_decode($ticket_rankings,true);
    $ticket_rankings_cust = $ticket_rankings['customer_ranking'];
        usort($ticket_rankings_cust, function($a, $b){ return strcmp($b['count'] , $a['count']); });
    $ticket_rankings_owners = $ticket_rankings['staff_ownership'];
        usort($ticket_rankings_owners, function($a, $b){ return strcmp($b['count'] , $a['count']); });

    $tickets = json_decode($tickets,true);
    $tickets = $tickets['data'];

    $today_tickets = json_decode($today_tickets,true);
    //$today_tickets = $today_tickets['data'];
    $today_tickets_count = $today_tickets['count'];

    $today_closed = json_decode($today_closed,true);
    //$today_closed = $today_closed['data'];
    $today_closed_count = $today_closed['count'];
    $close_open_ratio = round( ($today_closed_count / $today_tickets_count) * 100 );
        if( is_nan($close_open_ratio) ){ $close_open_ratio = 0; }



    $callqueue_highlimit = 20; // 20 calls in queue
    $now_time = time();
    $total_time_seconds = 0;


    $output_html =  '<table class="primarystats">
                        <thead>
                            <tr>
                                <th>Active</th>
                                <th>Avg Age</th>
                                <th>Raised</th>
                                <th>Resolved</th>
                                <th>Resolve Ratio</th>
                            </tr>
                        </thead>
                        <tbody>'; //start table

    /** Ticket Stat Assembly */
    if(count($tickets) === 0){
        $output_html.= "<tr><td colspan=\"5\" class=\"non_statusgrey\">No Tickets</td></tr>";
    } else {
        foreach($tickets as $ticket){
            $createdTime = strtotime($ticket['createdTime']);
            $total_time_seconds += round(abs($now_time - $createdTime),2); //time open in seconds
            //$queueStatus = $queue['status'];
        }

        $calc_avg_seconds = round($total_time_seconds / count($tickets));


        $avg_tickettime_class = "non_statusgrey";
        $resolve_ratio_class = "non_statusgrey";
        
        if($calc_avg_seconds <= ($avgwait_timehigh_seconds * 0.30)){
            $avg_tickettime_class = "count_low";
        }else if($calc_avg_seconds > ($avgwait_timehigh_seconds * 0.30) && $calc_avg_seconds < $avgwait_timehigh_seconds){
            $avg_tickettime_class = "count_medium";
        }else if($calc_avg_seconds >= $avgwait_timehigh_seconds){
            $avg_tickettime_class = "count_high";
        }

        if($close_open_ratio >= 80){ //80% is good
            $resolve_ratio_class = "count_low";
        }else if($close_open_ratio < 80 && $close_open_ratio >= 30){
            $resolve_ratio_class = "count_medium";
        }else if($close_open_ratio < 30 && $close_open_ratio < 0){
            $resolve_ratio_class = "count_high";
        }else if($close_open_ratio == 0){
            $resolve_ratio_class = "non_statusgrey";
        }



        $output_html .= "<tr><td class=\"non_statusgrey\">" . count($tickets) ."</td>";
        $output_html .= "<td class=\"".$avg_tickettime_class."\">" . humanTiming( $calc_avg_seconds ) . "</td>";
        $output_html .= "<td class=\"non_status\">" . $today_tickets_count . "</td>";
        $output_html .= "<td class=\"non_status\">" . $today_closed_count . "</td>";
        $output_html .= "<td class=\"".$resolve_ratio_class."\">" . $close_open_ratio . "%</td>";
    }



    $output_html .=  "</tbody>
                    </table>";


    // OUTPUT STAFF STATUS RANKINGS
    $output_html .= '<div class="sidebyside"><table class="substats">
                        <thead>
                            <tr>
                                <th colspan="2">Highest 5 Customers</th>
                            </tr>
                        </thead>
                        <tbody>';

    for($i=0; $i<sizeof($ticket_rankings_cust); $i++){
        $output_html .= "<tr><td class=\"non_statusgrey\">" . $ticket_rankings_cust[$i]['name'] . "</td>";
        $output_html .= "<td class=\"non_statusgrey\"><strong>"  . $ticket_rankings_cust[$i]['count'] . "</strong></td></tr>";
    }

    $output_html .=  "</tbody>
                    </table></div>";


    // OUTPUT ACCOUNT STATUS RANKINGS
    $output_html .= '<div class="sidebyside"><table class="substats">
                        <thead>
                            <tr>
                                <th colspan="2">Top 5 Closing Owners</th>
                            </tr>
                        </thead>
                        <tbody>';

    for($i=0; $i<sizeof($ticket_rankings_owners); $i++){
        $output_html .= "<tr><td class=\"non_statusgrey\">" . $ticket_rankings_owners[$i]['name'] . "</td>";
        $output_html .= "<td class=\"non_statusgrey\"><strong>"  . $ticket_rankings_owners[$i]['count'] . "</strong></td></tr>";
    }

    $output_html .=  "</tbody>
                    </table></div>";







    print $output_html;
    print "Last refresh: ".date("h:i:sa, j/n/Y");
}










/**

function getAll_custAccountTickets_thisMonth($ZoHoConfig, $offset){

     //Set call URL extension
    $server_local = ".com";
    switch( $ZoHoConfig['ServerLocal'] ){
        case "AU":
            $server_local = ".com.au";
            break;
        default:
            $server_local = ".com";
            break;
    }


    $start_day = converToTz(date('Y-m-01 00:00:00'),'UTC', date_default_timezone_get()); //starting from 2 weeks ago
    $now_time = converToTz(date('Y-m-d H:i:s'),'UTC', date_default_timezone_get());
    
    
    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    $headerParams = array("Content-Type: application/x-www-form-urlencoded",
                    "orgId: ".$ZoHoConfig['api_OrgID'],
                    "Authorization: Zoho-oauthtoken ".$ZoHoConfig['OAuth']['OAuth_AccessToken']);

    $dataParams = http_build_query([
            'status' => "\${CLOSED}",
            'from' => $offset,
            'limit' => '100',
            'modifiedTimeRange' => $start_day.",".$now_time,
            'sortBy' => 'modifiedTime'
            ]);
    
    curl_setopt($ch, CURLOPT_URL, "https://desk.zoho".$server_local."/api/v1/tickets/search?".$dataParams); //I DON'T know why, but the ZoHo API is INSISTING that it be taken this way....I probs missed something
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerParams);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParams);

    // Execute the cURL request
    $response = curl_exec($ch);
    if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
        $response = json_encode( array('data' => array(), 'count' => 0) );
    }

    curl_close($ch);

    return $response;

}
*/


/** Calculate time from a start date/time until now.
 * 
 * Orig Source: https://stackoverflow.com/questions/2915864/php-how-to-find-the-time-elapsed-since-a-date-time
 */ 
function humanTiming($time){

    $time = ($time<1)? 1 : $time; //not sure what this does
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = round($time / $unit,1);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }

}



?>

<br />
<input type="checkbox" id="ReloadEnable" name="ReloadEnable" value="Enable Reload">
<label for="ReloadEnable"> Pause 60 second reload?</label><br>
<input type="button" name="Dash Settings" id="ZohoSettings" value="Dash Settings" />


</body>
</html>