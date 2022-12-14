<?php

namespace C\S\Other;

use C\L\Service;
use C\P\ChuangLan;
use C\P\YunPian;
class Code extends Service
{

    private $errorMsg;

    protected function setModel()
    {
        $this->model = new \C\M\Code();
    }


    private function checkMsgType($type)
    {
        $config = $this->di['config']->get('code');
        if (empty($config->$type)) {
            return false;
        }

        return true;
    }

    public function verify($mobile, $code, $type)
    {
        try {

            if (!$this->checkMsgType($type)) {
                throw new \Exception($this->translate['illegal_verification_code_type']);
            }

            $data = $this->search(
                [
                    'mobile' => $mobile,
                    'type' => $type,
                    'status' => 'S',
                    'code' => $code,
                ]
            );

            if (empty($data) || $data['expire_time'] < time()) {
                throw new \Exception($this->translate['incorrect_verification_code']);
            }

            if (!$this->update($data['id'], ['status' => 'Y'])) {
                throw new \Exception($this->translate['code_modification_failed']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setCodeMsg($e->getMessage());
            return false;
        }
    }

    public function checkCode($mobile, $type)
    {
        try {

            if (!$this->di['public']->checkMobile($mobile)) {
                throw new \Exception($this->translate['wrong_mobile_phone_number']);
            }

            if (!$this->checkMsgType($type)) {
                throw new \Exception($this->translate['illegal_verification_code_type']);
            }

            $method = $type . 'Check';
            if (!$this->$method($mobile) || !$this->checkSendNum($mobile, $type, 6)) {
                throw new \Exception($this->errorMsg);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function send($mobile, $type, $params = '')
    {
        try {

            $config = $this->di['s_config']->get('sms_cl');
            $expireTime = 0;
            $code =  date("Y-m-d H:i:s");
//	    var_dump($config);exit;
            $username = $config['username2'];
            $password = $config['password2'];
            if (in_array($type, ['register', 'forgetpwd'])) {
                if (!$this->checkCode($mobile, $type)) {
                    throw new \Exception($this->di['message']->getSerMsg());
                }

                $username = $config['username1'];
                $password = $config['password1'];
                $code = rand(111111, 999999);
                $params = $code;
                $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            }

            if (empty($config[$type])) {
                throw new \Exception($this->translate['template_not_found']);
            }

            $logSer = $this->di['log']->set('code_cl_' . date('Ymd') . '.log');
            $cl = new ChuangLan($logSer, $username, $password);
	    $message = str_replace('{$var}',$code,$config[$type]);       	
            //var_dump($message);exit;
 	    if (!$cl->send($mobile, $message, $params)) {
                throw new \Exception($this->translate['sms_sending_failed']);
            }
	    
            /*$key  = $config['username1'];
	    //var_dump($type);exit;
	    $code = date("Y-m-d H:i:s");
            if (in_array($type, ['register', 'forgetpwd'])) {
                if (!$this->checkCode($mobile, $type)) {
                    throw new \Exception($this->di['message']->getSerMsg());
                }
                $code = rand(111111, 999999);
                $strArray = [$code];
                $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            }

            if (empty($config[$type])) {
                throw new \Exception('???????????????');
            }
	    $message = str_replace('{$var}',$code,$config[$type]);			
	    $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
	    $yp = new YunPian($logSer, $key);
            if (!$yp->send($mobile, $message)) {
                throw new \Exception('??????????????????');
            }	
	    */	  
            $add = [
                'mobile' => $mobile,
                'type' => $type,
                'expire_time' => $expireTime,
                'code' => $code,
                'message' => "{$config[$type]}|{$code}"
            ];

            if (!$this->save($add)) {
                throw new \Exception($this->translate['new_lang_send_error']);
            }

            return true;

        } catch (\Exception $e) {
	    echo  $e->getMessage();	
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function checkSendNum($mobile, $type)
    {

        try {

            $config = $this->di['config']->get('code');
            //$key = 'send_code_' . $mobile;
            //$lastCdSendTime = $this->di['cache']->get($key);
            $todayTime = strtotime(date('Ymd'));
//        if (!$lastCdSendTime) {
//            $lastCdSendTime = time();
//            //$this->di['cache']->set($key, $time, $config->$type['sms_cd_time']);
//        }
            $sendNum = $this->getCount(
                [
                    'mobile' => $mobile,
                    'type' => $type,
                    'addtime' => $todayTime
                ],
                [
                    'addtime' => '>'
                ]
            );

            $lastCode = $this->search($mobile, 'mobile');

            if ($config->$type['send_num'] <= $sendNum) {
                throw new \Exception($this->translate['sms_today_long_time']);
            }

            if (!empty($lastCode) && time() - $lastCode['addtime'] <= $config->$type['sms_cd_time']) {
                throw new \Exception($this->translate['send_too_fre_plase_wait']);
            }

            return true;

        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
        }

    }

    private function registerCheck($mobile)
    {
//        if ($this->di['s_user']->checkUserExist($mobile)) {
//            $this->errorMsg = '?????????????????????';
//            return false;
//        }

        return true;
    }

    private function forgetpwdCheck($mobile)
    {
        return true;
    }

}
