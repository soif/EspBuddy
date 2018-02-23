/* This is a bare minimum sketch to upload larger ESP Tasmota firmware files to ESP modules with only 1MB flash chips
 *
 * Based on the ESPEasy Upload Firmware - LetsControlIt - 2017.06.03
 * Modified by Francois Dechery for EspBuddy needs - https://github.com/soif/
 */

//#define OWN_DEBUG //uncomment to show serial log

#include "own_debug.h"


// ********************************************************************************
//	 DO NOT CHANGE ANYTHING BELOW THIS LINE
// ********************************************************************************
#define CMD_REBOOT		 89

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

boolean AP_Mode = false;
byte cmd_within_mainloop = 0;

// Tasmota Settings ####################################################################
// language/en-GB.h for sonoff_template.h ----------------
#define D_SENSOR_NONE     "None"
#define D_SENSOR_DHT11    "DHT11"
#define D_SENSOR_AM2301   "AM2301"
#define D_SENSOR_SI7021   "SI7021"
#define D_SENSOR_DS18X20  "DS18x20"
#define D_SENSOR_I2C_SCL  "I2C SCL"
#define D_SENSOR_I2C_SDA  "I2C SDA"
#define D_SENSOR_WS2812   "WS2812"
#define D_SENSOR_IRSEND   "IRsend"
#define D_SENSOR_SWITCH   "Switch"   // Suffix "1"
#define D_SENSOR_BUTTON   "Button"   // Suffix "1"
#define D_SENSOR_RELAY    "Relay"    // Suffix "1i"
#define D_SENSOR_LED      "Led"      // Suffix "1i"
#define D_SENSOR_PWM      "PWM"      // Suffix "1"
#define D_SENSOR_COUNTER  "Counter"  // Suffix "1"
#define D_SENSOR_IRRECV   "IRrecv"
#define D_SENSOR_MHZ_RX   "MHZ Rx"
#define D_SENSOR_MHZ_TX   "MHZ Tx"
#define D_SENSOR_PZEM_RX  "PZEM Rx"
#define D_SENSOR_PZEM_TX  "PZEM Tx"
#define D_SENSOR_SAIR_RX  "SAir Rx"
#define D_SENSOR_SAIR_TX  "SAir Tx"
#define D_SENSOR_SPI_CS   "SPI CS"
#define D_SENSOR_SPI_DC   "SPI DC"
#define D_SENSOR_BACKLIGHT "BkLight"
#define D_SENSOR_PMS5003  "PMS5003"

//#include "sonoff_template.h" -----------------------------
#define MAX_GPIO_PIN       18   // Number of supported GPIO
typedef struct MYIO {
  uint8_t      io[MAX_GPIO_PIN];
} myio;


#include "sonoff.h"                         // Enumeration used in user_config.h
#include "user_config.h"                    // Fixed user configurable options
#include "settings.h"


/*********************************************************************************************\
	 SETUP
\*********************************************************************************************/
void setup(){
#ifdef OWN_DEBUG
	Serial.begin(74880);
#endif
	DEBUG_PRINTLN(F("----"));

	SettingsLoad();

	WiFi.persistent(false); // Do not use SDK storage of SSID/WPA parameters
	WifiConnect(3);
	WebServerInit();
	WiFi.mode(WIFI_STA);

	ArduinoOTAInit();
}


/*********************************************************************************************\
	 MAIN LOOP
\*********************************************************************************************/
void loop(){
	if (cmd_within_mainloop == CMD_REBOOT)
	ESP.reset();

	WebServer.handleClient();
	yield();
	ArduinoOTA.handle();

	//once OTA is triggered, only handle that and dont do other stuff. (otherwise it fails)
	while (ArduinoOTAtriggered){
		yield();
		ArduinoOTA.handle();
	}
}

void ArduinoOTAInit()
{
	// Default port is 8266
	ArduinoOTA.setPort(8266);
	ArduinoOTA.setHostname(Settings.hostname);

	ArduinoOTA.onStart([]() {
		DEBUG_PRINTLN(F("OTA	 : Start upload"));
		SPIFFS.end(); //important, otherwise it fails
	});

	ArduinoOTA.onEnd([]() {
		DEBUG_PRINTLN(F("\nOTA	 : End"));
		//"dangerous": if you reset during flash you have to reflash via serial
		//so dont touch device until restart is complete
		DEBUG_PRINTLN(F("\nOTA	 : DO NOT RESET OR POWER OFF UNTIL BOOT+FLASH IS COMPLETE."));
		delay(100);
		ESP.reset();
	});

	ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
		DEBUG_PRINTF2("OTA	: Progress %u%%\r", (progress / (total / 100)));
	});

	ArduinoOTA.onError([](ota_error_t error) {
		DEBUG_PRINT(F("\nOTA	 : Error (will reboot): "));
		if (error == OTA_AUTH_ERROR) 			{DEBUG_PRINTLN(F("Auth Failed"));}
		else if (error == OTA_BEGIN_ERROR) 		{DEBUG_PRINTLN(F("Begin Failed"));}
		else if (error == OTA_CONNECT_ERROR)	{DEBUG_PRINTLN(F("Connect Failed"));}
		else if (error == OTA_RECEIVE_ERROR)	{DEBUG_PRINTLN(F("Receive Failed"));}
		else if (error == OTA_END_ERROR) 		{DEBUG_PRINTLN(F("End Failed"));}

		delay(100);
		ESP.reset();
	});

	ArduinoOTA.begin();

	DEBUG_PRINTLN(F("Arduino OTA enabled on port 8266"));
}

//********************************************************************************
// Web Interface init
//********************************************************************************
void WebServerInit(){
	WebServer.on("/", handle_root);
	httpUpdater.setup(&WebServer);
	WebServer.begin();
}


//********************************************************************************
// Web Interface root page
//********************************************************************************
void handle_root() {
	String sCommand = WebServer.arg("cmd");

	if (strcasecmp_P(sCommand.c_str(), PSTR("reboot")) != 0){
		String reply = "";
		reply += "<h1>Tasmota Uploader With Arduino OTA</h1>";

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
	else{
		// have to disconnect or reboot from within the main loop
		// because the webconnection is still active at this point
		// disconnect here could result into a crash/reboot...
		if (strcasecmp_P(sCommand.c_str(), PSTR("reboot")) == 0){
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
	if (state){
		AP_Mode = true;
		WiFi.mode(WIFI_AP_STA);
	}
	else{
		AP_Mode = false;
		WiFi.mode(WIFI_STA);
	}
}


//********************************************************************************
// Connect to Wifi AP
//********************************************************************************
boolean WifiConnect(byte connectAttempts){
#ifdef OWN_DEBUG
   char str[30];
#endif

	if (Settings.ip_address[0] != 0 && Settings.ip_address[0] != 255){
		WiFi.config(Settings.ip_address[0], Settings.ip_address[1], Settings.ip_address[2], Settings.ip_address[3]);  // Set static IP

		DEBUG_PRINTLN(F("Config set to Fixed IP. "));

#ifdef OWN_DEBUG
		uint8_t *ipPtr = (uint8_t*) &Settings.ip_address[0];
		sprintf_P(str, PSTR("%u.%u.%u.%u"), ipPtr[0], ipPtr[1], ipPtr[2], ipPtr[3]);
		DEBUG_PRINT(F(" - IP      : "));	DEBUG_PRINTLN(str);

		ipPtr = (uint8_t*) &Settings.ip_address[1];
		sprintf_P(str, PSTR("%u.%u.%u.%u"), ipPtr[0], ipPtr[1], ipPtr[2], ipPtr[3]);
		DEBUG_PRINT(F(" - Gateway : "));	DEBUG_PRINTLN(str);

		ipPtr = (uint8_t*) &Settings.ip_address[2];
		sprintf_P(str, PSTR("%u.%u.%u.%u"), ipPtr[0], ipPtr[1], ipPtr[2], ipPtr[3]);
		DEBUG_PRINT(F(" - Mask    : "));	DEBUG_PRINTLN(str);

		ipPtr = (uint8_t*) &Settings.ip_address[3];
		sprintf_P(str, PSTR("%u.%u.%u.%u"), ipPtr[0], ipPtr[1], ipPtr[2], ipPtr[3]);
		DEBUG_PRINT(F(" - DNS     : "));	DEBUG_PRINTLN(str);
#endif

	}
	else{
		DEBUG_PRINTLN(F("Config set to DHCP"));
	}

	if (WiFi.status() != WL_CONNECTED){
		if ((Settings.sta_ssid[0] != 0)	 ){
			for (byte tryConnect = 1; tryConnect <= connectAttempts; tryConnect++){

				if (tryConnect == 1){
					DEBUG_PRINTLN(F("Connecting to Wifi with: "));
					DEBUG_PRINT(F(" - SSID    : ")); DEBUG_PRINTLN(Settings.sta_ssid[0]);
					DEBUG_PRINT(F(" - Key     : ")); DEBUG_PRINTLN(Settings.sta_pwd[0]);

         			WiFi.begin(Settings.sta_ssid[0], Settings.sta_pwd[0]);
				}
				else{
					WiFi.begin();
				}
				DEBUG_PRINT("Waiting Connection : ");

				for (byte x = 0; x < 20; x++){
					if (WiFi.status() != WL_CONNECTED){
						DEBUG_PRINT(F("."));
						delay(500);
					}
					else{
						break;
					}
				}

				DEBUG_PRINTLN("");

				if (WiFi.status() == WL_CONNECTED){
#ifdef OWN_DEBUG
					IPAddress ip = WiFi.localIP();
					sprintf_P(str, PSTR("%u.%u.%u.%u"), ip[0], ip[1], ip[2], ip[3]);
					DEBUG_PRINT(F("Connected with IP Address : ")); DEBUG_PRINTLN(str);
#endif
					break;
				}
				else{
					DEBUG_PRINTLN("\nCan't Connect");
					ETS_UART_INTR_DISABLE();
					wifi_station_disconnect();
					ETS_UART_INTR_ENABLE();
					delay(1000);
				}
			}
/*
			// fix ip if last octet is set
			if (Settings.IP_Octet != 0 && Settings.IP_Octet != 255)
			{
			IPAddress ip = WiFi.localIP();
			IPAddress gw = WiFi.gatewayIP();
			IPAddress subnet = WiFi.subnetMask();
			ip[3] = Settings.IP_Octet;
			WiFi.config(ip, gw, subnet);
			}
*/
		}
		else{
			WifiAPMode(true);
		}
	}
}
