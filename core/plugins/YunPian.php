<?php

namespace C\P;


class YunPian
{
    private $log;
    private $key;

    public function __construct($logObj, $key)
    {
        $this->key = $key;
        $this->log = $logObj;
    }

    public function send($mobile, $msg)
    {
        try {

            $url = 'https://sms.yunpian.com/v2/sms/single_send.json';
            $params = [
                'apikey' => $this->key,
                'mobile' => $mobile,
                'text' => $msg,
            ];
	    $header = ['Accept'=>'application/json;charset=utf-8','Content-Type'=>'application/x-www-form-urlencoded;charset=utf-8'];
            $res = Http::post($url, http_build_query($params), $header, 10);
	    //var_dump($params,$res);exit;
            $this->log->info(json_encode([
                'send' => $params,
                'result' => $res
            ], JSON_UNESCAPED_UNICODE));
	    
            if (empty($res)) {
                throw new \Exception('返回信息为空');
            }
	    //echo $msg;exit;
            $result = json_decode($res, true);
	    //var_dump($result,$msg,$params);
            if ($result['code'] !== 0) {
                throw new \Exception('发送失败' . $res);
            }

            return true;

        } catch (\Exception $e) {

            return false;
        }

    }
}
