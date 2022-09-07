<?php

namespace User;

use C\L\WebUserController;

class FundsController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_funds;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->pubSearchKeys = [
            'type', 'stype', 'month'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['data']['status'] = 'Y';
        $this->params['data']['uid'] = $this->uid;
        $type = $this->getValue('type', false, 'string');
        $this->params['data']['type'] = $type ?? 'money';
        $this->params['page_num'] = 10000;
        if (isset($this->params['data']['stype']) && $this->params['data']['stype'] == 'checkin') {
            $this->params['data']['stype'] = ['checkin', 'cumulative_sign'];
            $this->params['data_type']['stype'] = 'in';
        }
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

    protected function afterSearch(&$data)
    {

        #系统充值title显示
        foreach($data['list'] as $key=>$val){
            $data['list'][$key]['stype_name'] = $val['stype'] =='sys_money' ? $val['remark']:$val['stype_name'];
        }

        $uid = $this->uid;
        $data['value'] = $type = $this->getValue('type') == 'money' ? $this->s_user->getValue('money', $uid) : $this->s_user->getValue('credit', $uid);
        
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

        foreach ($list as $key => &$value) {
            if ($value['title'] == '注册奖金' ||$value['title'] == '注册奖励' ){
                $value['title'] = $this->translate['new_lang_register_award'];
            }
            if ($value['title'] == '邀请好友-注册'){
                $value['title'] = $this->translate['new_lang_inviate_friend_register'];
            }
            if ($value['title'] == '存款余额' || $value['title']='账户充值' ){
                $value['title'] = $this->translate['blacne_income'];
            }
            if ($value['title'] == 'stationing' ){
                $value['title'] = $this->translate['stationing'];
            }            
            if ($value['title'] == '投资送等级积分' ){
                $value['title'] = $this->translate['new_lang_send_level_score'];
            }   
            if ($value['title'] == '投资送兑换礼品积分' ){
                $value['title'] = $this->translate['new_lang_invest_charge_gift_score'];
            }              
            
            $value['show_money'] = number_format($value['money'],2,".","," );
        }


        // exit();
        

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

        $this->success($data);
    }
    

}


