<?php

namespace C\S\Business;

use C\L\Service;

class BusinessCoo extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\BusinessCoo();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'Y' => '开启',
                'N' => '关闭'
            ],
        ];
    }

    public function start($uid)
    {
        try {

            $config = $this->di['s_config']->get('anwser');
            $time = time();

            $uanw = $this->di['s_uanw']->search(['uid' => $uid, 'no' => $config['no']]);
            if ($uanw && $uanw['extime'] > $time && !in_array($uanw['status'], ['Y', 'N'])) {
                return $uanw['id'];
            }

            if ($config['is_open'] != 'Y' || $config['begin_time'] > $time || $time > $config['begin_time'] + $config['stop_min'] * 60) {
                throw new \Exception('活动未开始');
            }

            if ($this->getAnwNum($uid) < 1) {
                throw new \Exception('答题次数不足');
            }

            $anwsers = $this->di['s_anw']->searchAll();

            $count = count($anwsers);
            if ($config['c'] > $count) {
                $config['c'] = $count;
            }

            $anwIds = [];
            $anwC = [];
            while (true) {
                $tmp = $anwsers[mt_rand(0, $count - 1)];
                $tmpId = $tmp['id'];
                if (!in_array($tmpId, $anwIds)) {
                    $anwIds[] = $tmpId;
                    $anwC[] = $tmp;
                    if (count($anwIds) >= $config['c']) {
                        break;
                    }
                }
            }

            $add = [
                'uid' => $uid,
                'extime' => 60 * $config['stop_min'] + $time,
                'anwc' => $config['c'],
                'no' => $config['no']
            ];

            $anwVipNum = $this->getAnwNum($uid, false);
            $this->di['db']->begin();

            if($anwVipNum < 1){
                $numArray = $this->di['s_user']->lockUpdate($uid, -1, 'anwser_num');
                if ($numArray === false) {
                    $msg = $this->di['message']->getSerMsg();
                    throw new \Exception($msg == '余额不足' ? '答题次数不足' : $msg);
                }
            }

            if (!$cid = $this->save($add)) {
                throw new \Exception('提交失败');
            }

            if($anwVipNum < 1){
                if (!$this->di['s_funds']->add($uid, -1, 'anwser_num', 'anwser_num_sub', '答题', $cid, 'Y', $numArray[0], $numArray[1])) {
                    throw new \Exception('流水添加失败:1001');
                }
            }

            $adds = [];
            foreach ($anwC as $v) {
                $adds[] = [
                    'addtime' => $time,
                    'uptime' => $time,
                    'acid' => $v['id'],
                    'cid' => $cid,
                    'uid' => $uid,
                ];
            }

            if (!$this->di['s_uanwlist']->saves($adds)) {
                throw new \Exception('提交失败');
            }

            $this->di['db']->commit();

            return $cid;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function apply($uid, $vid, $lid)
    {
        try {

            $lockKey = "uid:$uid:anwserapply";
            if (!$this->di['s_user']->lock($lockKey, 1)) {
                throw new \Exception('操作频繁，请稍后重试');
            }

            $uanwl = $this->di['s_uanwlist']->search($lid);
            if (empty($uanwl) || $uanwl['status'] != 'S') {
                return 'N';
            }

            $update = [
                'status' => 'N',
            ];
            if ($vid > 1 && $uanwl['extime'] >= time()) {
                $anwl = $this->di['s_anwlist']->search($vid);
                if ($anwl['is_ok'] == 'Y') {
                    $update['status'] = 'Y';
                }
            }

            if (!$this->di['s_uanwlist']->update($lid, $update)) {
                throw new \Exception('修改失败');
            }

            return $update['status'];

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function getAnwl($cid)
    {
        $config = $this->di['s_config']->get('anwser');

        $uanwl = $this->di['s_uanwlist']->search(['cid' => $cid, 'status' => 'D']);
        if (empty($uanwl)) {
            $this->di['s_uanw']->update($cid, ['status' => 'N']);
            return false;
        }

        if ($this->di['s_uanwlist']->getCount(['cid' => $cid, 'status' => 'D']) < 2) {
            $this->di['s_uanw']->update($cid, ['status' => 'N']);
        }

        $this->di['s_uanwlist']->update($uanwl['id'], ['extime' => time() + $config['m'], 'status' => 'S']);
        $uanwl['m'] = $config['m'];

        return $uanwl;
    }

    public function getAnwNum($uid, $is_user_anw_num = true)
    {
        $config = $this->di['s_config']->get('anwser');
        $level = $this->di['s_level']->search(['id' => $config['vip_key']]);
        $user = $this->di['s_user']->search($uid);

        $num = $config['anw_num'];
        if ($user['credit'] >= $level['credit']) {
            $num = $config['anw_vip_num'];
        }
        $num = $num - $this->di['s_uanw']->getCount(['uid' => $uid, 'no' => $config['no']]);
        if($is_user_anw_num){
            return $num > 0 ? $num : $user['anwser_num'];
        }

        return $num;
    }
}
