<?php

namespace C\S\Pack;

use C\L\Service;

class PackList extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\PackList();
    }

    public function getStatusConfig()
    {   
        return [
            'status' => [
                'S' => $this->translate['new_lang_status_1'],
                'D' => $this->translate['new_lang_status_2'],
                'C' => $this->translate['new_lang_status_3'],
                'Y' =>$this->translate['new_lang_status_4'],
                'N' => $this->translate['has_unable'],
                'X' => $this->translate['new_lang_status_6']
                // 'S' => '未达标',
                // 'D' => '已达标',
                // 'C' => '已达标',
                // 'Y' => '已结算',
                // 'N' => '未达标',
                // 'X' => '未达标'
            ],
        ];
    }

}
