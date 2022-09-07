<?php

namespace C\L;

class Message
{
    private $serMsg;
    private $codeMsg;

    public function setSerMsg($msg)
    {
        $this->serMsg = $msg;
    }

    public function getSerMsg()
    {
        return $this->serMsg;
    }

    public function setCodeMsg($msg)
    {
        $this->codeMsg = $msg;
    }

    public function getCodeMsg()
    {
        return $this->codeMsg;
    }


}