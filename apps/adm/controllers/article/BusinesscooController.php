<?php

namespace Article;
use C\C\AdmController;
class BusinesscooController extends AdmController
{

    protected function init()
    {
        $this->service = $this->s_business_coo;
	$this->showKeys = [
            'id', 'thumb','name','tel','email','status' 
        ];
        $this->hideKeys = [
            'is_delete'
        ];
	 $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->updateKeys = [
            'sort','thumb', 'name','tel','email','sort','status'
        ];

        $this->createKeys = [
	    'sort','thumb', 'name','tel','email','sort','status'	
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
    public function delAction(){
	$id = $this->getValue('id',true,'int');
	$this->service->update($id, ['is_delete' => 1]);
	$this->success();
    }		
    /*public function afterSearch(&$data){
	
	foreach($data['list'] as $key=>&$val){
	    $val['answer_list'] = $this->s_problem_answer->searchAll(['pid'=>$val['id']],[],['content']);
	}
	$this->success($data);
    }*/
}


