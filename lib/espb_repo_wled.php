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

class EspBuddy_Repo_Wled extends EspBuddy_Repo {

	protected $name 			= "Wled"; 							// Firmware's Name

	// location relative to the base repository path
	protected $version_file 	= "package.json";		// file to parse to get the version
	protected $version_regex 	= '|"version"\s*:\s*"([^"]+)"|s'; 		// regex used to extract the version in the version_file
	protected $version_regnum = 1; 										// captured parenthesis number where the version is extracted using the regex

	protected $firststep_firmware 	= 'firmwares/espurna-1.12.3-espurna-core.bin';	// first (intermediate) firmware to upload
	protected $api_urls=array(
		'backup'	=>	'/cfg.json?download',			// relative url to the URl where we can parse the remote version
		'backup2'	=>	'/json?download',			// relative url to the URl where we can parse the remote version
		'version'	=>	'/json/info',			// relative url to the URl where we can parse the remote version
	);

	protected $default_login 		= 'admin';							// Login name to use when not set

	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		parent::__construct($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		if($json=$this->_RemoteGetVersionJson($host_arr)){
			return trim($json['ver']);
		}
	}
	// ---------------------------------------------------------------------------------------
	public function RemoteGetStatus($host_arr){
		return $this->_RemoteGetVersionJson($host_arr);
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		$r=0;
		$r+=$this->_RemoteBackupSettings($host_arr, $dest_path, 'config.json','backup');
		$r+=$this->_RemoteBackupSettings($host_arr, $dest_path, 'presets.json','backup2');
		if($r==2){
			return $r;
		}
	}


}
?>