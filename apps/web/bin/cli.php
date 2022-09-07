<?php

define('APP_NAME', 'web');
define('APP_PUBLIC', __DIR__ . '/../../../public');
define('CORE', __DIR__ . '/../../../core');
define('APP_PATH', __DIR__ . '/..');
define('APP_MODE', 'prod');
define('IS_CLI', true);

require CORE . '/library/App.php';
$app = new \C\L\App();
$app->cli($argv);