<?php

namespace Pack;

use C\L\AdmController;

class UserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_packlist;
//        $this->keyworkSearchKeys = [
//            'no_date',
//        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'ok_time'
        ];

        $this->updateKeys = [
            'money', 'ok_step', 'set_step'
        ];
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            $item['name'] = $user['name'];
            $item['mobile'] = $user['mobile'];
            $backTime = $this->s_pack->getValue('back_time', $item['pack_id']);
            $item['back_time_date'] = $backTime > 0 ? date('Y-m-d H:i:s') : '';
        }

        $data['all_money'] = $this->s_packlist->getSum('money', []);
        return true;
    }

    protected function beforeSearch()
    {
        $keyword = $this->getValue('keyword_search_value');
        if (!empty($keyword)) {
            $users = $this->s_user->searchPage(['mobile' => $keyword], ['mobile' => '%']);
            if(!empty($users)){
                $this->params['data']['uid'] = array_column($users, 'uid');
                $this->params['data_type']['uid'] = 'in';
            }
        }

        $step = $this->getValue('set_step');
        if(!empty($step)){
            $this->params['data']['set_step'] = $step;
        }

        $step = $this->getValue('no_date');
        if(!empty($step)){
            $this->params['data']['no_date'] = $step;
        }

        return true;
    }

}


