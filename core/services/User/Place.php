<?php

namespace C\S\User;

use C\L\Service;

class Place extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserPlace();
    }

    /** 
     * 添加用户的来源渠道
     * 
     */
    public function save($data)
    {
        if (!$id = $this->model->add($data)) {
            return false;
        }
        return $id;
    }   
}