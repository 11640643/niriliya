<?php

namespace Fin;

use C\C\AdmController;

class PackController extends AdmController
{
    /**
     * 新增批量红包
     */
    public function addPackAction()
    {
        $name = $this->getValue('name', true, 'string');
        $money = $this->getValue('money', true, 'float');
        $vip = $this->getValue('vip', true, 'string');

        if ($this->s_funds->addPack($name, $vip, $money)) {
            $this->success();
        }
        $this->error();
    }

    public function userApplyAction()
    {
        $name = $this->getValue('name', true, 'string');
        $money = $this->getValue('money', true, 'float');
        $uid = $this->getValue('uid', true, 'int');

        if ($this->s_funds->applyUserPack($name, $uid, $money)) {
            $this->success();
        }

        $this->error();
    }

    /**
     * 创建 红包
     */
    public function createAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'create' => [],
        ];

        $add = $this->setCreateData();

        if (!$this->beforeCreate($add)) {
            $this->error($this->translate['doerror']);
        }

        if (empty($add)) {
            $this->error();
        }

        if (!$id = $this->service->save($add)) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterCreate($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }

}


