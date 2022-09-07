<?php

namespace Api;

use C\L\Controller;

class GdtController extends Controller
{
    /**
     *  1.2.2 定期刷新access_token
        获得 access_token 后，可以调用Marketing API进行转化数据上报。
        一旦 access_token 失效，将无法调用接口。access_token 和 refresh_token 的有效期可以通过 oauth/token 接口的返回字段获取，默认情况下 access_token 和 refresh_token 的有效期如下：
        access_token：默认有效期为24小时
        refresh_token：默认有效期为30天
        注：每次刷新access_token时， refresh_token会自动续期。
     */
    private $scope = 'user_actions';
    private $client_id = '';
    private $secret_key = '';
    private $log_file = '';
    private $auth_code_call_url = 'https://www.yiyebook.com/api/gdt/getAuthCode';
    private $auth_code_cache_key = 'gdt:auth:code';
    private $auth_code_expire = 300; // 5分钟
    private $access_token_cache_key = 'gdt:access:token:%s';
    private $refresh_token_cache_key = 'gdt:refresh:token:%s';
    private $access_token_call_url = '';
    private $gdt_report_url = 'https://api.e.qq.com/v1.1/user_actions/add?access_token=';

    public function init()
    {
        $this->log_file = $this->log->set('flow_gdt_' . date('Ymd') . '.log');
        $gdt_config = $this->di['config']->get('config')->gdt_config;
	    $this->client_id = $gdt_config->client_id;
        $this->secret_key = $gdt_config->secret_key;
    }

    public function gdtAction()
    {
        $params = $this->request->get();

        $log = $this->log->set('flow_gdt_' . date('Ymd') . '.log');
        $log->notice(json_encode(['get' => $params, 'post' => $this->request->getPost()]));

        $muid = $this->getValue('muid');
        if (empty($muid)) {
            $this->error($this->translate['no_empty']);
        }

        $device_os_type = $this->getValue('device_os_type');
        $os = 'android';
        $imei = '';
        $idfa = '';
        if ($device_os_type == 'ios') {
            $os = 'ios';
            $idfa = $muid;
        } else {
            $imei = $muid;
        }

        $oaid = $this->getValue('oaid');
        $add = [
            'aid' => $this->getValue('adgroup_id'),
            'oaid' => $oaid,
            'cid' => $this->getValue('creative_id'),
            'did' => $this->getValue('campaign_id'),
            //            'unit' => $this->getValue('unit'),
            //            'plan' => $this->getValue('plan'),
            'dname' => $this->getValue('adgroup_name'),
            'imei' => $imei,
            'idfa' => $idfa,
            'call_url' => $this->gdt_report_url,
            'ip' => $this->getValue('ip'),
            'time' => substr($this->getValue('click_time'), 0, 10),
            'os' => $os,
            'type' => 'gdt',
        ];

        $ret = [];
        $ret['ret'] = 1;
        $ret['msg'] = 'failure';
        if ($imei) {
            $imei_isset = $this->s_flowks->search(['imei' => $imei]);
            if ($imei_isset) {
                $ret['msg'] = 'imei isset';
                echo json_encode($ret);
                exit();
            }
        }
        if ($idfa) {
            $idfa_isset = $this->s_flowks->search(['idfa' => $idfa]);
            if ($idfa_isset) {
                $ret['msg'] = 'idfa isset';
                echo json_encode($ret);
                exit();
            }
        }
        if ($this->s_flowks->save($add)) {
            $ret['ret'] = 0;
            $ret['msg'] = 'success';
            $flowArray = [
                'dev_type' => $os,
                'dev_no' => empty($idfa) ? $imei : $idfa,
                'dev_oaid' => $oaid,
                'uid' => 0,
            ];
            // $this->cache->rPush('flow_list', json_encode($flowArray));
            // $this->success('请求成功');
        }

        echo json_encode($ret);
        exit();
        // $this->error('请求失败');
    }

    public function getToken()
    {
        $cache_key = sprintf($this->access_token_cache_key, $this->client_id);
        $token = $this->di['cache']->get($cache_key);
        if (empty($token)) {
            $token = $this->refreshToken();
        }
        return $token;
    }

    /**
     * 生产authorization_code
     */
    public function genretAuthCode()
    {
        $auth_code = $this->di['cache']->get($this->auth_code_cache_key);
        if (!$auth_code) {
            $auth_code_url = 'https://developers.e.qq.com/oauth/authorize?';
            $auth_code_url .= sprintf('client_id=%s&', $this->client_id);
            $auth_code_url .= sprintf('redirect_uri=%s&', urlencode($this->auth_code_call_url));
            $auth_code_url .= sprintf('state=%s&', rand(111111, 999999));
            $auth_code_url .= sprintf('scope=%s', $this->scope);
            $this->log_file->notice("生成authorization_code请求的URL: {$auth_code_url}");
            var_dump($auth_code_url);
            die(0);
            \C\P\Http::get($auth_code_url);
        }
        return $auth_code;
    }

    /**
     * 接收authorization_code
     * 并生成access_token
     */
    public function getAuthCode()
    {
        $params = $this->request->get();
        $this->log_file->notice("authorization_code回调结果: " . json_encode($params));
        $auth_code = $this->getValue('authorization_code');
        $this->di['cache']->setnx($this->auth_code_cache_key, $auth_code, $this->auth_code_expire);
        // 获取access_token
        $this->genretAccessToken($auth_code);
        $this->success();
    }

    /**
     * 生成 或 刷新 广点通access_token
     */
    public function refreshToken($auth_code = 0)
    {
        $access_token_url = 'https://api.e.qq.com/oauth/token?';
        $access_token_url .= sprintf('client_id=%s&', $this->client_id);
        $access_token_url .= sprintf('client_secret=%s&', $this->secret_key);
        if ($auth_code) {
            $access_token_url .= sprintf('grant_type=%s&', 'authorization_code');
            $access_token_url .= sprintf('authorization_code=%s&', $auth_code);
            $access_token_url .= sprintf('redirect_uri=%s', $this->access_token_call_url);
        } else {
            $access_token_url .= sprintf('grant_type=%s&', 'refresh_token');
            $cache_access_token = $this->di['cache']->get($this->access_token_cache_key);
            if (!$cache_access_token) {
                // 这里是当refresh_token过期时，直接重新生成
                return $this->genretAuthCode();
            }
            $refresh_token = json_decode($cache_access_token, true)['refresh_token'];
            $access_token_url .= sprintf('refresh_token=%s&', $refresh_token);
        }
        $this->log_file->notice("access_token生成或刷新URL: " . $access_token_url);
        // 这里不确定是立即返回结果，还是走了下边对回调接口，所以两边都写了
        $res = \C\P\Http::get($$access_token_url);
        $data = json_decode($res, true);
        $this->log_file->notice("生成或刷新结果: " . $res);
        if ($data['code'] != 0) {
            $this->log_file->notice("生成 或 刷新access_token失败，请稍后再试; {$res}");
            return $data;
        }
        $access_token = [
            'access_token' => $data['data']['access_token'],
            'access_token_expires_in' => $data['data']['access_token_expires_in'],
        ];
        // 缓存access_token
        $this->di['cache']->setnx(
            sprintf($this->access_token_cache_key, $this->client_id),
            json_encode($access_token),
            $access_token['access_token_expires_in']
        );
        $refresh_token = [
            'refresh_token' => $data['data']['refresh_token'],
            'refresh_token_expires_in' => $data['data']['refresh_token_expires_in'],
        ];
        // 缓存refresh_token
        $this->di['cache']->setnx(
            sprintf($this->refresh_token_cache_key, $this->client_id),
            json_encode($refresh_token),
            $refresh_token['refresh_token_expires_in']
        );
    }

    public function testGdtAction()
    {
        $ret = $this->di['s_flowks']->search(['type' => 'gdt']);
        $url = urldecode($ret['call_url']);
        $this->gdt($ret, $url, 333);
        var_dump($ret, $url);
        die(0);
    }

    public function gdt($flow, $url, $uid)
    {
        $config = $this->di['s_config']->get('gdt_conf');
        if (!$config) {
            throw new \Exception("缺少配置");
        }
        $time = time();
        $nonce = rand(111111, 999999);
        $url .= "{$config['access_token']}&timestamp={$time}&nonce={$nonce}";
        $os_type = !empty($flow['imei']) ? 'hash_imei' : 'hash_idfa';
        $os_value = !empty($flow['imei']) ? $flow['imei'] : $flow['idfa'];
        $user_id = [
            $os_type => $os_value,
        ];
        $data = [
            // 'account_id' => $config['account_id'],
            'account_id' => '15404695',
            'user_action_set_id' => $config['user_action_set_id'],
            'actions' => [[
                'action_time' => $flow['time'],
                'user_id' => $user_id,
                'action_type' => 'REGISTER'
            ]]
        ];
        $data = json_encode($data);
        $headers = ['Content-Type' => 'application/json'];
        $res = \C\P\Http::post($url, $data, $headers);
        var_dump($data, $url, $res);
        die(0);
        $json = json_decode($res, true);
        if (empty($json) || $json['msg'] !== 'success') {
            return $res;
        }
        return true;
    }
}
