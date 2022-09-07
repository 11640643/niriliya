<?php

namespace Sys;

use C\C\AdmController;

class UserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_sysuser;
        $this->hideKeys = [
            'password'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'last_login_time'
        ];
    }

    //系统用户信息
    public function infoAction()
    {
        $user = $this->s_sysuser->search($this->uid, false, ['id', 'username', 'last_login_time']);
        $user['last_login_time_date'] = date('Y-m-d H:i:s', $user['last_login_time']);
        $this->success($user);
    }

    /**
     * 修改密码
     */
    public function setpwdAction()
    {   
        $data = $this->getValue('data');

        $passwd   = $data['passwd'];
        $npasswd  = $data['npasswd'];
        $rpasswd  = $data['rpasswd'];

        if ( $rpasswd != $npasswd ){
            $this->error();
        }

        $user = $this->s_sysuser->search();

        if($user['password'] != md5($passwd)){
            $this->error($this->translate['oldpwerr']); 
        }


        if($user['password'] == md5($npasswd)){
            $this->error($this->translate['notmatch']);
        }


        if($this->s_sysuser->update($user['id'], ['password' => md5($npasswd)])){
            //$this->ssid->destory();
            //$this->success();
        }

        $this->error();

    }

    

}


