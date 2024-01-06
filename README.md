# Esp Buddy

[![GitHub release](https://img.shields.io/github/tag/soif/EspBuddy.svg)](https://GitHub.com/soif/EspBuddy/releases/)
[![GitHub stars](https://img.shields.io/github/stars/soif/EspBuddy.svg)](https://github.com/soif/EspBuddy/stargazers)
![GitHub last commit](https://img.shields.io/github/last-commit/soif/EspBuddy.svg)
[![GitHub license](https://img.shields.io/github/license/soif/EspBuddy.svg)](https://github.com/soif/EspBuddy/blob/master/LICENSE)
[![Twitter](https://img.shields.io/twitter/url.svg?style=social&url=https%3A%2F%2Fgithub.com%2Fsoif%2FEspBuddy)](https://twitter.com/intent/tweet?text=Wow:&url=https%3A%2F%2Fgithub.com%2Fsoif%2FEspBuddy)

- _Tired of typing very long commands to upload your custom firmwares?_
- _Bored to manually upload your firmwares in two steps for 1MB devices?_
- _Want to batch upload new firmwares to all your devices via OTA or backup all settings in one command?_
- _Want to remotely send commands to pre set some device parameters_
- _Want to build your own firmares, storing them by device, and applying some compilation options_
- _Need a solid tool to discover, control or flash Sonoff DIY devices (ie Sonoff Mini)_

This script allows you to easily upload firmwares to remote (ESP8266 based) devices via Wifi (Over The Air) or Serial, in one short command.
It also gathers various tool commands to be used in batch mode.


## Features

- OTA upload on 4M devices
- OTA upload on 1M devices using an intermediate firmware (automatic two steps)
- Use configuration presets for devices
- Optional compilation using platformio
- Optionally pass various -D flags to the compiler, including extracted parameters like IP or hostname
- Send commands (single or list) to remote devices
- Fetch versions or full status of remote devices
- Archive current firmware & previous firmware built per target
- Backup current settings & previous settings per device
- Download latest released firmwares
- Built-in (browsable) web server to easily upgrade (ie for tasmota) regular or custom firmwares
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
- [**Wled**](https://github.com/Aircoookie/WLED/)
- should virtually work with any ESP8266 firmware: *just add a small **espb_repo_xxx** class to describe it.*

## Requirements

- Linux or OSX Operating System (+ [Windows](doc/install_windows.md) for some method only)
- php v5.4 or newer
- PlatformIO __needed only for compiling__

## Installation

- Rename _config-sample.php_ to _config.php_.
- Fill in some hosts and configurations in config.php

## Usage

**espbuddy ACTION [TARGET] [OPTIONS]**

Valid Actions are:
 
- **flash**           : flash current repo firmware to Device(s) using the serial port
- **ota**             : Upgrade device(s) firmware using 'Arduino OTA'
- **upgrade**         : Upgrade device(s) firmware using our WebServer
- **build**           : Build current repo version
- **backup**          : Backup remote devices' settings
- **monitor**         : Monitor the serial port
- **send**            : Send Command(s)
- **server**          : Launch Firmwares WebServer
- **factory**         : Downloads and sort latest released firmwares
- **status**          : Show Device(s) Information
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
- **self**            : EspBuddy maintenance tools
- **help**            : Show full help


Examples:

- `espbuddy ota` selects the device to upgrade from the list of targets
- `espbuddy ota relay1` upgrade target 'relay1'
- `espbuddy ota all -b` upgrade all defined targets, while building the firmware first
- `espbuddy flash relay1` uploads using serial port to target 'relay1'
- `espbuddy flash relay1 -eb` builds 'relay1', then using the serial port, erase first and flash
- `espbuddy backup all` backups settings of all defined targets
- `espbuddy monitor relay1 --rate=9600` serial monitors  'relay1' target at 9600 bauds
- `espbuddy send relay1 SetOption13 1` Sends the "SetOption13 1" command to 'relay1'
- `espbuddy server` launches the builtin webserver on port 81, serving files from the backup directory
- `espbuddy factory download tasmota latest all` Downloads and links all latest released Tasmota firmwares
- `espbuddy version all` shows versions of all defined targets
- `espbuddy ping all` pings all defined targets
- `espbuddy sonodiy flash 192.168.1.10 1000abc1ef` flashes a (default) Tasmota firmware into a Sonoff Mini in DIY mode

See [more command examples](doc/command_examples.md) ...

## Contribute

Whether you are a developer or a regular user, [your help is most welcome](.github/CONTRIBUTING.md)!

## Licence

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
