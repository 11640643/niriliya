<?php

namespace C\L;

use Phalcon\DiInterface,
    Phalcon\Di\InjectionAwareInterface;

class Di implements InjectionAwareInterface
{
    protected $di;

    protected $model = null;

    final public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        return true;
    }

    public function setDi(DiInterface $di)
    {
        $this->di = $di;
        return true;
    }

    public function getDi()
    {
        return $this->di;
    }

}