<?php

namespace Shop;

use C\L\AdmController;

class CategoryController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_gcategory;

        $this->likeSearchKeys = [
            'name'
        ];

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime',
        ];

        $this->createKeys = [
            'name', 'image'
        ];

        $this->updateKeys = [
            'name', 'image'
        ];
    }

}


