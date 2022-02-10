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

class EspBuddy_Repo_Tasmota extends EspBuddy_Repo {

	// location relative to the base repository path
	protected $dir_build 		= ""; 									// (Trailing Slash) directory where the compiler must start
	protected $dir_firmware 	= ".pioenvs/"; 						// (Trailing Slash) directory where the firmware is built
	protected $version_file 	= "tasmota/tasmota_version.h";					// file to parse to get the version
	protected $version_regex 	= '|const\s+uint32_t\s+VERSION\s*=\s*([^\s;]+)|s'; 	// regex used to extract the version in the version_file
	protected $version_regnum	= 1; 									// captured parenthesis number where the version is extracted using the regex

	protected $firststep_firmware 	= 'firmwares/TasmotaUploader.OTA-0x20161209.bin';	// first (intermediate) firmware to upload
	
	private $default_login		='admin';

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
	public function RemoteGetVersion($host_arr){
		$host_arr['login'] or $host_arr['login']=$this->default_login;
		$url="http://{$host_arr['ip']}/in";
		$html=$this->_FetchPage($url, $host_arr['login'], $host_arr['pass']);
		$out="";
		if(preg_match('#Sonoff-Tasmota\s+([^\s]+)\s+by\s+Theo\s+Arends#i',$html,$m)){
			$out=$m[1];
		}		
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		$host_arr['login'] or $host_arr['login']=$this->default_login;
		return (int) $this->_DownloadFile("http://{$host_arr['ip']}/dl", 'config.dmp', $dest_path, $host_arr['login'], $host_arr['pass']);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteReboot($host_arr){
		$host_arr['login'] or $host_arr['login']=$this->default_login;
		$url="http://{$host_arr['ip']}/rb";
		echo "Rebooting...";
		if($this->_TriggerUrl($url, $host_arr['login'], $host_arr['pass'])){
			echo " OK\n";
			return true;
		}
		echo " Failed\n";			
	}



	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommands($host_arr, $commands_list){
		$commands	=$this->_CleanTxtListToArray($commands_list);

		// convert into backlog
		$max_backlog			=30;
		$delay_between_reboot	=3;

		if(is_array($commands)){
			$count=count($commands);
			if($count==1){
				$txt_command=key($commands)." ".reset($commands);				
				//echo "Sending: $txt_command\n";
				$result= $this->RemoteSendCommand($host_arr, $txt_command);
				return $result;
			}
			elseif($count){
				echo "Processing $count commands...\n";
				$start=0;
				$part=array_slice($commands,$start,$max_backlog);
				$step=1;
				while($part){
					if($start){
						echo "\n...Waiting reboot for $delay_between_reboot sec.";
						sleep($delay_between_reboot);
						echo "\n\n";
					}
					echo "# ";
					if($count > $max_backlog){
						echo "[$step] ";
					}
					$echo_start=$start+1;
					echo "Sending (max $max_backlog) commands from line $echo_start :\n\n";
					
					$backlog=$this->_CommandsToBacklog($part);
					echo "$backlog\n";
					
					$this->RemoteSendCommand($host_arr, $backlog);
					
					$start=$start + $max_backlog;
					$part=array_slice($commands,$start,$max_backlog);
					$step++;
				}
				return true;
			}	
		}

	}

	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommand($host_arr, $command){
			$url="http://{$host_arr['ip']}/cm?user={$host_arr['user']}&password={$host_arr['pass']}&cmnd=".rawurlencode($command);
			//echo "$url\n";		
			if ($json=$this->_FetchPage($url)){
				return json_decode($json,true);
			}
	}

	// ---------------------------------------------------------------------------------------
	protected function _CommandsToBacklog($commands){
		if(is_array($commands)){
			$str="backlog ";
			foreach($commands as $k => $v){
				$str.="$k $v;";
			}
			$str=substr($str, 0, -1); // remove last ';'
			return $str;
		}
	}

}
?>