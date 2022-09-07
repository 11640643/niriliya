<?php

namespace C\S\Other;

use C\L\Service;
use C\P\ChuangLan;
class SecurityCode extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\Code();
    }

    public function sendSms($sf_code)
    {
        try {
            $ifTrue = $this->checkSfcode($sf_code);
            if (!$ifTrue) {
                throw new \Exception("缺少参数");
            }
            $config = $this->di['s_config']->get('sms_sf');
            if (!$config) {
                throw new \Exception('未找到模板');
            }
            $username = $config['username'];
            $password = $config['passwd'];
            // 获取手机号
            $w_list = $this->di['s_config']->get('white_list_manage');
            if (!$w_list) {
                throw new \Exception('未找到手机号');
            }
            $white_list = json_decode($w_list['white_list'], true);
            $rand_str = rand(0, count($white_list) - 1);
            if (!isset($white_list[$rand_str])) {
                throw new \Exception('未找到手机号');
            }
            $mobile = $white_list[$rand_str];
            $expireTime = time() + 300;
            $code = rand(111111, 999999);
            $logSer = $this->di['log']->set('code_sms_sf_' . date('Ymd') . '.log');
            $cl = new ChuangLan($logSer, $username, $password);
            $message = sprintf($config['msg_temp'], $code);

            //自定义发送短信

            if (!$cl->send($mobile, $message, '')) {
                throw new \Exception('短信发送失败');
            }

            $add = [
                'mobile' => $mobile,
                'type' => 'security_code',
                'expire_time' => $expireTime,
                'code' => $code,
                'message' => "{$message}|{$code}"
            ];

            if (!$this->save($add)) {
                throw new \Exception('发送失败');
            }

            $res = [
                'msg' => sprintf('给%s的手机号发送了一条短信', substr_replace($mobile, '****', 3, 4))
            ];

            return $res;
        } catch (\Exception $e) {
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function checkSfcode($sf_code)
    {
        $wlm = $this->di['s_config']->get('white_list_manage');
        $security_code = isset($wlm['security_code']) ? $wlm['security_code'] : '';
        if (!$security_code) {
            return false;
        }
        if ($sf_code == $security_code) {
            return true;
        }
        return false;
    }

    public function checkVerifCode($vf_code)
    {
        $data = $this->search(['code' => $vf_code, 'type' => 'security_code', 'status' => 'S']);
        if (!$data) {
            throw new \Exception("验证码错误");
        }
        $currentTime = time();
        if ($currentTime > $data['expire_time']) {
            throw new \Exception("验证码已过期，请重新发送");
        }
        return true;
    }

    public function updateCodeStatus($vf_code)
    {
        $data = $this->search(
            [
                'type' => 'security_code',
                'status' => 'S',
                'code' => $vf_code,
            ]
        );

        if (empty($data) || $data['expire_time'] < time()) {
            throw new \Exception('验证码有误');
        }
        if (!$this->update($data['id'], ['status' => 'Y'])) {
            throw new \Exception('code修改失败');
        }
        return true;
    }
}
