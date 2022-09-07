<?php

namespace C\S\Record;

use C\L\Service;

class Login extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\LoginLog();
    }

    // public function getStatusConfig()
    // {
    //     return [
    //         'status' => [
    //             'S' => '待发货',
    //             'D' => '已发货',
    //         ],
    //     ];
    // }

    /**
     * 登录日志记录
     */
    public function setRecord($user)
    {
        $s_user = $this->di['s_user']->search(['uid' => $user['id']]);
        $name = isset($s_user['name']) ? $s_user['name'] : '';
        $ip = $this->di['s_ip']->getIp();
        $area = $this->di['s_ip']->getIpToLocation($ip);
        $mobile = isset($s_user['mobile']) ? $s_user['mobile'] : '';
        if (!$name) {
            $logSer = $this->di['log']->set('record_login_' . date('Ymd') . '.log');
            $logSer->info(json_encode([
                'name' => $name,
                'ip' => $ip,
                'area' => $area,
                'mobile' => $mobile
            ], JSON_UNESCAPED_UNICODE));
            return false;
        }
        return $this->add($name, $ip, $area, $mobile);
    }

    public function add($name, $ip, $area, $mobile)
    {
        $add = [
            'name' => $name,
            'ip' => $ip,
            'area' => $area,
            'mobile' => $mobile
        ];
        return $this->save($add);
    }


}