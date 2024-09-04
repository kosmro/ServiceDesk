# Service Desk Tools
 Service Desk tools I find useful or cool


## ZoHo Tickets Dash
**ZoHoDash.php**
This web php script shows some basic stats using the ZoHo Tickets API.
Currently setup to show Current Open, Closed Today, Average Open Ticket Age, Today's New Tickets (Raised), Created to Resolved radio.

![ZoHo Ticket Dash](https://github.com/kosmro/ServiceDesk/blob/main/Zoho_dash_test.png?raw=true)


#### ZoHo Setup:
 1. Head to https://api-console.zoho.com.au/ and start setup of new connection
 2. Choose type "Self Client"
 3. Copy-Paste the Secret and Client ID into the below variables
 4. Set the SCOPE of "Desk.tickets.ALL", and input the scope description (not sure if this matters)
 5. Select the Portal and an authorised Desk platform
 6. When you select Generate, it will generate the REFRESH token - set whatever time you need, typically 3 minutes will be plenty.

![Dash Setup](https://github.com/kosmro/ServiceDesk/blob/main/System_Setup.png?raw=true)





## Ring Central Dash
**RingCentralQueue.php**
This is a web php script designed to fetch status data from your RingCentral phone system and display.
To setup, you will need to generate an API Client ID and Secret, and JWT.
It shows the latest data refresh, and refreshes every 30 seconds automatically, unless checked to not do so