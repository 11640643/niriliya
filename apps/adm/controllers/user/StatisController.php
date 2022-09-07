<?php

namespace User;

use C\C\AdmController;

class StatisController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_uscd;
    }

    public function beforeSearch()
    {
        $type = $this->getValue('type', false, 'string');
        if (!$type) {
            $type = 'ip';
        }
        $cat = $this->s_usc->search(['type' => $type, 'status' => 'Y']);
        if ($cat) {
            $this->params['data']['cid'] = $cat['id'];
        }

        $this->params['order'] = 'id asc';
        return true;
    }

    public function afterSearch(&$data)
    {
        $data['config']['type'] = $this->s_usc->getStatusConfig()['type'];
        if (empty($this->params['data']['cid'])) {
            $data['list'] = [];
            return true;
        }

        $type = $this->getValue('type', false, 'string');
        foreach ($data['list'] as &$item) {
            $item['name'] = $item['mobile'] = '无信息';
            if (!in_array($type, ['ip'])) {
                $user = $this->s_user->search($item['v1']);
                $item['name'] = $user['name'];
                $item['mobile'] = $user['mobile'];
            }
        }
        return true;
    }

    public function statisAction()
    {
        if ($this->s_usc->apply()) {
            $this->success();
        }

        $this->error();
    }

    public function getStatisStatusAction()
    {
        $status = false;
        if ($this->s_usc->search(['status' => 'D'])) {
            $status = true;
        }

        $this->success([
            'status' => $status
        ]);

    }
}