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

	// location relative to the base repository path
	protected $dir_build 		= "code/"; 								// (Trailing Slash) directory where the compiler must start
	protected $version_file 	= "code/espurna/config/version.h";		// file to parse to get the version
	protected $version_regex 	= '|APP_VERSION\s+"([^"]+)"|s'; 		// regex used to extract the version in the version_file
	protected $version_regnum = 1; 										// captured parenthesis number where the version is extracted using the regex

//	protected $firststep_firmware 	= 'firmwares/xxx.bin';	// first (intermediate) firmware to upload
//	protected $firststep_delay 		= 16;					// time two wait befaore launching the second step

	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		parent::__construct($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		$url="http://{$host_arr['ip']}/config";
		$json=$this->_FetchPage($url,'admin',$host_arr['pass']);

		$out="";
		if($json and $arr=json_decode($json,true) and is_array($arr)){
			$out=trim($arr['version']);
		}
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		return (int) $this->_DownloadFile("http://{$host_arr['ip']}/config", 'config.json', $dest_path, 'admin', $host_arr['pass']);
	}

}
?>