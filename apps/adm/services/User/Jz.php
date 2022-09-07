<?php

namespace C\S\User;

use C\L\Service;

class Jz extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserJz();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'N' => '关闭',
                'Y' => '开启'
            ],
        ];
    }
}
