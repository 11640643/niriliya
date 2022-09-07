<?php

namespace User;

use C\L\AdmController;

class NoticereadController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_noticeread;

        $this->hideKeys = [
            'is_delete'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['data']['uid'] = $this->uid;
        $this->params['page_num'] = 500;
        return true;
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as $k => &$item) {
            $notice = $this->s_notice->search($item['cid']);
            if(empty($notice)){
                $this->s_noticeread->update($item['id'], ['is_delete' => 1]);
                unset($data['list'][$k]);
                continue;
            }
            $item['name'] = $notice['name'];
            $item['addtime'] = $notice['addtime'];
            $item['is_read'] = $item['read_time'] > 0 ? true : false;
            $item['content'] = $notice['content'];
        }
        $data['no_read_num'] = $this->s_noticeread->getCount(['uid' => $this->uid, 'read_time' => 0]);
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
        $read = $this->s_noticeread->search(['uid' => $this->uid, 'cid' => $data['view']['id']]);
        if ($read && $read['read_time'] == 0) {
            $this->s_noticeread->update($read['uid'], ['read_time' => time()]);
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

}


