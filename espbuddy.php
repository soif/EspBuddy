#!/usr/bin/php
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

$config_file=dirname(__FILE__).'/config.php';

if(!file_exists($config_file)){
	dieError("Configuration File Not Found at $config_file !!!\nPlease copy config-sample.php to config.php, set it to your environment, and try again.");
}
require($config_file);


// Main ###############################################################################################################################

SwitchCommand($argv[1]);
exit(0);


// ---------------------------------------------------------------------------------------
function SwitchCommand($command){
	global $cfg;//,$argv;
	//$cfg['args']=ParseArguments($argv);

	switch ($command) {
		case 'upload':
			ProcessCommand($command, ChooseTarget());
			break;

		case 'serial':
			ProcessCommand($command, ChooseTarget());
			break;

		case 'compil':
			ProcessCommand($command, ChooseTarget());
			break;

		case 'version':
			ProcessCommand($command, ChooseTarget());
			break;

		case 'ping':
			ProcessCommand($command, ChooseTarget());
			break;

		case 'repo_version':
			Command_repo('version');
			break;

		case 'repo_pull':
			Command_repo('pull');
			break;

		case 'help':
			Command_help();
			break;
	
		default:
			echo "Invalid Command!\n";
			Command_usage();
			global $argv;
			echo "* Use '{$argv[0]} help' to list all options\n";
			echo "\n";
			break;
	}
}
echo "\n";

// ---------------------------------------------------------------------------------------
function ListHosts($id){
	global $cfg;
	if($id == '0'){
		$ret=$cfg['hosts'];
	}
	else{
		$ret=array($id => $cfg['hosts'][$id]);
	}
	return $ret;	
}

// ---------------------------------------------------------------------------------------
function ProcessCommand($command, $id){
	global $cfg;
	if(ArgHasFlag('d')){
		$in_drymode=" in DRY MODE";
	}
	
	$hosts=ListHosts($id);
	$c=count($hosts);
	echo "Processing $c host(s)$in_drymode : \n";
	foreach($hosts as $this_id => $host){
		$name=str_pad(FillHostnameOrIp($this_id), 30);
		echo "\033[35m############ $name : \033[0m";
		if($c==1){echo "\n";}
		$fn="Command_$command";
		echo $fn($this_id);
		echo "\n";
	}
}

// ---------------------------------------------------------------------------------------
function ReplaceTags($str, $id){
	global $cfg;
	$host	=	$cfg['hosts'][$id];
	$ip		=	$host['ip'];
	list($ip1,$ip2,$ip3,$ip4)=explode('.',$ip);	
	$fqdn	=	$host['hostname'];
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
function Command_compil($id){
	global $cfg;
	$host	=	$cfg['hosts'][$id];
	$conf	=	$cfg['configs'][$host['config']];
	$repo	=	$cfg['repos'][$conf['repo']];
	$env	=	$repo['environments'][$conf['environment']];

	if(!is_array($conf)){
		return dieError ("Unknown Configuration ({$host['config']})!");
	}
	echo "\n";

	$dry_mode=ArgHasFlag('d');

	// compil
	$commands_compil[]="cd {$repo['path_code']} ";
	if(is_array($conf['exports'])){
		foreach( $conf['exports'] as $k => $v ){
			$commands_compil[]	=ReplaceTags("export $k='$v'", $id);
		}
	}
	$commands_compil[]="{$cfg['paths']['bin_pio']} run -e {$conf['environment']}";
	$command=implode('; ', $commands_compil);
	EchoStepStart("Compiling {$conf['repo']} : {$conf['environment']}", $command);
	if($dry_mode){
		return true;
	}
	else{
		passthru($command, $r);
		return !$r;
	}
}

// ---------------------------------------------------------------------------------------
function Command_serial($id,$action='write_flash'){
	global $argv;
	if(!$action=$argv[2]){
		dieError("No action specified!");
	}
	DoSerial($id, $action);
}


// ---------------------------------------------------------------------------------------
function DoSerial($id,$action='write_flash'){
	global $cfg;
	$host	=	$cfg['hosts'][$id];
	$conf	=	$cfg['configs'][$host['config']];
	$repo	=	$cfg['repos'][$conf['repo']];
	$env	=	$repo['environments'][$conf['environment']];

	$dry_mode=ArgHasFlag('d');

	$serial_port		=ArgGetOption('port')							or
		$serial_port	= $cfg['serial_ports'][$host['serial_port']]	or
		$serial_port	= $host['serial_port']							or
		$serial_port	= $cfg['serial_ports'][$conf['serial_port']]	or
		$serial_port	= $conf['serial_port']							or
		$serial_port	= $cfg['serial_ports']['default'];

	if(!$serial_port){
		return dieError ("No Serial Port choosen!");
	}

	$serial_rate		=ArgGetOption('rate')							or
		$serial_rate	= $cfg['serial_rates']['default'];
	
	$serial_rate and 
		$arg_rate=" -b {$serial_rate}" and
		$echo_rate=", Rate: $serial_rate bauds";

	$command="{$cfg['paths']['bin_esptool']} -p $serial_port$arg_rate $action ";

	switch ($action) {
		case 'write_flash':
			$command .="0x0 {$repo['path_code']}.pioenvs/{$conf['environment']}/firmware.bin ";
			break;
		case 'erase_flash':
			break;
		case 'read_mac':
			break;
		default:
			return dieError ("Invalid Action!");
			break;
	}
	EchoStepStart("Serial Action: $action (Port: $serial_port$echo_rate)",$command);
	
	if(!$dry_mode){
		passthru($command, $r);
		if($r){
			return dieError ("Serial Upload FAILED!!");
		}	
	}
}

// ---------------------------------------------------------------------------------------
function Command_upload($id){
	global $cfg;
	$host	=	$cfg['hosts'][$id];
	$conf	=	$cfg['configs'][$host['config']];
	$repo	=	$cfg['repos'][$conf['repo']];
	$env	=	$repo['environments'][$conf['environment']];
	$ip		=	$host['ip'];

	$dry_mode=ArgHasFlag('d');

	if(!is_array($conf)){
		return dieError ("Unknown Configuration ({$host['config']})!");
	}

	// compil
	if(ArgHasFlag('c')){
		if(! Command_compil($id)){
			dieError ("Compilation FAILED!!");
		}
	}

	// wire mode ---------------------------------------------
	if(ArgHasFlag('w')){
		if(ArgHasFlag('e')){
			DoSerial($id,'erase_flash');
			WaitReboot(5);
		}
		DoSerial($id,'write_flash');
	}
	// OTA mode ----------------------------------------------
	else{
		// two steps  upload ?
		if($env['2steps_firmware'] and ! ArgHasFlag('s') ){

			$command	="{$cfg['paths']['bin_esp_ota']} -r -d -i $ip  -f {$env['2steps_firmware']}";
			EchoStepStart("Uploading Intermediate Uploader Firmware",$command);
			
			if(!$dry_mode){
				passthru($command, $r);
				if($r){
					return dieError ("First Upload FAILED!!");
				}	
			}
			if($env['2steps_delay']){
				WaitReboot($env['2steps_delay']);
			}
		}

		// Final Upload
		$command	="{$cfg['paths']['bin_esp_ota']} -r -d -i $ip  -f {$repo['path_code']}.pioenvs/{$conf['environment']}/firmware.bin ";
		EchoStepStart("Uploading Final Firmware",$command);

		if(!$dry_mode){
			passthru($command, $r);
			if($r){
				return dieError ("First Upload FAILED!!");
			}	
		}
	}
}

// ---------------------------------------------------------------------------------------
function Command_version($id){
	global $cfg;
	$conf=	$cfg['hosts'][$id]['config'];;
	$repo=	$cfg['configs'][$conf]['repo'];;

	switch ($repo) {
		case 'espurna':
			dieError( __FUNCTION__ . " for $repo is not YET Implemented");			
			break;

		case 'espeasy':
			$ip=$cfg['hosts'][$id]['ip'];
			$url="http://$ip/json";
			$json=@file_get_contents($url);
			if($json and $arr=json_decode($json,true) and is_array($arr)){
				echo $arr['System']['Build']."\n";
			}
			break;
		
		default:
			dieError ("Unknown repo ($repo)!");
			# code...
			break;
	}
}


// ---------------------------------------------------------------------------------------
function Command_repo($type){
	global $argv, $cfg;
	$repo_key=$argv[2];
	$repo=$cfg['repos'][$repo_key];
	if(!$repo){
		dieError("Unknown Repo '$repo_key'. Possible repos are : ".implode(', ',array_keys($cfg['repos'])) );
	}
	if($type == "version"){
		echo "* The $repo_key repositary version is : ";
		if($repo['reg_version'] and $repo['path_version']){
			$reg	=$repo['reg_version'][0];
			$reg_n	=$repo['reg_version'][1];
			preg_match($reg,file_get_contents($repo['path_version']),$matches);
			echo $matches[$reg_n];
		}
		echo "\n";
	}
	if($type == "pull"){
		Command_repo('version');
		echo "* Loading $repo_key git Commits : \n";
		$command="cd {$repo['path_repo']} ; git pull 2>&1";
		echo "$command\n";
		passthru($command, $r);

		Command_repo('version');		
		echo "\n";
	}
}


// ---------------------------------------------------------------------------------------
function Command_ping($id,$count=0){
	global $cfg;
	$ip= $cfg['hosts'][$id]['ip'];
	if(!$count){
		$count	=4;
		$opt	="-o ";
	}
	$opt .="-c $count";
	$command="ping $opt -n -W 2000 -q $ip 2> /dev/null | grep loss";
	$result	=trim(shell_exec($command));
	$result	=str_replace('packet loss',	'loss',	$result);	
	$result	=str_replace('packets transmitted',	'sent',	$result);	
	$result	=str_replace('packets received',		'rcv',	$result);
	
	if		(preg_match('# 0.0% loss#', 	$result))	{$result .="\t\t OK";}	
	elseif	(preg_match('# 100.0% loss#',	$result))	{$result .="\t\t Offline";}	
	else												{$result .="\t\t -";}	
	return $result;
}

// ---------------------------------------------------------------------------------------
function Command_help(){
	global $argv;
	$bin= basename($argv[0]);
	Command_usage();
	echo <<<EOF

* upload (command) : 
	USAGE   : $bin upload [OPTIONS] [UPLOAD_OPTIONS]
	Desc    : Upload to the board using OTA as default
	Example : $bin upload

* compil (command) : 
	USAGE   : $bin compil [OPTIONS] 
	Desc    : Build the firmware
	Example : $bin compil

* version (command) : 
	USAGE   : $bin version  [OPTIONS]
	Desc    : Get the board installed version
	Example : $bin version

* ping (command) : 
	USAGE   : $bin ping [OPTIONS]
	Desc    : Ping board
	Example : $bin ping

* repo_version (command) : 
	USAGE   : $bin repo_version REPO
	Desc    : Parse the current repository (REPO) version. REPO is a supported repository (espurna or espeasy)
	Example : $bin repo_version espurna

* repo_pull (command) : 
	USAGE   : $bin repo_pull REPO
	Desc    : Git Pull the local repository (REPO). REPO is a supported repository (espurna or espeasy)
	Example : $bin repo_pull espurna


* OPTIONS :
	-f             : don't confirm choosen host (when no --host provided)
	--host=xxx     : select the host key to be used (or 'all')
	--port=xxx     : serial port to use (overrride main or per host serial ports)
	--rate=xxx     : serial port speed to use (overrride main or per host serial ports)

* UPLOAD_OPTIONS :
	-c  : Compil before Uploading
	-w  : Wire Mode (Serial port) instead of Default OTA
	-e  : In Wire Mode, erase flash first
	-s  : Skip Intermediate Upload (if set)
	-d  : Dry Run. Show commands but don't apply them
	-v  : Verbose


EOF;
}
/*
	-host_name=xxx : override hostname 
	-host_ip=xxx   : override IP Address 

*/
// ---------------------------------------------------------------------------------------
function Command_usage(){
	global $argv;
	$allowed_commands=array(
		'upload'		=> "Build and/or Upload current repo version to Device(s)",
		'compil'		=> "Build current repo version",
		'version'		=> "Show Device(s) Version",
		'ping'			=> "Ping Device(s)",
		'repo_version'	=> "Show Repo's Current version", 
		'repo_pull'		=> "Git Pull Repo's master version",
		'help'			=> "Show full help"
		);

	echo "* Usage: {$argv[0]} COMMAND [OPTIONS]\n";
	echo "\n";
	echo "* Valid Commands are: \n";
	//echo implode("\n  - ",$allowed_commands);
	foreach($allowed_commands as $k => $v){
		echo "  - ".str_pad($k,15)." : $v\n";
	}
	echo "\n";
}

// ---------------------------------------------------------------------------------------
function dieError($mess){
	echo "\n";
	die("\033[31mERROR: $mess\033[0m\n\n");
}

// ---------------------------------------------------------------------------------------
function EchoStepStart($mess, $command, $do_end=1,$char="*"){
	if(ArgHasFlag('v')){
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
		$do_end and EchoStepEnd();
	}
}
// ---------------------------------------------------------------------------------------
function EchoStepEnd(){
	echo "\033[0m\n";
}

// ---------------------------------------------------------------------------------------
function WaitReboot($sleep){
	EchoStepStart("Waiting  {$sleep} sec for ESP to reboot",'',0);
	$dry_mode=ArgHasFlag('d');
	if(!$dry_mode){
		while($sleep){
			sleep(1);
			echo "$sleep ";
			$sleep--;
		}
	}
	echo " ********\n";
}

// ---------------------------------------------------------------------------------------
function ArgHasFlag($flag){
	global $cfg, $argv;
	if(!is_array($cfg['args'])){
		$cfg['args']=ParseArguments($argv);
	}
	return in_array($flag, $cfg['args']['flags']);
}

// ---------------------------------------------------------------------------------------
function ArgGetOption($option){
	global $cfg, $argv;
	if(!is_array($cfg['args'])){
		$cfg['args']=ParseArguments($argv);
	}
	return $cfg['args']['options'][$option];
}

// ---------------------------------------------------------------------------------------
function GetCfgParamFromId($class,$key,$param){
	global $cfg;
	return $cfg[$class][$key][$param];
}

// ---------------------------------------------------------------------------------------
function ChooseTarget(){
	global $cfg;

	if($host=ArgGetOption('host')){
		$force_selected=true; 
		if($host=='all'){
			$choosen='a';
		}
		elseif(!$cfg['hosts'][$host]){
			dieError('Invalid Host');
		}
		else{
			$id=$host;
		}
	}

	$choices['a']='All Hosts';
	$str_choices="a # ALL #,";
	$n=1;
	foreach($cfg['hosts'] as $h => $arr){
		$index=chr(97+$n);	//65
		$n++;
		$choices[$index] =$h;
		$name=$arr['hostname'] or $name=$arr['ip'];
		$str_choices .="$index {$name}";
		if($n <= count($cfg['hosts'])){$str_choices .=",";}
	}
	if(!$force_selected){
		echo "Choose Target Host : \n";	
		$choosen	=Ask($str_choices);
		$id			=$choices[$choosen];
	}

	echo "\n-----------------------------------\n";
	echo "You have choosen : ";

	if($choosen == 'a'){
		echo " -> ALL HOSTS \n";
		$id=0;
	}
	else{
		echo "\n";
		FillHostnameOrIp($id);
		$host		=$cfg['hosts'][$id];
		echo " + Host key   : $id \n";
		echo " + Host Name  : {$host['hostname']}\n";
		echo " + Host IP    : {$host['ip']}\n";
		echo " + Config     : {$host['config']}\n";
		echo " + Parameters : \n";
		pretty($cfg['configs'][$host['config']]);
	}

	// confirm -------
	if(!ArgHasFlag('f') and !$force_selected){
		echo "\n";
		echo "Please Confirm : ";
		$confirm=strtolower(Ask("Yes,No",'',", ","? "));
		echo "\n";
		if($confirm=='n'){
			die("--> Cancelled!\n\n");
		}
	}
		echo "\n";
	// export ESPURNA_BOARD="MAGICHOME_LED_CONTROLLER_20"; export ESPURNA_AUTH="Louis140608"; export ESPURNA_IP="10.1.209.3";  pio run -t upload -e esp8266-1m-ota
	return $id;
}

// ---------------------------------------------------------------------------------------
function FillHostnameOrIp($id){
	global $cfg;
	$cfg['hosts'][$id]['ip'] 		or $cfg['hosts'][$id]['ip']			=gethostbyname($cfg['hosts'][$id]['hostname']);
	$cfg['hosts'][$id]['hostname']	or $cfg['hosts'][$id]['hostname']	=gethostbyaddr($cfg['hosts'][$id]['ip']);
	
	return "{$cfg['hosts'][$id]['hostname']} ({$cfg['hosts'][$id]['ip']})";
}

/*
// ---------------------------------------------------------------------------------------
function OtaUpload_espurna($id){
	global $cfg;
	$host	=$cfg['hosts'][$id];

	$params		=$cfg['configs'][$host['config']];
	$exports	=$params['exports'];
	$environment		=$params['environment'];
	
	$commands[]	="export PLATFORMIO_BUILD_FLAGS=\"'-DUSE_CUSTOM_H'\"";
	foreach( $exports as $k => $v ){
		$commands[]	="export $k=\"$v\"";
	}
	$ip=$host['ip'];
	$commands[]	="export ESPURNA_IP='$ip'";
	$commands[]	="pio run -t upload -e $environment";
	
	$txt=implode('; ', $commands);
	
	echo "$txt\n";

}
*/


// ---------------------------------------------------------------------------------------
//http://stackoverflow.com/questions/3684367/php-cli-how-to-read-a-single-character-of-input-from-the-tty-without-waiting-f
function Ask($str_choices='', $force='', $sep="\n", $eol="\n"){
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
//http://php.net/manual/fr/features.commandline.php
function ParseArguments( $args ){
	array_shift( $args );
	$endofoptions = false;

	$ret = array
		(
		'commands' => array(),
		'options' => array(),
		'flags'		 => array(),
		'arguments' => array(),
		);

	while ( $arg = array_shift($args) ){

		// if we have reached end of options,
		//we cast all remaining argvs as arguments
		if ($endofoptions){
			$ret['arguments'][] = $arg;
			continue;
		}

		// Is it a command? (prefixed with --)
		if ( substr( $arg, 0, 2 ) === '--' ){

			// is it the end of options flag?
			if (!isset ($arg[3])){
				$endofoptions = true;; // end of options;
				continue;
			}

			$value = "";
			$com	 = substr( $arg, 2 );

			// is it the syntax '--option=argument'?
			if (strpos($com,'='))
				list($com,$value) = split("=",$com,2);

			// is the option not followed by another option but by arguments
			elseif (strpos($args[0],'-') !== 0){
				while (strpos($args[0],'-') !== 0)
					$value .= array_shift($args).' ';
				$value = rtrim($value,' ');
			}

			$ret['options'][$com] = !empty($value) ? $value : true;
			continue;

		}

		// Is it a flag or a serial of flags? (prefixed with -)
		if ( substr( $arg, 0, 1 ) === '-' ){
			for ($i = 1; isset($arg[$i]) ; $i++)
				$ret['flags'][] = $arg[$i];
			continue;
		}

		// finally, it is not option, nor flag, nor argument
		$ret['commands'][] = $arg;
		continue;
	}

	if (!count($ret['options']) && !count($ret['flags'])){
		$ret['arguments'] = array_merge($ret['commands'], $ret['arguments']);
		$ret['commands'] = array();
	}
	return $ret;
}


// ---------------------------------------------------------------------------------------
// https://stackoverflow.com/questions/1168175/is-there-a-pretty-print-for-php
function pretty($arr, $level=0){
    $tabs = "    ";
    for($i=0;$i<$level; $i++){
        $tabs .= "    ";
    }
    $tabs .= " - ";
    foreach($arr as $key=>$val){
        if( is_array($val) ) {
            print ($tabs . $key . " : " . "\n");
            pretty($val, $level + 1);
        } else {
            if($val && $val !== 0){
                print ($tabs . str_pad($key,22) . " : " . $val . "\n"); 
            }
        }
    }
}

?>