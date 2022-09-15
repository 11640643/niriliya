<?php

namespace User;

use C\L\WebUserController;
use C\P\Http;

class InfoController extends WebUserController
{

    //我的-个人中心
    public function indexAction()
    {
        $user = $this->s_user->search($this->uid);
    $today_time  =  strtotime(date("Y-m-d 00:00:00"));
    $ts_time = strtotime(date("Y-m-d 00:00:00"))+86400;
    $item_apr = $this->s_iam->searchAll(['uid'=>$this->uid,'back_time'=>[$today_time,$ts_time]],['back_time'=>'between']);
        $ds_apr_money = 0;
    foreach($item_apr as $key=>$val){
        $total_money = bcadd($val['money'],$val['apr_money'],2);
        $ds_apr_money = bcadd($ds_apr_money,$total_money,2); 
    }
    $data = [
            'uid' => $this->uid,
            'money' => number_format($user['money'] ,2,".",","),
            'glod' => $user['gold'],
            'credit' => $user['credit'],
            'exchange_credit' =>  $user['exchange_credit'],
            'mobile' => $user['mobile'],
            'name' => $user['name'],
            'nick_name' => $user['nick_name'],
            'avatar' => $user['avatar'],
            //'ds_money' => number_format( bcadd(0, $this->s_iam->getSum('money', ['uid' => $this->uid, 'status' => 'D']), 2)),
            'ds_money' => number_format( bcadd(0, $this->s_iam->getSum('money', ['uid' => $this->uid, 'status' => 'D']), 2) ,2,".",","),
            //'ds_apr_money' => bcadd(0, $this->s_iam->getSum('apr_money', ['uid' => $this->uid, 'status' => 'D']), 2),
            //'ds_apr_money' => number_format($ds_apr_money),
            'ds_apr_money' => number_format($ds_apr_money,2,".",","),
            'vip_name' => $this->s_level->getLevel($this->uid)['name'],
            'user_logo' => $this->s_config->get('web')['user_logo'],
            'is_new_notice' => $this->s_noticeread->search(['uid' => $this->uid, 'read_time' => 0]) ? true : false,
            'is_auth' => $user['is_auth'],
            //'total_profit'=>number_format(bcadd(0, $this->s_iam->getSum('apr_money', ['uid' => $this->uid, 'status' => 'D']), 2)),     
            'total_profit'=>number_format( bcadd(0, $this->s_iam->getSum('apr_money', ['uid' => $this->uid, 'status' => 'D']), 2),2,".",","  ),     
//            'kefu_link' => $this->s_config->get('web')['kefu_link'],
       //'total_profit'=>$total_profit
        ];
        $this->success($data);
    }

    //我的余额页面
    public function moneyAction()
    {
        $uid = $this->uid;
        $data = [
            'money' => $this->s_user->getValue('money', $uid),
            'is_hide_pay' => 'N'
        ];

        $this->success($data);
    }

    // public function tttestAction()
    // {
    //     $r = $this->s_user->gdtRealName($this->uid);
    //     var_dump($r);
    //     die(0);
    // }

    //实名认证
    public function authAction()
    {
        $name = $this->getValue('name', true);
        $idcard = $this->getValue('idcard', true);
        // $card1 = $this->getValue('card1', true);
        // $card2 = $this->getValue('card2', trueA);
        // if($card1 == '_' && $card2 == '_'){
        //     $id_card_pic = '';
        // } else {
        //     $id_card_pic = json_encode(['card1' => $card1, 'card2' => $card2]);
        // }

        if ($this->s_user->auth($this->uid, $name, $idcard)) {
            $this->ssid->set('is_auth', 'Y');
            // 扔个队列，上报广点通的实名数
            $this->s_user->gdtRealName($this->uid);
            $this->success();
        }

        $this->error();
    }

    public function infoAction()
    {
        $user = $this->s_user->search($this->uid, [], [
            'is_auth', 'name', 'idcard', 'mobile','authFailed','id_card_pic'
        ]);
        $this->success($user);
    }

    //我的邀请
    public function shareAction()
    {
        //当天时间
        $start_time = strtotime(date('Y-m-d').'00:00:00');
        $end_time   = strtotime(date('Y-m-d').'23:59:59');
        $uid = $this->uid;
        
        $mobile = $this->s_user->getValue('mobile', $uid);
        $md5 = @md5_file('/home/www/wwwroot/station_admin/www/web/h5/index.html');
        $url = "https://www.cxfvqor.com/index.html?v=" . substr($md5, 0, 6) . "&m={$mobile}#/register";


        // echo '<pre/>';
        // echo $start_time;
     
        // $test = $this->di['s_teamapr']->getSum('team_apr_money', ['t_uid' => $uid,'addtime'=>$start_time,'addtime' => '>=' . $end_time]);
        // print_r($test);
        // exit();
        $data = [
            'mobile' => $this->s_user->getValue('mobile', $uid),
            'share_user_num' => $this->s_user->getCount(['t_uid' => $uid]),
            'share_image_url' => BASE_URL . '/api/api/qrcode?uid=' . $this->uid,
            'share_url' => $url,
            'share_user' => [],
            'avatar' => $this->s_config->get('web')['user_logo'],
            'shareinfo' => $this->s_config->get('jz')['bgimage'],
            'teaminfo' =>  $this->s_team->getTeamLevel($uid),//获取等级
            
            'itemMoneySum' => bcadd($this->s_il->getSum('money', ['uid' => $uid]), 0, 2),//总投资金额
            
            'income' => bcadd( $this->di['s_teamtree']->getSum('sum_money', ['t_uid' => $uid]), 0, 2),//总投资金额
            'todayIncome' => bcadd( $this->di['s_teamapr']->getSum('team_apr_money', ['t_uid' => $uid,'addtime'=>$start_time],['addtime' => '>=']), 0, 2),//总投资金额
            // 'itemMoneySum' => bcadd($this->di['s_il']->getSum('money', ['uid' => $v['uid']]), 0, 2);
            'today_user_num' => $this->s_user->getCount(['t_uid' => $uid,'addtime'=>$start_time],['addtime' => '>=']),
            // 'team_user_num' => $this->s_teamtree->gatAllTeamMembersCount($uid),//这里只算了5级
            'team_user' => $this->s_teamtree->gatAllTeamMembers($uid),//这里只算了5级
            'd_user_num' => $this->s_user->getCount(['t_uid' => $uid]),//直推
            'teamlist' => $this->s_team->getAllTeamLevel(),//获取等级
            'user_apr' => $this->s_teamtree->getAllTeamList($uid),
            'month_money' => [],
        ];
        // var_dump($data);
        foreach ($data['user_apr'] as &$val) {
            $val['username'] = substr($val['username'], 0, 3) . '*****' . substr($val['username'], -3, 3);
        }
        // $data['share_user_num'] = 110;
        if($data['share_user_num'] < 30){
            $data['month_money'] = array(
                'base_num' => 30,
                'money'=> '180000'
            );
        }elseif($data['share_user_num'] < 50){
            $data['month_money'] = array(
                'base_num' => 50,
                'money'=> '480000'
            );
        }elseif($data['share_user_num'] < 100){
            $data['month_money'] = array(
                'base_num' => 100,
                'money'=> '1000000'
            );
        // }elseif($data['share_user_num'] < 200){
        //     $data['month_money'] = array(
        //         'base_num' => 200,
        //         'money'=> '¥170000000'
        //     );
        }else{
            $data['month_money'] = array(
                'base_num' => 100,
                'money'=> '1000000'
            );
        }

        // $config = $this->s_config->get('jz');
        // $content = json_decode($config['content'], true);
        // $data['shareinfo'] = $content['bgimage'];

        $shareUserArray = $this->s_user->searchAll(['t_uid' => $uid], [], ['uid','mobile', 'addtime']);
        // var_dump($shareUserArray);
        if (!empty($shareUserArray)) {
            foreach ($shareUserArray as $item) {
                $data['share_user'][] = [
                    'mobile' => substr($item['mobile'], 0, 3) . '*****' . substr($item['mobile'], -3, 3),
                    'date' => date('Y-m-d H:i', $item['addtime']),
                    'amount' => bcadd($this->s_il->getSum('money', ['uid' =>$item['uid']]), 0, 2),
                    'income' => ''
                ];
            }
        }
        //拉取团队信息
        // $this->s_team->getTeamLevel($uid);
        $this->success($data);
    }

    public function repasswdAction()
    {
        $passwd = $this->getValue('passwd', true, 'string');
        $npasswd = $this->getValue('npasswd', true, 'string');

        if ($this->s_user->repasswd($this->uid, $passwd, $npasswd)) {
            $this->success();
        }

        $this->error();
    }

    public function repaypasswdAction()
    {
        $passwd = $this->getValue('passwd', false, 'string');
        $npasswd = $this->getValue('npasswd', true, 'string');

        if ($this->s_user->repaypasswd($this->uid, $passwd, $npasswd)) {
            $this->success();
        }

        $this->error();
    }

    public function resetpaypwdAction()
    {
        $passwd = $this->getValue('passwd', false, 'string');
        $npasswd = $this->getValue('npasswd', true, 'string');

        if ($this->s_user->resetpaypwd($this->uid, $passwd, $npasswd)) {
            $this->success();
        }

        $this->error();
    }

    public function checkinAction()
    {
        
        if ($check_money = $this->s_user->checkIn($this->uid)) {
            // $adv_con =$this->s_tree->getAdvicon($this->uid);
            // if($adv_con)
            // {
            //     $this->ssid->set('footer','n2');
            // }
            $this->success($check_money);
        }

        $this->error();
    }

    public function checkinfoAction()
    {
        if ($res = $this->s_user->checkInfo($this->uid)) {
            $this->success($res);
        }
        $this->error();
    }

//    public function checkinlistAction()
//    {
//        $checkinList = $this->s_user->searchPage([], [], ['mobile', 'check_in_num'], 'check_in_num desc');
//        foreach ($checkinList as &$item) {
//            $item['mobile'] = substr($item['mobile'], 0, 3) . "*****" . substr($item['mobile'], -4, 4);
//        }
//
//        $this->success([
//            'checkin_list' => $checkinList,
//            'is_checkin' => $this->s_user->getTodayCheckin($this->uid) ? true : false
//        ]);
//    }

    public function checkinListAction()
    {
        $config = $this->s_config->get('checkin');

        $data = [];

        $subDays = ceil((time() - strtotime(date('20191022'))) / 3600 / 24);
        foreach ($config as $k => $v) {

            if ($v && strstr($k, 'mobile')) {
                $data[] = [
                    'mobile' => substr($v, 0, 4) . "*****" . substr($v, -4, 4),
                    'num' => $config['num' . str_replace('mobile', '', $k)] + $subDays
                ];
            }
        }

        $this->success(array_merge([
            'list' => $data,
            'num' => $config['user_num'] + ceil((time() - strtotime(date('20191022'))) / 600) * 10,
            'is_checkin' => $this->s_user->getTodayCheckin($this->uid) ? true : false
        ]));
    }

    public function upavatarAction()
    {
        $avatar = $this->getValue('avatar');
        $nick_name = $this->getValue('nick_name');
        if ($res = $this->s_user->upavatar($this->uid, $avatar, $nick_name)) {
            $this->success($res);
        }
        $this->error();
    }

}


