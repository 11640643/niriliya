<?php

namespace C\S\User;

use C\L\Service;

class Cost extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserCost();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'N' => $this->translate['out_err'],
                'D' => $this->translate['has_ingore'],
                'S' => $this->translate['auth_err'],
                'Y' => $this->translate['out_success'],
                'C' => $this->translate['has_unable']
            ],
            'front_status' => [
                'S' => $this->translate['authing'],
                'D' => $this->translate['authing'],
                'Y' => $this->translate['out_success'],
                'N' => $this->translate['auth_err'],
                'C' => $this->translate['has_unable']
            ]
        ];
    }

    public function cash($uid, $money, $bankId, $passwd)
    {
        try {

            $lockKey = "uid:$uid:cash";
            if (!$this->di['s_user']->lock($lockKey, 5)) {
                throw new \Exception('操作频繁，请稍后重试');
            }

            if(!$this->di['s_user']->checkPayPwd($uid, $passwd)){
                throw new \Exception($this->translate['cost_pw_err']);
            }

            $bankInfo = $this->di['s_bank']->search(['id' => $bankId, 'uid' => $uid]);
            if (empty($bankInfo)) {
                throw new \Exception('未选择银行账户');
            }

            $web = $this->di['s_config']->get('pay');
            if ($web['cost_min_money'] > 0 && $web['cost_min_money'] > $money) {
                throw new \Exception('最小提现金额为' . $web['cost_min_money']);
            }

            if(!$this->di['s_il']->search(['uid' => $uid])){
                throw new \Exception($this->translate['no_item_cost']);
            }

            $this->di['db']->begin();

            $moneyArray = $this->di['s_user']->lockUpdate($uid, -$money, 'money');
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            $costAdd = [
                'uid' => $uid,
                'money' => $money,
                'bank_id' => $bankId,
                'bank_name' => $bankInfo['name'],
                'bank_card' => $bankInfo['card'],
            ];

            if (!$cid = $this->save($costAdd)) {
                throw new \Exception('提现出错,请稍后重试');
            }

            if (!$this->di['s_funds']->add($uid, -$money, 'money', 'cost', $this->translate['blance_cost'], $cid, 'Y', $moneyArray[0], $moneyArray[1])) {
                throw new \Exception('提现出错,请稍后重试');
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

            if(!$this->di['s_user']->lock("cost:$id", 5)){
                throw new \Exception('服务器忙,请稍后重试');
            }

            $cost = $this->search($id);
            if (!in_array($cost['status'], ['S', 'D']) || !in_array($status, ['Y', 'N', 'D'])) {
                throw new \Exception('当前订单不允许操作');
            }

            $this->di['db']->begin();

            $update = ['status' => $status, 'front_status' => $status];
            if($status == 'Y'){
                $update['ok_time'] = time();
            }else{
		$update['fail_tips'] = $fail_tips;
	    }

            if (!$this->update($id, $update)) {
                throw new \Exception('更新失败cost');
            }

            if ($status == 'N') {

                if (!$this->di['s_funds']->add($cost['uid'], $cost['money'], 'money', 'cost_back', '余额提现-失败返还'.$fail_tips, $id)) {
                    throw new \Exception('添加流水失败');
                }

                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $cost['uid'],
                    'type' => 'cost_no',
                    'params' => date('Y.m.d H:i')
                ]));
            }

            if($status == 'Y'){

                $this->di['cache']->rPush('sms_list', json_encode([
                    'uid' => $cost['uid'],
                    'type' => 'cost_ok',
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
