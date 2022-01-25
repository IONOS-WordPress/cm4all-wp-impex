#!/usr/bin/env bash

#
# refresh the current openend browser tab 
#

# Or BROWSER=firefox for the Firefox case
BROWSER=google-chrome 

CUR_WID=$(xdotool getwindowfocus)

for WID in $(xdotool search --onlyvisible --class $BROWSER)
do
  xdotool windowactivate $WID
  xdotool key 'F5'
done   

xdotool windowactivate $CUR_WID