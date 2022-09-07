<?php

namespace C\S\Sys;

use C\L\Service;

class Image extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\SysImage();
    }

    public function getStatusConfig()
    {
        return [
            'type' => [
                'banner' => '首页banner',
                'footer' => '底部导航',
                'links' => '首页底部链接'
            ],
            'status' => [
                'Y' => '是',
                'N' => '否'
            ]
        ];
    }

}
