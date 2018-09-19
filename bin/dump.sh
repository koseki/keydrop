#! /bin/bash

set -e

IFS=$'\t'

appname=$1
privatekey=$2

if [ -z "$appname" ]; then
    echo "Usage: dump.sh app-name [privatekey]"
    exit 1
fi

heroku pg:psql --app $appname -c "copy keys to stdout with csv delimiter E'\t';" | while read LINE; do
    tokens=(`echo "$LINE"`)
    if [ "${tokens[1]}" = 'publickey' -a -f "$privatekey" ]; then
        key=$(echo ${tokens[4]} | base64 -D | openssl rsautl -decrypt -inkey $privatekey)
        echo "${tokens[0]}$IFS${tokens[5]}$IFS${tokens[1]}$IFS${tokens[2]}$IFS${tokens[3]}$IFS$key"
    elif [ "${tokens[1]}" = 'htpasswd' ]; then
        echo "${tokens[0]}$IFS${tokens[5]}$IFS${tokens[1]}$IFS${tokens[2]}$IFS${tokens[3]}$IFS${tokens[4]}"
    else
        echo "### $LINE"
    fi
done
