<?php

namespace C\L;

class Session extends Service
{
    //用户状态凭证
    private $ssidKey;
    private $expireTime;

    public function expire()
    {
        $this->di['cache']->expire($this->ssidKey, $this->expireTime);
    }

    //删除此ssid
    public function destory()
    {
        $this->di['cache']->remove($this->ssidKey);
        $this->di['cache']->remove('ssid_' . $this->ssidKey);
        $this->ssidKey = null;
    }

    //判断ssid是否存在
    public function exists($ssid)
    {
        return $this->di['cache']->exists($ssid);
    }

    public function register($ssid = null)
    {
        $this->expireTime = $this->di['config']->get('config')['expire_time'];

        if ($this->exists($ssid) && $ssid!='' ) {

            $this->ssidKey = $ssid;

        } else {

            if (!$this->ssidKey) {
                $key = $this->di['public']->getRandStr(10);
                $this->ssidKey = substr(md5(uniqid(rand(), 1)), 8, 16);
                $this->set('aes_key', $key);
                $this->set('dev_no', '');
                $this->set('dev_type', '');
                $this->set('footer', 'n1');
                $this->set('expire_time', $this->expireTime);
            }

        }

        $this->expire();
        return $this->ssidKey;
    }

    public function getSsid()
    {
        return $this->ssidKey;
    }

    /**
     * 根据key获取值
     * @param type $key
     * @return null
     */
    public function get($key)
    {
        return $this->di['cache']->hGet($this->ssidKey, $key);
    }

    /**
     * 将值保存到ssid里面
     * @param type $key
     * @param type $value
     */
    public function set($key, $value)
    {
        return $this->di['cache']->hSet($this->ssidKey, $key, $value);
//        $this->_data[$key]=$value;
//        $this->save();
    }

    public function setM($data)
    {
        return $this->di['cache']->hMset($this->ssidKey, $data);
    }

    public function del($key)
    {
        return $this->di['cache']->hDel($this->ssidKey, $key);
    }
}
