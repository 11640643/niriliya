<?php

namespace C\M;

use C\L\Model;

// 二开 新增 Userplace 表
class UserPlace extends Model
{

    public function beforeValidationOnCreate()
    {
        $this->addtime = $this->uptime = time();
        return true;
    }

    public function beforeValidationOnUpdate()
    {
        $this->uptime = time();
        return true;
    }

}
