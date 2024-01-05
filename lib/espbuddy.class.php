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

	public $espb_version			= 'd2.50b3';					// EspBuddy Version
	public $espb_gh_owner			= 'soif';						// Github Owner
	public $espb_gh_repo			= 'EspBuddy';					// Github Repository
	public $espb_gh_branch_main		= 'master';						// Github Master Branch
	public $espb_gh_branch_dev		= 'develop';					// Github Develop Branch
	public $espb_gh_api_url			= 'https://api.github.com';		// Github API URL
	public $espb_name				= 'EspBuddy';					// Application Name
	private $espb_path				= '';		// Location of the EspBuddy root directory
	private $espb_path_lib			= '';		// Location of the EspBuddy lib directory

	private $cfg					= array();	// hold the configuration

	// command lines arguments
	private $args				= array();	// command line arguments
	private $bin				= '';		// binary name of the invoked command
	private $path_bin			= '';		// full path to the binary name of the invoked command
	private $action				= '';		// command line action 	(1st Arg)
	private $target				= '';		// command line target	(2nd Arg)
	private $opt1				= '';		// command line command	(3rd Arg)
	private $opt2				= '';		// command line command value	(4th Arg)

	// command lines flags
	private $flag_noconfirm		= false;
	private $flag_drymode		= false;
	private $flag_verbose		= false;
	private $flag_force			= false;
	private $flag_debug			= false;
	
	private $flag_build			= false;
	private $flag_serial		= false;
	private $flag_eraseflash	= false;
	private $flag_skipinter		= false;
	private $flag_prevfirm		= false;
	private $flag_monitor		= false;
	private $flag_copy			= false;
	private $flag_proxy			= false;
	private $flag_background	= false;
	
	private $flag_json			= false;

	// command lines variables
	private $arg_serial_port	= '';
	private $arg_serial_rate	= 0;
	private $arg_config			= '';
	private $arg_repo			= '';
	private $arg_firmware		= '';
	private $arg_login			= '';
	private $arg_pass			= '';
	private $arg_from			= '';		// repo to migrate from

	//selected configuration for the current host
	private $c_host				=array();	//	current host
	private $c_conf				=array();	//	current config
	//private $c_repo			=array();	//	current repository
	private $c_serial			=array();	//	current serial port and rate

	private $os					="";		// what is the OS we are running
	private $sh					;			//	shell object
	private $orepo				;			//	repo_object
	private $factory_dir		='_Factory';//	name of the factory Directory
	private $latest_link		='_Latest';	// name of the link to the latest assets
	private $previous_link		='_Previous';// name of the link to the previous assets
	private $path_factory		='';		// path to /backup/_Factory/
	private $path_factory_repo	='';		// path to /backup/_Factory/repo/
	private $server_pid			=null;		//	Our (Bg) server Process ID

	
	// preferences -------------
	private $prefs	=array(
		'config'		=>	'',				// default config to use
		'repo'			=>	'',				// default repo to use
		'serial_port'	=>	'',				// default serial Port (empty = autoselect)
		'serial_rate'	=>	'boot',			// default serial rate
		'time_zone'		=>	'Europe/Paris',	// Time Zone
		'show_version'	=>	2,				// show version in firmware name (0=no, 1=file version, 2=full git version)
		'firm_name'		=>	'Firmware',		// firmware name prefix
		'settings_name'	=>	'Settings',		// firmware settings name prefix
		'name_sep'		=>	'-',			// field separator in firmware name
		'keep_previous'	=>	3,				// number of previous firmware version to keep
		'checkout_mode'	=>	1,				// Mode when doing a Git checkout : 0 = no checkout, 1 = only if clean, 2 = allows modifications, 3 stash modifications first if any
		'server_port'	=>	81,				// Our builtin web server Port
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


	// Action Help Texts ------------------------------------------------------------------------
	private	$actions_desc=array(
		'root'=>array(
			'upload'		=> "Build and/or Upload current repo version to Device(s)",
			'upgrade'		=> "Upgrade device(s) firmware",
			'build'			=> "Build device(s) firmware",
			'backup'		=> "Download and archive settings from the remote device",
			'monitor'		=> "Monitor device connected to the serial port",
			'server'		=> "Launch Firmwares WebServer",
			'send'			=> "Send commands to device",
			'status'		=> "Show Device's Information",
			'version'		=> "Show remote device version",
			'reboot'		=> "Reboot Device(s)",
			'gpios'			=> "Test all Device's GPIOs",
			'ping'			=> "Ping Device(s)",
			'factory'		=> "Download, get information on the latest factory releases",
			'sonodiy'		=> "Discover, Control or Flash Sonoff devices in DIY mode",
			'self'			=> "Get current, latest or update EspBuddy version",
			'repo_version'	=> "Parse the current repository (REPO) version. REPO is a supported repository (espurna, espeasy, tasmota or wled)",
			'repo_pull'		=> "Git Pull the local repository (REPO). REPO is a supported repository (espurna, espeasy, tasmota or wled)",
			'list_hosts'	=> "List all hosts defined in config.php",
			'list_configs'	=> "List all available configurations, defined in config.php",
			'list_repos'	=> "List all available repositories, defined in config.php",
			'help'			=> "Show full help"
		),
		'factory'			=> array(
			'download'		=>'Download release assets',
			'clean'			=>'Remove oldest assets (keep only latest and previous)',
		),
		'sonodiy'			=> array(
			'help'			=>	'Show Sonoff DIY Help',
			'scan'			=>	'Scan Sonoff devices to find their IP & deviceID',
			'test'			=>	'Toggle relay to verify communication',
			'flash'			=>	'Upload a custom firmware (508KB max, DOUT mode). Use -P to proxy an external firmware URL',
			'ping'			=>	'Check if device is Online',
			'info'			=>	'Get Device Info',
			'pulse'			=>	'Set Inching (pulse) mode (0=off, 1=on) and width (in ms, 500ms step only)',
			'signal'		=>	'Get WiFi Signal Strength',
			'startup'		=>	'Set the Power On State (0=off, 1=on, 2=stay)',
			'switch'		=>	'Set Relay (0=off, 1=on)',
			'toggle'		=>	'Toggle Relay between ON and OFF',
			'unlock'		=>	'Unlock OTA mode',
			'wifi'			=>	'Set WiFi SSID and Password',
		),
		'self'		=> array(
			'version'		=> "Show EspBuddy version",
			'latest'		=> "Show the lastest version available on the 'master' branch",
			'avail'			=> 'Show all versions available',
			'log'			=> 'Show EspBuddy history between current version and VERSION (latest on master branch, if not set)',
			'update'		=> 'Update EspBuddy to the latest version',
		)
	);

	// Command Usages Texts ------------------------------------------------------------------------
	private	$actions_usage=array(
		'root'=>array(
			'upload'		=> "TARGET [options, auth_options, upload_options]",
			'upgrade'		=> "TARGET [options, auth_options, upload_options]",
			'build'			=> "TARGET [options]",
			'backup'		=> "TARGET [options, auth_options]",
			'monitor'		=> "[TARGET] [options]",
			'server'		=> "[ROOT_DIR]",
			'send'			=> "TARGET COMMAND|CMD_SET [options, auth_options]",
			'status'		=> "TARGET [options, auth_options]",
			'version'		=> "TARGET [options, auth_options]",
			'reboot'		=> "TARGET [options, auth_options]",
			'gpios'			=> "TARGET [options, auth_options]",
			'ping'			=> "TARGET [options]",
			'factory'		=> "ACTION [options]",
			'sonodiy'		=> "ACTION [options]",
			'self'			=> "ACTION [options]",
			'repo_version'	=> "REPO",
			'repo_pull'		=> "REPO",
			'list_hosts'	=> "",
			'list_configs'	=> "",
			'list_repos'	=> "",
			'help'			=> ""
		),
		'factory'			=> array(
			'download'		=>	'REPO [TAG] [ASSET] [options]',
			'clean'			=>	'REPO [KEEP] [options]',
		),
		'sonodiy'			=> array(
			'help'			=>	'',
			'scan'			=>	'',
			'test'			=>	'IP ID',
			'flash'			=>	'IP ID [URL] [SHA256SUM] [-P] [-f]',
			'ping'			=>  "IP [COUNT]",
			'info'			=>	'IP ID',
			'pulse'			=>	'IP ID [MODE] [WIDTH]',
			'signal'		=>	'IP ID',
			'startup'		=>	'IP ID [STATE]',
			'switch'		=>	'IP ID [STATE]',
			'toggle'		=>	'IP ID',
			'unlock'		=>	'IP ID',
			'wifi'			=>	'IP ID SSID [PASSWORD]',
		),
		'self'		=> array(
			'version'		=> '',
			'latest'		=> '',
			'avail'			=> '',
			'log'			=> '[VERSION]',
			'update'		=> '[TAG|VERSION|BRANCH]',
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

		$this->path_factory					="{$this->cfg['paths']['dir_backup']}{$this->factory_dir}/";
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
			case 'send':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'backup':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'status':
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
			case 'upgrade':
				$this->BatchProcessCommand($this->action, $this->ChooseTarget());
				break;
			case 'server':
				$this->Command_server();
				break;
			case 'factory':
				$this->Command_factory();
				break;
			case 'sonodiy':
				$this->Command_sonodiy();
				break;
			case 'proxy':
				$this->Command_proxy();
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
			case 'test':
				$this->Command_test();
				break;

			default:
				if(!$this->action){
					if($this->_BashAutoComplete('root')){
						$this->CommandLine();
					}
				}

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
		//echo "\n";
		exit(0);
	}

	// ---------------------------------------------------------------------------------------
	public function BatchProcessCommand($command, $id){
		if($this->flag_drymode){
			$in_drymode=" in DRY MODE";
		}
		$hosts=$this->_ListHosts($id);
		$c=count($hosts);
		if(!$this->flag_json){
			//echo "\n";
			if($c > 1){
				echo "* Processing $c host(s)$in_drymode : \n";
			}
			else{
				$name=str_pad($this->_FillHostnameOrIp($id), 30);
				echo "* Processing host '$id'$in_drymode : $name\n";
			}
		}
		foreach($hosts as $this_id => $host){
			if(!$this->flag_json){
				if($c > 1){
					$name=str_pad($this->_FillHostnameOrIp($this_id), 30);
					$this->_EchoHost($name);			
				}
			}
			//if($c==1){echo "\n";}
			$fn="Command_$command";
			$this->$fn($this_id);
			if(!$this->flag_json){
				echo "\n";
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_build($id){
		$this->_AssignCurrentHostConfig($id,true);
		$path_build=$this->orepo->GetPathBuild();
		$commands_compil[]="cd {$path_build} ";
		if(is_array($this->c_conf['exports'])){
			foreach( $this->c_conf['exports'] as $k => $v ){
				$commands_compil[]	=$this->_ReplaceTags("export $k='$v'");
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
			$firmware_created	=reset(glob($this->orepo->GetPathFirmware()."{$this->c_conf['environment']}/*.bin"));
	
			if(!$r and file_exists($firmware_created)){
				touch($firmware_created,$start_compil);
			}
		}
		echo "\n";
		if(!$r){
			if($this->_rotateFirmware($firmware_created, true)){
				if($commands_post=$this->orepo->GetPostBuildCommands($this->c_host, $this->cfg)){
					$command=implode(" ; \n   ", $commands_post);
					echo "\n";
					$this->_EchoStepStart("Processing Post Build Scripts ", $command);
					if(! $this->flag_drymode){
						passthru($command, $r);
						return !$r;
					}
					echo "\n";
				}
				return true;
			}
		}
		return !$r;
	}

	// ---------------------------------------------------------------------------------------
	public function Command_upgrade($id){
		return $this->Command_upload($id,'upgrade');
	}

	// ---------------------------------------------------------------------------------------
	private function _GetFirmwareVersion($path_firmware){
		if(realpath($path_firmware)){
			$dir_firmware=dirname(realpath($path_firmware));
		}
		else{
			$dir_firmware=realpath(dirname($path_firmware));

		}

		if(strpos($path_firmware,$this->path_factory)===0){
			return basename($dir_firmware);
		}
		elseif(strpos(realpath($path_firmware),$this->c_host['path_dir_backup'])===0){
			if(preg_match('#\([^,]+,([^,)]+)#',realpath($path_firmware),$m)){
				return $m[1];
			}
		}
	}
	// ---------------------------------------------------------------------------------------
	private function _Gzip($path_in, $path_out){
		$command="gzip -c \"$path_in\" > \"$path_out\"";
		$this->_EchoVerbose("$command");
		passthru($command,$r);
		return ! $r;
	}


	// ---------------------------------------------------------------------------------------
	private function _ChooseFirmwareToUse($id,$mode=''){
		$this->_AssignCurrentHostConfig($id);
		$arg_version=$this->args['commands'][3]; // intermediate firmware version for upgrade

		// choose firmware ---------------
		if($this->arg_firmware){
			if($mode=='upgrade'){
				$this->_dieError (" the '--firm' option is not supported with the upgrade command");
			}
			if(file_exists($this->arg_firmware)){
				$this->_rotateFirmware($this->arg_firmware);
				$path_firm_link="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			}
			$firm_type="EXTERNAL";
		}
		elseif($this->flag_build){
			if(! $this->Command_build($id)){
				$this->_dieError ("Compilation Failed");
			}
			echo "\n";
			$path_firm_link	="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			$firm_type="BUILT";
			$arg_version or $arg_version=$this->_GetFirmwareVersion($path_firm_link);
		}
		elseif($this->flag_prevfirm){
			$path_firm_link="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}_previous.bin";
			$firm_type="PREVIOUS";
			$arg_version or $arg_version=$this->_GetFirmwareVersion($path_firm_link);
		}
		else{
			$path_firm_link="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
			$firm_type="CURRENT";
			$arg_version or $arg_version=$this->_GetFirmwareVersion($path_firm_link);
		}


		if(!file_exists($path_firm_link)){
			$this->_EchoError ("No ($firm_type) Firmware found at: $path_firm_link");
			return false;
		}

		if($path_firm_link){
			$out=array();
			$firm_source=realpath($path_firm_link) or $firm_source=$path_firm_link;
			$out['path_firm_link']	=$path_firm_link;
			$out['type']			=$firm_type;
			$out['name']			=basename($firm_source);
			$out['date']			=date("d M Y - H:i:s", filemtime($firm_source));
			$out['size']			=filesize($firm_source);
			return $out;
		}
	}


	// ---------------------------------------------------------------------------------------
	public function Command_upload($id,$mode=''){
		//$this->_AssignCurrentHostConfig($id);

		if(!$firm=$this->_ChooseFirmwareToUse($id,$mode)){
			$this->_EchoError ("Cant figure what firwmare to use");
			return false;
		}
		$path_firm_link	=$firm['path_firm_link'];
		$firm_type		=$firm['type'];
		$firm_date		=$firm['date'];
		$firm_size		=$firm['size'];
		$firm_name		=$firm['name'];
		//echo "\n";		

		$this->_EchoStepStart("Using $firm_type Firmware: $firm_name   (Compiled on $firm_date )","");

		// upgrade mode ----------------------------------------------------------------
		if($mode=='upgrade'){
			$upg=$this->orepo->GetUpgradeConf();
			$path_serv_root	=$this->cfg['paths']['dir_backup'];
			$path_host_dir	=$path_serv_root.$this->_getHostBackupDir($this->c_host).'/';

			$path_firm_file	=realpath($path_firm_link);
			$path_firm_gz_file	=$path_firm_file.'.gz';
			$path_firm_gz_link	=$path_firm_link.'.gz';
			
			if($upg['method']=='server_mini'){
				// ----------------------
				if(!file_exists($path_firm_gz_file)){
					if(!$this->_Gzip($path_firm_file,$path_firm_gz_file)){
						$this->_EchoError("Cannot gzip the firmware");
						return false;
					}
				}
				$this->_SymlinkRelative($path_firm_gz_link, $path_firm_gz_file);
				$url_host_firm_gz	=$this->_GetServerBaseUrl().'/'.$this->_getHostBackupDir($this->c_host).'/'.basename($path_firm_gz_link);

				// is upgrade implemented ----------------------
				if(!$upg['upgrade_command']){
					$this->_EchoError($this->c_conf['repo']." doesnt support upgrade. Aborted");
					return false;
				}

				$pad=37;
				// makes mini firmware ----------------------
				if($upg['firmware']){
					$path_fact_minifirm =$this->path_factory_repo.$arg_version.'/'.$upg['firmware'];	
					$path_fact_minifirm_gz=$path_fact_minifirm.'.gz';
					if(!$arg_version){
						$arg_version=$this->_GetFirmwareVersion($this->path_factory_repo.$this->latest_link.'/'.$upg['firmware']);
						$path_fact_minifirm=$this->path_factory_repo.$arg_version.'/'.$upg['firmware'];			
						if(!file_exists($path_fact_minifirm)){
							echo "* I can't guess the intermediate firmware version to use. Can I revert to the latest (factory) version '$arg_version'? \n";
							if(!$this->_AskConfirm()){
									$this->_EchoError("I need an intermediate firmware version. (You can set it as last argument). Aborted");
									return false;
							}
						}
					}
					// Download mini firmware if not found  ----------------------
					if(!file_exists($path_fact_minifirm)){
						echo "* The $arg_version/{$upg['firmware']} intermediate firmware is not found in the Factory's {$this->c_conf['repo']} folder. Let's download it...\n";
						if(!$this->Factory_download($this->c_conf['repo'],$arg_version,$upg['firmware'])){
							$this->_EchoError("This intermediate firmware is required to perform the upgrade. Aborted");
							return false;
						}
						$arg_version=$this->_GetFirmwareVersion($path_fact_minifirm);
					}
					// Link to the mini firmware ----------------------
					if(!$minfirm_link= $this->orepo->GetMinimalFirmwareName($path_firm_link)){
						$this->_EchoError("Cant make Minimal Firmware name. Aborted");
						return false;					
					}
					$this->_SymlinkRelative($path_host_dir.$minfirm_link, $path_fact_minifirm);

					// Gzip mini firmware if not found  ----------------------
					if(!file_exists($path_fact_minifirm_gz)){
						if(!$this->_Gzip($path_fact_minifirm,$path_fact_minifirm_gz)){
							$this->_EchoError("Cannot gzip the firmware");
							return false;
						}
					}
					// Link to the mini gz firmware ----------------------
					if(!$minfirm_gz_link= $this->orepo->GetMinimalFirmwareName($path_firm_gz_link)){
						$this->_EchoError("Cant make Minimal Gz Firmware name. Aborted");
						return false;
					}
					$this->_SymlinkRelative($path_host_dir.$minfirm_gz_link, $path_fact_minifirm_gz);

					echo "* ". str_pad("Intermediate firmware is set to: ",$pad)."$arg_version / {$upg['firmware']}\n";
				}

				// get current url ----------------------
				if($upg['get_command']){
					$this->_EchoVerbose( str_pad("Current device's upgrade URL was: ",$pad),false);
					if(!$r=$this->orepo->RemoteSendCommands($this->c_host,$upg['get_command'])){
						$this->_EchoError("No answer");
						return false;
					}
					$url_dev_upg=$r[$upg['get_field']];
					$this->_EchoVerbosePart( "$url_dev_upg");
				}
				// set upgrade url ----------------------
				if($upg['set_command']){
					$this->_EchoVerbose(  str_pad("Set device's upgrade URL to: ",$pad).$url_host_firm_gz );
					$set_com=str_replace('{{server_url}}',$url_host_firm_gz, $upg['set_command']);
					if(!$r=$this->orepo->RemoteSendCommands($this->c_host, $set_com)){
						$this->_EchoError("No answer");
						return false;
					}
				}
				// Performs upgrade ----------------------
				$return =true;
				if($upg['upgrade_command']){
					if(!$this->_bgServerStartSafe()){
						$this->_EchoError("Can't start built-in WebServer");
						return false;
					}
					if($r=$this->orepo->RemoteSendCommands($this->c_host, $upg['upgrade_command'])){
						echo "* Uploading minimal firmware... ";
						$this->_WaitPingable($this->c_host['ip'],30,true);
						echo "* Rebooting.................... ";
						$this->_WaitPingable($this->c_host['ip'],15);
						echo "* Uploading new firmware....... ";
						$this->_WaitPingable($this->c_host['ip'],30,true);
						echo "* Rebooting.................... ";
						$this->_WaitPingable($this->c_host['ip'],15);
						echo "* Waiting a little bit more.... ";
						sleep(3);
						$this->_WaitPingable($this->c_host['ip'],10);
						if($vers=$this->orepo->RemoteGetVersion($this->c_host)){
							echo "\n";
							$this->sh->PrintAnswer( str_pad("SUCCESSFULLY updated to version: ",$pad). $vers);
						}
						else{
							$this->_EchoError("Cant get remote version! This maybe means that something has failed");
							$return = false;
						}
					}
					else{
						$this->_EchoError("Upgrade command was not accepted");
						$return = false;
					}
					$this->_bgServerStop(true);
				}

				// reverts upgrade url ----------------------
				if($upg['set_command'] and $url_dev_upg){
					$this->_EchoVerbose( str_pad("Revert device upgrade URL to: ",$pad).$url_dev_upg );
					$set_com=str_replace('{{server_url}}',$url_dev_upg, $upg['set_command']);
					if(!$r=$this->orepo->RemoteSendCommands($this->c_host, $set_com)){
						$this->_EchoError("No answer");
						return false;
					}
				}
				return $return;
			}
			else{
				return $this->_dieError ("Unknown Upgrade Method '{$upg['method']}'");
			}

		}
		// .wire mode -----------------------------------------------------------------------
		elseif($this->flag_serial){
			if($this->flag_eraseflash){
				$this->_DoSerial($id,'erase_flash');
				$this->_WaitReboot(5);
			}
			$this->_DoSerial($id,'write_flash', $path_firm_link);
			
			if($this->flag_monitor){
				$this->Command_monitor($id);
			}
		}

		// .OTA mode ------------------------------------------------------------------------
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
					return $this->_dieError ("Can't reach {$this->c_host['ip']} after 20sec.");
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
			$command	="{$this->cfg['paths']['bin_espota']} -r -d -i {$this->c_host['ip']}  -f \"$path_firm_link\"$arg_pass";
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
				$this->_EchoError($this->orepo->GetLastError());
				return false;
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
	private function _ChooseWebRoot($from=''){
		$root='';
		if($from){
			$path_host	=$this->cfg['paths']['dir_backup'].$from;
			$path_repo	=$this->path_factory.$from;
			if(isset($this->cfg['hosts'][$from])){
				$this->_AssignCurrentHostConfig($from);
				$root=$this->c_host['path_dir_backup'];
			}
			elseif(file_exists($path_host)){
				$root=$path_host;
			}
			elseif(file_exists($path_repo)){
				$root=$path_repo;
			}
			elseif(file_exists($from)){
				$root=$from;
			}
			else{
				$this->_dieError("Can not find this 'ROOT_DIR' directory in the Factory folder, in the backup folder or as an absolute path");
			}
		}
		$root or $root=$this->cfg['server_root'] or $root=$this->cfg['paths']['dir_backup'];
		$root = rtrim($root,"/");
		return $root;
	}

	// ---------------------------------------------------------------------------------------
	private function _GetServerBaseUrl($use_hostname=false){
		$port	=$this->prefs['server_port'];
		if($use_hostname){
			$ip	=getHostName();
		}
		else{
			$ip=getHostByName(getHostName());
		}
		return "http://$ip:$port";
	}

	// ---------------------------------------------------------------------------------------
	public function Command_server(){		
		$root=$this->_ChooseWebRoot($this->target);
		$index	=$this->espb_path_lib."espb_server_index.php";
		$port	=$this->prefs['server_port'];
		$command="php -S 0.0.0.0:$port -t $root $index";
		
		$ip_url		=$this->_GetServerBaseUrl();
		$host_url	=$this->_GetServerBaseUrl(true);
		$port		=$this->prefs['server_port'];

		$do_echo=true;
		if($do_echo){
		$this->_EchoStepStart("Launching WebServer on port $port on every network interfaces",$command);
			$tab="   ";
			echo "Some possible URLs are:\n";
			echo $tab."$ip_url\n";
			echo $tab."$host_url\n";
			echo $tab."http://localhost:$port\n";
			echo $tab."http://127.0.0.1:$port\n";
			echo "\n";
			echo "Serving directory:\n";
			echo $tab."$root\n";
			echo "\n";
			echo "(Press Ctrl-C to stop)\n";
		}
		if($this->flag_drymode){
			return true;
		}
		passthru($command, $r);
	}

	// ---------------------------------------------------------------------------------------
	public function Command_send($id){
		$this->_AssignCurrentHostConfig($id);

		$repo		=$this->cfg['commands'][$this->opt1]['repo'] or $repo=$this->c_conf['repo'];
		$commands	=$this->cfg['commands'][$this->opt1]['list'];
		//when exists in config
		if($commands){
			// add host command
			// $this->c_host['commands'] and $commands .="\n".$this->c_host['commands'];

		}
		else{
			// consider as a single command
			$commands="{$this->opt1} {$this->opt2}";
			$is_single=true;
		}


		if(!$repo){
			return $this->_dieError (" 'repo' is not set (neither in commands set, nor in target's config, nor as argument)");
		}
		if(!$commands){
			return $this->_dieError ("No commands founds. Please either specify a valid gobal command set, or specify 'commands' in the target configuration. ");
		}

		$commands=$this->_ParseCommands($commands,$id);
		if(!$this->flag_json){
			echo "\n";
			$this->_EchoStepStart("Sending commands ","");

			if($this->flag_verbose){
				if($is_single){
					echo "COMMAND: $commands\n\n";
				}
				else{
					echo "COMMANDS LIST:\n$commands\n\n";
				}
			}
		}
		if(! $is_single){
			if(! $this->_AskYesNo()){
				return false;
			}	
			if(!$this->flag_json){
				echo "\n";
			}	
		}

		if(!$this->flag_drymode){
			
			$this->orepo=$this->_RequireRepo($repo);
			
			$r=$this->orepo->RemoteSendCommands($this->c_host,$commands);
			if(is_array($r)){
				if($this->flag_json){
					echo json_encode($r,JSON_PRETTY_PRINT);
				}
				else{
					echo $this->_PrettyfyNoTabs($r);
				}
			
				//$txt=json_encode($r,JSON_PRETTY_PRINT);
				//echo $txt;
			}
			elseif($r){
				echo "$r";
			}
			elseif(!$r){
				$last_err=$this->_EchoError($this->orepo->GetLastError()) or $last_err='No Result';
				$this->_EchoError($last_err);
				return false;
			}
		}
		return true;
	}

	// ---------------------------------------------------------------------------------------
	private function _ParseCommands($str, $id=''){
		//remove blank lines
		$str=preg_replace('#^\s*[\n\r]+#m','',$str);
		if($id){
			$str=$this->_ReplaceTags($str,$id);
		}
		$str=trim($str);
		return $str;
	}

	// ---------------------------------------------------------------------------------------
	public function Command_status($id){
		$this->_AssignCurrentHostConfig($id);

		if(!$this->flag_verbose and !$this->flag_json){
			$this->_EchoCurrentHost();
			$this->_EchoCurrentConfig();
			echo "\n";
		}

		$r=$this->orepo->RemoteGetStatus($this->c_host);
		if(is_array($r)){
			if($this->flag_json){
				echo json_encode($r,JSON_PRETTY_PRINT);
			}
			else{
				echo "\n";
				echo $this->_PrettyfyNoTabs($r);
			}
			return true;
		}
		elseif($r){
			echo "\n";
			echo "$r";
			return true;
		}
		elseif(!$r){
			$last_err=$this->_EchoError($this->orepo->GetLastError()) or $last_err='No Result';
			$this->_EchoError($last_err);
			return false;
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_version($id){
		$this->_AssignCurrentHostConfig($id);
		if($version=$this->orepo->RemoteGetVersion($this->c_host)){
			echo "*** Remote '$id' ({$this->c_conf['repo']}) Version is	: $version \n";
			return true;
		}
		else{
			$this->_EchoError($this->orepo->GetLastError());
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_reboot($id){
		$this->_AssignCurrentHostConfig($id);
		//echo "{$this->c_conf['repo']}\t";
		if($this->orepo->RemoteReboot($this->c_host)){
			return true;
		}
		$this->_EchoError($this->orepo->GetLastError());
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

		$this->orepo=$this->_RequireRepo($repo_key,true);

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
	private function _getMyIpAddress(){
		//TODO verify if its ALWAYS working on all OS, with wifi, DNS, MDNS, etc...
		return getHostByName(getHostName());
	}

	// ---------------------------------------------------------------------------------------
	private function _getProxyPID(){
		//TODO move to TMP
		return $this->proxy_pid;
	}

	// ---------------------------------------------------------------------------------------
	private function _setProxyPID($pid){
		//TODO move to TMP
		$this->proxy_pid=$pid;
	}

	// ---------------------------------------------------------------------------------------
	var $proxy_pid	=0;
	var $proxy_port	=8765;
	var $proxy_bg	=false;
	public function Command_proxy($action="start", $port=0 ){
		$port or $port=$this->proxy_port;
		$bg=$this->proxy_bg or $bg=$this->flag_background;

		if($action=='start'){
			$ip=$this->_getMyIpAddress();
			$this->sh->PrintAnswer("Launching Proxy server at IP $ip , on port $port , using : ",false);

			$command="php -S 0.0.0.0:$port {$this->espb_path_lib}proxy.php";
			
			if($bg){
				if($this->os=='win'){
					echo "FAILED\n";
					$this->sh->PrintError("Launching the Proxy in background is not fully implemented in Windows");
					echo("Try ro run the following command in a SEPARATE console: ");
					$this->sh->PrintCommand("{$this->bin} proxy start");
					echo("Then retry the flash command, adding the '-f' flag.\n");
					exit(1);
				}
				else{
					$command="nohup php -S 0.0.0.0:$port {$this->espb_path_lib}proxy.php > /dev/null 2> /dev/null & echo $!";
				}
			}
			$this->sh->PrintCommand($command);
			$pid=trim(shell_exec($command));
			$this->_setProxyPID($pid);

			echo("Launched Proxy Server, with pid: $pid \n");
			if($this->flag_background){
				echo("Use This command to stop it: ");
				$this->sh->PrintCommand("kill -9 $pid");
			}

			return $pid;
		}
		elseif($action=='stop'){
			$this->sh->PrintAnswer("Stopping Proxy server using : ",false);
			if($pid=$this->_getProxyPID()){
				$command="kill -9 $pid";
				$this->sh->PrintCommand($command);
				passthru($command);
			}
			else{
				echo "FAILED\n";
				$this->sh->PrintError("Sorry I don't know the last PID of the Proxy server");
				echo "You will have to find it by yourself , and kill it manually.\n";
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_help($action='root',$error=''){
		$action or $action='root';
		if($action=='root'){
			echo $this->_getVersionBuddyLong();
			echo "\n\n";	
		}
		if($error){
			echo "\n";
			$this->sh->PrintError($error);
			echo "\n";	
		}

		$this->_show_command_usage($action);
		$this->_show_action_desc($action);
		$this->_show_action_usage($action);
		if($action=='root'){
			echo <<<EOF
---------------------------------------------------------------------------------
+ TARGET            : Target of the command. Either:
                       - the Host's ID (defined in \$cfg['hosts'] from config.php). This is the easiest way!
                       - an IP address or a Hostname. (Most of the time a --repo or --conf would also be needed!)
                       - 'all' (for commands supporting batch mode) loops thru all defined Hosts (defined from config.php)

+ COMMAND|CMD_SET   : Command(s) to send. Either:
                       - a single command as "command [value]" (following the the device's command own syntax)
                       - a commands List's ID (defined in \$cfg['commands'] from config.php), 

+ ROOT_DIR          : Root directory (for the built-in Web Server). Either:
                       - a REPO to only serves from the /espb_backup/{$this->factory_dir}/REPO/ folder
                       - an Host ID (or a Host folder) to only serves from its /espb_backup/folder
                       - an (absolute) path of a directory
                       - when left blank, it defaults to the /espb_backup/ folder (prefered way)

+ OPTIONS :
    -y              : Automatically set YES to confirm "Yes/No" dialogs
    -d              : Dry Run. Show commands but don't apply them
    -v              : Verbose mode
    -j              : Displays result as JSON (only for 'send', 'status', 'sonodiy api' commands)
    -D              : Debug mode (shows PHP errors)
    --conf=xxx      : Config name to use (overrides per host settings)
    --repo=xxx      : Repository to use (overrides per host settings)

+ UPLOAD_OPTIONS :
    -b              : Build before Flashing/Uploading/Upgrading firmware
    -w              : Wire Mode : Upload using the serial port instead of the default OTA
    -e              : In Wire Mode, erase flash first, then upload
    -m              : Switch to serial monitor after upload
    -p              : Upload previous firmware backuped (instead of the latest build)
    -s              : Skip Intermediate OTA Upload (when 2steps mode is  set)
    -c              : When using --firm, make a copy instead of a symbolic link
    --port=xxx      : Serial port to use (override main or per host serial port)
    --rate=xxx      : Serial port speed to use (override main or per host serial port)
    --firm=xxx      : Full path to the firmware file to upload (override latest build one)
    --from=REPO     : Migrate from REPO to the selected config

+ AUTH_OPTIONS :
    --login=xxx     : Login name (overrides host or per config login)
    --pass=xxx      : Password (overrides host or per config password)

EOF;
			//$this->_show_action_desc('sonodiy','sonodiy ACTIONS');
		}
		if($action=='factory'){
			echo <<<EOF
---------------------------------------------------------------------------------
+ REPO              : Repository (espurna, espeasy, tasmota or wled) to process

+ TAG               : Tag name (aka version)

+ ASSET             : Assets to download. Either: 
                      - a single asset name.
                      - a list of assets, separated by '#'; 
                      - an assets list's ID (defined in \$cfg[repos]['REPO']['assets_groups'] from config.php)
                      - 'all' selects all available assets

+ KEEP              : Clean mode defaults to keep the last 2 versions (latest and previous) and ask to delete oldest files.
                      You might enter the number of versions to keep, or use 'none' to select all files

+ OPTIONS :
    -y              : Automatically set YES to confirm "Yes/No" dialogs
    -v              : Verbose mode

EOF;
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Command_self(){

		if($this->target=='help'){
			$this->Command_help('self');
		}
		elseif($this->target=='version'){
			echo "Current version: {$this->espb_version}\n";
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
			echo "   ". str_pad('Tag', $p). str_pad('Version', $p). str_pad('Branch', $p). str_pad('Commit', 20)."\n";
			foreach ($tags as $branch => $rows){
				foreach ($rows as $v){
					$line=str_pad($v['tag'], $p). str_pad($v['version'], $p). str_pad($v['branch'], $p). str_pad($v['commit'], $p)."\n";
					if($v['version']==$this->espb_version){
						$this->sh->PrintBold(" = $line",false);
					}
					else{
						echo " - $line";
					}
				}
			}
		}
		elseif($this->target=='update'){
			$tags=$this->_GithubFetchLatestTags();

			$arg=$this->args['commands'][3];
			if($arg=='dev'){
				$arg=$this->espb_gh_branch_dev;
			}

			$arg or $tag=current($tags[$this->espb_gh_branch_main]); //latest tags
				
			$tag or	$tag=current($tags[$arg])
				or	$tag=$this->_GithubVersionToTag($arg)
				or	$tag=$tags[$this->espb_gh_branch_main][$arg]
				or	$tag=$tags[$this->espb_gh_branch_dev][$arg]
				or	$this->_dieError("Can't find a tag or branch named '$arg' ");

			if($tag['version']==$this->espb_version and !$this->flag_force){
				$this->sh->PrintAnswer("You're already running this version!");
			}
			else{
				echo "Update {$this->espb_name} from current version {$this->espb_version} to version {$tag['version']} (tag '{$tag['tag']}' on '{$tag['branch']}' branch).\n";
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
	public function Command_factory(){
		if($this->target=='download'){
			$repo	=$this->args['commands'][3];
			$tag	=$this->args['commands'][4];
			$a_name =$this->args['commands'][5];// or $a_name='all';
			$this->Factory_download($repo,$tag,$a_name);
		}
		elseif($this->target=='clean'){
			$repo	=$this->args['commands'][3];
			$back	=$this->args['commands'][4];
			if($back=='none'){
				$back	=0;
			}
			elseif($back and $back == intval($back)){
			}
			else{
				$back	=2;
			}
			$this->Factory_clean($repo,$back);
		}
		elseif($this->target=='help'){
			$this->Factory_help();
		}
		elseif($this->target){
			$error="Invalid Action: '{$this->target}'";
		}
		else{
			$error="Missing a '{$this->action}' Action";
		}
		if($error){
			$this->_showActionUsage($error);
			exit(1);
		}
		exit(0);
	}

	// ---------------------------------------------------------------------------------------
	private function _CreateDirectory($path, $time=''){
		if(!$path){
			return false;
		}
		if(! file_exists($path)){
			$dir=basename($path);
			$this->_EchoVerbose("Creating the '{$dir}' directory at: $path	",false);
			if(! @mkdir($path)){
				$this->_EchoVerbosePart("FAILED!");
				return false;
			}
			else{
				if($time){
					touch($path,$time,$time);
				}
				$this->_EchoVerbosePart("OK");
				return true;
			}
		}
		return true;
	}

	// ---------------------------------------------------------------------------------------
	private function _IsSymLinkFrom($path_file,$path_link){
		if(file_exists($path_link)){
			$path_file=rtrim($path_file,'/');
			$path_link=rtrim($path_link,'/');
			//echo "\n LINK: $path_link\n REAL: ".realpath($path_link)."\n VS  : $path_file";
			if($path_file==realpath($path_link)){
				return true;
			}
		}
		//echo "\n NOT FOUND: $path_link\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Factory_clean($repo='',$back_n=2){
		if(!$repo){
			$this->Command_help('factory',"Missing a REPO argument");
			exit(1);
		}
		$this->orepo=$this->_RequireRepo($repo);
		$i=0;
		if(is_dir($this->path_factory_repo)){
			$files_names=array();
			echo "* Keeping the latest $back_n directories in '$repo', and delete others....\n";
			if($files=$this->_ListFilesByDate($this->path_factory_repo,'dir')){
				echo "* All Assets found in: {$this->path_factory_repo} :\n";
				foreach($files as $time => $path_file){
					$name=basename($path_file);
					if(!is_link($path_file) and !preg_match('#^\.#',$name)){
						$date=date('M j, Y H:i',$time);
						$files_names[$path_file]=str_pad("$name ",18). str_pad($date, 18 ,' ', STR_PAD_LEFT)." ";
						$desc='';
						if($this->_IsSymLinkFrom($path_file, $this->path_factory_repo.$this->latest_link)){
							$desc="(latest)";
						}
						elseif($this->_IsSymLinkFrom($path_file, $this->path_factory_repo.$this->previous_link)){
							$desc="(previous)";
						}
						$files_names[$path_file] .=str_pad($desc,11);
						echo "  - ". $files_names[$path_file]."\n";
					}
				}
				echo "\n";

				if(count($files_names)){
					$back_n=intval($back_n);
					for ($i=0; $i < $back_n ; $i++) { 
						array_shift($files_names);
					}
					if(count($files_names)){
						$i=0;
						foreach($files_names as $path_file => $name){
							echo "* Delete ";
							if($this->flag_verbose){
								echo "$path_file	";
							}
							else{
								echo str_pad($name, 45);
							}
							echo "? ";
							if($this->_AskConfirm()){
								$command="rm -rf $path_file";
								if(strpos($path_file,$this->path_factory_repo)==0){
									shell_exec($command);
									$this->_SymlinkLatestAndPrevious();
									$i++;
								}
								else{
									echo "# CANCELED because $path_file is outside of the {$this->factory_dir} directory.\n# Please do it manually!\n";
								}
							}
						}

						if($i){
							echo "* Successfully deleted $i directories !\n";	
						}
						return $i;
					}
				}
			}
		}
		echo "# Directory is Empty !\n";
	}

	// ---------------------------------------------------------------------------------------
	public function Factory_download($repo='',$tag='',$asset_name=''){
		if(!$repo){
			$this->Command_help('factory',"Missing a REPO argument");
			exit(1);
		}
		$this->orepo=$this->_RequireRepo($repo);


		if(preg_match('/#/',$asset_name)){
			$asset_name=explode('#',$asset_name);
		}
		elseif($preset=$this->cfg['repos'][$repo]['assets_groups'][$asset_name]){
			$asset_name=$preset;
		}
		if($assets=$this->orepo->RepoChooseAssets($tag,$asset_name)){
			$size=$this->FormatBytes($assets['size_total']);
			echo "* Found {$assets['count']} assets for a total size of $size !\n";
			if($this->flag_verbose){
				foreach($assets['assets'] as $item){
					echo "  - ".$item['name']."\n";
				}
			}
			if($this->_AskConfirm()){
				//makes needed directories
				$path_data=$this->cfg['paths']['dir_backup'];
				if(! $this->_CreateDirectory($path_data)){return false;}
		
				$path_fact	="{$path_data}{$this->factory_dir}/";
				if(! $this->_CreateDirectory($path_fact)){return false;}
		
				$path_repo	="{$path_fact}$repo/";
				if(! $this->_CreateDirectory($path_repo)){return false;}
		
				$path_tag ="{$path_repo}{$assets['release']['tag_name']}/";
				if(! $this->_CreateDirectory($path_tag, $assets['release']['espb_time'])){return false;}

				echo "* Downloading {$assets['count']} assets into $path_tag ...\n";
				//touch($path_tag,time());

				$err=$ok=0;
				$col=$assets['espb_col'] or $col=50;
				foreach($assets['assets'] as $item){
					echo " - ".str_pad($item['name'],$col);
					if($this->orepo->DownloadAsset($item['browser_download_url'], $path_tag, $assets['release']['espb_time'])){
						echo "OK\n";
						$ok++;
					}
					else{
						echo "FAILED\n";
						$err++;
					}
				}

				//touch($path_tag,$assets['release']['espb_time'],$assets['release']['espb_time']);

				if($ok==$assets['count']){
					echo "* Successfully downloaded $ok assets!\n";
					$this->_SymlinkLatestAndPrevious();
					return true;
				}
				else{
					echo "* ERROR: Downloaded $ok/{$assets['count']} assets. $err have failed!\n";
				}
			}
		}
		elseif($tag and $asset_name){
		}
	}

	// ---------------------------------------------------------------------------------------
	public function Factory_help(){
		$this->Command_help('factory');
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
			exit(1);
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

		$this->sh->PrintAnswer("Scanning network for Devices using command: ",false);

		// --- linux ------------------------------
		if($this->os == "lin"){
			$command="avahi-browse -t _ewelink._tcp  --resolve";
			$this->sh->PrintCommand($command);
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
						$ids[$i]=str_replace("eWeLink_","",$match[1]);
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
					//OK
				}
				else{
					$this->sh->PrintError("\nSorry, I did not found any device!");
				}
			}
			else{
				$this->sh->PrintError("\nIt seems that we miss the 'avahi-browse' command");
				$this->sh->PrintAnswer("on Debian/ubuntu, please run:");
				$this->sh->PrintCommand("sudo apt-get install avahi-utils \n");
				$this->sh->PrintAnswer("on RHEL/Centos, please run:");
				$this->sh->PrintCommand("sudo yum install avahi-tools ");
			}
		} // --- OSX ------------------------------
		elseif($this->os == "osx"){
			$command="dns-sd -B $service";

			$this->sh->PrintCommand($command);
			$bash=$this->_sondiy_osx_com2bash($command,5);
			$lines_ids=trim(shell_exec($bash));
			
			if($lines_ids){
				$lines=explode("\n",$lines_ids);
				foreach($lines as $line){
					list($trash,$raw_id)=explode($service.'.' , $line);
					$ids[]=trim(str_replace('eWeLink_','',$raw_id));
				}
				//$this->sh->PrintBold( "Device IDs Found:");
				//echo "   - ". implode("\n   - ",$ids);
				//echo "\n\n";

				$first_id='eWeLink_'.$ids[0];
				$command="dns-sd -q $first_id.local";
				$this->sh->PrintAnswer("Get IP Address of the first device found ({$ids[0]}) using command: ",false);
				$this->sh->PrintCommand($command);
				
				$command=$this->_sondiy_osx_com2bash($command,4);
				$lines_ip=trim(shell_exec($command));
				if($lines_ip){
					list($line_ip,$trash)=explode("\n" , $lines_ip);
					list($trash,$raw_ip)=explode('IN' , $line_ip);
					$ips[0]=trim($raw_ip);
				}
				else{
					$this->sh->PrintError( "\nSorry, I could not get the IP Address");
				}
				// dns-sd -L  $first_id _ewelink._tcp local
			}
			else{
				$this->sh->PrintError( "\nSorry, I did not found any device");
			}
		}
		// --- Windows ------------------------------
		elseif($this->os == "win"){
			$command="python {$this->cfg['paths']['bin']}mdns.py";
			$this->sh->PrintCommand($command);
			$raw=trim(shell_exec($command));
			$raw_example=<<<EOF
inter add_service()
1000aba1ee  10.1.250.154  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
1000a2a1ee  10.1.250.152  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
1000aba1ee  10.1.250.154  8081  {b'type': b'diy_plug', b'data1': b'{"switch":"on","startup":"off","pulse":"off","sledOnline":"on","pulseWidth":1000,"rssi":-58}', b'id': b'1000aba1ee', b'apivers': b'1', b'seq': b'120', b'txtvers': b'1'}
			
EOF;
			if($raw){
				$lines=explode("\n",$raw);
				$first_line=$lines[0];
				unset($lines[0]);
				foreach($lines as $line){
					if(preg_match('#^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+.*?diy_plug#',$line,$match)){
						$raw_ids[$match[1]]		=$match[1];
						$raw_ips[$match[1]]		=$match[2];
						$raw_ports[$match[1]]	=$match[3];
					}
				}
				
				if($raw_ids){
					//reassign to num array
					$i=0;
					foreach($raw_ids as $k => $id){
						$ids[$i]	=$raw_ids[$k];
						$ips[$i]	=$raw_ips[$k];
						$ports[$i]	=$raw_ports[$k];
						$i++;
					}
				}
				else{
					if(preg_match('#add_service#',$first_line)){
						$this->sh->PrintError( "\nSorry, I did not found any device");
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
				$this->sh->PrintError("\nThe python script has certainly crashed");
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

		if($ips and $ids){
			$found_args="{$ips[0]} {$ids[0]}";
			$c1=15;	$c2=20;	$c3=22;
			$ct=$c1+$c2+$c3+1;
			echo "\n";
			$this->sh->PrintBold("Devices Found:\n");
			echo str_repeat('=',$ct)."\n";
			echo str_pad("| ID", 			$c1).	str_pad(" | IP Address", 	$c2).	str_pad(" | MAC Address",	$c3)."|\n" ;
			echo str_repeat('=',$ct)."\n";
			foreach($ids as $i => $id){
				$ip=$ips[$i];
				$ip and $found_mac=$this->_IpAddressToMAC($ip);
				echo str_pad("| {$id} ",	$c1).	str_pad(" | {$ip}", 	$c2).	str_pad(" | $found_mac", 	$c3)."|\n" ;
			}
			echo str_repeat('-',$ct)."\n";

			echo "\nYou can now use: ";
			$this->sh->PrintBold($found_args, false);
			echo " as arguments for sonodiy Actions!\nExamples:\n";
			$this->sh->PrintCommand( " {$this->bin} sonodiy test  $found_args");
			$this->sh->PrintCommand( " {$this->bin} sonodiy unlock  $found_args");
			$this->sh->PrintCommand( " {$this->bin} sonodiy flash $found_args");
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

			if($this->flag_verbose or $this->flag_drymode){

				if($result){
					$this->sh->PrintBold("\nDONE !");
				}
				echo "\n--- URL requested ---------------------------\n";
				$this->sh->PrintCommand($curl_url);
				echo "\n--- Request Sent: ---------------------------\n";
				$this->sh->PrintCommand(print_r($curl_req,true));
				echo "--- Command result ---------------------------\n";
				$this->sh->PrintCommand(print_r($curl_res,true));

				$info=$this->_sonodiy_api_info($ip,$id);
				if(is_array($result['data'])){
					//$info['data']=array_merge($info['data'],$result['data']);
				}
				echo "--- Last Information Data: -------------------\n";
				$this->sh->PrintCommand(print_r($info['data'],true));
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
	private function _sonodiy_api_flash($ip, $id,$url,$sha256='',$verify_unlocked=true){
		//$verify_unlocked=false;
		
		if($verify_unlocked){
			$this->sh->PrintAnswer( "Checking if the OTA method is unlocked : ", false);
			$info=$this->_sonodiy_api_info($ip, $id);
			if($info['data']['otaUnlock'] != 1 and !$this->flag_drymode){
				echo "NO\n";
				$this->sh->PrintError( "The OTA method is not Unlocked");
				echo "Please send the following command, and try again.\n";
				$this->sh->PrintCommand( " {$this->bin} sonodiy unlock $ip $id -v");
				echo "\n";
				exit(1);
			}
			if($this->flag_noconfirm){
				sleep(1); // let some time for the ESP to be ready for next command
			}	
			echo "OK\n";
			echo "\n";
		}

		$url or $url=$this->cfg['sonodiy']['firmware_url'];
		// 508KB max, DOUT mode
		if(!$url){
			$this->_dieError( "Missing Firmware URL");
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
		elseif($size_k == 0 ){
			$this->_dieError("I can not download a firmware at : $url\n Please verify the url, and try again");
		}
		elseif($size_k < 100 ){
			$this->_dieError("Size is less than 100 kB, this seems strange");
		}

		if($this->flag_proxy){
			if(!$this->_getProxyPID()){
				if(!$this->flag_force){
					$this->proxy_bg=true;
					echo "\n";
					$this->Command_proxy('start');
					sleep(1); //let him some time to start
				}
			}
			$ip=$this->_getMyIpAddress();					
			$my_proxy	="http://$ip:{$this->proxy_port}";
			$url		="$my_proxy/$url";
			$data		=file_get_contents($url);
			$p_sha256	=hash('sha256',$data);
			$size		=strlen(bin2hex($data))/2;
			$size_k		=$size/1024;
			$size_k_round=ceil($size_k);
			echo "\n";
			echo "Firmware to upload USING PROXY : \n";
			echo " - New URL    : $url\n";
			echo " - New sha256 : $p_sha256\n";
			echo " - New size   : {$size_k_round} kB ($size bytes)\n";
			if($sha256 != $p_sha256){
				if(!$this->flag_force){
					$this->Command_proxy('stop');
				}
				$this->_dieError("The direct firmware and the proxied firmware do not match! Something went wrong!");
			}
		}

		echo "\n";
		$ok=$this->_AskConfirm();
		echo "\n";
		
		if(!$ok){
			$this->sh->PrintAnswer( "Cancelling...");
			if($this->flag_proxy){
				if(!$this->flag_force){
					$this->Command_proxy('stop');
				}
			}
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
			$this->_WaitPingable($ip, 5);
			if($this->flag_proxy){
				if(!$this->flag_force){
					echo "\n";
					sleep(4); // just in case
					$this->Command_proxy('stop');
				}
			}
			echo "\n";
			echo "Flashed the new firmware!\n";

			$this->sh->PrintCommand("
	PLEASE NOTE If the firmware has been ignored by the device API:

- If you're using a LOCAL firmware URL, try again, it should work (successfully tested)!

- If you're using an EXTERNAL firmware URL (ie the one included in the config-sample.php), it seems that it is blindly ignored.
Please try the same command, but adding the -P flag at the end. This would create a proxy to try to fool the API, with a local URL.
The proxy is working (tested), but unfortunately I have no more device to test if the sonof API accept it, or also ignore It.
If this workaround method works or not for you, PLEASE do report in the Github issue #20 at:
https://github.com/soif/EspBuddy/issues/20

			"); 

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

		$json=json_encode($data, JSON_UNESCAPED_SLASHES); // php >=5.4
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
	private function _show_action_desc($action='root',$title=""){
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
	function _prefixPythonPath(){
		if($this->cfg['paths']['dir_python']){
			$prefix='export PATH="'. $this->cfg['paths']['dir_python'].':$PATH" ;'. "\n";
		}
		return $prefix;
	}

	// ---------------------------------------------------------------------------------------
	private function _DoSerial($id,$action='write_flash',$firmware_file=''){
		$this->_AssignCurrentHostConfig($id);
		//$path_build=$this->orepo->GetPathBuild();

		if(!$this->c_host['serial_port']){
			return $this->_dieError ("No Serial Port choosen");
		}

		$this->c_host['serial_rate']	 and
			$arg_rate=" -b {$this->c_host['serial_rate']}" and
			$echo_rate=", Rate: {$this->c_host['serial_rate']} bauds";

		$command=$this->_prefixPythonPath()."{$this->cfg['paths']['bin_esptool']} -p {$this->c_host['serial_port']}{$arg_rate} $action ";

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
	private function _CreateHost($host){
		$tmp=array();
		$tmp['is_created']=true;
		if(preg_match('#^\d+\.\d+\.\d+\.\d+$#',$host)){
			$tmp['ip']=$host;
			$tmp['hostname']=gethostbyaddr($tmp['ip']);	
		}
		else{
			$tmp['hostname']=$host;
			$tmp['ip']=gethostbyname($tmp['hostname']);
		}
		$id=$tmp['hostname'];

		//save to hosts
		$this->cfg['hosts'][$id]=$tmp;
				
		return $id;
	}

	// ---------------------------------------------------------------------------------------
	public function ChooseTarget(){
		if($host=$this->target){
			$force_selected=true;
			if($host=='all'){
				$choosen='a';
			}
			elseif(!$this->cfg['hosts'][$host]){
				$allows_host_creation=true;
				if($allows_host_creation){	
					$id=$this->_CreateHost($host);
				}
				else{
					$this->_dieError('Invalid Host','hosts');
				}
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
			echo "* Choose Target Host : \n ";
			$choosen	=$this->_Ask($str_choices);
			$id			=$choices[$choosen];
			echo "\n-----------------------------------\n";
		}

		if($choosen == 'a'){
			echo "* Selected Host : ALL HOSTS \n";
			$id=0;
		}
		else{
			$this->_AssignCurrentHostConfig($id);

			if(!$this->flag_json){
				if($this->flag_verbose){
					$this->sh->EchoStyleVerbose();
					$this->_EchoCurrentHost();
					$this->_EchoCurrentConfig();	
					$this->sh->EchoStyleClose();
					echo "\n";
				}
			}
		}

		// confirm -------
		if(!$force_selected){
			echo "\n";
			if(!$this->_AskConfirm()){
				$this->sh->PrintAnswer("Cancelled!");
				exit(0);
			}

			if(!$this->flag_json){
				echo "\n";
			}
		}
		return $id;
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoCurrentHost(){
		$host	=$this->c_host;
		
		//echo "\n";
		echo "* Selected Host      : {$host['id']}\n";
		echo "         + Host Name : {$host['hostname']}\n";
		echo "         + Host IP   : {$host['ip']}\n";
		if($host['serial_port']){
			echo "         + Serial    : {$host['serial_port']}	at {$host['serial_rate']} bauds\n";
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoCurrentConfig(){
		if ($this->c_host['config']){
			$host	=$this->c_host;
			echo "\n* Selected Config    : {$this->c_host['config']}\n";
			$this->_EchoVerbose("Config Parameters  :");
			if($this->flag_verbose){
				$this->sh->EchoStyleVerbose();
			}
			echo $this->_PrettyfyWithTabs($this->cfg['configs'][$host['config']]);
			if($this->flag_verbose){
				$repo_shown=true;
				$this->sh->EchoStyleClose();
			}	
		}
		if(!$repo_shown and $this->c_conf['repo'] ){
			echo "\n* Selected Repo      : {$this->c_conf['repo']}\n";
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _AssignCurrentHostConfig($id,$with_source=false){
		// current host -------------
		$this->_FillHostnameOrIp($id);

		$this->c_host					= $this->cfg['hosts'][$id];
		$this->c_host['id']				= $id;
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
		if(!is_array($this->c_conf) and !$this->c_host['is_created']){
			return $this->_dieError ("Unknown configuration '{$this->c_host['config']}' ",'configs');
		}
		$this->c_conf['repo']			= $this->_ChooseValueToUse('repo');

		// current repo ---------------
		//$this->c_repo	=	$this->cfg['repos'][$this->c_conf['repo']];
		if($this->c_conf['repo']){
			
			$this->orepo=$this->_RequireRepo($this->c_conf['repo'],$with_source);
			if($this->c_conf['2steps']){
				$this->c_conf['firststep_firmware']	=$this->espb_path . $this->orepo->GetFirstStepFirmware();
			}
		}


		// git commands add a little delay, so only use then if needed
		if($with_source and ($this->action=='build' or $this->action=='upload' or $this->flag_build)){
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
				$this->c_host['versions']['commit']	and $version_full	.="{$v}{$this->c_host['versions']['commit']}";
			}
			$this->c_host['versions']['full']	=$version_full;
			$version_full						="{$s}({$version_full})";
		}
		//TODO check $esc_version_short
		$esc_version_short="";
		//$esc_version_short	=str_replace('/','_',$version_short);
		$esc_version_full	=str_replace('/','_',$version_full);
		$this->c_host['firmware_name']	="{$this->c_conf['repo']}{$s}{$this->c_host['config']}{$esc_version_short}{$esc_version_full}";

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
				//TODO: Windows: list Serials Ports
			}
		}

		if(count($found)){
			return $found;
		}
		return false;
	}

	// ---------------------------------------------------------------------------------------
	private function _RequireRepo($name, $with_source=false){
		$repo_path	=$this->cfg['repos'][$name]['path_repo'];
		$class_path	= $this->espb_path_lib."espb_repo_{$name}.php";
		$class_name	= "EspBuddy_Repo_$name";
		// if(!$this->cfg['repos'][$name] and $with_source){
		// 	$this->_dieError ("Unknown repository '$name' ");
		// }
		if(!$repo_path and $with_source){
			$this->_dieError ("You must define the path to your '$name' repo,  in \$cfg['repos']['$name']['path_repo'] ");
		}
		if(!file_exists($class_path)){
			$this->_dieError ("Cant find a '$name' class at : $class_path");
		}
		$this->path_factory_repo="{$this->cfg['paths']['dir_backup']}{$this->factory_dir}/$name/";

		require_once($class_path);
		return new $class_name($repo_path);
	}

	// ---------------------------------------------------------------------------------------
	private function _CreateBackupDir($host){
		$dir	= $this->cfg['paths']['dir_backup'];
		$name	= $this->_getHostBackupDir($host);
		$path="$dir$name/";
		if(!file_exists($path)){
			$this->_EchoVerbose("Created the '$name' folder in the Backup directory");
			mkdir($path);
		}
		return $path;
	}
	// ---------------------------------------------------------------------------------------
	private function _getHostBackupDir($host){
		//$name	= $host['id'] or $name	= $host['hostname'] or $name = $host['ip'] or $name = "_ERROR_";
		$name	= $host['hostname'] or $name = $host['ip'] or $name = "_ERROR_";
		return $name;
	}

	// ---------------------------------------------------------------------------------------
	private function _rotateFirmware($path_new_firmware,$do_rename_build=false){
		$path_firmwares		="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}s/";
		$path_link_cur_firm	="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}.bin";
		$path_link_prev_firm="{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}_previous.bin";

		if(!is_dir($path_firmwares)){
			@mkdir($path_firmwares, 0777, true);
		}

		$keep=$this->prefs['keep_previous'];
		if($keep){
			$echo1="Keep the previous firmware, ";
			@unlink($path_link_prev_firm);
			rename($path_link_cur_firm,$path_link_prev_firm);
		}
		if($list_firmares=$this->_listFirmwares()){
			$i=1;
			krsort($list_firmares);
			foreach($list_firmares as $t => $file){
				if( ($i > $keep +1) or !$keep ){
					unlink($file);
				}
				$i++;
			}
		}
		
		$this->_EchoStepStart("{$echo1}Archive and rotate this new firmware");
		if($this->flag_drymode){
			return true;
		}

		$ok=false;
		//makes path_cur_firmware
		if($do_rename_build){
			$path_cur_firmware="$path_firmwares{$this->c_host['firmware_name']}{$this->prefs['name_sep']}".basename($path_new_firmware);
			$ok = copy($path_new_firmware, $path_cur_firmware);
		}
		else{
			if(strpos($path_new_firmware,$this->path_factory)==0){
				$link_new_firmware=str_replace($this->path_factory,'',$path_new_firmware);
				$link_new_firmware=str_replace('/','_',$link_new_firmware);
			}
			else{
				$link_new_firmware	=basename($path_new_firmware);
			}
			$path_cur_firmware=$path_firmwares.$link_new_firmware;

			if($this->flag_copy){
				@unlink($path_cur_firmware);
				$ok = copy($path_new_firmware, $path_cur_firmware);
			}
			else{
				$ok=$this->_SymlinkRelative($path_cur_firmware, $path_new_firmware);
			}
		}

		if($ok and $ok = $this->_SymlinkRelative($path_link_cur_firm, $path_cur_firmware)){
			$this->c_host['path_firmware']=$path_cur_firmware;
			echo "* Current firmware was set to: ".basename($path_cur_firmware)."\n";
			echo "\n";
			return true;
		}
		$this->_EchoError('Renaming or rotating current firmware failed');
	}

	// ---------------------------------------------------------------------------------------
	private function _getRelativePath($from, $to){	
		$from		= explode('/', $from);
		$to			= explode('/', $to);
		$relPath	= $to;
	
		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} 
			else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} 
				else {
					//$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}

	// ---------------------------------------------------------------------------------------
	private function _listFirmwares(){
		$firm_dir			= "{$this->c_host['path_dir_backup']}{$this->prefs['firm_name']}s/";
		//$mask				="{$firm_dir}{$this->prefs['firm_name']}*.bin";
		$mask				="{$firm_dir}*.bin";
		if($files=glob($mask) and count($files)){
			$time_files=array();
			foreach($files as $file){
				//$time=filemtime($file);
				$stat=lstat($file);	// we want thesymlink date
				$time=$stat['mtime'];
				$time_files[$time]=$file;
			}
			krsort($time_files);
			return $time_files;
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _getVersionBuddyLong(){
		$version="EspBuddby v{$this->espb_version}";
		
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
	private function _ReplaceTags($str, $id=''){
		if($id){
			$this->_AssignCurrentHostConfig($id,true);
		}

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
				elseif	($v=='host_id')		{$str=str_replace('{{'.$v.'}}', $id,	$str);}
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
	//TODO: Windows: make _Ask() work!
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
		list($this->path_bin) = get_included_files();
		$this->action	= $this->args['commands'][1];
		$this->target	= $this->args['commands'][2];
		$this->opt1		= $this->args['commands'][3];
		$this->opt2		= $this->args['commands'][4];

		//global flags
		$this->flag_noconfirm	= (boolean) $this->args['flags']['y'];
		$this->flag_drymode 	= (boolean) $this->args['flags']['d'];
		$this->flag_verbose		= (boolean) $this->args['flags']['v'];
		$this->flag_force		= (boolean) $this->args['flags']['f'];
		$this->flag_debug		= (boolean) $this->args['flags']['D'];

		$this->flag_build		= (boolean) $this->args['flags']['b'];
		$this->flag_prevfirm	= (boolean) $this->args['flags']['p'];
		$this->flag_serial		= (boolean) $this->args['flags']['w'];
		$this->flag_eraseflash	= (boolean) $this->args['flags']['e'];
		$this->flag_skipinter	= (boolean) $this->args['flags']['s'];
		$this->flag_monitor		= (boolean) $this->args['flags']['m'];
		$this->flag_copy		= (boolean) $this->args['flags']['c'];

		$this->flag_json		= (boolean) $this->args['flags']['j'];
		$this->flag_proxy		= (boolean) $this->args['flags']['P'];
		$this->flag_background	= (boolean) $this->args['flags']['B'];

		$this->arg_serial_port	= $this->args['vars']['port'];
		$this->arg_serial_rate	= $this->args['vars']['rate'];
		$this->arg_config		= $this->args['vars']['conf'];
		$this->arg_repo			= $this->args['vars']['repo'];
		$this->arg_firmware		= $this->args['vars']['firm'];
		$this->arg_login		= $this->args['vars']['login'];
		$this->arg_pass			= $this->args['vars']['pass'];
		$this->arg_from			= $this->args['vars']['from'];

		if($this->flag_debug){
			error_reporting(E_ALL & ~E_NOTICE);
		}
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
	private function _PrettyfyWithTabs($arr){
		return $this->_Prettyfy($arr, 0, "       ");
	}

	// ---------------------------------------------------------------------------------------
	private function _PrettyfyNoTabs($arr){
		return $this->_Prettyfy($arr,0,"");
	}

	// ---------------------------------------------------------------------------------------
	private function _Prettyfy($arr, $level=0,$left_prefix=""){
		$out='';
		//$tabs = "     "; //initial margin
		$tabs=$left_prefix; //initial margin

		$step_tabs= "    ";
		$l_step=strlen($step_tabs);
		$len_pad=$l_step * 7 + 1; 

		for($i=0;$i<$level; $i++){
			$tabs .= $step_tabs;
		}
		$tabs .= "- ";
		foreach($arr as $key=>$val){
			if( is_array($val) ) {
				$pad='=';
				$len_pad2=$len_pad +33;
				if($level){
					$pad='.';
					$len_pad2=$len_pad +6;
				}
				$out.= $tabs . str_pad($key." ",$len_pad2, $pad) . "\n";
				$out.= $this->_Prettyfy($val, $level + 1, $left_prefix);
			} 
			else {
				if($val && $val !== 0){
					$out.= $tabs . str_pad($key,$len_pad) . " : " . $val . "\n";
				}
			}
		}
		return $out;
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
		$max_fail=2;
		$out=false;
		$message="Waiting for ESP to be back online";
		if($invert){
			$message="Waiting for ESP to be offline (reboot)";
		}
		$this->sh->EchoStyleWait();
		echo str_pad("$message... ",42);
		if($this->flag_drymode){
			$out=true;
		}
		else{
			$i=1;
			$sep='';
			$seen=0;
			while($i <= $timeout){
				$state=$this->_ping($host);
				$bool=$state;
				$invert and $bool = ! $bool;

				echo "$sep$i";
				$sep=',';
				$i++;

				if($bool){
					$seen++;
					if($seen >= $max_fail){
						$out=true;
						break;
					}
					usleep(500 * 1000);
					continue;
				}
				sleep(1);
			}
		}
		echo " ...\n";
		$this->sh->EchoStyleClose();
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
		//if($verbose) echo "\n";
		$mess	="$char $mess ";
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
	private function _EchoHost($mess){
		$mess=str_pad("#### ".$mess." ", 130, '#');
		$this->sh->EchoStyleHost();
		echo $mess;
		$this->sh->EchoStyleClose();
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoStepEnd(){
		$this->sh->EchoStyleClose();
		echo "\n";
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoVerbosePart($mess,$with_cr=true){
		return $this->_EchoVerbose($mess,$with_cr,false);
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoVerbose($mess,$with_cr=true,$with_prefix=true){
		if(!$this->flag_verbose){
			return;
		}
		if($mess){
			$this->sh->EchoStyleVerbose();
			if($with_prefix) echo "* ";
			echo $mess;
			if($with_cr) echo "\n";
			$this->sh->EchoStyleClose();
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _EchoError($mess){
		if($mess){
			echo "\n";
			$this->sh->PrintError(' ERROR: '.$mess);
			echo "\n";	
		}
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
		$headers or $headers=array("User-Agent: {$this->espb_name}"); //gh need this, else 403
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL			, $url);
		curl_setopt($ch, CURLOPT_HEADER			, false);
		//curl_setopt($ch, CURLOPT_SSLVERSION		, 3); //fix SSL on my old debian
		curl_setopt($ch, CURLOPT_RETURNTRANSFER	, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION	, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT	, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER		, $headers); //array
		
		if($this->os=='win'){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER			, false); 
			// else 
			//curl_setopt($ch, CURLOPT_CAINFO,  getcwd().'/cert/cacert.pem');
			//or in php.ini
			// curl.cainfo = "C:\Users\admin\cer\cacert.pem"
		}
		if($this->flag_debug){
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		}
		$result = curl_exec($ch);
		//print_r(curl_getinfo($ch));
		curl_close($ch);
		return $result;
	}

	// ---------------------------------------------------------------------------------------
	private function _GithubVersionToTag($version=""){
		//$version or $version=$this->espb_version;
		if(!$branch_tags=$this->_latest_tags){
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
		$branch or $branch=$this->espb_gh_branch_main;

		if($tags=$this->_GithubFetchLatestTags()){
			return current($tags[$branch]);
		}
	}	

	private $_latest_tags;
	// ---------------------------------------------------------------------------------------
	private function _GithubFetchLatestTags(){
		if($this->_latest_tags){
			return $this->_latest_tags;
		}
		$url	= "{$this->espb_gh_api_url}/repos/{$this->espb_gh_owner}/{$this->espb_gh_repo}/tags";
		$data	= $this->_curl($url);

		if($data){
			$data=json_decode($data,true);
			foreach( $data as $i => $v){
				$k=$v['name'];
				
				$branch		="undefined";
				if(preg_match('/^v/',$v['name'])){
					$branch		=$this->espb_gh_branch_main;
				}
				elseif(preg_match('/^d/',$v['name'])){
					$branch		=$this->espb_gh_branch_dev;
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
		$commands[]="git fetch -f --all --tags --prune";
		$commands[]="git checkout -f $tag";
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
	private function _Git_GitHistory($dir,$vers1='',$vers2=''){
		if(!$dir){
			return false;
		}
		if(!$tag1=$this->_GithubVersionToTag($vers1)){
			$l=" latest";
			$tag1=$this->_GithubFetchLatestTag();
		}
		if(!$tag2=$this->_GithubVersionToTag($vers2)){
			//current version
			$c=" current";
			$tag2=$this->_GithubVersionToTag($this->espb_version);
		}
				
		$this->sh->PrintAnswer("Logs between{$l} {$tag1['version']} ({$tag1['branch']}) and{$c} {$tag2['version']} ({$tag2['branch']}) versions:");
		$commands[]="git fetch --all --tags --prune";
		$commands[]="git log --pretty=format:\" -  %cd %Cblue%h %Creset%s\" --date=short {$tag1['commit']}...{$tag2['commit']}";
		return $this->_Git($commands, $dir);
	}	

	// ---------------------------------------------------------------------------------------
	private function _Git($git_command, $path_base=""){
		$path_base or $path_base	= $this->orepo->GetPathBase();
		$commands[]	= " cd {$path_base}";

		if(is_array($git_command)){
			$commands=array_merge($commands,$git_command);
		}
		else{
			$commands[]	= $git_command;
		}
		
		$sep=" \n ";
		if($this->os=='win'){
			$sep=" && ";	// windows only (!!!) execute the first line so we have to put all on one single line
		}
		$command=implode($sep, $commands);
		
		$err=$this->_passthru($command);
		return !$err;
	}

	// ---------------------------------------------------------------------------------------
	private function _passthru($command){
		$print_command=$command;
		if($this->os=='win'){
			$print_command=str_replace(" && "," \n ",$command);
		}
		
		if($this->flag_drymode){
			$this->sh->PrintCommand($print_command);
			return 0;
		}
		else{
			if($this->flag_verbose){
				echo "$print_command\n";
			}
			$this->sh->EchoStyleCommand();
			passthru($command, $return);
			$this->sh->EchoStyleClose();
			echo "\n";
			return $return;
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _makeUsage($command, $action=''){
		$usage				=$this->actions_usage['root'][$command];
			$action and  $usage	=$this->actions_usage[$command][$action];
		if( !$action and is_array($this->actions_usage[$command])  ){
			$usage='';
		}
		if($usage ){
			$out="$this->bin $command ";
			$action and $out .="$action ";
			$out.="$usage";
			return $out;
		}
	}

	// ---------------------------------------------------------------------------------------
	var $_bash_commands=array();
	var $_bash_found	=0;
	var $_bash_max		=2;
	var $_bash_last_line='';

	// ---------------------------------------------------------------------------------------
	private function _BashAutoCompleteCallback($input, $index){
		$info=readline_info();
		$line=trim($info['line_buffer']);

		if($line and $line != $this->_bash_last_line){
			// if we call readline_list_history() it would break autocomplete, so....
			readline_add_history($line);
			$this->_bash_last_line=$line;
		}

		$words	=preg_split("#\s+#", $line);
		$first	=current($words);
		$last	=end($words);

		if(count($words) <2 and $this->_bash_found){
			$this->_bash_found=0;			
		}

		if($this->actions_desc['root'][$first]){
			$this->_bash_found=1;
			$this->_bash_commands[1]=$first;
		}
		if($this->_bash_found==1 and $this->actions_desc[$this->_bash_commands[1]][$last] ){
			$this->_bash_found=2;
			$this->_bash_commands[2]=$last;
		}

		if($this->_bash_found==2){
			if($usage=$this->_makeUsage($this->_bash_commands[1], $this->_bash_commands[2] )){
				echo "\nUsage: $usage";
				return array();	
			}
		}
		elseif($this->_bash_found==1){
			if($usage=$this->_makeUsage($this->_bash_commands[1] )){
				echo "\nUsage: $usage";
				return array();
			}
			return array_keys($this->actions_desc[$this->_bash_commands[1]]);
		}
		elseif($this->_bash_found==0){
			return array_keys($this->actions_desc['root']);
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _BashAutoCompleteReadCommandAndAction($function_to_callback, $query=''){
		readline_completion_function(array('self', $function_to_callback));

		if($this->os=='osx'){
			$query=$this->sh->StyleBold($query);
		}
		$command_input = readline("###### ". $query." ");		

		//TODO: if possible, add  $last_line="{$this->bin} $command_input"; to bash history
		return $command_input;
	}

	//private $_autocomplete_list=array();
	// ---------------------------------------------------------------------------------------
	private function _BashAutoComplete($index1){

		// TODO: Linux: fix NOT proposing the second action (work well in OSX and WIndows)
		if($this->os=='lin'){
			return false;
		}

		$this->_show_action_desc($index1);
		
		global $argv;
		$last_command	=preg_replace('#^(/[^/]+)+/#','',$argv[0]);
		$input			= $this->_BashAutoCompleteReadCommandAndAction("_BashAutoCompleteCallback",$last_command);

		$new_args=preg_split("/\s+/",trim($input));
		array_unshift($new_args, $argv[0]);
		$argv=$new_args;
		return true;
	}

	// ---------------------------------------------------------------------------------------
	function _IpAddressToMAC($ip){
		$this->_ping($ip);	//put in ARP cache
		if($this->os=='win'){
			$command="arp -a | findstr \"$ip\" ";
			$raw	=trim(shell_exec($command));
			$raw_example=<<<EOF
10.1.1.1              74-d4-35-1b-fd-72     dynamique
10.1.10.1             00-50-56-01-01-01     dynamique
10.1.11.1             0c-e8-6c-68-4c-7c     dynamique
10.1.100.101          00-50-56-00-01-01     dynamique
10.1.255.255          ff-ff-ff-ff-ff-ff     statique

EOF;
			if($raw){
				$lines=preg_split("/[\n\r]+/", $raw);
				$first_line=current($lines);
				list($l_ip,$mac,$type)=preg_split("/\s+/", $first_line);
				$mac=str_replace('-',':',$mac);	
			}
		}
		else{
			$command="arp -an | grep $ip | awk '{print \$4}' | head -1";
			$mac=trim(shell_exec($command));
		}
		//validate mac address
		if(preg_match("/^([a-z0-9]{1,2}:){5}[a-z0-9]{1,2}$/i",$mac)){
			$parts=explode(':',$mac);
			foreach($parts as $k => $v){
				$parts[$k]=str_pad($v,'2','0',STR_PAD_RIGHT);
			}
			$pretty_mac=strtolower(implode(':',$parts));
			return $pretty_mac;
		}	
	}

	// ---------------------------------------------------------------------------------------
	private function _GetDirOrFileTime($path){
		if(is_dir($path)){
			$path=rtrim($path,'/').'/';
			//$files=array_diff(scandir($path), array('..', '.'));
			$files=glob($path.'*'); // no hidden files			
			//echo "GET date: $path : "; print_r($files);echo "\n\n";
			if(count($files)){				
				$first_file=reset($files);
				return filemtime($first_file);
			}
		}
		return filemtime($path);
	}

	// ---------------------------------------------------------------------------------------
	private function _SymlinkRelative($path_link, $path_to){
		$path_link=rtrim($path_link,'/'); //else rel path will be wrong
		if(!$path_link or !$path_to){
			return false;
		}

		$cur_dir=getcwd();
		if($target=$this->_getRelativePath($path_link, $path_to) and $link=basename($path_link) and $link_dir=dirname($path_link) ){
			$this->_EchoVerbose("Symlink $link	-> $target");
			chdir($link_dir);
			//echo " PATH: $path_link\n TO  : $to\n CD  : $link_dir\n LINK: $link\n TARG: $target\n";
			if(is_link($link)){ // else the target is created inside the link destination
				unlink($link);	//echo "rm link $link\n";
			}
			symlink($target,$link); //
			//passthru("ln -sFi \"$target\" \"$link\" ");			
			chdir($cur_dir);
			return true;
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _ListFilesByDate($path_to_dir,$mode=''){
		$path_to_dir=rtrim($path_to_dir,'/');
		if(is_dir($path_to_dir)){
			$files=glob($path_to_dir.'/*'); // no hidden files			
			if(is_array($files) and count($files)){
				$tmp=array();
				foreach($files as $path_file){
					$time=$this->_GetDirOrFileTime($path_file);
					if($mode='dir' and is_dir($path_file) and !is_link($path_file)){	// and !is_link($path_file)
						$tmp[$time]=$path_file;
					}
					elseif($mode=='file' and is_file($path_file) and !is_link($path_file)){ // and !is_link($path_file)
						$tmp[$time]=$path_file;
					}
					elseif(!$mode){
						$tmp[$time]=$path_file;
					}
				}
			}
			if(count($tmp)){
				krsort($tmp);
				return $tmp;
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _SymlinkLatestFromDir($mode='dir', $path_link, $path_to_dir, $previous=0){
		$path_to_dir=rtrim($path_to_dir,'/');
		if($tmp=$this->_ListFilesByDate($path_to_dir,$mode)){
			$previous=intval($previous);
			for ($i=0; $i < $previous ; $i++) { 
				array_shift($tmp);
			}
			$last_file=reset($tmp);
			if($last_file){
				return $this->_SymlinkRelative($path_link, $last_file);
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _SymlinkLatestAndPrevious($path_dir=''){
		$path_dir or $path_dir=$this->path_factory_repo;
		if(is_dir($path_dir)){
			$this->_SymlinkLatestFromDir('dir',$path_dir.$this->latest_link,	$path_dir);
			$this->_SymlinkLatestFromDir('dir',$path_dir.$this->previous_link,	$path_dir, 1);
		}
	}



	// ##################################################################################################################################
	// ##### TEST zone ##################################################################################################################
	// ##################################################################################################################################


	// ---------------------------------------------------------------------------------------
	public function Command_test(){
		echo "#### TEST zone ##############################\n";
		//$this->_RequireRepo('espurna');
		//$this->_SymlinkLatestAndPrevious();
		//return $this->_bgServerStartSafe();
	}
	// ---------------------------------------------------------------------------------------
	private function _bgServerStartSafe(){
		
		if($this->_bgServerCheck()){
			echo "* Another server is already running on port {$this->prefs['server_port']}\n";
			echo "* Do you want to kill it first? ";
			if(!$this->_AskConfirm()){
				return true;
			}
			if(! $this->_bgServerStop()){
				echo "# ERROR: Cant' stop server!\n";
				return false;
			}
		}
		return $this->_bgServerStart();
	}


	// ---------------------------------------------------------------------------------------
	private function _bgServerStart($kill_on_shutdown=true) {
		//exec('nohup php {$this->path_bin} server > nohup.out & > /dev/null');
		$command="nohup {$this->path_bin} server > /dev/null 2> /dev/null & echo $!";
		//echo "$command\n";
		$this->_EchoVerbose("Launching builtin WebServer in the background...");

		$this->server_pid=trim(shell_exec($command));
		if($kill_on_shutdown){
			register_shutdown_function(array($this,'_bgServerStop'));
		}
		return $this->server_pid;
	}

	// ---------------------------------------------------------------------------------------
	public function _bgServerStop($echo=false) { //need to be public (for register_shutdown_function)
		if(!$this->server_pid){
			exec("ps a | grep {$this->bin} | grep server | grep -v grep", $r);
			$r = array_filter(explode(' ', $r[0]));
			$this->server_pid = $r[0];
		}
		if($this->server_pid){
			if($echo) $this->_EchoVerbose("Stopping builtin WebServer...");
			return ! shell_exec("pkill -TERM -P {$this->server_pid}");
		}
	}

	// ---------------------------------------------------------------------------------------
	private function _bgServerCheck() {
		$command='netstat -an | grep -E "^tcp4\s+[^*]+\*\.'.$this->prefs['server_port'].'\s+.*?LISTEN"';
		$r=trim(shell_exec($command));		
		if($r){
			return true;
		}
		
	}



	// ##################################################################################################################################
	// ##### Static ####################################################################################################################
	// ##################################################################################################################################

	// ---------------------------------------------------------------------------------------
	public static function FormatBytes($bytes, $precision = 2,$with_space=true) { 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 
		$space="";
		$with_space and $space=" ";
		return number_format(round($bytes, $precision),$precision) .$space. $units[$pow]; 
	} 
	
	// ---------------------------------------------------------------------------------------
	public static function GetUserAgent(){
		$o = new self;
		return $o->espb_name.' (v'.$o->espb_version.')';
	}

	// ---------------------------------------------------------------------------------------
	public static function GetFactoryDirName(){
		$o = new self;
		return $o->factory_dir;
	}

}
?>