<?php

namespace C\P;


class ChuangLan
{
    private $log;
    private $key;

    public function __construct($logObj, $username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->log = $logObj;
    }

    public function send($mobile, $msg, $params)
    {
        try {

            $url = 'http://smssh1.253.com/msg/send/json';
            $params = [
                'account' => $this->username,
                'password' => $this->password,
                'msg' => $msg,
                'phone' => $mobile,
                'report'=>true
            ];

            $res = $this->post($url, $params);

            $this->log->info(json_encode([
                'send' => $params,
                'result' => $res
            ], JSON_UNESCAPED_UNICODE));

            if (empty($res)) {
                throw new \Exception('返回信息为空');
            }

            $result = json_decode($res, true);
            if ($result['code'] !== '0') {
                throw new \Exception('发送失败' . $res);
            }

            return true;

        } catch (\Exception $e) {

            return false;
        }

    }

    private function post($url, $postFields)
    {
        $postFields = json_encode($postFields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
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
}
