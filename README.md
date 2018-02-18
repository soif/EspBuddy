# Esp Buddy 

This script allows to easely upload sketches to remote ESP8266 based devices using Wifi (Over The Air) or Serial

_This is typically a **WORK IN PROGRESS :-) !!!**_


## Features
 - OTA upload, even on small ROM devices (two steps upload)
 - use config preset for devices
 - optional prior compilation using platformio
 
## Supported Platforms
Works great with :
- [**ESPeasy**](https://github.com/letscontrolit/ESPEasy/)	: Tested 
- [**Espura**](https://github.com/xoseperez/espurna) 		: Some small tweaks still needed

## Install
- Rename _config-sample.php_ to _config.php_.
- Fill some host and configuration in config.php

## Usage

espbuddy.php COMMAND [OPTIONS]

Examples:
- `espbuddy.php help` show help
- `espbuddy.php upload` choose from the list of host the one to upload to
- `espbuddy.php upload --host=led1 -c` upload to host 'led1', while compiling the firmware first 
- `espbuddy.php upload --host=all -c` upload to all defined hosts , while compiling the firmware first 
- `espbuddy.php upload --host=led1 -w` upload using serial to host 'led1'
- `espbuddy.php version --host=all` show versions of all defined hosts
- `espbuddy.php ping --host=all` ping of all defined hosts

