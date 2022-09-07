<?php

namespace C\P;

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin {

    protected $publicAcl;

    public function __construct($dependencyInjector, $publicAcl = array(),$publicModule=array())
    {
        $this->_dependencyInjector = $dependencyInjector;
        $this->publicAcl = $publicAcl;
        $this->publicModule = $publicModule;
    }

    /**
     * 在进行路由分发之前进行权限判断，从ssid中获取当前登录用户的acl列表进行判断
     * @param \Phalcon\Events\Event $event
     * @param \Phalcon\Mvc\Dispatcher $dispatcher
     * @return boolean
     * @TODO 等acl服务建立好后开启权限判断
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
    	
   //      return true;

        $action = $dispatcher->getModuleName() . '/' . $dispatcher->getControllerName() . '/' . $dispatcher->getActionName();
//        if ($action=='api/api/login') {
//            return true;
//        }
        // echo $action;exit;
       // var_dump($this->publicAcl);var_dump($action);
        if (in_array($action, $this->publicAcl))
            return true;
        if (in_array($dispatcher->getModuleName(), $this->publicModule))
            return true;

        //调试关闭权限验证
        if (!$this->uid) {
            $this->response->setStatusCode(200, "OK");
            $this->response->setJsonContent(array('ret' => 402, 'msg' => '请先登录！'));
            $this->response->send();
            die();
        }

        if ($this->ssid->get('web'))
            return true;
        $roleName = $this->ssid->get('role_name');
        $acl = json_decode($this->ssid->get('acl'), true);

        //根据ssid获得acl，判断acl是否在用户权限当中。
        if ($roleName == 'admin') {
            return true;
        } else {
            $menu = $acl[APP_NAME]['children'];
            $acl = array();
            foreach($menu as $value) {
                foreach($value['children'] as $moduleInfo) {
                    foreach ($moduleInfo['children'] as $actionInfo) {
                        $acl[] = $actionInfo['url'];
                    }
                }
            }

            if (!in_array($action, $acl)) {
                $this->response->setStatusCode(200, "OK");
                $this->response->setJsonContent(array('ret' => 0, 'msg' => '您无权访问！'));
                $this->response->send();
                die();
            }
        }
    }

}
