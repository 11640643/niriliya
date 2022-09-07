<?php

namespace User;

use C\L\WebUserController;

class InvestController extends WebUserController
{

    protected function init()
    {
        $this->service = $this->s_invest;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'ok_time'
        ];

        $this->pubSearchKeys = [
            'type', 'stype', 'month'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['page_num'] = 500;
        $this->params['data']['uid'] = $this->uid;
        if (isset($this->params['data']['month'])) {
            $month = $this->params['data']['month'];
            list($year, $month) = explode('-', $month);
            unset($this->params['data']['month']);
            $t = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $stime = mktime(0, 0, 0, $month, 1, $year);
            $etime = mktime(23, 59, 59, $month, $t, $year);
            $this->params['data']['addtime'] = [$stime, $etime];
            $this->params['data_type']['addtime'] = 'between';
        }
        return true;
    }

    public function searchAction()
    {   
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'data' => [],
            'data_type' => [],
            'columns' => [],
            'order' => '',
        ];

        if (empty($this->params['page_curren'])) {
            $this->params['page_curren'] = $this->getValue('page_curren', false, 'int') ?: 1;
        }
        if (empty($this->params['page_num'])) {
            $this->params['page_num'] = $this->getValue('page_num', false, 'int') ?: 10;
        }

        $this->setSearchParams();

        if (!$this->beforeSearch()) {
            $this->error($this->translate['request_error']);
        }

        $data = empty($this->params['data']) ? [] : $this->params['data'];
        $dataType = empty($this->params['data_type']) ? [] : $this->params['data_type'];
        $columns = empty($this->params['columns']) ? [] : $this->params['columns'];
        $order = empty($this->params['order']) ? '' : $this->params['order'];
        
        $list = $this->service->searchPage($data, $dataType, $columns, $order, $this->params['page_curren'], $this->params['page_num']);
        $this->setHide($list);
        $this->setShow($list);
        $this->setStatusName($list);
        $this->setCategoryName($list);
        $this->autoTimeToDate($list);
        $data = [
            'list' => $list,
            'count' => $this->service->getCount($data, $dataType),
            'page_num' => $this->params['page_num'],
            'page_curren' => $this->params['page_curren'],
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterSearch($data)) {
            $this->error($this->translate['request_error']);
        }
        foreach ($data['list'] as $key => &$value) {
            //$value['show_money'] = number_format($value['money'] );
            $value['show_money'] = number_format($value['money'],2,".","," );
        }
  
        $this->success($data);
    }
    
    public function usdtAction(){
        $money = $this->getValue('money', true);
        $channel = $this->getValue('channel', true);
        $voucher = $this->getValue('voucher', true);
        $invest_min_money= $this->getValue('invest_min_money', true);
        
        if (!$this->uid ||$this->uid<=0 ){exit();}
        if ( $money<=0 || $invest_min_money > $money  ){exit();}
        $user = $this->s_user->search($this->uid);
        $merchantOrderNo = $this->uid . 'F'.  time()  ;
        $add = [
             'uid' => $this->uid,
             'type' => 2,
             'channel' => $channel,
             'money' => $money,
             'name' => $user['name'],
             'voucher' => $voucher,
             'orderNo' => $merchantOrderNo,
             'remark' => '',
        ];

        $ret = $this->s_invest->save($add);
        if($ret){
            $this->success();
        } else {
            $this->error();
        }
    }


    //渠道
    public function channelAction()
    {
        $this->checkAuth();
        $pay = $this->s_config->get('pay');
        $data = [];

        if ($pay['onepay_save_is_open'] == 'Y') {
            $data[] = [
                'key' => 'onepay',
                'name' => 'Onepay',
                'is_default' => 'Y',
            ];
        }


        if ($pay['wx_is_open'] == 'Y') {
            $data[] = [
                'key' => 'wx',
                'name' => $this->translate['wx'],
                'is_default' => 'Y',
            ];
        }

        if ($pay['bank_is_open'] == 'Y') {
            $data[] = [
                'key' => 'bank',
                'name' =>  $this->translate['recharge_bank_transfer'],
                'is_default' => 'N',
                'bank_back_apr' => $pay['bank_back_apr'],
                'bank_is_back' => $pay['bank_is_back']
            ];
        }


        if ($pay['alipay_is_open'] == 'Y') {
            $data[] = [
                'key' => 'alipay',
                'name' => $this->translate['alipay'],
                'is_default' => 'N',
            ];
        }

        $this->success([
            'invest_min_money' => $this->s_config->get('pay')['invest_min_money'],
            'config' => $data,
            'money' => $this->s_user->getValue('money', ['uid' => $this->uid]),
        ]);
    }

    //充值
    public function applyAction()
    {
        $money = $this->getValue('money', true, 'float');
        $channel = $this->getValue('channel', true);
        $pay_account = $this->getValue('pay_account', true);

        $pay = $this->s_config->get('pay');
        if ($pay['invest_min_money'] > 0 && $money < $pay['invest_min_money']) {
            $this->error($this->translate['min_income'] . $pay['invest_min_money']);
        }

        if (!in_array($channel, ['wx', 'alipay', 'bank'])) {
            //$this->error('充值渠道不存在');
            $channel = 'wx';
        }

        $image = '';
        $content = '';
        $payConfig = $this->s_config->get('pay');
        if (in_array($channel, ['wx', 'alipay'])) {
            $image = BASE_URL . '/' . $payConfig[$channel . '_code'] . '?rand=' . mt_rand(1000, 9999);
            $content = $payConfig[$channel . '_content'];
        }

        $data = [
            'channel' => $channel,
            'title' => $this->translate['scane_pay'],
            'image' => $image,
            'content' => $content,
            'pay_account' => $pay_account,
        ];

        $add = [
            'uid' => $this->uid,
            'channel' => $channel,
            'money' => $money,
            'name' => $this->ssid->get('name'),
            'pay_account' => $pay_account,
        ];
        if (!$this->s_invest->save($add)) {
            $this->error($this->translate['income_err']);
        }

        $this->success($data);
    }

    public function getInvateInfoAction()
    {
        $money = $this->getValue('money', true, 'float');
        $channel = $this->getValue('channel', true);
        $pay = $this->s_config->get('pay');
        if ($pay['invest_min_money'] > 0 && $money < $pay['invest_min_money']) {
            $this->error($this->translate['min_income'] . $pay['invest_min_money']);
        }
        if (!in_array($channel, ['wx', 'alipay', 'bank'])) {
            //$this->error('充值渠道不存在');
            $channel = 'wx';
        }
        $image = '';
        $content = '';
        $payConfig = $this->s_config->get('pay');
        if (in_array($channel, ['wx', 'alipay'])) {
            $image = BASE_URL . '/' . $payConfig[$channel . '_code'] . '?rand=' . mt_rand(1000, 9999);
            $content = $payConfig[$channel . '_content'];
        }
        $data = [
            'channel' => $channel,
            'title' => $this->translate['scane_pay'],
            'image' => $image,
            'content' => $content,
        ];
        $this->success($data);
    }

    //银行转账
    public function bankAction()
    {
        $pay = $this->s_config->get('pay');
        $public = $this->s_config->get('public');
        $this->success([
            'bank_card' => $pay['bank_card'],
            'bank_user' => $pay['bank_user'],
            'bank_name' => $pay['bank_name'],
            'account' => $public['bank_account'] ?? '',
        ]);
    }

    public function applybAction()
    {
        $name = $this->getValue('name', true, 'string');
        $remark = $this->getValue('remark', true, 'string');
        $money = $this->getValue('money', true, 'float');

        $pay = $this->s_config->get('pay');
        if ($pay['invest_min_money'] > 0 && $money < $pay['invest_min_money']) {
            $this->error($this->translate['min_income'] . $pay['invest_min_money']);
        }

        if(strlen($name) > 200){
            $this->error($this->translate['max_name']);
        }

        $add = [
            'uid' => $this->uid,
            'channel' => 'bank',
            'money' => $money,
            'name' => $name,
            'remark' => $remark
        ];
    

        if(!$this->s_invest->save($add)){
            $this->error();
        }

        $this->success();
    }

}


