<?php

namespace Item;

use C\L\WebUserController;

class DqController extends WebUserController
{
    protected function init()
    {
        $this->service = $this->s_item;
        $this->hideKeys = [
            'is_delete'
        ];
        $this->language_fields = [
            'name'
        ];
        $this->language_message_fields = [
            'name'
        ];
    }
    		
    public function indexAction()
    {
        $this->checkSetPayPwd();

        $id = $this->getValue('id', true, 'int');
        $item = $this->s_item->search($id);
        $user = $this->s_user->search($this->uid);
        $itemList = $this->s_il->search(['uid'=>$this->uid,'cid'=>$id, 'vouchers_money' => 0], ['vouchers_money' => '>']);
        if($item['if_open_vouchers'] =='N' || ($itemList && abs(intval($itemList['vouchers_money']))>0 )){
            $item['vouchers_money'] = 0;
        }
        $aprHour = '24h';
        if ($item['type'] == 'B' || $item['type'] == 'D') {
            $aprHour = '7x24h';
        }

        if ($item['type'] == 'C') {
            $aprHour = '30x24h';
        }

        if ($item['type'] == 'E') {
            $aprHour = $item['days'] . 'x24小时';
        }
	    $curren_buy_count = $this->s_il->getCount(['cid'=>$item['id']]);

        $item['rem_count'] =  $item['rem_count'] >0 ? $item['rem_count']-$curren_buy_count:0;
        $_SESSION['__token__'] = md5(time());
   

        $kt_miney = $item['min_money']*$item['rem_count'];
	    $data = [
            'money' => $user['money'],
            //'kt_money' => bcmul($item['money'], (100 - $item['schedule']) / 100, 2),
            'kt_money'=>$kt_miney,
	        'min_money' => $item['min_money'],
            'apr_hour' => $aprHour
        ];
        
        $data['__token__'] = $_SESSION['__token__'];
        // 配置需要转换成站点语言的字段配置
        if($this->language != 'cn'){
            foreach($this->language_fields as $v){
                $item['name'] = $item[$v.'_'.$this->language];
            }
        }
        $this->success(array_merge($item, $data));
    }

    public function applyAction()
    {
        $money = $this->getValue('money', true, 'float');
        $itemId = $this->getValue('id', true, 'int');
        $passwd = $this->getValue('passwd', true, 'string');
        $vouchers_money = $this->getValue('vouchers_money', false, 'float');
        $__token__ = $this->getValue('__token__', true, 'string');
        if(!empty($_SESSION['__token__'])){
            if($__token__ != $_SESSION['__token__']){
                $this->error($this->translate['param_error']);
            }
            $_SESSION['__token__'] = null;
        }
        
        if ($cid = $this->s_il->dqapply($this->uid, $money, $itemId, $passwd, $vouchers_money)) {
            $this->success(['cid' => $cid]);
        }

        $this->error($this->message->getSerMsg());
    }

    protected function afterSearch(&$data)
    {
        foreach ($data['list'] as &$item) {
            $item['name'] = $item['apr_money'];
            $item['apr_money'] = bcmul($item['min_money'], $item['apr'] / 100, 2);

            //$item['show_apr_money'] =  number_format($item['apr_money'] );
            //$item['show_min_money'] =  number_format($item['min_money'] );

            $item['show_apr_money'] =  $data['view']['show_apr_money'] =number_format($item['apr_money'] ,2,".",",");
            $item['show_min_money'] =  $data['view']['show_apr_money'] =number_format($item['min_money'],2,".",",");
            $item['show_apr_days_money'] = number_format( $item['apr_money'] * $item['days'] ,2,".",",");

            $config_buy_count = $item['stock']-$item['rem_count'];
            $curren_buy_count = $this->s_il->getCount(['cid'=>$item['id']]);
            $item['rem_count'] =  $item['rem_count'] >0 ? $item['rem_count']-$curren_buy_count:0;
            $item['buy_count'] = $curren_buy_count+$config_buy_count>$item['stock']?$item['stock']:$curren_buy_count+$config_buy_count;
            $item['schedule'] = isset($item['stock']) && $item['stock'] >0?100*bcdiv($item['buy_count'],$item['stock'],4):'10';


             $item['rem_count'] =  $item['rem_count'] >0? $item['rem_count'] : 0 ;
            $item['buy_count'] = $item['buy_count'] >0 ?$item['buy_count'] :$item['stock'];

            // 配置需要转换成站点语言的字段配置
            if($this->language != 'cn'){
                foreach($this->language_fields as $v){
                    $item['name'] = $item[$v.'_'.$this->language];
                }
            }
        }
        return true;
    }

    protected function beforeSearch()
    {
        $this->params['order'] = 'sort desc';
        $this->params['data']['status'] = 'Y';
        $this->params['page_num'] = 500;
        $this->params['data']['begin_time'] = time();
        $this->params['data_type']['begin_time'] = '<=';
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

        //$data['view']['show_apr_money'] = number_format( $data['view']['apr_money'] );
        $data['view']['show_apr_money'] =number_format($data['view']['apr_money'],2,".",",");
        $data['view']['show_min_money'] =number_format($data['view']['min_money'],2,".",",");
        $data['view']['show_money'] =number_format($data['view']['money'],0,".",",");


        $data['view']['show_apr_days_money'] = number_format( $data['view']['apr_money'] * $data['view']['days'] ,2,".",",");

        $data['view']['buy_count']      = $data['view']['buy_count'] >0 ?$data['view']['buy_count'] :$data['view']['stock'];

        
    


        $this->success($data);
    }

    protected function afterView(&$data)
    {
        // var_dump($data);
        #$data['view']['money'] = bcdiv($data['view']['money'], 10000, 2);
        $data['view']['money'] = $data['view']['stock'];
        $data['view']['apr_money'] = bcmul($data['view']['min_money'] / 100, $data['view']['apr'], 2);
        $contract = $this->s_config->get('contract_dq');

        $config_buy_count = $data['view']['stock']-$data['view']['rem_count'];
        $curren_buy_count = $this->s_il->getCount(['cid'=>$data['view']['id']]);
        $data['view']['rem_count'] =  $data['view']['rem_count'] >0 ? $data['view']['rem_count']-$curren_buy_count:0;
        $data['view']['buy_count'] = $curren_buy_count+$config_buy_count>$data['view']['stock']?$data['view']['stock']:$curren_buy_count+$config_buy_count;
        $data['view']['schedule'] = isset($data['view']['stock']) && $data['view']['stock'] >0?100*bcdiv($data['view']['buy_count'],$data['view']['stock'],4):'10';

        $data['view']['contract_name3'] = $contract['name3'];
        //多语言设置
        // 配置需要转换成站点语言的字段配置
        if($this->language != 'cn'){
            foreach($this->language_fields as $v){
                $data['view'][$v] = $data['view'][$v.'_'.$this->language];
            }
        }
   
        $data['view']['rem_count'] = $data['view']['rem_count']>=0? $data['view']['rem_count'] :0;

	/*$curren_count  = $this->s_il->getCount(['id'], ['cid' => $data['view']['id']]);
	$buy_count = $data['view']['rem_count'] ==0?$data['view']['stock']:$data['view']['rem_count']+ ;
	#$buy_count = $this->s_il->getCount(['id'], ['cid' => $item['id']]);
        #$item['rem_count'] = isset($item['stock']) && $item['stock'] >0 ? $item['stock'] - $item['buy_count']:0;
        $data['view']['schedule'] = isset($data['view']['stock']) && $data['view']['stock'] >0 ? 100*bcdiv($buy_count,$data['view']['stock'],2):'10';
	var_dump($item['view']['schedule'],$buy_count,$data['view']['stock']);
	#$data['view']['schedule'] = bcadd($data['view']['schedule'], 0, 2);
//        $itemdq = $this->s_config->get('item_dq');*/
//        $data['view']['11'] = $contract['name3'];
        return true;
    }

    public function calAction()
    {
        $days = $this->getValue('days', true, 'int');
        $type = $this->getValue('type', true, 'string');
        $money = $this->getValue('money', true, 'float');
        $apr = $this->getValue('apr', true, 'float');

        if($days > 2000){
            $this->error($this->translate['max_num']);
        }

        $this->success($this->s_il->cal($money, $apr, $type, $days));
    }

}


