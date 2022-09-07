<?php

namespace Article;
use C\L\WebUserController;
class BusinesscoolistController extends WebUserController
{
    
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
	  $this->error($this->translate['param_error']);
	}
	$uid = $this->uid;
	if($this->s_business_coo_list->apply($name,$company,$city,$status,$tel,$uid,$pid)){
	  $this->success($this->translate['success']);	 
	}
	$this->error($this->message->getSerMsg());
    }
}


