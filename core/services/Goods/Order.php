<?php

namespace C\S\Goods;

use C\L\Service;

class Order extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\GoodsOrder();
    }

    public function getStatusConfig()
    {
        if ($this->language == 'cn'){
            return [
                'status' => [
                    'S' => $this->translate['to_be_shipped'],// '待发货',
                    'D' => $this->translate['shipped'],//'已发货',
                    // 'Y' => '已签收',//'已签收'
                ],
                'courier_company' => [
                    ['courier_name' => '中通快递', 'courier_name_pinyin' => 'zhongtong', 'tel' => '95311'],
                    ['courier_name' => '圆通快递', 'courier_name_pinyin' => 'yuantong', 'tel' => '95554'],
                    ['courier_name' => '申通快递', 'courier_name_pinyin' => 'shentong', 'tel' => '95543'],
                    ['courier_name' => '百世快递', 'courier_name_pinyin' => 'baishi', 'tel' => '95320'],
                    ['courier_name' => '韵达快递', 'courier_name_pinyin' => 'yunda', 'tel' => '95546'],
                    ['courier_name' => '顺丰速运', 'courier_name_pinyin' => 'shunfeng', 'tel' => '95338'],
                    ['courier_name' => 'EMS', 'courier_name_pinyin' => 'ems', 'tel' => '11183'],
                    ['courier_name' => '天天快递', 'courier_name_pinyin' => 'tiantian', 'tel' => '4001888888'],
                    ['courier_name' => '邮政包裹信件', 'courier_name_pinyin' => 'youzhengguonei', 'tel' => '11183'],
                    ['courier_name' => '京东快递', 'courier_name_pinyin' => 'jingdong', 'tel' => '950616'],
                ],
                'waybill_state' => [
                    0 => 'In transit',
                    1 => 'Collect',
                    2 => 'Difficult ',
                    3 => 'Sign',
                    4 => 'Return to sign',
                    5 => 'Deliver',
                    6 => 'Return',
                    7 => 'Transfer order',
                    10 =>'Pending customs clearance',
                    11 =>'Customs clearance in progress',
                    12 =>'Customs cleared',
                    13 =>'Customs clearance abnormity ',
                    14 => 'Recipent refuses to sign',
                ],
            ];
        }else{
            return [
                'status' => [
                    'S' => $this->translate['to_be_shipped'],// '待发货',
                    'D' => $this->translate['shipped'],//'已发货',
                    // 'Y' => 'Recebido',//'已签收'
                ],
                'courier_company' => [
                    ['courier_name' => 'Zto Express', 'courier_name_pinyin' => 'zhongtong', 'tel' => '95311'],
                    ['courier_name' => 'Yuantong Express', 'courier_name_pinyin' => 'yuantong', 'tel' => '95554'],
                    ['courier_name' => 'Sto Express', 'courier_name_pinyin' => 'shentong', 'tel' => '95543'],
                    ['courier_name' => 'Best Express', 'courier_name_pinyin' => 'baishi', 'tel' => '95320'],
                    ['courier_name' => 'Yunda Express', 'courier_name_pinyin' => 'yunda', 'tel' => '95546'],
                    ['courier_name' => 'S.F. Express', 'courier_name_pinyin' => 'shunfeng', 'tel' => '95338'],
                    ['courier_name' => 'EMS', 'courier_name_pinyin' => 'ems', 'tel' => '11183'],
                    ['courier_name' => 'Daily Express', 'courier_name_pinyin' => 'tiantian', 'tel' => '4001888888'],
                    ['courier_name' => 'Encomenda postal', 'courier_name_pinyin' => 'youzhengguonei', 'tel' => '11183'],
                    ['courier_name' => 'Jingdong Express', 'courier_name_pinyin' => 'jingdong', 'tel' => '950616'],
                ],
                'waybill_state' => [
                    0 => 'In transit',
                    1 => 'Collect',
                    2 => 'Difficult ',
                    3 => 'Sign',
                    4 => 'Return to sign',
                    5 => 'Deliver',
                    6 => 'Return',
                    7 => 'Transfer order',
                    10 =>'Pending customs clearance',
                    11 =>'Customs clearance in progress',
                    12 =>'Customs cleared',
                    13 =>'Customs clearance abnormity ',
                    14 =>  'Recipent refuses to sign',
                ],
            ];            
        }
    }

    public function apply($uid, $id, $name, $mobile, $address)
    {

        try {

            $goods = $this->di['s_goods']->search(['id' => $id,'status' => 'Y']);
            if (empty($goods)) {
                throw new \Exception($this->translate['new_lang_goods_empty']);
                // throw new \Exception('未找到商品');
            }

            if (!$this->di['public']->checkMobile($mobile)) {
                throw new \Exception($this->translate['new_lang_mobile_error']);
            }

            // $vip = $this->di['s_level']->getLevel($uid);
            // if(!$vip || substr($vip['name'], 3, 1) < 1){
            //     throw new \Exception('会员等级不足，无法兑换');
            // }

            $todayMaxNum = $this->di['s_gorder']->getCount(['goods_id' => $id, 'addtime' => strtotime(date('Ymd'))], ['addtime' => '>=']);
            if ($goods['max_shop_num'] > 0 && $todayMaxNum >= $goods['max_shop_num']) {
                throw new \Exception('Hạng mục bán hết！');
            }

            $add = [
                'uid' => $uid,
                'goods_id' => $id,
                'name' => $name,
                'mobile' => $mobile,
                'address' => $address,
                'credit' => $goods['credit']
            ];

            $this->di['db']->begin();
            
            if (!$cid = $this->di['s_gorder']->save($add)) {
                throw new \Exception($this->translate['serve_busy']."111");
            }

            $numArray = $this->di['s_user']->lockUpdate($uid, -$goods['credit'], 'exchange_credit');
            if ($numArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            if (!$this->di['s_funds']->add($uid, -$goods['credit'], 'exchange_credit', 'goods_apply',  $this->translate['new_lang_score_recharge_goods'] . "-{$goods['title']}", $cid, 'Y', $numArray[0], $numArray[1])) {
                throw new \Exception($this->translate['serve_busy']);
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

    /**
     * 获取物流详情service
     */
    public function logisInfo($courier_id)
    {
        $lockKey = "courierid:select:$courier_id";
        if (!$this->lock($lockKey, 1)) {
            throw new \Exception($this->translate['new_lang_operation_error']);
        }
        $order_info = $this->search(['kd_sn' => $courier_id]);
        if (!$order_info) {
            return false;
            // throw new \Exception('无此订单信息');
        }
        // 读取配置信息
        $status_config = $this->getStatusConfig();
        $courier_config = $this->di['config']->get('config')->courier_config;
        $ret = [];
        // 快递公司编号
        $courier_name_pinyin = $order_info['kd_name_pinyin'];
        $ret['id'] = $order_info['id'];
        // $ret['thumb'] = $order_info['thumb'];
        $ret['kd_sn'] = $courier_id;
        $ret['kd_name'] = $order_info['kd_name'];
        $ret['kd_name_pinyin'] = $courier_name_pinyin;
        $ret['kd_tel'] = '';
        foreach ($status_config['courier_company'] as $info) {
            if ($info['courier_name_pinyin'] == $courier_name_pinyin) {
                $ret['kd_tel'] = $info['tel'];
                break;
            }
        }
        $param = json_encode(['com' => $courier_name_pinyin, 'num' => $courier_id]);
        $post_data = [];
        $post_data['customer'] = $courier_config->customer;
        $post_data['param'] = $param;
        $key = $courier_config->key;
        $post_data['sign'] = strtoupper(md5($post_data["param"] . $key . $post_data["customer"]));
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";        //默认UTF-8编码格式
        }
        $post_data = substr($o, 0, -1);
        // 发起请求
        $res = \C\P\Http::post($courier_config->url, $post_data);
        $res = json_decode($res, true);
	//var_dump($res);exit;
        if (isset($res['status']) && $res['status'] == 200) {
            $ret['state'] = $res['state'];
            $ret['state_name'] = $status_config['waybill_state'][$res['state']];
            $ret['kd_info'] = $res['data'];
        } else {
            $ret['kd_info'] = [];
        }
        return $ret;
    }

    public function lock($key, $ttl = 15)
    {
        return $this->di['cache']->setnx($key, 1, $ttl);
    }

    /**
     * 导出excel(csv)
     * @data 导出数据
     * @headlist 第一行,列名
     * @fileName 输出Excel文件名
     */
    public function csvExport($data = array(), $headlist = array(), $fileName)
    {
        // header('Content-Type: application/vnd.ms-excel');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        header('Cache-Control: max-age=0');
        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        //输出Excel列名信息
        foreach ($headlist as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }
        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);
        //计数器
        $num = 0;
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;
        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}
