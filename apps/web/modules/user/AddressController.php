<?php

namespace User;

use C\L\WebUserController;

class AddressController extends WebUserController
{

    protected function init()
    {
        $this->service = $this->s_address;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime',
        ];
    }

    protected function beforeSearch()
    {
        $this->params['data']['uid'] = $this->uid;
        return true;
    }

    public function searchAction()
    {   
        if (empty($this->service)) {
            $this->error();
        }

        $this->params = [
            'data' => [],
            'data_type' => [],
            'columns' => [],
            'order' => '',
        ];

        if (empty($this->params['page_curren'])) {
            $this->params['page_curren'] = $this->getValue('page_curren', false, 'int') ?: 1;
        }
        if (empty($this->params['page_num'])) {
            $this->params['page_num'] = $this->getValue('page_num', false, 'int') ?: 10;
        }

        $this->setSearchParams();

        if (!$this->beforeSearch()) {
            $this->error($this->translate['request_error']);
        }

        $data = empty($this->params['data']) ? [] : $this->params['data'];
        $dataType = empty($this->params['data_type']) ? [] : $this->params['data_type'];
        $columns = empty($this->params['columns']) ? [] : $this->params['columns'];
        $order = empty($this->params['order']) ? '' : $this->params['order'];
        
        $list = $this->service->searchPage($data, $dataType, $columns, $order, $this->params['page_curren'], $this->params['page_num']);
        $this->setHide($list);
        $this->setShow($list);
        $this->setStatusName($list);
        $this->setCategoryName($list);
        $this->autoTimeToDate($list);
        $data = [
            'list' => $list,
            'count' => $this->service->getCount($data, $dataType),
            'page_num' => $this->params['page_num'],
            'page_curren' => $this->params['page_curren'],
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterSearch($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }

    protected function afterView(&$data)
    {
        $data['view']['tags'] = json_decode($data['view']['tags'], true);
        return true;
    }

    public function viewAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        if (!$this->beforeView()) {
            $this->error($this->translate['doerror']);
        }

        $id = $this->getValue('id', true, 'int');
        if (empty($id)) {
            $id = $this->params;
        }

        $view = $this->service->search($id);
        if (empty($view)) {
            $this->error($this->translate['nodata']);
        }

        $this->setHide($view);
        $this->setShow($view);
        $this->setStatusName($view);
        $this->autoTimeToDate($view);

        $data = [
            'view' => $view,
        ];

        $data['config'] = $this->service->getStatusConfig();
        if (!$this->afterView($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }


    public function saveAction()
    {
        $id = $this->getValue('id', true, 'int');
        $name = $this->getValue('name', true, 'string');
        $tags = $this->getValue('tags');
        $tag = $this->getValue('tag', true);
        $tel = $this->getValue('tel', true, 'string');
        $province = $this->getValue('province', true, 'string');
        $city = $this->getValue('city', true, 'string');
        $county = $this->getValue('county', true, 'string');
        $address = $this->getValue('addressDetail', true, 'string');
        $areaCode = $this->getValue('areaCode', true, 'string');
        $postalCode = $this->getValue('postalCode', true, 'string');
        $isDefault = $this->getValue('isDefault', true);

        $data = [
            'uid' => $this->uid,
            'name' => $name,
            'tags' => $tags,
            'tag' => $tag,
            'tel' => $tel,
            'province' => $province,
            'city' => $city,
            'county' => $county,
            'address' => $address,
            'area_code' => $areaCode,
            'postal_code' => $postalCode,
            'is_default' => $isDefault ? 'Y' : 'N',
        ];
        // var_dump($data);
        $oldAddress = $this->s_address->search($id);
        if (isset($oldAddress['uid']) && $oldAddress['uid'] != $this->uid) {
            $this->error($this->translate['no_acccess']);
        }

        if ($isDefault) {
            $this->s_address->updates(['is_default' => 'N'], ['uid' => $this->uid]);
        }

        if ($oldAddress) {
            if ($this->s_address->update($id, $data)) {
                $this->success();
            }
        } else {

            if ($this->s_address->save($data)) {
                $this->success();
            }
        }

        $this->error();
    }

    public function removeAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        if (!$this->beforeRemove()) {
            $this->error($this->translate['doerror']);
        }

        $id = $this->getValue('id', true);

        if (empty($id)) {
            $this->error($this->translate['doerror']);
        }

        if (!$this->service->update($id, ['is_delete' => 1])) {
            $this->error($this->translate['nodata']);
        }

        $data = ['id' => $id];
        if (!$this->afterRemove($data)) {
            $this->error($this->translate['doerror']);
        }

        $this->success($data);
    }
}


