<?php

class RealnameTask extends \C\L\Task
{
    public function realAction()
    {
        try{
            while ($dev = $this->cache->blPop('gdt_real_name_list',100)) {
                $dev = $dev[1];
                try {
                    $res = '';
                    $devJson = json_decode($dev, true);
                    if (empty($devJson) && empty($devJson['dev_no']) && empty($devJson['dev_oaid'])) {
                        throw new Exception('数据为空');
                    }
                    if (!empty($devJson['dev_no'])) {
                        $devType = $devJson['dev_type'] == 'ios' ? 'idfa' : 'imei';
                        $typeValue =  md5($devJson['dev_no']);
                        $imeiRes = $this->s_flowks->search([$devType => $typeValue]);
                    }
                    if (!empty($devJson['dev_oaid'])) {
                        $devType = $devJson['dev_type'] == 'ios' ? 'idfa' : 'oaid';
                        $typeValue =  md5($devJson['dev_oaid']);
                        $oaidRes = $this->s_flowks->search([$devType => $typeValue]);
                    }
                    if (empty($imeiRes) && empty($oaidRes)) {
                        throw new Exception('未查询到数据');
                    } elseif (!empty($imeiRes)) {
                        $where['imei'] = $devJson['dev_no'];
                        $flow = $imeiRes;
                    } else {
                        $where['oaid'] = $devJson['dev_oaid'];
                        $flow = $oaidRes;
                    }
                    $flow['call_url'] = urldecode($flow['call_url']);  
                    switch ($flow['type']) {
                        case 'gdt':
                            $res = $this->gdt($flow, $flow['call_url'], $devJson['uid']);
                            break;
                        default:
                            throw new Exception('未知的流量类型');
                    }

                    if ($res !== true) {
                        throw new Exception('发送失败');
                    }
                    $this->logs(['msg' => '处理成功', 'params' => $devJson, 'res' => $res]);

                } catch (Exception $e) {
                    $this->logs(['msg' => $e->getMessage(), 'params' => $devJson, 'res' => $res]);
                }
            }
        }catch(Exception $err){
            $this->logs(['msg' => $err->getMessage(), 'params' => 1, 'res' => '']);
        }
            
    }

    public function gdt($flow, $url, $uid)
    {
        $gdt_config = $this->di['config']->get('config')->gdt_config;
        $config = json_decode(json_encode($gdt_config), true);
        if (!$config) {
            throw new \Exception("缺少配置");
        }
        $time = time();
        $nonce = rand(111111, 999999);
        $url .= "{$config['access_token']}&timestamp={$time}&nonce={$nonce}";
        $os_type = !empty($flow['imei']) ? 'hash_imei' : 'hash_idfa';
        $os_value = !empty($flow['imei']) ? $flow['imei'] : $flow['idfa'];
        $user_id = [
            $os_type => $os_value,
        ];
        $data = [
            'account_id' => $config['account_id'],
            'user_action_set_id' => $config['user_action_set_id'],
            'actions' => [[
                'action_time' => $time,
                'user_id' => $user_id,
                'action_type' => 'COMPLETE_ORDER'
            ]]
        ];
        $data = json_encode($data);
        $headers = ['Content-Type' => 'application/json'];
        $res = \C\P\Http::post($url, $data, $headers);
        $this->logs(['flow' => json_encode($flow), 'data' => $data, 'result' => $res]);
        $json = json_decode($res, true);
        if (empty($json) || $json['code'] != 0) {
            return $res;
        }
        return true;
    }
}