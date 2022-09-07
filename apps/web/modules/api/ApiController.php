<?php

namespace Api;

use C\L\WebController;
use C\P\ImageCode\ImageCode;
use C\P\QRcode\QRcode;

class Tool
{
    //XXX替换对应的国家对应的地址
    //const HOST_URL = 'https://api-XXX.onepay.news'; //网关地址切换正式环境不需要替换
    const HOST_URL = 'https://api-ngn.onepay.news'; //网关地址切换正式环境不需要替换
    const PAY_CODE = 'N8012';    //订单中的支付编码

    public $method = 'AES-128-CBC'; //AES加密定义不要更改
        
    //以下3个参数需要开启正式商户号后替换.
    public $password = 'wMK3b9py65eB14O5'; //AES密钥
    public $authorizationKey = 'oaH5Xf6w96';  //请求头中的商户Key
    //
    //推送入款单
    static public $oderReceive = self::HOST_URL . '/api/v1/order/receive';
    //推送出款单
    static public $oderOut = self::HOST_URL . '/api/v1/order/out';
    //订单查询
    static public $oderQuery = self::HOST_URL . '/api/v1/order/query';
    //商户余额查询
    static public $balanceQuery = self::HOST_URL . '/api/v1/merchant/balance';
    //自助回调
    static public $orderNotify = self::HOST_URL .'/api/v1/test/orderNotify';


    /**加密
     * @param array $data
     * @return string
     */
    public function encryptionAes(array $data)
    {
//        $jsonData = json_encode($data,true);
        //修改
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE );
        $aesSecret = bin2hex(openssl_encrypt($jsonData, $this->method,$this->password,  OPENSSL_RAW_DATA, $this->password));
        return $aesSecret;
    }

    /**解密
     * @param $aesSecret
     * @return false|string
     */
    public function decryptAes($aesSecret)
    {
        $str="";
        for($i=0;$i<strlen($aesSecret)-1;$i+=2){
            $str.=chr(hexdec($aesSecret[$i].$aesSecret[$i+1]));
        }
        $jsonData =  openssl_decrypt($str,$this->method,$this->password, OPENSSL_RAW_DATA,$this->password);
        $data = json_decode($jsonData,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        return $data;
    }


    /**获取随机数
     * @param int $length 随机数长度
     * @return string 返回随机数
     */
    public function GetRandStr($length = 8)
    {
        $str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len=strlen($str)-1;
        $randStr='';
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randStr .= $str[$num];
        }
        return $randStr;
    }

    /**post请求
     * @param string $url
     * @param array $data
     * @return false|string
     */
    public function curlPost($url = '', $data=null)
    {
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//访问的URL
        curl_setopt($ch, CURLOPT_POST, true);//请求方式为post请求
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
        $header = [
            'Content-type: application/json;charset=UTF-8',
            'Authorization: '. $this->authorizationKey,
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));//请求数据
        $result = curl_exec($ch);//执行请求
        curl_close($ch);//关闭curl，释放资源
        return $result;
    }

    /**get请求
     * @param string $url
     * @param array $data
     * @return false|string
     */
    public function curlGet($url = '', $data = array())
    {
        if(!empty($data)) $url = $url .'?'. http_build_query($data);
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//访问的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
        $result = curl_exec($ch);//执行请求
        curl_close($ch);//关闭curl，释放资源
        return $result;
    }

    /**post加密数据并提交请求
     * @param string $url 请求地址
     * @param array $data   请求数据
     * @return false|string     返回数据（json字符串）
     */
    public function postRes($url, array $data)
    {
        $info['data'] = $this->encryptionAes($data);
        $res = $this->curlPost($url, $info);
        return $res;
    }

    /**解密并解析数据为数组；
     * @param string $data 示例:{"data":"50C7CC8B58CEFFD1A824ADE524F4F55DB0DAEE6029ADBB597C1F99D28E1D0779C33B562526F05C6821932DE20B6893ADD6834D3397B7A8E08CC03995A5CDEA7E6B4DF0485466D4C25AEB223DD456DBC0321921FDCA18F9596A1C14B54C5A018CC7C0B922E3DE371626887DA78E539DA81E64EC41938BC3EC5BEBC26A948803E8","merchantNo":"2022011116544301686"}
     * @return false|array
     */
    public function parseData( $data )
    {
        $info = json_decode($data,true );
        $val = $this->decryptAes($info['data']);
        return $val;
    }
}

class ApiController extends WebController
{
    protected function init()
    {
        $this->language_fields = [
            'name'
        ];
    }
    public function qrcodeAction()
    {
        $uid = $this->getValue('uid', true, 'int');
        $user = $this->s_user->search($uid);
        $md5 = @md5_file('/home/www/wwwroot/station_admin/www/web/h5/index.html');
        $url = "/index.html?v=" . substr($md5, 0, 6) . "&m={$user['mobile']}#/register";
        ob_clean();
        QRcode::png('https://www.cxfvqor.com' . $url, 8, 0);
    }
    
    public function xcAction()
    {
        $data = $this->s_config->get('xc');
        if ($data['is_open'] == 'Y') {
            $this->success([
                'content' => $data['content']
            ]);
        }
        $this->error();
    }

    public function imageCodeAction()
    {
        $code = new ImageCode();
        $code->initialize([
            'width' => 100,     // 宽度
            'height' => 40,     // 高度
            'line' => true,     // 直线
            'curve' => false,   // 曲线
            'noise' => 1,   // 噪点背景
            'fonts' => []       // 字体
        ]);

        $base64Image = $code->create()->toBase64($this->ssidKey);
        $this->ssid->set('image_code', $code->getText());
        $this->success([
            'code' => $base64Image,
        ]);
    }

    //首页
    public function indexAction()
    {
        $config = $this->s_config->get('web');
        $items = $this->s_item->searchAll([
            'is_show_index' => 'Y',
            'status' => 'Y',
            //'schedule' => 100,
            'begin_time' => time()
        ], ['schedule' => '<', 'begin_time' => '<='], [], 'sort desc');


        foreach ($items as $key=>$item) {
            if((int)$item['schedule']>=100){
                unset($items[$key]);
                continue;
            }
            #$items[$key]['money'] = $item['stock'];    
            $items[$key]['apr_money'] = bcmul($item['min_money'], $item['apr'] / 100, 2);
            $config_buy_count = $item['stock']-$item['rem_count'];
            $curren_buy_count = $this->s_il->getCount(['cid'=>$item['id']]);
            $items[$key]['rem_count'] =  $item['rem_count'] >0 ? $item['rem_count']-$curren_buy_count:0;
            $items[$key]['buy_count'] = $curren_buy_count+$config_buy_count>$item['stock']?$item['stock']:$curren_buy_count+$config_buy_count;
            $items[$key]['schedule'] = isset($item['stock']) && $item['stock'] >0?100*bcdiv($items[$key]['buy_count'],$item[ 'stock'],4):'10';  
            $items[$key]['type_name'] = $this->s_item->getStatusConfig()['type'][$item['type']];
            // 配置需要转换成站点语言的字段配置
            if($this->language != 'cn'){
                foreach($this->language_fields as $v){
                    $items[$key]['name'] = $item[$v.'_'.$this->language];
                }
            }
            //$items[$key]['show_min_money'] =  number_format( $items[$key]['min_money']);
            //$items[$key]['show_apr_money'] =  number_format( $items[$key]['apr_money']);
            $items[$key]['show_min_money'] =  number_format($items[$key]['min_money'],2,".",",");
            $items[$key]['show_apr_money'] =  number_format($items[$key]['apr_money'],2,".",",");

            $items[$key]['rem_count'] =  $item['rem_count'] >0 ? $item['rem_count']:0;
            if ( $items[$key]['stock'] ==$items[$key]['buy_count']  ){
                $items[$key]['rem_count'] =0;
            }
        }
        $data = [
            'items' => $items,
            'notice' => $config['notice'],
            'kefu_tel' => $config['kefu_tel'],
            'ipcc_no' => $config['ipcc_no'],
            'is_login' => $this->uid > 0 ? true : false,
            'version' => $config['version'],
            'logo' => $config['logo'],
            'ad' => $this->s_config->get('ad'),
            //'footer' => $this->ssid->get('footer'),
           'footer'=>'n2',
         'app' => array_merge($this->s_config->get('app'), ['app_link' => $config['app_link']])
        ];




        $this->success($data);
    }

    private function getStepInfo($step)
    {
        $todayKm = bcmul(0.61 / 1000, $step, 2);
        $data = [
            'today_step' => intval($step),
            'today_km' => floatval($todayKm),
            'today_kll' => round(60 * $todayKm * 1.036, 2),
        ];

        return $data;
    }


    public function syncAction()
    {
        $devNo = $this->getValue('dev_no', false, 'string');
        $devType = $this->getValue('dev_type', false, 'string');
        $dev_oaid = $this->getValue('dev_oaid', false, 'string');
        $step = $this->getValue('step', false, 'int') ?? 0;
        if($this->uid > 0 ){
            $user = $this->s_user->search(['uid'=>$this->uid]);
            #防止脚本大量无效数据,只取当日未认证渠道的用户
            if($user['channel']=='domain' && date("Ymd",$user['addtime'])==date("Ymd") && (!empty($devNo) || !empty($dev_oaid))){
                if(!empty($devNo) && $this->ssid->get('dev_no') != $devNo){
                    $this->s_user->update($this->uid, ['dev_no' => $devNo, 'dev_type' => $devType]);
                }
                if(!empty($dev_oaid) && $this->ssid->get('dev_oaid') != $dev_oaid){
                    $this->s_user->update($this->uid, ['dev_oaid' => $dev_oaid, 'dev_type' => $devType]);
                }
                $flowArray = [
                    'dev_type' => $devType,
                    'dev_oaid'=>$dev_oaid,
                    'dev_no' => md5($devNo),
                    'uid' => $this->uid,
                ];
                $this->cache->rPush('flow_list', json_encode($flowArray));
            }    
            //$data['check_dev_no'] = true;
        }
        if ($this->uid <= 0 && (!empty($devNo) || !empty($dev_oaid))) {
            if ($devType == 'ios') {
                $where = ['idfa' => $devNo, 'is_open_index' => 0];
                $flowArr = $this->s_flowks->search($where);
            } else {
                $where = [ 'is_open_index' => 0, 'imei' => $devNo, 'oaid' => $dev_oaid];
                $flowArr = $this->s_flowks->search($where, ['imei' => '=|', 'oaid' => '|=']);
            }
            if (!empty($flowArr)) {
                $flowArray = [
                    'dev_type' => $devType,
                    'dev_oaid' => $dev_oaid,
                    'dev_no' => md5($devNo),
                    'uid' => 0,
                ];
                $this->cache->rPush('flow_list', json_encode($flowArray));
            }
        }
        $stepKey = 'step_' . date('Ymd');
        $this->ssid->set($stepKey, $step);
        $this->ssid->set('dev_oaid', $dev_oaid);
        $this->ssid->set('dev_no', $devNo);
        $this->ssid->set('dev_type', $devType);
        $data = [
            'check_dev_no' => false,
            //'footer' => $this->ssid->get('footer')
            'footer'=>'n2'  
    ];
        $data['is_open_notice_dialog'] = $this->s_notice->syncNotice($this->uid);
        $data['step'] = $this->getStepInfo($step);
        // 将步数同步到表
        if ($step > 0) {
            $this->s_packlist->updates([
                'status' => 'D',
                'ok_step' => $step,
                'ok_time' => time(),
                'uptime' => time()
            ], ['uid' => $this->uid, 'no_date' => date('Ymd')]);
        }
        // 将步数入redis缓存
        // $this->setStepToRedis($this->uid, $step);
        // 
   
        $this->success($data);
    }

    /**
     * 将步数入redis缓存
     */
    public function setStepToRedis($uid, $step)
    {
        if ($step <= 0) {
            return false;
        }
        $date = date('Ymd');
        $cache_key = "sync:step:{$date}";
        $second = 86400 + 3600;
        $this->cache->zAdd($cache_key, $uid, $step);
        $this->cache->expire($cache_key, $second);
    }

    //登录
    public function loginAction()
    {
        $username = $this->getValue('username', true);
        $password = $this->getValue('password', true);

        if ($this->s_user->login($username, $password)) {
            $this->s_item->promotionFooter($username);
            $this->success(['footer' => $this->ssid->get('footer')]);
        }

        $this->error();
    }

    public function logoutAction()
    {
        $this->ssid->destory();
        $this->success();
    }

    //注册
    public function registerAction()
    {

        // echo "注册接口请求";exit;
        // 渠道注册
        $place_id = $this->getValue('place_id');

        $username = $this->getValue('mobile', true);
        // $username = $this->getValue('email', true);
        $password = $this->getValue('password', true);
        $code = $this->getValue('code', true);
        $tMobile = $this->getValue('t_mobile', false, 'string');
        $devNo = $this->ssid->get('dev_no');
        $dev_oaid = $this->ssid->get('dev_oaid');
        $devType = $this->ssid->get('dev_type');
        $channel = $this->ssid->get('channel');

        if ($this->s_user->register($username, $password, $code, $tMobile, $devNo, $devType, $channel,$dev_oaid,$username,$place_id)) 
        {
            $config = $this->s_config->get('web');
            $data = [
                'app_link' => $config['app_link'],
            ];
            $promotion_host1 = @(array)$this->di['config']->get('config')->promotion_host1;
            if (!empty($promotion_host1) && in_array($_SERVER['HTTP_HOST'] ?? 'nil', $promotion_host1)) {
                $this->di['cache']->set('promotion:'.$username, $_SERVER['HTTP_HOST'] ?? 'nil', (3600 * 24 * 365));
                $this->di['s_user']->updates(['channel' => 'wxpop1'], ['mobile' => $username]);
            }
            $promotion_host2 = @(array)$this->di['config']->get('config')->promotion_host2;
            if (!empty($promotion_host2) && in_array($_SERVER['HTTP_HOST'] ?? 'nil', $promotion_host2)) {
                $this->di['cache']->set('promotion:'.$username, $_SERVER['HTTP_HOST'] ?? 'nil', (3600 * 24 * 365));
                $this->di['s_user']->updates(['channel' => 'wxpop2'], ['mobile' => $username]);
            }
            $promotion_host3 = @(array)$this->di['config']->get('config')->promotion_host3;
            if (!empty($promotion_host3) && in_array($_SERVER['HTTP_HOST'] ?? 'nil', $promotion_host3)) {
                $this->di['cache']->set('promotion:'.$username, $_SERVER['HTTP_HOST'] ?? 'nil', (3600 * 24 * 365));
                $this->di['s_user']->updates(['channel' => 'kuaishou1'], ['mobile' => $username]);
            }
            $data['promotion_host'] = array_merge($promotion_host1, $promotion_host2, $promotion_host3);
            $this->success($data);
        }
        $this->error();
    }

    //找回密码
    public function forgetpwdAction()
    {
        $username = $this->getValue('username', true);
        $password = $this->getValue('password', true);
        $code = $this->getValue('code', true);

        if ($this->s_user->forgetpwd($username, $password, $code)) {
            $this->success();
        }

        $this->error();
    }

    //发送验证码
    public function codeAction()
    {
            // echo  "发送验证码？";exit;
        $mobile = $this->getValue('mobile', true, 'string');
        $type = $this->getValue('type', true);

        if ($type == 'register' || $type == 'forgetpwd') {
            // $imageCode = $this->getValue('image_code', true, 'string');
            // if ($this->ssid->get('image_code') != strtolower($imageCode)) {
            //     $this->error($this->translate['img_code_error']);
            // }
            // $this->ssid->set('image_code', '');
            if ($code = $this->s_code->send($mobile, $type)) {
                $this->success();
            }else{
                $this->error();  
            }
        }else{
            $this->success();
        }
        // if ($this->s_code->send($mobile, $type)) {
        //     $this->success();
        // }

        // $this->error();
    }
    //发送验证码
    public function emailcodeAction()
    {
        //用户手机号
        $mobile = $this->getValue('mobile', true, 'string');
        //操作类型
        $type = $this->getValue('type', true);
        // $mobile = $this->getValue('mobile', true, 'string');
        //输入的email 和 type数据
        $email = $this->getValue('email', true, 'string');
        $type = $this->getValue('type', true);
        if ($type == 'register' || $type == 'forgetpwd') {
            $imageCode = $this->getValue('image_code', true, 'string');
            // echo "图形验证码".$imageCode;exit();
            if ($this->ssid->get('image_code') != strtolower($imageCode)) {
                $this->error($this->translate['img_code_error']);
            }
            $this->ssid->set('image_code', '');
        }

        if ($this->s_code->sendEmail($email, $type)) {
            $this->success();
        }

        $this->error();
    }
    
    
    //手机找回密码接口
    public function phonecodeAction(){
        //用户手机号
        $mobile = $this->getValue('mobile', true, 'string');
        //操作类型
        $type = $this->getValue('type', true);
        //验证码
        // $code = $this->getValue('code', true);
        //用户密码
        // $pwd = $this->getValue('pwd',true);
        
        echo $mobile."<br>".$type;
        
        exit();
        // // if ($type == 'register' || $type == 'forgetpwd') {
            
        // // }
        
        // echo "111111111111111111111111111111";
        
        //     echo "我的操作类型：".$type;exit;
        // if ($this->s_code->sendEmail($mobile, $type)) {
        //     $this->success();
        // }
        // exit();
        $this->error();
    }
    
    //获取配置文件
    public function configAction()
    {

//        $att = ['CDEF89AB45670123', 'CDEF89AB45670123', 'BA98FEDC32107654', 'EFCDAB8967452301'];
//
//        for ($i = 1; $i < 10001; $i++) {
//
//            $att1 = ['C', 'C', 'B', 'E'];
//            $z = sprintf('%04s', dechex($i));
//
//            for ($x = 0; $x < strlen($z); $x++) {
//                //var_dump(hexdec(substr($z, $x, 1)));
//                $att1[$x] = substr($att[$x], hexdec(substr($z, $x, 1)), 1);
//            }
//
//
//            echo $i . '：' . $att1[2] . $att1[3] . $att1[0] . $att1[1] . '3B8E' . '</br>';
//        }
//
//
//        die;


        $data = [
            'ssid' => $this->ssid->register(),
            'ssid_expire_time' => $this->ssid->get('expire_time'),
            //'error' => $this->config->get('error')->toArray(),
            //'aes_key' => $this->ssid->get('aes_key'),
        ];
        $this->success($data);
    }

    // public function webconfigAction()
    // {
    //     $type = $this->getValue('type', true);
    //     $config = $this->s_config->get($type);
    //     if (empty($config)) {
    //         $this->error('未找到相关配置');
    //     }

    //     if ($type == 'web') {
    //         unset($config['auth_key'], $config['sms_key']);
    //     }

    //     $this->success($config);
    // }

    public function webconfigAction()
    {
        $type = $this->getValue('type', true);
        if ($type == 'banner' || $type == 'links') {
            $res = [];
            $banner = $this->s_image->searchAll([
                'type' => $type,
                'status' => 'Y'
            ], [], ['name', 'url', 'thumb', 'sort']);
            $res['banner'] = $banner;
            $this->success($res);
        }
        $config = $this->s_config->get($type);
        if ($type == 'web') {
            unset($config['auth_key'], $config['sms_key']);
        }
        $this->success($config);
    }

    public function imageAction()
    {
        $type = $this->getValue('type', true, 'string');
        $images = $this->s_image->searchAll(['type' => $type, 'status' => 'Y'], [], [], 'sort desc');

        foreach ($images as &$item) {
            if (!strstr($item['url'], 'http')) {
                $item['url'] = '#' . $item['url'];
            }
        }

        $this->success($images);
    }

    public function treeAction()
    {
        $this->error($this->translate['access_error']);
        $background = 'forest_wrap_day';
        if (date('H') > 17 || date('H') < 6) {
            $background = 'forest_wrap_night';
        }
        $data = [
            'fruit' => 0,
            'water' => 0,
            'manure' => 0,
            'type' => 'tree0',
            'background' => $background,
            'tree_rule' => []
        ];

        if ($this->uid) {

            $config = $this->s_config->get('tree');

            $searchTime = strtotime(date('Ymd'));

            $addWater = 0;
            if ($config['login_water'] > 0) {

                // if (!$this->s_funds->search(['uid' => $this->uid, 'type' => 'water', 'stype' => 'login_water', 'addtime' => $searchTime], ['addtime' => '>='])) {
                if ($this->dailyGivingLock('login_water')) {
                    $this->di['s_funds']->add($this->uid, $config['login_water'], 'water', 'login_water', "每日赠送", $this->uid);
                    $addWater = $config['login_water'];
                }

            }

            $addManure = 0;
            if ($config['login_manure'] > 0) {

                // if (!$this->s_funds->search(['uid' => $this->uid, 'type' => 'manure', 'stype' => 'login_manure', 'addtime' => $searchTime], ['addtime' => '>='])) {
                if ($this->dailyGivingLock('login_manure')) {
                    $this->di['s_funds']->add($this->uid, $config['login_manure'], 'manure', 'login_manure', "每日赠送", $this->uid);
                    $addManure = $config['login_manure'];
                }

            }

            $tree = $this->s_tree->search(['uid' => $this->uid, 'status' => ['S', 'D']], ['status' => 'in']);
            if (empty($tree)) {
                $tree = [
                    'value' => 0,
                ];
                $this->s_tree->save([
                    'uid' => $this->uid,
                ]);
            }

            $user = $this->s_user->search($this->uid);
            $data = [
                'fruit' => $user['fruit'],
                'water' => $user['water'] + $addWater,
                'manure' => $user['manure'] + $addManure,
                'type' => $this->s_tree->getTreeType($tree['value']),
                'background' => $background,
                'tree_rule' => json_decode($config['tree_rule']),
                // 'tree_progress' => $this->s_tree->countTreeProgress($config['tree'], $tree),
                'notice' => isset($config['notice']) ? $config['notice'] : '',
                'adv_icon' => $this->s_tree->getAdvicon($this->uid),
            ];
        }

        $this->success($data);

    }

    public function dailyGivingLock($type)
    {
        $redisKey = sprintf('daily:giving:%s:%s:%s', $type, date('Ymd'), $this->uid);
        $isGiving = $this->di['cache']->get($redisKey);
        if ($isGiving) {
            return false;
        }
        return $this->di['cache']->setex($redisKey, 1, (3600 * 24));
    }

    public function uploadAction()
    {

        if (!$this->request->hasFiles()) {
            $this->error($this->translate['upload_empty']);
        }

        $files = $this->request->getUploadedFiles();
        $list = [];
        foreach ($files as $file) {

            /*二次开发
             * 2022-05-11
             */
            $file_t = fopen($file->getTempName(), "rb");
            $bin = fread($file_t, 2); //只读2字节
            fclose($file_t);
            $strInfo = @unpack("C2chars", $bin);
            $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
            $fileType = '';
            switch ($typeCode) {
                case 7790: $fileType = 'exe'; break;
                case 7784: $fileType = 'midi'; break;
                case 8297: $fileType = 'rar'; break;
                case 8273: $fileType = 'webp'; break;
                case 255216: $fileType = 'jpg'; break;
                case 7173: $fileType = 'gif'; break;
                case 6677: $fileType = 'bmp'; break;
                case 13780: $fileType = 'png'; break;
                default: $fileType='unknown';break;
            }
            if (!in_array($fileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                continue;
            }
            
            $extName = $file->getExtension();
            if (!in_array($extName, ['jpg', 'png', 'jpeg', 'gif'])) {
                continue;
            }

            $path = '/upload/' . date('Y/m/d');
            $filePath = APP_PUBLIC . $path;
            $fileNme = date('YmdHis') . '_' . substr(md5($file->getName() . rand(10, 99)), 0, 8) . '.' . $extName;
            if (!file_exists($filePath)) {
                //echo $filePath;exit;
                if (!@mkdir($filePath, 0755, true)) {
                    $this->error($this->translate['sys_file_error']);
                }
            }
            $file->moveTo(
                $filePath . '/' . $fileNme
            );

            $list[$file->getKey()] = $path . '/' . $fileNme;
        }

        $this->success($list);
    }

    // 推送入款单 通知
    public function onepayPaymentNoticeAction(){
        $json_data = file_get_contents("php://input");   //获取post数据
        // $json_data = '{"data":"E5D5B24E62D8C817D9F63F14706C49C4E67950210474F86E3AD9BA5F327D802F404CCF611031FFAE836AA941F32DDC848D201342C80C03CD5AD7766CB535E6721BE23F315C67E7061CEF087AA1ECE4EC62F1ADFC0CB09D2F46724AA48C3EE45314BBF82E41E4DEB5640FFCAC3FC6DA79C985EC9C393CBF981C81759BD7AB6CFA","merchantNo":"1657119486498156"}';
    
        //打印日志
        $file = "/www/wwwroot/www.cxfvqor.com/public/logs/notice_onepay_payment_notice" . date("Ymd") . ".log";

        $ct = date("Y-m-d H:i:s", time());
        error_log( $ct . "收到的回调数据" . var_export($json_data, true) . " \r\n", 3, $file);        
  
        $tool = new Tool();
        $res = $tool->parseData($json_data);  //解析数据结果为数组。
        
        // $ct = date("Y-m-d H:i:s", time());
        // error_log("收到的回调数据" . var_export($res, true) . " \r\n", 3, $file);    
    
        if ( $res['status'] == '2'  && $res['orderType'] == "1"  ){
            $order = $this->s_pay_order->search( ['orderNo'=>$res['channelNo'] ]);
            if ($order['state']!=1){
                $user = $this->s_user->search($order['uid']);
                $this->s_pay_order->update( $order['id'],['state'=>1] );
                $add = [
                    'uid' => $order['uid'],
                    'channel' => 'onepay',
                    'money' => $res['amount']/100,
                    'name' => $user['name'],
                    'remark' => '',
                ];
                $this->s_invest->save($add);
                echo 'success';exit();
            }
        }
        echo 'failed';exit();
    }

    // 推送入款单
    public function onepayPaymentAction(){
        $money = $this->getValue('money', true);
        $invest_min_money= $this->getValue('invest_min_money', true);
        $payname= $this->getValue('payname', true);

        if (!$this->uid ||$this->uid<=0 ){exit();}
        if ( $money<=0 || $invest_min_money > $money  ){exit();}
        $user = $this->s_user->search($this->uid);
        $merchantNo = "oaH5Xf6w96";
        $merchantOrderNo = $this->uid . '-'. time() .mt_rand(100000, 999999) ;
        $notifyUrl = "https://apifront.cxfvqor.com/api/api/onepaypaymentnotice";
        $pageUrl = "https://www.cxfvqor.com/#/user";
        ###########推送入款单
        $postdata['orderNo'] = $merchantOrderNo;
        $postdata['payCode'] = Tool::PAY_CODE;
        $postdata['amount'] = $money*100; //金额是到分,平台金额是元需要除100
        $postdata['notifyUrl'] = $notifyUrl;
        $postdata['returnUrl'] = $pageUrl;
        $out['payeeType'] = '1';
        //以下参数自行修改
        $postdata['payerName'] = $user['name'];
        $tool = new Tool();
        $res = $tool->postRes(Tool::$oderReceive, $postdata);
        $res = json_decode($res,true);
        $data = $res;
 
        if ($data['code'] == 200 ){
            $insertdata['payname'] = $payname;
            $insertdata['uid'] =$this->uid;
            $insertdata['paymentAmount'] = $money;
            $insertdata['paymentType'] = 'save_money';
            $insertdata['orderNo'] = $data['data']['channelNo'];
            $insertdata['merchantOrderNo'] = $merchantOrderNo;
            $insertdata['merchantNo'] = $merchantNo;
            $this->s_pay_order->save($insertdata);
        }
        $res['code'] = intval($res['code']);
        $res['data']['pageurl'] = $pageUrl;
        echo json_encode($res);exit();        
    } 
    
    // 出款单
    public function onepayTransferAction(){
        if (!$this->uid ||$this->uid<=0 ){exit();}
        $user = $this->s_user->search($this->uid);
            
        $passwd = $this->getValue('passwd', true);
        if(!$this->di['s_user']->checkPayPwd($this->uid, $passwd)){
            throw new \Exception($this->translate['cost_pw_err']);
        }
        
  

        $money = $this->getValue('money', true);
        if ($money >$user['money']){ exit(); }
        $cost_min_money= 200;
        $payname= $this->getValue('payname', true);
        
        $web = $this->di['s_config']->get('pay');
        if ($web['cost_min_money'] > 0 && $web['cost_min_money'] > $money) {
         throw new \Exception($this->translate['new_lang_min_get_cash'] . $web['cost_min_money']);
        }
 

        $merchantNo = "oaH5Xf6w96";
        $merchantOrderNo = $this->uid . '-'. time() .mt_rand(100000, 999999) ;
        $notifyUrl = "https://apifront.cxfvqor.com/api/api/onepaytransfernotice";
        $pageUrl = "https://www.cxfvqor.com/#/user";
        ###########推送入款单
        $postdata['orderNo'] = $merchantOrderNo;
        $postdata['payCode'] = Tool::PAY_CODE;
        $postdata['amount'] = $money*100; //金额是到分,平台金额是元需要除100
        $postdata['notifyUrl'] = $notifyUrl;
        $postdata['returnUrl'] = $pageUrl;
        $out['payeeType'] = '0';
        //以下参数自行修改
        $postdata['payerName'] = $user['name'];
        $tool = new Tool();
        $res = $tool->postRes(Tool::$oderReceive, $postdata);
        $res = json_decode($res,true);
        $data = $res;
        if ($data['code'] == 200 ){
            $insertdata['payname'] = $payname;
            $insertdata['uid'] =$this->uid;
            $insertdata['paymentAmount'] = $money;
            $insertdata['paymentType'] = 'save_money';
            $insertdata['orderNo'] = $data['data']['channelNo'];
            $insertdata['merchantOrderNo'] = $merchantOrderNo;
            $insertdata['merchantNo'] = $merchantNo;
            $this->s_pay_order->save($insertdata);
        }
        $res['code'] = intval($res['code']);
        $res['data']['pageurl'] = $pageUrl;
        echo json_encode($res);exit();        
    } 
    
    // 出款 Notice
    public function onepaytransferNoticeAction(){
        $json_data = file_get_contents("php://input");   //获取post数据
        $tool = new Tool();
        $res = $tool->parseData($json_data);  //解析数据结果为数组。

        
        //打印日志
        $file = "/www/wwwroot/www.cxfvqor.com/public/logs/notice_onepay_transfer_notice" . date("Ymd") . ".log";
        $ct = date("Y-m-d H:i:s", time());
        error_log("收到的回调数据" . var_export($json_data, true) . " \r\n", 3, $file);
        
  
            if ( $res['status'] == '2'  ){
                $order = $this->s_pay_order->search( ['orderNo'=>$res['channelNo'] ]);
                $user = $this->s_user->search($order['uid']);
                if ($order['state']!=1){
                    $this->di['db']->begin();
                    $this->s_pay_order->update( $order['id'],['state'=>1,'fee'=>$res['fee']/100 ] );
                    $result = $this->s_cost->cash2($order['uid'],$res['amount']/100, $order['orderNo'], 0, 'onepaybank', 'onepaybank' );
                    $this->di['db']->commit();
                echo 'success';exit();
                 }
             }
                echo 'failed';exit();
    }
}


