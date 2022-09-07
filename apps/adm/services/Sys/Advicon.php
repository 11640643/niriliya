<?php

namespace C\S\Sys;

use C\L\Service;

class Advicon extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\SysAdvicon();
    }

    public function getStatusConfig()
    {
        $tree = $this->di['s_config']->get('tree');
        return [
            'tree' => $tree['tree']
        ];
    }
}
