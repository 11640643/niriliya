<?php

namespace User;

use C\L\WebUserController;

class PrizeController extends WebUserController
{
    protected function init()
    {
        $this->hideKeys = [
            'is_delete'
        ];
        $this->service = $this->s_prize;
    }

    protected function beforeSearch()
    {
        $this->params['data']['uid'] = $this->uid;
        $this->params['page_num'] = 500;
        return true;
    }


    public function applyAction()
    {
        if ($prize = $this->s_prize->apply($this->uid)) {
            $this->success($prize);
        }

        $this->error();
    }

    public function indexAction()
    {
        $prizeNum = $this->s_user->getValue('prize_num', $this->uid);
        $config = $this->s_config->get('prize');

        $this->success([
            'prize_num' => $prizeNum,
            'prize_name_list' => $this->s_prize->getPirzeNameList(),
            'prize_list' => $this->s_prize->searchAll(['is_show_index' => 'Y']),
            'prize_remark' => $config['remark'],
            'background' => $config['image']
        ]);
    }
}


