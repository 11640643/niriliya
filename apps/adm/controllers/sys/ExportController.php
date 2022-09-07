<?php

namespace Sys;

use C\C\AdmController;

class ExportController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_export;
        $this->timeToDateKeys = [
            'uptime', 'addtime',
        ];
    }
    /* 
     * 搜索前
     */
    protected function beforeGetlist()
    {
        return true;
    }

    /**
     * 搜索后
     */
    protected function afterGetlist(&$data)
    {
        $data['export_file_loading'] = $this->cache->get('start_user_csv') > 0 ? true : false;
        return true;
    }

    public function getlistAction()
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

        if (!$this->beforeGetlist()) {
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
        if (!$this->afterGetlist($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }


    public function exportAction()
    {
        $isLoading = false;
        $type = $this->getValue('type', true, 'string');
        if ($type == 'user') {

            if ($this->cache->get('start_user_csv') > 0) {
                $isLoading = true;
            } else {

                $this->cache->set('start_user_csv', 2);
            }

        }

        $this->success([
            'is_loading' => $isLoading
        ]);
    }

    public function exportStatusAction()
    {
        $isLoading = false;
        $type = $this->getValue('type', true, 'string');

        if ($type == 'user') {

            if ($this->cache->get('start_user_csv') > 0) {
                $isLoading = true;
            }
        }

        $this->success([
            'is_loading' => $isLoading,
        ]);
    }
}


