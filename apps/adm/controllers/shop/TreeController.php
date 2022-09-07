<?php

namespace Shop;

use C\L\AdmController;

class TreeController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_tree;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->pubSearchKeys = [
            'status'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];

        $this->keyworkSearchKeys = [
            'uid'
        ];

    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $user = $this->s_user->search($item['uid']);
            if(!$user){
                unset($item);
                continue;
            }
            $item['user_name'] = $user['name'];
            $item['user_mobile'] = $user['mobile'];
        }
        return true;
    }

}


