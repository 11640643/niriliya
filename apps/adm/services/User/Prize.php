<?php

namespace C\S\User;

use C\L\Service;

class Prize extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserPrize();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' => '未处理',
                'Y' => '已处理',
                'N' => $this->translate['has_unable']
            ],
            'is_show_index' => [
                'Y' => '是',
                'N' => '否'
            ]
        ];
    }

    public function apply($uid)
    {
        try {

            $lockKey = "uid:$uid:prize";
            if (!$this->di['s_user']->lock($lockKey, 5)) {
                throw new \Exception('服务器忙,请稍后重试');
            }

            $config = $this->di['s_config']->get('prize');

            if ($config['is_open'] != 'Y') {
                throw new \Exception('抽奖已关闭');
            }

            $rand = mt_rand(100, 10000);
            $prize = $config['prize'][7];
            $kk = 7;

            $spro = 0;
            foreach ($config['prize'] as $k => $p) {

                $pro = intval($p['pro'] * 100);
                if ($pro <= 0) {
                    continue;
                }

                $spro += $pro;
                if ($rand <= $spro) {
                    $prize = $p;
                    $kk = $k;
                    break;
                }

            }

            $this->di['db']->begin();

            $numArray = $this->di['s_user']->lockUpdate($uid, -1, 'prize_num');
            if ($numArray === false) {
                $msg = $this->di['message']->getSerMsg();
                throw new \Exception($msg == '余额不足' ? '次数不足' : $msg);
            }

            if ($prize && $prize['num'] > 0) {

                $user = $this->di['s_user']->search($uid);
                $addData = [
                    'uid' => $uid,
                    'jp_name' => $prize['name'],
                    'jp_pro' => $prize['pro'],
                    'name' => $user['name'],
                    'mobile' => $user['mobile'],
                    'is_show_index' => 'Y',
                    'status' => $prize['type'] == 'money' ? 'Y' : 'N'
                ];

                if (!$cid = $this->save($addData)) {
                    throw new \Exception('系统错误');
                }

                if (!$this->di['s_funds']->add($uid, -1, 'prize_num', 'prize_num_sub', '抽奖', $cid, 'Y', $numArray[0], $numArray[1])) {
                    throw new \Exception('流水添加失败:1001');
                }

                if ($prize['type'] == 'money') {
                    $funName = '抽奖:' . $prize['name'];
                    if (!$this->di['s_funds']->add($uid, $prize['num'], 'money', "prize_money", $funName, $cid)) {
                        throw new Exception('添加流水失败:1002');
                    }
                }

                // 抽奖所得积分计入兑换积分
                if ($prize['type'] == 'credit') {
                    $funName = '抽奖:' . $prize['name'];
                    if (!$this->di['s_funds']->add($uid, $prize['num'], 'exchange_credit', "prize_credit", $funName, $cid)) {
                        throw new Exception('添加流水失败:1002');
                    }
                }
            }

            $this->di['db']->commit();
            $this->di['s_user']->unlock($lockKey);

            return [
                'index' => $kk,
                'name' => $prize['num'] > 0 ? "恭喜您，获得：" . $prize['name'] : $prize['name'],
                'is_prize' => $p['num'] > 0 ? true : false,
                'type' => $prize['type'],
                'num' => $prize['num'],
                'rand' => $rand
            ];

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function getPirzeNameList()
    {
        $config = $this->di['s_config']->get('prize');
        $prize_name_list = [];
        foreach ($config['prize'] as $k => $val) {
            $temp = [];
            $temp['name'] = $val['name'];
            $temp['img'] = $val['img'] ?? '';
            $prize_name_list[] = $temp;
        }
        return $prize_name_list;
    }

}
