<?php

namespace C\S\User;

use C\L\Service;

class UserStatisCatData extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\UserStatisCatData();
    }
}
