<?php

namespace User;

use C\C\AdmController;

class UseranwserController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_uanw;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];
    }

    protected function beforeSearch()
    {
        $config = $this->s_config->get('anwser');
        $this->params['data']['no'] = $config['no'];
        return true;
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            $item['name'] = $user['name'];
            $item['mobile'] = $user['mobile'];
            $item['sum'] = $this->s_uanwlist->getCount(['cid' => $item['id'], 'status' => 'Y']) . '/' . $this->s_uanwlist->getCount(['cid' => $item['id'], 'status' => 'N']);
        }
        return true;
    }

    public function resetAction()
    {
        $config = $this->s_config->search(['type' => 'anwser']);
        $content = json_decode($config['content'], true);
        $content['no'] = md5(time());

        if ($this->s_config->update($config['id'], ['content' => json_encode($content)])) {
            $this->success();
        }

        $this->error();
    }

    public function sendAction()
    {
        if($this->cache->get('anwser_send') == ''){
            $this->cache->set('anwser_send', 'D');
        }

        $this->success();
    }

}


