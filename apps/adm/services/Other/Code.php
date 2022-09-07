<?php

namespace C\S\Other;

use C\L\Service;
use C\P\ChuangLan;
use C\P\YunPian;
use C\P\ZhiNiu;


// use C\P\PHPMailer;
use C\P\PHPMailer\src\PHPMailer;
use C\P\PHPMailer\src\Exception;
use C\P\PHPMailer\src\SMTP;

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
                throw new \Exception('非法的型验证码类型');
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
                throw new \Exception('验证码有误');
            }

            if (!$this->update($data['id'], ['status' => 'Y'])) {
                throw new \Exception('code修改失败');
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setCodeMsg($e->getMessage());
            return false;
        }
    }
    public function verifyEmail($email, $code, $type)
    {
        try {

            if (!$this->checkMsgType($type)) {
                throw new \Exception('非法的型验证码类型');
            }

            $data = $this->search(
                [
                    'email' => $email,
                    'type' => $type,
                    'status' => 'S',
                    'code' => $code,
                ]
            );
            // var_dump($data);
            if (empty($data) || $data['expire_time'] < time()) {
                throw new \Exception('验证码有误或超时');
            }

            if (!$this->update($data['id'], ['status' => 'Y'])) {
                throw new \Exception('code修改失败');
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

            // if (!$this->di['public']->checkMobile($mobile)) {
            //     throw new \Exception('手机号有误');
            // }

            if (!$this->checkMsgType($type)) {
                throw new \Exception('非法的型验证码类型');
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
    public function sendEmail($email, $type, $params = '')
    {
        $code = rand(111111, 999999);//验证码
        $mail = new PHPMailer(true); // Passing `true` enables exceptions
        $translate =  $this->getTranslation();
        $message = $translate['email-subject'];
        // var_dump($message);die;
        try {

            $config = $this->di['s_config']->get('sms_cl');
            $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            $code = rand(111111, 999999);//验证码
            $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
            //服务器配置
            $mail->CharSet ="UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.126.com';                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = 'chuanxiaoyi558@126.com';                // SMTP 用户名  即邮箱的用户名
            $mail->Password = 'cczx.147258';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            // $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = 25;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom('chuanxiaoyi558@126.com');  //发件人
            // $mail->addAddress('yiranfeng@aliyun.com');  // 收件人
            $mail->addAddress($email);  // 收件人
            
            //Content
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = $message;
            $mail->Body    = $message.':'.$code;
            // $mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
            $mail->send();
            //发送成功之后之执行写入操作
            $add = [
                'email' => $email,
                'type' => $type,
                'expire_time' => $expireTime,
                'code' => $code,
                'message' => "{$message}|{$code}"
            ];
            if (!$this->save($add)) {
                throw new \Exception($translate['send-error']);
            }
            return true;
            // echo '邮件发送成功';
        } catch (Exception $e) {
            echo  $e->getMessage();	
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
            // throw new \Exception($mail->ErrorInfo);
        }
    }

    public function send($mobile, $type, $params = '')
    {
        try {

            $config = $this->di['s_config']->get('sms_cl');
            $expireTime = 0;
  //          $code = '';
//	    var_dump($config);exit;
          /*  $username = $config['username2'];
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
                throw new \Exception('未找到模板');
            }

            $logSer = $this->di['log']->set('code_cl_' . date('Ymd') . '.log');
            $cl = new ChuangLan($logSer, $username, $password);
		
            if (!$cl->send($mobile, $config[$type], $params)) {
                throw new \Exception('短信发送失败');
            }
	    $key  = $config['username1']; 
	    */
	        $appkey  = $config['username1'];
            $appcode = $config['password1'];
            $appsecret = $config['username2'];
            // var_dump($config);exit;
            $code = date("Y-m-d H:i:s");
            if (in_array($type, ['register', 'forgetpwd'])) {
                if (!$this->checkCode($mobile, $type)) {
                    throw new \Exception($this->di['message']->getSerMsg());
                }
                $code = rand(111111, 999999);
                $strArray = [$code];
                $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            }else{
                $key = $config['username2'];
            }

            if (empty($config[$type])) {
                throw new \Exception('未找到模板');
            }
            $message = str_replace('{$var}',$code,$config[$type]);
            $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
            
            //原有发送通道
	        $yp = new ZhiNiu($logSer, $appkey,$appcode,$appsecret);
            if (!$yp->send($mobile, $message)) {
                throw new \Exception('短信发送失败');
            }
            //新增日志
            $add = [
                'mobile' => $mobile,
                'type' => $type,
                'expire_time' => $expireTime,
                'code' => $code,
                'message' => "{$config[$type]}|{$code}"
            ];
	       // var_dump($add);
            if (!$this->save($add)) {
                throw new \Exception('发送失败');
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
                throw new \Exception('今日发送已超过限制，请明日再试');
            }

            if (!empty($lastCode) && time() - $lastCode['addtime'] <= $config->$type['sms_cd_time']) {
                throw new \Exception('发送太频繁请稍后');
            }

            return true;

        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
        }

    }

    private function registerCheck($mobile)
    {
//        if ($this->di['s_user']->checkUserExist($mobile)) {
//            $this->errorMsg = '该手机号已注册';
//            return false;
//        }

        return true;
    }

    private function forgetpwdCheck($mobile)
    {
        return true;
    }

}
