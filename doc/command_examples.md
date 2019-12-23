# EspBuddy Command Examples

This document shows the terminal output from various EspBuddy commands.


## Main EspBuddy commands


### `# espbuddy.php help`

List of all EspBuddy commands.

```plaintext
EspBuddby v2.02b5 ( EspTool v2.6 )

* Usage             : espbuddy.php COMMAND [TARGET] [options]

* Valid COMMANDS : 
  - upload          : Build and/or Upload current repo version to Device(s)
  - build           : Build firmware for the selected device
  - backup          : Download and archive settings from the remote device
  - monitor         : Monitor device connected to the serial port
  - version         : Show remote device version
  - reboot          : Reboot Device(s)
  - gpios           : Test all Device's GPIOs
  - ping            : Ping Device(s)
  - sonodiy         : Discover, Control or Flash Sonoff devices in DIY mode
  - repo_version    : Parse the current repository (REPO) version. REPO is a supported repository (espurna, espeasy or tasmota)
  - repo_pull       : Git Pull the local repository (REPO). REPO is a supported repository (espurna, espeasy or tasmota)
  - list_hosts      : List all hosts defined in config.php
  - list_configs    : List all available configurations, defined in config.php
  - list_repos      : List all available repositories, defined in config.php
  - self            : Get current, latest or update EspBuddy version
  - help            : Show full help

* Commands Usage : 
  - upload          : espbuddy.php upload       [TARGET] [options, auth_options, upload_options]
  - build           : espbuddy.php build        [TARGET] [options]
  - backup          : espbuddy.php backup       [TARGET] [options, auth_options]
  - monitor         : espbuddy.php monitor      [TARGET] [options]
  - version         : espbuddy.php version      [TARGET] [options]
  - reboot          : espbuddy.php reboot       [TARGET] [options]
  - gpios           : espbuddy.php gpios        [TARGET] [options]
  - ping            : espbuddy.php ping         [TARGET] [options]
  - sonodiy         : espbuddy.php sonodiy      ACTION [options]
  - repo_version    : espbuddy.php repo_version REPO
  - repo_pull       : espbuddy.php repo_pull    REPO
  - list_hosts      : espbuddy.php list_hosts   
  - list_configs    : espbuddy.php list_configs 
  - list_repos      : espbuddy.php list_repos   
  - self            : espbuddy.php self         ACTION [options]
  - help            : espbuddy.php help         

---------------------------------------------------------------------------------
* OPTIONS :
	-y  : Automatically confirm Yes/No
	-d  : Dry Run. Show commands but don't apply them
	-v  : Verbose mode
	-D  : Debug mode (shows PHP errors)

* UPLOAD_OPTIONS :
	-b           : Build before Uploading
	-w           : Wire Mode : Upload using the Serial port instead of the default OTA
	-e           : In Wire Mode, erase flash first, then upload
	-p           : Upload previous firmware backuped, instead of the latest built
	-s           : Skip Intermediate Upload (if set)

	--port=xxx   : serial port to use (override main or per host serial port)
	--rate=xxx   : serial port speed to use (override main or per host serial port)
	--conf=xxx   : config to use (override per host config)
	--firm=xxx   : full path to the firmware file to upload (override latest build one)
	--from=REPO  : migrate from REPO to the selected config

* AUTH_OPTIONS :
	--login=xxx  : login name (override host or per config login)
	--pass=xxx   : password (override host or per config password)

```

----------



### `# espbuddy.php version led2`

Grab the remote version of the 'led2' host. *'led2' is an host defined from the config.php file.*

```plaintext
Selected Host      : led2
       + Host Name : led2.lo.lo
       + Host IP   : 10.1.209.2

Selected Config    : espurna_mh20

Processing 1 host(s) : 

##### led2.lo.lo                    (10.1.209.2    ) ##### : espurna	1.13.4-dev


```

----------



### `# espbuddy.php upload led2`

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



### `# espbuddy.php self help`

EspBuddy self maintenance tools.

```plaintext
* Usage             : espbuddy.php self ACTION [options]

* Valid 'self' Actions : 
  - version         : Show EspBuddy version
  - latest          : Show the lastest version available on the 'master' branch
  - avail           : Show all versions available
  - log             : Show EspBuddy history between current version and VERSION (latest on master branch, if not set)
  - update          : Update EspBuddy to the latest version

* 'self' Actions Usage : 
  - version         : espbuddy.php self version 
  - latest          : espbuddy.php self latest 
  - avail           : espbuddy.php self avail  
  - log             : espbuddy.php self log    [VERSION]
  - update          : espbuddy.php self update [TAG|VERSION|BRANCH]


```





##  Sonoff DIY (sonodiy) specific commands


### `# espbuddy.php sonodiy help`

Tasks for the 'sonodiy' command.

```plaintext
* Usage             : espbuddy.php sonodiy ACTION [options]

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
  - help            : espbuddy.php sonodiy help   
  - scan            : espbuddy.php sonodiy scan   
  - test            : espbuddy.php sonodiy test   IP ID
  - flash           : espbuddy.php sonodiy flash  IP ID [URL] [SHA256SUM]
  - ping            : espbuddy.php sonodiy ping   IP [COUNT]
  - info            : espbuddy.php sonodiy info   IP ID
  - pulse           : espbuddy.php sonodiy pulse  IP ID [MODE] [WIDTH]
  - signal          : espbuddy.php sonodiy signal IP ID
  - startup         : espbuddy.php sonodiy startup IP ID [STATE]
  - switch          : espbuddy.php sonodiy switch IP ID [STATE]
  - toggle          : espbuddy.php sonodiy toggle IP ID
  - unlock          : espbuddy.php sonodiy unlock IP ID
  - wifi            : espbuddy.php sonodiy wifi   IP ID SSID [PASSWORD]

---------------------------------------------------------------------------------
Setup Instructions
---------------------------------------------------------------------------------
  1) Setup an access point in your network named "sonoffDiy" with password "20170618sn"
  2) Set the OTA/DIY jumper in your Sonoff Device, and power it On.
  3) Run 'espbuddy.php sonodiy scan'        to find your device IP & ID
  4) Run 'espbuddy.php sonodiy test  IP ID' to toggle the relay on the device (verification)
  5) Run 'espbuddy.php sonodiy flash IP ID' to upload another firmware (Tasmota by default)
  6) Enjoy!
```

----------



### `# espbuddy.php sonodiy scan`

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
 espbuddy.php sonodiy test  10.1.250.154 1000aba1ee
 espbuddy.php sonodiy flash 10.1.250.154 1000aba1ee

```

----------



### `# espbuddy.php sonodiy test 10.1.250.154 1000aba1ee`

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



### `# espbuddy.php sonodiy info 10.1.250.154 1000aba1ee -v`

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



### `# espbuddy.php sonodiy info 10.1.250.154 1000aba1ee -j`

Show device information in JSON format.

```plaintext
{"data":{"switch":"on","startup":"off","pulse":"off","pulseWidth":1000,"ssid":"iot","otaUnlock":true}}
```



