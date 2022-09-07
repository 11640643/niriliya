<?php

namespace Item;

use C\C\AdmController;

class DqController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_item;

        $this->pubSearchKeys = [
            'status', 'type'
        ];

        $this->likeSearchKeys = [
            'name'
        ];
   

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'end_time'
        ];

        $this->createKeys = [
            'name','name_en','name_yn','stock','rem_count', 'min_money', 'max_money', 'schedule', 'is_low_risk', 'apr', 'days', 'type', 'max_count', 'status', 'sort', 'thumb', 'is_show_index', 'item_desc','item_desc_en','item_desc_yn', 'money', 'pack', 'top_apr', 'begin_time', 'pack_money', 'pack_max_num', 'prize_num', 'if_open_vouchers', 'vouchers_money'
        ];

        $this->updateKeys = [
            'name','name_en','name_yn', 'stock','rem_count','min_money', 'max_money', 'schedule', 'is_low_risk', 'apr', 'days', 'type', 'max_count', 'status', 'sort', 'thumb', 'is_show_index', 'item_desc','item_desc_en','item_desc_yn', 'money', 'pack', 'top_apr', 'begin_time', 'pack_money', 'pack_max_num', 'prize_num', 'if_open_vouchers', 'vouchers_money'
        ];
    }

    protected function beforeGetlist()
    {
        $this->params['order'] = 'sort desc';
        return true;
    }

    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $sql = " SELECT count(id) as buy_count,sum(`money`) as `money`  from item_list WHERE cid='" . $item['id'] . "'";

  
            $data1 = $this->di['db']->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
     

            $item['sum_money'] = $data1[0]['money']!=''?$data1[0]['money']:0;
	        $item['buy_count'] = $data1[0]['buy_count'];


	    $item['rem_count'] = isset($item['stock']) && $item['stock'] >0 ? $item['stock'] - $item['buy_count']:0;
       	    $item['schedule'] = isset($item['stock']) && $item['stock'] >0 ? 100*bcdiv($item['buy_count'],$item['stock'],2).'%':'10%';
	
	   }

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

    protected function beforeCreate(&$data)
    {
        $this->rule($data);
        return true;
    }

    protected function beforeUpdate(&$data)
    {
        $this->rule($data);
        return true;
    }

    private function rule($data)
    {
        if ($data['days'] <= 0) {
            $this->error('投资天数不能为0或小于0的数');
        }

        /*if($data['money'] <= 0){
            $this->error('项目金额不能为小于等于0');
        }*/

        if (bcmul($data['apr'], $data['min_money'] / 100, 2) == 0) {
            $this->error('当前利率 * 最小金额 不能为0');
        }

        if ($data['type'] == 'B' && $data['days'] % 7 != 0) {
            $this->error('投资类型为周返，则投资天数应为7的倍数');
        }

        if ($data['type'] == 'C' && $data['days'] % 30 != 0) {
            $this->error('投资类型为周返，则投资天数应为30的倍数');
        }

//        if($data['money'] / $data['min_money'] > 10000){
//            $this->error('为了保证进度能正常使用，项目金额最大为最小投资金额的10000分之1');
//        }
    }

    public function closeAction()
    {
        $ids = $this->getValue('id', true);
        if($this->s_item->update($ids, ['status' => 'N'])){
            $this->success();
        }
        $this->error();
    }

    public function backmoneyAction()
    {
        $id = $this->getValue('id', true, 'int');

        if ($this->s_item->backMoney($id)) {
            $this->success();
        }

        $this->error();
    }
    /**
     * 编辑 
     */
    public function editAction($id=0)
    {   
        if ( \C\P\Helper::isPUT() ){
            if (empty($this->service) ) {
                $this->error();
            }

            $this->params = [
                'update' => [],
            ];
            $update = $this->getValue('data');
            $id = isset( $update['id'] )? $update['id'] :'' ;
            unset($update['id']);
            if (empty($id)) {
                $id = $this->params;
            }

            if (!$this->beforeUpdate($update)) {
                $this->error($this->translate['doerror']);
            }

            $data = ['id' => $id];
            if (empty($update)) {
                $this->success($data);
            }

            $update = $this->setUpdateVlaue2($update);
            unset($update['is_show_index_name']);
            unset($update['if_open_vouchers_name']);

            if (!$this->service->update($id, $update)) {
                $this->error($this->translate['nodata']);
            }

            if (!$this->afterUpdate($data)) {
                $this->error($this->translate['doerror']);
            }

            $this->success($data);
            
        }

        if (empty($this->service)) {
            $this->error();
        }
        if (!$this->beforeView()) {
            $this->error($this->translate['doerror']);
        }
        if (empty($id)) {
            $this->error($this->translate['nodata']);
        }
        if ( !is_numeric( $id )){ $this->error($this->translate['nodata']);}
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

    /**
     * 创建 项目
     * 
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

    /*
     * 获取 配置信息
     */
    public function configAction()
    {
        if (empty($this->service)) {
            $this->error();
        }

        $this->success($this->service->getStatusConfig());
    }


}


