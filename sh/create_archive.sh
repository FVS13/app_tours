#!/bin/bash

export_dir="$1"
input_subdir="$2"
archive_path="$3"

mv "$export_dir/$input_subdir" "$export_dir/export"
cd "$export_dir/"

tar -zcvf ./"$archive_path" export/*
mv "$export_dir/export" "$export_dir/$input_subdir"
