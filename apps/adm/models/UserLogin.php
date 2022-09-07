<?php

namespace C\M;


use C\M\Model;

class UserLogin  extends Model
{

    public function beforeValidationOnCreate()
    {
        $this->addtime = time();
        return true;
    }


}
