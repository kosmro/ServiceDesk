<html lang=en-AU>
  <head>
    <title>Call Queues</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta name="author" content="Robert Kosmac">
    <script type="text/javascript">
        
        /** If checkbox unchecked, reload the page every 30 seconds */
        window.setTimeout( function() {
            if(!document.getElementById('ReloadEnable').checked){
                window.location.reload();
            }
        }, 30000);
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

$clientId = 'your_client_id'; // Replace with your RingCentral client ID
$clientSecret = 'your_client_secret'; // Replace with your RingCentral client secret
$jwt = 'your_generated_jwt'; // Replace with your pre-generated JWT from RingCentral



/** Authenticate with RingCentral, will get an updated Token for next actions */
function getAccessToken($clientId, $clientSecret, $jwt) {
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
function getCallQueueDetails($accessToken) {
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

function getCallQueueCalls($accessToken, $queueId) {
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
function getQueueAnalytics($accessToken, $queueId) {
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

function getTotalCallsToday($accessToken, $queueId) {
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
function displayCallQueueDetails($queues, $accessToken) {

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
            $calls = getCallQueueCalls($accessToken, $queueId);
            $activeCallCount = count($calls);
            $connectedCallCount = 0;
            foreach ($calls as $call) {
                if ($call['status'] === 'Connected') {
                    $connectedCallCount++;
                }
            }

            // Fetch analytics data for the queue
            $analytics = getQueueAnalytics($accessToken, $queue['id']);
            $averageWaitTime = $analytics['averageWaitTime'] ?? 'N/A';

            $totalCallsToday = getTotalCallsToday($accessToken, $queueId);



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
$accessToken = getAccessToken($clientId, $clientSecret, $jwt);
$queues = getCallQueueDetails($accessToken);
displayCallQueueDetails($queues, $accessToken);


?>

<br />
<input type="checkbox" id="ReloadEnable" name="ReloadEnable" value="Enable Reload">
<label for="ReloadEnable"> Pause 30 second reload?</label><br>


</body>
</html>