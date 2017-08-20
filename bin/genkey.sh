#! /bin/bash

DATE=`date +"%Y%m%d%H%M%S"`

openssl genrsa -aes256 -out private-$DATE.pem 2048
openssl rsa -pubout -in private-$DATE.pem -out public-$DATE.pem
