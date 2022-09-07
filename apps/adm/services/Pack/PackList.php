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
                'S' => '未提交',
                'D' => '已提交',
                'C' => '结算中',
                'Y' => '已结算',
                'N' => '已失效',
                'X' => '未达标'
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
