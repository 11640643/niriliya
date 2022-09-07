<?php

namespace Article;

use C\C\AdmController;

class ArtController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_article;
        $this->likeSearchKeys = [
            'title',
        ];
        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime'
        ];

        $this->updateKeys = [
            'pubtime','title','title_en','title_yn', 'content','content_en','content_yn', 'is_disable', 'sort', 'cid', 'icon','thumb','is_show_index'
        ];

        $this->createKeys = [
            'pubtime','title','title_en','title_yn', 'content','content_en','content_yn', 'is_disable', 'sort', 'cid', 'icon','thumb','is_show_index'
        ];
    }

    protected function beforeGetlist()
    {
        $keywordSearchValue = $this->getValue('keyword_search_value', false, 'string');
        if (!empty($keywordSearchValue)) {
            $this->params['data']['title'] = $keywordSearchValue;
            $this->params['data_type']['title'] = 'like';

        }
        $this->params['order'] = 'sort desc,id desc';
        $type = $this->getValue('type', false, 'string');

        $this->params['data']['cid'] = 1;
        if($type == 'about'){
            $this->params['data']['cid'] = 2;
        }elseif($type == 'focus'){
	        $this->params['data']['cid'] = 3; 	
    	}elseif($type == 'index_article_config'){
    	    $this->params['data']['cid'] = 4;	
    	}elseif($type=='notice'){
    	    $this->params['data']['cid'] = 5;	
    	}elseif($type='none'){
            //$this->params['data']['cid'] = 1;   
            //
            unset($this->params['data']['cid'] );
        }

            return true;
    }

    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $cat = $this->s_acat->search($item['cid']);
            if (count( $cat  )  > 0){
                $item['cat_name'] = $cat['name'];
                $item['cat_type_name'] = $this->s_acat->getStatusConfig()['type'][$cat['type']];
                $item['url'] = '/art/' . $item['code'];
            }
        }
        return true;
    }


    public function GetlistAction()
    {


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


    protected function beforeUpdate(&$data)
    {
        // if (empty($data['cid'])) {
        //     $this->error('必须选择分类');
        // }
        // $data['content'] = $this->request->getPost('content');
        return true;
    }

    protected function beforeCreate(&$data)
    {
        if (empty($data['cid'])) {
            $this->error('必须选择分类');
        }
        $data['content'] = $this->request->getPost('content');
        return true;
    }

    protected function afterCreate(&$data)
    {
        $this->s_article->update($data['id'], ['code' => substr(md5($data['id']), 0, 6)]);
        return true;
    }

    public function configAction()
    {
        $this->success([
            'cats' => $this->s_acat->searchAll([], [], ['id', 'name'])
        ]);
    }


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

    public function editAction($id=0)
    {   
  
        if ( \C\P\Helper::isPOST()  && $_POST['content'] !='' ){
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

            $update = $_POST;
            
  
           if (empty($update)) {
                $this->success($data);
            }
            $id = $update['id'];
            unset($update['id']);
            unset($update['is_show_index_name']);
            unset($update['is_disable_name']);
            unset($update['uptime_date']);
            unset($update['addtime_date']);
            unset($update['addtime']);
            unset($update['uptime']);
            unset($update['pubtime']);
 
            if (!$this->service->update($id, $update)) {
                $this->error($this->translate['nodata']);
            }

            if (!$this->afterUpdate($data)) {
                $this->error($this->translate['doerror']);
            }

            $this->success($data);
            
        }

        if ( $_POST['data']['content'] !='' ){
            
   
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

            unset($update['id']);
            unset($update['is_show_index_name']);
            unset($update['is_disable_name']);
            unset($update['uptime_date']);
            unset($update['addtime_date']);
            unset($update['addtime']);
            unset($update['uptime']);
            unset($update['pubtime']);

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


}


