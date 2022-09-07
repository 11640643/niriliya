<?php

namespace C\S\User;

use C\L\Service;

class UserAnwserList extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserAnwserList();
    }
}
