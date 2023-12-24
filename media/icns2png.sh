#!/bin/bash

# --------------------------------------------------------------------------------------------------------------------------------------
# EspBuddy - icns2png
# --------------------------------------------------------------------------------------------------------------------------------------
# Extracts all 16x16 icons of each xxx.ICNS file in a directory, and move then into a icons16/ folder inside this directory
# --------------------------------------------------------------------------------------------------------------------------------------
# Copyright (C) 2023  by François Déchery - https://github.com/soif/
#
# EspBuddy is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
#
# EspBuddy is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.




# Check if a directory is provided as an argument
if [ $# -eq 0 ]; then
    echo "Usage: $0 <directory>"
    exit 1
fi

directory="$1"

# Check if the provided directory exists
if [ ! -d "$directory" ]; then
	echo "Error: Directory '$directory' not found."
	exit 1
fi

#mkdir "$directory/icons32"
mkdir "$directory/icons16"

# Loop through each file in the directory with a .icns extension
for file in "$directory"/*.icns; do
	# Check if the file is a regular file
	if [ -f "$file" ]; then
		
		# Get the file name
		filename=$(basename "$file")
		
		# Get the file size
		size=$(du -h "$file" | cut -f1)
		
		# Echo the file name and size
		echo -n "# Processing: $file 	( $size )..... "
		
		# Process the file with iconutil
		iconutil -c iconset "$file"

		# Check if iconutil command succeeded
		if [ $? -eq 0 ]; then
			
			# Get the directory created by iconutil
			iconset_directory="$directory/${filename%.icns}.iconset"

			# Get the file name without extension
			base_filename=$(basename "$file" .icns)
		
			# Rename 'icon_32x32.png' to the original filename with .png extension
			#mv "$iconset_directory/icon_32x32.png" "$directory/icons32/$base_filename.png"

			# Rename 'icon_16x16.png' to the original filename with .png extension
			mv "$iconset_directory/icon_16x16.png" "$directory/icons16/$base_filename.png"

			# Remove the iconset directory
			rm -rf "$iconset_directory"
			
			echo " OK"
		else
			echo "\n  ---> ERROR processing file: $filename"
		fi
	fi
done

