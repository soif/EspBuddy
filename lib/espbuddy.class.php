<?php
/*
--------------------------------------------------------------------------------------------------------------------------------------
EspBuddy - An Upload Toolbox for ESP8266 based devices
--------------------------------------------------------------------------------------------------------------------------------------
Copyright (C) 2018  by François Déchery - https://github.com/soif/

EspBuddy is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

EspBuddy is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------------------------------------------------------
*/
class EspBuddy {

	public $class_version		= '1.40';	// EspBuddy Version

	private $cfg				= array();	// hold the configuration
	private $espb_path			= '';	// Location of the EspBuddy root directory

	// command lines arguments
	private $args				= array();	// command line arguments
	private $bin				= '';		// binary name of the invoked command
	private $action				= '';		// command line action
	private $target				= '';		// command line target

	// command lines flags
	private $flag_noconfirm		= false;
	private $flag_drymode		= false;
	private $flag_verbose		= false;
	private $flag_build			= false;
	private $flag_serial		= false;
	private $flag_eraseflash	= false;
	private $flag_skipinter		= false;
	private $flag_prevfirm		= false;

	// command lines variables
	private $arg_port			= '';
	private $arg_rate			= 0;
	private $arg_conf			= '';
	private $arg_login			= '';
	private $arg_pass			= '';

	//selected configuration for the current host
	private $c_host	=array();	//	current host
	private $c_conf	=array();	//	current config
	private $c_repo	=array();	//	current repository
	private $c_serial=array();	//	current serial port and rate

	private $orepo	;			//	repo_object



	// ---------------------------------------------------------------------------------------
	function __construct(){
		$this->espb_path=dirname(dirname(__FILE__)).'/';
	}

	// ---------------------------------------------------------------------------------------
	public function LoadConf($config_file){
		if(!file_exists($config_file)){
			$this->_dieError("Configuration File Not Found at $config_file !!!\nPlease copy config-sample.php to config.php, set it to your environment, and try again.");
		}
		require($config_file);
		$this->cfg=$cfg;
		
		if(!$this->cfg['paths']['dir_backup']){
			$this->_dieError("You must define a \$cfg['paths']['dir_backup'] to store firmwares. See config-sample.php for an example.");			
		}

		if(! file_exists($this->cfg['paths']['dir_backup'])){
			mkdir($this->cfg['paths']['dir_backup']);
			if(!is_dir($this->cfg['paths']['dir_backup'])){
				$this->_dieError("Can not create the backup directory at : {$this->cfg['paths']['dir_backup']}");			
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	public function CommandLine(){
		$this->_ParseCommandLine();

		switch ($this->action) {
			case 'upload':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'build':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'monitor':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'backup':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'version':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'ping':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;

			case 'repo_version':
				$this->Command_repo('version');
				break;
			case 'repo_pull':
				$this->Command_repo('pull');
				break;
			case 'list_configs':
				$this->Command_list('configs');
				break;
			case 'list_hosts':
				$this->Command_list('hosts');
				break;
			case 'list_repos':
				$this->Command_list('repos');
				break;
			case 'help':
				$this->Command_help();
				break;
	
			default:
				echo "Invalid Command! ";
				$this->Command_usage();
				global $argv;
				echo "* Use '{$this->bin} help' to list all options\n";
				echo "\n";
				break;
		}
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	public function BatchProcessCommand($command, $id){
		if($this->flag_drymode){
			$in_drymode=" in DRY MODE";
		}
		$hosts=$this->_ListHosts($id);
		$c=count($hosts);

		echo "Processing $c host(s)$in_drymode : \n\n";

		foreach($hosts as $this_id => $host){
			$name=str_pad($this->_FillHostnameOrIp($this_id), 30);
			echo "\033[35m##### $name ##### : \033[0m";
			//if($c==1){echo "\n";}
			$fn="Command_$command";
			$this->$fn($this_id);
		}
		//if($c==1){echo "\n";}
	}

	// ---------------------------------------------------------------------------------------
	private function _ListHosts($id){
		if($id == '0'){
			$ret=$this->cfg['hosts'];
		}
		else{
			$ret=array($id => $this->cfg['hosts'][$id]);
		}
		return $ret;
	}

	// ---------------------------------------------------------------------------------------
	public function ChooseTarget(){
		if($host=$this->target){
			$force_selected=true; 
			if($host=='all'){
				$choosen='a';
			}
			elseif(!$this->cfg['hosts'][$host]){
				$this->_dieError('Invalid Host','hosts');
			}
			else{
				$id=$host;
			}
		}

		$choices['a']='All Hosts';
		$str_choices="a # ALL #,";
		$n=1;
		foreach($this->cfg['hosts'] as $h => $arr){
			$index=chr(97+$n);	//65
			$n++;
			$choices[$index] =$h;
			$name=$arr['hostname'] or $name=$arr['ip'];
			$str_choices .="$index {$name}";
			if($n <= count($this->cfg['hosts'])){$str_choices .=",";}
		}
		if(!$force_selected){
			echo "Choose Target Host : \n ";	
			$choosen	=$this->_Ask($str_choices);
			$id			=$choices[$choosen];
		}

		if($choosen == 'a'){
			echo "\n-----------------------------------\n";
			echo "You have choosen : ";
			echo " -> ALL HOSTS \n";
			$id=0;
		}
		else{
			$this->_AssignCurrentHostConfig($id);
			echo "\n";
			echo "\n-----------------------------------\n";
			echo "You have choosen : ";
			$host	=$this->c_host;
			echo " + Host key   : $id \n";
			echo " + Host Name  : {$host['hostname']}\n";
			echo " + Host IP    : {$host['ip']}\n";
			echo " + Config     : {$this->c_host['config']}\n";
			if($this->flag_verbose){
				echo "\033[37m";
				echo " + Parameters : \n";
				$this->_Prettyfy($this->cfg['configs'][$host['config']]);
				echo "\033[0m";
			}
		}

		// confirm -------
		if(!$this->flag_noconfirm and !$force_selected){
			echo "\n";
			echo "Please Confirm : ";
			$confirm=strtolower($this->_Ask("Yes,No",'',", ","? "));
			echo "\n";
			if($confirm=='n'){
				die("--> Cancelled!\n\n");
			}
		}
			echo "\n";
		return $id;
	}

	// ---------------------------------------------------------------------------------------
	private function _AssignCurrentHostConfig($id){
		$this->_FillHostnameOrIp($id);

		$this->c_host					=	$this->cfg['hosts'][$id];
		$this->arg_conf and $this->c_host['config']=$this->arg_conf;

		$this->c_host['path_dir_backup']=	$this->_CreateBackupDir($this->c_host);
		
		$this->c_conf	=	$this->cfg['configs'][$this->c_host['config']];
		$this->c_repo	=	$this->cfg['repos'][$this->c_conf['repo']];
		
		if(!is_array($this->c_conf)){
			return $this->_dieError ("Unknown configuration '{$this->c_host['config']}' ",'configs');
		}

		// login / pass for this host ----------
		$tmp		= $this->arg_login		or
			$tmp	= $this->c_host['login']	or
			$tmp	= $this->c_conf['login']	;
		$this->c_host['login']	=$tmp;

		$tmp		= $this->arg_pass		or
			$tmp	= $this->c_host['pass']	or
			$tmp	= $this->c_conf['pass']	;
		$this->c_host['pass']	=$tmp;

		// serial port to use ---------------
		$this->c_serial['port']		=	$this->arg_port	or
			$this->c_serial['port']	=	$this->cfg['serial_ports'][$this->c_host['serial_port']]	or
			$this->c_serial['port']	=	$this->c_host['serial_port']								or
			$this->c_serial['port']	=	$this->cfg['serial_ports'][$this->c_conf['serial_port']]	or
			$this->c_serial['port']	=	$this->c_conf['serial_port']								or
			$this->c_serial['port']	=	$this->cfg['serial_ports']['default']	;

		$this->c_serial['rate']		=	$this->arg_rate	or
			$this->c_serial['rate']	=	$this->cfg['serial_rates'][$this->c_host['serial_rate']]	or
			$this->c_serial['rate']	=	$this->c_host['serial_rate']								or
			$this->c_serial['rate']	=	$this->cfg['serial_rates'][$this->c_conf['serial_rate']]	or
			$this->c_serial['rate']	=	$this->c_conf['serial_rate']								or
			$this->c_serial['rate']	=	$this->cfg['serial_rates']['default']	;
		
		if($this->c_conf['repo']){
			$this->_RequireRepo($this->c_conf['repo']);
			if($this->c_conf['2steps']){
				$this->c_conf['firststep_firmware']	=$this->espb_path . $this->orepo->GetFirstStepFirmware();
				$this->c_conf['firststep_delay']	=$this->orepo->GetFirstStepDelay();
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _RequireRepo($name){
		$repo_path	=$this->cfg['repos'][$name]['path_repo'];
		$class_path	= dirname(__FILE__)."/espb_repo_{$name}.php";
		$class_name	= "EspBuddy_Repo_$name";
		if(!$this->cfg['repos'][$name]){
			$this->_dieError ("Unknown repository '$name' ");
		}
		if(!$repo_path){
			$this->_dieError ("You must define the path to your '$name' repo,  in \$cfg['repos']['$name']['path_repo'] ");
		}
		if(!file_exists($class_path)){
			$this->_dieError ("Cant find a '$name' class at : $class_path");
		}

		require_once($class_path);
		$this->orepo= new $class_name($repo_path);
	}

	// ---------------------------------------------------------------------------------------
	private function _CreateBackupDir($host){
		$dir	= $this->cfg['paths']['dir_backup'];
		$name	= $host['hostname'] or $name = $host['ip'] or $name = "_ERROR_";
		$path="$dir$name/";
		if(!file_exists($path)){
			mkdir($path);
		}
		return $path;
	}


	// ---------------------------------------------------------------------------------------
	public function Command_upload($id){
		$this->_AssignCurrentHostConfig($id);

		//compilation ---------------
		if($this->flag_build){
			if(! $this->Command_build($id)){
				$this->_dieError ("Compilation Failed");
			}
			$firmware="{$this->c_host['path_dir_backup']}firmware.bin";	
			$echo_name="NEWEST";	
		}
		elseif($this->flag_prevfirm){
			$firmware="{$this->c_host['path_dir_backup']}firmware_previous.bin";
			$echo_name="PREVIOUS";			
		}
		else{
			//$path_build=$this->orepo->GetPathBuild();
			//$firmware_pio="{$path_build}.pioenvs/{$this->c_conf['environment']}/firmware.bin";
			$firmware="{$this->c_host['path_dir_backup']}firmware.bin";	
			$echo_name="LATEST";			
		}
		
		if(!file_exists($firmware)){
			$this->_dieError ("No ($echo_name) Firmware found at: $firmware");
		}

		echo "\n";
		$date=date("d M Y - H:i::s", filemtime($firmware));
		$this->_EchoStepStart("Using $echo_name Firmware (Compiled on $date )  : $firmware","");

		// wire mode ------------------
		if($this->flag_serial){
			if($this->flag_eraseflash){
				$this->DoSerial($id,'erase_flash');
				$this->_WaitReboot(5);
			}
			$this->DoSerial($id,'write_flash', $firmware);
		}

		// OTA mode ------------------
		else{
			// two steps  upload ?
			if($this->c_conf['2steps'] and ! $this->flag_skipinter ){
				$command	="{$this->cfg['paths']['bin_esp_ota']} -r -d -i {$this->c_host['ip']}  -f {$this->c_conf['firststep_firmware']}";
				echo "\n";
				$this->_EchoStepStart("Uploading Intermediate Uploader Firmware", $command);
			
			if(!$this->flag_drymode){
					passthru($command, $r);
					if($r){
						return $this->_dieError ("First Upload Failed");
					}	
				}
				//wait ?
				if($this->c_conf['firststep_delay']){
					echo "\n";
					$this->_WaitReboot($this->c_conf['firststep_delay']);
				}
			}

			// Final Upload
			$command	="{$this->cfg['paths']['bin_esp_ota']} -r -d -i {$this->c_host['ip']}  -f  $firmware";
			echo "\n";
			$this->_EchoStepStart("Uploading Final Firmware", $command);

			if(!$this->flag_drymode){
				passthru($command, $r);
				if($r){
					return $this->_dieError ("Upload Failed");
				}
			}
			return true;	
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_build($id){
		$this->_AssignCurrentHostConfig($id);
		$path_build=$this->orepo->GetPathBuild();

		$commands_compil[]="cd {$path_build} ";
		if(is_array($this->c_conf['exports'])){
			foreach( $this->c_conf['exports'] as $k => $v ){
				$commands_compil[]	=$this->_ReplaceTags("export $k='$v'", $id);
			}
		}
		$start_compil =time();
		$commands_compil[]="{$this->cfg['paths']['bin_pio']} run -e {$this->c_conf['environment']}";
		$command=implode(" ; \n   ", $commands_compil);
		echo "\n";
		$this->_EchoStepStart("Compiling {$this->c_conf['repo']} : {$this->c_conf['environment']}", $command);
		if(! $this->flag_drymode){
			passthru($command, $r);
			//keep STARTING compil time
			$firmware_created="{$path_build}.pioenvs/{$this->c_conf['environment']}/firmware.bin";
			if(!$r and file_exists($firmware_created)){
				touch($firmware_created,$start_compil);
			}
		}
		if(!$r){
			
			$command_backup[] = "mv -f {$this->c_host['path_dir_backup']}firmware.bin {$this->c_host['path_dir_backup']}firmware_previous.bin";	
			$command_backup[] = "cp -p {$path_build}.pioenvs/{$this->c_conf['environment']}/firmware.bin {$this->c_host['path_dir_backup']}";	
			$command=implode(" ; \n   ", $command_backup);
			echo "\n";
			$this->_EchoStepStart("Backup the previous firmware, and archive the new one", $command);
			if(! $this->flag_drymode){
				passthru($command, $r2);
			}
			return !$r2;
		}
		return !$r;
	}

	// ---------------------------------------------------------------------------------------
	function Command_backup($id){
		$this->_AssignCurrentHostConfig($id);
		$tmp_dir	=$this->c_host['path_dir_backup']."settings_tmp/";
		$prev_dir	=$this->c_host['path_dir_backup']."settings_previous/";
		$dest_dir	=$this->c_host['path_dir_backup']."settings/";
		@mkdir($tmp_dir, 0777, true);
		if(is_dir($tmp_dir)){
			$count= $this->orepo->BackupRemoteSettings($this->c_host, $tmp_dir);
			if($count){
				echo "Downloaded $count files \n";
				//remove prev
				@array_map( "unlink", glob( $prev_dir."*" ) );
				@rmdir($prev_dir);
				//mv last to prev
				@rename($dest_dir, $prev_dir);
				//mv tmp to dest
				@rename($tmp_dir, $dest_dir);
				
				return true;
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	function Command_monitor($id){
		$this->_AssignCurrentHostConfig($id);
		$command="{$this->cfg['paths']['bin_pio']} device monitor --port {$this->c_serial['port']} --baud {$this->c_serial['rate']} --raw  --echo ";
		echo "\n";
		$this->_EchoStepStart("Monitoring Serial Port: {$this->c_serial['port']} at {$this->c_serial['rate']} baud",$command);
		if(!$this->flag_drymode){
			passthru($command, $r);
			if($r){
				return $this->_dieError ("Serial monitor Failed");
			}	
		}
		return true;
	}

	// ---------------------------------------------------------------------------------------
	private function DoSerial($id,$action='write_flash',$firmware_file=''){
		$this->_AssignCurrentHostConfig($id);
		$path_build=$this->orepo->GetPathBuild();

		if(!$this->c_serial['port']){
			return $this->_dieError ("No Serial Port choosen");
		}

		$this->c_serial['rate']	 and 
			$arg_rate=" -b {$this->c_serial['rate']}" and
			$echo_rate=", Rate: {$this->c_serial['rate']} bauds";

		$command="{$this->cfg['paths']['bin_esptool']} -p {$this->c_serial['port']}{$this->c_serial['rate']} $action ";

		switch ($action) {
			case 'write_flash':
				$command .="0x0 {$firmware_file} ";
				break;
			case 'erase_flash':
				break;
			case 'read_mac':
				break;
			default:
				return $this->_dieError ("Invalid Action");
				break;
		}
		echo "\n";
		$this->_EchoStepStart("Serial Action: $action (Port: {$this->c_serial['port']}$echo_rate)",$command);
	
		if(!$this->flag_drymode){
			passthru($command, $r);
			if($r){
				return $this->_dieError ("Serial Upload Failed");
			}	
		}
		return true;
	}

	// ---------------------------------------------------------------------------------------
	public function Command_version($id){
		$this->_AssignCurrentHostConfig($id);
		echo "{$this->c_conf['repo']}\t".$this->orepo->GetRemoteVersion($this->c_host) . "\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_repo($type){
		$repo_key=$this->target;
		$repo=$this->cfg['repos'][$repo_key];

		$this->_RequireRepo($repo_key);
		
		if($type == "version"){			
			$version = $this->orepo->GetVersion() or $version= "Not found";
			echo "*** Local '$repo_key' Repository Version is	: $version \n";
		}
		if($type == "pull"){
			$this->Command_repo('version');
			
			$command="cd {$repo['path_repo']} ; git pull ";
			echo("*** Loading '$repo_key' git commits	: ");
			if(!$this->flag_drymode){
				if(passthru($command, $r)){
					echo "\n";
				}
			}
			$this->Command_repo('version');
			echo "\n";
		}
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_ping($id,$count=0){
		$this->_AssignCurrentHostConfig($id);
		if(!$count){
			$count	=4;
			$opt	="-o ";
		}
		$opt .="-c $count";
		$command="ping $opt -n -W 2000 -q {$this->c_host['ip']} 2> /dev/null | grep loss";
		if(!$this->flag_drymode){
			$result	=trim(shell_exec($command));
			$result	=str_replace('packet loss',	'loss',	$result);	
			$result	=str_replace('packets transmitted',	'sent',	$result);	
			$result	=str_replace('packets received',		'rcv',	$result);
	
			if		(preg_match('# 0.0% loss#', 	$result))	{$result .="\t\t OK";}	
			elseif	(preg_match('# 100.0% loss#',	$result))	{$result .="\t\t Offline";}	
			else												{$result .="\t\t -";}	
			echo "$result\n";
		}
		return "$command\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_list($type){
		switch ($type) {
			case 'configs':
				echo "Available Configurations are: \n";
				foreach($this->cfg['configs'] as $conf => $arr){
					$name=str_pad($conf,25);
					echo "  - $name : Repo = {$arr['repo']},	Env = {$arr['environment']}\n";
				}
				break;

			case 'hosts':
				echo "Available Hosts are: \n";
				foreach($this->cfg['hosts'] as $id => $arr){
					$name=str_pad($id,15);
					echo "  - $name		: " . $this->_FillHostnameOrIp($id)."\n";
				}
				break;
	
			case 'repos':
				echo "Available Repositories are: \n";
				foreach($this->cfg['repos'] as $repo => $arr){
					$name=str_pad($repo,15);
					echo "  - $name		: {$arr['path_repo']}\n";
				}
				break;
	
			default:
				$this->_dieError ("Unknown List type '$type'");
				# code...
				break;
		}
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_usage(){
		$allowed_commands=array(
			'upload'		=> "Build and/or Upload current repo version to Device(s)",
			'build'			=> "Build current repo version",
			'backup'		=> "Backup remote devices settings",
			'monitor'		=> "Monitor the serial port",
			'version'		=> "Show Device(s) Version",
			'ping'			=> "Ping Device(s)",
			'repo_version'	=> "Show Repo's Current version", 
			'repo_pull'		=> "Git Pull Repo's master version",
			'list_hosts'	=> "List all available hosts",
			'list_configs'	=> "List all available configurations",
			'list_repos'	=> "List all available repositories",
			'help'			=> "Show full help"
			);

		echo "USAGE: {$this->bin} [OPTIONS] COMMAND [ARGUMENTS] \n";
		echo "\n";
		echo "* Valid Commands are: \n";
		foreach($allowed_commands as $k => $v){
			echo "  - ".str_pad($k,15)." : $v\n";
		}
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_help(){
		$bin= $this->bin;
		$this->Command_usage();
		echo <<<EOF

* upload (command) : 
	USAGE   : $bin [OPTIONS+UPLOAD_OPTIONS] upload [HOST]
	Desc    : Upload to the board using OTA as default

* build (command) : 
	USAGE   : $bin [OPTIONS]  build [HOST]
	Desc    : Build the firmware

* backup (command) : 
	USAGE   : $bin [OPTIONS+AUTH_OPTIONS]  backup [HOST]
	Desc    : Download and archive settings from the remote board

* monitor (command) : 
	USAGE   : $bin [OPTIONS]  monitor [HOST]
	Desc    : Monitor the serial port

* version (command) : 
	USAGE   : $bin [OPTIONS] version
	Desc    : Get the board installed version

* ping (command) : 
	USAGE   : $bin [OPTIONS] ping
	Desc    : Ping board

* repo_version (command) : 
	USAGE   : $bin repo_version REPO
	Desc    : Parse the current repository (REPO) version. REPO is a supported repository (espurna or espeasy)

* repo_pull (command) : 
	USAGE   : $bin repo_pull REPO
	Desc    : Git Pull the local repository (REPO). REPO is a supported repository (espurna or espeasy)

* list_hosts (command) : 
	USAGE   : $bin list_hosts
	Desc    : List all hosts defined in config.php

* list_configs (command) : 
	USAGE   : $bin list_configs
	Desc    : List all available configurations defined in config.php

* list_repos (command) : 
	USAGE   : $bin list_repos
	Desc    : List all available repositories defined in config.php



* OPTIONS :
	-f  : don't confirm choosen host (when no host provided)
	-d  : Dry Run. Show commands but don't apply them
	-v  : Verbose

* UPLOAD_OPTIONS :
	-b  : Build before Uploading
	-p  : Upload previous firmware backuped, instead of the latest built 
	-s  : Skip Intermediate Upload (if set)
	-w  : Wire Mode : Upload using the Serial port instead of the default OTA
	-e  : In Wire Mode, erase flash first, then upload

	--port=xxx     : serial port to use (overrride main or per host serial port)
	--rate=xxx     : serial port speed to use (overrride main or per host serial port)
	--conf=xxx     : config to use (overrride per host config)

* AUTH_OPTIONS :
	--login=xxx    : login name (overrride host or per config login)
	--pass=xxx     : password (overrride host or per config password)

EOF;
	}

	// ---------------------------------------------------------------------------------------
	private function _ReplaceTags($str, $id){
		$this->_AssignCurrentHostConfig($id);

		$ip		=	$this->c_host['ip'];
		list($ip1,$ip2,$ip3,$ip4)=explode('.',$ip);	

		$fqdn	=	$this->c_host['hostname'];
		list($name,$domain)=explode('.', $fqdn);	

		if(preg_match_all('#{{([^}]*)}}#',$str,$matches)){
			foreach($matches[1] as $k=>$v){
				if		($v=='host_ip')		{$str=str_replace('{{'.$v.'}}', $ip,	$str);}
				elseif	($v=='host_ip1')	{$str=str_replace('{{'.$v.'}}', $ip1,	$str);}
				elseif	($v=='host_ip2')	{$str=str_replace('{{'.$v.'}}', $ip2,	$str);}
				elseif	($v=='host_ip3')	{$str=str_replace('{{'.$v.'}}', $ip3,	$str);}
				elseif	($v=='host_ip4')	{$str=str_replace('{{'.$v.'}}', $ip4,	$str);}
				elseif	($v=='host_fqdn')	{$str=str_replace('{{'.$v.'}}', $fqdn,	$str);}
				elseif	($v=='host_name')	{$str=str_replace('{{'.$v.'}}', $name,	$str);}
			}
		}
		return $str;
	}

	// ---------------------------------------------------------------------------------------
	//http://stackoverflow.com/questions/3684367/php-cli-how-to-read-a-single-character-of-input-from-the-tty-without-waiting-f
	private function _Ask($str_choices='', $force='', $sep="\n ", $eol="\n"){
		if($force){
			return $force;
		}

		if($str_choices){
			//echo "$str_choices ? ";
			$choices=explode(',',$str_choices);
			$n=count($choices);
			foreach($choices as $k=>$v){
				$n--;
				$choices[$k]=strtolower(substr($v,0,1));
				echo "[".strtoupper($choices[$k])."]".substr($v,1);
				if($n){
					echo "$sep";	//", "
				}
				else{
					echo " $eol";	//"? "
				}
			}
		}

		// Save existing tty configuration
		$term = trim(`stty -g`);
		system("stty -icanon");
		//wait answer
		while ($c = fread(STDIN, 1)) {
	    	$c=strtolower($c);
			if($choices and !in_array($c,$choices)){
				echo " ";
				continue;
			}
			//echo "\n";
			break;
		}
		// Reset the tty back to the original configuration
		system("stty '" . $term . "'");
		return $c;
	}

	// ---------------------------------------------------------------------------------------
	private function _FillHostnameOrIp($id){
		global $cfg;
		$this->cfg['hosts'][$id]['ip'] 			or $this->cfg['hosts'][$id]['ip']		=gethostbyname($this->cfg['hosts'][$id]['hostname']);
		$this->cfg['hosts'][$id]['hostname']	or $this->cfg['hosts'][$id]['hostname']	=gethostbyaddr($this->cfg['hosts'][$id]['ip']);
		
		$name = str_pad($this->cfg['hosts'][$id]['hostname'], 30) . '(' . str_pad($this->cfg['hosts'][$id]['ip'],14) .')' ;	
		return $name;
	}

	// -------------------------------------------------------------
	private function _ParseCommandLine(){
		global $argv;
		$this->args		= $this->_ParseArguments($argv);
		$this->bin 		= basename($this->args['commands'][0]);
		$this->action	= $this->args['commands'][1];
		$this->target	= $this->args['commands'][2];

		//global flags
		$this->flag_noconfirm	= (boolean) $this->args['flags']['f'];
		$this->flag_drymode 	= (boolean) $this->args['flags']['d'];
		$this->flag_verbose		= (boolean) $this->args['flags']['v'];

		$this->flag_build		= (boolean) $this->args['flags']['b'];
		$this->flag_prevfirm	= (boolean) $this->args['flags']['p'];
		$this->flag_serial		= (boolean) $this->args['flags']['w'];
		$this->flag_eraseflash	= (boolean) $this->args['flags']['e'];
		$this->flag_skipinter	= (boolean) $this->args['flags']['s'];

		$this->arg_port			= $this->args['vars']['port'];
		$this->arg_rate			= $this->args['vars']['rate'];
		$this->arg_conf			= $this->args['vars']['conf'];
		$this->arg_login		= $this->args['vars']['login'];
		$this->arg_pass			= $this->args['vars']['pass'];

		$this->host				= $this->args['commands'][2];
		
	}

	// -------------------------------------------------------------
	// http://php.net/manual/en/features.commandline.php#78804
	private function _ParseArguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
				$key = $matches[1];
				switch ($matches[2]) {
					case '':
					case 'true':
						$arg = true;
						break;
					case 'false':
						$arg = false;
						break;
					default:
						$arg = $matches[2];
				}
		   
				/* make unix like -afd == -a -f -d */			
				if(preg_match("/^-([a-zA-Z0-9]+)/", $matches[0], $match)) {
					$string = $match[1];
					for($i=0; strlen($string) > $i; $i++) {
						$_ARG['flags'][$string[$i]] = true;
					}
				}
				else {
					$_ARG['vars'][$key] = $arg;	  
				}			
			}
			else {
				$_ARG['commands'][] = $arg;
			}		
		}
		$_ARG['commands'][0] = basename($_ARG['commands'][0]);
		return $_ARG;	
	}

	// ---------------------------------------------------------------------------------------
	// https://stackoverflow.com/questions/1168175/is-there-a-pretty-print-for-php
	private function _Prettyfy($arr, $level=0){
	    $tabs = "    ";
	    for($i=0;$i<$level; $i++){
	        $tabs .= "    ";
	    }
	    $tabs .= " - ";
	    foreach($arr as $key=>$val){
	        if( is_array($val) ) {
	            print ($tabs . $key . " : " . "\n");
	            $this->_Prettyfy($val, $level + 1);
	        } else {
	            if($val && $val !== 0){
	                print ($tabs . str_pad($key,22) . " : " . $val . "\n"); 
	            }
	        }
	    }
	}

	// ---------------------------------------------------------------------------------------
	private function _WaitReboot($sleep){
		$this->_EchoStepStart("Waiting  {$sleep} sec for ESP to reboot",'',0);
		if(!$this->flag_drymode){
			while($sleep){
				sleep(1);
				echo "$sleep ";
				$sleep--;
			}
		}
		echo " ********\n";
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoStepStart($mess, $command="", $do_end=1,$char="*"){
		if($this->flag_verbose){
			$verbose=true;
		}
		if($verbose) echo "\n";
		$mess	="$char$char $mess ";
		if($do_end){
			$mess=str_pad($mess, 120, $char);
		}
		echo "\033[34m$mess";
		if($verbose and $command){
			echo "\n\033[0m\033[37m-> $command\033[0m\n";
		}
		else{
			$do_end and $this->_EchoStepEnd();
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoStepEnd(){
		echo "\033[0m\n";
	}

	// ---------------------------------------------------------------------------------------
	private function _dieError($mess,$list=''){
		echo "\n";
		echo "\033[31mFATAL ERROR: $mess !!!";
		echo "\033[0m\n";
		if($list){
			echo "\n";
			$this->command_list($list);
		}
		else{
			echo "\n";
		}
		die();
	}

}

?>