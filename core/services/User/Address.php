<?php

namespace C\S\User;

use C\L\Service;

class Address extends Service
{
    public function getStatusConfig()
    {
        return [
            'tags' => [
                '家',
                '公司',
                '学校',
            ]
        ];
    }

    protected function setModel()
    {
        $this->model = new \C\M\UserAddress();
    }

}
