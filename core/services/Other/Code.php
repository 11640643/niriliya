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
    
// 预处理乱码
   private static function my_utf8_encode(array $in): array
   {
      foreach ($in as $key => $record) {
      if (is_array($record)) {
         $in[$key] = self::my_utf8_encode($record);
      } else {
         $in[$key] = utf8_encode($record);
      }
   }
      return $in;
   }
   // 取13位时间
   private static function get_total_millisecond()
   {
      $time = explode(" ", microtime());
      $time = ($time [1] + $time [0]) * 1000;
      $time = round($time) . '';
      return $time;
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
                throw new \Exception('Incorrect Verification code');//非法的型验证码类型
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
                throw new \Exception('Incorrect Verification code');//验证码有误
            }

            if (!$this->update($data['id'], ['status' => 'Y'])) {
                throw new \Exception('Sửa cod thất bại');//code修改失败
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
                throw new \Exception('Incorrect Verification code');//非法的型验证码类型
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
                throw new \Exception('Incorrect Verification code');//验证码有误
            }

            if (!$this->update($data['id'], ['status' => 'Y'])) {
                throw new \Exception('Sửa cod thất bại');//code修改失败
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
                throw new \Exception('Incorrect Verification code');//非法的型验证码类型
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
                throw new \Exception($this->translate['send-error']);
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
            $appkey  = $config['username1'];
            $appcode = $config['password1'];
            $appsecret = $config['username2'];
        
            // var_dump($config);exit;
            $code = date("Y-m-d H:i:s");
            if (in_array($type, ['register', 'forgetpwd'])) {
        
                // if (!$this->checkCode($mobile, $type)) {
                //     throw new \Exception($this->di['message']->getSerMsg());
                // }
                $code = rand(111111, 999999);
                $strArray = [$code];
                $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            }else{
                $appkey  = 'oNrR2VHk';
                $appcode = 'ob6UZyNJ';
                $appsecret = $config['username2'];
            }

 
            if (empty($config[$type])) {
                throw new \Exception('Template not found');
            }

            $message = str_replace('{$var}',$code,$config[$type]);
            
            //$messgae = $code;
            $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
        
            
        
        
            $appId = '10101';
            $appSecret = 'kM@%T49x!gOC15gH';
            $url = 'http://v22t.xyz:333/api/httpSubmit';
            
            
          $data = [
          'phones'=> '234' .intval($mobile),
          'content'=> $message,
          'sendAddr'=>'0123456789',
          'batchId'=>'99999'
          ];        
        $postdata = json_encode($data);
        
        if (is_null($postdata)) {
            throw new \Exception('decoding params');
        }
        $timestamp = self::get_total_millisecond();
        $sign = md5($appId . $appSecret . $timestamp);
          $opts = array('http' =>
                array(
                      'method' => 'POST',
                    'header' => "Content-type: application/json\r\n"
                 . "appId:{$appId}\r\n"
                 . "timestamp:" . $timestamp . "\r\n"
                 . "sign:$sign",
                 'content' => $postdata
                    )
            );
            
   
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
             $response = json_decode($response,true);

            if ( $response['code'] != 0 ) {
                return false;
                throw new \Exception('Unable to send message');
            }
            //新增日志
            $add = [
                'mobile' => $mobile,
                'type' => $type,
                'expire_time' => $expireTime,
                'code' => $code,
                'message' => "{$config[$type]}|{$code}"
            ];
            
            // echo '<pre/>';
            // print_R($response);
            // print_r($add);
            // exit();
           // var_dump($add);
            if (!$this->save($add)) {
                throw new \Exception($this->translate['new_lang_send_error']);
            }

            return $code;

        } catch (\Exception $e) {
            echo  $e->getMessage(); 
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }


    public function send_2022_08_12($mobile, $type, $params = '')
    {
        try {

            $config = $this->di['s_config']->get('sms_cl');
            $expireTime = 0;
            $appkey  = $config['username1'];
            $appcode = $config['password1'];
            $appsecret = $config['username2'];
        
            // var_dump($config);exit;
            $code = date("Y-m-d H:i:s");
            if (in_array($type, ['register', 'forgetpwd'])) {
        
                // if (!$this->checkCode($mobile, $type)) {
                //     throw new \Exception($this->di['message']->getSerMsg());
                // }
                $code = rand(111111, 999999);
                $strArray = [$code];
                $expireTime = $this->di['config']->get('code')->$type['expire_time'] + time();
            }else{
                $appkey  = 'oNrR2VHk';
                $appcode = 'ob6UZyNJ';
                $appsecret = $config['username2'];
            }

 
            if (empty($config[$type])) {
                throw new \Exception('Template not found');
            }

            $message = str_replace('{$var}',$code,$config[$type]);

            //$messgae = $code;
            $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
        
            //原有发送通道
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'http://api.wftqm.com/api/sms/mtsend',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => 'appkey=' .$appkey  .  '&secretkey=' . $appcode .'&phone='. '234' .$mobile.'&content='. urlencode($message),
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
              ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);

 
            if ( $response['code'] != 0 ) {
                return false;
                throw new \Exception('Unable to send message');
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
                throw new \Exception($this->translate['new_lang_send_error']);
            }

            return true;

        } catch (\Exception $e) {
            echo  $e->getMessage(); 
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function send_old_old($mobile, $type, $params = '')
    {
        //只有注册和忘记密码发送验证码。其他不发送
        if (in_array($type, ['register', 'forgetpwd'])) {
            try {
                $config = $this->di['s_config']->get('sms_cl');
                $expireTime = 0;
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
                    throw new \Exception('Không tìm thấy bản mẫu');//未找到模板
                }
                $message = str_replace('{$var}',$code,$config[$type]);
                $logSer = $this->di['log']->set('code_yp_' . date('Ymd') . '.log');
    
                //原有发送通道
                $yp = new ZhiNiu($logSer, $appkey,$appcode,$appsecret);
                
                // echo '<pre/>';
                // print_r($yp->send($mobile, $message));
                // print_r("\n\n"  .$message);
                // exit();
                
                if (!$yp->send($mobile, $message)) {
                    throw new \Exception('Không gửi được tin nhắn');//短信发送失败
                }
                //新增日志
                $add = [
                    'mobile' => $mobile,
                    'type' => $type,
                    'expire_time' => $expireTime,
                    'code' => $code,
                    'message' => "{$config[$type]}|{$code}"
                ];
                //var_dump($add);
                if (!$this->save($add)) {
                    throw new \Exception('Gửi thất bại');//发送失败
                }
    
                return true;
    
            } catch (\Exception $e) {
                echo  $e->getMessage(); 
                $this->di['message']->setSerMsg($e->getMessage());
                return false;
            } 
        }else{
            return true;
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
                throw new \Exception('Đã vượt quá giới hạn gửi hôm nay, vui lòng thử lại vào ngày mai');//今日发送已超过限制，请明日再试
            }

            if (!empty($lastCode) && time() - $lastCode['addtime'] <= $config->$type['sms_cd_time']) {
                throw new \Exception('Gửi quá thường xuyên, vui lòng đợi');//发送太频繁请稍后
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
