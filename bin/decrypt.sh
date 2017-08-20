#! /bin/bash

set -e

echo
base64 -D $1 | openssl rsautl -decrypt -inkey $2
echo

