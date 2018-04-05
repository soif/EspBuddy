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
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); // Show Notices
error_reporting(E_ERROR | E_WARNING | E_PARSE); // Only Fatal & Warning errors

$config_file=dirname(__FILE__).'/config.php';

require_once(dirname(__FILE__).'/lib/espbuddy.class.php');
$espbuddy = new EspBuddy();
$espbuddy->LoadConf($config_file);
$espbuddy->CommandLine();
exit(0);
?>