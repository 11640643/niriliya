<?php

namespace C\S\User;

use C\L\Service;

class UserLogin extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserLogin();
    }


}
