<?php

namespace Api;

use C\C\Controller;
use C\P\ChuangLan;
use Exception;

class ApiController extends Controller
{
    protected function init()
    {
        $translate =  $this->getTranslation();
    }
    //登录
    public function loginAction()
    {   

        $username = $this->getValue('username','string',true) ;
        $password = $this->getValue('password','string',true);
        // $sf_code = $this->getValue('sf_code', true);
        // $vf_code = $this->getValue('vf_code', true);
        // if ($this->s_sysuser->login($username, $password, $sf_code, $vf_code)) {
        if ($this->s_sysuser->login($username, $password,$this->config)) {
            $this->success();
        }

        $this->error();
    }

    public function logoutAction()
    {
        $this->ssid->destory();
        $this->success();
    }


    //获取配置文件
    public function configAction()
    {   
        if ($this->uid !=''){
            $data = [
                'ssid' => $this->ssid->register(),
                'ssid_expire_time' => $this->config->get('config')->toArray()['expire_time'],
                'error' => $this->config->get('error')->toArray(),
                //'aes_key' => $this->ssid->get('aes_key'),
                'pstt'=>'',
                'is_maintain' => $this->cache->get('web_is_maintain') ? true : false
            ];
        }else{
            $data = [
            'ssid' => $this->ssid->register(),
            'ssid_expire_time' => $this->ssid->get('expire_time'),
            'pstt'=>'',
            'error' => $this->config->get('error')->toArray(),
            //'aes_key' => $this->ssid->get('aes_key'),
            'is_maintain' => $this->cache->get('web_is_maintain') ? true : false
        ];
        }


        $this->success($data);
    }

    public function uploadAction()
    {
        if (!$this->request->hasFiles()) {
            $this->error('上传为空');
        }

        $files = $this->request->getUploadedFiles();
        $list = [];
        foreach ($files as $file) {
            $file_t = fopen($file->getTempName(), "rb");
            $bin = fread($file_t, 2); //只读2字节
            fclose($file_t);
            $strInfo = @unpack("C2chars", $bin);
            $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
            $fileType = '';
            switch ($typeCode) {
                case 7790: $fileType = 'exe'; break;
                case 7784: $fileType = 'midi'; break;
                case 8297: $fileType = 'rar'; break;
                case 8273: $fileType = 'webp'; break;
                case 255216: $fileType = 'jpg'; break;
                case 7173: $fileType = 'gif'; break;
                case 6677: $fileType = 'bmp'; break;
                case 13780: $fileType = 'png'; break;
                default: $fileType='unknown';break;
            }
            if (!in_array($fileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                continue;
            }
    
            $extName = $file->getExtension();
            if (!in_array($extName, ['jpg', 'png', 'jpeg', 'gif'])) {
                continue;
            }

            $path = '/upload/' . date('Y/m/d');
            $filePath = APP_PUBLIC . $path;
            $fileNme = date('YmdHis') . '_' . substr(md5($file->getName() . rand(10, 99)), 0, 8) . '.' . $extName;
            if (!file_exists($filePath)) {
		//echo $filePath;exit;
                if (!@mkdir($filePath, 0755, true)) {
                    $this->error('文件系统错误');
                }
            }
            $file->moveTo(
                $filePath . '/' . $fileNme
            );

            $list[$file->getKey()] = $path . '/' . $fileNme;

        }

        $this->success($list);
    }

    public function sendsmsAction()
    {
        $sf_code = $this->getValue('sf_code', true);
        if ($res = $this->s_sfcode->sendSms($sf_code)) {
            $this->success($res);
        }
        $this->error();
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
        $file_name = 'goods_order_' . date('Y-m-d');
        $this->di['s_gorder']->csvExport($new_list, $head_list, $file_name);
    }

    public function downSearchAction()
    {
        try {
            $page_curren = $this->getValue('page_curren', false, 'int') ?? 1;
            $page_num = $this->getValue('page_num', false, 'int') ?? 10;
            $serve_name = $this->di['config']->get('config')['serve_name'];
            $where = ['sport' => $serve_name];
            $date = $this->getValue('date', false, 'string');
            if (!empty($date)) {
                if (strtotime($date) === false) $this->error('参数错误');
                $where['date'] = $date;
            }
            $where_type = [];
            $list = $this->s_down_log->searchPage(
                $where,
                $where_type,
                [],
                '',
                $page_curren,
                $page_num
            );
            array_walk($list, function (&$v, $k) {
                $v['addtime_date'] = date('Y-m-d H:i:s', $v['addtime']);
                $v['uptime_date'] = date('Y-m-d H:i:s', $v['uptime']);
            });
            $data = [
                'list' => $list,
                'count' => $this->s_down_log->getCount($where, $where_type),
                'page_num' => $page_num,
                'page_curren' => $page_curren,
            ];
            $this->success($data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}


