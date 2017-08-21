<?php

class Keydrop
{
    public function connectDB()
    {
        $dbopts = parse_url(getenv('DATABASE_URL'));
        $dsn = sprintf('pgsql:host=%s;dbname=%s', $dbopts['host'], substr($dbopts['path'], 1));

        $pdo = new PDO($dsn, $dbopts['user'], $dbopts['pass']);
        if (!$pdo) {
            echo "ERROR: can't connect";
            return false;
        }
        return $pdo;
    }

    public function createTableIfNotExists($pdo)
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

    public function save($path, $user, $key)
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

    public function validate()
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
        } elseif (!preg_match('{[A-Z]}', $pass)) {
            $error = 'ERROR: Password must include capital character.';
        } elseif (!preg_match("{[$symbol]}", $pass)) {
            $error = 'ERROR: Password must include symbol character.';
        }
        return $error;
    }

    public function saveAccount()
    {
        $type = getenv('ENCRYPTION_TYPE');
        if ($type == 'htpasswd') {
            $htpasswd = new HTPasswd();
            $key = $this->username . ':' . $htpasswd->md5($this->password);
        } else {
            $pubkey = getenv('PUBLICKEY');
            openssl_public_encrypt($this->password, $raw, $pubkey);
            $key = base64_encode($raw);
        }
        $result = $this->save($this->path, $this->username, $key);
        if (!$result) {
            return;
        }

        $escaped = htmlspecialchars($this->username, ENT_QUOTES);
        require __DIR__ . '/../views/thankyou.php';
    }

    public function readPostData()
    {
        $this->username = empty($_POST['username']) ? null : $_POST['username'];
        $this->password = empty($_POST['password']) ? null : $_POST['password'];
        $this->submit = empty($_POST['submit']) ? null : $_POST['submit'];
    }

    public function main()
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

    public function renderForm($error = null)
    {
        require __DIR__ . '/../views/form.php';
    }
}
