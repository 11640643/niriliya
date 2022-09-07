<?php

namespace C\S\Article;

use C\L\Service;

class Cat extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\ArticleCat();
    }

    public function getStatusConfig()
    {
        return [
            'type' => [
                'none' => $this->translate['cat_type_none'],
                'ad' => $this->translate['cat_type_ad'],
                'about' => $this->translate['cat_type_about']
            ]
        ];
    }
}
