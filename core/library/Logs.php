<?php

namespace C\L;

use Phalcon\Logger\Adapter\File;

class Logs extends Service
{
    private $logs;

    public function set($file, $path = 'logs')
    {
        $path = APP_PUBLIC . '/' . $path . '/' . date('/Y/m');
        if (!file_exists($path)) {
            $oldumask = @umask(0);
            if (!@mkdir($path, 0777, true)) {
                throw new \Exception('目录创建失败，请检查目录权限');
            }
            @chown($path, 'www-data');
            @chgrp($path, 'www-data');
            @umask($oldumask);
        }

        $isChmod = false;
        $pathFile = $path . '/' . $file;
        if(!file_exists($pathFile)){
            $isChmod = true;
        }

        $this->logs = new File($pathFile);
        if($isChmod){
            $oldumask = @umask(0);
            @chown($pathFile, 'www-data');
            @chgrp($pathFile, 'www-data');
            @chmod($pathFile, 0777);
            @umask($oldumask);
        }

        return $this->logs;
    }
}