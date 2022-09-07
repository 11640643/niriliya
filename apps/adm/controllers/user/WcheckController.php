<?php

namespace User;

use C\C\AdmController;

class WcheckController extends AdmController
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
            'status'
        ];

        $this->pubSearchKeys = [
            'status', 'reg_ip', 't_uid', 'last_login_ip'
        ];
    }

    protected function beforeSearch()
    {
        $topMobile = $this->getValue('top_mobile', false, 'string');
        if ($topMobile) {
            $topUser = $this->s_user->search(['mobile' => $topMobile]);
            if ($topUser)
                $this->params['data']['t_uid'] = $topUser['uid'];
        }
        return true;
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $topUserNum = $this->s_user->getCount(['t_uid' => $item['uid']]);
            $item['top_user_num'] = $topUserNum;
            $item['hasChildren'] = $topUserNum > 0 ? true : false;
            $item['tree_key'] = md5($item['uid'] . time());
            $item['xuid'] = isset($this->params['data']['t_uid']) ?
                $this->params['data']['t_uid'] . '-' . $item['uid'] : $item['uid'];
            $item['top_mobile'] = '';
            $topUser = $this->s_user->search($item['t_uid']);
            if ($topUser) {
                $item['top_mobile'] = $topUser['mobile'];
            }
        }

        return true;
    }

    public function disableAction()
    {
        $ids = $this->getValue('ids', true);
        if ($this->s_user->updates(['status' => 'N'], ['uid' => $ids], ['uid' => 'in'])) {
            $this->success();
        }
        $this->error('更新失败');
    }

    public function enableAction()
    {
        $ids = $this->getValue('ids', true);
        if ($this->s_user->updates(['status' => 'Y'], ['uid' => $ids], ['uid' => 'in'])) {
            $this->success();
        }
        $this->error('更新失败');
    }

    public function disableTypeAction()
    {
        $content = $this->getValue('content', false, 'string');
        $type = $this->getValue('type', true, 'string');

        if(empty($content)){
            $this->error('内容有误');
        }

        if(!in_array($type, ['top', 'reg_ip', 'last_login_ip'])){
            $this->error('请选择类型');
        }

        if($type == 'top'){
            $type = 't_uid';
            $user = $this->s_user->search(['mobile' => $content]);
            if($user){
                $content = $user['uid'];
            }
        }

        if ($this->s_user->updates(['status' => 'N'], [$type => $content])) {
            $this->success();
        }
        $this->error('更新失败');
    }

}