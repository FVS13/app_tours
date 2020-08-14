#!/bin/bash

WEBROOT_PATH="$1"
PARSERS_SERVER="$2"

sudo rsync -qctz --recursive -e ssh root@"$PARSERS_SERVER":"$WEBROOT_PATH"/icaos-ozon-out.json "$WEBROOT_PATH"/
sudo rsync -qctz --recursive -e ssh root@"$PARSERS_SERVER":"$WEBROOT_PATH"/icaos-seatguru-out.json "$WEBROOT_PATH"/
sudo rsync -qctz --recursive -e ssh "$WEBROOT_PATH"/icaos-ozon.json root@"$PARSERS_SERVER":"$WEBROOT_PATH"/
sudo rsync -qctz --recursive -e ssh "$WEBROOT_PATH"/icaos-seatguru.json root@"$PARSERS_SERVER":"$WEBROOT_PATH"/
