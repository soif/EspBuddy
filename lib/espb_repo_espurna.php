<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy : Repository class for Espurna
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

class EspBuddy_Repo_Espurna extends EspBuddy_Repo {

	protected $name 			= "Espurna"; 							// Firmware's Name

	// location relative to the base repository path
	protected $dir_build 		= "code/"; 								// (Trailing Slash) directory where the compiler must start
	protected $dir_firmware 	= ".pio/build/"; 						// (Trailing Slash) directory where the firmware is built
	protected $version_file 	= "code/espurna/config/version.h";		// file to parse to get the version
	protected $version_regex 	= '|APP_VERSION\s+"([^"]+)"|s'; 		// regex used to extract the version in the version_file
	protected $version_regnum = 1; 										// captured parenthesis number where the version is extracted using the regex

	protected $firststep_firmware 	= 'firmwares/espurna-1.12.3-espurna-core.bin';	// first (intermediate) firmware to upload
	protected $api_urls=array(
		'command'	=>	'DUMMY',				// relative url to send a command : it must exist , so we set it to a dummy value (not used))
		'backup'	=>	'/config',				// relative url to the URl where we can perform the backup
		'version'	=>	'/config',				// relative url to the URl where we can parse the remote version
	);

	protected $default_login 		= 'admin';							// Login name to use when not set

	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		parent::__construct($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		if($json=$this->_RemoteGetVersionJson($host_arr)){
			return trim($json['version']);
		}
		return false;


		$html=$this->_RemoteGetVersionRaw($host_arr);
				
		$out="";
		if(preg_match('#app_version">([^<]+)#i',$html,$m)){			
			$out=$m[1];
		}		
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		return $this->_RemoteBackupSettings($host_arr, $dest_path, 'config.json');
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommand($host_arr, $txt_command){
		$raw=$this->_TelnetSendCommand($host_arr,$txt_command);
		return $this->_ExtractEspuraTelnetData($raw);
	}





	// ---------------------------------------------------------------------------------------
	protected function _SendGpioOn($host_arr,$pin){
		return $this->_SendGpio($host_arr, $pin, 1);
	}

	// ---------------------------------------------------------------------------------------
	protected function _SendGpioOff($host_arr,$pin){
		return $this->_SendGpio($host_arr, $pin, 0);
	}

	// ---------------------------------------------------------------------------------------
	protected function _SendGpio($host_arr, $pin, $value){
		$txt_command="gpio $pin $value";
		$raw=$this->_TelnetSendCommand($host_arr,$txt_command);
		return $this->_ExtractEspuraTelnetData($raw);
	}



	// ---------------------------------------------------------------------------------------
	private function _ExtractEspuraTelnetData($arr){
		if($arr){
			$started=0;
			$out=false;
			foreach($arr as $line){
				//echo $line."\n";
				if($started){
					//end of commandline results
					if( preg_match('#^\[\d+\]\s+\+OK#',$line) ){
						break;
					}
					// error
					if( preg_match('#ERROR\sunknown\scommand#i',$line) ){
						break;
					}
				}
				if($started){
					$tmp=$line;
					$tmp=preg_replace('#^\[\d+\]\s+#','',$tmp);
					$out[]=$tmp;
				}
				if(preg_match('#^\[\d+\]\s+\[TELNET\]#',$line)){
					$started=1;
				}
			}
			if(is_array($out) and count($out)==1){
				$out=reset($out);
			}
			return $out;
		}
	}



}
?>