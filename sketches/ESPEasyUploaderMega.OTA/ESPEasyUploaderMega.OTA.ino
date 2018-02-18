// This is a bare minimum sketch to upload larger ESP Easy Mega firmware files to ESP modules with only 1MB flash chips
// LetsControlIt - 2017.06.03
// Should work up to 600kB images

// This sketch can be uploaded to 1MB ESP modules using the Mega (SPIFFS-128k) firmware.
// With the default firmware loaded (V2.0.0-dev9 release), these modules will have only 344 kB of free space left to upload images.
// So you will not be able to use OTA again to replace the stock firmware
// Loading the uploader image first, provides 604 kB of free space, so you can upload larger images.
 
// So this will always be a two-step upload proces, but you don't have to resort to serial upload...

// ********************************************************************************
//   DO NOT CHANGE ANYTHING BELOW THIS LINE
// ********************************************************************************
#define ESP_PROJECT_PID           2016110801L
#define VERSION                             2
#define CMD_REBOOT                         89

#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPUpdateServer.h>
ESP8266HTTPUpdateServer httpUpdater(true);
#include <FS.h>

extern "C" {
#include "user_interface.h"
}


#include <WiFiUdp.h>
#include <ArduinoOTA.h>
#include <ESP8266mDNS.h>
bool ArduinoOTAtriggered=false;


// WebServer
ESP8266WebServer WebServer(80);

struct SecurityStruct
{
  char          WifiSSID[32];
  char          WifiKey[64];
  char          WifiAPKey[64];
} SecuritySettings;

struct SettingsStruct
{
  unsigned long PID;
  int           Version;
  int16_t       Build;
  byte          IP[4];
  byte          Gateway[4];
  byte          Subnet[4];
  byte          DNS[4];
  byte          IP_Octet;
} Settings;

boolean AP_Mode = false;
byte cmd_within_mainloop = 0;

/*********************************************************************************************\
   SETUP
  \*********************************************************************************************/
void setup()
{
  Serial.begin(115200);

  if (SPIFFS.begin())
  {
    LoadSettings();

    if (Settings.Version == VERSION && Settings.PID == ESP_PROJECT_PID)
    {
      WiFi.persistent(false); // Do not use SDK storage of SSID/WPA parameters
      WifiConnect(3);
      WebServerInit();
      WiFi.mode(WIFI_STA);
    }
    else
    {
      Serial.println("PID?");
      delay(1);
    }
  }
  else
  {
    Serial.println("SPIFFS?");
    delay(1);
  }


  ArduinoOTAInit();

}


/*********************************************************************************************\
   MAIN LOOP
  \*********************************************************************************************/
void loop()
{
  if (cmd_within_mainloop == CMD_REBOOT)
    ESP.reset();

  WebServer.handleClient();
  yield();
  ArduinoOTA.handle();

  //once OTA is triggered, only handle that and dont do other stuff. (otherwise it fails)
  while (ArduinoOTAtriggered)
  {
    yield();
    ArduinoOTA.handle();
  }

}

void ArduinoOTAInit()
{
  // Default port is 8266
 	ArduinoOTA.setPort(8266);
//	ArduinoOTA.setHostname(Settings.Name);


  ArduinoOTA.onStart([]() {
      Serial.println(F("OTA  : Start upload"));
      SPIFFS.end(); //important, otherwise it fails
  });

  ArduinoOTA.onEnd([]() {
      Serial.println(F("\nOTA  : End"));
      //"dangerous": if you reset during flash you have to reflash via serial
      //so dont touch device until restart is complete
      Serial.println(F("\nOTA  : DO NOT RESET OR POWER OFF UNTIL BOOT+FLASH IS COMPLETE."));
      delay(100);
      ESP.reset();
  });

  ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
      Serial.printf("OTA  : Progress %u%%\r", (progress / (total / 100)));
  });

  ArduinoOTA.onError([](ota_error_t error) {
      Serial.print(F("\nOTA  : Error (will reboot): "));
      if (error == OTA_AUTH_ERROR) Serial.println(F("Auth Failed"));
      else if (error == OTA_BEGIN_ERROR) Serial.println(F("Begin Failed"));
      else if (error == OTA_CONNECT_ERROR) Serial.println(F("Connect Failed"));
      else if (error == OTA_RECEIVE_ERROR) Serial.println(F("Receive Failed"));
      else if (error == OTA_END_ERROR) Serial.println(F("End Failed"));

      delay(100);
      ESP.reset();
  });

  ArduinoOTA.begin();

  Serial.println(F("OTA  : Arduino OTA enabled on port 8266"));

}


/********************************************************************************************\
  Load settings from SPIFFS
  \*********************************************************************************************/
boolean LoadSettings()
{
  LoadFromFile((char*)"config.dat", 0, (byte*)&Settings, sizeof(struct SettingsStruct));
  LoadFromFile((char*)"security.dat", 0, (byte*)&SecuritySettings, sizeof(struct SecurityStruct));
}


/********************************************************************************************\
  Load data from config file on SPIFFS
  \*********************************************************************************************/
void LoadFromFile(char* fname, int index, byte* memAddress, int datasize)
{
  fs::File f = SPIFFS.open(fname, "r+");
  if (f)
  {
    f.seek(index, fs::SeekSet);
    byte *pointerToByteToRead = memAddress;
    for (int x = 0; x < datasize; x++)
    {
      *pointerToByteToRead = f.read();
      pointerToByteToRead++;// next byte
    }
    f.close();
  }
}


//********************************************************************************
// Web Interface init
//********************************************************************************
void WebServerInit()
{
  WebServer.on("/", handle_root);
  httpUpdater.setup(&WebServer);
  WebServer.begin();
}


//********************************************************************************
// Web Interface root page
//********************************************************************************
void handle_root() {

  String sCommand = WebServer.arg("cmd");

  if (strcasecmp_P(sCommand.c_str(), PSTR("reboot")) != 0)
  {
    String reply = "";
    reply += "<h1>ESP Easy Uploader With Arduino OTA</h1>";

    IPAddress ip = WiFi.localIP();

    reply += "";
    reply += "<form>";
    reply += "<table>";

    reply += "<TR><TD>Flash Size:<TD>";
    reply += ESP.getFlashChipRealSize() / 1024; //ESP.getFlashChipSize();
    reply += " kB";

    reply += "<TR><TD>Sketch Max Size:<TD>";
    reply += ESP.getFreeSketchSpace() / 1024;
    reply += " kB";

    reply += "<TR><TD>System<TD><a class=\"button-link\" href=\"/?cmd=reboot\">Reboot</a>";
    reply += "<TR><TD>Firmware<TD><a class=\"button-link\" href=\"/update\">Load</a>";

    reply += "</table></form>";
    WebServer.send(200, "text/html", reply);
  }
  else
  {
    // have to disconnect or reboot from within the main loop
    // because the webconnection is still active at this point
    // disconnect here could result into a crash/reboot...
    if (strcasecmp_P(sCommand.c_str(), PSTR("reboot")) == 0)
    {
      cmd_within_mainloop = CMD_REBOOT;
    }

    WebServer.send(200, "text/html", "OK");
  }
}


//********************************************************************************
// Set Wifi AP Mode
//********************************************************************************
void WifiAPMode(boolean state)
{
  if (state)
  {
    AP_Mode = true;
    WiFi.mode(WIFI_AP_STA);
  }
  else
  {
    AP_Mode = false;
    WiFi.mode(WIFI_STA);
  }
}


//********************************************************************************
// Connect to Wifi AP
//********************************************************************************
boolean WifiConnect(byte connectAttempts)
{
  String log = "";

  if (Settings.IP[0] != 0 && Settings.IP[0] != 255)
  {
    char str[20];
    sprintf_P(str, PSTR("%u.%u.%u.%u"), Settings.IP[0], Settings.IP[1], Settings.IP[2], Settings.IP[3]);
    IPAddress ip = Settings.IP;
    IPAddress gw = Settings.Gateway;
    IPAddress subnet = Settings.Subnet;
    WiFi.config(ip, gw, subnet);
  }


  if (WiFi.status() != WL_CONNECTED)
  {
    if ((SecuritySettings.WifiSSID[0] != 0)  && (strcasecmp(SecuritySettings.WifiSSID, "ssid") != 0))
    {
      for (byte tryConnect = 1; tryConnect <= connectAttempts; tryConnect++)
      {
        if (tryConnect == 1)
          WiFi.begin(SecuritySettings.WifiSSID, SecuritySettings.WifiKey);
        else
          WiFi.begin();

        for (byte x = 0; x < 20; x++)
        {
          if (WiFi.status() != WL_CONNECTED)
          {
            delay(500);
          }
          else
            break;
        }
        if (WiFi.status() == WL_CONNECTED)
        {
          break;
        }
        else
        {
          ETS_UART_INTR_DISABLE();
          wifi_station_disconnect();
          ETS_UART_INTR_ENABLE();
          delay(1000);
        }
      }

      // fix ip if last octet is set
      if (Settings.IP_Octet != 0 && Settings.IP_Octet != 255)
      {
        IPAddress ip = WiFi.localIP();
        IPAddress gw = WiFi.gatewayIP();
        IPAddress subnet = WiFi.subnetMask();
        ip[3] = Settings.IP_Octet;
        WiFi.config(ip, gw, subnet);
      }
    }
    else
    {
      WifiAPMode(true);
    }
  }
}

