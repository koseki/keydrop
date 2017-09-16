<?php

require __DIR__ . '/../lib/HTPasswd.php';

use PHPUnit\Framework\TestCase;
use Koseki\Keydrop\HTPasswd;

class HTPasswdTest extends TestCase
{
    private static $repeat = 50;

    /**
     * PHP -(salt)-> openssl
     */
    public function testAPR1OpenSSL()
    {
        $htpasswd = new HTPasswd();

        for ($i = 0; $i < self::$repeat; $i++) {
            $password = $this->passwordForTest();
            $result = $htpasswd->apr1($password);
            list($apr1, $salt, $hash) = preg_split('{\$}', substr($result, 1));

            $escaped = escapeshellarg($password);
            $escapedSalt = escapeshellarg($salt);

            $expected = exec("openssl passwd -apr1 -salt $escapedSalt $escaped");
            // echo "$expected\n";

            $this->assertEquals('apr1', $apr1);
            $this->assertRegExp('{\A[a-zA-Z0-9/\.]{8}\z}', $salt);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * htpasswd -(salt)-> PHP
     */
    public function testAPR1HTPasswd()
    {
        $htpasswd = new HTPasswd();

        for ($i = 0; $i < self::$repeat; $i++) {
            $out = null;
            $password = $this->passwordForTest();
            $escaped = escapeshellarg($password);
            exec("htpasswd -nbm $i $escaped", $out);

            $expected = preg_split('{:}', $out[0], 2)[1];
            list($apr1, $salt, $hash) = preg_split('{\$}', substr($expected, 1));

            $result = $htpasswd->apr1($password, $salt);
            $tokens = preg_split('{\$}', $result);

            $this->assertEquals('apr1', $tokens[1]);
            $this->assertRegExp('{\A[a-zA-Z0-9/\.]{8}\z}', $tokens[2]);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * PHP -> htpasswd -v
     */
    public function testAPR1HTPasswdV()
    {
        $htpasswd = new HTPasswd();

        $tmpfile = tempnam('/tmp', 'HTPasswdTest-');
        for ($i = 0; $i < self::$repeat; $i++) {
            $password = $this->passwordForTest();
            $escaped = escapeshellarg($password);
            $result = $htpasswd->apr1($password);
            // echo $result . "\n";

            $out = fopen($tmpfile, 'w');
            fputs($out, "$i:$result\n");
            fclose($out);
            exec("htpasswd -vbm $tmpfile $i $escaped 2>&1", $out);
            $this->assertEquals("Password for user $i correct.", $out[0]);
        }
        unlink($tmpfile);
    }

    private function passwordForTest()
    {
        $alphanum = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $symbol = '"\'!#$%&()*+/:;<=>?@[\\]^_`{|}~-,.';
        $passwordLength = rand() % 20 + 1;
        return $this->randomString($passwordLength, $alphanum . $symbol);
    }

    public function randomString($keyspace, $length)
    {
        $keysize = strlen($keyspace);
        $str = '';
        try {
            for ($i = 0; $i < $length; ++$i) {
                $str .= $keyspace[random_int(0, $keysize - 1)];
            }
        } catch (TypeError $e) {
            die('An unexpected error has occurred');
        } catch (Error $e) {
            die('An unexpected error has occurred');
        } catch (Exception $e) {
            die('Could not generate a random int. Is our OS secure?');
        }
        return $str;
    }
}
