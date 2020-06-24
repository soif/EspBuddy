<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy : Repository class for Espeasy
--------------------------------------------------------------------------------------------------------------------------------------
Copyright (C) 2018  by François Déchery - https://github.com/soif/

EspBuddy is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

EspBuddy is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------------------------------------------------------
*/
require_once(dirname(__FILE__).'/espb_repo.php');

class EspBuddy_Repo_Espeasy extends EspBuddy_Repo {

	// location relative to the base repository path
	protected $dir_build 		= ""; 									// (Trailing Slash) directory where the compiler must start
	protected $version_file 	= "src/ESPEasy_buildinfo.h";			// file to parse to get the version
	protected $version_regex 	= '|#define\s+BUILD\s+([^\s\n\r]+)|s'; 	// regex used to extract the version in the version_file
	protected $version_regnum	= 1; 									// captured parenthesis number where the version is extracted using the regex

	protected $firststep_firmware 	= 'firmwares/ESPEasyUploader.OTA.1m128.esp8266.bin';	// first (intermediate) firmware to upload
	protected $flash_sizes 	  = array(	//maximum flash sizes
		'1M'	=>	917504,	// 1M - 128k SPIFS 
		'2M'	=>	1048576,	// 2M - 1M   SPIFS
		'4M'	=>	3145728		// 4M - 1M   SPIFS
	);

	protected $url_gpio_on 		= '/control?cmd=gpio,{{gpio}},1';		// relative url to switch gpio ON : start with "/", use "{{gpio}}" as a placeholder for the GPIO pin number
	protected $url_gpio_off 	= '/control?cmd=gpio,{{gpio}},0';		// relative url to switch gpio ON : start with "/", use "{{gpio}}" as a placeholder for the GPIO pin number

	private $bin_crc2 	= 'crc2.py';


	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		parent::__construct($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		//$this->_PreAuthenticate($host_cfg);

		$url="http://{$host_arr['ip']}/json";
		$json=@file_get_contents($url);
		$out="";
		if($json and $arr=json_decode($json,true) and is_array($arr)){
			$out=trim($arr['System']['Build']);
		}
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		$this->_PreAuthenticate($host_arr);

		$files=array('config.dat','security.dat','notification.dat','rules1.txt','rules2.txt','rules3.txt','rules4.txt',);
		$url="http://{$host_arr['ip']}";				
		foreach($files as $i => $file){
			if(! $this->_DownloadFile("$url/$file",	$file,	$dest_path)){
				break;
			}
		} 

		if($i >= 3 ){
			return $i;
		}
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteReboot($host_arr){
		echo "Rebooting...";
		$this->_PreAuthenticate($host_arr);
		if($this->_TriggerUrl("http://{$host_arr['ip']}/?cmd=reboot")){
			echo " OK\n";
			return true;
		}
		echo " Failed\n";			
	}


	// ---------------------------------------------------------------------------------------
	public function GetPostBuildCommands($host_arr,$cfg){
		$bin_crc2	=$cfg['paths']['bin'].$this->bin_crc2;
		$commands[]	="python \"$bin_crc2\" \"{$host_arr['path_firmware']}\" ";					
		$commands[]	="rm -f \"{$host_arr['path_firmware']}1\" "; // remove bin1 not properly removed by the crc script					
		$commands[]	="rm -f \"{$host_arr['path_firmware']}2\" "; // remove bin2 not properly removed by the crc script					
		return $commands;		
	}


	// ####### Privates ##########################################################################

	// ---------------------------------------------------------------------------------------
	private function _PreAuthenticate($host_arr){
		// pre authenticate
		if($host_arr['pass']){
			file_get_contents("http://{$host_arr['ip']}/login?password={$host_arr['pass']}");
		}
	}
	
}
?>