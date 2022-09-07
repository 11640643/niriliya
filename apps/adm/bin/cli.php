<?php

define('APP_NAME', 'web');
define('APP_PUBLIC', __DIR__ . '/../../../www/web');
define('CORE', APP_PUBLIC . '/../../core');
define('APP_PATH', APP_PUBLIC . '/../../apps/' . APP_NAME);
define('APP_MODE', 'dev');

require CORE . '/library/App.php';
$app = new \C\L\App();
$app->cli($argv);