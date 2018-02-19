<?php

// ################################################################################################################################
// #################################### GLOBALS ###################################################################################
// ################################################################################################################################
/* Most default setting should be fine, but feel free to change it if you wish */

// Paths ##########################################################################################################################
$tmp_current_dir=dirname(__FILE__);	//shortcut to the current dir (Not needed in the main application)

//path to the platformio binanry
$cfg['paths']['bin_pio']						="/usr/local/bin/pio";

//path to esp_ota.py (script provided with the esp8266 arduino v2.3 environment, in packages/esp8266/hardware/esp8266/2.3.0/tools/)
$cfg['paths']['bin_esp_ota']					=$tmp_current_dir.	"/bin/espota.py";

//path to esptools , ie /usr/local/bin/esptool.py 
$cfg['paths']['bin_esptool']					=$tmp_current_dir.	"/bin/esptool.py";

//intermediate firmware for ESPeasy OTA upload of boards wih 1M
$cfg['paths']['firmware_espeasy_1m_uploader']	=$tmp_current_dir.	"/firmwares/ESPEasyUploaderMega.OTA.1m128.bin";

// Backup Directory where uploaded firmwares are stored (WITH a trailing slash),
$cfg['paths']['dir_backup']						="/tmp/EspBuddy/";


// Serial ##########################################################################################################################

// Default Serial rate when not set on the command line (optionnal)
$cfg['serial_rates']['default']		=74880;	//74880, 115200

// Default Serial port & rate when not set on the command line (optionnal)
$cfg['serial_ports']['default']		="/dev/tty.wchusbserialfa140";

// other known ports (optionnal)
$cfg['serial_ports']['nodemcu']		="/dev/tty.SLAB_USBtoUART";
$cfg['serial_ports']['fdti1']		="/dev/tty.usbserial-A50285BI";



// ######################################################################################################################################
// #################################### USER SETTINGS ###################################################################################
// ######################################################################################################################################
/* Here is where you set your own settings */

// Repositories ###################################################################################################################
/*
Define each repositories you wish to use, where:
- 'path_repo' 	: the path to the repository
- 'path_code' 	: the path to the repository folder where the compiler should start
- 'path_version': repository file that contains the version number
- 'reg_version' : a regex to extract the version number , in the form : array( REGEX, CAPTURED_PARENTHESIS_NUMBER)
*/

$cfg['repos']['espurna']['path_repo']								="/Users/soif/dev/espurna/";
$cfg['repos']['espurna']['path_code']								=$cfg['repos']['espurna']['path_repo']."code/";
$cfg['repos']['espurna']['path_version']							=$cfg['repos']['espurna']['path_repo']."code/espurna/config/version.h";
$cfg['repos']['espurna']['reg_version']								=array('#APP_VERSION\s+"([^"]+)"#s',1);

$cfg['repos']['espeasy']['path_repo']								="/Users/soif/mount/dev_apache/src/ESPEasy/";
$cfg['repos']['espeasy']['path_code']								=$cfg['repos']['espeasy']['path_repo'];
$cfg['repos']['espeasy']['path_version']							=$cfg['repos']['espeasy']['path_repo']."src/ESPEasy.ino";
$cfg['repos']['espeasy']['reg_version']								=array('|#define\s+BUILD\s+([^\s\n\r]+)|s',1);



// Configurations ###################################################################################################################
/*
Define all configurations needed by your hosts, where:
- 'repo'		: the repository to use from the list above
- 'environment'	: the environment to pass to platformio when compiling
- 'exports'		: various export to perform before compiling
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

$cfg['configs']['espurna_h801']['repo']								="espurna";
$cfg['configs']['espurna_h801']['environment']						="esp8266-1m-ota";
$cfg['configs']['espurna_h801']['exports']['PLATFORMIO_BUILD_FLAGS']="-DUSE_CUSTOM_H";
$cfg['configs']['espurna_h801']['exports']['ESPURNA_BOARD']			="HUACANXING_H801";
$cfg['configs']['espurna_h801']['exports']['ESPURNA_AUTH']			="MyEspurnaPassword";


// espeasy Configurations ------------------------------
date_default_timezone_set('Europe/Paris');$my_build="Soif-".date("dM-H.i");
$my_espeasy_flags ='-DUSE_CUSTOM_H -DBUILD_DEV=\"'.$my_build.'\" -DMY_IP=\"{{host_ip}}\" -DMY_AP_IP={{host_ip1}},{{host_ip2}},{{host_ip3}},{{host_ip4}} -DMY_NAME=\"{{host_name}}\" -DMY_UNIT={{host_ip4}}';

$cfg['configs']['espeasy_1024']['repo']								="espeasy";
$cfg['configs']['espeasy_1024']['environment']						="normal_ESP8266_1024";
$cfg['configs']['espeasy_1024']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;

$cfg['configs']['espeasy_4096']['repo']								="espeasy";
$cfg['configs']['espeasy_4096']['environment']						="normal_ESP8266_4096";
$cfg['configs']['espeasy_4096']['exports']['PLATFORMIO_BUILD_FLAGS']=$my_espeasy_flags;
//$cfg['configs']['espeasy_4096']['serial_port']					="nodemcu";

$cfg['configs']['espeasy_4096_testing']								=$cfg['configs']['espeasy_4096'];
$cfg['configs']['espeasy_4096_testing']['environment']				="dev_ESP8266_4096";



// Environments ###################################################################################################################
/*
	additionnals parameters  per repo/environment, typically 2steps definitions...
	This should move elsewhere in future release
*/

$cfg['repos']['espeasy']['environments']['normal_ESP8266_1024']['2steps_delay']		=16;
$cfg['repos']['espeasy']['environments']['normal_ESP8266_1024']['2steps_firmware']	=$cfg['paths']['firmware_espeasy_1m_uploader'];



// Hosts ###################################################################################################################
/* each host must at least define :
- 'hostname' or 'ip' (non defined ip or hostname is automatically filled by a dns request
- 'config' : the configuration to load (from above 'configs')

Optionally you can add:
- 'serial_port' : the serial port (from the one defined above) to use when in wire mode
- 'serial_rate' : another serial baud rate to use when in wire mode
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

$cfg['hosts']['nodemcu']['ip']			="192.168.1.240";
$cfg['hosts']['nodemcu']['config']		="espeasy_4096_testing";
$cfg['hosts']['nodemcu']['serial_port']	="nodemcu";


?>