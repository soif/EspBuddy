#!/bin/bash

DIR=`dirname $0`
DOC=command_examples.md
FILE=$DIR/$DOC
APP=espbuddy.php
NAME=EspBuddy
IP=10.1.250.154
ID=1000aba1ee

# -----------------------------------------------------------------------------------------
# https://superuser.com/questions/380772/removing-ansi-color-codes-from-text-stream
# remove color AND move chars (ie Uploding:....) 
# s/\x1B\[[0-9;]*m//g	s/\x1B\[[0-9;]*[a-zA-Z]//g
#https://askubuntu.com/questions/420981/how-do-i-save-terminal-output-to-a-file
# pipe ALL
print_com(){
	$1  2>&1 | tee | perl -pe 's/\x1B\[[0-9;]*m//g'
}

# sonodiy scan use some funcky redirection, and hang when 2>&1 is set
print_com_special(){
	$1 | tee | perl -pe 's/\x1B\[[0-9;]*m//g'
}

# -----------------------------------------------------------------------------------------
append_command(){
	echo " - processing: $1";

	( 
	echo ""
	echo "### \`# $1\`"
	echo ""
	if [[ ! -z  "$2" ]]; then echo "$2"; fi
	echo ""
	echo '```plaintext'
	if [[ ! -z  "$3" ]]; then 
		print_com_special "$1"
	else
		print_com "$1"
	fi
	echo '```'
	echo ""
	if [[ ! -z  "$3" ]]; then echo "----------"; fi
	echo ""
	echo ""
	) | cat >> $FILE

	sleep 1
}

# -----------------------------------------------------------------------------------------
make_title(){
	echo "" >> $FILE
	echo "" >> $FILE
	echo "## $1" >> $FILE
	echo "" >> $FILE
}

#### MAIN ##########################################################################################@
echo "Building..."

echo "# $NAME Command Examples" > $FILE
echo "" >> $FILE
echo "This document shows the terminal output from various $NAME commands." >> $FILE

make_title "Main $NAME commands"
append_command "$APP help"			"List of all $NAME commands." 1
append_command "$APP version led2"	"Grab the remote version of the 'led2' host. *'led2' is an host defined from the config.php file.*" 1
append_command "$APP upload led2"	"Upload the latest firmware to the 'led2' host, using an intermediate OTA firmware *as set in the 'led2' configuration, from the config.php file.*" 1
append_command "$APP self help"		"$NAME self maintenance tools."

make_title " Sonoff DIY (sonodiy) specific commands"
append_command "$APP sonodiy help"	"Tasks for the 'sonodiy' command." 1
append_command "$APP sonodiy scan"	"Show the IP Adresses and IDs of connected devices." 1 1
append_command "$APP sonodiy test $IP $ID"	"Test if we can successfully connect to the Sonoff Device." 1
append_command "$APP sonodiy info $IP $ID -v"	"Show device information." 1
append_command "$APP sonodiy info $IP $ID -j"	"Show device information in JSON format."

# clean Uploading progress bars -----------------------------------
echo "Cleaning..."
perl -i -pe 's/^.*?Uploading:[^\d]+\d{1,2}%.*?[\r\n]+$//g' "$FILE"

exit 0
