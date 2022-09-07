<?php

namespace C\L;

use C\P\Publics;
use Phalcon\Loader,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Events\Manager,
    Phalcon\Db\Adapter\Pdo\Mysql,
    Phalcon\Events\Event;

Class App
{

    private $di;

    public function __construct()
    {

    }

    private function pubRegService($di)
    {

        $di->set('loader', function () {
            return new Loader();
        });

        //注册命名空间
        $di['loader']->registerNamespaces(array(
            'C\L' => CORE . '/library/',
            'C\P' => CORE . '/plugins/',
            'C\S' => CORE . '/services/',
            'C\M' => CORE . '/models/'
        ))->register();

        //配置文件服务
        $di->set('config', function () {
            return new Config();
        });

        //系统消息
        $di->set('message', function () {
            return new Message();
        }, true);

        //事件捕捉
        $di->set('eventsManager', function () {
            $manger = new Manager();
            return $manger;
        });

        //缓存服务
        $di->set('cache', function () use ($di) {
            return new \C\P\Redis(
                $di['config']->get('redis')
            );
        });

        //数据库
        $di->set('db', function () use ($di) {
            return new Mysql(
                $di['config']->get('db')->toArray()
            );
        });

        //加载自定义服务
        $serviceConfig = $di['config']->get('services', true);
        foreach ($serviceConfig as $key => $value) {
            $di->set($key, $value);
        }

        //日志服务
        $di->set('log', function () use ($di) {
            return new Logs();
        });

        $di->set('public', function () {
            return new Publics();
        }, true);


        $this->di = $di;

        return true;
    }

    private function cliRegService($di)
    {
        $di['loader']->registerDirs([
            APP_PATH . '/tasks',
        ]);
    }

    private function webRegService($di)
    {
        //控制视图
        $di->set('view', function () {
            return new \Phalcon\Mvc\View();
        });

        //路由
        $di->set('router', function () {

            $router = new \Phalcon\Mvc\Router();

            $router->add('/:module/:controller/:action/:params', array(
                'module' => 1,
                'controller' => 2,
                'action' => 3,
                'params' => 4
            ));

//            $router->add('/:module/:controller/:action', array(
//                'module' => 'api',
//                'controller' => 1,
//                'action' => 2,
//            ));

            $router->notFound([
                'module' => 'api',
                'controller' => 'api',
                'action' => 'index',
            ]);

            return $router;
        });

        //web日志
        $di->set('weblog', function () use ($di) {
            return $di['log']->set(APP_NAME . '_' . date('md') . '.log');
        }, true);

        $di->set('ssid', function () {
            return new Session();
        }, true);

        return true;
    }

    private function setJsonToPost()
    {
        $inputData = trim(file_get_contents('php://input'));
        if (substr($inputData, 0, 1) == '{') {

            $inputData = json_decode($inputData, true);
            if (empty($inputData)) {
                $inputData = [];
            }

            $_POST = array_merge($_POST, $inputData);
        }
    }


    public function web()
    {
        try {

            $this->setJsonToPost();

            //注册公共类服务
            $this->pubRegService(
                new \Phalcon\Di\FactoryDefault()
            );

            //注册web类服务
            $this->webRegService(
                $this->di
            );

            $this->di['weblog']->info(
                json_encode(['url' => $_SERVER['QUERY_STRING'], 'post' => $_POST], JSON_UNESCAPED_UNICODE)
            );

            //控制显示
            $web = new \Phalcon\Mvc\Application();
            $web->setDi(
                $this->di
            );
            //注册模块
            $web->registerModules(
                $this->di['config']->getModConf()
            );

            //监听服务服务
            $this->di['dispatcher']->setEventsManager(
                $this->di['eventsManager']
            );

            $listener = new Listener();
            $this->di['eventsManager']->attach(
                'application', $listener
            );

            $response = $web->handle();
            $response->send();


        } catch (\Exception $e) {

            $error = [
                'code' => 404,
                'msg' => $this->translate['acess_no_limit'],
            ];

            if (APP_MODE == 'dev') {
                $error['dev_show_msg'] = $e->getMessage();
            }

            echo json_encode($error, JSON_UNESCAPED_UNICODE);
        }
    }

    public function cli($argv)
    {

        $this->pubRegService(
            new \Phalcon\Di\FactoryDefault\Cli()
        );

        $this->cliRegService(
            $this->di
        );

        $cli = new \Phalcon\Cli\Console(
            $this->di
        );

        if (empty($argv[1])) {
            $argv[1] = 'main';
        }

        if (empty($argv[2])) {
            $argv[2] = 'run';
        }

        try {

            // 处理传入的参数
            $cli->handle([
                'task' => $argv[1],
                'action' => $argv[2],
                'params' => array_slice($argv, 3)
            ]);

        } catch (\Exception $e) {

            echo $e->getMessage();
        }
    }

}