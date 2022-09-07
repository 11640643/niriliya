<?php

namespace C\S\User;

use C\L\Service;

class UserStatisCat extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserStatisCat();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' => '等待中',
                'S' => '分析中',
                'Y' => '分析完成'
            ],
            'type' => [
                'ip' => '按IP排序',
                'tj_a1' => '一级推荐人数排序',
                'tj_b1' => '多级推荐人数排序',
                'verify_a' => '按签到排序',
                'verify_b' => '按只签到不充值排序',
                'invest_a' => '按充值金额',
                'money_a' => '按余额排序',
                'item_a' => '按投资金额排序'
            ]
        ];
    }


    public function getNextUserNum($uid, $teration = false)
    {
        $users = $this->di['s_user']->searchAll(['t_uid' => $uid, 'status' => 'Y'], [], ['uid']);
        if (empty($users)) {
            return [];
        }

        $uids = array_column($users, 'uid');

        if ($teration) {
            foreach ($uids as $uid) {
                $uids = array_merge($uids, $this->getNextUserNum($uid, $teration));
            }
        }

        return $uids;
    }

    public function getIps()
    {
        $sql = "select reg_ip from user where status = 'Y' and reg_ip > '' group by reg_ip";
        return $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function apply()
    {
        try {

            if($this->search(['status' => 'D'])){
                throw new \Exception('已有分析在进行');
            }

            $time = time();
            $types = $this->getStatusConfig()['type'];

            $this->di['db']->begin();

            foreach ($types as $k => $type) {
                if (!$this->save(['type' => $k, 'time' => $time])) {
                    throw new \Exception('失败');
                }
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
