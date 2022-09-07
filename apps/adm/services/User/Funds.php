<?php

namespace C\S\User;

use C\S\Service;

class Funds extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserFunds();
    }

    public function add($uid, $money, $type, $stype, $title, $cid, $status = 'D', $beforMoney = 0, $afterMoney = 0, $remark = '')
    {
    
        $add = [
            'cid' => $cid,
            'uid' => $uid,
            'money' => $money,
            'type' => $type,
            'stype' => $stype,
            'title' => $title,
            'status' => $status,
            'befor_money' => $beforMoney,
            'after_money' => $afterMoney,
            'btype' => $money < 0 ? 'sub' : 'add',
            'remark' => $remark
        ];

        // echo '<pre/>';print_r($add);
        // exit();
        return $this->save($add);
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' => '待处理',
                'S' => '处理中',
                'Y' => '处理成功'//'处理成功'
            ],
            'type' => [
                'money' => '金额',
                'credit' => '等级积分',
                'prize_num' => '抽奖',
                'exchange_credit' => '兑换积分',
                // 'manure' => '肥料',
                // 'water' => '浇水',
                // 'fruit' => '果实',
//                'anwser_num' => '答题',
            ],
            'stype' => [
                'bank_invest_back' => $this->translate['bank_incoe_apr'],
                'back_money' => '满返本金',
                'pack_send' => '红包发送',
                'prize_money' => '抽奖红包',
                'prize_num_sub' => '抽奖减少',
                'prize_num_add_item' => '投资项目',
                'sys_prize_num' => '后台操作增减抽奖',
                'sys_money' => '后台操作增减金额',
                'sys_anwser_num' => '后台操作增减答题',
                'item_pack' => '投资送红包',
                'item_credit' => $this->translate['item_credit'],
                'itemdq_apr' => '理财利息',
                'itemdq_money' => '理财本金',
                'invest_wx' => '微信充值',
                'invest_alipay' => '支付宝充值',
                'invest_bank' => '银行卡充值',
                'cost' => '提现',
                'invest_credit' => '充值送兑换积分',
                'itemdq_apply' => '项目投资',//'项目投资',
                'cost_back' => '提现拒绝退还',
                'share_money' => '邀请好友注册奖励',
                'reg_money' => '新用户注册奖励',
                'huzhuan_out' => '会员互转-转出',
                'huzhuan_in' => '会员互转-转入',
                'checkin' => '签到奖励',
                'reward_invest' => '好友充值奖励',
                'reward_item' => '好友投资奖励',
                'reward_item_f' => '好友投资分润奖励',
                'apply_item' => '参与投资任务',
                // 'manure_apply' => '浇水',
                // 'water_apply' => '施肥',
                // 'tree_pluck' => '果实采摘',
                'goods_apply' => '积分换购商品',
                'clo' => '早起挑战赛',
                'pack' => '运动挑战赛',
                // 'yeb' => '余额宝任务',
                // 'clos' => '持续早起任务',
                'auth' => '认证任务',
                'task_item' => '投资理财任务',
                // 'pack_step' => '达3000步任务',
                'pack_apply' => '运动挑战赛报名',
                'pack_back' => '运动挑战赛退还',
                'clo_apply' => '早起挑战赛报名',
                'clo_back' => '早起挑战赛退还',
                // 'login_manure' => '每日赠送肥料',
                // 'login_water' => '每日赠送浇水',
                'cumulative_sign' => '累计签到奖励',
//                'anwser' => '答题奖励',
//                'anwser_num_sub' => '参与答题',
            ],
        ];
    }

    /**
     * 新增批量红包
     *
     */
    public function addPack($name, $vip, $money)
    {

        try {

            if (empty($name)) {
                throw new \Exception('红包名不能为空');
            }

            if ($money < 0.01) {
                throw new \Exception('红包金额最小为0.01');
            }

            $vipConfig = $this->di['s_level']->search($vip);
            if (empty($vipConfig)) {
                throw new \Exception('未找到当前vip配置');
            }

            $data = [];
            $scores = $this->di['s_level']->getNextLevelScore($vip);
            if ($scores[1] > 0) {
                $scores[1] -= 1;
                $data['data']['credit'] = $scores;
                $data['data_type']['credit'] = 'between';
            } else {
                $data['data']['credit'] = $scores[0];
                $data['data_type']['credit'] = '>=';
            }

            if (empty($data)) {
                throw new \Exception('添加失败');
            }

            $users = $this->di['s_user']->searchAll($data['data'], $data['data_type'], ['uid', 'credit']);
            if (empty($users)) {
                throw new \Exception('当前VIP等级下无用户');
            }

            $adds = [];
            $time = time();
            foreach ($users as $user) {
                $adds[] = [
                    'cid' => $user['uid'],
                    'uid' => $user['uid'],
                    'money' => $money,
                    'type' => 'money',
                    'stype' => 'pack_send',
                    'title' => $name,
                    'status' => 'D',
                    'uptime' => $time,
                    'addtime' => $time,
                ];
            }

            if (!$this->saves($adds)) {
                throw new \Exception('添加失败');
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

}
