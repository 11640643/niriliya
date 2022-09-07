<?php

namespace User;

use C\C\AdmController;

class UserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_user;
        $this->keyworkSearchKeys = [
            'name',
            'mobile'
        ];
        $this->hideKeys = [
            'passwd', 'pay_pwd', 'salt', 'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'freeze_login_time', 'last_login_time'
        ];

        $this->updateKeys = [
            'name', 'tmobile', 'idcard', 'passwd', 'pay_pwd', 'mobile', 'is_auth', 'money', 'status', 'prize_num','remark'
        ];

        $this->createKeys = [
            'name', 'tmobile', 'idcard', 'mobile', 'passwd', 'pay_pwd', 'is_auth', 'money', 'status', 'prize_num'
        ];
        $this->pubSearchKeys = [
            'status', 'is_auth', 'reg_ip', 'channel'
        ];
    }

    /* 
     * 搜索前
     */
    protected function beforeGetlist()
    {
        $key = 'login_user';
        $this->cache->zRemRangeByScore($key, 0, time() - 180);
        $isOnline = $this->getValue('is_online', false);
        if ($isOnline) {

            $key = 'login_user';
            $uids = $this->cache->zRangeByScore($key, 0, time());
            if (!empty($uids)) {
                $this->params['data']['uid'] = $uids;
                $this->params['data_type']['uid'] = 'in';
            } else {
                $this->params['data']['uid'] = -1;
            }

        }


        $levelId = $this->getValue('level', true, 'int');
        if ($levelId > 0) {
            $scores = $this->s_level->getNextLevelScore($levelId);
            if ($scores[1] > 0) {
                $this->params['data']['credit'] = $scores;
                $this->params['data_type']['credit'] = 'between';
            } else {
                $this->params['data']['credit'] = $scores[0];
                $this->params['data_type']['credit'] = '>=';
            }
        }
        $channel_id = $this->getValue('channel_id', true, 'int');
        if ($channel_id > 0) {
             $this->params['data']['channel_id'] = $channel_id;
        }
        return true;
    }

    /**
     * 搜索后
     */
    protected function afterGetlist(&$data)
    {
        $key = 'login_user';
        foreach ($data['list'] as &$value) {
            $other = [
                'online_status' => $this->cache->zScore($key, $value['uid']) ? '在线' : '离线', //在线状态
                'top_mobile' => $this->s_user->getTopMobile($value['t_uid']), //推荐人手机号
                'user_level' => $this->s_level->getLevel($value['uid'])['name']
            ];
            $value = array_merge($value, $other);
        }
        $data['user_num'] = $this->s_user->getCount();
        $data['online_user_num'] = $this->cache->zCount($key, 0, time());

        $config = $this->s_user->getStatusConfig()['channel'];

        $channelInfo = [];
        $todayTime = strtotime(date('Ymd'));
        foreach ($config as $k => $v) {
            $count = $this->s_user->getCount(['channel' => $k]);
            $todayCount = $this->s_user->getCount(['channel' => $k, 'addtime' => $todayTime], ['addtime' => '>=']);
            $channelInfo[] = "{$v}：[总人数：$count, 今日注册人数：$todayCount]";
        }
        $data['channel_info'] = implode('，', $channelInfo);
        $placelist = $this->s_place->searchAll();
        $data['placelist'] = $placelist;
        

        return true;
    }

    /**
     * 搜索
     * 
     */
    public function getlistAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'data' => [],
            'data_type' => [],
            'columns' => [],
            'order' => '',
        ];

        if (empty($this->params['page_curren'])) {
            $this->params['page_curren'] = $this->getValue('page_curren', false, 'int') ?: 1;
        }
        if (empty($this->params['page_num'])) {
            $this->params['page_num'] = $this->getValue('page_num', false, 'int') ?: 10;
        }

        $this->setSearchParams();

        if (!$this->beforeGetlist()) {
            $this->error($this->translate['request_error']);
        }

        $data = empty($this->params['data']) ? [] : $this->params['data'];
        $dataType = empty($this->params['data_type']) ? [] : $this->params['data_type'];
        $columns = empty($this->params['columns']) ? [] : $this->params['columns'];
        $order = empty($this->params['order']) ? '' : $this->params['order'];
        
        $list = $this->service->searchPage($data, $dataType, $columns, $order, $this->params['page_curren'], $this->params['page_num']);
        $this->setHide($list);
        $this->setShow($list);
        $this->setStatusName($list);
        $this->setCategoryName($list);
        $this->autoTimeToDate($list);
        $data = [
            'list' => $list,
            'count' => $this->service->getCount($data, $dataType),
            'page_num' => $this->params['page_num'],
            'page_curren' => $this->params['page_curren'],
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterGetlist($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }

    protected function afterView(&$data)
    {
        $data['view']['passwd'] = $data['view']['pay_pwd'] = '';
        $data['view']['tmobile'] = '';
        if ($data['view']['t_uid'] > 0) {
            $data['view']['tmobile'] = $this->s_user->getValue('mobile', $data['view']['t_uid']);
        }
        return true;
    }
    
    public function configAction(){
        $this->success([
            'is_auth' => [
                'N' => 'não certificado',
                'Y' => 'verificado',
                'U' => 'sob revisão',
                'F' => 'Falha na auditoria',
            ],
        ]);
    }

    /**
     * 删除 
     */
    public function deleteAction($id = 0){
        if (empty($this->service)) {
            $this->error();
        }

        if (!$this->beforeRemove()) {
            $this->error($this->translate['doerror']);
        }

        if ( !is_numeric( $id )){ $this->error($this->translate['nodata']);}
        

        if (empty($id)) {
            $this->error($this->translate['doerror']);
        }

        if (!$this->service->update($id, ['is_delete' => 1])) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterRemove($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }     

    /**
     * 编辑 商品分类
     */
    public function editAction($id=0)
    {   
        if ( \C\P\Helper::isPUT() ){
            if (empty($this->service) ) {
                $this->error();
            }

            $this->params = [
                'update' => [],
            ];
            $update = $this->getValue('data');
            $id = isset( $update['id'] )? $update['id'] :'' ;
            unset($update['id']);
            if (empty($id)) {
                $id = $this->params;
            }

            if (!$this->beforeUpdate($update)) {
                $this->error($this->translate['doerror']);
            }

            $data = ['id' => $id];
            if (empty($update)) {
                $this->success($data);
            }

            $update = $this->setUpdateVlaue2($update);
            unset($update['is_show_index_name']);
            unset($update['is_disable_name']);
            unset($update['channel_name']);
            unset($update['is_auth_name']);
            unset($update['freeze_login_time_date']);
            unset($update['last_login_time_date']);

            if (!$this->service->update($id, $update)) {
                $this->error($this->translate['nodata']);
            }

            if (!$this->afterUpdate($data)) {
                $this->error($this->translate['doerror']);
            }

            $this->success($data);
            
        }

        if (empty($this->service)) {
            $this->error();
        }
        if (!$this->beforeView()) {
            $this->error($this->translate['doerror']);
        }
        if (empty($id)) {
            $this->error($this->translate['nodata']);
        }
        if ( !is_numeric( $id )){ $this->error($this->translate['nodata']);}
        $view = $this->service->search($id);

        if (empty($view)) {
            $this->error($this->translate['nodata']);
        }

        $this->setHide($view);
        $this->setShow($view);
        $this->setStatusName($view);
        $this->autoTimeToDate($view);

        $data = [
            'view' => $view,
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterView($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    } 

    protected function beforeUpdate(&$data)
    {

        $user = $this->s_user->search(['mobile' => $data['mobile']]);
        if (!empty($user) && $user['uid'] != $data['uid']) {
            $this->error('当前账号已存在');
        }
        if (!isset( $data['passwd'] )){
            $this->error('请输入密码');
        }

        
        if (!empty($data['passwd'])) {
            $data['clear_text'] = $data['passwd'];
            $data['passwd'] = md5($data['passwd'] . $user['salt']);
        }

        if (!empty($data['pay_pwd'])) {
            $data['pay_pwd'] = md5($data['pay_pwd'] . $user['salt']);
        }

        if (!empty($data['tmobile'])) {
            $user = $this->s_user->search(['mobile' => $data['tmobile']]);
            if (!empty($user)) {
                $data['t_uid'] = $user['uid'];
            }
        }
        
        unset($data['tmobile']);
        return true;
    }

    protected function beforeCreate(&$data)
    {
        if (empty($data['mobile'])) {
            $this->error('手机号必填');
        }

        if (empty($data['passwd'])) {
            $this->error('登录密码必填');
        }

        if (empty($data['pay_pwd'])) {
            $this->error('交易密码必填');
        }

        $user = $this->s_user->search(['mobile' => $data['mobile']]);
        if (!empty($user)) {
            $this->error('会员已存在');
        }

        $data['salt'] = $this->public->getRandStr(10);
        $data['clear_text'] = $data['passwd'];
        $data['passwd'] = md5($data['passwd'] . $data['salt']);
        $data['pay_pwd'] = md5($data['pay_pwd'] . $data['salt']);
        return true;
    }

    public function createAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'create' => [],
        ];

        $add = $this->setCreateData();

        if (!$this->beforeCreate($add)) {
            $this->error($this->translate['doerror']);
        }

        if (empty($add)) {
            $this->error();
        }

        if (!$id = $this->s_user->save($add)) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterCreate($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }


    //冻结会员
    public function freezeAction()
    {   
        $uid = $this->getValue('uid', false, 'int');
        $user = $this->service->search($uid);
        if (!empty($user)) {
            //$this->cache->remove($this->cache->get('ssid_' . $uid));
            $this->service->update($uid, ['status' => 'S']);
        }

        $this->success();
    }

    //删除用户
    public function removeAction()
    {
        $uid = $this->getValue('uid', false, 'int');
        $user = $this->service->search($uid);

        if (!empty($user)) {
            $this->service->update($uid, ['is_delete' => 1]);
        }

        $this->success();
    }

//    public function setMoneyAction()
//    {
//        $id = $this->getValue('id', true, 'int');
//        $money = $this->getValue('money', true, 'float');
//        $type = $this->getValue('type', true) ?: 0;
//        $remark = $this->getValue('remark', false, 'string');
//
//        if ($this->s_user->setMoney($id, $money, $type, 'money', $remark)) {
//            $this->success();
//        }
//
//        $this->error($this->message->getSerMsg());
//    }

    public function setMoneyAction()
    {
        $id = $this->getValue('id', true, 'int');
        $money = $this->getValue('num', true, 'float');
        $type = $this->getValue('type', true, 'string');
        $stype = $this->getValue('stype', true, 'string');
        $title = $this->getValue('title', true, 'string');

        //临时修改 
        if($this->s_user->setMoney($id, $money, $type, $stype, $title)){
            $this->success();
        }

        $this->error($this->message->getSerMsg());
    }

    public function setPrizeAction()
    {
        $id = $this->getValue('id', true, 'int');
        $num = $this->getValue('num', true, 'int');
        $type = $this->getValue('type', true) ?: 0;
        $remark = $this->getValue('remark', false, 'string');

        if ($this->s_user->setMoney($id, $num, $type, 'prize_num', $remark)) {
            $this->success();
        }

        $this->error($this->message->getSerMsg());
    }

    public function setAnwserAction()
    {
        $id = $this->getValue('id', true, 'int');
        $num = $this->getValue('num', true, 'int');
        $type = $this->getValue('type', true) ?: 0;
        $remark = $this->getValue('remark', false, 'string');

        if ($this->s_user->setMoney($id, $num, $type, 'anwser_num', $remark)) {
            $this->success();
        }

        $this->error($this->message->getSerMsg());
    }
}


