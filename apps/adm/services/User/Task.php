<?php

namespace C\S\User;

use C\L\Service;

class Task extends Service
{
    public $count = 0;

    protected function setModel()
    {
        $this->model = new \C\M\UserTask();
    }

    public function getStatusConfig()
    {
        $config = [
            'status' => [
                'D' => '处理中',
                'Y' => '已参与',
                'N' => '无效'
            ],
            'type' => [
                'credit' => '积分奖励',
                'money' => '余额奖励'
            ],
            'stype' => [
                'login' => '每日登录',
                'auth' => '实名认证',
                'reg' => '邀请好友注册',
                'clo' => '报名早起挑战赛',
               'pack' => '报名运动挑战赛',
                // 'yeb' => '参与松鼠宝库',
                'clos' => '持续早起',
//                'pack_step' => '每日达3000步',
            ],
        ];
        foreach ($this->config() as $k => $v) {
            $config['stype'][$k] = $v['title'];
        }

        return $config;
    }

    public function config()
    {
        return [
            'signin' => [
                'key' => 'signin',
                'type' => 'money',
                'money' => 3,
                'title' => '每日签到',
                't1' => '每日领福利',
                't2' => '奖励现金',
                't3' => '每日签到即可完成任务',
            ],
//            'reg' => [
//                'key' => 'reg',
//                'type' => 'money',
//                'money' => 3,
//                'title' => '邀请好友注册',
//                't1' => '推荐好友注册',
//                't2' => "奖励3现金（已完成%s/3)",
//                't3' => '推荐好友注册并完成实名认证',
//            ],

            'auth' => [
                'key' => 'auth',
                'type' => 'money',
                'money' => 3,
                'title' => '实名认证',
                't1' => '实名认证',
                't2' => "奖励3现金",
                't3' => '完成实名认证后即可领取',
            ],
            // 'task_item' => [
            //     'key' => 'task_item',
            //     'type' => 'money',
            //     'money' => 5,
            //     'title' => '投资理财产品',
            //     't1' => '参与投资',
            //     't2' => '奖励5现金',
            //     't3' => '当日投入即可完成任务',
            // ],
            'apply_item' => [
                'key' => 'apply_item',
                'type' => 'exchange_credit',
                'money' => 100,
                'title' => '投资理财产品',
                't1' => '参与投资',
                't2' => '奖励100积分',
                't3' => '当日投入即可完成任务',
            ],
            'pack' => [
                'key' => 'pack',
                'type' => 'exchange_credit',
                'money' => 20,
                'title' => '运动挑战赛',
                't1' => '运动挑战赛',
                't2' => '奖励20积分',
                't3' => '当日报名即可完成任务',
            ],
        //    'pack_step' => [
        //        'key' => 'pack_step',
        //        'type' => 'exchange_credit',
        //        'money' => 100,
        //        'title' => '每日达3000步',
        //        't1' => '每日达3000步',
        //        't2' => '奖励100积分',
        //        't3' => '当日完成提交即可完成任务',
        //    ],
            'prize' => [
                'key' => 'prize',
                'type' => 'exchange_credit',
                'money' => 100,
                'title' => '幸运转盘',
                't1' => '幸运转盘',
                't2' => '抽奖领好礼',
                't3' => '',
            ],
            'yao' => [
                'key' => 'yao',
                'type' => 'exchange_credit',
                'money' => 100,
                'title' => '邀请好友',
                't1' => '邀请好友',
                't2' => '奖励现金',
                't3' => '邀请好友领好礼',
            ],
            'clo' => [
                'key' => 'clo',
                'type' => 'exchange_credit',
                'money' => 60,
                'title' => '早起挑战赛',
                't1' => '早起挑战赛',
                't2' => '奖励60积分',
                't3' => '当日报名即可完成任务',
            ],
            'clos' => [
                'key' => 'clos',
                'type' => 'exchange_credit',
                'money' => 200,
                'title' => '持续早起',
                't1' => '持续早起',
                't2' => '奖励200积分',
                't3' => "连续3天即可完成任务（已完成%s/3）",

            ],
        ];
    }

    public function add($uid, $stype)
    {
        try {

            $lockKey = "uid:$uid:task:$stype";
            if (!$this->di['s_user']->lock($lockKey, 5)) {
                throw new \Exception('服务器忙,请稍后重试');
            }

            if (empty($this->config()[$stype])) {
                throw new \Exception('非法操作');
            }

            if ($this->check($uid, $stype)) {
                throw new \Exception('奖励已领取');
            }

            if ($this->$stype($uid) != 'N') {
                throw new \Exception('任务未完成');
            }

            $money = $this->config()[$stype]['money'];
            $type = $this->config()[$stype]['type'];
            $add = [
                'uid' => $uid,
                'stype' => $stype,
                'type' => $type,
                'money' => $money,
                'status' => 'Y'
            ];

            $this->di['db']->begin();
            if (!$cid = $this->save($add)) {
                throw new \Exception('添加失败');
            }

            if (!$this->di['s_funds']->add($uid, $money, $type, $stype, $this->config()[$stype]['title'], $cid)) {
                throw new \Exception('添加失败');
            }

            $this->di['db']->commit();
            $this->di['s_user']->unlock($lockKey);
            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    private function check($uid, $stype = 'auth')
    {
        $todayTime = $todayTime = strtotime(date('Ymd'));
        $data = ['uid' => $uid, 'stype' => $stype, 'status' => ['D', 'Y']];
        $dataType = ['status' => 'in'];
        if ($stype != 'auth') {
            $data['addtime'] = $todayTime;
            $dataType['addtime'] = '>=';
        }
	
        if ($this->search($data, $dataType)) {
            return true;
        }

        return false;
    }

    public function login($uid)
    {

        if ($this->check($uid, 'login')) {
            return 'Y';
        }

        return 'N';
    }


    public function auth($uid)
    {
	#var_dump($uid,$this->check($uid, 'auth'));exit;
        if ($this->check($uid, 'auth')) {
            return 'Y';
        }
	
        $user = $this->di['s_user']->search($uid);
	if ($user['is_auth'] == 'Y') {
            return 'N';
        }

        return 'S';
    }

    public function reg($uid)
    {
        $todayTime = strtotime(date('Ymd'));
        $this->count = $regCount = $this->di['s_user']->getCount(['t_uid' => $uid, 'is_auth' => 'Y', 'addtime' => $todayTime], ['addtime' => '>=']);

        if ($this->check($uid, 'reg')) {
            return 'Y';
        }

        if ($regCount >= 3) {
            return 'N';
        }

        return 'S';
    }

    public function clo($uid)
    {
        if ($this->check($uid, 'clo')) {
            return 'Y';
        }

        $no_date = date('Ymd');
        $todayClo = $this->di['s_clolist']->search([
            'uid' => $uid, 'no_date' => $no_date, 'status' => 'D'
        ]);
        if (!empty($todayClo)) {
            return 'N';
        }

        return 'S';
    }

    public function pack($uid)
    {
        if ($this->check($uid, 'pack')) {
            return 'Y';
        }

        $no_date = date('Ymd');
        $set_step = $this->di['s_packlist']->getValue('set_step', ['uid' => $uid]);
        $todayPack = $this->di['s_packlist']->search([
            'uid' => $uid, 'no_date' => $no_date, 'status' => 'D', 'ok_step' => $set_step
        ], ['ok_step' => '>=']);
        if (!empty($todayPack)) {
            return 'N';
        }

        return 'S';
    }

    public function pack_step($uid)
    {
        if ($this->check($uid, 'pack_step')) {
            return 'Y';
        }

        $step = 3000;
        $ssid = $this->di['cache']->get('ssid_' . $uid);

        if ($this->di['cache']->hGet($ssid, 'step_' . date('Ymd')) >= $step) {
            return 'N';
        }

        return 'S';
    }

    public function yeb($uid)
    {
        if ($this->check($uid, 'yeb')) {
            return 'Y';
        }

        $todayTime = strtotime(date('Ymd'));
        $todayYeb = $this->di['s_yebin']->search(['uid' => $uid, 'addtime' => $todayTime], ['addtime' => '>=']);
        if (!empty($todayYeb)) {
            return 'N';
        }

        return 'S';
    }

    public function clos($uid)
    {
        $beginTime = strtotime(date('Ymd')) - 2 * 24 * 3600;
        $this->count = $closCount = $this->di['s_clolist']->getCount([
            'uid' => $uid, 'addtime' => $beginTime, 'status' => ['D', 'Y']
        ], ['addtime' => '>=', 'status' => 'in']);

        if ($this->check($uid, 'clos')) {
            return 'Y';
        }

        $clos = $this->di['s_task']->search(['uid' => $uid, 'status' => ['D', 'Y'], 'stype' => 'clos'], ['status' => 'in']);
        if (!empty($clos) && $clos['addtime'] > $beginTime) {
            $beginTime = strtotime(date('Ymd', $clos['addtime'])) + 24 * 3600;
            $this->count = $closCount = $this->di['s_clolist']->getCount(['uid' => $uid, 'addtime' => $beginTime], ['addtime' => '>=']);
        }
        if ($closCount >= 3) {
            return 'N';
        }

        return 'S';
    }

    public function task_item($uid)
    {
        if ($this->check($uid, 'task_item')) {
            return 'Y';
        }

        $todayTime = strtotime(date('Ymd'));
        $todayPack = $this->di['s_il']->search(['uid' => $uid, 'addtime' => $todayTime], ['addtime' => '>=']);
        if (!empty($todayPack)) {
            return 'N';
        }

        return 'S';
    }

    public function apply_item($uid)
    {
        if ($this->check($uid, 'apply_item')) {
            return 'Y';
        }

        $todayTime = strtotime(date('Ymd'));
        $todayPack = $this->di['s_il']->search(['uid' => $uid, 'addtime' => $todayTime], ['addtime' => '>=']);
        if (!empty($todayPack)) {
            return 'N';
        }

        return 'S';
    }

    public function signin($uid)
    {
        $check_status = $this->di['s_user']->getTodayCheckin($uid);
        if ($check_status) {
            return 'Y';
        }
        return 'S';
    }
}
