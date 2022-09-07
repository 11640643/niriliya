<?php

namespace Goods;

use C\C\AdmController;

class OrderController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_gorder;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];

        $this->pubSearchKeys = [
            'status',
        ];

        $this->updateKeys = [
            'name', 'mobile', 'address', 'kd_sn', 'kd_name', 'status', 'kd_name_pinyin'
        ];

    }


    /* 
     * 搜索前
     */
    protected function beforeGetlist()
    {
        return true;
    }

    /**
     * 搜索后
     */    
    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $goods = $this->s_goods->search($item['goods_id']);
            $item['goods_id_title'] = $goods['title'] ?? '';
            $cat = $this->s_gcat->search($goods['cat_id'] ?? '');
            $item['cat_id_name'] = $cat['name'] ?? '';
            $user = $this->s_user->search($item['uid']);
            $item['user_name'] = $item['user_mobile'] = '暂无';
            if($user){
                $item['user_name'] = $user['name'];
                $item['user_mobile'] = $user['mobile'];
            }
        }
        $data['config']['status'] = $this->s_gorder->getStatusConfig()['status'];
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


    protected function afterView(&$data)
    {
        $data['config']['status'] = $this->s_gorder->getStatusConfig()['status'];
        return true;
    }

    protected function beforeUpdate(&$data)
    {
        $id = $this->getValue('id');
        if (!$id) {
            $this->error('缺少ID');
        }
        if (!isset($data['kd_sn'])) {
            $this->error('缺少快递单号');
        }
        if (!isset($data['kd_name'])) {
            $this->error('缺少快递公司名称');
        }
        if (!isset($data['status'])) {
            $this->error('缺少状态字段');
        }
        if (!isset($data['kd_name_pinyin'])) {
            $this->error('缺少快递公司编号');
        }
        // $order_info = $this->s_gorder->search(['id' => $id]);
        // if ($order_info['status'] == 'D') {
        //     $this->error('该订单已是发货状态了');
        // }
        return true;
    }

    public function logisInfoAction()
    {
        $courier_id = $this->getValue('kd_sn', true);
        $data = $this->s_gorder->logisInfo($courier_id);
        if (!$data) {
            $this->error('获取物流信息失败');
        }
        $this->success($data);
    }

    /**
     * 兑换列表导出
     */
    public function exportAction()
    {
        $sdate = strtotime(
            $this->getValue('sdate', true) . ' 00:00:00'
        );
        $edate = strtotime(
            $this->getValue('edate', true) . ' 23:59:59'
        );
        $data['addtime'] = [$sdate, $edate];
        $data_type['addtime'] = 'between';
        $order = 'id desc';
        $list = $this->di['s_gorder']->searchAll($data, $data_type, [], $order);
        if (empty($list)) {
            $this->error('该日期之间暂无数据');
        }
        $order_config = $this->di['s_gorder']->getStatusConfig()['status'];
        $new_list = [];
        foreach ($list as $item) {
            $user = $this->s_user->search($item['uid']);
            // $address = $this->s_address->search(['uid' => $item['uid'], 'is_default' => 'Y']);
            // $address = '';
            $user_name = $user['name'];
            $user_mobile = $user['mobile'];
            // if ($address) {
            //     $address = "{$address['name']} {$address['tel']} {$address['province']} {$address['city']} {$address['county']} {$address['address']} {$address['postal_code']}";
            // }
            $temp = [];
            $temp['user_name'] = $user_name;
            $temp['user_mobile'] = $user_mobile;
            $temp['shop_name'] = $item['name'];
            $temp['money'] = $item['money'];
            $temp['num'] = $item['num'];
            $temp['ex_date'] = date('Y-m-d H:i:s', $item['addtime']);
            $temp['address'] = $item['address'];
            $temp['status'] = $order_config[$item['status']];
            $temp['courier_name'] = empty($item['courier_name']) ? '' : $item['courier_name'];
            $temp['courier_id'] = empty($item['courier_id']) ? '' : $item['courier_id'];
            $new_list[] = $temp;
        }
        $head_list = [
            '用户昵称', '用户手机号', '商品名称', '兑换价格',
            '兑换数量', '兑换日期', '收货地址', '发货状态', '物流公司', '物流单号'
        ];
        $file_name = 'goods_order_' . date('Y-m-d') . 'csv';
        $this->di['s_gorder']->csvExport($new_list, $head_list, $file_name);
    }

    /**
     * 填写物流信息
     */
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


