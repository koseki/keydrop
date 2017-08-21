<?php

require '../vendor/autoload.php';
require __DIR__ . '/../lib/HTPasswd.php';
require __DIR__ . '/../lib/Keydrop.php';

date_default_timezone_set('UTC');

$keydrop = new Keydrop();
if ($keydrop->checkPath()) {
    require __DIR__ . '/../views/main.php';
} else {
    header("HTTP/1.1 404 Not Found");
    echo '404 Not Found';
}
