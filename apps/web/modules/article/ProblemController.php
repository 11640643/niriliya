<?php

namespace Article;

use C\L\Controller;
class ProblemController extends Controller
{

    protected function init()
    {
        $this->service = $this->s_problem;
	$this->showKeys = [
            'id', 'title', 
        ];
        $this->hideKeys = [
            'is_delete'
        ];
    }

    public function beforeSearch()
    {
	/*$id = $this->getValue('id',false,'int');
	if($id){
	  $this->params['data']['id'] = $id;
	}*/
        $this->params['page_num'] = 100;
        $this->params['order'] = 'sort asc';
	$this->params['field'] = ['id','title'];
        return true;
    }

    /*public function afterSearch(&$data){

	foreach($data['list'] as $key=>&$val){
	    $val['answer_list'] = $this->s_problem_answer->searchAll(['pid'=>$val['id']],[],['content']);
	}
	$this->success($data);
    }*/

    
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


