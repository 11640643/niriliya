<?php

namespace C\S\Sys;

use C\L\Service;

class Config extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\SysConfig();
    }


    public function get($key)
    {
        $config = $this->getValue('content', ['type' => $key]);
        if(empty($config)){
            return [];
        }

        $json = json_decode($config, true);
        if($json){
            return $json;
        }

        return [];
    }

    public function getSignTypeConfig()
    {
        return [
            'sign_reward_type' => [
                'exchange_credit' => $this->translate['lang_cfg_exchange_credit'],
                'money' => $this->translate['money'],
            ],
        ];
    }
}
