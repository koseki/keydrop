<?php

require __DIR__ . '/../vendor/autoload.php';

use \Koseki\Keydrop\Keydrop;

$keydrop = new Keydrop();
if ($keydrop->checkPath()) {
    $keydrop->renderMain();
} else {
    header("HTTP/1.1 404 Not Found");
    echo '404 Not Found';
}
