<?php

namespace User;

use C\C\AdmController;

class AnwserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_anw;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->likeSearchKeys = [
            'title'
        ];

        $this->updateKeys = [
            'title',
        ];

        $this->createKeys = [
            'title'
        ];
    }

    protected function afterSearch(&$data)
    {
        $data['sum'] = $this->s_uanw->getCount();
        $data['ok_sum'] = $this->s_uanw->getCount(['status' => 'Y']);

        foreach($data['list'] as &$item){
            $item['ok_sum'] = $this->s_uanwlist->getCount(['acid' => $item['id'], 'status' => 'Y']);
        }
        return true;
    }

    protected function afterView(&$data)
    {
        $data['values'] = $this->s_anwlist->searchAll(['cid' => $data['view']['id']]);
        return true;
    }

    protected function afterCreate(&$data)
    {
        $values = $this->request->getPost('values');
        $adds = [];
        $time = time();
        foreach($values as $v){
            $adds[] = [
                'cid' => $data['id'],
                'is_ok' => $v['is_ok'],
                'value' => $v['label'],
                'uptime' => $time,
                'addtime' => $time,
            ];
        }

        if($adds){
            $this->s_anwlist->saves($adds);
        }

        return true;
    }
}


