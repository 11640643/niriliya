<?php

namespace C\M;

use C\L\Model;

class UserTeamTree  extends Model
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