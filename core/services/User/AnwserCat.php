<?php

namespace C\S\User;

use C\L\Service;

class AnwserCat extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\AnwserCat();
    }

}
