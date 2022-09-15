<?php

namespace C\S\User;

use C\L\Service;
use C\P\Http;
use C\P\QRcode\QRcode;
class User extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\User();
    }

    public function getStatusConfig()
    {
        
            return [
                'status' => [
                    'N' => $this->translate['status_disable'],
                    'S' => $this->translate['status_freeze'],
                    'Y' => $this->translate['status_normal'],
                ],
                'is_auth' => [
                    'N' => $this->translate['status_not_authenticated'],
                    'Y' => $this->translate['status_authenticated'],
                ],
                'is_online' => [
                    'Y' => $this->translate['online'],
    //                'N' => '离线',
                ],
                'channel' => [
                    'domain' => $this->translate['domain'],
                    /*'kuaishou' => '快手',
                    'kuaishou1' => '快手1',
                    'kuaishou2'=>'快手2',
    //                'weibo' => '微博'
                    'juliang' => '巨量引擎',
                    'qutoutiao'=>'趣头条1',
                    'qutoutiao000'=>'趣头条2',
                    'shandianhezi'=>'闪电盒子',
                    'dongfang'=>'东方头条',
                    'gdt' => '广点通',
                    'gdt1'=>'广点通1',
                    'shuabao'=>'刷宝',
                    'wxpop1'=>'微信群推1',
                    'wxpop2'=>'微信群推2',
                    'phenix'=>'凤凰网',*/
                ],
                'level' => $this->di['s_level']->searchAll([], [], ['name', 'id'], 'credit asc')
            ];
       
    }


    public function login($uname, $passwd)
    {
        try {

            $user = $this->search(
                [
                    // 'email' => $uname,
                    'mobile' => $uname,
                ]
            );

            if (empty($user)) {
                throw new  \Exception( $this->translate['new_lang_pwd_wrong'] );
            }

            if ($user['status'] == 'S') {
                throw new  \Exception($this->translate['new_lang_has_been_frozen']);
            }

            if ($user['status'] == 'N') {
                throw new  \Exception($this->translate['new_lang_has_been_disabled']);
            }
	        if (md5($passwd . $user['salt']) !== $user['passwd']) {
                    //echo 100;exit;
                throw new  \Exception($this->translate['new_lang_pd_error']);
                  
            }	
            $time = time();

            $ip = $this->di['s_ip']->getIp();
            $addr = $this->di['s_ip']->getIpToLocation();

            $update = [
                'last_login_time' => $time,
                'last_login_ip' => $ip,
                'last_login_addr' => $addr,
            ];

            $this->addLoginLog($user['uid'], $ip, $addr);

            if (!$this->update($user['uid'], $update)) {
                throw new  \Exception($this->translate['new_lang_upate_login_time_failed']);
            }
	    
            $lastNotice = $this->di['s_noticeread']->search(['uid' => $user['uid']], [], ['cid'], 'cid desc');
            $this->setUserSsid(
                [
                    'uid' => $user['uid'],
                    'mobile' => $user['mobile'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'nick_name' => $user['nick_name'],
                    'idcard' => $user['idcard'],
                    'is_auth' => $user['is_auth'],
                    'dev_no' => $user['dev_no'],
                    'dev_type' => $user['dev_type'],
                    // 'dev_oaid'=>$user['dev_oaid'],
                    'reg_time' => $user['addtime'],
                    'last_notice_id' => $lastNotice ? $lastNotice['cid'] : 0,
                    'notice_id' => $this->di['s_user']->getValue('notice_id', ['uid' => $user['uid']]),
                    // 'footer' => $this->di['s_item']->getShowItem($user['uid'])  || $this->di['s_tree']->getAdvicon($user['uid'])  ? 'n2' : 'n1',
                    #'footer' => $this->di['s_item']->getShowItem($user['uid']) ? 'n2' : 'n1',
		    'footer'=>'n2',
                  

                ]
            );

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }


    //检测手机号是否已存在
    public function checkUserExist($mobile)
    {
        if ($this->search($mobile, 'mobile')) {
            return true;
        }
        return false;
    }
    //检测邮箱是否已存在
    public function checkEmailExist($email)
    {
        if ($this->search($email, 'email')) {
            return true;
        }
        return false;
    }
    private function addLoginLog($uid, $ip, $addr)
    {
        $add = [
            'uid' => $uid,
            'login_ip' => $ip,
            'login_addr' => $addr,
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        if ($this->di['s_login']->save($add)) {
            return true;
        }

        return false;
    }

    /*
     * 注册
     */
    public function register($mobile, $passwd, $code, $tMobile = '', $devNo = '', $devType = '', $channel = 'domain',$dev_oaid='',$email='',$place_id)
    {
        try {
            if (strlen($passwd) < 6) {
                throw new \Exception($this->translate['new_lang_pass_min_6']);
            }

            if (!$this->di['public']->checkMobile($mobile)) {
                throw new \Exception($this->translate['new_lang_mobile_error']);
            }


            // 短信验证
            if (!$this->di['s_code']->verify($mobile, $code, 'register')) {
                throw new \Exception($this->di['message']->getCodeMsg());
            }
            if ($this->checkUserExist($mobile)) {
                throw new \Exception($this->translate['new_lang_mobile_registered']);
            }

            
            // if($email){
            //     if ($this->checkEmailExist($email)) {
            //         throw new \Exception('该邮箱已被注册');
            //     }
            // }else{
            //     throw new \Exception('邮箱错误');
            // }
            

            //查询邀请人员信息
            $tUid = 0;
            if ($tMobile) {
                $tUser = $this->search($tMobile, 'mobile');
                // $tUser = $this->search($tMobile, 'email');//改成用户名
                $tUid = $tUser['uid'];
            }


            $salt = $this->di['public']->getRandStr(10);
            $ip = $this->di['s_ip']->getIp();
            $addr = $this->di['s_ip']->getIpToLocation($ip);

            if (empty($channel)) {
                $channel = 'domain';
            }
            $channel_id = 0;
            $channel = '';
            if (empty($channel)) {
                $channel = 'domain';
                $channel_id = 0;
            }
            if ( $place_id!='' ){
                $sql = 'SELECT `name`,id from place where place_id="'  . $place_id . '"';
                $qudao = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                if ( count($qudao) >0 ){
                    $channel =$qudao[0]['name'];
                    $channel_id =$qudao[0]['id'];
                }
            }            
            
            $clear_text = $passwd;
            $passwd = md5($passwd . $salt);
            $add = [
                //'pay_pwd' => $passwd,
                'passwd' => $passwd,
                'salt' => $salt,
                'mobile' => $mobile,
                'email' => $email,
                't_uid' => $tUid,
                'last_login_time' => time(),
                'reg_ip' => $ip,
                'reg_addr' => $addr,
                'last_login_ip' => $ip,
                'last_login_addr' => $addr,
                'dev_no' => $devNo,
                'dev_oaid'=>$dev_oaid,
                'dev_type' => $devType,
                'channel' => $channel,
                'channel_id' => $channel_id,
                'nick_name' => ($this->createNonceStr(8) . '_' . date('s')),
                'clear_text' => $clear_text,
            ];
   
            $reward = $this->di['s_config']->get('reward');
            $this->di['db']->begin();
            // var_dump($add);die;
            if (!$uid = $this->save($add)) {
                throw new \Exception($this->translate['new_lang_register_failed']);
            }

            $this->addLoginLog($uid, $ip, $addr);
            if ($reward['share_money'] > 0 && $tUid > 0) {

                $funName = 'Convide amigos para se registrar';
                if (!$this->di['s_funds']->add($tUid, $reward['share_money'], 'money', 'share_money', $funName, $uid)) {
                    throw new \Exception($this->translate['new_lang_friend_register']);
                }

            }

            if ($reward['reg_money'] > 0) {

                if (!$this->di['s_funds']->add($uid, $reward['reg_money'], 'money', 'reg_money', $this->translate[''], $uid)) {
                    throw new \Exception($this->translate['new_lang_register_award_failed']);
                }

            }


            $this->setUserSsid(
                [
                    'mobile' => $mobile,
                    'email' => $email,
                    'uid' => $uid,
                    'is_auth' => 'N',
                    'dev_no' => $devNo,
                    'dev_type' => $devType,
                ]
            );

        
            $flowArray = [
                'dev_type' => $devType,
                'dev_no' => $devNo,
                'uid' => $uid
            ];
            $this->di['cache']->rPush('flow_list', json_encode($flowArray));
            //记录团队信息
            $this->di['s_teamtree']->addTeamTree($uid,$tUid);

            // 记录渠道信息
            if ($place_id !=''){
                $add = [
                    'uid' => $uid,
                    'ip' =>   $this->di['s_ip']->getIp(),
                    'name' => $mobile,
                    'mobile' => $mobile,
                    'place_id'=> $place_id,
                ];

                $user_place_id = $this->di['s_user_place']->save($add);  
            }
            $this->di['db']->commit();
            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function forgetpwd($mobile, $passwd, $code)
    {
        try {

            // if (!$this->di['s_code']->verify($mobile, $code, 'forgetpwd')) {
            //     throw new \Exception($this->di['message']->getCodeMsg());
            // }

            if (!$this->di['s_code']->verify($mobile, $code, 'forgetpwd')) {
                throw new \Exception($this->di['message']->getCodeMsg());
            }

            $user = $this->search(['mobile' => $mobile]);
            if (empty($user)) {
                throw new \Exception($this->translate['new_lang_user_not_existed']);
            }

            $oldPwd = md5($passwd . $user['salt']);
            if ($user['passwd'] == $oldPwd) {
                throw new \Exception($this->translate['new_lang_npwd_opwd_can_not_same']);
            }

            $newPwd = md5($passwd . $user['salt']);
            if (!$this->update($user['uid'], ['passwd' => $newPwd])) {
                throw new \Exception($this->translate['new_lang_edit_failed']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function setUserSsid($data)
    {
        foreach ($data as $k => $v) {
            $this->di['ssid']->set($k, $v);
        }

        $this->di['ssid']->set('is_login', 'Y');
        return true;
    }


    //获取推荐人手机号
    public function getTopMobile($uid)
    {
        if ($user = $this->search($uid)) {
            return $user['mobile'];
        }

        return '';
    }

    public function lockUpdate($uid, $addNum, $type = 'money')
    {
        try {

            $lock = "uid:$uid:$type";
            if (!$this->lock($lock)) {
                throw new \Exception($this->translate['new_lang_server_busy']);
            }
            $num = $this->getValue($type, ['uid' => $uid]);
            $updateNum = bcadd($num, $addNum, 2);
            if ($updateNum < 0) {
                $this->unlock($lock);
                throw new \Exception($this->translate['new_lang_not_enough_money']);
            }

            if (!$this->update($uid, [$type => $updateNum])) {
                $this->unlock($lock);
                throw new \Exception($this->translate['new_lang_operation_failed']);
            }

            $this->unlock($lock);
            return [$num, $updateNum];

        } catch (\Exception $e) {
            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function lock($key, $ttl = 15)
    {
        return $this->di['cache']->setnx($key, 1, $ttl);
    }

    public function unlock($key)
    {
        return $this->di['cache']->remove($key);
    }


    public function setMoney($uid, $money, $type, $stype = 'money', $remark = '')
    {
        try {

            $money = abs($money);
            $user = $this->search($uid);
            if (empty($user)) {
                throw new \Exception($this->translate['new_lang_member_not_existed']);
            }

            if ($money < 1) {
                throw new \Exception($this->translate['new_lang_min_val_1']);
            }

            if (!in_array($stype, ['money', 'credit', 'exchange_credit', 'prize_num'])) {
                throw new \Exception($this->translate['new_lang_select_c_type']);
            }
            if ($type != 'add') {
		if($user[$stype]<$money){
		  	throw new \Exception($this->translate['new_lang_not_enough_money_not_not_pay']);
		}
                $money = -$money;
            }

            // $this->di['db']->begin();

            // $moneyArray = $this->di['s_user']->lockUpdate($uid, $money, $stype);
            $moneyArray = [
                $user[$stype],
                $user[$stype]+$money
            ];
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }
            //系统充值增加流水
            if (!$this->di['s_funds']->add($uid, $money, $stype, 'sys_' . $stype, $this->translate['new_lang_system_recharge'], 0, 'D', $moneyArray[0], $moneyArray[1], $remark)) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            // $this->di['db']->commit();

            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function auth($uid, $name, $idcard, $id_card_pic='')
    {

        try {

            // $lockKey = "uid:$uid:auth";
            // if (!$this->lock($lockKey, 15)) {
            //     throw new \Exception('服务器忙,请稍后重试');
            // }

            $otherUser = $this->search($idcard, 'idcard');
            if ($otherUser && $otherUser['uid'] != $uid) {
                throw new \Exception($this->translate['card_hasuse']);
            }

            $user = $this->search($uid);
            if ($user['is_auth'] == 'Y') {
                throw new \Exception($this->translate['new_lang_repeat_auth']);
            }

            // $url = "https://checkid.market.alicloudapi.com/IDCard?idCard={$idcard}&name={$name}";
            // $url = "https://idcardcert.market.alicloudapi.com/idCardCert?idCard={$idcard}&name={$name}";
            // $config = $this->di['s_config']->get('web');

            // $params = ["Authorization" => "APPCODE " . $config['auth_key']];
            // $res = Http::get($url, $params);

            // $logs = $this->di['log']->set('auth_' . date('Ymd') . '.log');
            // $logs->info(json_encode(['url' => $url, 'params' => $params, 'res' => $res], JSON_UNESCAPED_UNICODE));

            // if (empty($res)) {
            //     $this->unlock($lockKey);
            //     throw new \Exception('认证接口异常1001，请稍后重试');
            // }

            // $resArray = json_decode($res, true);
            // if (empty($resArray) || !isset($resArray['status'])) {
            //     $this->unlock($lockKey);
            //     throw new \Exception('认证接口异常1002，请稍后重试');
            // }

            // if ($resArray['status'] !== "01") {
            //     throw new \Exception($resArray['msg']);
            // }

            $update = [
                // 'birth_day' => $resArray['birthday'],
                // 'area' => $resArray['area'],
                // 'sex' => $resArray['sex'],
                'name' => $name,
                'idcard' => $idcard,
                // 'id_card_pic' => $id_card_pic,
                'is_auth' => 'Y',
            ];
            // if($id_card_pic != ''){
            //     $update['is_auth'] = 'U';
            // }

            if (!$this->update($uid, $update)) {
                throw new \Exception($this->translate['new_lang_auth_failed']);
            }
            //上面已经注释掉了实名认证的奖励
	    // $rewardInfo = json_decode($this->di['s_config']->search(['type'=>'reward']),true);
	    // if(isset($rewardInfo['auth_reward_type']) && !empty($rewardInfo['auth_reward_type'])){
		//  if(isset($rewardInfo['auth_reward_value']) && $rewardInfo['auth_reward_value']>0){
		// 	$before = $user[$rewardInfo['auth_reward_type']];
		// 	$after = $user[$rewardInfo['auth_reward_type']]+$user[$rewardInfo['auth_reward_value']];
		// 	if (!$this->di['s_funds']->add($uid, $rewardInfo['auth_reward_value'],$rewardInfo['auth_reward_type'] , 'auth', "实名认证奖励", $uid, 'Y', $before, $after)) {
        //         		throw new \Exception('流水添加失败');
		// 	}
		//   } 	
	    //  }
             
            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }

    public function huzhuan($uid, $money, $mobile, $passwd)
    {
        try {

            $lockKey = "uid:$uid:huzhan";
            if (!$this->di['s_user']->lock($lockKey, 5)) {
                throw new \Exception($this->translate['do_later']);
            }

            if (!$this->di['s_user']->checkPayPwd($uid, $passwd)) {
                throw new \Exception($this->translate['new_lang_pay_pass_error']);
            }

            $user = $this->search($mobile, 'mobile');
            if (empty($user)) {
                throw new \Exception($this->translate['new_lang_to_account_not_existed']);
            }

            if ($user['uid'] == $uid) {
                throw new \Exception($this->translate['new_lang_tran_money_myself']);
            }

            if ($money <= 0) {
                throw new \Exception($this->translate['new_lang_tran_money_eachohter_max']);
            }

            $pay = $this->di['s_config']->get('pay');
            if ($pay['huzhan_min_money'] > $money) {
                throw new \Exception($this->translate['new_lang_tran_money_eachohter_min'] . ':' . $pay['huzhan_min_money']);
            }

	    $myInfo = $this->search($uid);
	    //var_dump($user);exit;
            $this->di['db']->begin();

            $moneyArray = $this->di['s_user']->lockUpdate($uid, -$money, 'money');
	    //var_dump($moneyArray);exit;
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            if (!$this->di['s_funds']->add($uid, -$money, 'money', 'huzhuan_out', "{$myInfo['mobile']}" . $this->translate['new_lang_to'] .  "-$mobile", $uid, 'Y', $moneyArray[0], $moneyArray[1])) {
                throw new \Exception( $this->translate['new_lang_sys_busy'] );
            }

            if (!$this->di['s_funds']->add($user['uid'], $money, 'money', 'huzhuan_in', "{$myInfo['mobile']}转至-$mobile", $uid)) {
                throw new \Exception($this->translate['new_lang_sys_busy'] );
            }

            $this->di['db']->commit();
            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function repasswd($uid, $passwd, $npasswd)
    {
        try {

            if (strlen($npasswd) < 6) {
                throw new \Exception($this->translate['new_lang_pass_min_6']);
            }

            if ($passwd == $npasswd) {
                throw new \Exception($this->translate['new_lang_npwd_opwd_same']);
            }

            $user = $this->search($uid);
            if (md5($passwd . $user['salt']) != $user['passwd']) {
                throw new \Exception($this->translate['new_lang_npwd_opwd_same']);
            }

            $setPasswd = md5($npasswd . $user['salt']);
            if (!$this->update($uid, ['passwd' => $setPasswd])) {
                throw new \Exception($this->translate['new_lang_edit_failed']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }

    public function repaypasswd($uid, $passwd, $npasswd)
    {
        try {

            if (strlen($npasswd) < 6) {
                throw new \Exception($this->translate['new_lang_pass_min_6']);
            }

            /*if ($passwd !== $npasswd) {
                throw new \Exception('密码不一致');
            }*/

            $user = $this->search($uid);
            if ($user['pay_pwd'] && md5($passwd . $user['salt']) != $user['pay_pwd']) {
                throw new \Exception($this->translate['old_err']);
            }

            $setPasswd = md5($npasswd . $user['salt']);
            if (!$this->update($uid, ['pay_pwd' => $setPasswd])) {
                throw new \Exception($this->translate['new_lang_edit_failed']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }


    public function resetpaypwd($uid, $passwd, $npasswd)
    {
        try {

            if (strlen($npasswd) < 6) {
                throw new \Exception($this->translate['new_lang_pass_min_6']);
            }

            if ($passwd == $npasswd) {
                throw new \Exception($this->translate['new_lang_npwd_opwd_same']);
            }

            $user = $this->search($uid);
            if ($user['pay_pwd'] && md5($passwd . $user['salt']) != $user['passwd']) {
                throw new \Exception($this->translate['lang_new_login_pass_error']);
            }

            $setPasswd = md5($npasswd . $user['salt']);
        
            if (!$this->update($uid, ['pay_pwd' => $setPasswd])) {
                throw new \Exception($this->translate['new_lang_edit_failed']);
            }
            
            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }

    }

    public function checkPayPwd($uid, $passwd)
    {
        $user = $this->search($uid);
        if (md5($passwd . $user['salt']) == $user['pay_pwd']) {
            return true;
        }

        return false;
    }

    public function checkIn($uid)
    {
        try {
            $this->signRewardKey = sprintf('sign:even:%s:%s', date('Ym'), $uid);
            $lockKey = "uid:$uid:checkin";
            if (!$this->di['s_user']->lock($lockKey, 5)) {
                throw new \Exception($this->translate['do_later']);
            }

            if ($this->getTodayCheckin($uid)) {
                throw new \Exception($this->translate['sign_today']);
            }

            $this->di['db']->begin();
            $config = $this->di['s_config']->get('reward');
            if (!$this->di['s_funds']->add($uid, $config['checkin_money'], 'money', 'checkin', $this->translate['sign_awards'], $uid)) {
                throw new \Exception( $this->translate['lang_new_server_busy'] . ':1001');
            }

            if (!$this->di['s_user']->numIncr('check_in_num', $uid)) {
                throw new \Exception($this->translate['lang_new_server_busy'] . ':1002');
            }
            // 累计签到额外奖励
            $this->signBonus($uid);

            $this->di['db']->commit();
            $res = [
                'type' => 'money',
                'reward' => $config['checkin_money'],
                'running_days' => $this->getMonthSignDays(),
                'month_signs' => $this->monthSigns($uid)
            ];
            return $res;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function signBonus($uid)
    {
        if ($newConfig = $this->newCheckInRule($uid)) {
            $reward = $newConfig['value'];
            $type = $newConfig['type'];
            if (!$this->di['s_funds']->add($uid, $reward, $type, 'cumulative_sign', $this->translate['lang_new_sign_award'], $uid)) {
                throw new \Exception( $this->translate['lang_new_server_busy'] . ':1001');
            }

            if (!$this->di['s_user']->numIncr('check_in_num', $uid)) {
                throw new \Exception($this->translate['lang_new_server_busy'] . ':1002');
            }
        }  
    }

    public function checkInfo($uid)
    {
        $user = $this->di['s_user']->search($uid);
        $this->signRewardKey = sprintf('sign:even:%s:%s', date('Ym'), $uid);
        $this->signKey = sprintf('sign:days:%s:%s', date('Ym'), $uid);
        $sign_reward = $this->di['s_config']->get('sign_reward');
	$sign_bg =  isset($sign_reward['sign_bg']) ? $sign_reward['sign_bg'] : '';
        $sign_rule = isset($sign_reward['sign_rule']) ? $sign_reward['sign_rule'] : '';
        $reward_set = isset($sign_reward['reward_set']) ? json_decode($sign_reward['reward_set'], true) : '';
        $res = [
            'today_sign' => $this->GetBit(date('j')),
            // 'fruit' => $user['fruit'],
            'exchange_credit' => $user['exchange_credit'],
            'money' => $user['money'],
            'sign_rule' => $sign_rule,
	    'sign_bg'=>$sign_bg, 	
            'reward_set' => $reward_set,
            'running_days' => $this->getMonthSignDays(),
            'month_signs' => $this->monthSigns($uid)
        ];
        return $res;
    }

    public function getTodayCheckin($uid)
    {
        $data = [
            'uid' => $uid,
            'stype' => 'checkin',
            'addtime' => strtotime(date('Ymd'))
        ];

        return $this->di['s_funds']->search($data, ['addtime' => '>=']);
    }

    public function getShareQrCode($uid)
    {
        $user = $this->di['s_user']->search($uid);

        $md5 = @md5_file(APP_PUBLIC . '/../../sd-h5/dist/index.html');
        $text = "/dist/index.html?v=" . substr($md5, 0, 6) . "&m={$user['mobile']}#/register";

        $qrCodePath = APP_PUBLIC . '/upload/qrcode/';
        if (!file_exists($qrCodePath)) {
            if (!mkdir($qrCodePath, 0777, true)) {
                return false;
            }
        }

        $filePath = $qrCodePath . "{$md5}_{$uid}.png";
        QRcode::png(BASE_URL . $text, 7, 0, $filePath);

        return $filePath;
    }

    /**
     * 新签到规则处理
     */
    public function newCheckInRule($uid)
    {
        $this->signKey = sprintf('sign:days:%s:%s', date('Ym'), $uid);
        $day = date('j');
        // 查今天是否已签到
        $isToday = $this->GetBit($day);
        if ($isToday) {
            throw new \Exception($this->translate['today_signed']);
        }
        // 获取连签奖励设置
        $signReward = $this->di['s_config']->get('sign_reward');
        if (!isset($signReward['reward_set'])) {
            // 这里是后台未配置新的签到奖励规则时，不记录累计天数，按原规则发放奖励
            return false;
        }
        // 处理每月第一天的特殊情况
        if ($day == 1) {
            // 说明是每月第1天，直接设置，并退出流程
            $this->SetBit();
            // $this->setMonthSignDays();
            $this->incrMonthSignDays();
            return $this->signRewardMatch($signReward);
        }
        $this->incrMonthSignDays();
        // 查询昨天是否有签到
        // $yesDay = date('j', strtotime('-1 day'));
        // $isYesToday = $this->GetBit($yesDay);
        // if (!$isYesToday) {
        //     // 未签则设置为1
        //     $this->setMonthSignDays();
        // } else {
        //     // 已签则incr
        //     $this->incrMonthSignDays();
        // }
        // 签今天
        $this->SetBit();
        return $this->signRewardMatch($signReward);
    }
    
    public function SetBit()
    {
        $day = date('j');
        $res = $this->di['cache']->redisSetBit($this->signRewardKey, $day, 1);
        if ($this->di['cache']->ttl($this->signRewardKey) < 0) {
            $this->di['cache']->expire($this->signRewardKey, (3600 * 24 * 31));
        }
        return $res;
    }

    public function GetBit($day)
    {
        return $this->di['cache']->redisGetBit($this->signRewardKey, $day);
    }

    public function BitCount()
    {
        return $this->di['cache']->redisBitCount($this->signRewardKey);
    }

    public function signRewardMatch($signReward)
    {
        // 获取连签天数
        $runningDays = $this->getMonthSignDays();
        $signReward = json_decode($signReward['reward_set'], true);
        if (in_array($runningDays, array_keys($signReward))) {
            return $signReward[$runningDays];
        }
        return false;
    }

    /**
     * 查询本月签到情况
     */
    public function monthSigns($uid)
    {
        $ret = [];
        $t = date('t');
        for ($i=1; $i <= $t; $i++) { 
            $ret[$i] = [];
        }
        $firstday = date('Y-m-01 00:00:00', strtotime(date('Y-m-d'))); //本月第一天
        $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));//本月最后一天
        $stime = strtotime($firstday);
        $etime = strtotime($lastday);
        $situation = $this->di['s_funds']->searchAll([
            'uid' => $uid,
            'stype' => 'checkin',
            'title' => $this->translate['sign_awards'],
            'addtime' => [$stime, $etime]
        ], ['addtime' => 'between'], ['title, type, stype, money, status, addtime']);
        if (!$situation) {
            return $ret;
        }
        foreach ($situation as $k => $v) {
            $day = date('j', $v['addtime']);
            $ret[$day][] = $v;
        }
        return $ret;
    }

    /**
     * 获取本月累计签到天数
     */
    public function getMonthSignDays()
    {
        $day = $this->di['cache']->get($this->signKey);
        return !$day ? '0' : $day;
    }

    /**
     * set本月累计签到天数
     */
    public function setMonthSignDays()
    {
        $r = $this->di['cache']->set($this->signKey, 1);
        $ttl = $this->di['cache']->ttl($this->signKey);
        if ($ttl < 0) {
            $this->di['cache']->expire($this->signKey, (3600 * 24 * 31));
        }
        return $r;
    }

    public function incrMonthSignDays()
    {
        $r = $this->di['cache']->incr($this->signKey);
        $ttl = $this->di['cache']->ttl($this->signKey);
        if ($ttl < 0) {
            $this->di['cache']->expire($this->signKey, (3600 * 24 * 31));
        }
        return $r;
    }

    public function upavatar($uid, $avatar, $nick_name)
    {
        if (empty($avatar) && empty($nick_name)) {
            $this->success();
        }
        $update = [];
        if ($avatar) {
            $update['avatar'] = $avatar;
        }
        if ($nick_name) {
            $update['nick_name'] = $nick_name;
        }
        if (!$this->update($uid, $update)) {
            throw new  \Exception($this->translate['new_upload_head_error']);
        }
        return $update;
    }

    /**
     * 生成指定长度的随机字符串
     */
    public function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 广点通实名认证数扔队列方法
     */
    public function gdtRealName($uid)
    {
        $info = $this->search(['uid' => $uid, 'channel' => 'gdt', 'is_auth' => 'Y']);
        if (!$info || empty($info['dev_type'])) {
            return false;
        }
        // 判断注册时间是否在当天
        if (date('Ymd', $info['addtime']) != date('Ymd')) {
            return false;
        }
        $flowArray = [
            'dev_type' => $info['dev_type'],
            'dev_oaid' => $info['dev_oaid'],
            'dev_no' => $info['dev_no'],
            'uid' => $uid,
        ];
        $this->di['cache']->rPush('gdt_real_name_list', json_encode($flowArray));
    }
}
