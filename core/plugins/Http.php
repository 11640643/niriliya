<?php

namespace C\P;

class Http
{

    private static $_curl = null;
    private static $_opts = array();
    private static $_init = false;
    private static $_error = '';
    public static $header = array();
    public static $withHeader = false;
    public static $cookieFile = null;

    public static function exec($curl)
    {
        self::$_error = '';
        $res = curl_exec($curl);
        $errno = curl_errno($curl);
        if ($errno != 0) {
            self::$_error = $errno . ' ' . curl_error($curl);
        }
        return $res;
    }
    public static function YunZhiXin($url,$body,$method){

        if (function_exists("curl_init")) {
            $header = array(
                'Accept:application/json',
                'Content-Type:application/json;charset=utf-8',
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if($method == 'post'){
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = array();
            $opts['http'] = array();
            $headers = array(
                "method" => strtoupper($method),
            );
            $headers[]= 'Accept:application/json';
            $headers['header'] = array();
            $headers['header'][]= 'Content-Type:application/json;charset=utf-8';

            if(!empty($body)) {
                $headers['header'][]= 'Content-Length:'.strlen($body);
                $headers['content']= $body;
            }

            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }
        return $result;

    }
    public static function get($url, $header = [], $timeout = 10, $close = true)
    {
        self::init();

        self::setOption('timeout', $timeout);
        self::setOption('connecttimeout', $timeout);
        self::setOption('url', $url);
        self::setOption('post', false);
        self::setOption('httpheader', self::getHeader($header));

        $res = self::exec(self::$_curl);
        if ($close) {
            self::close();
        }
        return $res;
    }

    public static function post($url, $data = array(), $header = [], $timeout = 5, $close = true)
    {
        self::init();
        self::setOption('timeout', $timeout);
        self::setOption('connecttimeout', $timeout);
        self::setOption('url', $url);
        self::setOption('post', true);
        self::setOption('ssl_verifypeer', false);
        if (is_string($data) && substr($data, 0, 1) == '{') {
            self::setOption('postfields', $data);
        } else {
            self::setOption('postfields', self::toFields($data));
        }
        if (!empty($header) && is_array($header)) {
            self::setOption('httpheader', self::getHeader($header));
        }
        $res = self::exec(self::$_curl);
        if ($close) {
            self::close();
        }
        return $res;
    }

    public static function toFields($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        ksort($data);
        $field = '';
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vv) {
                    $field .= $k . '[]=' . $vv . '&';
                }

            } else {
                if (substr($v, 0, 1) == '@') {
                    return $data;
                }
                $field .= $k . '=' . $v . '&';
            }
        }

        return trim($field, '&');
    }

    public static function getOption($key)
    {
        return isset(self::$_opts[$key]) ? self::$_opts[$key] : '';
    }

    public static function setOption($key, $value)
    {
        self::$_opts[$key] = $value;
        //userpwd,useragent,header,cookie,referer,...
        $res = curl_setopt(self::$_curl, constant("CURLOPT_" . strtoupper($key)), $value);

        return $res;
    }

    public static function setHeader($key, $value = null)
    {
        if (is_array($key)) {
            self::$header = $key;

        } else {
            if (empty($value)) {
                self::$header[] = $key;

            } else {
                self::$header[] = $key . ': ' . $value;
            }
        }
        return true;
    }

    public static function getHeader($data)
    {
        $default = ['Content-Type: application/json; charset=utf-8'];
        if (empty($data)) {
            return $default;
        }

        $header = [];
        foreach ($data as $k => $v) {
            $header[] = "{$k}:{$v}";
        }

        return $header;
    }

    public static function init()
    {
        if (self::$_curl) {
            curl_close(self::$_curl);
        }
        self::$_curl = curl_init();

        //discard header
        self::setOption('header', self::$withHeader);
        self::setOption('useragent', 'Http/1.0');
        //self::setOption('referer', 'http://www.google.com/');
        self::setOption('returntransfer', true);
        self::setOption('followlocation', true);

        self::setOption('ssl_verifypeer', false);

        if (!empty(self::$header)) {
            self::setOption('httpheader', self::$header);
        }

        if (!empty(self::$cookieFile)) {
            self::setOption('cookiejar', self::$cookieFile);
            self::setOption('cookiefile', self::$cookieFile);
        }

        self::$_init = true;
    }

    public static function close()
    {
        if (self::$_curl) {
            curl_close(self::$_curl);
            self::$_curl = null;
        }

        if (!empty(self::$cookieFile) && file_exists(self::$cookieFile)) {
            unlink(self::$cookieFile);
        }
    }

    public static function __callStatic($method, $args)
    {
        //like: getContentType,getHttpCode
        $pre = substr($method, 0, 3);
        if ('get' == $pre) {
            $key = substr($method, 3);
            $key = preg_replace('/[A-Z]/', "_\\0", $key);
            $key = strtolower(trim($key, '_'));
            $info = self::getInfo();
            if (isset($info[$key])) {
                return $info[$key];
            }
        }

        throw new Exception('No such method: ' . $method);
    }

    public static function getInfo($curl = null)
    {
        if ($curl === null) {
            $curl = self::$_curl;
        }

        if ($curl === null) {
            return array();
        }

        return curl_getinfo($curl);
    }

    public static function getError()
    {
        return self::$_error;
    }

    public static function curlPost($url, $data = array(), $timeout = 5, $close = true)
    {
        self::init();
        self::setOption('timeout', $timeout);
        self::setOption('connecttimeout', $timeout);
        self::setOption('url', $url);
        self::setOption('post', true);
        self::setOption('ssl_verifypeer', false);
        if (is_string($data) && substr($data, 0, 1) == '{') {
            self::setOption('httpheader', array('Content-Type: application/json; charset=utf-8'));
            self::setOption('postfields', $data);
        } else {
            self::setOption('postfields', $data);
        }
        $res = self::exec(self::$_curl);
        if ($close) {
            self::close();
        }
        return $res;
    }

    public static function zhihuGet($url, $header = [], $timeout = 10, $close = true)
    {
        self::init();

        self::setOption('timeout', $timeout);
        self::setOption('connecttimeout', $timeout);
        self::setOption('url', $url);
        self::setOption('post', false);
        self::setOption('httpheader', self::getHeader($header));

        $res = self::exec(self::$_curl);
        $status_code = self::getInfo();
        if ($close) {
            self::close();
        }
        $result = [
            'status_code' => $status_code['http_code'],
            'res' => $res
        ];
        return json_encode($result);
    }
}
