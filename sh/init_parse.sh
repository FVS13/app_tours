#!/bin/bash

mmin_val=$1
data_path="$2"
incoming_data_path="$3"

cd "$data_path";
find . -maxdepth 4 -mindepth 4 -name "*.json" -mmin -$mmin_val -type f -exec cp --parents {} "${incoming_data_path}/" \;
