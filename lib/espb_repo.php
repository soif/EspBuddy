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

	protected $name 			= ""; 							// Firmware's Name

	// location relative to the base repository path
	protected $dir_build 		= ""; // (Trailing Slash) directory where the compiler must start
	protected $dir_firmware 	= ""; // (Trailing Slash) directory where the firmware is built
	protected $version_file 	= ""; // file to parse to get the version
	protected $version_regex 	= ""; // regex used to extract the version in the version_file
	protected $version_regnum	= ""; // captured parenthesis number where the version is extracted using the regex
	protected $firststep_firmware = ''; // when uploading in 2steps mode, first upload this intermediate firmware
	protected $flash_sizes 	  = array(	//maximum flash sizes
		'512K'	=>	524288,		// 512 * 1024
		'1M'	=>	1048576,	// 1024 * 1024
		'2M'	=>	2097152,	// 2048 * 1024
		'4M'	=>	4194304		// 4096 * 1024
	);
	protected $api_urls=array(
		'version'	=>	'',			// relative url to the URl where we can parse the remote version
		'reboot'	=>	'',			// relative url to the Reboot Command
		'gpio_on'	=>	'',			// relative url to switch gpio ON : start with "/", use "{{gpio}}" as a placeholder for the GPIO pin number
		'gpio_off'	=>	'',			// relative url to switch gpio OFF : start with "/", use "{{gpio}}" as a placeholder for the GPIO pin number
		'command'	=>	'',			// relative url to send a command
	);
	protected $api_prefix 		= 'http://';	// scheme to use : http:// , https://
	protected $default_login 	= '';			// Login name to use when not set
	
	
	// internal properties -----------------------------------
	protected $last_http_code 	= 200; 	// last HTTP status code returned by curl
	protected $last_http_status = '';	// last HTTP status
	protected $version			= "";	// extracted version
	private $path_base			= "";	// path to the repository directory
	private $path_build			= "";	// path to the directory where the compiler must start 
	private $path_firmware		= "";	// path to the directory where the firmware is built


//	private $git_version		= "";	// latest commit
//	private $git_date			= "";	// latest commit date


	private $http_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Moved Temporarily',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
		901 => "Can't create file for downloading (Permission issue?)",
	);



	// ---------------------------------------------------------------------------------------
	function __construct($path_to_repo=''){
		$this->_Init($path_to_repo);
	}

	// ---------------------------------------------------------------------------------------
	public function GetVersion(){
		if(! $this->version){
			$this->_ParseVersion();
		}
		return $this->version;
	}

	// ---------------------------------------------------------------------------------------
	private function _gitCommand($git_command){
		$command="cd \"{$this->path_base}\" ; $git_command ";
		$r=shell_exec($command);
		return trim($r);
	}

	// ---------------------------------------------------------------------------------------
	public function GetBranch(){
		return $this->_gitCommand("git rev-parse --abbrev-ref HEAD");
	}

	// ---------------------------------------------------------------------------------------
	public function GetTag(){
		return $this->_gitCommand("git describe --abbrev=0 --tags");
	}

	// ---------------------------------------------------------------------------------------
	public function GetTagCommit(){
		$tag= $this->GetTag();
		return $this->_gitCommand("git rev-list -n 1 --abbrev-commit $tag");
	}

	// ---------------------------------------------------------------------------------------
	public function GetCommit(){
		return $this->_gitCommand("git rev-parse --short HEAD");
	}

	// ---------------------------------------------------------------------------------------
	public function GetLastError(){
		if($this->last_http_code >=400 ){
			//echo "\033[31m HTTP Error {$this->last_http_code} => {$this->last_http_status} \033[0m";
			//return true;
			return "{$this->last_http_code} => {$this->last_http_status}";
		}
	}

	// ---------------------------------------------------------------------------------------
	public function GetPostBuildCommands($host_arr,$cfg){
		return false;
	}

	// ---------------------------------------------------------------------------------------
	public function SettLastStatus($code){
		$this->last_http_code 	=$code;
		$this->last_http_status =$this->http_codes[$code];
	}

	// ---------------------------------------------------------------------------------------
	public function GetPathBuild(){
		return $this->path_build;
	}

	// ---------------------------------------------------------------------------------------
	public function GetPathFirmware(){
		return $this->path_firmware;
	}

	// ---------------------------------------------------------------------------------------
	public function GetPathBase(){
		return $this->path_base;
	}

	// ---------------------------------------------------------------------------------------
	public function GetFirstStepFirmware(){
		return $this->firststep_firmware;
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteGetVersion($host_arr){
		if($this->api_urls['version']){
			if($arr=$this->_RemoteGetVersionJson($host_arr)){
				return $arr;
			}
		}
		$this->_EchoNotImplemented();
	}


	// ---------------------------------------------------------------------------------------
	public function RemoteBackupSettings($host_arr, $dest_path){
		if($this->api_urls['backup']){
			return $this->_RemoteBackupSettings($host_arr, $dest_path,'backup');			
		}
		else{
			$this->_EchoNotImplemented();
		}
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteReboot($host_arr){
		if($this->api_urls['reboot']){
			echo "Rebooting...";
			$url=$this->_MakeApiUrl($host_arr,$this->api_urls['reboot']);
			if($this->_FetchPage($url, $host_arr['login'], $host_arr['pass'])){
				echo " OK\n";
				return true;
			}
			echo " Failed\n";
		}
		else{
			$this->_EchoNotImplemented();
		}
	}




	// ---------------------------------------------------------------------------------------
	public function RemoteTestAllGpios($host_arr){
		$first_gpio 	= 0;
		$last_gpio		= 16;
		$delay_state 	= 100;	// ms
		$delay_gpio 	= 800;	// ms

		if($this->api_urls['gpio_on'] or $this->api_urls['gpio_off']){
			for($i=$first_gpio; $i <= $last_gpio ; $i++){
				echo "$i";
				if($this->api_urls['gpio_on']){
					$this->_SendGpioOn($host_arr,$i);
					usleep($delay_state * 1000);
				}
				if($this->api_urls['gpio_off']){
					$this->_SendGpioOff($host_arr,$i);
					usleep($delay_state * 1000);
				}
				usleep($delay_gpio * 1000);
				echo " ";
			}
			echo "\n";
			return true;
		}
		else{
			$this->_EchoNotImplemented();
		}
	}



	// ---------------------------------------------------------------------------------------
	public function GetFlashSize($k){
		return $this->flash_sizes[$k];
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommands($host_arr, $commands_list){
		if($this->api_urls['command']){
			$commands_txt	=$this->_CleanTxtList($commands_list);
			$this->_EchoNotImplemented("While sending Commands: $commands_txt :\n");
		}
		else{
			$this->_EchoNotImplemented();
		}
	}

	// ---------------------------------------------------------------------------------------
	public function RemoteSendCommand($host_arr, $command){
		if($this->api_urls['command']){
			$url=$this->_MakeApiUrl($host_arr, $this->api_urls['command'], $command);

			//echo "$url\n";		
			if ($json=$this->_FetchPage($url)){
				return json_decode($json,true);
			}
	
			if($this->last_http_code==200){
				return true;
			}
		}
		else{
			$this->_EchoNotImplemented("While sending Command: $command :\n");
		}
	}

	

	// ##### Protected ########################################################################
	// ---------------------------------------------------------------------------------------
	protected function _RemoteGetVersionRaw($host_arr){
		if($this->api_urls['version']){
			$url=$this->_MakeApiUrl($host_arr, $this->api_urls['version']);
			return $this->_FetchPage($url, $host_arr['login'], $host_arr['pass']);
		}
	}



	// ---------------------------------------------------------------------------------------
	protected function _RemoteGetVersionJson($host_arr){
		if($this->api_urls['version']){
			if($json=$this->_RemoteGetVersionRaw($host_arr) ){
				if($json and $arr=json_decode($json,true) and is_array($arr)){
					return $arr;
				}
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	protected function _RemoteBackupSettings( $host_arr, $dest_path, $file_name='config',$url_key='backup'){
		$url=$this->api_urls[$url_key] or $url=$url_key;
		$url=$this->_MakeApiUrl($host_arr,$url);
		return (int) $this->_DownloadFile($url, $file_name, $dest_path, $host_arr['login'], $host_arr['pass']);
	}

	// ---------------------------------------------------------------------------------------
	protected function _SendGpioOn($host_arr,$pin){
		return $this->_SendGpio($host_arr, $pin, $this->api_urls['gpio_on']);
	}

	// ---------------------------------------------------------------------------------------
	protected function _SendGpioOff($host_arr,$pin){
		return $this->_SendGpio($host_arr, $pin, $this->api_urls['gpio_off']);
	}

	// ---------------------------------------------------------------------------------------
	protected function _SendGpio($host_arr, $pin, $gpio_url){
		$url=$this->_MakeApiUrl($host_arr,$gpio_url);
		$url=str_replace('{{gpio}}', $pin, $url);
		//echo "\n $url \n";
		return $this->_FetchPage($url, $host_arr['login'], $host_arr['pass']);
	}


	// ---------------------------------------------------------------------------------------
	protected function _MakeApiUrl($host_arr, $url,$suffix=''){
		$host_arr['login'] or $host_arr['login']=$this->default_login;

		$url=$this->api_prefix.$host_arr['ip'].$url;
		$url=str_replace('{{login}}',	$host_arr['login'], $url);
		$url=str_replace('{{pass}}',	$host_arr['pass'], $url);
		$url .=$suffix;
		return $url;
	}

	// ---------------------------------------------------------------------------------------
	protected function _CleanTxtListToArray($commands_list){
		$commands_list	=$this->_CleanTxtList($commands_list);
		$commands		=$this->_TxtListToarray($commands_list);
		return $commands;
	}

	// ---------------------------------------------------------------------------------------
	protected function _CleanTxtList($str){
		
		//remove comments
		$str=preg_replace('|\s*#.*$|m','',$str);
		
		//remove blank lines
		$str=preg_replace('#^\s*[\n\r]+#m','',$str);
		return $str;
	}

	// ---------------------------------------------------------------------------------------
	protected function _TxtListToarray($str){
		$lines=preg_split('#[\n\r]+#',$str);
		if(is_array($lines)){
			foreach($lines as $line){
				$line=trim($line);
				list($k,$v)=preg_split('#\s+#',$line,2);
				$arr[$k]=$v;
			}
			return $arr;
		}
	}

/*
	// ---------------------------------------------------------------------------------------
	protected function _TriggerUrl($url,$auth_login="",$auth_pass=""){
		$http			=array();
		$http['method']	='GET';
		$http['timeout']=0.5;

		$auth_login or $auth_login=$this->default_login;
		if($auth_login and $auth_pass){
			$auth = base64_encode("$auth_login:$auth_pass");
			$http['header'] = array("Authorization: Basic $auth");
		}
		$opts = array('http' => $http);
		return @file_get_contents($url, false, stream_context_create($opts));
	}
*/
	// ---------------------------------------------------------------------------------------
	protected function _DownloadFile($url, $file_name, $dest_path, $auth_login='', $auth_pass=''){
		$tmp_file	= $dest_path.'temp_file';
		$dest_file	= $dest_path. $file_name;

		$this->SettLastStatus(0);

		$fp = fopen($tmp_file, 'w+');
		if($fp === false){
			$error=true;
			@fclose($fp);
			@unlink($tmp_file);
			$this->SettLastStatus(901);
			return false;
		}

		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$auth_login or $auth_login=$this->default_login;
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
		$this->SettLastStatus($status);
		//print_r(curl_getinfo($ch));
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

		$this->SettLastStatus(0);

		$ch = curl_init($url);

		$auth_login or $auth_login=$this->default_login;
		if($auth_login and $auth_pass){
			curl_setopt($ch, CURLOPT_USERPWD, "$auth_login:$auth_pass");
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dont verify SSL
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$result	= curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);		
		if(curl_errno($ch) or $status !=200 ){
			$result='';
		}
		$this->SettLastStatus($status);
		//print_r(curl_getinfo($ch));
		curl_close($ch);
		return $result;
	}



	// ##### Privates #########################################################################

	// ---------------------------------------------------------------------------------------
	private function _Init($path_to_repo){
		$this->path_base	= $path_to_repo;
		$this->path_build	= $this->path_base . $this->dir_build;
		$this->path_firmware= $this->path_build . $this->dir_firmware;
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
	protected function _EchoNotImplemented($add_txt=""){
		if($add_txt){
			echo $add_txt;
		}
		echo "\n#### ERROR: Not Implemented in repo: {$this->name} ####\n";

		$back=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
		$method=$back[1]['class'].'->'.$back[1]['function'];
		echo "     (Coders, please create the $method method!)\n";
				
	}

}
?>