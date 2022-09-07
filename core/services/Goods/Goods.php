<?php

namespace C\S\Goods;

use C\L\Service;

class Goods extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\GoodsList();
    }

    public function getStatusConfig()
    {
        if ($this->language =='cn' ){
        return [
            // 'is_show_index' => [
            //     'Y' => '是',
            //     'N' => '否'
            // ],
            'status' => [
                'Y' => $this->translate['new_lang_shang_jia'],
                'N' => $this->translate['new_lang_xia_jia'],
            ]
        ];
        }else{
                    return [
            // 'is_show_index' => [
            //     'Y' => '是',
            //     'N' => '否'
            // ],
            'status' => [
                'Y' => $this->translate['new_lang_shang_jia'],
                'N' => $this->translate['new_lang_xia_jia'],
            ]
        ];
        }
    }

}
