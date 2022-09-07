<?php

namespace C\M;


use C\L\Model;

class UserLogin  extends Model
{

    public function beforeValidationOnCreate()
    {
        $this->addtime = time();
        return true;
    }


}
