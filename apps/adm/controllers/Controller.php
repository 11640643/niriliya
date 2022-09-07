<?php

namespace C\C;
use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Filter;

// 后台
class Controller extends \Phalcon\Mvc\Controller
{
    protected $service;
    protected $params;
    protected $hideKeys;
    protected $showKeys;
    protected $timeToDateKeys = [
        'addtime', 'uptime'
    ];
    protected $updateKeys = [];
    protected $createKeys = [];
    protected $uid = 0;
    protected $username = '';
    protected $ssidKey = '';

    protected $keyworkSearchKeys;
    protected $pubSearchKeys;
    protected $likeSearchKeys;
    protected $jwt;
    protected $jwt_auth_token;
    protected $filter;

    protected function onConstruct()
    {    
        $this->filter =  new Filter();
        // $_SERVER['HTTP_AUTHORIZATION']
        // exit();
        //获取语言标识
        $this->language = $this->request->getHeader('language');
        $url = $this->router->getMatches()[0];
        $url = str_replace('/v1/api', '', $url);

        $publicUrls = $this->config->get('config')['public_api_urls']->toArray();
        $this->ssidKey = $this->getValue('ssid', false, 'alphanum');
       
        $this->translate = $this->getTranslation();


        $_SERVER['HTTP_AUTHORIZATION'] =  isset($_SERVER['HTTP_AUTHORIZATION'] )? $_SERVER['HTTP_AUTHORIZATION']: '' ;
        $auth_token = strlen( $_SERVER['HTTP_AUTHORIZATION'] )>20 ? substr( $_SERVER['HTTP_AUTHORIZATION'],7) : '' ;
        if ( $auth_token =='' ){
            // $auth_token = $this->ssid->get('jwtcode');
        }

        $config = $this->config->get('config')->toArray();    
        $this->jwt = new \C\P\Jwt($config['jwt_secret'],$config['jwt_expire_time']);
        $auth_answer = $this->jwt->verifyToken($auth_token);

 
        if (!in_array($url, $publicUrls)) {
            if ($auth_answer == '' && $url !='/api/api/login'  ){
                $this->error($this->translate['auth_error'], 401);
            }
        }


        if ($auth_answer != ''  && $url !='/api/api/login'   ){
            $auth_answer_arr = explode('::', $auth_answer);
            if (is_array( $auth_answer_arr ) && count($auth_answer_arr) >2 ){
                // 用户名 密码以验证
                //  ...
                $this->jwt_auth_token = $auth_answer_arr;
                $this->uid = $auth_answer_arr[2];
                $this->username = $auth_answer_arr[3];
                if (   $this->di['s_ip']->getIp() !=  $auth_answer_arr[0])  {
                    $this->error($this->translate['auth_error'], 401);
                }
            }
        }

        $this->checkMaintain();
        $this->checkLogin();
        $this->init();
    }

    // protected function onConstruct()
    // {
    //     //获取语言标识
    //     $this->language = $this->request->getHeader('language');
    //     $url = $this->router->getMatches()[0];
    //     $publicUrls = $this->config->get('config')['public_api_urls']->toArray();
    //     $this->ssidKey = $this->getValue('ssid', false, 'alphanum');
       
    //     $this->translate = $this->getTranslation();

    //     if (!in_array($url, $publicUrls)) {
    //         if (!$this->ssid->exists($this->ssidKey)) {
    //             $this->error($this->translate['auth_error'], 401);
    //         }

    //     }

    //     if ($this->ssidKey) {
    //         $this->ssid->register($this->ssidKey);
    //     }
    //     $this->uid = $this->ssid->get('uid');
        
    //     $this->checkMaintain();
    //     $this->checkLogin();
    //     $this->init();
    // }
    protected function getTranslation()
    {
        // 询问浏览器什么是最好的语言
        // $language = $this->request->getBestLanguage();
        
        $messages = [];

        $translationFile = '../../apps/lang/' . $this->language . '.php';
        // 检查我们是否有该lang的翻译文件
        if (file_exists($translationFile)) {
            require $translationFile;
        } else {
            // 回退到某些默认值
            require APP_PATH . '/lang/cn.php';
        }

        // 返回翻译对象$messages来自上面的require语句
        return new NativeArray(
            [
                'content' => $messages,
            ]
        );
        // $this->translate = new NativeArray(
        //     [
        //         'content' => $messages,
        //     ]
        // );
    }
    protected function checkMaintain()
    {
        return true;
    }

    protected function checkLogin()
    {
        return true;
    }

    protected function init()
    {
        return true;
    }

    protected function error($message = '', $code = 410)
    {
        $serMsg = $this->di['message']->getSerMsg();

        if (!empty($serMsg)) {
            $message = $serMsg;
        }

        if (empty($message)) {
            $message = $this->translate['doerror'];
        }

        $this->echoJson([
            'code' => $code,
            'message' => $message,
        ]);
    }

    protected function success($data = [], $message = '')
    {
        $this->echoJson([
            'message' => $message,
            'data' => $data
        ]);
    }

    private function echoJson($data = [])
    {
        $message = $this->translate['action_success'];

        if (!empty($data['message'])) {
            $message = $data['message'];
        }

        $code = 200;
        if (!empty($data['code'])) {
            $code = $data['code'];
        }

        if (empty($data['data'])) {
            $data['data'] = [];
        }

        $json = [
            'code' => $code,
            'msg' => $message,
        ];

        if (APP_MODE == 'dev') {
            $json['dev_show_msg'] = implode('|', [
                $this->config->get('error')[$message],
                $this->message->getSerMsg(),
                $this->message->getCodeMsg()
            ]);
        }

        if ($code == 200) {
            $json['data'] = $data['data'];
        }

        if ($this->ssid->get('is_login') == 'Y') {
            $this->cache->zAdd('login_user', $this->ssid->get('uid'), time());
        }

            $url =  $this->router->getMatches()[0] ;
            $url = str_replace('/v1/api', '', $url);

        if  ($url  =='/api/api/login'){        
            $json = array_merge($json, [
                'btime' => $_SERVER['REQUEST_TIME'],
                'etime' => time(),
                'ssid' => $this->ssidKey,
                //'pstt' => $this->ssid->get('jwtcode'),
                'pstt' =>$_SESSION['jwtcode'],
            ]);

        }else{
            $json = array_merge($json, [
                'btime' => $_SERVER['REQUEST_TIME'],
                'etime' => time(),
                'ssid' => $this->ssidKey,
                'pstt' => '',
            ]);
        }
        $this->weblog->alert(json_encode($json, JSON_UNESCAPED_UNICODE));
        $response = new \Phalcon\Http\Response;
        $response->setStatusCode(200, 'OK');
        $response->setContent(json_encode($json));
        $response->send();

        exit();
    }


    protected function getValue($key = 'id', $check = false, $type = 'trim')
    {
        $value = $this->request->get($key, $type);
        if (empty($value)) {
            $value = $this->request->getPost($key);
            if(is_string($value)){
                $value = trim($value);
            }
        }

        if (is_array($check) && !in_array($value, $check)) {
            $this->error($this->translate['param_error']);
        }

        if ($check && (!isset($_GET[$key]) && !isset($_POST[$key]))) {
            $this->error($this->translate['param_error']);
        }


        if ($type == 'int') {

            if (strlen($value) > 10) {
                $this->error($this->translate['max_num']);
            }
        }

        if (empty($value) && !isset($_GET[$key]) && !isset($_POST[$key])) {
            $value = null;
        }

        return $value;
    }

    /**
     *  可以对 POST['data'] 内的字段进行检查
     */
    protected function getValue2($key = 'id', $check = false, $type = 'trim')
    {

        $value = $this->request->get($key, $type);
        if (empty($value)) {
            $value = $this->request->getPost('data')[$key];
            if(is_string($value)){
                $value = trim($value);
            }
        }

        if (is_array($check) && !in_array($value, $check)) {
            $this->error($this->translate['param_error']);
        }

        if ($check && (!isset($_GET[$key]) && !isset($_POST[$key]))) {
            $this->error($this->translate['param_error']);
        }


        if ($type == 'int') {

            if (strlen($value) > 10) {
                $this->error($this->translate['max_num']);
            }
        }

        if (empty($value) && !isset($_GET[$key]) && !isset($_POST[$key])) {
            $value = null;
        }

        return $value;
    }



    protected function setCategoryName(&$data)
    {
        $category_server = new \C\M\GoodsCat();
        foreach ($data as $key => &$value) {
            if (!isset($value['category_id'])) {
                continue;
            }
            if ($value['category_id'] == '0') {
                $value['category_name'] = '暂无';
            } else {
                $category_name = $category_server->findFirst($value['category_id']);
                $value['category_name'] = $category_name->name;
            }
        }
        return true;
    }

    protected function setSearchParams()
    {
        $params = array_merge($this->request->getPost(), $this->request->get());
        foreach ($params as $key => $value) {

            $skey = '';
            if (strstr($key, 'begin_')) {
                $skey = 'begin_';
            }
            if (strstr($key, 'end_')) {
                $skey = 'end_';
            }

            if (empty($skey) || empty($value)) {
                continue;
            }

            $keys = explode($skey, $key);
            if (empty($key[1]) || !empty($this->params['data'][$keys[1]])) {
                continue;
            }

            $beginKey = 'begin_' . $keys[1];
            $endKey = 'end_' . $keys[1];

            $beginNumber = 0;
            $endNumber = 0;
            if (empty($params[$beginKey]) && empty($params[$endKey])) {
                continue;
            }

            if (!empty($params[$beginKey])) {
                $beginTime = strtotime($params[$beginKey]);
                $beginNumber = $this->isDate($params[$endKey]) ? $beginTime : intval($params[$beginKey]);
            }

            if (!empty($params[$endKey])) {
                $endNumber = $this->isDate($params[$endKey]) ?
                    strtotime($params[$endKey]) + 24 * 3600 - 1 : intval($params[$endKey]);
            }

            unset($params[$beginKey]);
            unset($params[$endKey]);

            if ($beginNumber && $endNumber) {

                $this->params['data'][$keys[1]] = [$beginNumber, $endNumber];
                $this->params['data_type'][$keys[1]] = "between";

            } else if ($beginNumber) {

                $this->params['data'][$keys[1]] = $beginNumber;
                $this->params['data_type'][$keys[1]] = '>=';
            } else if ($endNumber) {

                $this->params['data'][$keys[1]] = $endNumber;
                $this->params['data_type'][$keys[1]] = '<=';
            }

        }

        if (!empty($params['keyword_search_value']) && !empty($this->keyworkSearchKeys)) {

            $count = count($this->keyworkSearchKeys);
            foreach ($this->keyworkSearchKeys as $i => $key) {

                if ($key == 'uid') {

                    $keyword = $this->getValue('keyword_search_value');
                    if (!empty($keyword)) {
                        $users = $this->s_user->searchPage(['mobile' => " (mobile like '%{$keyword}' or name like '%{$keyword}') "], ['mobile' => 'sql']);
                        if (!empty($users)) {
                            $this->params['data']['uid'] = array_column($users, 'uid');
                            $this->params['data_type']['uid'] = 'in';
                        } else {
                            $this->params['data']['uid'] = -1;
                        }
                    }

                } else {

                    $type = '|';
                    $this->params['data'][$key] = $params['keyword_search_value'];

                    if ($count > 1) {

                        if ($i == 0) {
                            $type = '|%';
                        }

                        if ($i == $count - 1) {
                            $type = '%|';
                        }

                    } else {

                        $type = '%';
                    }

                    $this->params['data_type'][$key] = $type;

                }
            }

        }


        if (!empty($this->pubSearchKeys)) {

            foreach ($this->pubSearchKeys as $key) {
                $value = $this->getValue($key);
                if (!empty($value)) {
                    $this->params['data'][$key] = $value;
                    if (is_array($value)) {
                        $this->params['data_type'][$key] = 'in';
                    }
                }
            }
        }

        if (!empty($this->likeSearchKeys)) {

            foreach ($this->likeSearchKeys as $key) {
                $value = $this->getValue($key);
                if (!empty($value)) {
                    $this->params['data'][$key] = $value;
                    $this->params['data_type'][$key] = 'like';
                }
            }
        }

        return true;
    }

    protected function isDate($dateString)
    {
        return strtotime(date('Y-m-d', strtotime($dateString))) === strtotime($dateString);
    }

    protected function setStatusName(&$data)
    {
        $config = $this->service->getStatusConfig();
        if (isset($data[0])) {
            foreach ($data as $key => &$value) {

                foreach ($value as $k => $v) {
                    if (array_key_exists($k, $config)) {
                        $value[$k . '_name'] = key_exists($v, $config[$k]) ? $config[$k][$v] : $this->translate['nonow'];
                    }
                }
            }
        } else {
            foreach ($data as $k => &$v) {
                if (array_key_exists($k, $config)) {
                    $data[$k . '_name'] = array_key_exists($v, $config[$k]) ? $config[$k][$v] : $this->translate['nonow'];
                }
            }
        }

        return true;
    }

    protected function setHide(&$data)
    {
        if ($this->hideKeys) {

            if (isset($data[0])) {
                foreach ($data as &$value) {

                    foreach ($value as $k => $v) {
                        if (in_array($k, $this->hideKeys)) {
                            unset($value[$k]);
                        }
                    }

                }
            } else {

                foreach ($data as $k => $v) {
                    if (in_array($k, $this->hideKeys)) {
                        unset($data[$k]);
                    }
                }

            }

        }
    }

    protected function setShow(&$data)
    {
        if ($this->showKeys) {

            if (isset($data[0])) {
                foreach ($data as &$value) {

                    foreach ($value as $k => $v) {
                        if (!in_array($k, $this->showKeys)) {
                            unset($value[$k]);
                        }
                    }

                }
            } else {

                foreach ($data as $k => $v) {
                    if (!in_array($k, $this->showKeys)) {
                        unset($data[$k]);
                    }
                }

            }

        }
    }

    protected function autoTimeToDate(&$data)
    {
        if ($this->timeToDateKeys) {
            $this->setTimeToDate($data, $this->timeToDateKeys);
        }

        return true;
    }

    protected function setTimeToDate(&$data, $keys)
    {
        if (isset($data[0])) {
            foreach ($data as &$value) {

                foreach ($value as $k => $v) {
                    if (in_array($k, $keys)) {
                        $value[$k . '_date'] = $v > 0 ? date('Y-m-d H:i:s', $v) : $this->translate['no_time'];
                    }
                }

            }
        } else {

            foreach ($data as $k => &$v) {
                if (in_array($k, $keys)) {
                    $data[$k . '_date'] = $v > 0 ? date('Y-m-d H:i:s', $v) : $this->translate['no_time'];
                }
            }
        }

        return true;
    }



    protected function beforeView()
    {
        return true;
    }

    protected function afterView(&$data)
    {
        return true;
    }



    protected function beforeUpdate(&$data)
    {
        return true;
    }

    protected function afterUpdate(&$data)
    {
        return true;
    }

    protected function setUpdateVlaue()
    {
        $update = [];
        $config = $this->service->getStatusConfig();
        if (!empty($this->updateKeys)) {
            foreach ($this->updateKeys as $v) {
                if ($this->checkPostValueInit($v)) {
                    $value = $this->getValue($v);
                    if (array_key_exists($v, $config)) {
                        if (array_key_exists($value, $config[$v])) {
                            $update[$v] = $value;
                        }
                    } else {
                        $update[$v] = $value;
                    }
                }
            }
        }

        return $update;
    }

    protected function setUpdateVlaue2( $data )
    {
        unset($data['type_name']);
        unset($data['status_name']);
        unset($data['uptime_date']);
        unset($data['addtime_date']);
        // foreach ($data as $key => &$value) {
         
        // }

        return $data;
    }


    protected function afterRemove(&$data)
    {
        return true;
    }

    protected function beforeRemove()
    {
        return true;
    }


    protected function afterCreate(&$data)
    {
        return true;
    }

    protected function beforeCreate(&$data)
    {
        return true;
    }

    protected function setCreateData()
    {
        $data = [];
        $config = $this->service->getStatusConfig();

        if (!empty($this->createKeys)) {
            foreach ($this->createKeys as $v) {
                if ($this->checkPostValueInit($v)) {
                    $value = $this->getValue($v);
                    if (array_key_exists($v, $config)) {
                        if (array_key_exists($value, $config[$v])) {
                            $data[$v] = $value;
                        }
                    } else {
                        $data[$v] = $value;
                    }
                }
            }
        }

        return $data;
    }

    protected function checkPostValueInit($key)
    {
        return $this->checkValueInit($key, $_GET) || $this->checkValueInit($key, $_POST);
    }

    protected function checkValueInit($key, $array = [])
    {
        if (isset($array[$key])) {
            return true;
        }

        return false;
    }
}
