<?php

define('APP_NAME', 'adm');
define('APP_PUBLIC', __DIR__ . '/../../public/');
define('CORE', __DIR__ . '/../../core');
define('APP_PATH', __DIR__ . '/../../apps/' . APP_NAME);
define('IS_CLI', false);
// var_dump(APP_PUBLIC);die;

if (substr($_SERVER['REQUEST_URI'], 0, 5) == '/') {
    $md5 = @md5_file(APP_PUBLIC . '/../../adm/dist/index.html');
    header('Location:/dist/index.html?v=' . substr($md5, 0, 6));
    exit();
}

if (strstr($_SERVER['HTTP_HOST'], 'ruicixing.com')) {
    define('APP_MODE', 'prod');
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
    define('APP_MODE', 'dev');
}
require CORE . '/library/Appadm.php';
$app = new \C\L\App();


$app->web();