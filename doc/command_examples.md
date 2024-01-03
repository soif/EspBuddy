# EspBuddy Command Examples

This document shows the terminal output from various EspBuddy commands.


## Main EspBuddy commands


### `# espbuddy help`

List of all EspBuddy commands.

```plaintext
EspBuddby v2.50 ( EspTool v3.3.3 )

* Usage             : espbuddy COMMAND [TARGET] [options]

* Valid COMMANDS : 
  - upload          : Build and/or Upload current repo version to Device(s)
  - upgrade         : Upgrade device(s) firmware
  - build           : Build device(s) firmware
  - backup          : Download and archive settings from the remote device
  - monitor         : Monitor device connected to the serial port
  - server          : Launch Firmwares WebServer
  - send            : Send commands to device
  - status          : Show Device's Information
  - version         : Show remote device version
  - reboot          : Reboot Device(s)
  - gpios           : Test all Device's GPIOs
  - ping            : Ping Device(s)
  - factory         : Download, get information on the latest factory releases
  - sonodiy         : Discover, Control or Flash Sonoff devices in DIY mode
  - self            : Get current, latest or update EspBuddy version
  - repo_version    : Parse the current repository (REPO) version. REPO is a supported repository (espurna, espeasy, tasmota or wled)
  - repo_pull       : Git Pull the local repository (REPO). REPO is a supported repository (espurna, espeasy, tasmota or wled)
  - list_hosts      : List all hosts defined in config.php
  - list_configs    : List all available configurations, defined in config.php
  - list_repos      : List all available repositories, defined in config.php
  - help            : Show full help

* Commands Usage : 
  - upload          : espbuddy upload       TARGET [options, auth_options, upload_options]
  - upgrade         : espbuddy upgrade      TARGET [options, auth_options, upload_options]
  - build           : espbuddy build        TARGET [options]
  - backup          : espbuddy backup       TARGET [options, auth_options]
  - monitor         : espbuddy monitor      [TARGET] [options]
  - server          : espbuddy server       [ROOT_DIR]
  - send            : espbuddy send         TARGET COMMAND|CMD_SET [options, auth_options]
  - status          : espbuddy status       TARGET [options, auth_options]
  - version         : espbuddy version      TARGET [options, auth_options]
  - reboot          : espbuddy reboot       TARGET [options, auth_options]
  - gpios           : espbuddy gpios        TARGET [options, auth_options]
  - ping            : espbuddy ping         TARGET [options]
  - factory         : espbuddy factory      ACTION [options]
  - sonodiy         : espbuddy sonodiy      ACTION [options]
  - self            : espbuddy self         ACTION [options]
  - repo_version    : espbuddy repo_version REPO
  - repo_pull       : espbuddy repo_pull    REPO
  - list_hosts      : espbuddy list_hosts   
  - list_configs    : espbuddy list_configs 
  - list_repos      : espbuddy list_repos   
  - help            : espbuddy help         

---------------------------------------------------------------------------------
+ TARGET            : Target of the command. Either:
                       - the Host's ID (defined in $cfg['hosts'] from config.php). This is the easiest way!
                       - an IP address or a Hostname. (Most of the time a --repo or --conf would also be needed!)
                       - 'all' (for commands supporting batch mode) loops thru all defined Hosts (defined from config.php)

+ COMMAND|CMD_SET   : Command(s) to send. Either:
                       - a single command as "command [value]" (following the the device's command own syntax)
                       - a commands List's ID (defined in $cfg['commands'] from config.php), 

+ ROOT_DIR          : Root directory (for the built-in Web Server). Either:
                       - a REPO to only serves from the /espb_backup/_Factory/REPO/ folder
                       - an Host ID (or a Host folder) to only serves from its /espb_backup/folder
                       - an (absolute) path of a directory
                       - when left blank, it defaults to the /espb_backup/ folder (prefered way)

+ OPTIONS :
    -y              : Automatically set YES to confirm "Yes/No" dialogs
    -d              : Dry Run. Show commands but don't apply them
    -v              : Verbose mode
    -j              : Displays result as JSON (only for 'send', 'status', 'sonodiy api' commands)
    -D              : Debug mode (shows PHP errors)
    --conf=xxx      : Config name to use (overrides per host settings)
    --repo=xxx      : Repository to use (overrides per host settings)

+ UPLOAD_OPTIONS :
    -b              : Build before Flashing/Uploading/Upgrading firmware
    -w              : Wire Mode : Upload using the serial port instead of the default OTA
    -e              : In Wire Mode, erase flash first, then upload
    -m              : Switch to serial monitor after upload
    -p              : Upload previous firmware backuped (instead of the latest build)
    -s              : Skip Intermediate OTA Upload (when 2steps mode is  set)
    -c              : When using --firm, make a copy instead of a symbolic link
    --port=xxx      : Serial port to use (override main or per host serial port)
    --rate=xxx      : Serial port speed to use (override main or per host serial port)
    --firm=xxx      : Full path to the firmware file to upload (override latest build one)
    --from=REPO     : Migrate from REPO to the selected config

+ AUTH_OPTIONS :
    --login=xxx     : Login name (overrides host or per config login)
    --pass=xxx      : Password (overrides host or per config password)
```

----------



### `# espbuddy version led2`

Grab the remote version of the 'led2' host. *'led2' is an host defined from the config.php file.*

```plaintext


*** Remote 'led2' (tasmota) Version is	: 9.4.0 


```

----------



### `# espbuddy send led2 Status 1`

Send the 'Status 1' command to the 'led2' host.

```plaintext



** Sending commands  *************************************************************************************************************
- StatusPRM ====================================================
    - Baudrate                      : 115200
    - SerialConfig                  : 8N1
    - GroupTopic                    : tasmotas
    - OtaUrl                        : http://ota.tasmota.com/tasmota/release/tasmota.bin.gz
    - RestartReason                 : Exception
    - Uptime                        : 22T11:56:54
    - StartupUTC                    : 2022-01-22T23:08:58
    - Sleep                         : 50
    - CfgHolder                     : 4617
    - BootCount                     : 1136
    - BCResetTime                   : 2020-12-27T21:08:55
    - SaveCount                     : 4756
    - SaveAddress                   : F8000


```

----------



### `# espbuddy status  10.1.209.32 --repo=espeasy`

Show Device information.

```plaintext


Selected Host      : sensor2.lo.lo
       + Host Name : sensor2.lo.lo
       + Host IP   : 10.1.209.32
       + Serial    : /dev/tty.wchusbserialfa140	at 115200 bauds

Selected Repo      : espeasy


- System =======================================================
    - Load                          : 20.57
    - Load LC                       : 1272
    - Build                         : 20116
    - Git Build                     : HEAD,mega-20211224,#16308606e/02Feb-18.43
    - System Libraries              : ESP82xx Core 2843a5ac, NONOS SDK 2.2.2-dev(38a443e), LWIP: 2.1.2 PUYA support
    - Plugin Count                  : 48
    - Plugin Description            : [Normal]
    - Local Time                    : 2022-02-14 12:01:14
    - Time Source                   : NTP
    - Time Wander                   : 0.015
    - Use NTP                       : true
    - Unit Number                   : 32
    - Unit Name                     : sensor2_Test_NodeMcu
    - Uptime                        : 4188
    - Uptime (ms)                   : 251271613
    - Last Boot Cause               : Soft Reboot
    - Reset Reason                  : Software/System restart
    - CPU Eco Mode                  : false
    - Heap Max Free Block           : 7880
    - Heap Fragmentation            : 33
    - Free RAM                      : 11912
    - Free Stack                    : 3488
    - Sunrise                       : 7:10
    - Sunset                        : 19:17
    - Timezone Offset               : 60
- WiFi =========================================================
    - Hostname                      : sensor2-Test-NodeMcu-32
    - mDNS                          : sensor2-Test-NodeMcu-32.local
    - IP Config                     : Static
    - IP Address                    : 10.1.209.32
    - IP Subnet                     : 255.255.0.0
    - Gateway                       : 10.1.11.1
    - STA MAC                       : 5C:CF:7F:5A:44:0E
    - DNS 1                         : 10.1.10.1
    - DNS 2                         : (IP unset)
    - SSID                          : iot
    - BSSID                         : EA:94:F6:06:2D:87
    - Channel                       : 11
    - Encryption Type               : WPA2/PSK
    - Connected msec                : 102229000
    - Last Disconnect Reason        : 8
    - Last Disconnect Reason str    : (8) Assoc leave
    - Number Reconnects             : 1
    - Configured SSID1              : iot
    - Force WiFi B/G                : false
    - Restart WiFi Lost Conn        : false
    - Force WiFi No Sleep           : false
    - Periodical send Gratuitous ARP : false
    - Max WiFi TX Power             : 17.5
    - Current WiFi TX Power         : 14
    - WiFi Sensitivity Margin       : 3
    - Send With Max TX Power        : false
    - Use Last Connected AP from RTC : false
    - RSSI                          : -66
- Sensors ======================================================
    - 0 .................................
        - TaskValues ........................
            - 0 .................................
                - ValueNumber                   : 1
                - Name                          : Temperature
                - NrDecimals                    : 2
                - Value                         : 20.3
            - 1 .................................
                - ValueNumber                   : 2
                - Name                          : Humidity
                - NrDecimals                    : 2
                - Value                         : 52.82
        - DataAcquisition ...................
            - 0 .................................
                - Controller                    : 1
                - IDX                           : 908
                - Enabled                       : true
            - 1 .................................
                - Controller                    : 2
                - Enabled                       : false
            - 2 .................................
                - Controller                    : 3
                - Enabled                       : false
        - TaskInterval                  : 30
        - Type                          : Environment - SHT30/31/35 [TESTING]
        - TaskName                      : Capteur
        - TaskDeviceNumber              : 68
        - TaskEnabled                   : true
        - TaskNumber                    : 1
    - 1 .................................
        - TaskValues ........................
            - 0 .................................
                - ValueNumber                   : 1
                - Name                          : Temperature
                - NrDecimals                    : 2
                - Value                         : 19.98
            - 1 .................................
                - ValueNumber                   : 2
                - Name                          : Humidity
                - NrDecimals                    : 2
                - Value                         : 51.5
        - DataAcquisition ...................
            - 0 .................................
                - Controller                    : 1
                - IDX                           : 909
                - Enabled                       : true
            - 1 .................................
                - Controller                    : 2
                - Enabled                       : false
            - 2 .................................
                - Controller                    : 3
                - Enabled                       : false
        - TaskInterval                  : 30
        - Type                          : Environment - SI7021/HTU21D
        - TaskName                      : Sonde
        - TaskDeviceNumber              : 14
        - TaskEnabled                   : true
        - TaskNumber                    : 2
    - 2 .................................
        - TaskValues ........................
            - 0 .................................
                - ValueNumber                   : 1
                - Name                          : Count
                - NrDecimals                    : 2
            - 1 .................................
                - ValueNumber                   : 2
                - Name                          : Total
                - NrDecimals                    : 2
            - 2 .................................
                - ValueNumber                   : 3
                - Name                          : Time
                - NrDecimals                    : 2
        - DataAcquisition ...................
            - 0 .................................
                - Controller                    : 1
                - Enabled                       : false
            - 1 .................................
                - Controller                    : 2
                - Enabled                       : false
            - 2 .................................
                - Controller                    : 3
                - Enabled                       : false
        - TaskInterval                  : 60
        - Type                          : Generic - Pulse counter
        - TaskName                      : Eau
        - TaskDeviceNumber              : 3
        - TaskEnabled                   : false
        - TaskNumber                    : 3
    - 3 .................................
        - TaskValues ........................
            - 0 .................................
                - ValueNumber                   : 1
                - Name                          : State
        - DataAcquisition ...................
            - 0 .................................
                - Controller                    : 1
                - Enabled                       : false
            - 1 .................................
                - Controller                    : 2
                - Enabled                       : false
            - 2 .................................
                - Controller                    : 3
                - Enabled                       : false
        - Type                          : Switch input - Switch
        - TaskName                      : sw
        - TaskDeviceNumber              : 1
        - TaskEnabled                   : true
        - TaskNumber                    : 4
- TTL                           : 30000


```

----------



### `# espbuddy upload led2`

Upload the latest firmware to the 'led2' host, using an intermediate OTA firmware *as set in the 'led2' configuration, from the config.php file.*

```plaintext
Selected Host      : led2
       + Host Name : led2.lo.lo
       + Host IP   : 10.1.209.2

Selected Config    : espurna_mh20

Processing 1 host(s) : 

##### led2.lo.lo                    (10.1.209.2    ) ##### : 
** Using LATEST Firmware (Compiled on 14 Jan 2019 - 04:01::16 ) : Firmware-espurna_mh20-(fix_domoticz_rgb_idx,1.13.3,#a7c60f3).bin 

** Uploading Intermediate Uploader Firmware **************************************************************************************

** Waiting for ESP to be back online 1 2 3 4 5 6 7 8 9 10 11 12 13 14  **********

** Uploading Final Firmware ******************************************************************************************************


```

----------



### `# espbuddy self help`

EspBuddy self maintenance tools.

```plaintext
* Usage             : espbuddy self ACTION [options]

* Valid 'self' Actions : 
  - version         : Show EspBuddy version
  - latest          : Show the lastest version available on the 'master' branch
  - avail           : Show all versions available
  - log             : Show EspBuddy history between current version and VERSION (latest on master branch, if not set)
  - update          : Update EspBuddy to the latest version

* 'self' Actions Usage : 
  - version         : espbuddy self version 
  - latest          : espbuddy self latest 
  - avail           : espbuddy self avail  
  - log             : espbuddy self log    [VERSION]
  - update          : espbuddy self update [TAG|VERSION|BRANCH]


```





##  Sonoff DIY (sonodiy) specific commands


### `# espbuddy sonodiy help`

Tasks for the 'sonodiy' command.

```plaintext
* Usage             : espbuddy sonodiy ACTION [options]

* Valid 'sonodiy' Actions : 
  - help            : Show Sonoff DIY Help
  - scan            : Scan Sonoff devices to find their IP & deviceID
  - test            : Toggle relay to verify communication
  - flash           : (EXPERIMENTAL: see issue #20 on GitHub) Upload a custom firmware (508KB max, DOUT mode)
  - ping            : Check if device is Online
  - info            : Get Device Info
  - pulse           : Set Inching (pulse) mode (0=off, 1=on) and width (in ms, 500ms step only)
  - signal          : Get WiFi Signal Strength
  - startup         : Set the Power On State (0=off, 1=on, 2=stay)
  - switch          : Set Relay (0=off, 1=on)
  - toggle          : Toggle Relay between ON and OFF
  - unlock          : Unlock OTA mode
  - wifi            : Set WiFi SSID and Password

* 'sonodiy' Actions Usage : 
  - help            : espbuddy sonodiy help   
  - scan            : espbuddy sonodiy scan   
  - test            : espbuddy sonodiy test   IP ID
  - flash           : espbuddy sonodiy flash  IP ID [URL] [SHA256SUM]
  - ping            : espbuddy sonodiy ping   IP [COUNT]
  - info            : espbuddy sonodiy info   IP ID
  - pulse           : espbuddy sonodiy pulse  IP ID [MODE] [WIDTH]
  - signal          : espbuddy sonodiy signal IP ID
  - startup         : espbuddy sonodiy startup IP ID [STATE]
  - switch          : espbuddy sonodiy switch IP ID [STATE]
  - toggle          : espbuddy sonodiy toggle IP ID
  - unlock          : espbuddy sonodiy unlock IP ID
  - wifi            : espbuddy sonodiy wifi   IP ID SSID [PASSWORD]

---------------------------------------------------------------------------------
Setup Instructions
---------------------------------------------------------------------------------
  1) Setup an access point in your network named "sonoffDiy" with password "20170618sn"
  2) Set the OTA/DIY jumper in your Sonoff Device, and power it On.
  3) Run 'espbuddy sonodiy scan'        to find your device IP & ID
  4) Run 'espbuddy sonodiy test  IP ID' to toggle the relay on the device (verification)
  5) Run 'espbuddy sonodiy flash IP ID' to upload another firmware (Tasmota by default)
  6) Enjoy!
```

----------



### `# espbuddy sonodiy scan`

Show the IP Adresses and IDs of connected devices.

```plaintext
--> Scanning network for Devices using command: --> Scanning network for Devices using command: dns-sd -B _ewelink._tcp
--> Get IP Address of the first device found (1000aba1ee) using command: dns-sd -q eWeLink_1000aba1ee.local

Devices Found:

==========================================================
| ID            | IP Address        | MAC Address        |
==========================================================
| 1000aba1ee    | 10.1.250.154      | d8:f1:5b:c7:e9:db  |
----------------------------------------------------------

You can now use: 10.1.250.154 1000aba1ee as arguments for sonodiy Actions!
Examples:
 espbuddy sonodiy test  10.1.250.154 1000aba1ee
 espbuddy sonodiy flash 10.1.250.154 1000aba1ee

```

----------



### `# espbuddy sonodiy test 10.1.250.154 1000aba1ee`

Test if we can successfully connect to the Sonoff Device.

```plaintext
--> Sending 5 pings to 10.1.250.154 : OK!
--> Toggling Relay: OK (did you heard it?)
--> API response	: 
Array
(
    [seq] => 182
    [error] => 0
    [data] => Array
        (
            [switch] => on
            [startup] => off
            [pulse] => off
            [pulseWidth] => 1000
            [ssid] => iot
            [otaUnlock] => 1
        )

)
```

----------



### `# espbuddy sonodiy info 10.1.250.154 1000aba1ee -v`

Show device information.

```plaintext

DONE !

--- Last Information Data Received: ---------
Array
(
    [switch] => on
    [startup] => off
    [pulse] => off
    [pulseWidth] => 1000
    [ssid] => iot
    [otaUnlock] => 1
)


```

----------



### `# espbuddy sonodiy info 10.1.250.154 1000aba1ee -j`

Show device information in JSON format.

```plaintext
{"data":{"switch":"on","startup":"off","pulse":"off","pulseWidth":1000,"ssid":"iot","otaUnlock":true}}
```



