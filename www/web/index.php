<?php
//echo phpinfo();exit;
#ini_set('display_errors','on');
#error_reporting(E_ALL ^ ~E_NOTICE);
define('APP_NAME', 'web');
define('APP_PUBLIC', __DIR__ . '/../../public/');
define('CORE', __DIR__ . '/../../core');
define('APP_PATH', __DIR__ . '/../../apps/' . APP_NAME);

define('IS_CLI', false);
if (substr($_SERVER['REQUEST_URI'], 0, 5) == '/') {
    $md5 = @md5_file(APP_PUBLIC . '/../../h5/dist/index.html');
    // header('Location:/dist/index.html?v=' . substr($md5, 0, 6));
    header('Location:/dist/index.html');
    exit();
}

if (strstr($_SERVER['HTTP_HOST'], 'ruicixing.com')) {
    define('APP_MODE', 'prod');
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
    define('APP_MODE', 'dev');
}

require CORE . '/library/App.php';
$app = new \C\L\App();
$app->web();
