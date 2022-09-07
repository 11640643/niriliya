<?php

namespace User;

use C\L\AdmController;

class NoticeController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_notice;

        $this->hideKeys = [
            'is_delete'
        ];
    }

    protected function beforeView()
    {
        $id = $this->getValue('id', true, 'int');
        if (!$this->service->search($id)) {
            $read = $this->s_noticeread->search(['uid' => $this->uid, 'cid' => $id]);
            if ($read) {
                $this->s_noticeread->update($read['id'], ['is_delete' => 1]);
            }
            $this->error($this->translate['content_empty']);
        }
        return true;
    }

    protected function afterView(&$data)
    {
        $read = $this->s_noticeread->search(['uid' => $this->uid, 'cid' => $data['view']['id']]);
        if ($read) {
            $this->s_noticeread->update($read['id'], ['read_time' => time()]);
        }
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
}


