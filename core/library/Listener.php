<?php

namespace C\L;

use Phalcon\Events\Event;

class Listener extends Service
{
    public function boot(Event $event, $application)
    {

    }

    public function beforeStartModule(Event $event, $application)
    {

    }

    public function beforeException(Event $event, $application)
    {
        //throw new \Exception('非法访问');
    }


}