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
        return [
            'status' => [
                'S' => '待发货',
                'D' => '已发货',
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
                0 => '在途',
                1 => '揽收',
                2 => '疑难',
                3 => '签收',
                4 => '退签',
                5 => '派件',
                6 => '退回',
                7 => '转单',
                10 => '待清关',
                11 => '清关中',
                12 => '已清关',
                13 => '清关异常',
                14 => '收件人拒签',
            ],
        ];
    }

    /**
     * 获取物流详情service
     */
    public function logisInfo($courier_id)
    {
        $lockKey = "courierid:select:$courier_id";
        if (!$this->lock($lockKey, 1)) {
            throw new \Exception('操作频繁，请稍后重试');
        }
        $order_info = $this->search(['courier_id' => $courier_id]);
        if (!$order_info) {
            return false;
            // throw new \Exception('无此订单信息');
        }
        // 读取配置信息
        $status_config = $this->getStatusConfig();
        $courier_config = $this->di['config']->get('config')->courier_config;
        $ret = [];
        // 快递公司编号
        $courier_name_pinyin = $order_info['courier_name_pinyin'];
        $ret['id'] = $order_info['id'];
        $ret['thumb'] = $order_info['thumb'];
        $ret['courier_id'] = $courier_id;
        $ret['courier_name'] = $order_info['courier_name'];
        $ret['courier_name_pinyin'] = $courier_name_pinyin;
        $ret['courier_tel'] = '';
        foreach ($status_config['courier_company'] as $info) {
            if ($info['courier_name_pinyin'] == $courier_name_pinyin) {
                $ret['courier_tel'] = $info['tel'];
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
        if (isset($res['status']) && $res['status'] == 200) {
            $ret['state'] = $res['state'];
            $ret['state_name'] = $status_config['waybill_state'][$res['state']];
            $ret['courier_info'] = $res['data'];
        } else {
            $ret['courier_info'] = [];
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
