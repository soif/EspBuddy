/*
	STRIPPED DOWN VERSION or the sonoff/settings.io !!!!!!!!!!!!!!!!

*/

/*
  settings.ino - user settings for Sonoff-Tasmota

  Copyright (C) 2018  Theo Arends

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


/*********************************************************************************************\
 * Config - Flash
\*********************************************************************************************/

extern "C" {
#include "spi_flash.h"
}
#include "eboot_command.h"

extern "C" uint32_t _SPIFFS_end;

// From libraries/EEPROM/EEPROM.cpp EEPROMClass
#define SPIFFS_END          ((uint32_t)&_SPIFFS_end - 0x40200000) / SPI_FLASH_SEC_SIZE

// Version 3.x config
#define SETTINGS_LOCATION_3 SPIFFS_END - 4

// Version 4.2 config = eeprom area
#define SETTINGS_LOCATION   SPIFFS_END  // No need for SPIFFS as it uses EEPROM area
// Version 5.2 allow for more flash space
#define CFG_ROTATES         8           // Number of flash sectors used (handles uploads)

uint32_t settings_hash = 0;
uint32_t settings_location = SETTINGS_LOCATION;

/********************************************************************************************/

/*********************************************************************************************\
 * Config Save - Save parameters to Flash ONLY if any parameter has changed
\*********************************************************************************************/

void SettingsLoad()
{
/* Load configuration from eeprom or one of 7 slots below if first load does not stop_flash_rotate
 */
  struct SYSCFGH {
    unsigned long cfg_holder;
    unsigned long save_flag;
  } _SettingsH;

  settings_location = SETTINGS_LOCATION +1;
  for (byte i = 0; i < CFG_ROTATES; i++) {
    settings_location--;
    noInterrupts();
    spi_flash_read(settings_location * SPI_FLASH_SEC_SIZE, (uint32*)&Settings, sizeof(SYSCFG));
    spi_flash_read((settings_location -1) * SPI_FLASH_SEC_SIZE, (uint32*)&_SettingsH, sizeof(SYSCFGH));
    interrupts();

//  snprintf_P(log_data, sizeof(log_data), PSTR("Cnfg: Check at %X with count %d and holder %X"), settings_location -1, _SettingsH.save_flag, _SettingsH.cfg_holder);
//  AddLog(LOG_LEVEL_DEBUG);

    if (((Settings.version > 0x05000200) && Settings.flag.stop_flash_rotate) || (Settings.cfg_holder != _SettingsH.cfg_holder) || (Settings.save_flag > _SettingsH.save_flag)) {
      break;
    }
    delay(1);
  }
  // snprintf_P(log_data, sizeof(log_data), PSTR(D_LOG_CONFIG D_LOADED_FROM_FLASH_AT " %X, " D_COUNT " %d"),  settings_location, Settings.save_flag);
  // AddLog(LOG_LEVEL_DEBUG);
  if (Settings.cfg_holder != CFG_HOLDER) {
    // Auto upgrade
    noInterrupts();
    spi_flash_read((SETTINGS_LOCATION_3) * SPI_FLASH_SEC_SIZE, (uint32*)&Settings, sizeof(SYSCFG));
    spi_flash_read((SETTINGS_LOCATION_3 + 1) * SPI_FLASH_SEC_SIZE, (uint32*)&_SettingsH, sizeof(SYSCFGH));
    if (Settings.save_flag < _SettingsH.save_flag)
      spi_flash_read((SETTINGS_LOCATION_3 + 1) * SPI_FLASH_SEC_SIZE, (uint32*)&Settings, sizeof(SYSCFG));
    interrupts();
    if ((Settings.cfg_holder != CFG_HOLDER) || (Settings.version >= 0x04020000)) {
      //SettingsDefault();
    }
  }

  //settings_hash = GetSettingsHash();

  //RtcSettingsLoad();
}

