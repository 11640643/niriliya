<?php

namespace Article;

use C\L\Controller;

class InfoController extends Controller
{
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


