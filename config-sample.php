<?php

// ################################################################################################################################
// Global Paths  ('paths') ########################################################################################################
// ################################################################################################################################
/*
	'dir_backup': The  directory where uploaded firmwares and settings are stored.
	(This will also be the default root directory of EspBuddy builtin web server)
 */
$cfg['paths']['dir_backup']			="/tmp/EspBuddy/";				// (with a trailing slash!)
//$cfg['paths']['dir_backup']		="/Users/soif/EspBuddy_data/";	// (with a trailing slash!)

// for Windows OS, the default tmp dir is at (edit and uncomment the following:)
//$cfg['paths']['dir_backup']		="/Users/<YOUR_USER_NAME>/AppData/Local/EspBuddy/"; // (with a trailing slash!)


// 'dir_python': Directory where is python (force Esptool to use the right python version, ie on OSX)
$cfg['paths']['dir_python']			="/Users/soif/.platformio/penv/bin/";	 // (with a trailing slash!)

// 'bin_pio':  Path to the platformio binary (Only needed if you want to compil some firmware)
$cfg['paths']['bin_pio']			="/Users/soif/.platformio/penv/bin/pio";


// ################################################################################################################################
// Global Preferences ('prefs') ###################################################################################################
// ################################################################################################################################
/* Most default settings should be fine, but feel free to change it if you wish */

//$cfg['prefs']['config']				='';				// Default config to use
//$cfg['prefs']['repo']					='tasmota';			// Default repo to use
//$cfg['prefs']['serial_port']			='';				// Default serial Port (empty = autoselect)
//$cfg['prefs']['serial_rate']			='boot';			// Default serial Rate (default to 'boot' speed: 74880)
//$cfg['prefs']['time_zone']			='Europe/Paris';	// Time zone for dates, see http://php.net/manual/en/timezones.php
//$cfg['prefs']['show_version']			='2';				// Show version in firmware name (0=no, 1=file version, 2=full git version)
//$cfg['prefs']['firm_name']			='Firmware';		// Firmware name prefix
//$cfg['prefs']['settings_name']		='Settings';		// Firmware settings name prefix
//$cfg['prefs']['name_sep']				='-';				// Field separator in firmware name
//$cfg['prefs']['keep_previous']		=3;					// Number of previous firmwares to keep
//$cfg['prefs']['server_port']			=81;				// Built-in Webserver port
//$cfg['prefs']['server_root']			='';				// Built-in Webserver Root directory (defaults to $cfg['paths']['dir_backup'])



// ################################################################################################################################
// Repositories Definitions ('repos') ###################################################################################################
// ################################################################################################################################
/*
	Defines the repositories (aka Firmwares types, among: espeasy, espura, tasmota, wled ) that you wish to use.

	SYNTAX: $cfg['repos']['NAME']['PARAM']		="VALUE";
		* NAME  : is one of the supported firmwares :  'espeasy' | 'espura' | 'tasmota' | 'wled'
		* PARAM : Each repo can use one or some of the following parameters:
			- 'path_repo'		: path to the main folder where the git repository is cloned (with a trailing slash!)
									- This is required only for 'repo_xxx' or 'build' (or with -b) commands
			- 'assets_groups'	: (optionnal) some preset lists of assets to grab with the 'factory download' command 
									- SYNTAX: $cfg['repos']['NAME']['assets_groups']['ID']=array('name1','name2','regex',....)
									- Each asset group is indexed by an ID (Your own list's name) to use as the [ASSET] argument
									- Each member of the array can be either the exact name of the asset, or a Regular Expression.
*/
// Examples --------------------------
$cfg['repos']['espurna']['path_repo']				="/Users/soif/mount/dev_apache/src/espurna/";	

$cfg['repos']['espeasy']['path_repo']				="/Users/soif/mount/dev_apache/src/ESPEasy/";

$cfg['repos']['tasmota']['path_repo']				="/Users/soif/mount/dev_apache/src/Tasmota/";
$cfg['repos']['tasmota']['assets_groups']['my']		=array(	// ie to use in command like : "espbuddy factory download tasmota latest my"
														'tasmota.bin',
														'tasmota.bin.gz',
														'tasmota-lite.bin',
														'tasmota-lite.bin.gz',
														'tasmota-minimal.bin',
														'tasmota-minimal.bin.gz',
														'tasmota-FR.bin',
														'tasmota-FR.bin.gz',
														'tasmota-sensors.bin',
														'tasmota-sensors.bin.gz',
														'tasmota32.bin',
														'tasmota32.factory.bin',
													);

$cfg['repos']['wled']['assets_groups']['esp8266']	=array('.*ESP0','.*ESP8266');	// grab only files matching this regex
$cfg['repos']['wled']['assets_groups']['esp32']		=array('.*ESP32');				// grab only files matching this regex


// ################################################################################################################################
// Configurations ('configs') #####################################################################################################
// ################################################################################################################################
/*	
	(REQUIRED)

	Define all configurations sets  (needed by your 'hosts')

	Configurations 'configs' are used by 'hosts' (see below) to basically describe, the firmware type used (espeasy,espura,tasmota,wled)
	and optionally the 'environment' used to build, some 'exports' to pass to the compiler, and/or some 'pass' or 'serial_port' to use.

	SYNTAX: $cfg['configs']['ID']['PARAM']		="VALUE";
	* ID	: Your own config's name. (avoid spaces or funky characters here)
	* PARAM : Each config can use one or some of the following parameters:
		- 'repo'		: (REQUIRED)  the repository to use from the list above

		- 'environment'	: (optionnal) the environment to pass to platformio (required when compiling)
		- '2steps'		: (optionnal) set this to true, to upload an intermediate OTA firmware (needed for 1M firmwares)
		- 'size'		: (optionnal) Flash Size: 512K|1M|2M|4M . Only needed when you want to check if the firmware fit in the flash memory
		- 'login'		: (optionnal) a global default login name to use for this config
		- 'pass'		: (optionnal) a global default password to use for this config
		- 'serial_port'	: (optionnal) the serial port (or its alias name) to use, when in Wire mode
		- 'serial_rate'	: (optionnal) the serial baud rate (or its alias name) to use, when in Wire mode
		- 'exports'		: (optionnal) various export to perform before compiling
						Exports can inlude special variables that get replaced by their values extracted from the host definition.
							- {{host_name}}	is replaced by the host (first) part of the FQDN
							- {{host_ip}}	is replaced by the host IP address
							- {{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}}	are the 4 parts of the host IP address
							- {{git_version}} is replaced by the full git version (branch,tag,commit)
*/

// Examples: Tasmota Configurations --------------------------------------------------------------
$cfg['configs']['tasmota']['repo']										="tasmota";
$cfg['configs']['tasmota']['environment']								="tasmota";

$cfg['configs']['tasmota32']['repo']									="tasmota";
$cfg['configs']['tasmota32']['environment']								="tasmota32";

$cfg['configs']['tasmota_sens']['repo']									="tasmota";
$cfg['configs']['tasmota_sens']['environment']							="tasmota-sensors";

$cfg['configs']['tasmota_lcd_sht']['repo']								="tasmota";
$cfg['configs']['tasmota_lcd_sht']['environment']						="tasmota";
$cfg['configs']['tasmota_lcd_sht']['exports']['PLATFORMIO_BUILD_FLAGS']	="-DUSE_DISPLAY -DUSE_DISPLAY_LCD -DUSE_SHT3X -DUSE_HTU  -DUSE_DS18x20";
$cfg['configs']['tasmota_lcd_sht']['2steps']							=true;


// Examples: Espurna Configurations --------------------------------------------------------------
$cfg['configs']['espurna_mh20']['repo']								="espurna";
$cfg['configs']['espurna_mh20']['environment']						="esp8266-1m-ota";
$cfg['configs']['espurna_mh20']['exports']['PLATFORMIO_BUILD_FLAGS']="-DUSE_CUSTOM_H";
$cfg['configs']['espurna_mh20']['exports']['ESPURNA_BOARD']			="MAGICHOME_LED_CONTROLLER_20";
//$cfg['configs']['espurna_mh20']['pass']							="MyEspurnaPassword";

$cfg['configs']['espurna_h801']['repo']								="espurna";
$cfg['configs']['espurna_h801']['environment']						="esp8266-1m-ota";
$cfg['configs']['espurna_h801']['exports']['PLATFORMIO_BUILD_FLAGS']="-DUSE_CUSTOM_H";
$cfg['configs']['espurna_h801']['exports']['ESPURNA_BOARD']			="HUACANXING_H801";
//$cfg['configs']['espurna_h801']['pass']							="MyEspurnaPassword";


// Examples: ESPEasy Configurations --------------------------------------------------------------
//date_default_timezone_set($cfg['prefs']['time_zone']);
//$my_build="{{git_version}}/".date("dM-H.i");
//$my_espeasy_flags ='-DUSE_CUSTOM_H -DBUILD_DEV=\"'.$my_build.'\" -DMY_IP=\"{{host_ip}}\" -DMY_AP_IP={{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}} -DMY_NAME=\"{{host_name}}\" -DMY_UNIT={{host_ip4}}';

$cfg['configs']['espeasy_1M']['repo']								="espeasy";
$cfg['configs']['espeasy_1M']['environment']						="normal_ESP8266_1024";
$cfg['configs']['espeasy_1M']['2steps']								=true;
//$cfg['configs']['espeasy_1M']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;

$cfg['configs']['espeasy_4M']['repo']								="espeasy";
$cfg['configs']['espeasy_4M']['environment']						="normal_ESP8266_4096";
//$cfg['configs']['espeasy_4M']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;
//$cfg['configs']['espeasy_4M']['pass']								="MyEspeasyPassword";
//$cfg['configs']['espeasy_4M']['serial_port']						="nodemcu";

$cfg['configs']['espeasy_4M_testing']								=$cfg['configs']['espeasy_4M'];
$cfg['configs']['espeasy_4M_testing']['environment']				="dev_ESP8266_4096";




// ################################################################################################################################
// Hosts ('hosts') ################################################################################################################
// ################################################################################################################################
/*
	(REQUIRED)
	You have to create some "Hosts": Each 'host' basically describes one of your devices by its 'hostname' (or 'ip' Address) 
	and points to one of the 'configs' (that you've defined above). You sould preferably use the (dns) hostname. 
	The IP ('ip)') should then be automatically resolved (depending on your own network configuration). 
	If not, you can also (or either) set the 'ip' address.

	SYNTAX: $cfg['hosts']['ID']['PARAM']		="VALUE";
	* ID  	: Your own device's name. (avoid spaces or funky characters here)
	* PARAM : Each host must at least be defined by two parameters :
		- 'hostname' or 'ip': (REQUIRED)  the real (FQDN) hostname of your device or its IP address
		- 'config'			: (REQUIRED)  the configuration name to use 

		- 'serial_port' 	: (optionnal) the serial port (or its alias names) to use, when in Wire mode
		- 'serial_rate' 	: (optionnal) the serial baud rate (or its alias name) to use, when in Wire mode
		- 'login' 			: (optionnal) the login name used to authenticate to the device's web server (for all remote actions)
		- 'pass' 			: (optionnal) the password used to authenticate to the device's web server (for all remote actions)
*/

// Examples: ---------------------------------------------------
$cfg['hosts']['led1']['hostname']			="led1.local";
$cfg['hosts']['led1']['config']				="espurna_mh20";

$cfg['hosts']['hall_sensor']['hostname']	="hall.local";
$cfg['hosts']['hall_sensor']['config']		="tasmota_sens";

$cfg['hosts']['garden_sensor']['ip']		="192.168.1.203";
$cfg['hosts']['garden_sensor']['config']	="espeasy_1M";
$cfg['hosts']['garden_sensor']['pass']		="MyEspeasyPassword";

$cfg['hosts']['nodemcu']['hostname']		="nodemcu";
$cfg['hosts']['nodemcu']['config']			="tasmota32";
$cfg['hosts']['nodemcu']['serial_port']		="wemos";



// ################################################################################################################################
// Commands Lists ('commands') #####################################################################################################
// ################################################################################################################################
/*
	(optionnal)
	Define some commands sets that will be used in the EspBuddy 'send' action. 

	SYNTAX: $cfg['commands']['ID']['PARAM']		="VALUE";
	* ID	: Your own commands set's name.
	* PARAM	: Each Commands Set must at least be defined by 1 or 2 parameter :
		- 'list'	: (REQUIRED)  a (newline separated) list of commands:
						- Separate command and value on each line, with space(s) or tab(s). 
						- Blank lines, extras spaces and Comments (starting with "#") are ignored
						- The list can inlude special variables that get replaced by their values extracted from the host's definition:
							- {{host_fqdn}}	is replaced by the fully Qualified Domain Name, aka 'hostname'
							- {{host_name}}	is replaced by the host's (first) part of the FQDN
							- {{host_id}}	is replaced by the host's ID
							- {{host_ip}}	is replaced by the host IP address.
							- {{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}}	are the 4 parts of the host IP address.
							- {{git_version}} is replaced by the full git version (branch,tag,commit)
		- 'repo'	: (optionnal) the repo to use (if not set as argument, as config parameter, or as a default prefs)

*/

// Example: Basic Tasmota settings ----------------------------------------------
$cfg['commands']['tasmo_main']['repo']	='tasmota';
$cfg['commands']['tasmo_main']['list']	="
	Topic 		{{host_name}}	# mqtt topic
	IPAddress1 	{{host_ip}}		# Net IP address		
	IPAddress2 	10.1.11.1		# Net Gateway
	IPAddress3 	255.255.0.0		# Net Mask
	IPAddress4 	10.1.10.1		# Net DNS

	NtpServer1 	time.lo.lo		# NTP Server

	LogHost 	log.lo.lo		# Log Server
	SysLog 		2				# Log Level

	MqttHost 	mqtt.lo.lo		# MQTT Host

	SetOption56 1				# Wi-Fi network scan to select strongest signal on restart
";


// Example: Settings for buttons ----------------------------------------------
$cfg['commands']['tasmo_button']['repo']	='tasmota';
$cfg['commands']['tasmo_button']['list']	="

	# includes the previously set 'tasmota_main' commands list ++++++
	{$cfg['commands']['tasmota_main']['list']}	

	# additional buttons settings +++++++++++++++++
	SetOption13 1		# Allow immediate action on single button press
	SetOption1	1		# restrict to single to penta press and hold actions
";


// Examples: Upgrade from our server ----------------------------------------------
$cfg['commands']['tasmo_upg']['repo']	='tasmota';
$cfg['commands']['tasmo_upg']['list']	="
OtaUrl	http://192.168.1.15:81/{{host_id}}/Firmware.bin	# Set OTA URL to Espbuddy builtin web server
Upgrade 1											# Upgrade and restart
";

// Examples: Get some GPIO status ----------------------------------------------
$cfg['commands']['espeasy_gpios']['repo']="espeasy";
$cfg['commands']['espeasy_gpios']['list']	="
Status,GPIO,0
Status,GPIO,1
Status,GPIO,2
Status,GPIO,3
Status,GPIO,4
Status,GPIO,5
Status,GPIO,9
Status,GPIO,10
Status,GPIO,12
Status,GPIO,13
Status,GPIO,14
Status,GPIO,15
";




// ###########################################################################################################################
// "sonodiy" command settings ################################################################################################
// ###########################################################################################################################
/*
	URL of the Firmware to upload when using then "sonodiy flash" command (for Sonoff "DIY" devices only ).
	Be sure to use a firmware < 508kB, but DON'T use the tasmota-minimal.bin (it wont allow to store settings)
*/

$cfg['sonodiy']['firmware_url']="https://ota.tasmota.com/tasmota/release/tasmota-lite.bin"; 

/*
	unfortunately (!!!!!) The sonoff API don't seem to work with an External URL, (at least not the tested one).
	You can try this URL , by adding the -P flag at the end of the 'flash' action.
	(This should proxy the external URL, to a local URL, and may be fool the sonoff API)
	
	If It does not work, please only use an URL to a LAN webserver (see Git issue #20)
	You might use the EspBuddy builtin web server:
*/
//$cfg['sonodiy']['firmware_url']="http://<ESPBUDDY_SERVER_IP_OR_HOSTNAME>:81/firmwares/tasmota-lite.bin"; 


?>
