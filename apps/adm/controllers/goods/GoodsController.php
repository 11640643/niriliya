<?php

namespace Goods;

use C\C\AdmController;

class GoodsController extends AdmController
{
    protected function init()
    {
        $this->service = $this->s_goods;

        $this->keyworkSearchKeys = [
            'title'
        ];

        $this->pubSearchKeys = [
            'cat_id', 'status'
        ];

        $this->hideKeys = [
            'is_delete'
        ];

        $this->timeToDateKeys = [
            'uptime', 'addtime', 'ok_time'
        ];

        $this->createKeys = [
            'title','title_en','title_yn', 'credit', 'cat_id', 'thumb', 'thumbs', 'content', 'content_en', 'content_yn', 'sort', 'is_show_index', 'max_shop_num', 'status'
        ];

        $this->updateKeys = [
            'title','title_en','title_yn', 'credit', 'cat_id', 'thumb', 'thumbs', 'content', 'content_en', 'content_yn', 'sort', 'is_show_index', 'max_shop_num', 'status'
        ];
    }

    public function beforeGetlist()
    {
        $keywordSearchValue = $this->getValue('keyword_search_value', false, 'string');
        if (!empty($keywordSearchValue)) {
            $this->params['data']['title'] = $keywordSearchValue;
            $this->params['data_type']['title'] = 'like';
        }
        $cats = $this->getValue('cats',false,'int');
        if ($cats !=''){
            $this->params['data']['cat_id'] = $cats;
        }
        $this->params['order'] = 'sort desc, id desc';

        return true;
    }

    protected function afterGetlist(&$data)
    {
        foreach ($data['list'] as &$item) {
            $item['thumbs'] = empty($item['thumbs']) ? [] : json_decode($item['thumbs'], true);
            $cat = $this->s_gcat->search($item['cat_id']);
            $item['cat_name'] = empty($cat) ? '' : $cat['name'];
        }
        $data['config']['cats'] = $this->s_gcat->searchPage(false, false, ['id', 'name']);
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


    protected function afterView(&$data)
    {
        $thumbs = json_decode($data['view']['thumbs'], true);
        $data['view']['thumbs'] = $thumbs ? : [];
        $data['config']['cats'] = $this->s_gcat->searchPage(false, false, ['id', 'name']);
        return true;
    }


    protected function beforeUpdate(&$data)
    {   
        $params = $this->request->getPost('data') ;
      
        if (!$this->getValue2('id')) {
            $this->error('????????????', 1);
        }
        if (isset($data['thumbs'])) {
            $data['thumbs'] = is_array($data['thumbs']) ? json_encode($data['thumbs']) : '';
        }
        $data['content'] =$params['content'];
        return true;
    }


    /**
     * ?????? ??????
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


    protected function beforeCreate(&$data)
    {
        $data['content'] = $this->request->getPost('content');
        if (isset($data['thumbs'])) {
            $data['thumbs'] = is_array($data['thumbs']) ? json_encode($data['thumbs']) : '';
        }
        return true;
    }

    /**
     *  ??????????????????
     *
     */
    public function configAction()
    {
        $this->success([
            'status' => $this->s_gcat->searchPage(false, false, ['id', 'name'])
        ]);
    }

    /**
     * ?????? ????????????
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
     * ??????
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


