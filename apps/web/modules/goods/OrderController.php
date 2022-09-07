<?php

namespace Goods;

use C\L\WebUserController;

class OrderController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_gorder;
        $this->hideKeys = [
            'is_delete'
        ];
        $this->updateKeys=[
            'status'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['data']['uid'] = $this->uid;

        $keywordSearchValue = $this->getValue('keyword_search_value', false, 'string');
        if (!empty($keywordSearchValue)) {
            $goods = $this->s_goods->searchPage(['title' => $keywordSearchValue], ['title' => 'like']);
            $this->params['data']['goods_id'] = 0;
            if (!empty($goods)) {
                $this->params['data']['goods_id'] = array_column($goods, 'id');
                $this->params['data_type']['goods_id'] = 'in';
            }
        }

        $status = $this->getValue('status');
        if(!empty($status)){
            $this->params['data']['status'] = $status;
        }

        return true;
    }

    protected function afterSearch(&$data)
    {
        $web = json_decode($this->s_config->search(['type' => 'web'])['content'], true);
        foreach ($data['list'] as &$item) {
            $goods = $this->s_goods->search($item['goods_id']);
            $item['goods_id_title'] = $goods['title'];
            $cat = $this->s_gcat->search($goods['cat_id']);
            $item['thumb'] = $goods['thumb'];
            $item['cat_id_name'] = $cat['name'];
            $item['store_name'] = $web['title'];
        }
        $data['config']['cats'] = $this->s_gcat->searchPage(false, false, ['id', 'name']);
        return true;
    }

    protected function beforeUpdate(&$data)
    {
        $order = $this->s_gorder->search($this->getValue('id'));
        if ($order['status'] != 'D' && $this->getValue('status') != 'Y') {
            $this->error($this->translate['action_un']);
        }
        return true;
    }

    public function applyAction()
    {
        $this->checkAuth();

        $uid = $this->uid;
        $id = $this->getValue('id', true, 'int');
        $address = $this->getValue('address', true, 'string');
        $mobile = $this->getValue('mobile', true, 'string');
        $name = $this->getValue('name', true, 'string');


        if($this->s_gorder->apply($uid, $id, $name, $mobile, $address)){
            $this->success();
        }

        $this->error();
    }
     public function logisInfoAction()
    {
        $courier_id = $this->getValue('courier_id', true);
        $data = $this->s_gorder->logisInfo($courier_id);
        if (!$data) {
            $this->error($this->translate['track_error']);
        }
        $this->success($data);
    }
	
}


