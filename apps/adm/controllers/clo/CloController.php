<?php

namespace Clo;

use C\L\AdmController;

class CloController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_clo;
        $this->keyworkSearchKeys = [
            'no_date',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'no_time', 'min_dk_time', 'max_dk_time'
        ];

        $this->updateKeys = [
            'is_disable',
            'min_dk_time',
            'max_dk_time',
            'auto_add_num',
            'auto_add_user_money',
            'lt_uid',
            'apr',
            'min_money',
            'not_apr'
        ];
    }

    protected function afterSearch(&$data)
    {
        $date = date('Ymd');
        $defaultId = 0;
        foreach ($data['list'] as &$item) {

            if($item['no_date'] == $date){
                $defaultId = $item['id'];
            }

            $item['bm_user_num'] = $this->s_clolist->getCount(['no_date' => $item['no_date']]);
            $item['bm_all_money'] = $this->s_clolist->getSum('money', ['no_date' => $item['no_date']]);
            $item['back_money'] = $this->s_clolist->getSum('money', ['no_date' => $item['no_date']]);

        }
        $data['default_id'] = $defaultId;

        return true;
    }

}


