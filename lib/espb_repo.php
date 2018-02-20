<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy - Repo class
--------------------------------------------------------------------------------------------------------------------------------------
Copyright (C) 2018  by François Déchery - https://github.com/soif/

EspBuddy is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

EspBuddy is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------------------------------------------------------
*/
class EspBuddy_Repo {

	// Should be defined in ach Repo classes

	// location relative to the base repository path
	protected $dir_build 		= ""; // (Trailing Slash) directory where the compiler must start
	protected $version_file 	= ""; // file to parse to get the version
	protected $version_regex 	= ""; // regex used to extract the version in the version_file
	protected $version_regnum	= ""; // captured parenthesis number where the version is extracted using the regex

	protected $firststep_firmware 	= ''; // when uploading in 2steps mode, first upload this intermediate firmware
	protected $firststep_delay 		= 16; // when uploading in 2steps mode, wait this time (sec) to let the esp reboot before launching the second step

	// internal properties
	private $path_base		= "";	// path to the repository directory
	private $path_build		= "";	// path to the directory where the compiler must start 
	private $version		= "";	// extracted version
	private $git_version	= "";	// latest commit
	private $git_date		= "";	// latest commit date


	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		$this->_Init($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	private function _Init($path_to_repo){
		$this->path_base	= $path_to_repo;
		$this->path_build	= $this->path_base . $this->dir_build;
		$this->path_version	= $this->path_base . $this->version_file;
		$this->_ParseVersion();
	}

	// ---------------------------------------------------------------------------------------
	private function _ParseVersion(){		
		if( $file=$this->path_version and $reg=$this->version_regex and $reg_n=$this->version_regnum ){
			preg_match($reg, file_get_contents($file),$matches);
			$this->version=trim($matches[$reg_n]);
		}
	}

	// ---------------------------------------------------------------------------------------
	protected function _DownloadFile($url, $file_name, $dest_path, $auth_login='', $auth_pass=''){
		$tmp_file	= $dest_path.'temp_file';
		$dest_file	= $dest_path. $file_name;

		$fp = fopen($tmp_file, 'w+');
		if($fp === false){
			$error=true;
			@fclose($fp);
			@unlink($tmp_file);
			return false;
		}

		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($auth_login and $auth_pass){
			curl_setopt($ch, CURLOPT_USERPWD, "$auth_login:$auth_pass");
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dont verify SSL
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(curl_errno($ch) or $status !=200 ){
			$error=true;
		}
		curl_close($ch);
		@fclose($fp);

		if(! $error){
			@unlink($dest_file);	//just incase a previous file remains
			if(@rename($tmp_file, $dest_file)){
				return true;
			}
		}
		@unlink($tmp_file);
		return false;
	}

	// ---------------------------------------------------------------------------------------
	protected function _FetchPage($url, $auth_login='', $auth_pass=''){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($auth_login and $auth_pass){
			curl_setopt($ch, CURLOPT_USERPWD, "$auth_login:$auth_pass");
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dont verify SSL
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$result	= curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);		
		if(curl_errno($ch) or $status !=200 ){
			$result='';
		}
		curl_close($ch);
		return $result;
	}

	// ---------------------------------------------------------------------------------------
	public function GetVersion(){
		if(! $this->version){
			$this->_ParseVersion();
		}
		return $this->version;
	}

	// ---------------------------------------------------------------------------------------
	public function GetPathBuild(){
		return $this->path_build;
	}

	// ---------------------------------------------------------------------------------------
	public function GetFirstStepFirmware(){
		return $this->firststep_firmware;
	}
	// ---------------------------------------------------------------------------------------
	public function GetFirstStepDelay(){
		return $this->firststep_delay;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		return "Not Implemented";
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		echo "Not Implemented\n";
		return false;
	}

}
?>