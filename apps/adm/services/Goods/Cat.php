<?php

namespace C\S\Goods;

use C\L\Service;

class Cat extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\GoodsCat();
    }

}
