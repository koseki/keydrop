#! /bin/bash

set -e

if [ -f "$1" ]; then
    heroku config:set PUBKEY="$(cat $1)"
fi
