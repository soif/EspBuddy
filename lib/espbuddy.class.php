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

	public $class_version		= '1.84b';	// EspBuddy Version

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
	private $arg_serial_port	= '';
	private $arg_serial_rate	= 0;
	private $arg_config			= '';
	private $arg_firmware		= '';
	private $arg_login			= '';
	private $arg_pass			= '';
	private $arg_from			= '';	// repo to migrate from

	//selected configuration for the current host
	private $c_host		=array();	//	current host
	private $c_conf		=array();	//	current config
	//private $c_repo		=array();	//	current repository
	private $c_serial	=array();	//	current serial port and rate

	private $orepo	;			//	repo_object

	// preferences -------------
	private $prefs	=array(
		'config'		=>	'',				// default config to use
		'serial_port'	=>	'',				// default serial Port (empty = autoselect)
		'serial_rate'	=>	'boot',			// default serial rate
		'time_zone'		=>	'Europe/Paris',	// Time Zone
		'show_version'	=>	2,				// show version in firmware name (0=no, 1=file version, 2=full git version)
		'firm_name'		=>	'Firmware',		// firmware name prefix
		'settings_name'	=>	'Settings',		// firmware settings name prefix
		'name_sep'		=>	'-',			// field separator in firmware name
		'keep_previous'	=>	3,				// number of previous firmware version to keep
		'checkout_mode'	=>	1,				// Mode when doing a Git checkout : 0 = no checkout, 1 = only if clean, 2 = allows modifications, 3 stash modifications first if any
 	);

	private $serial_ports	= array(
		'nodemcu'	=>	'/dev/tty.SLAB_USBtoUART',		// Node Mcu
		'wemos'		=>	'/dev/tty.wchusbserialfa140',	// Wemos
		'espusb'	=>	'/dev/tty.wchusbserialfd130',	// generic ESP-01 USB programmer
		'Xftdi'		=>	'/dev/tty.usbserial-',			// FTDI on OSX

		'Lftdi'		=>	'/dev/tty.USB',					// FTDI on Linux
	);

	private $serial_rates	= array(
		'slow'		=>	'57600',
		'boot'		=>	'74880',
		'fast'		=>	'115200',
		'turbo'		=>	'460800',
	);

	private $os		="";			// what is the OS we are running


	// ---------------------------------------------------------------------------------------
	function __construct(){
		$this->espb_path=dirname(dirname(__FILE__)).'/';
		$this->_SetRunningOS();
	}



	// ##################################################################################################################################
	// ##### PUBLIC #####################################################################################################################
	// ##################################################################################################################################


	// ---------------------------------------------------------------------------------------
	public function LoadConf($config_file){
		if(!file_exists($config_file)){
			$this->_dieError("Configuration File Not Found at $config_file !!!\nPlease copy config-sample.php to config.php, set it to your environment, and try again.");
		}
		require($config_file);
		$this->cfg=$cfg;

		// preferences
		$this->_LoadPreferences($this->cfg['prefs']);

		// dir backup ------------------------
		if(!$this->cfg['paths']['dir_backup']){
			$this->_dieError("You must define a \$cfg['paths']['dir_backup'] to store firmwares. See config-sample.php for an example.");
		}

		if(! file_exists($this->cfg['paths']['dir_backup'])){
			mkdir($this->cfg['paths']['dir_backup']);
			if(!is_dir($this->cfg['paths']['dir_backup'])){
				$this->_dieError("Can not create the backup directory at : {$this->cfg['paths']['dir_backup']}");
			}
		}

		// make paths
		$this->cfg['paths']['bin']			= $this->espb_path.'bin/';
		$this->cfg['paths']['bin_espota']	= $this->cfg['paths']['bin'].	"espota.py";
		$this->cfg['paths']['bin_esptool']	= $this->cfg['paths']['bin'].	"esptool.py";
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
			case 'reboot':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'gpios':
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
				echo "Invalid Command! \n";
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
		$this->_EchoStepStart("Compiling {$this->c_conf['repo']} : {$this->c_conf['environment']} , version : {$this->c_host['versions']['file']} - {$this->c_host['versions']['full']}", $command);
		if(! $this->flag_drymode){
			passthru($command, $r);
			//keep STARTING compil time
			$firmware_created="{$path_build}.pio/build/{$this->c_conf['environment']}/firmware.bin";
			if(!$r and file_exists($firmware_created)){
				touch($firmware_created,$start_compil);
			}
		}
		if(!$r){
			if($this->_rotateFirmware()){
				if($commands_post=$this->orepo->GetPostBuildCommands($this->c_host, $this->cfg)){
					$command=implode(" ; \n   ", $commands_post);
					echo "\n";
					$this->_EchoStepStart("Processing Post Build Scripts ", $command);
					if(! $this->flag_drymode){
						passthru($command, $r);
						return !$r;
					}
				}
				return true;
			}
		}
		return !$r;
	}


	// ---------------------------------------------------------------------------------------
	public function Command_upload($id){
		$this->_AssignCurrentHostConfig($id);

		// choose firmware ---------------
		if($this->arg_firmware){
			if(file_exists($this->arg_firmware)){
				$this->_rotateFirmware($this->arg_firmware);
				$firmware="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			}
			$echo_name="EXTERNAL";
		}
		elseif($this->flag_build){
			if(! $this->Command_build($id)){
				$this->_dieError ("Compilation Failed");
			}
			$firmware="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			$echo_name="NEWEST";
		}
		elseif($this->flag_prevfirm){
			$firmware="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}_previous.bin";
			$echo_name="PREVIOUS";
		}
		else{
			$firmware="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			$echo_name="LATEST";
		}

		if(!file_exists($firmware)){
			$this->_dieError ("No ($echo_name) Firmware found at: $firmware");
		}

		echo "\n";
		$firm_source=readlink($firmware) or $firm_source=$firmware;
		$date		=date("d M Y - H:i::s", filemtime($firm_source));
		$firm_size	=filesize($firm_source);
		$firm_source=basename($firm_source);

		$this->_EchoStepStart("Using $echo_name Firmware (Compiled on $date ) : $firm_source","");

		// wire mode ------------------
		if($this->flag_serial){
			if($this->flag_eraseflash){
				$this->_DoSerial($id,'erase_flash');
				$this->_WaitReboot(5);
			}
			$this->_DoSerial($id,'write_flash', $firmware);
		}

		// OTA mode ------------------
		else{
			if($this->c_host['pass']){
				$arg_pass=" -a {$this->c_host['pass']}";
			}

			// two steps  upload ?
			if($this->c_conf['2steps'] and ! $this->flag_skipinter ){
				if($repo_from=$this->arg_from){
					$orepo1=$this->_RequireRepo($repo_from);
					$this->c_conf['firststep_firmware']	=$this->espb_path . $orepo1->GetFirstStepFirmware();
				}

				$command	="{$this->cfg['paths']['bin_espota']} -r -d -i {$this->c_host['ip']}  -f \"{$this->c_conf['firststep_firmware']}\"$arg_pass";
				echo "\n";
				$this->_EchoStepStart("Uploading Intermediate Uploader Firmware", $command);

				if(!$this->flag_drymode){
					passthru($command, $r);
					if($r){
						return $this->_dieError ("First Upload Failed");
					}
				}
				echo "\n";
				sleep(1); // let him reboot
				if(!$this->_WaitPingable($this->c_host['ip'], 20)){
					return $this->_dieError ("Can't reach {$this->c_host['ip']} after 20sec. Please retry with the -s option");
				}
				sleep(1); // give it some more time to be ready

				// assuming this is a 1M borard if not set------
				$this->c_conf['size'] or $this->c_conf['size']='1M';
				if($this->flag_verbose and $this->c_conf['size']){
					$board_size	= $this->orepo->GetFlashSize($this->c_conf['size']);
					$firm1_size	= filesize($this->c_conf['firststep_firmware']);
					$max_size	= $board_size - $firm1_size;
					$f_firm_size=round($firm_size/1024);
					$f_max_size	=round($max_size/1024);
					echo "You're going to upload a {$f_firm_size}K firmware into a {$this->c_conf['size']} device\n";
					echo "The maximum allowed size is {$f_max_size}k : ";
					if($firm_size > $max_size){
						echo "This will certainly FAIL, while espota.py will falsely seem to wait for the upload.\n";
					}
					else{
						echo "Excellent, it should fit in the flash memory !\n";
					}
				}
			}

			// Final Upload
			$command	="{$this->cfg['paths']['bin_espota']} -r -d -i {$this->c_host['ip']}  -f \"$firmware\"$arg_pass";
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
	function Command_backup($id){
		$this->_AssignCurrentHostConfig($id);
		$tmp_dir	="{$this->c_host['path_dir_backup']}{$this->c_host['settings_name']}_tmp/";
		$prev_dir	="{$this->c_host['path_dir_backup']}{$this->c_host['settings_name']}_previous/";
		$dest_dir	="{$this->c_host['path_dir_backup']}{$this->c_host['settings_name']}/";
		@mkdir($tmp_dir, 0777, true);
		if(is_dir($tmp_dir)){
			$count= $this->orepo->RemoteBackupSettings($this->c_host, $tmp_dir);
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
			else{
				$this->orepo->EchoLastError();
				echo "\n";
			}
		}
	}


	// ---------------------------------------------------------------------------------------
	function Command_monitor($id){
		$this->_AssignCurrentHostConfig($id);
		$command="{$this->cfg['paths']['bin_pio']} device monitor --port {$this->c_host['serial_port']} --baud {$this->c_host['serial_rate']} --raw  --echo ";
		echo "\n";
		$this->_EchoStepStart("Monitoring Serial Port: {$this->c_host['serial_port']} at {$this->c_host['serial_rate']} baud",$command);
		if(!$this->flag_drymode){
			passthru($command, $r);
			if($r){
				return $this->_dieError ("Serial monitor Failed");
			}
		}
		return true;
	}


	// ---------------------------------------------------------------------------------------
	public function Command_version($id){
		$this->_AssignCurrentHostConfig($id);
		echo "{$this->c_conf['repo']}\t".$this->orepo->RemoteGetVersion($this->c_host) . "\n";
	}


	// ---------------------------------------------------------------------------------------
	public function Command_reboot($id){
		$this->_AssignCurrentHostConfig($id);
		echo "{$this->c_conf['repo']}\t";
		$this->orepo->RemoteReboot($this->c_host);
	}


	// ---------------------------------------------------------------------------------------
	public function Command_gpios($id){
		$this->_AssignCurrentHostConfig($id);
		//echo "{$this->c_conf['repo']}\t";
		$this->orepo->RemoteTestAllGpios($this->c_host);
	}


	// ---------------------------------------------------------------------------------------
	public function Command_repo($type){
		$repo_key=$this->target;
		$repo=$this->cfg['repos'][$repo_key];

		$this->orepo=$this->_RequireRepo($repo_key);

		if($type == "version"){
			$version = $this->orepo->GetVersion() or $version= "Not found";
			echo "*** Local '$repo_key' Repository Version is	: $version \n";
		}
		elseif($type == "pull"){
			$this->Command_repo('version');
			echo("*** Pulling '$repo_key' git commits	: ");
			$this->_DoGit('git pull');
			$this->Command_repo('version');
		}
		elseif($type == 'checkout'){
// TODO: Checkout Git
			//$branch	= $this->c_host['checkout'] or $branch = 'master';
			//$this->_DoGit("git checkout {$branch}");
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
	private	$actions_desc=array(
			'upload'		=> "Build and/or Upload current repo version to Device(s)",
			'build'			=> "Build firmware for the selected device",
			'backup'		=> "Download and archive settings from the remote device",
			'monitor'		=> "Monitor device connected to the serial port",
			'version'		=> "Show remote device version",
			'reboot'		=> "Reboot Device(s)",
			'gpios'			=> "Test all Device's GPIOs",
			'ping'			=> "Ping Device(s)",
			'repo_version'	=> "Parse the current repository (REPO) version. REPO is a supported repository (espurna, espeasy or tasmota)", 
			'repo_pull'		=> "Git Pull the local repository (REPO). REPO is a supported repository (espurna, espeasy or tasmota)",
			'list_hosts'	=> "List all hosts defined in config.php",
			'list_configs'	=> "List all available configurations, defined in config.php",
			'list_repos'	=> "List all available repositories, defined in config.php",
			'help'			=> "Show full help"
			);
	// ---------------------------------------------------------------------------------------
	private	$actions_usage=array(
			'upload'		=> "upload	[TARGET] [options, auth_options, upload_options]",
			'build'			=> "build	[TARGET] [options]",
			'backup'		=> "backup	[TARGET] [options, auth_options]",
			'monitor'		=> "monitor	[TARGET] [options]",
			'version'		=> "version	[options]",
			'reboot'		=> "reboot	[options]",
			'gpios'			=> "gpios	[options]",
			'ping'			=> "ping	[options]",
			'repo_version'	=> "repo_version REPO", 
			'repo_pull'		=> "repo_pull    REPO",
			'list_hosts'	=> "list_hosts",
			'list_configs'	=> "list_configs",
			'list_repos'	=> "list_repos",
			'help'			=> "help"
			);

	// ---------------------------------------------------------------------------------------
	public function Command_usage(){

		echo "* Usage             : {$this->bin} ACTION [TARGET] [options]\n";
		echo "\n";
		echo "* Valid Actions : \n";
		foreach($this->actions_desc as $k => $v){
			echo "  - ".str_pad($k,15)." : $v\n";
		}
		echo "\n";
	}


	// ---------------------------------------------------------------------------------------
	public function Command_help(){
		$bin= $this->bin;
		echo $this->_espbVersions();
		echo "\n\n";
		$this->Command_usage();
		echo "* Actions Usage: \n";
		foreach($this->actions_usage as $k => $usage){
			echo "  - ".str_pad($k,15)." : $bin $usage\n";
			//echo str_pad('',6)." {$this->actions_desc[$k]}\n";
			//echo "\n";
		}
		echo <<<EOF

* OPTIONS :
	-f  : don't confirm choosen host (when no host provided)
	-d  : Dry Run. Show commands but don't apply them
	-v  : Verbose

* UPLOAD_OPTIONS :
	-b  : Build before Uploading
	-w  : Wire Mode : Upload using the Serial port instead of the default OTA
	-e  : In Wire Mode, erase flash first, then upload
	-p  : Upload previous firmware backuped, instead of the latest built 
	-s  : Skip Intermediate Upload (if set)

	--port=xxx     : serial port to use (overrride main or per host serial port)
	--rate=xxx     : serial port speed to use (overrride main or per host serial port)
	--conf=xxx     : config to use (overrride per host config)
	--firm=xxx     : full path to the firmware file to upload (override latest build one)
	--from=REPO    : migrate from REPO to the selected config

* AUTH_OPTIONS :
	--login=xxx    : login name (overrride host or per config login)
	--pass=xxx     : password (overrride host or per config password)

EOF;
	}




	// ##################################################################################################################################
	// ##### PRIVATE ####################################################################################################################
	// ##################################################################################################################################


	// ---------------------------------------------------------------------------------------
	private function _LoadPreferences($prefs){
		foreach($this->prefs as $k => $v){
			if(isset($prefs[$k])){
				$this->prefs[$k] = $prefs[$k];
			}
		}

		date_default_timezone_set($this->prefs['time_zone']);
	}


	// ---------------------------------------------------------------------------------------
	private function _SetRunningOS(){
		$os		="lin";
		$os_id	= strtolower(substr(php_uname(), 0, 3));
		
		if		($os_id=='win'){$os=='win';}	// windows
		elseif	($os_id=='dar'){$os=='osx';}	// darwin = OSX
		elseif	($os_id=='lin'){$os=='lin';}	// linux
		$this->os = $os;
	}


	// ---------------------------------------------------------------------------------------
	private function _DoGit($git_command){
		$path_base	= $this->orepo->GetPathBase();
		$commands[]	= "cd {$path_base} ";
		$commands[]	= $git_command;
		$command=implode(" ; \n   ", $commands);
		//echo "\n";
		//$this->_EchoStepStart("GIT: $git_command ",$command);
		if(!$this->flag_drymode){
			passthru($command, $r);
			if($r){
				return false;
			}
			else{
				return true;
			}
		}
		return true;
	}


	// ---------------------------------------------------------------------------------------
	private function _DoSerial($id,$action='write_flash',$firmware_file=''){
		$this->_AssignCurrentHostConfig($id);
		$path_build=$this->orepo->GetPathBuild();

		if(!$this->c_host['serial_port']){
			return $this->_dieError ("No Serial Port choosen");
		}

		$this->c_host['serial_rate']	 and
			$arg_rate=" -b {$this->c_host['serial_rate']}" and
			$echo_rate=", Rate: {$this->c_host['serial_rate']} bauds";

		$command="{$this->cfg['paths']['bin_esptool']} -p {$this->c_host['serial_port']}{$arg_rate} $action ";

		switch ($action) {
			case 'write_flash':
				$command .="0x0 \"{$firmware_file}\" ";
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
		$this->_EchoStepStart("Serial Action: $action (Port: {$this->c_host['serial_port']}$echo_rate)",$command);

		if(!$this->flag_drymode){
			passthru($command, $r);
			if($r){
				return $this->_dieError ("Serial Upload Failed");
			}
		}
		return true;
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
			echo "\n-----------------------------------\n";
		}

		if($choosen == 'a'){
			echo "Selected Host : ALL HOSTS \n";
			$id=0;
		}
		else{
			$this->_AssignCurrentHostConfig($id);
			//echo "\n";
			echo "Selected Host      : $id\n";
			$host	=$this->c_host;
			echo "       + Host Name : {$host['hostname']}\n";
			echo "       + Host IP   : {$host['ip']}\n";
			echo "       + Serial    : {$host['serial_port']}	at {$host['serial_rate']} bauds\n";
			echo "\nSelected Config    : {$this->c_host['config']}\n";
			if($this->flag_verbose){
				echo "\033[37m";
				echo "       Parameters : \n";
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
		// current host -------------
		$this->_FillHostnameOrIp($id);

		$this->c_host					= $this->cfg['hosts'][$id];
		$this->c_host['config']			= $this->_ChooseValueToUse('config');
		$this->c_host['path_dir_backup']= $this->_CreateBackupDir($this->c_host);
		$this->c_host['login']			= $this->_ChooseValueToUse('login');
		$this->c_host['pass']			= $this->_ChooseValueToUse('pass');
		$this->c_host['settings_name']	="{$this->prefs['settings_name']}{$this->prefs['name_sep']}{$this->c_conf['repo']}";

		$this->c_host['serial_rate']	= $this->_ChooseValueToUse('serial_rate', $this->serial_rates, $this->serial_rates['boot']);
		if($connected_serials=$this->_findConnectedSerialPorts()){
			$first_serial_found =reset($connected_serials);
		}
		$this->c_host['serial_port']	= $this->_ChooseValueToUse('serial_port', $this->serial_ports, $first_serial_found);

		// current config ------------
		$this->c_conf	=	$this->cfg['configs'][$this->c_host['config']];
		if(!is_array($this->c_conf)){
			return $this->_dieError ("Unknown configuration '{$this->c_host['config']}' ",'configs');
		}

		// current repo ---------------
		//$this->c_repo	=	$this->cfg['repos'][$this->c_conf['repo']];
		if($this->c_conf['repo']){
			$this->orepo=$this->_RequireRepo($this->c_conf['repo']);
			if($this->c_conf['2steps']){
				$this->c_conf['firststep_firmware']	=$this->espb_path . $this->orepo->GetFirstStepFirmware();
			}
		}


		// git commands add a little delay, so only use then if needed
		if($this->action=='build' or ($this->action=='upload' and $this->flag_build)){
			$this->_SetCurrentVersionNames();
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _SetCurrentVersionNames(){
		$s	=$this->prefs['name_sep'];

		$this->c_host['versions']['file']		=$this->orepo->GetVersion();
		$version_short="";
		if($this->prefs['show_version']){
			$this->c_host['versions']['file'] 		and $version_short	.="{$s}v{$this->c_host['versions']['file']}";
		}

		$version_full='';
		if($this->prefs['show_version'] > 1){
			$this->c_host['versions']['branch']		=$this->orepo->GetBranch();
			$this->c_host['versions']['tag']		=$this->orepo->GetTag();
			$this->c_host['versions']['tag_commit']	=$this->orepo->GetTagCommit();
			$this->c_host['versions']['commit']		=$this->orepo->GetCommit();

			$v	=",";
			$version_full	.="{$this->c_host['versions']['branch']}";
			$this->c_host['versions']['tag']		and $version_full	.="{$v}{$this->c_host['versions']['tag']}";
			if($this->c_host['versions']['tag_commit'] != 	$this->c_host['versions']['commit']	){
				$this->c_host['versions']['commit']	and $version_full	.="{$v}#{$this->c_host['versions']['commit']}";
			}
			$this->c_host['versions']['full']	=$version_full;
			$version_full						="{$s}({$version_full})";
		}
		$esc_version_short	=str_replace('/','_',$esc_version_short);
		$esc_version_full	=str_replace('/','_',$version_full);
		$this->c_host['firmware_name']	="{$this->prefs['firm_name']}{$s}{$this->c_host['config']}{$esc_version_short}{$esc_version_full}";

	}


	// ---------------------------------------------------------------------------------------
	private function _ChooseValueToUse($name, $list='', $default=''){
		$tmp		= '';
		$arg_name	= "arg_$name";
		$arg_value	= $this->$arg_name;

		($list and	$tmp = $list[	$arg_value]				)	or
					$tmp =			$arg_value					or
		 ($list and	$tmp = $list[	$this->c_host[$name]]	)	or
					$tmp =			$this->c_host[$name]		or
		 ($list and $tmp = $list[	$this->c_conf[$name]]	)	or
					$tmp =			$this->c_conf[$name]		or
		 ($list and $tmp = $list[	$this->prefs[$name]]	)	or
					$tmp =			$this->prefs[$name]			or
					$tmp = $default ;
		return $tmp;
	}


	// ---------------------------------------------------------------------------------------
	private function _findConnectedSerialPorts(){
		$found=array();
		foreach ($this->serial_ports as $k => $port){
			// linux, osx
			if($this->os=='lin' or $this->os=='osx' ){
				if($matched= glob("{$port}*") ){
					foreach($matched as $i => $matched_port){
						if($matched > 1){
							$k_name=$k."". ($i+1);
						}
						else{
							$k_name=$k;
						}
						if(!in_array($matched_port, $found) ){
							$found[$k_name]=$matched_port;
						}
					}
				}
			}
			elseif($this->os=='win'){
//TODO: WINDOWS list Serials Ports
			}
		}

		if(count($found)){
			return $found;
		}
		return false;
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
		return new $class_name($repo_path);
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
	private function _rotateFirmware($new_firmware=''){
		$command_backup=array();
		$back_dir		= $this->c_host['path_dir_backup'];
		$firm_dir		= "{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}s/";
		$cur_firmware	="$firm_dir{$this->c_host['firmware_name']}.bin";
		$cur_firm_link	="$back_dir{$this->prefs['firm_name']}.bin";
		$prev_firm_link	="$back_dir{$this->prefs['firm_name']}_previous.bin";
		$path_build=$this->orepo->GetPathBuild();
		if(!is_dir($firm_dir)){
			@mkdir($firm_dir, 0777, true);
		}
		if($this->prefs['keep_previous']){
			$echo1="Keep the previous firmware, and a";
			//if(file_exists($cur_firm_link)){
				$command_backup[] = "mv -f \"$cur_firm_link\" \"$prev_firm_link\"";
			//}
			if($list_firmares=$this->_listFirmwares()){
				$i=1;
				krsort($list_firmares);
				foreach($list_firmares as $t => $file){
					if($i > $this->prefs['keep_previous'] +1 ){
						unlink($file);
					}
					$i++;
				}
			}
		}
		else{
			$echo1="A";
			if($cur_firm=@readlink($cur_firm_link)){
				$command_backup[] = "rm -f \"$cur_firm\"";
			}
		}
		if($new_firmware){
			$cur_firmware=$firm_dir.basename($new_firmware);
		}
		else{
			$new_firmware="{$path_build}.pio/build/{$this->c_conf['environment']}/firmware.bin";
		}

		$command_backup[] = "cp -p \"$new_firmware\" \"$cur_firmware\"";
		$command_backup[] = "ln -s \"$cur_firmware\" \"$cur_firm_link\"";
		$this->c_host['path_firmware']=$cur_firmware;


		if(count($command_backup)){
			$command=implode(" ; \n   ", $command_backup);
			echo "\n";
			$this->_EchoStepStart("{$echo1}rchive the new firmawre : {$this->c_host['firmware_name']} ", $command);
			if(! $this->flag_drymode){
				passthru($command, $r);
				return !$r;
			}
		}
		return true;
	}


	// ---------------------------------------------------------------------------------------
	private function _listFirmwares($all=false){
		$firm_dir			= "{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}s/";
		$mask				="{$firm_dir}{$this->prefs['firm_name']}*.bin";
		$all	and $mask	="{$firm_dir}*.bin";
		if($files=glob($mask) and count($files)){
			$time_files=array();
			foreach($files as $file){
				$time=filemtime($file);
				$time_files[$time]=$file;
			}
			krsort($time_files);
			return $time_files;
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _espbVersions(){
		$version="EspBuddby v{$this->class_version}";
		$tmp= @file_get_contents($this->cfg['paths']['bin_esptool']);
		if(preg_match('#__version__\s*=\s*"([^"]+)"#', $tmp,$m)){
			$version .= " ( EspTool v{$m[1]} )";
		}
		return $version;
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
				elseif	($v=='git_version')	{$str=str_replace('{{'.$v.'}}', $this->c_host['versions']['full'],	$str);}
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

		$this->arg_serial_port	= $this->args['vars']['port'];
		$this->arg_serial_rate	= $this->args['vars']['rate'];
		$this->arg_config		= $this->args['vars']['conf'];
		$this->arg_firmware		= $this->args['vars']['firm'];
		$this->arg_login		= $this->args['vars']['login'];
		$this->arg_pass			= $this->args['vars']['pass'];
		$this->arg_from			= $this->args['vars']['from'];
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
	    $tabs = "     ";
	    for($i=0;$i<$level; $i++){
	        $tabs .= "     ";
	    }
	    $tabs .= "  - ";
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
		echo " ********";
		$this->_EchoStepEnd();
	}


	// ---------------------------------------------------------------------------------------
	private function _WaitPingable($host,$timeout=60){
		$this->_EchoStepStart("Waiting for ESP to be back online",'',0);
		if($this->flag_drymode){
			$out=true;
		}
		else{
			$i=1;
			while($i <= $timeout){
				if($this->_ping($host)){
					$out=true;
					break;
				}
				echo "$i ";
				$i++;
				sleep(1);
			}
		}
		echo " **********";
		$this->_EchoStepEnd();
		return $out;
	}


	// ---------------------------------------------------------------------------------------
	function _ping ($host) {
		$command ="ping -q -c1 -t1 $host "; // > /dev/null 2>&1
		exec($command, $output, $r);
	    return ! $r;
	}


	// ---------------------------------------------------------------------------------------
	private function _EchoStepStart($mess, $command="", $do_end=1,$char="*"){
		if($this->flag_verbose){
			$verbose=true;
		}
		if($verbose) echo "\n";
		$mess	="$char$char $mess ";
		if($do_end){
			$mess=str_pad($mess, 130, $char);
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
