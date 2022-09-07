<?php

namespace Goods;

use C\L\WebUserController;
use C\L\Controller;
class InfoController extends Controller
{
    protected function init()
    {
        $this->service = $this->s_goods;

        $this->pubSearchKeys = [
            'cat_id'
        ];
        $this->language_fields = [
            'name'
        ];
        $this->language_fields_list = [
            'title'
        ];
    }

    protected function afterSearch(&$data)
    {
        #$data['credit'] = $this->s_user->getValue('credit', $this->uid);
        $data['config']['cats'] = $this->s_gcat->searchPage(false, false, ['id', 'name','name_en','name_yn']);
        if($this->language != 'cn'){
            foreach($data['config']['cats'] as &$v){
                foreach($this->language_fields as $field){
                    $v[$field] = $v[$field.'_'.$this->language];
                }
            }
        }
        if($this->language != 'cn'){
            foreach($data['list'] as &$v){
                foreach($this->language_fields_list as $field){
                    $v[$field] = $v[$field.'_'.$this->language]?$v[$field.'_'.$this->language]:$v[$field];
                }
                $v['credit'] = intval($v['credit']);
            }
        }


        return true;
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


    public function afterView(&$data)
    {

        $data['view']['thumbs'] = empty($data['view']['thumbs']) ? [] : json_decode($data['view']['thumbs'], true);
        if($this->language != 'cn'){
                foreach($this->language_fields_list as $field){
                    $data['view'][$field] = $data['view'][$field.'_'.$this->language]?$data['view'][$field.'_'.$this->language]:$$data['view'][$field];
                }
            
        }
        return true;
    }

    protected function beforeSearch()
    {
        $this->params['page_num'] = 500;
        $order = $this->getValue('order', false, 'string');
        $catId = $this->getValue('cat_id', false, 'int');
        if (!empty($catId)) {
            $this->params['data']['cat_id'] = $catId;
        }

        // $this->params['data']['status'] = 'Y';
        //$this->params['order'] = empty($order) ? 'sort desc' : $order;
         $this->params['order'] = 'sort desc';	
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

    public function configAction()
    {
        $data = [
            #'credit' => $this->s_user->getValue('credit', $this->uid),
            'cats' => $this->s_gcat->searchAll(false, false, ['id', 'name','name_en','name_yn']),
        ];
        if($this->language != 'cn'){
            foreach($data['cats'] as &$v){
                foreach($this->language_fields as $field){
                    $v[$field] = $v[$field.'_'.$this->language];
                }
            }
        }
        $this->success($data);
    }

}


