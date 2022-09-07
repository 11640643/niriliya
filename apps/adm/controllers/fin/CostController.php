<?php

namespace Fin;

use C\C\AdmController;

class CostController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_cost;

        $this->keyworkSearchKeys = [
            'uid',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

//        $this->updateKeys = [
//            'status'
//        ];

        $this->pubSearchKeys = [
            'status'
        ];
    }

    public function removeAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        if (!$this->beforeRemove()) {
            $this->error($this->translate['doerror']);
        }

        $id = $this->getValue('id', true);

        if (empty($id)) {
            $this->error($this->translate['doerror']);
        }

        if (!$this->service->update($id, ['is_delete' => 1])) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterRemove($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }

    protected function afterGetlist(&$data)
    {
        $data['config'] = $this->s_cost->getStatusConfig();
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            if ( count($user)>0){
            $item['name'] = $user['name'];
            $item['mobile'] = $user['mobile'];
            $item['remark'] = isset($user['remark'])?$user['remark']:'';
            }
            if (empty($item['bank_name']) && empty($item['bank_card'])) {
                if ($bank = $this->s_bank->search($item['bank_id'])) {
                    $item['bank_name'] = $bank['bankname'];
                    $item['bank_card'] = $bank['card'];
                }
                if ( $item['bank_id']  == '0' ){
                    $item['bank_name'] = '第三方代收';
                    $item['bank_card'] = '第三方代收';
                }
                
            }
            $item['invest_money'] = $this->s_invest->getSum('money', ['uid' => $item['uid'], 'status' => 'Y']);
            $item['cost_money'] = $this->s_cost->getSum('money', ['uid' => $item['uid'], 'status' => 'Y']);
        }

        $this->params['data']['status'] = 'Y';
        $data['sum_ok_money'] = $this->s_cost->getSum('money', $this->params['data'], $this->params['data_type']);
        $this->params['data']['status'] = 'N';
        $data['sum_no_money'] = $this->s_cost->getSum('money', $this->params['data'], $this->params['data_type']);
        $this->params['data']['status'] = ['S', 'D'];
        $this->params['data_type']['status'] = 'in';
        $data['sum_on_money'] = $this->s_cost->getSum('money', $this->params['data'], $this->params['data_type']);
        return true;
    }

    public function verifyAction()
    {
        $status = $this->getValue('status', true, 'string');
        $id = $this->getValue('id', true, 'int');
	$fail_tips = $this->getValue('fail_tips',false,'string');
        if($this->s_cost->verify($id, $status,$fail_tips)){
            $this->success();
        }

        $this->error();
    }

    protected function beforeGetlist()
    {
        $this->params['page_num'] = 50;
        return true;
    }
    public function getlistAction()
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

        if (!$this->beforeGetlist()) {
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
        if (!$this->afterGetlist($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }


}


