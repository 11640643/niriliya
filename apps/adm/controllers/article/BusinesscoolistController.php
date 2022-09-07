<?php

namespace Article;
use C\C\AdmController;
class BusinesscoolistController extends AdmController
{
   protected function init()
    {
        $this->service = $this->s_business_coo_list;
        /*$this->showKeys = [
            'id', 'thumb','name','tel','email','status'
        ];*/
        $this->hideKeys = [
            'is_delete'
        ];
         $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->updateKeys = [
            'pid', 'name','company','city','tel','status'
        ];

        $this->createKeys = [
	    'pid', 'name','company','city','tel','status' 	
        ];
    } 
    public function beforeSearch()
    {
        $pid = $this->getValue('pid',false,'int');
        if($pid){
          $this->params['data']['pid'] = $id;
        }
        $this->params['page_num'] = 100;
        $this->params['order'] = 'addtime desc';
        return true;
    }
	

    public function applyAction()
    {
	$name = $this->getValue('name',true,'string');
	$company = $this->getValue('company',true,'string');
	$city = $this->getValue('city',true,'string');
   	$status = $this->getValue('status',true,'string');
	$tel = $this->getValue('tel',true,'string');
	$pid = $this->getValue('pid',true,'string');
	#var_dump($this->s_business_coo_list->getStatusConfig()['status']);exit;
	if(!array_key_exists($status,$this->s_business_coo_list->getStatusConfig()['status'])){
	  $this->error('参数错误');
	}
	$uid = $this->uid;
	if($this->s_business_coo_list->apply($name,$company,$city,$status,$tel,$uid,$pid)){
	  $this->success('成功');	 
	}
	$this->error($this->message->getSerMsg());
    }
}


