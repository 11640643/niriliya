<?php

namespace User;

use C\C\AdmController;

class AnwserlistController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_anwlist;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->updateKeys = [
            'value', 'cid', 'is_ok'
        ];

        $this->createKeys = [
            'value', 'cid', 'is_ok'
        ];
    }

    protected function beforeUpdate(&$data)
    {
        $anwlist = $this->service->search($this->getValue('id'));
        if(!empty($anwlist)){
            $this->service->updates(['is_ok' => 'N'], ['cid' => $anwlist['cid']]);
        }
        return true;
    }

}


