<?php

namespace Shop;

use C\L\AdmController;

class OrderController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_gorder;

        $this->keyworkSearchKeys = [
            'uid'
        ];

        $this->likeSearchKeys = [
            'name'
        ];

        $this->pubSearchKeys = [
            'status'
        ];

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime',
        ];

        $this->updateKeys = [
            'status', 'courier_id', 'courier_name', 'courier_name_pinyin'
        ];
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            $item['user_name'] = $user['name'];
            $item['user_mobile'] = $user['mobile'];
            if (empty($item['address'])) {
                $address = $this->s_address->search(['uid' => $item['uid'], 'is_default' => 'Y']);
                if($address){
                    $item['address'] = "{$address['name']} {$address['tel']} {$address['province']} {$address['city']} {$address['county']} {$address['address']} {$address['postal_code']}";
                }

            }
        }

        return true;
    }

    protected function beforeUpdate(&$data)
    {
        $id = $this->getValue('id');
        if (!$id) {
            $this->error('缺少ID');
        }
        if (!isset($data['courier_id'])) {
            $this->error('缺少快递单号');
        }
        if (!isset($data['courier_name'])) {
            $this->error('缺少快递公司名称');
        }
        if (!isset($data['status'])) {
            $this->error('缺少状态字段');
        }
        if (!isset($data['courier_name_pinyin'])) {
            $this->error('缺少快递公司编号');
        }
        // $order_info = $this->s_gorder->search(['id' => $id]);
        // if ($order_info['status'] == 'D') {
        //     $this->error('该订单已是发货状态了');
        // }
        return true;
    }

    /**
     * 发货之后短信通知用户
     */
    protected function afterUpdate(&$data)
    {
        return true;
        // 读取配置信息
        $sms_config = $this->di['config']->get('config')->sms_config;
        $key = $sms_config->key;
        $userid = $sms_config->userid;
        $post_data = [];
        $post_data['sign'] = strtoupper(md5($key . $userid));
        $post_data['userid'] = $userid;
        $post_data['seller'] = $sms_config->seller;
        $user_info = $this->di['s_user']->search(['uid' => $data['uid']]);
        $post_data['phone'] = $user_info['mobile'];
        $post_data['tid'] = $sms_config->tid;
        $param = json_encode([
            '接收人姓名' => $user_info['name'],
            '公司名' => $sms_config->seller,
            '快递单号' => $data['courier_id'],
            'url' => '123123',
        ]);
        $post_data['content'] = $param;
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";        //默认UTF-8编码格式
        }
        $post_data = substr($o, 0, -1);
        $res = \C\P\Http::post($courier_config->url, $post_data);
        $log = $this->log->set('shop_order_sms_' . date('Ymd') . '.log');
        $log->notice(json_encode(['param' => $post_data, 'result' => $res]));
        return true;
    }

    public function logisInfoAction()
    {
        $courier_id = $this->getValue('courier_id', true);
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
        $head_list = ['用户昵称','用户手机号','商品名称','兑换价格',
            '兑换数量','兑换日期','收货地址','发货状态','物流公司','物流单号'];
        $file_name = 'goods_order_'.date('Y-m-d').'csv';
        $this->di['s_gorder']->csvExport($new_list, $head_list, $file_name);
    }
}


