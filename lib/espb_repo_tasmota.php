<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy : Repository class for Tasmota
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

class EspBuddy_Repo_Tasmota extends EspBuddy_Repo {

	protected $name 			= "Tasmota"; 							// Firmware's Name

	// location relative to the base repository path
	protected $dir_build 		= ""; 									// (Trailing Slash) directory where the compiler must start
	protected $dir_firmware 	= ".pio/build/"; 						// (Trailing Slash) directory where the firmware is built
	protected $version_file 	= "tasmota/include/tasmota_version.h";	// file to parse to get the version
	protected $version_regex 	= '|const\s+uint32_t\s+TASMOTA_VERSION\s*=\s*([^\s;]+)|s'; 	// regex used to extract the version in the version_file
	protected $version_regnum	= 1; 									// captured parenthesis number where the version is extracted using the regex

	protected $gh_owner			= 'arendst'; 							// Github OWNER name
	protected $gh_repo			= 'Tasmota'; 							// Github REPO name
	protected $gh_asset_name_len=32;								 	// max lenght ofan asset name (used to make the column width in the RepoListAssets method)

	protected $firststep_firmware 	= 'firmwares/TasmotaUploader.OTA-0x20161209.bin';	// first (intermediate) firmware to upload

	protected $upgrade_conf	=array(
		'method'			=> 'server_mini',				// method used to upgrade
		'firmware'			=> 'tasmota-minimal.bin',		// The intermediate firmware (from factory) needed to make a two step upgrade
		'set_command'		=> 'OtaUrl {{server_url}}',		// Command to set the upgrade URL. {{server_url}} will be replaced by our builtin webserver
		'get_command'		=> 'OtaUrl',					// Command to get the current upgrade URL
		'get_field'			=> 'OtaUrl',					// The JSON field holding the current upgrade URL
		'upgrade_command'	=> 'Upgrade 1',					// Command to launch the upgrade.
	);

	protected $api_urls=array(
		'backup'	=>	'/dl',			// relative url to the URl where we can perform the backup
		'command'	=>	'/cm?user={{login}}&password={{pass}}&cmnd=',						// relative url to send a command
		'reboot'	=>	'/?rst=',			// relative url to the Reboot Command
		'version'	=>	'/in',			// relative url to the URl where we can parse the remote version
	);

	protected $default_login 	= 'admin';					// Login name to use when not set

	// ---------------------------------------------------------------------------------------
	private $_rebooting_commands=array(				//command that trigger a restart
		'restart',
		'DevGroupName\d+',
		'Reset',
		'Upgrade',
		'upload',
		'Hostname',
		'Password\d',
		'SSId\d',
		'FullTopic',
		'GroupTopic\d+',
		'MqttClient',
		'MqttHost',
		'MqttPassword',
		'MqttPort',
		'MqttUser',
		'Prefix\d',
		'Topic',
		'Rule\d+\s+(8|9|10)',
		'Sensor54\s+2',
		'Sensor54\s+(0|1)',
		'DevGroupName\d+',
		'ZbConfig',
	);



	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		parent::__construct($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function GetVersion(){
		$v=parent::GetVersion();
		
		if(preg_match('|0x(.{2})(.{2})(.{2})(.{2})|',$v,$arr)){
			$l=intval($arr[3]) and $letter=chr(96+$l);
			$this->version= hexdec($arr[1]).'.'.hexdec($arr[2]).'.'.hexdec($arr[3]).$letter;
		}
		return $this->version;
	}

	// ---------------------------------------------------------------------------------------
	protected function MakeMiniFirmwareName($name){
		return preg_replace("#(.*?)((\.bin)(\.gz)?)#",'$1-minimal$2',$name);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		$html=$this->_RemoteGetVersionRaw($host_arr);
		$out="";
		if(preg_match('#Tasmota\s+([^\s]+)\s+by\s+Theo\s+Arends#i',$html,$m)){
			$out=$m[1];
		}		
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetStatus($host_arr){
		return $this->RemoteSendCommands($host_arr,'Status 0');
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		return $this->_RemoteBackupSettings($host_arr, $dest_path, 'config.dmp');
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommands($host_arr, $commands_list){
		$commands	=$this->_CleanTxtListToArray($commands_list);

		// convert into backlog
		$max_backlog			=3;
		$delay_between_reboot	=3;

		if(is_array($commands)){
			$count=count($commands);
			if($count==1){
				$txt_command=reset($commands);
	
				//echo "Sending: $txt_command\n\n";
				$result= $this->RemoteSendCommand($host_arr, $txt_command);
				return $result;
			}
			elseif($count){
				echo "* Processing $count commands...\n";
				$start=0;
				$part=array_slice($commands,$start,$max_backlog);
				$step=1;
				while($part){
					$echo_step='';
					if($count > $max_backlog){
						$echo_step="[$step] ";
					}
					$echo_start=$start+1;
					$echo_count=$max_backlog;
					// do not backlog lines with backlogs---
					$exceptions=array();
					$count_exceptions=0;
					
					foreach($part as $k => $this_com){
						if(preg_match('#backlog\s#i',$this_com)){
							$exceptions[]=$this_com;
							unset($part[$k]);
							$count_exceptions++;
						}
					}

					//sending  commands as backlog ------------
					echo "* {$echo_step}Sending (max $echo_count) commands from line $echo_start :\n";
					$backlog=$this->_CommandsToBacklog($part);
					$this->sh->PrintCommand($backlog);
					$this->RemoteSendCommand($host_arr, $backlog);
					$this->_WaitForReboot($delay_between_reboot,$backlog);
					
					//sending  backlog commands separately------------
					if($count_exceptions){
						$echo_step and $echo_step="[{$step}-solo] ";
						echo "* {$echo_step}Sending $count_exceptions commands containing backlog as single line(s) :\n";
						foreach($exceptions as $com_ex){
							$this->sh->PrintCommand($com_ex);
							$this->RemoteSendCommand($host_arr, $com_ex);
							$this->_WaitForReboot($delay_between_reboot,$com_ex);
						}
					}
					
					$start=$start + $max_backlog;
					$part=array_slice($commands,$start,$max_backlog);
					$step++;

				}
				return true;
			}	
		}

	}

	// ---------------------------------------------------------------------------------------
	private function _WaitForReboot($delay_between_reboot=3,$command=''){
		if(!$this->_CommandNeedsReboot($command)){
			return false;
		}	
		echo "* Waiting reboot for $delay_between_reboot sec.";
		sleep($delay_between_reboot);
		echo "\n";
	}
	
	// ---------------------------------------------------------------------------------------
	private function _CommandNeedsReboot($txt){
		foreach($this->_rebooting_commands as $com){
			if(preg_match("#$com#i",$txt)){
				return true;
			}
		}
	}






	// ---------------------------------------------------------------------------------------
	protected function _CleanCustom($str){
		return $this->_CleanRulesToOneline($str);
	}

	// ---------------------------------------------------------------------------------------
	private function _CleanRulesToOneline($str){
		$str=preg_replace('#endon[\s\r\n]on+#i','ENDON ON',$str);
		return $str;
	}

	// ---------------------------------------------------------------------------------------
	protected function _CommandsToBacklog($commands){
		if(is_array($commands)){
			$str="BACKLOG ".implode(';',$commands);
			return $str;
		}
	}

}
?>