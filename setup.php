<html lang=en-AU>
  <head>
    <title>ZoHo Stats</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta name="author" content="Robert Kosmac">
    <script src="assets/js/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#OAuth_submit").on('click',Submit_OAuth_Register);
            $("#OAuth_refresh").on('click',Refresh_OAuth_Token);
            $("#save_ringcentral").on('click',Save_RingCentral);
            $("#GoDash").on('click', function(){
                window.location.href = "ZoHoDash.php";
            });



            //Check and read the config hiddens
            $("#zoho_enabled").val( $("#zoho_enabledflag").val() );
            $("#ringc_enabled").val( $("#rcsetup_enabledflag").val() );

        });

        /** If checkbox unchecked, reload the page every 30 seconds */
    /**    window.setTimeout( function() {
            if(!document.getElementById('ReloadEnable').checked){
                window.location.reload();
            }
        }, 30000); */



        function Submit_OAuth_Register(){

            $("#auth_results").val('');

            var fd = new FormData();
                fd.append('request', 'regRefresh' );
                fd.append('status',  $("#zoho_enabled").val() );
                fd.append('code',  $("#auth_code").val() );
                fd.append('id', $("#auth_id").val() );
                fd.append('secret', $("#auth_secret").val() );
                fd.append('workspace', $("#namespace").val() );
                fd.append('api_OrgID', $("#api_orgid").val() );
                fd.append('api_DeskID', $("#api_department").val() );
                fd.append('server_local', $("#zohoserver_local").val() );
                fd.append('ticket_openstat', $("#zohotickets_openstatus").html());

            //Send all data to the CREATE PHP script
            $.ajax({
                type: "POST",
                data: fd,
                processData: false,
                contentType: false,
                url: "processing/ZoHo_Setup.php",
                context: document.body,
                success: function(data){     
                    $("#auth_results").val(data);

                    /**
                    try{
                        var data = JSON.parse(data);
                        if( data.code == 0 ){
                        }

                    }catch(err){
                        //Make sure unusual errors are caught
                        console.log(err);
                    }
                    */
                }
            });
        }


        function Save_RingCentral(){
            $("#auth_results").val('');

            var fd = new FormData();
                fd.append('request', 'SaveConfig' );
                fd.append('status',  $("#ringc_enabled").val() );
                fd.append('client_id',  $("#rcsetup_oauth_clientid").val() );
                fd.append('client_secret', $("#rcsetup_oauth_clientsecret").val() );
                fd.append('oauth_jwt', $("#rcsetup_oauth_jwt").val() );
                fd.append('queue_name', $("#rcsetup_queueName").val() );

            //Send all data to the CREATE PHP script
            $.ajax({
                type: "POST",
                data: fd,
                processData: false,
                contentType: false,
                url: "processing/RingCentral_Setup.php",
                context: document.body,
                success: function(data){     
                    try{
                        var data = JSON.parse(data);
                        if( data.code == 0 ){
                            $("#auth_results").val('Successfully saved');
                        }

                    }catch(err){
                        //Make sure unusual errors are caught
                        $("#auth_results").val(err);
                    }
                    
                }
            });
        }


        function Refresh_OAuth_Token(){

            $("#auth_results").val('');

            var fd = new FormData();
                fd.append('request', 'tokenRefresh' );

            //Send all data to the CREATE PHP script
            $.ajax({
                type: "POST",
                data: fd,
                processData: false,
                contentType: false,
                url: "processing/ZoHo_Setup.php",
                context: document.body,
                success: function(data){     
                    //console.log(data);
                    $("#auth_results").val(data);

                    /**
                    try{
                        var data = JSON.parse(data);
                        if( data.code == 0 ){
                        }

                    }catch(err){
                        //Make sure unusual errors are caught
                        console.log(err);
                    }
                    */
                }
            });
        }
    </script>

    <style>
        html{
            width: calc(100% - 2px);
            height: fit-content;
            font-family: "Gill Sans", sans-serif;
        }
        table{
            border: none;
            border-collapse: collapse;
            width: 100%;
        }
        table.SettingsTableView{
            width: 70%;
            font-size: 10pt;
            border: none;
            border-radius: 10px;
            padding: 0.2em;
            margin-left: auto;
            margin-right: auto;
            background-color: #f2f2f2;
        }
        label{
            color: #373737;
            font-style: italic;
        }
        table td{
            /** border: 1px solid rgba(150,150,150,0.8); */
            vertical-align: top;
        }
        div.SettingSection{
            border-radius: 5px;
            background-color: #f2f2f2;
            padding: 20px;
        }
        div.CentredDiv{
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        table th{
            padding: 1em 2em;
            text-align: left;
            vertical-align: middle;
        }
        table th h2{
            border-bottom: 1px #373737 solid;
        }

        input[type='text'],input[type=password]{
            width: 90%;
            padding: 12px 20px;
            margin: 3px 0 8px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input.display_input,textarea.display_input{
            background-color: rgba(100,100,100,0.25);
            resize: none;
        }
        textarea.input_textarea{
            resize: none;
        }
        input.oauth_input{
            background-color: rgba(184,184,255,0.3);
        }

        select{
            padding: 6px 10px;
            margin: 2px 0 5px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1.1em;
            font-weight: bold;
            float: right;
        }

        select.zohoserver_local{
            float: none;
            font-weight: 100;
        }

        input[type=button] {
          width: 80%;
          color: white;
          font-weight: bold;
          padding: 1.2em 2em;
          margin: 0.5em 1em;
          border: none;
          border-radius: 4px;
          cursor: pointer;
        }
        input[type=button].GreenButton{
            background-color: #4CAF50;
        }
        input[type=button].BlueButton{
            background-color: #4c84af;
        }
        input[type=button].RedButton{
            background-color: #af4c4c;
        }


        input[type=button].GreenButton:hover{
            background-color: #2b8f2f;
        }
        input[type=button].BlueButton:hover{
            background-color: #2e79b3;
        }
        input[type=button].RedButton:hover{
            background-color: #f52a2a;
        }

    </style>
  </head>
  <body>

    <!-- ZoHo One Setup -->
    <table class="SettingsTableView">
        <tr>
            <th>
                <table>
                    <tr>
                        <td width="80%">
                            <h2>ZoHo One Configurations</h2>
                        </td>
                        <td width="20%">
                            <select class="enable_select" id="zoho_enabled" name="zoho_enabled">
                                <option value=1 >Enabled</option>
                                <option value=0 selected>Disabled</option>
                            </select>
                        </td>
                    </tr>
                </table>
               
            </th>
            <th>
                <table>
                    <tr>
                        <td width="80%">
                            <h2>RingCentral Configurations</h2>
                        </td>
                        <td width="20%">
                            <select class="enable_select" id="ringc_enabled" name="ringc_enabled">
                                <option value=1 >Enabled</option>
                                <option value=0 selected>Disabled</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </th>
        </tr>
        <tr>
            <td>
                <div class="SettingSection">
                    <table>
                        
<?php
    include 'processing/ZoHo_Setup.php';
    $zoho_config = read_ZoHoConfig();
    $auth_config = $zoho_config['OAuth'];

    //check certain values
    if( $auth_config['OAuth_Expire'] == '' || is_null($auth_config['OAuth_Expire']) ){ $zoho_oauth_exp = 'Not Generated'; }
        else{ $zoho_oauth_exp = date('h:ia, jS M Y', $auth_config['OAuth_Expire']); }
    if( $zoho_config['LastSave'] == '' || is_null($zoho_config['LastSave']) ){ $last_save_date = 'Not Generated'; }
        else{ $last_save_date = date('h:ia, jS M Y', $zoho_config['LastSave']); }

    
    print '<tr><td><label for="namespace" >ZoHo Name Space</label><br/>';
    print '<input type="hidden" id="zoho_enabledflag" value="'.$zoho_config['enabled'].'" />'; //hidden input for Enable Flag
    print '<input type="text" id="namespace" name="namespace" placeholder="ZoHo NameSpace" value="'.$zoho_config['workspace_name'].'" /></td>';
    print '<td><label for="api_orgid" >ZoHo API Organisation ID</label><br/>';
    print '<input type="text" id="api_orgid" name="api_orgid" placeholder="ZoHo API Organisation ID" value="'.$zoho_config['api_OrgID'].'" /></td></tr>';

    print '<tr><td><label for="auth_code" >OAuth Self Code</label><br/>';
    print '<input type="text" id="auth_code" name="auth_code" class="oauth_input" placeholder="Generated OAuth Link Code" value="'.$auth_config['OAuth_InitCode'].'" /></td>';
    print '<td><label for="api_department" >ZoHo Ticket Department</label><br/>';
    print '<input type="text" id="api_department" name="api_department" placeholder="ZoHo Ticket Department" value="'.$zoho_config['api_DeskDepartment'].'" /></td></tr>';

    print '<tr><td><label for="auth_id" >OAuth Client ID</label><br/>';
    print '<input type="text" id="auth_id" name="auth_id" placeholder="OAuth ID" value="'.$auth_config['OAuth_ClientID'].'" /></td>';
    print '<td><label for="auth_secret" >OAuth Client Secret</label><br/>';
    print '<input type="password" id="auth_secret" name="auth_secret" placeholder="OAuth Secret" value="'.$auth_config['OAuth_ClientSecret'].'" /></td></tr>';

    print '<tr><td><label for="auth_expire_time" >Zoho Server Location</label><br/>';
    print '<select id="zohoserver_local" class="zohoserver_local" name="zohoserver_local" >';
        switch( $zoho_config['ServerLocal'] ){
            case 'AU':
                print '<option value="AU" selected>Australia</option><option value="US" >United States</option>';
                break;
            default:
                print '<option value="AU" >Australia</option><option value="US" selected>United States</option>';
                break;
        }
    print '</select></td>';
    
    print '<td><label for="api_department" >ZoHo Ticket Open Status</label><br/>';
    print '<textarea class="input_textarea" id="zohotickets_openstatus" name="zohotickets_openstatus" placeholder="ZoHo Ticket Status: Open,In Progress,Hold" cols="25" rows="4" >'.$zoho_config['tickets_openstatus'].'</textarea></td></tr>';
    print '</tr>';

    print '<tr><td><label for="auth_expire_time" >OAuth Expiry</label><br/>';
    print '<input type="text" id="auth_expire_time" name="auth_expire_time" placeholder="OAuth Expiry" class="display_input" value="'.$zoho_oauth_exp.'" disabled/></td>';
    print '<td><label for="zoho_lastcnf_save" >Last Save</label><br/>';
    print '<input type="text" id="zoho_lastcnf_save" name="zoho_lastcnf_save" placeholder="Last Save" class="display_input" value="'.$last_save_date.'" disabled/></td></tr>';

?>
                    
                    <tr>
                        <td colspan="2"><br /></td>
                    </tr>
                    <tr>
                        <td>
                            <input type="button" class="RedButton" name="Submit" id="OAuth_submit" value="Register Auth" />
                        </td>
                        <td>
                            <input type="button" class="BlueButton" name="Refresh Tokens" id="OAuth_refresh" value="Refresh Token" />
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td>
            <div class="SettingSection">
                <table>
<?php
    include 'processing/RingCentral_Setup.php';
    $RingC_config = read_RingCentralConfig();
    $RingC_AuthConfig = $RingC_config['OAuth'];

    print '<tr><td><label for="rcsetup_queueName" >RingCentral Queue Name</label><br/>';
    print '<input type="hidden" id="rcsetup_enabledflag" value="'.$RingC_config['enabled'].'" />'; //hidden input for Enable Flag
    print '<input type="text" id="rcsetup_queueName" name="rcsetup_queueName" placeholder="RingCentral Queue Name" value="'.$RingC_config['Phone_Queue'].'" /></td>';
    print '<td><label for="rcsetup_oauth_clientid" >RingCentral OAuth Client ID</label><br/>';
    print '<input type="text" id="rcsetup_oauth_clientid" name="rcsetup_oauth_clientid" placeholder="OAuth ClientID" value="'.$RingC_AuthConfig['OAuth_ClientID'].'" /></td></tr>';

    print '<tr><td><label for="rcsetup_oauth_clientsecret" >RingCentral OAuth Secret</label><br/>';
    print '<input type="password" id="rcsetup_oauth_clientsecret" name="rcsetup_oauth_clientsecret" placeholder="OAuth Secret" value="'.$RingC_AuthConfig['OAuth_ClientSecret'].'" /></td>';
    print '<td><label for="rcsetup_oauth_jwt" >RingCentral OAuth Token</label><br/>';
    print '<input type="text" id="rcsetup_oauth_jwt" name="rcsetup_oauth_jwt" placeholder="OAuth JWT" value="'.$RingC_AuthConfig['OAuth_jwt'].'" /></td></tr>';
?>
                    <tr>
                        <td colspan="2"><br /></td>
                    </tr>
                    <tr>
                        <td><br /></td>
                        <td><input type="button" class="BlueButton" name="Save" id="save_ringcentral" value="Save" /></td>
                    </tr>
                </table>            
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="SettingSection CentredDiv">
                <hr/>
                <textarea id="auth_results" cols="60" rows="12" class="display_input" placeholder="Response output will be here"></textarea>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="SettingSection CentredDiv">
                <input type="button" class="GreenButton" name="Return To Dash" id="GoDash" value="Return To Dash" style="width:35%;" />
            </div>
        </td>
    </tr>
</table>


  </body>
</html>