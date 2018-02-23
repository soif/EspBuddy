/*
DebugUtils.h - Simple debugging utilities.
Ideas taken from:
http://forum.arduino.cc/index.php?topic=46900.0
v2.0

usage:
#define OWN_DEBUG
#include "own_debug.h"

*/


#ifdef OWN_DEBUG
#include <Arduino.h>

#define DEBUG_PRINT(str)	Serial.print(str);
#define DEBUG_PRINTDEC(str)	Serial.print(str,DEC);
#define DEBUG_PRINTHEX(str)	Serial.print(str,HEX);
#define DEBUG_PRINTLN(str)  Serial.println(str);
#define DEBUG_PRINTF(str) 	Serial.printf(str);
#define DEBUG_PRINTF2(str1,str2) 	Serial.printf(str1,str2);

#else

#define DEBUG_PRINT(str)
#define DEBUG_PRINTDEC(str)
#define DEBUG_PRINTHEX(str)
#define DEBUG_PRINTLN(str)
#define DEBUG_PRINTF(str)
#define DEBUG_PRINTF2(str1,str2)

#endif


