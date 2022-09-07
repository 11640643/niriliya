<?php

namespace C\S\User;

use C\L\Service;

class AnwserList extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\AnwserList();
    }

}
