<?php

namespace C\S\Article;

use C\L\Service;

class Article extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\ArticleInfo();
    }

    public function getStatusConfig()
    {
        return [
            'is_disable' => [
                'Y' => $this->translate['is_disable_y'],
                'N' => $this->translate['is_disable_n']
            ]
        ];
    }
}
