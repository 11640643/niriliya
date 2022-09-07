<?php

namespace C\S\Sys;

use C\L\Service;

class User extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\SysUser();
    }


    /**
     * [login 二开]
     * 采用 JWT 方式登录
     * @param  [type] $username [description]
     * @param  [type] $password [description]
     * @param  [type] $config   [description]
     * @return [type]           [description]
     */
    public function login($username, $password,$config){
        try {
            $config = $config->get('config')->toArray();    
            $jwt = new \C\P\Jwt($config['jwt_secret'],$config['jwt_expire_time']);

            $user = $this->search(
                [
                    'username' => $username
                ]
            );               
            if(md5($password)!=$user['password']){
                throw new \Exception($this->translate['new_lang_pwd_wrong']);
            }
            $jwtcode =  $jwt->genTOken( $this->di['s_ip']->getIp() .'::' md5($password) .'::'. $user['id'] . '::'.$user['username'] );
            $this->di['ssid']->set('uid', $user['id']);
            $this->di['ssid']->set('username', $user['username']);
            $this->di['ssid']->set('jwtcode', $jwtcode );
            $this->di['ssid']->set('realip', $this->di['s_ip']->getIp()  );

            // 操作日志记录
            $this->di['s_login_adm']->setRecord($user);
            return true;
        } catch (\Exception $e) {
            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }

  
    // public function login($username, $password, $sf_code, $vf_code)
    public function loginbak($username, $password)
    {

        try {

            $user = $this->search(
                [
                    'username' => $username
                ]
            );
            
            if(md5($password)!=$user['password']){
                throw new \Exception($this->translate['new_lang_pwd_wrong']);
            }
            
            $this->di['ssid']->set('uid', $user['id']);
            $this->di['ssid']->set('username', $user['username']);

            // 验证两个码
            // $code1 = $this->di['s_sfcode']->checkSfcode($sf_code);
            // $code2 = $this->di['s_sfcode']->updateCodeStatus($vf_code);
            // if (!$code1 || !$code2) {
            //     throw new \Exception('验证码有误');
            // }

            if (!$this->update($user['id'], ['last_login_time' => time()])) {
                throw new \Exception($this->translate['login_failed']);
            }

            // 操作日志记录
            $this->di['s_login_adm']->setRecord($user);

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }



}
