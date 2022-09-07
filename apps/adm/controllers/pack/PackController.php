<?php

namespace Pack;

use C\L\AdmController;

class PackController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_pack;
        $this->keyworkSearchKeys = [
            'no_date',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'no_time'
        ];

        $this->updateKeys = [
            'lt_uid',
            'is_disable',
            'set_task_step1',
            'set_task_apr1',
            'set_task_step2',
            'set_task_apr2',
            'set_task_step3',
            'set_task_apr3',
            'set_task_step4',
            'set_task_apr4',
            'set_task_money1',
            'set_task_money2',
            'set_task_money3',
            'set_task_money4',
            'auto_add_num',
            'auto_add_user_money',
            'min_money',
            'not_apr'
        ];

    }


    public function afterSearch(&$data)
    {
        $date = date('Ymd');
        $defaultId = 0;
        foreach ($data['list'] as &$item) {

            for ($i = 1; $i < 5; $i++) {
                $where = ['pack_id' => $item['id'], 'step_task_key' => 'set_task_step' . $i];
                $item['step' . $i] = [
                    'user_num' => $this->s_packlist->getCount($where),
                    'user_money' => $this->s_packlist->getSum('money', $where),
                    'step' => $item['set_task_step' . $i],
                    'apr' => $item['set_task_apr' . $i],
                ];
            }

            if($item['no_date'] == $date){
                $defaultId = $item['id'];
            }

            $item['bm_all_money'] = $this->s_packlist->getSum('money', ['no_date' => $item['no_date']]);
            $item['bm_user_num'] = $this->s_packlist->getCount(['no_date' => $item['no_date']]);
            $item['back_all_money'] = $this->s_packlist->getSum('back_money', ['no_date' => $item['no_date']]);
        }
        $data['default_id'] = $defaultId;

        return true;
    }
}


