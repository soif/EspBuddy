# -*- coding: utf-8 -*-
import serial
import argparse
import io

timeout=1

argParser = argparse.ArgumentParser()
argParser.add_argument("command", help="Command to Send")
argParser.add_argument("-p", "--port", help="Serial Port")
argParser.add_argument("-b", "--baud", help="Baud Rate")
argParser.add_argument("-l", "--lines", help="Anwser Lines to wait for",default=4)
args = argParser.parse_args()

#print('lines: '+str(args.lines))

#command = str.encode(args.text+'\n')
command = args.command+'\n'

ser = serial.Serial(args.port, args.baud,timeout=timeout)
sio = io.TextIOWrapper(io.BufferedRWPair(ser, ser))
sio.write(command)
sio.flush()
result=''
for x in range(args.lines):
	#print('Line ' + str(x))
	result += sio.readline()
ser.close()

print(result)
