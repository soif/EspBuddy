# Esp Buddy 

- _Tired of typing very long commands to upload your custom firmwares?_ 
- _Bored to manually uplooad your firmwares in two steps for 1MB devices?_ 
- _Want to batch upload new firmwares to all your devices via OTA or backup all settings in one command?_ 

This script allows you to easily upload firmwares to remote (ESP8266 based) devices via Wifi (Over The Air) or Serial, in one short command.
It also gathers various tool commands to be used in batch mode.


## Features
 - OTA upload on 4M devices
 - OTA upload on 1M devices using an intermediate firmware (automatic two steps)
 - Use configuration presets for devices
 - Optional compilation using platformio
 - Optionally pass various -D flags to the compiler, including extracted parameters like IP or hostname
 - Fetch versions of remote devices
 - Archive current firmware & previous firmware per target
 - Backup current settings & previous settings per target
 - Parse Repositories' installed versions
 - Git Pull Repositories
 - Ping Remote Host
 
## Supported (tested) Firmwares
Works with :
- [**ESPeasy**](https://github.com/letscontrolit/ESPEasy/)
- [**Espurna**](https://github.com/xoseperez/espurna)
- [**Tasmota**](https://github.com/arendst/Sonoff-Tasmota/)
- should virtually work with any ESP8266 firmware, just add a small espb_repo_xxx class to describe it.

## Requirements
- Linux or OSX Operating System
- php5 or newer
- platformio

## Installation
- Rename _config-sample.php_ to _config.php_.
- Fill in some hosts and configurations in config.php

## Usage

**espbuddy.php ACTION [TARGET] [OPTIONS]**

Valid Actions are: 
  - **upload**          : Build and/or Upload current repo version to Device(s)
  - **build**           : Build current repo version
  - **backup**          : Backup remote devices' settings
  - **monitor**         : Monitor the serial port
  - **version**         : Show Device(s) Version
  - **ping**            : Ping Device(s)
  - **repo_version**    : Show Repo's Current version
  - **repo_pull**       : Git Pull Repo's master version
  - **list_hosts**      : List all available hosts
  - **list_configs**    : List all available configurations
  - **list_repos**      : List all available repositories
  - **help**            : Show full help


Examples:
- `espbuddy.php upload` select the one to upload to from the list of targets
- `espbuddy.php upload relay1` upload to target 'relay1'
- `espbuddy.php upload all -b` upload to all defined targets, while building the firmware first 
- `espbuddy.php upload relay1 -w` upload using serial to target 'relay1'
- `espbuddy.php backup all` backup settings all defined targets
- `espbuddy.php monitor relay1 --rate=9600` serial monitor target 'led1' at 9600 bauds
- `espbuddy.php version all` show versions of all defined targets
- `espbuddy.php ping all` ping the all defined targets
