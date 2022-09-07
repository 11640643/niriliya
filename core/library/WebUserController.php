<?php

namespace C\L;

class WebUserController extends WebController
{
    protected function checkLogin()
    {
        if (!$this->uid) {
            $this->error($this->translate['login_first'], 403);
        }

//        $key = 'today_is_login_' . $this->uid;
//        if($this->cache->incr($key, 1, 24 * 3600) < 2){
//            $config = $this->s_config->get('reward');
//            $this->s_funds->add($this->uid, $config['login_money'], 'money', '每日登录奖励');
//        }

        if ($this->uid > 0) {

            $this->cache->setex('ssid_' . $this->uid, $this->ssidKey, 48 * 3600);
            if ($this->s_user->getValue('status', $this->uid) != 'Y') {
                $this->error($this->translate['login_first'], 403);
            }

        }

    }

    protected function checkAuth()
    {
        if ($this->s_user->getValue('is_auth', $this->uid) != 'Y') {
            $this->error($this->translate['auth_first'], 405);
        }

        return true;
    }


    public function checkSetPayPwd()
    {
        if (!$this->s_user->getValue('pay_pwd', $this->uid)) {
            $this->error($this->translate['set_pay_pw'], 406);
        }

        return true;
    }

}
