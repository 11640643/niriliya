<?php

namespace C\S\Item;

use C\L\Service;

class ItemAutoApply extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\ItemAutoApply();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'Y' => '连投成功',
                'D' => '处理中',
                'N' => '连投失败'
            ],
        ];
    }
}
