<?php

namespace Article;

use C\L\Controller;

class AboutController extends Controller
{

    protected function init()
    {
        $this->service = $this->s_article;

        $this->hideKeys = [
            'is_delete'
        ];
        $this->language_fields = [
          'title'
      ];
    }

    public function beforeSearch()
    {
        $this->params['page_num'] = 100;
	$type = $this->getValue('type',false,'string');
	$this->params['data']['cid'] = 2;
	if(isset($type) && $type=='focus'){
	  $this->params['data']['cid'] = 3;
	}elseif(isset($type) && $type=='index_article_config'){
	  $this->params['data']['cid'] = 4;	
	}elseif(isset($type) && $type=='notice'){
	  $this->params['data']['cid'] = 5;		
	}
        $this->params['data']['is_disable'] = 'Y';
        $this->params['order'] = 'sort desc';
        return true;
    }
    protected function afterSearch(&$data)
    {

        //多语言设置
        // 配置需要转换成站点语言的字段配置
        if($this->language != 'cn'){
            foreach ($data['list'] as &$item) {
                foreach($this->language_fields as $v){
                    $item[$v] = $item[$v.'_'.$this->language]?$item[$v.'_'.$this->language]:$item[$v];
                }
            }
        }
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

    public function detailAction()
    {
        $code = $this->getValue('code', true, 'string');
        $article = $this->s_article->search(['code' => $code, 'is_disable' => 'Y'], [], ['title', 'content']);
        if ($article) {
            $this->success($article);
        }

        $this->error($this->translate['content_empty']);
    }
}


