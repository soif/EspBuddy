# Esp Buddy

- _Tired of typing very long commands to upload your custom firmwares?_
- _Bored to manually upload your firmwares in two steps for 1MB devices?_
- _Want to batch upload new firmwares to all your devices via OTA or backup all settings in one command?_
- _Need a solid tool to discover, control or flash Sonoff DIY devices (ie Sonoff Mini)_

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
- Parse Repositories installed versions
- Git Pull Repositories
- Ping Remote Host
- Sonoff DIY: scan network for devices in DIY mode
- Sonoff DIY: total control using the Ithead factory API.
- Sonoff DIY: replace the factory firmware with any other one (default to Tasmota) OTA. (see [#20](https://github.com/soif/EspBuddy/issues/20) )
- Sonoff DIY: cross-platform support (Mac, Win, Linux)

## Supported Firmwares

Works with :

- [**ESPeasy**](https://github.com/letscontrolit/ESPEasy/)
- [**Espurna**](https://github.com/xoseperez/espurna)
- [**Tasmota**](https://github.com/arendst/Sonoff-Tasmota/)
- should virtually work with any ESP8266 firmware: *just add a small **espb_repo_xxx** class to describe it.*

## Requirements

- Linux or OSX Operating System (+ Windows for some method only)
- php5 or newer
- PlatformIO __needed only for compiling__

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
- **reboot**          : Reboot remote devive
- **gpios**           : Test (On/Off) each GPIOs
- **ping**            : Ping Device(s)
- **sonodiy**         : Discover, Control or Flash Sonoff devices in DIY mode
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
- `espbuddy.php backup all` backup settings ofall defined targets
- `espbuddy.php monitor relay1 --rate=9600` serial monitors  'relay1' target at 9600 bauds
- `espbuddy.php version all` show versions of all defined targets
- `espbuddy.php ping all` ping the all defined targets
- `espbuddy.php sonodoy flash 192.168.1.10 1000abc1ef` flashes a (default) Tasmota firmware into a Sonoff Mini in DIY mode

## Contribute

Whether you are a developer or a regular user, [your help is most welcome](.github/CONTRIBUTING.md)!

## Licence

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
