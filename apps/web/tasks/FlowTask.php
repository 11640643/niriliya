<?php

class FlowTask extends \C\L\Task
{
    #定时清理往期数据
    public function clearAction()
    {
        $time = strtotime(date("Y-m-d 00:00:00"));
        $phql = 'DELETE FROM `flow_kuaishou` WHERE addtime < ' . $time;
        $this->s_flowks->model->getWriteConnection()->execute($phql);
    }

    public function flowAction()
    {
        try {
            while ($dev = $this->cache->blPop('flow_list',2)) {
                $dev = $dev[1];
                $this->logs('Redis读出来的数据：'.$dev);
                try {
                    $res = '';
                    $devJson = json_decode($dev, true);
                    if (empty($devJson) && empty($devJson['dev_no']) && empty($devJson['dev_oaid']) ) {
                        throw new Exception('数据为空');
                    }
                    $flowType = $devJson['uid'] > 0 ? 'is_reg' : 'is_open_index';
                    if (!empty($devJson['dev_no'])){
                        $devType = $devJson['dev_type'] == 'ios' ? 'idfa' : 'imei';
                        $typeValue =  $devJson['dev_no'];           
                        $imeiRes = $this->s_flowks->search([$devType=>$typeValue,$flowType=>0]);
                    }
                    if(!empty($devJson['dev_oaid'])){
                        $devType = $devJson['dev_type'] == 'ios' ? 'idfa' : 'oaid'; 
                        $typeValue =  $devJson['dev_oaid'];   
                        $oaidRes = $this->s_flowks->search([$devType=>$typeValue,$flowType=>0]);
                    }
                    if (empty($imeiRes) && empty($oaidRes)) {
                        throw new Exception('未查询到数据');
                    }elseif(!empty($imeiRes)){
                        $where['imei'] = $devJson['dev_no'];
                        $flow = $imeiRes;
                    }else{
                        $where['oaid'] = $devJson['dev_oaid'];
                        $flow = $oaidRes;
                    }
                    $where[$flowType] = 1;
                    $historyFlow = $this->s_flowks->search($where);
                    if (empty($historyFlow)) {
                        $flow['call_url'] = urldecode($flow['call_url']);    
                        switch ($flow['type']) {
                            case 'kuaishou':
                                $res = $this->kuaishou($flow['call_url'], $devJson['uid']);
                                break;
                            case 'kuaishou2':
                                $res = $this->kuaishou($flow['call_url'], $devJson['uid']);
                                break;    
                            case 'qutoutiao':
                                $res = $this->qutoutiao($flow['call_url'], $devJson['uid']);
                                break;
                            case 'qutoutiao001':
                                $res = $this->qutoutiao($flow['call_url'], $devJson['uid']);
                                break;
                            case 'qutoutiao000':
                                $res = $this->qutoutiao($flow['call_url'], $devJson['uid']);
                                break;
                            case 'juliang':
                                $res = $this->juliang($flow['call_url'], $devJson['uid']);
                                break;
                            case 'shandianhezi':
                                $res = $this->shandian($flow['call_url'], $devJson['uid']);
                                break;    
                            case 'dongfang':
                                $res = $this->dongfang($flow['call_url'], $devJson['uid']);
                                break;     
                                break;
                            case 'gdt':
                                $flow['call_url'] = urldecode($flow['call_url']);
                                $res = $this->gdt($flow, $flow['call_url'], $devJson['uid']);
                                break;
                            case 'gdt1':
                                $flow['call_url'] = urldecode($flow['call_url']);
                                $res = $this->gdt1($flow, $flow['call_url'], $devJson['uid']);
                                break;
                            case 'shuabao':
                                $flow['call_url'] = urldecode($flow['call_url']);
                                $res = $this->shuabao($flow['call_url']);
                                break; 
                            case 'phenix':
                                $flow['call_url'] = urldecode($flow['call_url']);
                                $res = $this->phenix($flow, $devJson['uid']);
                                break;
                            default:
                                throw new Exception('未知的流量类型');
                        }

                        if ($res !== true) {
                           // $this->cache->rPush('flow_list', $dev);
                            throw new Exception('发送失败');
                        }
                    }

                    if (!empty($devJson['uid'])) {
                        $this->s_user->update($devJson['uid'], [
                            'channel' => $flow['type']
                        ]);
                    }
                    $this->s_flowks->update($flow['id'], [$flowType => 1]);
                    $this->logs(['msg' => '处理成功', 'dev_no' => $dev, 'res' => $res]);
                    $imeiRes = $oaidRes = '';
                } catch (Exception $e) {
                    $this->logs(['msg' => $e->getMessage(), 'dev_no' => $dev, 'res' => $res]);
                    $imeiRes = $oaidRes = '';
                }
            }
        }catch(Exception $err){
            $this->logs(['msg' => $err->getMessage(), 'dev_no' => 1, 'res' => 1]);
            exit;
        }        
    }
    
    private function shuabao($url)
    {
        $res = \C\P\Http::get($url);
        $json = json_decode($res, true);
        if (empty($json) || $json['message'] != 'success') {
            return $res;
        }
        return true;
    }

    
    private function shandian($url,$uid)
    {
        $ventType = 'activate';//注册即是激活
        $dtype = 'imei';
        $url .= "&event_type=$ventType&dtype=".$dtype."&convert_time=".time();
        $res = \C\P\Http::get($url);
        $json = json_decode($res, true);
	    echo $url;
	    echo $res;
        if (empty($json) || $json['msg'] != 'success') {
            return $res;
        }
        return true;
    }

    private function dongfang($url,$uid)
    {
        // $ventType = 'activate';//注册即是激活
        // $dtype = 'imei';
        // $url .= "&event_type=$ventType&dtype=".$dtype."&convert_time=".time();
        $res = \C\P\Http::get($url);
        $json = json_decode($res, true);
	    echo $url;
	    echo $res;
        if (empty($json) || $json['msg'] != 'success') {
            return $res;
        }
        return true;
    }

    private function kuaishou($url,$uid)
    {
        #$ventType = $uid > 0 ? 2 : 1;
	    $ventType = 1;//注册即是激活
	    list($t1, $t2) = explode(' ', microtime());
        $timestamp=(float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        $url .= "&event_type=$ventType&event_time=".$timestamp;
        $res = \C\P\Http::get($url);
        $json = json_decode($res, true);
	    echo $url;
	    echo $res;
        if (empty($json) || $json['result'] !== 1) {
            return $res;
        }
        return true;
    }

    private function qutoutiao($url, $uid)
    {
        if ($uid <= 0) {
            return false;
        }
        // $op2 = $uid > 0 ? 1 : 0;
        $op2 = 1; //注册即是激活
        $url .= "&op2=$op2&opt_active_time=" . time();
        \C\P\Http::get($url);
        return true;
    }

    private function juliang($url, $uid)
    {
        //$ventType = $uid > 0 ? 1 : 0;
        $ventType = 0;//注册即是激活
        $url .= "&event_type=$ventType";
        $res = \C\P\Http::get($url);
        $json = json_decode($res, true);
        if (empty($json) || $json['msg'] !== 'success') {
            return $res;
        }

        return true;
    }
    private function gdt1($flow, $url, $uid)
    {
        // if ($uid<=0) {
        //     throw new Exception('gdt 只上报注册数据做为激活数据');
        // }
        $gdt_config = $this->di['config']->get('config')->gdt1_config;
        $config = json_decode(json_encode($gdt_config),true);
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
                'action_time' => $flow['time'],
                'user_id' => $user_id,
                'action_type' => $uid > 0 ? 'REGISTER' : 'ACTIVATE_APP'
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
	
    private function gdt($flow, $url, $uid)
    {
        // if ($uid<=0) {
        //     throw new Exception('gdt 只上报注册数据做为激活数据');
        // }
        $gdt_config = $this->di['config']->get('config')->gdt_config;
        $config = json_decode(json_encode($gdt_config),true);
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
                'action_time' => $flow['time'],
                'user_id' => $user_id,
                'action_type' => $uid > 0 ? 'REGISTER' : 'ACTIVATE_APP'
            ]]
        ];
        $data = json_encode($data);
        $headers = ['Content-Type' => 'application/json'];
        $res = \C\P\Http::post($url, $data, $headers);
        $this->logs(['flow' => json_encode($flow), 'data' => $data, 'result' => $res, 'uid' => $uid]);
        $json = json_decode($res, true);
        if (empty($json) || $json['code'] != 0) {
            return $res;
        }
        return true;
    }

    public function phenix($flow, $uid)
    {
        $call_url = $flow['call_url'];
        $res = \C\P\Http::zhihuGet($call_url);
        $this->logs(['phenix_flow' => json_encode($flow), 'url' => $call_url, 'result' => $res, 'uid'=>$uid]);
        $json = json_decode($res, true);
        if (empty($json) || !in_array($json['status_code'], [200])) {
            return $res;
        }
        return true;
    }
}
