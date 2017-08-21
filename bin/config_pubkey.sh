#! /bin/bash

set -e

if [ -f "$1" ]; then
    heroku config:set PUBKEY="$(cat $1)"
else
    echo "Usage: config_pubkey.sh public_key_file"
fi
