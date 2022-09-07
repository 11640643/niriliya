<?php

namespace C\S\Pay;

use C\L\Service;

class PayOrder extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\PayOrder();
    }

    public function save($data)
    {
        if (!$id = $this->model->add($data)) {
            return false;
        }
        return $id;
    }   
    
    public function test(){
        echo 1234;
        exit();
    }
}