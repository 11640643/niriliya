<?php

namespace C\S\Sys;

use C\L\Service;
use C\P\Http;

class IpData extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\SysIpData();
    }

    //ip转int
    private function intIp($ip)
    {
        $ips = explode('.', $ip);
        if (count($ips) != 4) {
            return false;
        }

        $number = 0;

        foreach ($ips as $k => $p) {
            if ($p < 0 || $p > 255) {
                return false;
            }
            $number += $p * pow(256, 3 - $k);
        }

        return $number;
    }


    //返回ip
    public function getIpToLocation($ip = '')
    {
        if (empty($ip)) {
            $ip = $this->getIp();
        }

        $intIp = $this->intIp($ip);
        $ipInfo = $this->search(['r_min' => $intIp, 'r_max' => $intIp], ['r_min' => '<=', 'r_max' => '>=']);
        if (!empty($ipInfo)) {
            return "{$ipInfo['country']} {$ipInfo['province']} {$ipInfo['city']} {$ipInfo['type']}";
        }

        //$url = 'http://ip.taobao.com//service/getIpInfo.php?ip=' . $ip;
        $url = 'http://ip-api.com/json/'.$ip.'?lang=zh-CN';
        
        $res = Http::get($url);
        if (!empty($res)) {
            $json = json_decode($res, true);
            if ($json['status'] == 'fail') {
                return '暂无匹配地址';
            }
            if (!empty($json) && isset($json['data'])) {
                return "{$json['data']['country']} {$json['data']['region']} {$json['data']['city']} {$json['data']['isp']}";
            }
            if (!empty($json) && !isset($json['data'])) {
                return "{$json['country']} {$json['region']} {$json['city']} {$json['isp']}";
            }
        }

        return '暂无匹配地址';
    }

    public function getIp()
    {
        $ip = '';
        if(!empty($_SERVER['HTTP_ALI_CDN_REAL_IP'])){
            $ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
        }

        if(empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if(empty($ip) && !empty($_SERVER['REMOTE_ADDR'])){
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

}
