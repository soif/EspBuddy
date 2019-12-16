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

	public $class_version			= '1.89.10b';					// EspBuddy Version
	public $class_gh_owner			= 'soif';						// Github Owner
	public $class_gh_repo			= 'EspBuddy';					// Github Repository
	public $class_gh_branch_main	= 'master';						// Github Master Branch
	public $class_gh_branch_dev		= 'develop';					// Github Develop Branch
	public $class_gh_api_url		= 'https://api.github.com';		// Github API URL
	public $app_name				= 'EspBuddy';					// Application Name

	private $cfg				= array();	// hold the configuration
	private $espb_path			= '';		// Location of the EspBuddy root directory
	private $espb_path_lib		= '';		// Location of the EspBuddy lib directory

	private $sh					;			//	shell object

	// command lines arguments
	private $args				= array();	// command line arguments
	private $bin				= '';		// binary name of the invoked command
	private $action				= '';		// command line action
	private $target				= '';		// command line target

	// command lines flags
	private $flag_noconfirm		= false;
	private $flag_drymode		= false;
	private $flag_verbose		= false;
	private $flag_force			= false;
	
	private $flag_build			= false;
	private $flag_serial		= false;
	private $flag_eraseflash	= false;
	private $flag_skipinter		= false;
	private $flag_prevfirm		= false;
	
	private $flag_json			= false;

	// command lines variables
	private $arg_serial_port	= '';
	private $arg_serial_rate	= 0;
	private $arg_config			= '';
	private $arg_firmware		= '';
	private $arg_login			= '';
	private $arg_pass			= '';
	private $arg_from			= '';	// repo to migrate from

	//selected configuration for the current host
	private $c_host		=array();		//	current host
	private $c_conf		=array();		//	current config
	//private $c_repo		=array();	//	current repository
	private $c_serial	=array();		//	current serial port and rate

	private $orepo	;					//	repo_object


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

	// Action Help Texts ------------------------------------------------------------------------
	private	$actions_desc=array(
		'root'=>array(
				'upload'		=> "Build and/or Upload current repo version to Device(s)",
				'build'			=> "Build firmware for the selected device",
				'backup'		=> "Download and archive settings from the remote device",
				'monitor'		=> "Monitor device connected to the serial port",
				'version'		=> "Show remote device version",
				'reboot'		=> "Reboot Device(s)",
				'gpios'			=> "Test all Device's GPIOs",
				'ping'			=> "Ping Device(s)",
				'sonodiy'		=> "Discover, Control or Flash Sonoff devices in DIY mode",
				'repo_version'	=> "Parse the current repository (REPO) version. REPO is a supported repository (espurna, espeasy or tasmota)",
				'repo_pull'		=> "Git Pull the local repository (REPO). REPO is a supported repository (espurna, espeasy or tasmota)",
				'list_hosts'	=> "List all hosts defined in config.php",
				'list_configs'	=> "List all available configurations, defined in config.php",
				'list_repos'	=> "List all available repositories, defined in config.php",
				'self'			=> "Get current, latest or update EspBuddy version",
				'help'			=> "Show full help"
		),
		'sonodiy'		=> array(
			'help'		=>	'Show Sonoff DIY Help',
			'scan'		=>	'Scan Sonoff devices to find their IP & deviceID',
			'test'		=>	'Toggle relay to verify communication',
			'flash'		=>	'Upload a Tasmota firmware (508KB max, DOUT mode)',
			'ping'		=>	'Check if device is Online',
			'info'		=>	'Get Device Info',
			'pulse'		=>	'Set Inching (pulse) mode (0=off, 1=on) and width (in ms, 500ms step only)',
			'signal'	=>	'Get WiFi Signal Strength',
			'startup'	=>	'Set the Power On State (0=off, 1=on, 2=stay)',
			'switch'	=>	'Set Relay (0=off, 1=on)',
			'toggle'	=>	'Toggle Relay between ON and OFF',
			'unlock'	=>	'Unlock OTA mode',
			'wifi'		=>	'Set WiFi SSID and Password',
		),
		'self'		=> array(
			'version'	=> "Show EspBuddy version",
			'latest'	=> 'Show the lastest version available',
			'log'		=> '(DRAFT) Show EspBuddy history between current tag and TAG (latest if not set)',
			'avail'		=> 'Show all versions available',
			'update'	=> 'Update EspBuddy to the latest version',
		)
	);

	// Command Usages Texts ------------------------------------------------------------------------
	private	$actions_usage=array(
		'root'=>array(
				'upload'		=> "[TARGET] [options, auth_options, upload_options]",
				'build'			=> "[TARGET] [options]",
				'backup'		=> "[TARGET] [options, auth_options]",
				'monitor'		=> "[TARGET] [options]",
				'version'		=> "[TARGET] [options]",
				'reboot'		=> "[TARGET] [options]",
				'gpios'			=> "[TARGET] [options]",
				'ping'			=> "[TARGET] [options]",
				'sonodiy'		=> "ACTION [options]",
				'repo_version'	=> "REPO",
				'repo_pull'		=> "REPO",
				'list_hosts'	=> "",
				'list_configs'	=> "",
				'list_repos'	=> "",
				'self'			=> "ACTION [options]",
				'help'			=> ""
		),
		'sonodiy'		=> array(
			'help'		=>	'',
			'scan'		=>	'',
			'test'		=>	'IP ID',
			'flash'		=>	'IP ID [URL] [SHA256SUM]',
			'ping'		=>  "IP [COUNT]",
			'info'		=>	'',
			'pulse'		=>	'[MODE] [WIDTH]',
			'signal'	=>	'',
			'startup'	=>	'[STATE]',
			'switch'	=>	'[STATE]',
			'toggle'	=>	'STATE',
			'unlock'	=>	'',
			'wifi'		=>	'SSID [PASSWORD]',
		),
		'self'		=> array(
			'version'	=> '',
			'latest'	=> '',
			'log'		=> '[TAG]',
			'avail'		=> '',
			'update'	=> '[TAG|VERSION|BRANCH]',
		)
	);


	// ##################################################################################################################################


	// ---------------------------------------------------------------------------------------
	function __construct(){
		$this->espb_path		=dirname(dirname(__FILE__)).'/';
		$this->espb_path_lib	=$this->espb_path.'lib/';
		$this->_SetRunningOS();

		require_once($this->espb_path_lib."espb_shell.php");
		$this->sh =new EspBuddy_Shell(); 
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

			case 'sonodiy':
				$this->Command_sonodiy();
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
			case 'self':
				$this->Command_self();
				break;
			case 'help':
				$this->Command_help();
				break;

			default:
				$this->action and $com=" '{$this->action}'";
				echo "\n";
				$this->sh->PrintError("Invalid$com Command");		
				echo "\n";
				$this->_show_command_usage();
				$this->_show_action_desc();
				//global $argv;
				echo "* Use '{$this->bin} help' to list all options\n";
				//echo "\n";
				break;
		}
		echo "\n";
		exit(0);
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
		echo "\n";
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

		// .wire mode ------------------
		if($this->flag_serial){
			if($this->flag_eraseflash){
				$this->_DoSerial($id,'erase_flash');
				$this->_WaitReboot(5);
			}
			$this->_DoSerial($id,'write_flash', $firmware);
		}

		// .OTA mode ------------------
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
				sleep(2); // give it some more time to be ready

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
			$this->_Git('git pull');
			$this->Command_repo('version');
		}
		elseif($type == 'checkout'){
// TODO: Checkout Git
			//$branch	= $this->c_host['checkout'] or $branch = 'master';
			//$this->_Git("git checkout {$branch}");
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
	public function Command_help($action='root'){
		$action or $action='root';
		if($action=='root'){
			echo $this->_getVersionBuddyLong();
			echo "\n\n";	
		}
		$this->_show_command_usage($action);
		$this->_show_action_desc($action);
		$this->_show_action_usage($action);
		if($action=='root'){
			echo <<<EOF
---------------------------------------------------------------------------------
* OPTIONS :
	-y  : Automatically confirm Yes/No
	-d  : Dry Run. Show commands but don't apply them
	-v  : Verbose mode

* UPLOAD_OPTIONS :
	-b           : Build before Uploading
	-w           : Wire Mode : Upload using the Serial port instead of the default OTA
	-e           : In Wire Mode, erase flash first, then upload
	-p           : Upload previous firmware backuped, instead of the latest built
	-s           : Skip Intermediate Upload (if set)

	--port=xxx   : serial port to use (override main or per host serial port)
	--rate=xxx   : serial port speed to use (override main or per host serial port)
	--conf=xxx   : config to use (override per host config)
	--firm=xxx   : full path to the firmware file to upload (override latest build one)
	--from=REPO  : migrate from REPO to the selected config

* AUTH_OPTIONS :
	--login=xxx  : login name (override host or per config login)
	--pass=xxx   : password (override host or per config password)

EOF;
			//$this->_show_action_desc('sonodiy','sonodiy ACTIONS');
		}
	}


	// ---------------------------------------------------------------------------------------
	public function Command_self(){

		if($this->target=='help'){
			$this->Command_help('self');
		}
		elseif($this->target=='version'){
			echo "Current version: {$this->class_version}\n";
		}
		elseif($this->target=='latest'){
			$tag=$this->_GithubFetchLatestTag();
			echo "Latest version: {$tag['version']}\n";
		}
		elseif($this->target=='log'){
			$this->_Git_GitHistory($this->espb_path,$this->args['commands'][3]);
		}
		elseif($this->target=='avail'){
			$tags=$this->_GithubFetchLatestTags();
			echo "Versions available : \n";
			$p=10;
			echo "    ". str_pad('TAG', $p). str_pad('Version', $p). str_pad('Branch', $p). str_pad('Commit', 20)."\n";
			foreach ($tags as $branch => $rows){
				foreach ($rows as $v){
					echo "  - ". str_pad($v['tag'], $p). str_pad($v['version'], $p). str_pad($v['branch'], $p). str_pad($v['commit'], $p)."\n";					
				}
			}
		}
		elseif($this->target=='update'){
			$tags=$this->_GithubFetchLatestTags();

			$arg=$this->args['commands'][3];
			if($arg=='dev'){
				$arg=$this->class_gh_branch_dev;
			}

			$arg or $tag=current($tags[$this->class_gh_branch_main]); //latest tags
				
			$tag or	$tag=current($tags[$arg])
				or	$tag=$this->_GithubVersionToTag($arg, true)
				or	$tag=$tags[$this->class_gh_branch_main][$arg]
				or	$tag=$tags[$this->class_gh_branch_dev][$arg]
				or	$this->_dieError("Can't find a tag or branch named '$arg' ");

			if($tag['version']==$this->class_version and !$this->flag_force){
				$this->sh->PrintAnswer("You're already running this version!");
			}
			else{
				echo "Update Espbuddy from {$this->class_version} to {$tag['version']} (tag '{$tag['tag']}' on '{$tag['branch']}' branch).\n";
				if($ok=$this->_AskYesNo("This will replace your current '{$tag['branch']}' branch! Are you sure")){
					$this->sh->PrintAnswer("Updating to version {$tag['version']} ...");
					$this->_GitSwitchToBranchTag($this->espb_path, $tag['tag'], $tag['branch']);
				}
				else{
					$this->sh->PrintAnswer("Canceled!");
				}

			}
		}
		else{
			$this->_showActionUsage();			
		}

	}

	// ---------------------------------------------------------------------------------------
	public function Command_sonodiy(){

		if($this->target=='help'){
			$this->Sonodiy_help();
		}
		elseif($this->target=='ping'){
			$this->Sonodiy_ping($this->args['commands'][3], $this->args['commands'][4]);
			echo "\n";
		}
		elseif($this->target=='scan'){
			$this->Sonodiy_scan();
		}
		elseif($this->target=='test'){
			$this->Sonodiy_test($this->args['commands'][3],$this->args['commands'][4]);
		}
		elseif($this->target){
			if(in_array($this->target,array_keys($this->actions_desc[$this->action]))){
				$device_ip		=$this->args['commands'][3];
				$device_id		=$this->args['commands'][4];
				$device_param1	=$this->args['commands'][5];
				$device_param2	=$this->args['commands'][6];
				$this->Sonodiy_api($this->target,$device_ip, $device_id,$device_param1,$device_param2);	
			}
			else{
				$error="Invalid Action: '{$this->target}'";
			}
		}
		else{
			$error="Missing a '{$this->action}' Action";
		}
		if($error){
			$this->_showActionUsage($error);
		}
		exit(0);

	}
	// ---------------------------------------------------------------------------------------
	public 	function _showActionUsage($error=""){
		$error or $error="Invalid Action: '{$this->target}'";
		echo "\n";
		$this->sh->PrintError($error);
		echo "\n";
		$this->_show_command_usage($this->action);
		$this->_show_action_desc($this->action);
		echo "* Use '{$this->bin} {$this->action} help' for all options\n";
		echo "\n";
		exit(1);
	}

	// ---------------------------------------------------------------------------------------
	public 	function Sonodiy_test($ip, $id){
		$this->Sonodiy_ping($ip,5);
		$this->sh->PrintAnswer("Toggling Relay: ",false);
		if($r=$this->_sonodiy_api_toggle($ip,$id,0)){
			echo "OK (did you heard it?)";
		}
		else{
			echo "FAILED";
		}
		echo "\n";
		$this->sh->PrintAnswer("API response	: ");
		print_r($this->_sonodiy_api_info($ip,$id));
	}


	// ---------------------------------------------------------------------------------------
	public 	function Sonodiy_help(){
		$this->Command_help('sonodiy');
		echo <<<EOF
---------------------------------------------------------------------------------
Setup Instructions
---------------------------------------------------------------------------------
  1) Setup an access point in your network named "sonoffDiy" with password "20170618sn"
  2) Set the OTA/DIY jumper in your Sonoff Device, and power it On.
  3) Run '{$this->bin} sonodiy scan'        to find your device IP & ID
  4) Run '{$this->bin} sonodiy test  IP ID' to toggle the relay on the device (verification)
  5) Run '{$this->bin} sonodiy flash IP ID' to upload another firmware (Tasmota by default)
  6) Enjoy!

EOF;
	}


	// ---------------------------------------------------------------------------------------
	public 	function Sonodiy_ping($ip, $count=1){
		$count =intval($count);
		$count or $count=1;
		if(!$ip){
			$this->_dieError("Missing IP");
		}
		$this->sh->PrintAnswer("Sending $count pings to $ip : ", false);
		if($this->flag_verbose){
			echo "\n";
		}
		$r=0;
		for ($i=0; $i < $count ; $i++) { 
			$x=$i+1;
			$state ='not OK!';
			if($bool = $this->_ping($ip) ){
				$r++;
			}
			$bool and $state="OK";
			if($this->flag_verbose){
				echo " $x	: $state\n";
			}
			if($bool and $x < $count){
				sleep(1);
			}
	}
		if($x==$r){
			echo "OK!\n";
		}
		else{
			echo "I've received $r answers out of $count requests.\n";
		}
	}


	// ---------------------------------------------------------------------------------------
	public 	function Sonodiy_scan(){
		$service="_ewelink._tcp";

		if($this->os == "lin"){
			$command="avahi-browse -t _ewelink._tcp  --resolve";
			echo "Scanning network for Devices using command:	$command\n";
			$raw=trim(shell_exec($command));
			$raw_example=<<<EOF
+   eth0 IPv4 eWeLink_1000aba1ee                            _ewelink._tcp        local
=   eth0 IPv4 eWeLink_1000aba1ee                            _ewelink._tcp        local
	hostname = [eWeLink_1000aba1ee.local]
	address = [10.1.250.154]
	port = [8081]
	txt = ["data1={"switch":"off","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1500,"rssi":-63}" "seq=96" "apivers=1" "type=diy_plug" "id=1000aba1ee" "txtvers=1"]


EOF;
			if($raw){
				//echo $raw_example;
				$lines=explode("\n",$raw);
				$i=0;
				foreach($lines as $line){
					if(preg_match('#IPv4\s*?([^\s]+)#',$line,$match)){
						$ids[$i]=$match[1];
					}
					if(preg_match('#address\s*?=\s*?\[([^\]]+)\]#',$line,$match)){
						$ips[$i]=$match[1];
					}
					if(preg_match('#port\s*?=\s*?\[([^\]]+)\]#',$line,$match)){
						$ports[$i]=$match[1];
						$i++;
					}
				}
				
				if($ids){
					$this->sh->PrintAnswer("Devices Found:");
					$pad=15;
					echo str_pad("ID",$pad+8).str_pad("IP",$pad).str_pad("PORT",$pad)."\n";
					echo str_repeat('-',45)."\n";
					foreach($ids as $k => $id){
						echo "".str_pad($id,$pad+8).str_pad($ips[$k],$pad).str_pad($ports[$k],$pad)."\n";
					}
					echo str_repeat('-',45)."\n";
					$found_args="{$ips[0]} {$ids[0]}";	
				}
				else{
					$this->sh->PrintAnswer("Sorry, I did not found any device!");
				}
			}
		}
		elseif($this->os == "osx"){
			$command="dns-sd -B $service";

			echo "Scanning network for Devices using command:	$command\n";
			$bash=$this->_sondiy_osx_com2bash($command,5);
			$lines_ids=trim(shell_exec($bash));
			if($lines_ids){
				$this->sh->PrintAnswer( "Device IDs Found:");
				$lines=explode("\n",$lines_ids);
				foreach($lines as $line){
					list($trash,$raw_id)=explode($service.'.' , $line);
					$ids[]=trim(str_replace('eWeLink_','',$raw_id));
				}
				echo "   - ". implode("\n   - ",$ids);
				echo "\n\n";

				$first_id='eWeLink_'.$ids[0];
				$command="dns-sd -q $first_id.local";
				echo "Resolving IP Address for the first device found ({$ids[0]}) using command:	$command\n";
				$command=$this->_sondiy_osx_com2bash($command,4);
				$lines_ip=trim(shell_exec($command));
				if($lines_ip){
					list($line_ip,$trash)=explode("\n" , $lines_ip);
					list($trash,$raw_ip)=explode('IN' , $line_ip);
					$ip=trim($raw_ip);
					$this->sh->PrintAnswer( "Device IP Address is: $ip");
					$found_args="$ip {$ids[0]}";
				}
				else{
					$this->sh->PrintAnswer( "Sorry, I could not resolve the IP Address!");
				}
				// dns-sd -L  $first_id _ewelink._tcp local
			}
			else{
				$this->sh->PrintAnswer( "Sorry, I did not found any device.");
			}
		}
		elseif($this->os == "win"){ //windows
			$command="python {$this->cfg['paths']['bin']}mdns.py";
			echo "Scanning network for Devices using command:	$command\n";
			$raw=trim(shell_exec($command));
			$raw_example=<<<EOF
inter add_service()
1000aba1ee  10.1.250.154  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
1000aba1ee  10.1.250.154  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
1000aba1ee  10.1.250.154  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
			
EOF;
			if($raw){
				//echo $raw_example;
				$lines=explode("\n",$raw);
				$first_line=$lines[0];
				unset($lines[0]);
				foreach($lines as $line){
					if(preg_match('#^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+.*?diy_plug#',$line,$match)){
						$ids[$match[1]]		=$match[1];
						$ips[$match[1]]		=$match[2];
						$ports[$match[1]]	=$match[3];
					}
				}
				
				if($ids){
					$this->sh->PrintAnswer("Devices Found:");
					$pad=15;
					echo str_pad("ID",$pad+8).str_pad("IP",$pad).str_pad("PORT",$pad)."\n";
					echo str_repeat('-',45)."\n";
					$i=0;
					foreach($ids as $k => $id){
						echo "".str_pad($id,$pad+8).str_pad($ips[$k],$pad).str_pad($ports[$k],$pad)."\n";
						//reassign to num array
						$ids[$i]	=$ids[$k];
						$ips[$i]	=$ips[$k];
						$ports[$i]	=$ports[$k];
						$i++;
					}
					echo str_repeat('-',45)."\n";
					$found_args="{$ips[0]} {$ids[0]}";	
				}
				else{
					if(preg_match('#add_service#',$first_line)){
						$this->sh->PrintAnswer( "Sorry, I did not found any device!");
					}
					else{
						$crashed=1;
					}
				}
			}
			else{
				$crashed=1;
			}

			if($crashed){
				echo "\n";
				$this->sh->PrintError("The python script has certainly crashed");
				echo <<<EOF

It seems that you dont have a working Python installation!
Please follows these steps;
  1) Install Python v3.xx , bundled with pip.
  2) From the command line, type: "python --version" (to verify the version)
  3) From the command line; type: "pip install zeroconf PySide2"
Then try again!

(WINDOWS SUPPORT IS STILL EXPERIMENTAL !)

EOF;
				
			}
		}

		if($found_args){
			echo "\nYou can now use: \"$found_args\" as arguments for sonodiy Actions!\nie:\n";
			echo "  {$this->bin} sonodiy test  $found_args\n";
			echo "  {$this->bin} sonodiy flash $found_args\n";
		}
		echo "\n";

	}


	// ---------------------------------------------------------------------------------------
	private function _sondiy_osx_com2bash($command,$skip){
		//https://github.com/pstadler/non-terminating-bash-processes/blob/master/README.md
		$bash=<<<EOFB
bash <<'END'
trap '{
	if [ -z "\$out" ]; then
		#echo "-->No Sonoff device found."
		exit 0
	fi
	printf "%s\n" "\${out[@]}"
	#echo "-->\${#out[@]} host(s) found."
}' EXIT

out=(); i=0
while read -r line; do
	i=`expr \$i + 1`
	if [ \$i -lt $skip ]; then continue; fi
	out+=("\$line")
	if [ $(echo \$line | cut -d ' ' -f 3) -ne '3' ]; then
		break
	fi
done < <((sleep 0.5; pgrep -q dns-sd && kill -13 \$(pgrep dns-sd)) &
			$command)
pgrep -q dns-sd && kill -13 \$(pgrep dns-sd)
exit 0
END
EOFB;
		return $bash;
	}


	// ---------------------------------------------------------------------------------------
	private $_itead_error_codes=array(
		400	=> "The request was formatted incorrectly. The request body is not a valid JSON format.",
		401	=> "The request was unauthorized. Device information encryption is enabled on the device, but the request is not encrypted.",
		404	=> "The device does not exist. The device does not support the requested deviceid.",
		422	=> "The request parameters are invalid. For example, the device does not support setting specific device information.",
		403	=> "The OTA function was not unlocked. The interface '3.2.6OTA function unlocking' must be successfully called first",
		408	=> "The pre-download firmware timed out. You can try to call this interface again after optimizing the network environment or increasing the network speed.",
		413	=> "The request body size is too large. The size of the new OTA firmware exceeds the firmware size limit allowed by the device.",
		424	=> "The firmware could not be downloaded. The URL address is unreachable (IP address is unreachable, HTTP protocol is unreachable, firmware does not exist, server does not support Range request header, etc.)",
		471	=> "The firmware integrity check failed. The SHA256 checksum of the downloaded new firmware does not match the value of the request body's sha256sum field. Restarting the device will cause bricking issue.",
	);

	public 	function Sonodiy_api($task, $ip, $id, $param1='', $param2=''){
		if(!$ip){
			$this->_dieError("Missing IP");
		}
		if(!$id){
			$this->_dieError("Missing ID");
		}
		$fn="_sonodiy_api_$task";
		if(method_exists($this, $fn)){
			$result		=$this->$fn($ip, $id, $param1, $param2);
			$curl_req	=$this->_last_curl_request;
			$curl_url	=$this->_last_curl_url;
			$curl_res	=$this->_last_curl_result;
			
			if(!$result){
				echo "\n";
				$this->sh->PrintError("API failed with error code: {$curl_res['error']}");
				echo "\n";
				echo "From : https://github.com/itead/Sonoff_Devices_DIY_Tools/blob/master/SONOFF%20DIY%20MODE%20Protocol%20Doc%20v1.4.md \n";
				echo " {$curl_res['error']} : \"".$this->_itead_error_codes[$curl_res['error']]."\"\n";
				echo "\n";
			}

			if($this->flag_verbose){
				$info=$this->_sonodiy_api_info($ip,$id);
				if(is_array($result['data'])){
					$info['data']=array_merge($result['data'],$info['data']);
				}
				
				if(!$result or $this->flag_drymode){
					echo "--- URL requested ---------------------------\n";
					echo "$curl_url\n";
					echo "\n--- Request Sent: ---------------------------\n";
					print_r($curl_req);
					echo "\n--- Response Received: ----------------------\n";
					print_r($curl_res);
				}
				echo "\n--- Last Information Data Received: ---------\n";
				print_r($info['data']);
				echo "\n";
			}
			
			if($this->flag_json){
				$info or $info=$this->_sonodiy_api_info($ip,$id);
				$curl_info['data']=$info['data'];
				if($this->flag_verbose){
					echo "JSON: ";
				}
				echo json_encode($curl_info)."\n";
			}
			
			if(! $result){
				exit(1);
			}
		}
		else{
			$this->_dieError("No method for action: '$task'");
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_info($ip, $id){
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array()
		);
		return $this->_sondiy_curl($ip,'info',$data);
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_flash($ip, $id,$url,$sha256=''){
		$url or $url=$this->cfg['sonodiy']['firmware_url'];
		// 508KB max, DOUT mode
		if(!$url){
			$this->_dieError( "Missing Firmaware URL");
		}

		$data	=file_get_contents($url);
		$size	=strlen(bin2hex($data))/2;
		$size_k	=$size/1024;
		$sha256 or $sha256=hash('sha256',$data);
		$size_k_round=ceil($size_k);
		echo "Firmware to upload: \n";
		echo " - URL    : $url\n";
		echo " - sha256 : $sha256\n";
		echo " - Size   : {$size_k_round} kB ($size bytes)\n";
		if($size_k >= 508 ){
			$this->_dieError("Size is more than 508 kB. Please use a smaller firmware");
		}
		elseif($size_k < 100 ){
			$this->_dieError("Size is less than 108 kB, this seems strange");
		}
		echo "\n";
		$ok=$this->_AskConfirm();
		
		if(!$ok){
			$this->sh->PrintAnswer( "Cancelling...");
			echo "\n";
			exit(0);
		}
		$this->sh->PrintAnswer( "Uploading...");
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array(
				'downloadUrl'	=> $url,
				'sha256sum'		=> $sha256
			)
		);
		if($r=$this->_sondiy_curl($ip,'ota_flash',$data)){
			$this->_WaitPingable($ip, 60, true);
			$this->_WaitPingable($ip, 3);
			echo "Finished!\n";
			return $r;
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_pulse($ip, $id, $state_bool=0, $width=1000){
		$state='off';
		$state_bool and $state="on";
		$width=intval($width);

		$data=array(
			'deviceid'	=> $id,
			'data'		=> array(
				'pulse'			=> $state
			)
		);
		if($width){
//TODO Check that $width is a multiples of 500
			$data['data']['pulseWidth']=$width;
		}
		return $this->_sondiy_curl($ip,'pulse',$data);
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_signal($ip, $id){
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array()
		);
		return $this->_sondiy_curl($ip,'signal_strength',$data);
	}
	

	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_startup($ip, $id, $state_num=0){
		if(!$state_num){
			$state='off';
		}
		elseif($state_num==1){
			$state='on';
		}
		elseif($state_num==2){
			$state='stay';
		}
		else{
			return;
		}
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array(
				'startup'	=> $state
			)
		);
		return $this->_sondiy_curl($ip,'startup',$data);
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_switch($ip, $id, $state_bool=0){
		$state='off';
		$state_bool and $state="on";
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array(
				'switch'	=> $state
			)
		);
		return $this->_sondiy_curl($ip,'switch',$data);
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_toggle($ip, $id){
		if($info=$this->_sonodiy_api_info($ip,$id)){
			$state=1;
			if($info['data']['switch']=='on'){
				$state=0;
			}
			return $this->_sonodiy_api_switch($ip,$id,$state);
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_unlock($ip, $id){
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array()
		);
		return $this->_sondiy_curl($ip,'ota_unlock',$data);
	}


	// ---------------------------------------------------------------------------------------
	private function _sonodiy_api_wifi($ip, $id, $ssid, $pass){
		if(!$ssid){			
			$this->_dieError( "Missing SSID");
		}
		if(!$pass){
			if(!$this->flag_force){
				echo "\n";
				$this->sh->PrintError("Missing Password");
				echo "Use '-f', if you want to set a blank password.\n\n";
				exit(1);					
			}
			else{
				$pass="";
				if($this->flag_verbose){
					echo "Setting Wifi to SSID=$ssid with NO password!\n";
				}
			}
		}
		else{
			if($this->flag_verbose){
				echo "Setting Wifi to SSID=\"$ssid\" , Password=\"$pass\"\n";
			}
		}
		$data=array(
			'deviceid'	=> $id,
			'data'		=> array(
				'ssid'			=> $ssid,
				'password'		=> $pass
			)
		);
		return $this->_sondiy_curl($ip,'wifi',$data);
	}


	// ---------------------------------------------------------------------------------------
	private $_last_curl_request;
	private $_last_curl_url;
	private $_last_curl_result;
	private function _sondiy_curl($ip, $endpoint, $data){
		//https://github.com/itead/Sonoff_Devices_DIY_Tools/blob/master/SONOFF%20DIY%20MODE%20Protocol%20Doc%20v1.4.md

		$json=json_encode($data);
		$url="http://$ip:8081/zeroconf/$endpoint";
		
		$this->_last_curl_request	=$data;
		$this->_last_curl_url		=$url;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($json))
		);
		if($this->flag_drymode){
			curl_close($ch);
			$out['error']=0;
			$out['data']['dryMode']="No Datas in Dry mode";
		}
		else{
			$result 	= curl_exec($ch);
			curl_close($ch);
			$out 		= @json_decode($result,true);	
			$out['data']=@json_decode($out['data'],true);
		}
		$this->_last_curl_result=$out;
		if($out['error']==0){
			return $out;
		}
	}



	// ##################################################################################################################################
	// ##### PRIVATE ####################################################################################################################
	// ##################################################################################################################################


	// ---------------------------------------------------------------------------------------
	public function _show_action_desc($action='root',$title=""){
		if($action=='root'){
			$name="Valid COMMANDS";
		}
		else{
			$name="Valid '$action' Actions";
		}
		$title or $title="$name";
		if($this->actions_desc[$action]){
			echo "* $title : \n";
			foreach($this->actions_desc[$action] as $k => $v){
				echo "  - ".str_pad($k,15)." : $v\n";
			}
			echo "\n";
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _show_action_usage($action='root',$title=""){
		if($action=='root'){
			$pad_sub=12;
			$name="Commands";
		}
		else{
			$pad_sub=6;
			$name="'$action' Actions";
			$sub="$action ";
		}
		$title or $title="$name Usage";
		if($this->actions_usage[$action]){
			echo "* $title : \n";
			foreach($this->actions_usage[$action] as $k => $v){
				echo "  - ".str_pad($k,15)." : {$this->bin} $sub".str_pad($k,$pad_sub)." $v\n";
			}
			echo "\n";
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _show_command_usage($action='root'){
		
		if($action !='root'){
			$usage="$action ". $this->actions_usage['root'][$action];
		}
		else{
			$usage="COMMAND [TARGET] [options]";
		}
		echo "* Usage             : {$this->bin} $usage\n";
		echo "\n";
	}


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

		if		($os_id=='win'){$os='win';}	// windows
		elseif	($os_id=='dar'){$os='osx';}	// darwin = OSX
		elseif	($os_id=='lin'){$os='lin';}	// linux
		$this->os = $os;
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
			if($host['serial_port']){
				echo "       + Serial    : {$host['serial_port']}	at {$host['serial_rate']} bauds\n";
			}
			echo "\nSelected Config    : {$this->c_host['config']}\n";
			if($this->flag_verbose){
				echo "\033[37m";
				echo "       Parameters : \n";
				$this->_Prettyfy($this->cfg['configs'][$host['config']]);
				echo "\033[0m";
			}
		}

		// confirm -------
		if(!$force_selected){
			echo "\n";
			if(!$this->_AskConfirm()){
				$this->sh->PrintAnswer("Cancelled!");
				exit(0);
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
		//TODO check $esc_version_short
		$esc_version_short="";
		//$esc_version_short	=str_replace('/','_',$version_short);
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
//WINDOWS TODO: list Serials Ports
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
		$class_path	= $this->espb_path_lib."espb_repo_{$name}.php";
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
	private function _getVersionBuddyLong(){
		$version="EspBuddby v{$this->class_version}";
		
		$esptool_ve=$this->_getVersionEsptool();
		$esptool_ve and $version .=" ( EspTool v{$esptool_ve} )";
		
		return $version;
	}

	// ---------------------------------------------------------------------------------------
	private function _getVersionEsptool(){
		$tmp= @file_get_contents($this->cfg['paths']['bin_esptool']);
		if(preg_match('#__version__\s*=\s*"([^"]+)"#', $tmp,$m)){
			return $m[1];
		}
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
	private function _AskConfirm(){
		if($this->flag_noconfirm){
			return true;
		}
		$confirm=$this->_AskYesNo("Please Confirm");
		//echo "\n";
		return $confirm;
	}
	

	// ---------------------------------------------------------------------------------------
	private function _AskYesNo($question='Are you sure', $allow_noconfirm = true){
		$force='';
		if($this->flag_noconfirm and $allow_noconfirm){
			$force='y';
		}
		$confirm=strtolower($this->_Ask("Yes,No",$force,", ","? ", $question));
		if($confirm=='y'){
			return true;
		}	
	}


	// ---------------------------------------------------------------------------------------
	//http://stackoverflow.com/questions/3684367/php-cli-how-to-read-a-single-character-of-input-from-the-tty-without-waiting-f
	private function _Ask($str_choices='', $force='', $sep="\n ", $eol="\n",$message=""){
		if($force  and !$this->flag_verbose){
			return $force;
		}

		if($message){
			$this->sh->PrintQuestion( "$message : ", false);
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
		if($force and $this->flag_verbose){
			echo $force;
			if($message){
				echo "\n";
			}
			return $force;
		}
// WINDOWS TODO - check compatibility 
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
		if($message){
			echo "\n";
		}
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
		$this->flag_noconfirm	= (boolean) $this->args['flags']['y'];
		$this->flag_drymode 	= (boolean) $this->args['flags']['d'];
		$this->flag_verbose		= (boolean) $this->args['flags']['v'];
		$this->flag_force		= (boolean) $this->args['flags']['f'];

		$this->flag_build		= (boolean) $this->args['flags']['b'];
		$this->flag_prevfirm	= (boolean) $this->args['flags']['p'];
		$this->flag_serial		= (boolean) $this->args['flags']['w'];
		$this->flag_eraseflash	= (boolean) $this->args['flags']['e'];
		$this->flag_skipinter	= (boolean) $this->args['flags']['s'];
		$this->flag_json		= (boolean) $this->args['flags']['j'];

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
	private function _WaitPingable($host,$timeout=60,$invert=false){
		$message="Waiting for ESP to be back online";
		if($invert){
			$message="Waiting for ESP to be offline (reboot)";
		}
		$this->_EchoStepStart($message,'',0);
		if($this->flag_drymode){
			$out=true;
		}
		else{
			$i=1;
			while($i <= $timeout){
				$state=$this->_ping($host);
				$bool=$state;
				$invert and $bool = ! $bool;
				if($bool){
					$out=true;
					break;
				}
				echo "$i ";
				if($state and $i < $timeout){
					sleep(1);
				}
				$i++;
			}
		}
		echo " **********";
		$this->_EchoStepEnd();
		return $out;
	}

	// ---------------------------------------------------------------------------------------
	function _ping ($host) {
		//$command ="ping -q -c1 -t1 $host "; // > /dev/null 2>&1
		//exec($command, $output, $r);
		//return ! $r;
		//https://stackoverflow.com/questions/8030789/pinging-an-ip-address-using-php-and-echoing-the-result
		if($this->os=='win'){
			if (!exec("ping -n 1 -w 1 $host 2>NUL > NUL && (echo 0) || (echo 1)")){
				return true;
			}	
		}
		else{
			if (!exec("ping -q -c1 -t1 $host >/dev/null 2>&1 ; echo $?")){
				return true;
			}
		}
		return false;
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
		$this->sh->EchoStyleStep();
		echo $mess;
		if($verbose and $command){
			$this->sh->EchoStyleClose();
			echo "\n";
			$this->sh->PrintCommand("$command");
		}
		else{
			$do_end and $this->_EchoStepEnd();
		}
	}


	// ---------------------------------------------------------------------------------------
	private function _EchoStepEnd(){
		$this->sh->EchoStyleClose();
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	private function _dieError($mess,$list=''){
		echo "\n";
		$this->sh->PrintError(' FATAL ERROR: '.$mess);
		echo "\n";
/*
		if($list){
			echo "\n";
			$this->command_list($list);
		}
		else{
			echo "\n";
		}
*/
		exit(1);
	}

	// ---------------------------------------------------------------------------------------
	private function _curl($url,$headers=''){
		$headers or $headers=array("User-Agent: EspBuddy"); //gh need this, else 403
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL			, $url);
		curl_setopt($ch, CURLOPT_HEADER			, false);
		//curl_setopt($ch, CURLOPT_SSLVERSION		, 3); //fix SSL on my old debian
		curl_setopt($ch, CURLOPT_RETURNTRANSFER	, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT	, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER		, $headers); //array
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	// ---------------------------------------------------------------------------------------
	private function _GithubVersionToTag($version="",$use_cached_tags=0){
		$version or $version=$this->class_version;
		if($use_cached_tags){
			if(!$branch_tags=$this->_latest_tags){
				$branch_tags=$this->_GithubFetchLatestTags();
			}
		}
		else{
			$branch_tags=$this->_GithubFetchLatestTags();
		}

		if($branch_tags){
			foreach($branch_tags as $branch => $tags){
				foreach($tags as $tag){
					if($tag['version']==$version){
						return $tag;
					}
				}
			}
		}
	}	

	// ---------------------------------------------------------------------------------------
	private function _GithubFetchLatestTag($branch=''){
		$branch or $branch=$this->class_gh_branch_main;

		if($tags=$this->_GithubFetchLatestTags()){
			return current($tags[$branch]);
		}
	}	

	private $_latest_tags;
	// ---------------------------------------------------------------------------------------
	private function _GithubFetchLatestTags(){
		$url	= "{$this->class_gh_api_url}/repos/{$this->class_gh_owner}/{$this->class_gh_repo}/tags";
		$data	= $this->_curl($url);

		if($data){
			$data=json_decode($data,true);
			foreach( $data as $i => $v){
				$k=$v['name'];
				
				$branch		="undefined";
				if(preg_match('/^v/',$v['name'])){
					$branch		=$this->class_gh_branch_main;
				}
				elseif(preg_match('/^d/',$v['name'])){
					$branch		=$this->class_gh_branch_dev;
				}
				
				$out[$branch][$k]['branch']		=$branch;
				$out[$branch][$k]['tag']		=$v['name'];
				$out[$branch][$k]['commit']		=$v['commit']['sha'];
				$out[$branch][$k]['url_zip']	=$v['zipball_url'];
				$out[$branch][$k]['url_gz']		=$v['tarball_url'];
				$out[$branch][$k]['version']		=preg_replace('/^v|d/','',$v['name']);
			}
			$this->_latest_tags=$out;
			return $out;
		}

	}	

	// ---------------------------------------------------------------------------------------
	private function _GitSwitchToBranchTag($dir,$tag,$branch){ //,$origin=""
		//$origin or $origin="origin";
		if(!$dir or !$tag or !$branch){
			return false;
		}
		$commands[]="git fetch --all --tags --prune";
		$commands[]="git checkout $tag";
		$commands[]="git branch -D $branch";
		$commands[]="git checkout -b $branch ";

		//$commands[]="git branch -D $branch";
		//$commands[]="git branch $branch $tag";
		//$commands[]="git checkout $branch ";
		//$commands[]="git checkout --track -b $branch $origin/$branch";
		//$commands[]="git merge $tag";
		//$commands[]="git checkout tags/$tag $git_branch";
		return $this->_Git($commands, $dir);
	}	

	// ---------------------------------------------------------------------------------------
	private function _Git_GitHistory($dir,$tag1='',$tag2=''){
		if(!$dir){
			return false;
		}
		if(!$tag1){
			$gh=$this->_GithubFetchLatestTag();
			$tag1=$gh['tag'];
		}
		if(!$tag2){
			$gh=$this->_GithubVersionToTag('',true);
			$tag2=$gh['tag'];
		}

		$commands[]="git fetch --all --tags --prune";
		$commands[]="git log --pretty=format:\" -  %cd %Cblue%h %Creset%s\" --date=short {$tag1}...{$tag2}";
		return $this->_Git($commands, $dir);
	}	


	
	// ---------------------------------------------------------------------------------------
	private function _Git($git_command, $path_base=""){
		$path_base or $path_base	= $this->orepo->GetPathBase();
		$commands[]	= " cd {$path_base} ";

		if(is_array($git_command)){
			$commands=array_merge($commands,$git_command);
		}
		else{
			$commands[]	= $git_command;
		}
		$command=implode(" \n ", $commands);
		$err=$this->_passthru($command);
		return !$err;
	}

	// ---------------------------------------------------------------------------------------
	private function _passthru($command){
		if($this->flag_drymode){
			$this->sh->PrintCommand($command);
			return 0;
		}
		else{
			if($this->flag_verbose){
				echo"$command\n";
			}
			$this->sh->EchoStyleCommand();
			passthru($command, $return);
			$this->sh->EchoStyleClose();
			echo "\n";
			return $return;
		}
	}

}
?>
