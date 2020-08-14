#!/bin/bash

DATA_PATH="$1"
PARSERS_SERVER="$2"
LOGS_PATH="$3"

sudo rsync -qctz --recursive --chown=www-data:www-data -e ssh root@"$PARSERS_SERVER":"$DATA_PATH"/* "$DATA_PATH" 2>&1 1>"$LOGS_PATH"/init_parse_with_remote.log
