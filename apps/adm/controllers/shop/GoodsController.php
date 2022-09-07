<?php

namespace Shop;

use C\L\AdmController;

class GoodsController extends AdmController
{
    protected function init()
    {
        
        $this->service = $this->s_goods;

        $this->likeSearchKeys = [
            'name'
        ];

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime',
        ];

        $this->createKeys = [
            'name', 'money', 'thumb', 'remark', 'status', 'category_id', 'vip_exchange'
        ];

        $this->updateKeys = [
            'name', 'money', 'thumb', 'remark', 'status', 'category_id', 'vip_exchange'
        ];
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as $key => &$value) {
            if (isset($value['vip_exchange']) && !empty($value['vip_exchange'])) {
                $value['vip_exchange'] = json_decode($value['vip_exchange']);
            }
        }
        return true;
    }
}


