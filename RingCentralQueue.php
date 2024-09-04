<html lang=en-AU>
  <head>
    <title>Call Queues</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta name="author" content="Robert Kosmac">
    <script src="assets/js/jquery.min.js"></script>
    <script type="text/javascript">
        
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
        table{
            width: 100%;
            font-size: 14pt;
            background-color: rgba(150,150,150,0.8);
            border: 1px solid rgba(150,150,150,0.8);
            border-radius: 10px;
            padding: 0.2em;
        }
        table thead{
            font-weight: bold;
            background-color: rgba(150,150,150,0.8);
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

        td.non_status{
            background-color: rgba(200,230,175,0.8); /** light grass green as the non-function colour */
        }
        td.non_statusgrey{
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
 * RING CENTRAL Processing
 * 
 * ************************/

include 'processing/RingCentral_Setup.php';
$RingCnt_Config = read_RingCentralConfig();

$ringcentral_clientId = $RingCnt_Config['OAuth']['OAuth_ClientID']; // RingCentral client ID
$ringcentral_clientSecret = $RingCnt_Config['OAuth']['OAuth_ClientSecret']; // RingCentral client secret
$ringcentral_jwt = $RingCnt_Config['OAuth']['OAuth_jwt']; // Pre-generated JWT from RingCentral



/** Authenticate with RingCentral, will get an updated Token for next actions */
function getRingAccessToken($clientId, $clientSecret, $jwt) {
    $url = 'https://platform.ringcentral.com/restapi/oauth/token';
    $headers = [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching access token: ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['access_token'];
}







/** Get the queue data */
function getRingCallQueueDetails($accessToken) {
    $url = 'https://platform.ringcentral.com/restapi/v1.0/account/~/call-queues';
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching call queue data: ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['records'];
}

function getRingCallQueueCalls($accessToken, $queueId) {
    $url = "https://platform.ringcentral.com/restapi/v1.0/account/~/extension/$queueId/active-calls"; // Example endpoint
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching call queue active calls: ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['records'];
}
function getRingQueueAnalytics($accessToken, $queueId) {
    $url = "https://analytics.ringcentral.com/platform/v1/queues/$queueId/summary"; // Example endpoint, check the actual API documentation
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching queue analytics: ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    return $result;
}

function getRingTotalCallsToday($accessToken, $queueId) {
    $url = 'https://platform.ringcentral.com/restapi/v1.0/account/~/extension/' . $queueId . '/call-log';
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];
    
    // Get today's date in ISO 8601 format
    $today = date('Y-m-d') . 'T00:00:00.000Z';

    $queryParams = http_build_query([
        'dateFrom' => $today,
        'dateTo' => date('Y-m-d\TH:i:s.000\Z'), // Current time
        'type' => 'Voice'
    ]);

    $ch = curl_init("$url?$queryParams");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching call log data: ' . curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($response, true);
    return count($result['records']);
}







/** Output the active call count for each queue */
function displayRingCallQueueDetails($queues, $accessToken) {

    $display_waitlimit_seconds = 120; // 2 minute wait time
    $callqueue_highlimit = 20; // 20 calls in queue



    $output_html =  "<table>
                        <thead>
                            <tr>
                                <th></th>
                                <th>Waiting</th>
                                <th>Active</th>
                                <th>Avg Wait</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>"; //start table


    /** Call Queue Count */
    if (count($queues) === 0) {
        $output_html.= "<tr><td colspan=\"5\" class=\"non_statusgrey\">No active queues</td></tr>";
    } else {
        foreach ($queues as $queue) {
            $queueId = $queue['id'];
            $queueName = $queue['name'];
            $queueStatus = $queue['status'];


            // Fetch call details for the queue
            $calls = getRingCallQueueCalls($accessToken, $queueId);
            $activeCallCount = count($calls);
            $connectedCallCount = 0;
            foreach ($calls as $call) {
                if ($call['status'] === 'Connected') {
                    $connectedCallCount++;
                }
            }

            // Fetch analytics data for the queue
            $analytics = getRingQueueAnalytics($accessToken, $queue['id']);
            $averageWaitTime = $analytics['averageWaitTime'] ?? 'N/A';

            $totalCallsToday = getRingTotalCallsToday($accessToken, $queueId);



            $active_call_class = "non_statusgrey";
            $average_wait_class = "non_statusgrey";
            
            if($activeCallCount <= ($display_waitlimit_seconds * 0.25)){
                $active_call_class = "count_low";
            }else if($activeCallCount > ($display_waitlimit_seconds * 0.25) && $activeCallCount <= ($display_waitlimit_seconds * 0.75)){
                $active_call_class = "count_medium";
            }else if($activeCallCount > ($display_waitlimit_seconds * 0.75)){
                $active_call_class = "count_high";
            }


            if($averageWaitTime <= ($display_waitlimit_seconds * 0.25)){
                $average_wait_class = "count_low";
            }else if($averageWaitTime > ($display_waitlimit_seconds * 0.25) && $averageWaitTime <= ($display_waitlimit_seconds * 0.75)){
                $average_wait_class = "count_medium";
            }else if($averageWaitTime > ($display_waitlimit_seconds * 0.75)){
                $average_wait_class = "count_high";
            }





            $output_html .= "<tr><td class=\"non_statusgrey\">" . $queueName ."[" . $queueStatus . "]</td>";
            $output_html .= "<td class=\"".$active_call_class."\">" . $activeCallCount . "</td>";
            $output_html .= "<td class=\"non_status\">" . $connectedCallCount . "</td>";
            $output_html .= "<td class=\"".$average_wait_class."\">" . $averageWaitTime . " seconds</td>";
            $output_html .= "<td class=\"non_status\">" . $totalCallsToday . "</td>";
        }
    }


    $output_html .=  "</tbody>
                    </table>
                    <br /><br />";


    print $output_html;
    print "Last refresh: ".date("h:i:sa, j/n/Y");
}


/** Call above scripts */
$ring_accessToken = getRingAccessToken($ringcentral_clientId, $ringcentral_clientSecret, $ringcentral_jwt);
$ringcentral_queues = getRingCallQueueDetails($ring_accessToken);
displayRingCallQueueDetails($ringcentral_queues, $ring_accessToken);












/**************************
 * 
 *  ZOHO ONE Processing
 * 
 * 
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


?>

<br />
<input type="checkbox" id="ReloadEnable" name="ReloadEnable" value="Enable Reload">
<label for="ReloadEnable"> Pause 60 second reload?</label><br>


</body>
</html>