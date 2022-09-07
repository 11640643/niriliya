<?php

namespace Item;

use C\C\AdmController;

class AprController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_iam;

        $this->pubSearchKeys = [
            'status', 'type', 'cid'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'ok_time', 'back_time'
        ];
    }

    protected function beforeGetlist()
    {
        //$this->params['order'] = 'sort desc';
        return true;
    }

    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            if (count( $user )>0){
                $item['name'] = $user['name'];
                $item['mobile'] = $user['mobile'];
            }
            $il = $this->s_il->search($item['cid']);
            $item['item_name'] = $il['name'];
        }

        $data['sum_money'] = $this->service->getSum('money', $this->params['data'], $this->params['data_type']);
        $data['sum_apr_money'] = $this->service->getSum('apr_money', $this->params['data'], $this->params['data_type']);
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


