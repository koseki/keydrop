<?php

namespace Koseki\Keydrop;

class Keydrop
{
    private function connectDB()
    {
        $dbopts = parse_url(getenv('DATABASE_URL'));
        $dsn = sprintf('pgsql:host=%s;dbname=%s', $dbopts['host'], substr($dbopts['path'], 1));

        $pdo = new \PDO($dsn, $dbopts['user'], $dbopts['pass'] ?? null);
        if (!$pdo) {
            echo "ERROR: can't connect";
            return false;
        }
        return $pdo;
    }

    private function createTableIfNotExists($pdo)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS keys (
            id serial PRIMARY KEY,
            type varchar(32) NOT NULL,
            path varchar(64) NOT NULL,
            username varchar(64) NOT NULL,
            key varchar(1024) NOT NULL,
            created_at timestamp with time zone NOT NULL
        )';
        $res = $pdo->query($sql);
        if (!$res) {
            echo "ERROR: can't save";
            return false;
        }
        return true;
    }

    private function save($path, $user, $key)
    {
        $pdo = $this->connectDB();
        if (!$pdo) return;
        if (!$this->createTableIfNotExists($pdo)) return;

        $sql = 'INSERT INTO KEYS (
            path,
            type,
            username,
            key,
            created_at
        ) VALUES (?, ?, ?, ?, ?)';

        $stmt = $pdo->prepare($sql);
        $type = getenv('ENCRYPTION_TYPE');
        $res = $stmt->execute(array($path, $type, $user, $key, date('c')));

        if (!$res) {
            echo "ERROR: can't save";
            return false;
        }
        return true;
    }

    public function checkPath()
    {
        $paths = preg_split('{\s*,\s*}', getenv('ACCEPT_PATHS'));
        $path = $_SERVER["REQUEST_URI"];

        if (!in_array($path, $paths)) {
            return false;
        }
        $this->path = $_SERVER["REQUEST_URI"];
        return true;
    }

    private function validate()
    {
        $error = '';
        $user = $this->username;
        $pass = $this->password;

        $quote = '"\'';
        $symbol = preg_quote($quote . '!#$%&()*+/:;<=>?@[\\]^_`{|}~-,.');

        if (empty($user) || empty($pass)) {
            $error = 'ERROR: Empty ID or PASS.';
        } elseif (!preg_match('{\A[-a-zA-Z0-9_]+\z}', $user)) {
            $error = 'ERROR: Illegal ID string (Use: a-z,A-Z,0-0,_-).';
        } elseif (strlen($pass) < 10) {
            $error = 'ERROR: Password is too short';
        } elseif (strlen($pass) > 128) {
            // The RSA key size must be larger than 2048.
            $error = 'ERROR: Password is too long';
        } elseif (!preg_match('{\d}', $pass)) {
            $error = 'ERROR: Password must include numeric character.';
        } elseif (!preg_match('{[a-z]}', $pass)) {
            $error = 'ERROR: Password must include lowercase character.';
        } elseif (!preg_match('{[A-Z]}', $pass)) {
            $error = 'ERROR: Password must include uppercase character.';
        } elseif (!preg_match("{[$symbol]}", $pass)) {
            $error = 'ERROR: Password must include symbol character.';
        }
        return $error;
    }

    private function saveAccount()
    {
        $type = getenv('ENCRYPTION_TYPE');
        if ($type == 'htpasswd') {
            $htpasswd = new HTPasswd();
            $key = $this->username . ':' . $htpasswd->apr1($this->password);
        } else {
            $pubkey = getenv('PUBLICKEY');
            openssl_public_encrypt($this->password, $raw, $pubkey);
            $key = base64_encode($raw);
        }
        $result = $this->save($this->path, $this->username, $key);
        if (!$result) {
            return;
        }

        $this->renderThankYou();
    }

    private function readPostData()
    {
        $this->username = $_POST['username'] ?? null;
        $this->password = $_POST['password'] ?? null;
        $this->submit = $_POST['submit'] ?? null;
    }

    private function main()
    {
        $this->readPostData();
        if (!empty($this->submit)) {
            $error = $this->validate();
            if (empty($error)) {
                $this->saveAccount();
            } else {
                $this->renderForm($error);
            }
        } else {
            $this->renderForm();
        }
    }

    public function renderMain()
    {
        ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Keydrop - Password Inbox</title>
    <link rel="stylesheet" href="/site.css">
    <script>
function togglePassword() {
    var field = document.getElementById('password');
    var toggle = document.getElementById('passwordToggle');
    var inputType = field.type;
    if (inputType == 'password') {
        field.type = 'text';
        toggle.text = 'Hide';
    } else {
        field.type = 'password';
        toggle.text = 'Show';
    }
}
    </script>
  </head>
  <body>
    <?php $this->main(); ?>
  </body>
</html>
        <?php
    }

    private function renderForm($error = null)
    {
        if (!empty($error)) {
            echo "<p><strong>$error</strong></p>";
        }
        ?>
<section>
  <p class="lead">Submit your new password. We will add your account soon.</p>
  <form method="post" action="" autocomplete="off">
    <label>ID: <input name="username" type="text"></label>
    <label>Password: <input name="password" type="password" id="password"></label> <a href="javascript:void(0)" id="passwordToggle" onClick="togglePassword(); return false;">Show</a>
    <br><br>
    <input type="submit" name="submit" value="Submit" class="submit">
  </form>

  <hr>

  <p>The password must have:</p>
  <ul>
    <li>More than 10 characters</li>
    <li>At least 1 numeric character (<code>0 - 9</code>)</li>
    <li>At least 1 uppercase character (<code>A - Z</code>)</li>
    <li>At least 1 symbol character (<code>#$%@&amp;*!...</code>)</li>
  </ul>
  <p>Do not use the same password for other online services.</p>
</section>
        <?php
    }

    private function renderThankYou()
    {
        $escaped = htmlspecialchars($this->username, ENT_QUOTES);
        ?>
<section>
<p>
  Thank you <strong><?php echo $escaped; ?></strong>! We will add your account soon.
</p>
</section>
        <?php
    }
}
