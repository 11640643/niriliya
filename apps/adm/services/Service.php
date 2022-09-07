<?php

namespace C\S;
use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Http\Request;
class Service extends \C\L\Di
{
    public $model = null;

    protected function init()
    {
        $this->setModel();
        $this->request = new Request();
        $this->language = $this->request->getHeader('language');//获取当前语言
        $this->translate = $this->getTranslation();
    }
    protected function getTranslation()
    {
        // 询问浏览器什么是最好的语言
        // $language = $this->request->getBestLanguage();
        
        $messages = [];

        $translationFile = '../../apps/lang/' . $this->language . '.php';

        // 检查我们是否有该lang的翻译文件
        if (file_exists($translationFile)) {
            require $translationFile;
        } else {
            // 回退到某些默认值
            require APP_PATH . '/lang/cn.php';
        }

        // 返回翻译对象$messages来自上面的require语句
        return new NativeArray(
            [
                'content' => $messages,
            ]
        );
        // $this->translate = new NativeArray(
        //     [
        //         'content' => $messages,
        //     ]
        // );
    }
    //设置服务对应的
    protected function setModel()
    {
        $this->model = null;
    }

    public function save($data)
    {
        if (!$id = $this->model->add($data)) {
            return false;
        }
        return $id;
    }

    public function saves($data)
    {
        return $this->model->multiAdd($data);
    }

    public function update($id, $update, $columns = null)
    {
        return $this->model->editByPrimary($id, $update, $columns);
    }

    public function updates($update, $data, $dataType = [])
    {
        return $this->model->updates($update, $data, $dataType);
    }

    public function search($data = [], $dataType = [], $columns = [], $order = '')
    {
        if (is_array($data)) {
            return $this->model->get($data, $dataType, $columns, $order);
        }

        if (!empty($dataType)) {

            return $this->model->get(
                [$dataType => $data], [], $columns, $order
            );

        }

        return $this->model->getByPrimary($data, $dataType, $columns, $order);
    }

    public function searcha($data = [], $dataType = [], $columns = [], $order = '')
    {
        if (is_array($data)) {
            return $this->model->get($data, $dataType, $columns, $order, true);
        }

        if (!empty($dataType)) {

            return $this->model->get(
                [$dataType => $data], [], $columns, $order, true
            );

        }

        return $this->model->getByPrimary($data, $dataType, $columns, $order, true);
    }

    public function searchPage($data = [], $dataType = [], $columns = [], $order = '', $pageCurren = 1, $pageNum = 10)
    {
        if (!is_array($data)) {

            $data = [
                $dataType => $data
            ];

            $dataType = [];
        }
        return $this->model->search(
            $data, $dataType, $columns, $order, $pageCurren, $pageNum
        );
    }


    public function getCount($data = [], $dataType = [])
    {
        return $this->model->getCount($data, $dataType);
    }

    public function getSum($key, $data = [], $dataType = [])
    {
        $num = $this->model->getSum($key, $data, $dataType);
        if ($num == null) {
            return 0;
        }
        return $num;
    }

    public function getValue($key, $data = [], $dataType = [])
    {
        $data = $this->search($data, $dataType);
        if (!empty($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }
        return '';
    }

    public function searchAll($data = [], $dataType = [], $columns = [], $order = '', $limit = '')
    {
        return $this->model->getAll($data, $dataType, $columns, $order, $limit);
    }

    public function getStatusConfig()
    {
        return [];
    }

    public function numIncr($key, $id, $incr = 1)
    {
        return $this->model->incrByColumn($key, $id, $incr);
    }

}