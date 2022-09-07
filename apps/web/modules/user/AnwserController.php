<?php

namespace User;

use C\L\WebUserController;

class AnwserController extends WebUserController
{

    public function indexAction()
    {
        $config = $this->s_config->get('anwser');
        $status = 'Y';
        $msg = $this->translate['active_not_begin'];
        $anw_num = $this->s_uanw->getAnwNum($this->uid);

        $time = time();
        if ($config['is_open'] == 'Y') {

            if($time > $config['begin_time'] + $config['stop_min'] * 60){
                $status = 'S';
                $msg = $this->translate['active_has_end'];
            }

            if($anw_num < 1){
                $status = 'S';
                $msg = $this->translate['qa_num_limit'];
            }

            if($config['begin_time'] > $time){
                $status = 'N';
                $msg = $this->translate['active_not_begin'];
            }

        }else{
            $status = 'N';
            $msg = $this->translate['active_not_begin'];
        }

        $this->success([
            'remark' => $config['remark'],
            'status' => $status,
            'msg' => $msg,
            'num' => $anw_num
        ]);

    }

    public function startAction()
    {
        if (!$cid = $this->s_uanw->start($this->uid)) {
            $this->error();
        }

        $uanwl = $this->s_uanw->getAnwl($cid);
        if (empty($uanwl)) {
            $this->success([
                'status' => 'N'
            ]);
        }

        $this->success([
            'lid' => $uanwl['id'],
            'id' => $uanwl['acid'],
            'title' => $this->s_anw->search($uanwl['acid'])['title'],
            'values' => $this->s_anwlist->searchAll(['cid' => $uanwl['acid']], [], ['id', 'value']),
            'm' => $uanwl['m'],
            'status' => 'Y'
        ]);
    }

    public function applyAction()
    {
        $vid = $this->getValue('vid', true, 'int');
        $lid = $this->getValue('lid', true, 'int');

        if ($status = $this->s_uanw->apply($this->uid, $vid, $lid)) {
            $cid = $this->s_uanwlist->getValue('cid', ['uid' => $this->uid, 'id' => $lid]);
            $count = $this->s_uanwlist->getCount(['cid' => $cid, 'status' => 'D']);
            if ($count < 1) {
                $okCount = $this->s_uanwlist->getCount(['cid' => $cid, 'status' => 'Y']);
                if ($okCount == $this->s_uanw->getValue('anwc', ['id' => $cid])) {
                    $this->s_uanw->update($cid, ['status' => 'Y']);
                }
            }
            $this->success([
                'status' => $status,
                'count' => $count
            ]);
        }

        $this->error();
    }

    public function stopAction()
    {
        $config = $this->s_config->get('anwser');
        $uanw = $this->s_uanw->search(['uid' => $this->uid, 'no' => $config['no']]);

        $okSum = 0;
        $errSum = 0;
        $date = '00:00';
        if($uanw){
            $okSum = $this->s_uanwlist->getCount(['cid' => $uanw['id'], 'status' => 'Y']);
            $errSum = $this->s_uanwlist->getCount(['cid' => $uanw['id'], 'status' => 'N']);
            $date = $this->intToDate($uanw['uptime'] - $uanw['addtime']);
        }

        $stopTime = $config['begin_time'] + ($config['stop_min'] * 60) - time();
        $anw_ok_num = $this->s_uanw->getCount(['status' => 'Y', 'no' => $config['no']]) + $config['anw_ok_sum'];

        $this->success([
            'money' => bcdiv($config['money'], $anw_ok_num, 2),
            'anw_user_sum' => $this->s_uanw->getCount(['no' => $config['no']]) + $config['anw_sum'],
            'anw_ok_user_sum' => $anw_ok_num,
            'ok_sum' => $okSum,
            'err_sum' => $errSum,
            'date' => $date,
            'hdate' => $this->intToDate($stopTime),
            'time' => $stopTime > 0 ? $stopTime : 0,
        ]);
    }

    private function intToDate($num)
    {
        if ($num <= 0) {
            return '00:00';
        }

        if ($num < 60) {
            return '00:' . sprintf('%02d', $num);
        }

        if ($num <= 3600) {
            return sprintf('%02d', intval($num / 60)) . ':' . sprintf('%02d', $num % 60);
        }

        return sprintf('%02d', intval($num / 3600)) . ':' . sprintf('%02d', intval($num % 3600 / 60)) . ':' . sprintf('%02d', $num % 60);
    }
}


