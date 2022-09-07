<?php

namespace C\S\Downlog;

use C\L\Service;

class Downlog extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\DownLog();
    }
}
