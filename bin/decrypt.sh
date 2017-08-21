#! /bin/bash

set -e

if [ -f "$1" -a -f "$2" ]; then
    echo
    base64 -D $1 | openssl rsautl -decrypt -inkey $2
    echo
else
    echo "Usage: decrypt.sh encrypted_file private_key_file"
    exit 1
fi
