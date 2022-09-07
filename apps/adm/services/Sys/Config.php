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
                'exchange_credit' => '兑换积分',
                'money' => '现金'
            ],
        ];
    }
}
