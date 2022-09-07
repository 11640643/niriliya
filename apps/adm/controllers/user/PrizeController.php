<?php

namespace User;

use C\C\AdmController;

class PrizeController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_prize;

        $this->keyworkSearchKeys = [
            'mobile', 'name'
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->updateKeys = [
            'status'
        ];

        $this->pubSearchKeys = [
            'status', 'is_show_index'
        ];

        $this->likeSearchKeys = [
            'jp_name'
        ];

        $this->createKeys = [
            'name', 'mobile', 'jp_name', 'is_show_index', 'status'
        ];

        $this->updateKeys = [
            'name', 'mobile', 'jp_name', 'is_show_index', 'status'
        ];
    }

    public function setShowsAction()
    {
        $ids = $this->getValue('ids', true, 'trim');
        $type = $this->getValue('type', true);
        if (empty($ids)) {
            $this->success();
        }

        if(!in_array($type, ['Y', 'N'])){
            $this->error('未选择类型');
        }

        if ($this->s_prize->updates(['is_show_index' => $type], ['id' => $ids], ['id' => 'in'])) {
            $this->success();
        }

        $this->error();
    }

    protected function beforeGetlist()
    {
        $keywordSearchValue = $this->getValue('keyword_search_value', false, 'string');
        if (!empty($keywordSearchValue)) {
            $this->params['data']['name'] = $keywordSearchValue;
            $this->params['data_type']['name'] = 'like';
            $this->params['data']['mobile'] = $keywordSearchValue;
            $this->params['data_type']['mobile'] = 'like';
        }


        $jp_name = $this->getValue('jp_name',false,'string');
        if ($jp_name !=''){
            $this->params['data']['jp_name'] = $jp_name;
        }
        $is_show_index = $this->getValue('is_show_index',false,'string');
        if ($is_show_index !=''){
            $this->params['data']['is_show_index'] = $is_show_index;
        }
         $status = $this->getValue('status',false,'string');
        if ($status !=''){
            $this->params['data']['status'] = $status;
        }       
      
        return true;
    }

    protected function afterGetlist(&$data)
    {
        return true;
    }

    public function GetlistAction()
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

    /**
     * 编辑与修改
     */
    public function editAction($id=0)
    {   
        if ( \C\P\Helper::isPUT() ){
            if (empty($this->service)) {
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


    /**
     * 删除
     */
    public function deleteAction($id = 0){
        if (empty($this->service)) {
            $this->error();
        }

        if (!$this->beforeRemove()) {
            $this->error($this->translate['doerror']);
        }

        if ( !is_numeric( $id )){ $this->error($this->translate['nodata']);}
        

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

    /**
     * 创建
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

    /**
     * 删除
     */

}


