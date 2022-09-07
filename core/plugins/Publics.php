<?php

namespace C\P;

class Publics
{
    public function getRandStr($length = 10, $common = true)
    {
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($common) {
            $str .= '!@#$%^&*()_+{}';
        }

        $randString = '';
        $len = strlen($str) - 1;
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randString .= $str[$num];
        }
        return $randString;
    }


    public function getClentIp()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_ALI_CDN_REAL_IP'])) {
            $ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
        }

        if (empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public function checkMobile($mobile)
    {   
        if (preg_match("/[1234567890]+$/", $mobile)) {

            return true;
        }else{

            return false;
        }

    }
}
