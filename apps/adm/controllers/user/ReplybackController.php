<?php

namespace User;

use C\C\AdmController;

class ReplybackController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_replyback;
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        #$this->pubSearchKeys = [
        #    'type', 'stype', 'month'
        #];
    }

    protected function beforeGetlist()
    {
	$page_num = $this->getValue('page_num', false, 'int') ?? 10;
        #$this->params['data']['status'] = 'Y';
	$this->params['order'] = 'addtime desc';
	$this->params['page_num'] = $page_num;
       /* $type = $this->getValue('type', false, 'string');
        $this->params['data']['type'] = $type ?? 'money';
        $this->params['page_num'] = 10000;
        if (isset($this->params['data']['stype']) && $this->params['data']['stype'] == 'checkin') {
            $this->params['data']['stype'] = ['checkin', 'cumulative_sign'];
            $this->params['data_type']['stype'] = 'in';
        }
        if (isset($this->params['data']['month'])) {
            $month = $this->params['data']['month'];
            list($year, $month) = explode('-', $month);
            unset($this->params['data']['month']);
            $t = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $stime = mktime(0, 0, 0, $month, 1, $year);
            $etime = mktime(23, 59, 59, $month, $t, $year);
            $this->params['data']['addtime'] = [$stime, $etime];
            $this->params['data_type']['addtime'] = 'between';
        }*/
        return true;
    }
    public function replyAction(){
	
	$content = $this->getValue('reply_content',true,'string');
	$reply_id = $this->getValue('reply_id',true,'int');
	$reply_time = date("Y-m-d H:i:s");
	if($res = $this->s_replyback->reply($content,$reply_time,$reply_id)){
		$this->success();
	}
	$this->error();
    }
    /*protected function afterSearch(&$data)
    {
	
	
        #系统充值title显示
        foreach($data['list'] as $key=>$val){
	  $data['list']
        }

        $uid = $this->uid;
        $data['value'] = $type = $this->getValue('type') == 'money' ? $this->s_user->getValue('money', $uid) : $this->s_user->getValue('credit', $uid);
        
        return true;
    }*/

    protected function afterGetlist(&$data)
    {
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


}


