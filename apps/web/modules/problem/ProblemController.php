<?php

namespace Problem;

use C\L\Controller;
use C\L\WebUserController;
class ProblemController extends WebController
{

    protected function init()
    {
        $this->service = $this->s_problem;

        $this->hideKeys = [
            'is_delete'
        ];
    }

    public function beforeSearch()
    {
        //$this->params['page_num'] = 100;
        $this->params['order'] = 'sort desc';
        return true;
    }

    public function afterSearch(&$data){
	foreach($data as $key=>$val){
	    $val['answer_list'] = $this->s_problem_answer->search(['pid'=>$val['id'],['content']);
	}
	return true;
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


