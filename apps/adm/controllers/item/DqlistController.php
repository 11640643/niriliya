<?php

namespace Item;

use C\C\AdmController;

class DqlistController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_il;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'end_time',
        ];

        $this->likeSearchKeys = [
            'name'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];

        $this->pubSearchKeys = [
            'status', 'type'
        ];
    }

    protected function beforeGetlist()
    {
        $this->params['data']['stype'] = 'dq';
        return true;
    }

    public function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            if ( count($user)>0){
            $item['name'] = $user['name'];
            $item['mobile'] = $user['mobile'];
            }
            $dq = $this->s_item->search($item['cid']);
            $item['item_name'] = $dq['name'];
            $item['back_money'] = bcadd($item['money'], $this->s_iam->getSum('apr_money', ['cid' => $item['id']]));

            $item['next_apr_no'] = $item['next_apr_date'] = '已结束';
            $nextAprData = $this->s_iam->search(['cid' => $item['id'], 'status' => 'D'], [], [], 'id asc');
            if (!empty($nextAprData)) {
                $item['next_apr_date'] = date('Y-m-d H:i:s', $nextAprData['back_time']);
                $item['next_apr_no'] = $nextAprData['apr_no'];
            }
        }

        $this->params['data']['status'] = 'Y';
        $data['sum_ok_money'] = $this->service->getSum('money', $this->params['data'], $this->params['data_type']);
        $this->params['data']['status'] = 'D';
        $data['sum_on_money'] = $this->service->getSum('money', $this->params['data'], $this->params['data_type']);
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


    public function resetAction()
    {
        $id = $this->getValue('id', true, 'int');
        if ($this->s_il->reset($id)) {
            $this->success();
        }
        $this->error();
    }

}


