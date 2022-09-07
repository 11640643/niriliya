<?php

namespace Clo;

use C\L\AdmController;

class UserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_clolist;
        $this->keyworkSearchKeys = [
            'uid',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'ok_time'
        ];

        $this->updateKeys = [
            'money', 'ok_step', 'set_step'
        ];

        $this->pubSearchKeys = [
            'no_date'
        ];
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            $item['name'] = $user['name'];
            $item['mobile'] = $user['mobile'];
            $backTime = $this->s_pack->getValue('back_time', $item['clo_id']);
            $item['back_time_date'] = $backTime > 0 ? date('Y-m-d H:i:s') : '';
            $item['set_dk_date'] = date('H:i', $item['min_dk_time']) . '-' . date('H:i', $item['max_dk_time']);
        }
        $data['all_money'] = $this->s_clolist->getSum('money');
        return true;
    }


}


