<?php

namespace C\S\Pack;

use C\S\Service;

class Pack extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\Pack();
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

    public function apply($uid, $key)
    {
        try {

            $time = strtotime('now +1 day');
            $date = date('Ymd', $time);

            $lockKey = "uid:$uid:pack";
            if (!$this->di['s_user']->lock($lockKey, 10)) {
                throw new \Exception('操作频繁，请稍后重试');
            }

            $pack = $this->search(['no_date' => $date]);
            if (empty($pack) || !key_exists($key, $pack) || $pack[$key] == 0) {
                throw new \Exception('当期红包未设置');
            }

            $no = substr($key, 13, 1);
            if ($this->di['s_packlist']->search(['uid' => $uid, 'no_date' => $date])) {
                throw new \Exception('您已报名');
            }

            $money = $pack['set_task_money' . $no];

            $add = [
                'uid' => $uid,
                'pack_id' => $pack['id'],
                'no_date' => $date,
                'money' => $money,
                'apr' => $pack['set_task_apr' . $no],
                'set_step' => $pack[$key],
                'step_task_key' => $key,
                'not_apr' => $pack['not_apr'],
                'min_money' => $pack['set_task_money' . $no],
            ];

            $this->di['db']->begin();

            $moneyArray = $this->di['s_user']->lockUpdate($uid, -$money, 'money');
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            if (!$cid = $this->di['s_packlist']->save($add)) {
                throw new \Exception('系统忙，请稍后重试');
            }

            if (!$this->di['s_funds']->add($uid, -$money, 'money', 'pack_apply', "运动挑战赛-" . date('md', $time), $cid, 'Y', $moneyArray[0], $moneyArray[1])) {
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
