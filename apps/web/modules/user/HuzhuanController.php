<?php

namespace User;

use C\L\WebUserController;

class HuzhuanController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_funds;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->pubSearchKeys = [
            'type', 'stype', 'month'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['page_num'] = 500;
        $this->params['data']['stype'] = [
            'huzhuan_in', 'huzhuan_out'
        ];
        $this->params['data_type']['stype'] = 'in';
        $this->params['data']['status'] = 'Y';
        $this->params['data']['uid'] = $this->uid;
        if (isset($this->params['data']['month'])) {
            $month = $this->params['data']['month'];
            list($year, $month) = explode('-', $month);
            unset($this->params['data']['month']);
            $t = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $stime = mktime(0, 0, 0, $month, 1, $year);
            $etime = mktime(23, 59, 59, $month, $t, $year);
            $this->params['data']['addtime'] = [$stime, $etime];
            $this->params['data_type']['addtime'] = 'between';
        }
        return true;
    }

    public function applyAction()
    {
        $mobile = $this->getValue('mobile', true, 'string');
        $passwd = $this->getValue('passwd', true, 'string');
        $money = $this->getValue('money', true, 'string');

        if ($this->s_user->huzhuan($this->uid, $money, $mobile, $passwd)) {
            $this->success();
        }

        $this->error();
    }

    public function indexAction()
    {
        $this->checkSetPayPwd();
        $user = $this->di['s_user']->search($this->uid);
        $res = [
            'uid' => $this->uid,
            'money' => $user['money'],
        ];
        $this->success($res);
    }

}


