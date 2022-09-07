<?php

namespace C\S\User;

use C\L\Service;

class Invest extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserInvest();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'N' => $this->translate['income_err'],
                'D' => $this->translate['has_ingore'],
                'S' => $this->translate['post_app'],
                'Y' => $this->translate['income_success'],
                'C' => $this->translate['has_unable']
            ],
            'front_status' => [
                'S' => $this->translate['authing'],
                'D' => $this->translate['authing'],
                'Y' => $this->translate['income_success'],
                'N' => $this->translate['auth_err'],
                'C' => $this->translate['has_unable']
            ],
            'channel' => [
                'wx' => $this->translate['wx'],
                'alipay' => $this->translate['alipay'],
                'bank' => '银行转账',//'银行转账'
                'bpay' => 'bpay',//'银行转账'
            ]
        ];
    }
    
    public function audit($id, $status,$fail_tips)
    {
        try {

            if (!$this->di['s_user']->lock("invest:$id", 5)) {
                throw new \Exception($this->translate['serve_busy']);
            }

            $invest = $this->search($id);
            if (!in_array($invest['status'], ['S', 'D']) || !in_array($status, ['Y', 'N', 'D', 'C'])) {
                throw new \Exception('当前订单不允许操作');
            }

            $this->di['db']->begin();

            $update = ['status' => $status, 'front_status' => $status];
            if ($status == 'Y') {
                $update['ok_time'] = time();
            }else{
    		    $update['fail_tips'] = $fail_tips;
    	    }

            if (!$this->update($id, $update)) {
                throw new \Exception('更新失败invest');
            }

            if ($status == 'Y') {
                $config = $this->di['s_config']->get('pay');
                $invest_credit_apr = $config['rate'];
                $exchange_credit = bcmul($invest['money'], $invest_credit_apr, 2);
                
                // $send_ex_credit = $this->di['s_funds']->add(
                //     $invest['uid'], 
                //     $exchange_credit, 
                //     'exchange_credit', 
                //     'invest_credit', 
                //     '充值送兑换积分', 
                //     $id
                // );
                // if ($exchange_credit > 0 && !$send_ex_credit) {
                //     throw new \Exception('充值送兑换积分流水添加失败');
                // }

                $stype = "invest_{$invest['channel']}";
                if (!$this->di['s_funds']->add($invest['uid'], $exchange_credit, 'money', $stype, $this->translate['blacne_income'], $id)) {
                    throw new \Exception('流水添加失败');
                }

                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $invest['uid'],
                    'type' => 'invest_ok',
                    'params' => date('Y.m.d H:i')
                ]));

            }

            if ($status == 'N') {
                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $invest['uid'],
                    'type' => 'invest_no',
                    'params' => date('Y.m.d H:i')
                ]));
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

    public function verify($id, $status,$fail_tips)
    {
        try {

            if (!$this->di['s_user']->lock("invest:$id", 5)) {
                throw new \Exception($this->translate['serve_busy']);
            }

            $invest = $this->search($id);
            if (!in_array($invest['status'], ['S', 'D']) || !in_array($status, ['Y', 'N', 'D', 'C'])) {
                throw new \Exception('当前订单不允许操作');
            }

            $this->di['db']->begin();

            $update = ['status' => $status, 'front_status' => $status];
            if ($status == 'Y') {
                $update['ok_time'] = time();
            }else{
		$update['fail_tips'] = $fail_tips;
	    }

            if (!$this->update($id, $update)) {
                throw new \Exception('更新失败invest');
            }

            if ($status == 'Y') {
                $invest_credit_apr = 100;
                $exchange_credit = bcmul($invest['money'], $invest_credit_apr / 100, 2);
                
                // $send_ex_credit = $this->di['s_funds']->add(
                //     $invest['uid'], 
                //     $exchange_credit, 
                //     'exchange_credit', 
                //     'invest_credit', 
                //     '充值送兑换积分', 
                //     $id
                // );
                // if ($exchange_credit > 0 && !$send_ex_credit) {
                //     throw new \Exception('充值送兑换积分流水添加失败');
                // }

                $stype = "invest_{$invest['channel']}";
                if (!$this->di['s_funds']->add($invest['uid'], $invest['money'], 'money', $stype, $this->translate['blacne_income'], $id)) {
                    throw new \Exception('流水添加失败');
                }

                $pay = $this->di['s_config']->get('pay');
                if ($invest['money'] >= 3000 && $invest['channel'] == 'bank' && $pay['bank_is_back'] == 'Y' && $pay['bank_back_apr'] > 0) {
                    $backMoney = bcmul($invest['money'] / 100, $pay['bank_back_apr'], 2);
                    if (!$this->di['s_funds']->add($invest['uid'], $backMoney, 'money', 'bank_invest_back', $this->translate['bank_incoe_apr'], $id)) {
                        throw new \Exception('返现流水添加失败');
                    }
                }

                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $invest['uid'],
                    'type' => 'invest_ok',
                    'params' => date('Y.m.d H:i')
                ]));

            }

            if ($status == 'N') {
                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $invest['uid'],
                    'type' => 'invest_no',
                    'params' => date('Y.m.d H:i')
                ]));
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
