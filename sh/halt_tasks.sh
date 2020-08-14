#!/bin/bash

APP_PATH="$1"

ps aux | grep "$APP_PATH"/bilet_apptours | tr -s " " | cut -d " " -f 2 | while read PID; do kill -9 "$PID"; done;
"$APP_PATH"/bilet_apptours tours/halt-tasks;
