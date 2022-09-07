<?php

namespace C\S\Goods;

use C\L\Service;

class Category extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\GoodsCategory();
    }

    // public function getStatusConfig()
    // {
    //     return [
    //         'status' => [
    //             'S' => '待发货',
    //             'D' => '已发货',
    //         ],
    //     ];
    // }
}
