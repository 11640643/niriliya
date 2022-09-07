<?php

namespace Fin;

use C\C\AdmController;

class FundsController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_funds;

        $this->keyworkSearchKeys = [
            'uid',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->updateKeys = [
            'status'
        ];

        $this->pubSearchKeys = [
            'type','stype'
        ];
    }

    protected function beforeGetlist()
    {
        $this->params['order'] = $this->getValue('order', false, 'string');
        return true;
    }

    protected function afterGetlist(&$data)
    {
        $data['config'] = $this->s_funds->getStatusConfig();
        $data['config']['vip'] = $this->s_level->searchAll();

        foreach($data['list'] as &$item){
            $user = $this->s_user->search($item['uid']);
            if(!empty($user)){
                $item['name'] = $user['name'];
                $item['mobile'] = $user['mobile'];
            }else{
                $item['name'] = '用户不存在';
                $item['mobile'] = '';
            }
        }
        
        $this->params['data']['btype'] = 'add';
        $data['sum_add_money'] = $this->s_funds->getSum('money', $this->params['data'], $this->params['data_type']);
        $this->params['data']['btype'] = 'sub';
        $data['sum_sub_money'] = $this->s_funds->getSum('money', $this->params['data'], $this->params['data_type']);
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


    protected function afterUpdate(&$data)
    {
        //其实这里有个逻辑漏洞，用户需要在update的时候，更新当前的流水记录
        $info = $this->s_funds->search($data['id']);
        if($info['status'] == "Y"){
            $userinfo =  $this->s_user->search($info['uid']);
            $userinfo[$info['type']] = $userinfo[$info['type']] + $info['money'];
            //更新用户数据
            $update = [
                $info['type'] => $userinfo[$info['type']]
            ];
            $this->s_user->update($info['uid'],$update);
        }
        return true;
    }


    public function updateAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'update' => [],
        ];

        $id = $this->getValue('id');
        if (empty($id)) {
            $id = $this->params;
        }
        $update = $this->setUpdateVlaue();
        if (!$this->beforeUpdate($update)) {
            $this->error($this->translate['doerror']);
        }

        $data = ['id' => $id];
        if (empty($update)) {
            $this->success($data);
        }

        if (!$this->service->update($id, $update)) {
            $this->error($this->translate['nodata']);
        }

        if (!$this->afterUpdate($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }    
}


