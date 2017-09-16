<?php

namespace Koseki\Keydrop;

/**
 * APR1 htpasswd entry generator
 *
 *   Original: https://stackoverflow.com/questions/2994637/how-to-edit-htpasswd-using-php/8786956#8786956
 *   Spec: https://httpd.apache.org/docs/2.4/misc/password_encryptions.html
 *
 *
 * random_compat is required if you are using PHP < 7.0.
 *
 *    composer require paragonie/random_compat
 *
 * See: https://github.com/paragonie/random_compat
 */
class HTPasswd
{
    /**
     * Generate APR1 htpasswd entry.
     */
    public function apr1($plainpasswd, $salt = null)
    {
        if (empty($salt)) {
            $salt = $this->salt();
        }
        $len = strlen($plainpasswd);
        $text = $plainpasswd . '$apr1$' . $salt;

        $bin = pack('H32', md5($plainpasswd . $salt . $plainpasswd));
        for($i = $len; $i > 0; $i -= 16) {
            $text .= substr($bin, 0, min(16, $i));
        }
        for($i = $len; $i > 0; $i >>= 1) {
            $text .= ($i & 1) ? chr(0) : $plainpasswd{0};
        }

        $bin = pack('H32', md5($text));
        for($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plainpasswd : $bin;
            if ($i % 3) $new .= $salt;
            if ($i % 7) $new .= $plainpasswd;
            $new .= ($i & 1) ? $bin : $plainpasswd;
            $bin = pack('H32', md5($new));
        }

        $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $nums = '0123456789';
        $tmp = '';
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) $j = 5;
            $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
        }
        $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
        $tmp = strtr(
            strrev(substr(base64_encode($tmp), 2)),
            $alpha . $nums . '+/',
            './' . $nums . $alpha
        );

        return "\$apr1\$$salt\$$tmp";
    }

    /**
     * https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
     */
    public function salt()
    {
        try {
            $salt = strtr(base64_encode(random_bytes(6)), '+', '.');
        } catch (TypeError $e) {
            die('An unexpected error has occurred');
        } catch (Error $e) {
            die('An unexpected error has occurred');
        } catch (Exception $e) {
            die('Could not generate a random int. Is our OS secure?');
        }
        return $salt;
    }
}
