<?php

namespace Pack;

use C\L\WebUserController;

class InfoController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_packlist;
        $this->showKeys = [
            'id', 'money', 'addtime', 'no_date', 'status'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['data']['uid'] = $this->uid;
        $status = $this->getValue('status');
        if ($status == 'A') {
            $this->params['data']['status'] = ['S', 'D', 'C'];
            $this->params['data_type']['status'] = 'in';
        }

        if ($status == 'B') {
            $this->params['data']['status'] = ['Y', 'N'];
            $this->params['data_type']['status'] = 'in';
        }
        return true;
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $item['sdate'] = date('Y-m-d h:i', $item['addtime']);
            $item['edate'] = date('Y-m-d 23:59', strtotime($item['no_date']));
        }
        return true;
    }

    //红包首页接口
    public function indexAction()
    {
        $uid = $this->uid;
        $todayTime = strtotime(date('Ymd'));
        $tomorrowTime = $todayTime + 24 * 3600;
        $bmTaskStep = 0;
        $todayPack = $this->s_packlist->search([
            'uid' => $uid,
            'no_date' => date('Ymd', $todayTime),
        ]);

        $tomorrowPack = $this->s_packlist->search([
            'uid' => $uid,
            'no_date' => date('Ymd', $tomorrowTime),
        ]);

        $packTime = $tomorrowTime;
        if (empty($todayPack) && empty($tomorrowPack)) {
            $showPageStatus = 'X';
        }

        if (empty($todayPack) && !empty($tomorrowPack)) {
            $bmTaskStep = $tomorrowPack['set_step'];
            $showPageStatus = 'C';
        }

        $isShowNotice = 'N';
        if (!empty($todayPack)) {
            $packTime = $todayTime;
            $bmTaskStep = $todayPack['set_step'];
            $showPageStatus = $todayPack['status'];
            if (date('Hi') > 2330) {
                $isShowNotice = 'Y';
            }
        }

        $bmAllMoney = 0;
        $bmUserNum = 0;
        $no = '';

        $packDate = date('Ymd', $packTime);
        $pack = $this->s_pack->search(['no_date' => $packDate]);
        
	if (!empty($pack)) {
            $no = date('md', strtotime($pack['no_date']));
            $bmAllMoney = $pack['set_all_money'] + $this->s_packlist->getSum('money', ['no_date' => $packDate]);
            $bmUserNum = $pack['set_user_num'] + $this->s_packlist->getCount(['no_date' => $packDate]);
            if ($bmTaskStep == 0) {
                $bmTaskStep = $pack['set_task_step1'] ?: $pack['set_task_step2'] ?: $pack['set_task_step3'] ?: $pack['set_task_step4'];
            }
        }

        $tomorrowPack_ = $this->s_pack->search(['no_date' => date('Ymd', $tomorrowTime)]);
        $data = [
            'money' => $this->s_user->getValue('money', $this->uid), //余额
            'no' => $no, //期数
            'bm_all_money' => $bmAllMoney, //总金额
            'bm_user_num' => $bmUserNum, //报名人数
            'bm_task_step' => $bmTaskStep, //任务步数
            'show_page_status' => $showPageStatus, //X:显示报名页面，S:显示提交成绩页面，D:显示奖金结算页面，C:显示明天开赛页面
            'is_show_notice' => $isShowNotice,
            'end_time' => strtotime(date('Ymd') . ' +1 day'),
            'next' => [
                'tomorrow_running_title' => '明日 '.date('m月d日', $tomorrowTime).' 00:00 开赛',
                'no' => date('md', $tomorrowTime),
                'bm_user_num' => ($tomorrowPack_['set_user_num'] ?? 0) + $this->s_packlist->getCount(['no_date' => date('Ymd', $tomorrowTime)]),
                'bm_all_money' => ($tomorrowPack_['set_all_money'] ?? 0) + $this->s_packlist->getSum('money', ['no_date' => date('Ymd', $tomorrowTime)]),
                'is_bm_next' => empty($tomorrowPack) ? 'N' : 'Y', //是否报名下一期
            ]
        ];

        $this->success($data);
    }

    //立即报名接口
    public function bminfoAction()
    {
        // $this->error('红包已关闭');
        $time = strtotime('now +1 day');
        $date = date('Ymd', $time);
        $nextPack = $this->s_pack->search(['no_date' => $date]);
        $config = [];
        foreach ($nextPack as $k => $v) {
            if (strstr($k, 'set_task_step') && $v > 0) {
                $config[$k] = [
                    'key' => $k,
                    'step' => $v,
                    'apr' => $nextPack['set_task_apr' . substr($k, 13, 1)],
                    'money' => $nextPack['set_task_money' . substr($k, 13, 1)],
                    'is_default' => 'N'
                ];
            }
        }

        $config = array_values($config);
        if (!empty($config)) {
            $config[0]['is_default'] = 'Y';
        }
        $userMoney = $this->s_user->getValue('money', $this->uid);
        $data = [
            'money' => $userMoney,
            'no' => date('md', $time),
            'begin_date' => date('Y/m/d', $time) . ' 00:00-23:59',
            'config' => $config,
            'min_money' => $nextPack['min_money'],
            'repay' => [
                [
                    'type' => 'money',
                    'name' => "余额支付(可用余额：{$userMoney}卡币)",
                    'is_default' => 'Y'
                ]
            ]
        ];
        $this->success($data);
    }

    //红包报名
    public function applyAction()
    {
        $this->checkAuth();

        // $this->error('红包已关闭');

        $key = $this->getValue('key', true);

        if ($this->s_pack->apply($this->uid, $key)) {
            $this->success();
        }

        $this->error();
    }

    //步数提交
    public function okAction()
    {
        $this->error('非法操作');
        $uid = $this->uid;
        $stepKey = 'step_' . date('Ymd');
        $todayStep = $this->ssid->get($stepKey) ?: 0;
        $pack = $this->s_packlist->search(['uid' => $uid, 'no_date' => date('Ymd')]);
        if (empty($pack)) {
            $this->error('未找到报名记录');
        }

        if ($todayStep < $pack['set_step']) {
            $this->error('未达标');
        }

        $update = [
            'ok_time' => time(),
            'ok_step' => $todayStep,
            'status' => 'D'
        ];
        if ($this->s_packlist->update($pack['id'], $update)) {
            $this->success();
        }

        $this->error();
    }
}


