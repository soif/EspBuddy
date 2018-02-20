<?php

// ################################################################################################################################
// #################################### GLOBALS ###################################################################################
// ################################################################################################################################
/* Most default settings should be fine, but feel free to change it if you wish */

// Paths ##########################################################################################################################
$tmp_current_dir=dirname(__FILE__);	//shortcut to the current dir (only used as a shorcut in this file)

//path to the platformio binary
$cfg['paths']['bin_pio']						="/usr/local/bin/pio";

//path to esp_ota.py (script provided with the esp8266 arduino v2.3 environment, in packages/esp8266/hardware/esp8266/2.3.0/tools/)
$cfg['paths']['bin_esp_ota']					=$tmp_current_dir.	"/bin/espota.py";

//path to esptools , ie /usr/local/bin/esptool.py 
$cfg['paths']['bin_esptool']					=$tmp_current_dir.	"/bin/esptool.py";



// Serial #########################################################################################################################

// Default Serial rate when not set on the command line (optionnal)
$cfg['serial_rates']['default']		=74880;	//74880, 115200

// Default Serial port & rate when not set on the command line (optionnal)
$cfg['serial_ports']['default']		="/dev/tty.wchusbserialfa140";

// other known ports (optionnal)
$cfg['serial_ports']['nodemcu']		="/dev/tty.SLAB_USBtoUART";
$cfg['serial_ports']['fdti1']		="/dev/tty.usbserial-A50285BI";



// Misc ##########################################################################################################################

$cfg['misc']['time_zone']			='Europe/Paris';	//Time zone for dates, see http://php.net/manual/en/timezones.php






// ################################################################################################################################
// #################################### USER SETTINGS #############################################################################
// ################################################################################################################################

/* Here is where you set your own settings */


// Backup Directory where uploaded firmwares and download settings are stored 
$cfg['paths']['dir_backup']						="/tmp/EspBuddy/"; //(WITH a trailing slash)



// Repositories ###################################################################################################################
// Define the paths to your local copy (as 'path_repo') of each repositories you wish to use :
$cfg['repos']['espurna']['path_repo']				="/Users/soif/dev/espurna/";
$cfg['repos']['espeasy']['path_repo']				="/Users/soif/mount/dev_apache/src/ESPEasy/";



// Configurations #################################################################################################################
/*
Define all configurations needed by your hosts, where:
- 'repo'		: the repository to use from the list above
- 'environment'	: the environment to pass to platformio when compiling
- '2steps'		: for (EspEasy) 1M firmwares, set this to true, to upload an intermediate OTA firmware
- 'login'		: (optionnal) a global default login name to use for this config
- 'pass'		: (optionnal) a global default password to use for this config
- 'exports'		: (optionnal) various export to perform before compiling
	BTW exports can inlude special variables that get replaced by their values extracted from the host definition.
		- {{host_name}}	is replaced by the host (first) part of the FQDN
		- {{host_ip}}	is replaced by the host IP address
		- {{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}}	are the 4 parts of the host IP address
*/


// espurna Configurations ------------------------------
$cfg['configs']['espurna_mh20']['repo']								="espurna";
$cfg['configs']['espurna_mh20']['environment']						="esp8266-1m-ota";
$cfg['configs']['espurna_mh20']['exports']['PLATFORMIO_BUILD_FLAGS']="-DUSE_CUSTOM_H";
$cfg['configs']['espurna_mh20']['exports']['ESPURNA_BOARD']			="MAGICHOME_LED_CONTROLLER_20";
$cfg['configs']['espurna_mh20']['exports']['ESPURNA_AUTH']			="MyEspurnaPassword";
$cfg['configs']['espurna_mh20']['pass']								="MyEspurnaPassword";

$cfg['configs']['espurna_h801']['repo']								="espurna";
$cfg['configs']['espurna_h801']['environment']						="esp8266-1m-ota";
$cfg['configs']['espurna_h801']['exports']['PLATFORMIO_BUILD_FLAGS']="-DUSE_CUSTOM_H";
$cfg['configs']['espurna_h801']['exports']['ESPURNA_BOARD']			="HUACANXING_H801";
$cfg['configs']['espurna_h801']['exports']['ESPURNA_AUTH']			="MyEspurnaPassword";
$cfg['configs']['espurna_h801']['pass']								="MyEspurnaPassword";


// espeasy Configurations ------------------------------
date_default_timezone_set($cfg['misc']['time_zone']);$my_build="Soif-".date("dM-H.i");
$my_espeasy_flags ='-DUSE_CUSTOM_H -DBUILD_DEV=\"'.$my_build.'\" -DMY_IP=\"{{host_ip}}\" -DMY_AP_IP={{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}} -DMY_NAME=\"{{host_name}}\" -DMY_UNIT={{host_ip4}}';

$cfg['configs']['espeasy_1024']['repo']								="espeasy";
$cfg['configs']['espeasy_1024']['environment']						="normal_ESP8266_1024";
$cfg['configs']['espeasy_1024']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;
$cfg['configs']['espeasy_1024']['2steps']							=true;

$cfg['configs']['espeasy_4096']['repo']								="espeasy";
$cfg['configs']['espeasy_4096']['environment']						="normal_ESP8266_4096";
$cfg['configs']['espeasy_4096']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;
//$cfg['configs']['espurna_h801']['pass']							="MyEspeasyPassword";
//$cfg['configs']['espeasy_4096']['serial_port']					="nodemcu";

$cfg['configs']['espeasy_4096_testing']								=$cfg['configs']['espeasy_4096'];
$cfg['configs']['espeasy_4096_testing']['environment']				="dev_ESP8266_4096";



// Hosts ###################################################################################################################################
/* each host must at least be defined by :
- 'hostname' or 'ip' (non defined ip or hostname are automatically filled by a dns request)
- 'config' : the configuration to load (from above 'configs')

Optionally you can add:
- 'serial_port' : the serial port (from the one defined above) to use when in wire mode
- 'serial_rate' : another serial baud rate to use when in wire mode
- 'login' 		: the login name used to authenticate to the web (for 'backup' and 'version' actions)
- 'pass' 		: the password used to authenticate to the web (for 'backup' and 'version' actions)
*/

// ---------------------------------------------------
$cfg['hosts']['led1']['hostname']		="led1.local";
$cfg['hosts']['led1']['config']			="espurna_mh20";

$cfg['hosts']['led2']['hostname']		="led2.local";
$cfg['hosts']['led2']['config']			="espurna_h801";

$cfg['hosts']['led3']['ip']				="192.168.1.203";
$cfg['hosts']['led3']['config']			="espeasy_1024";
$cfg['hosts']['led3']['serial_port']	="fdti1";

$cfg['hosts']['relay1']['hostname']		="relay1.local";
$cfg['hosts']['relay1']['config']		="espeasy_4096";
$cfg['hosts']['relay1']['pass']			="MyEspeasyPassword";

$cfg['hosts']['nodemcu']['ip']			="192.168.1.240";
$cfg['hosts']['nodemcu']['config']		="espeasy_4096_testing";
$cfg['hosts']['nodemcu']['serial_port']	="nodemcu";

?>