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
                'N' => $this->translate['item_status_n'],
                'Y' => $this->translate['item_status_y'],
            ],
        ];
    }
}
