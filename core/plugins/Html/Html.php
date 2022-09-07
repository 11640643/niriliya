<?php

namespace C\P\Html;


class Html
{
    public function __construct()
    {
        require 'simple_html_dom.php';
    }

    public function init($str)
    {
        return str_get_html($str);
    }

}