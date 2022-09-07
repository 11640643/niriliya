<?php
namespace C\P;
class ZhiNiu
{
    private $log;
    private $sid;
    private $token;
    private $appid;
    private $template_id;
    public function __construct($logObj,$appkey,$appcode,$appsecret)
    {
	
        $this->appkey = $appkey;
        $this->appcode=$appcode;
        $num_arr = explode('.',microtime(true));
        $this->timestamp = $timestamp = $num_arr[0].$num_arr[1];
        $this->sign = md5($appkey.$appsecret.$timestamp);
        $this->log = $logObj;
    }
    public function send($mobile,$msg)
    {
        try {
            $url = 'https://api.adshopco.com/sms/sendVerify';
            // $params = [
            //     'act' => 'st11',
            //     'credentials'=> 'dbfce1bbeffe561fa9394d31e25e2adf',
            //     // 'timestamp'=>$this->timestamp,
            //     // 'sign'=>$this->sign,
            //     // 'phone' => $mobile,
            //     // 'msg' => $msg,
            // ];
            // $header = ['Accept'=>'application/json;charset=utf-8','Content-Type'=>'application/x-www-form-urlencoded;charset=utf-8'];
	        // $res = $this->request($url, 'POST','',$params);
            $time = time();
            $sign = md5('RDjcbExIehgm'.'OCCVXUTETCQIHIIZ'.$time);
            $params = array("numbers"=>$mobile, "content"=>$msg,'mcc'=>'84','withAreaCode'=>'0');
            $header = array("Content-Type"=>"application/json;charset=UTF-8","sign"=>$sign,"timestamp"=>$time,"api_key"=>"RDjcbExIehgm");
            // $res = $this->curlPost($url, $params,5, $header);
            $res = Http::post($url, json_encode($params),$header);
            $this->log->info(json_encode([
                'send' => $params,
                'result' => $res
            ], JSON_UNESCAPED_UNICODE));
            
            if (empty($res)) {
                throw new \Exception('返回信息为空');
            }
            //echo $msg;exit;
            $result = json_decode($res, true);
            if ($result['status'] !='0') {
                throw new \Exception('发送失败' . $res);
            }
            return true;

        } catch (\Exception $e) {
         return false;
        }
    }
    public function send_old($mobile,$msg)
    {
        try {
            $url = 'https://sxqunfa.com/sms/api/v1/getAllSender.shtml';
            // $params = [
            //     'act' => 'st11',
            //     'credentials'=> 'dbfce1bbeffe561fa9394d31e25e2adf',
            //     // 'timestamp'=>$this->timestamp,
            //     // 'sign'=>$this->sign,
            //     // 'phone' => $mobile,
            //     // 'msg' => $msg,
            // ];
            // $header = ['Accept'=>'application/json;charset=utf-8','Content-Type'=>'application/x-www-form-urlencoded;charset=utf-8'];
	        // $res = $this->request($url, 'POST','',$params);
            $params = array("act"=>"st11", "credentials"=>"2a3f2aec754e52d8ee3d5ac039c3467d");
            // $header = array("Content-Type:multipart/x-www-form-urlencoded");
            // $res = $this->curlPost($url, $params,5, $header);
            $res_pre = Http::curlPost($url, $params);
            // var_dump($res_pre);
            $res_pre_data = json_decode($res_pre);
            $sender_data = json_decode($res_pre_data->data,true);
            $sender_data = $sender_data[0][0];
            $sendurl = 'https://sxqunfa.com/sms/api/v1/send.shtml';
            $sendparams = array(
                "act"=>"st11",
                "credentials"=>"2a3f2aec754e52d8ee3d5ac039c3467d",
                'sendContent'=>$msg,
                // 'taskNumName'=>'',
                'country'=>$sender_data['country'],
                'senderId'=>$sender_data['senderId'],
                'nums'=>"+84".$mobile,
                'sendTime'=>date('yyyy-mm-dd HH:mm',time()),
                'isDefine'=>$sender_data['isDefine'],
                'channelId'=>$sender_data['channelId'],
                // 'templateId'=>'',
                // 'customerNums'=>''
            );
            $res = Http::curlPost($sendurl, $sendparams);
            $this->log->info(json_encode([
                'send' => $sendparams,
                'result' => $res
            ], JSON_UNESCAPED_UNICODE));

            if (empty($res)) {
                throw new \Exception('返回信息为空');
            }
            //echo $msg;exit;
            $result = json_decode($res, true);
            if ($result['status'] != '0') {
                throw new \Exception('发送失败' . $res);
            }

            return true;

        } catch (\Exception $e) {
         return false;
        }
    }
    private function post($url, $postFields)
    {
        // var_dump($postFields);
        // $postFields = json_encode($postFields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }
    /**
     * 传入数组进行HTTP POST请求
     */
    function curlPost($url, $post_data = array(), $timeout = 5, $header = "", $data_type = "") {
        $header = empty($header) ? '' : $header;
        //支持json数据数据提交
        if($data_type == 'json'){
            $post_string = json_encode($post_data);
        }elseif($data_type == 'array') {
            $post_string = $post_data;
        }elseif(is_array($post_data)){
            $post_string = http_build_query($post_data, '', '&');
        }
        
        $ch = curl_init();    // 启动一个CURL会话
        curl_setopt($ch, CURLOPT_URL, $url);     // 要访问的地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查   // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        //curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     // Post提交的数据包
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // 获取的信息以文件流的形式返回 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        $result = curl_exec($ch);

        // 打印请求的header信息
        // $a = curl_getinfo($ch);
        // var_dump($a);

        curl_close($ch);
        var_dump($result);
        return $result;
    }
    public function sendOld($mobile,$msg)
    {
        try {
            $url = 'http://yx.sms.zuiniu.xin:9090/sms/batch/v1';
            $params = [
                'appkey' => $this->appkey,
                'appcode'=>$this->appcode,
                'timestamp'=>$this->timestamp,
                'sign'=>$this->sign,
                'phone' => $mobile,
                'msg' => $msg,
            ];
            $header = ['Accept'=>'application/json;charset=utf-8','Content-Type'=>'application/x-www-form-urlencoded;charset=utf-8'];
	    $res = Http::YunZhiXin($url, json_encode($params),'post');
            $this->log->info(json_encode([
                'send' => $params,
                'result' => $res
            ], JSON_UNESCAPED_UNICODE));

            if (empty($res)) {
                throw new \Exception('返回信息为空');
            }
            //echo $msg;exit;
            $result = json_decode($res, true);
            if ($result['code'] !='00000') {
                throw new \Exception('发送失败' . $res);
            }

            return true;

        } catch (\Exception $e) {
         return false;
        }

    }
    /**
     * 请求云服务器
     * @param  string   $path    请求的PATH
     * @param  string   $method  请求方法
     * @param  array    $headers 请求header
     * @param  resource $body    上传文件资源
     * @return boolean
     */
    private function request($path, $method, $headers = null, $body = null){
        $ch  = curl_init($path);

        $_headers = array('Expect:');
        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        $length = 0;
        $date   = gmdate('D, d M Y H:i:s \G\M\T');

        if (!is_null($body)) {
            if(is_resource($body)){
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);

                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }

        // array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
        array_push($_headers, "Date: {$date}");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'PUT' || $method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }
    
}

