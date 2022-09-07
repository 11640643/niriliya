<?php

namespace Article;
use C\C\AdmController;
class ProblemController extends AdmController
{

    protected function init()
    {
        $this->service = $this->s_problem;
	    $this->showKeys = [
            'id', 'title','sort', 
        ];
        $this->hideKeys = [
            'is_delete'
        ];
	   $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->updateKeys = [
            'title', 'sort'
        ];

        $this->createKeys = [
            'title', 'sort'
        ];
    }

    public function beforeGetlist()
    {
	/*$id = $this->getValue('id',false,'int');
	if($id){
	  $this->params['data']['id'] = $id;
	}*/
        $this->params['page_num'] = 100;
        $this->params['order'] = 'sort asc';
        return true;
    }

    /*public function afterSearch(&$data){

	foreach($data['list'] as $key=>&$val){
	    $val['answer_list'] = $this->s_problem_answer->searchAll(['pid'=>$val['id']],[],['content']);
	}
	$this->success($data);
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

    /**
     * 编辑与更新
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
            unset($update['is_disable_name']);

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
     * 创建 问题类目详情
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


