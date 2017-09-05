# Keydrop

## About

Keydrop is an instant Heroku application for receiving passwords safely.

![form](https://user-images.githubusercontent.com/11028/30041495-914536b6-9225-11e7-8209-f803e39be696.png)

This will do

 * Encryption
 * Validation


### Encryption

 * Sending: `https://*.herokuapp.com/...`
 * Saving: htpasswd apr1 or RSA public key
 * Receiving: PostgreSQL SSL connection


### Validation

At default, the password must have...

* More than 10 characters
* At least 1 numeric character (`0 - 9`)
* At least 1 capital character (`A - Z`)
* At lease 1 symbol character (`#$%@&*!...`)

If you want to change the conditions, please clone the source code from Heroku git repository and edit it.



## Prepare

1. [Create a Heroku account.](https://www.heroku.com/)
2. [Install the Heroku command line app.](https://devcenter.heroku.com/articles/heroku-cli)
3. Install OpenSSL if you want to use the public key encryption type.



## Start

Click the following button.

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

And you will see the configulation screen.

![config](https://user-images.githubusercontent.com/11028/30041490-88f772e4-9225-11e7-921e-970290005b74.png)

### App name

Leave empty. The automatically generated randome name is preferable.

### ACCEPT_PATHS

Input random path name. You can set multiple paths. For example:

```
/K7FElrHrPmUnmBdw,/Mfj2Wl2YF6rFcMQ6
```

The form URL is like this.

```
https://${App name}.herokuapp.com${ACCEPT_PATH}
```

Other URLs will return 404.

### ENCRYPTION_TYPE

The default value is `htpasswd`.

If you want to use public key, set `publickey`.

### PUBLICKEY

You don't need to change this value if you use `htpasswd` encryption type. The default value is an insecure sample key, so please don't use it for the real purpose.

#### Generate private and public keys

Use `bin/genkey.sh`.

```bash
#! /bin/bash

DATE=`date +"%Y%m%d%H%M%S"`

openssl genrsa -aes256 -out private-$DATE.pem 2048
openssl rsa -pubout -in private-$DATE.pem -out public-$DATE.pem
```



## Get the result

You can receive the passwords data using `heroku pg:sql` command.

```console
$ heroku pg:psql --app random-appname-12345
--> Connecting to postgresql-dbname-54321
psql (9.6.1, server 9.6.4)
SSL connection (protocol: TLSv1.2, cipher: ECDHE-RSA-AES256-GCM-SHA384, bits: 256, compression: off)
Type "help" for help.

random-appname-12345::DATABASE=> select * from keys;
 id |   type    |     path      | username |                                                                                                                                                                           key                                                                                                                                                                            |       created_at
 ----+-----------+---------------+----------+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+------------------------
   1 | htpasswd  | /vEswu2ech4ta | test-1   | test-1:$apr1$4.Pz0U/W$TppMU9SjE/W0yg1FAPiMS/                                                                                                                                                                                                                                                                                                             | 2017-08-30 03:06:57+00
   2 | publickey | /vEswu2ech4ta | test-2   | nJ97fbhHIkZAMISp/zXVETvitUl8Qlbi1pyOTtoF3ybI9EDrqenPFb4WMOISrTn8sW+Qu5xvNsjaMEIC3j0Md+hmtEzlLmVK+Nb9bq989I9TnmjgdtFE9klyKkhb5J7r+7SKqBgzfmu7kAoREYBtg05hvNb3mJXGbAruybElbZlxNgf06b5f6W/kkHtGcJaV49oNHKBEmg03ceMip2wP5H6tk/BS6O4FTrEKvpYsn4+Kh6+7JMioCVQEXz3NvpH0BIkmnGncXBZTdtPihju7srb0uEHe0sys66PPBZGZQWbisBdr9knJ5WTfnh2iWLOGv2NgOwfgXQZyMdizINALDw== | 2017-08-30 03:08:19+00
(2 rows)
```

### Decryption (publickey)

Use `bin/decrypt.sh`.

```bash
#! /bin/bash

set -e

if [ -f "$1" -a -f "$2" ]; then
    base64 -D $1 | openssl rsautl -decrypt -inkey $2
    echo
else
    echo "Usage: decrypt.sh encrypted_file private_key_file"
    exit 1
fi
```

### Clean up

I recommend to destroy the heroku app, when you finish receiving all the passwords.
