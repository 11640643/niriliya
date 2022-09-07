<?php

namespace C\S\Clo;

use C\L\Service;

class Clo extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\Clo();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' => '未完成',
                'S' => '结算中',
                'Y' => '已结算',
            ],
            'is_disable' => [
                'Y' => '已关闭',
                'N' => '已开启'
            ]
        ];
    }

    public function apply($uid)
    {
        try {

            $noTime = strtotime('+1 day');
            $noDate = date('Ymd', $noTime);

            $lockKey = "uid:$uid:clo";
            if (!$this->di['s_user']->lock($lockKey, 10)) {
                throw new \Exception('操作频繁，请稍后重试');
            }

            $dk = $this->di['s_clolist']->search(['uid' => $uid, 'no_date' => $noDate]);
            if (!empty($dk)) {
                throw new \Exception('您已报名');
            }

            $clo = $this->search(['no_date' => $noDate]);
            if (empty($clo)) {
                throw new \Exception('当期早起未设置');
            }

            $money = $clo['min_money'];

            $this->di['db']->begin();

            $moneyArray = $this->di['s_user']->lockUpdate($uid, -$money, 'money');
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            $add = [
                'uid' => $uid,
                'min_dk_time' => $clo['min_dk_time'],
                'max_dk_time' => $clo['max_dk_time'],
                'clo_id' => $clo['id'],
                'no_date' => $noDate,
                'apr' => $clo['apr'],
                'money' => $money,
                'not_apr' => $clo['not_apr'],
            ];
            if (!$cid = $this->di['s_clolist']->save($add)) {
                throw new \Exception('系统忙，请稍后重试');
            }

            if (!$this->di['s_funds']->add($uid, -$money, 'money', 'clo_apply', "早起挑战赛-".date('md', $noTime), $cid, 'Y', $moneyArray[0], $moneyArray[1])) {
                throw new \Exception('系统忙，请稍后重试');
            }

            $this->di['db']->commit();
            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }

}
