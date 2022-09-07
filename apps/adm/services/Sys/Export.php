<?php

namespace C\S\Sys;

use C\L\Service;

class Export extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\ExportFile();
    }


    public function getStatusConfig()
    {
        return [
            'type' => [
                'user' => '用户',
            ]
        ];
    }
}
