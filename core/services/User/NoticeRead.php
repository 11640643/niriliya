<?php

namespace C\S\User;

use C\L\Service;

class NoticeRead extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserNoticeRead();
    }
}
