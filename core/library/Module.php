<?php

namespace C\L;

use Phalcon\Loader,
    Phalcon\Mvc\ModuleDefinitionInterface;

class Module implements ModuleDefinitionInterface
{

    protected $moduleName;

    public function registerAutoloaders(\Phalcon\DiInterface $di = null)
    {
        $di->get('loader')->registerNamespaces([
            ucfirst($this->moduleName) => APP_PATH . '/modules/' . strtolower($this->moduleName)
        ])->register();
    }

    public function registerServices(\Phalcon\DiInterface $di)
    {
        $di['dispatcher']->setDefaultNamespace(ucfirst($this->moduleName));

    }

}

