<?php

namespace Item;

use C\L\WebUserController;

class IamController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_iam;

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'addtime', 'uptime', 'back_time'
        ];
        $this->pubSearchKeys = [
            'month'
        ];
    }

    protected function beforeSearch()
    {
        $this->params['page_num'] = 500;
        $this->params['data']['uid'] = $this->uid;
        if (isset($this->params['data']['month'])) {
            $month = $this->params['data']['month'];
            list($year, $month) = explode('-', $month);
            unset($this->params['data']['month']);
            $t = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $stime = mktime(0, 0, 0, $month, 1, $year);
            $etime = mktime(23, 59, 59, $month, $t, $year);
            $this->params['data']['back_time'] = [$stime, $etime];
            $this->params['data_type']['back_time'] = 'between';
        }
        return true;
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $il = $this->s_il->search($item['cid']);
            $item['item_name'] = isset($il['name']) ? $il['name'] : '';
            $item['back_time_date'] = isset($item['back_time']) ? date('Y-m-d H:i:s', $item['back_time']) : '';
        
            //$item['show_apr_money'] = number_format($item['apr_money'] );
            $item['show_apr_money'] = number_format($item['apr_money'] ,2,".",",");
        }

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
        foreach ($list as $key => &$value) {
            
            if ($value['title'] == 'stationing' ){
                $value['title'] = 'Geração de energia';
            }            
        }
  
        
        if (!$this->afterSearch($data)) {
            $this->error($this->translate['request_error']);
        }

        $this->success($data);
    }
    

}


