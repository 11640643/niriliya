<?php

namespace C\C;

class AdmController extends Controller
{
    protected function checkLogin()
    {
        if (!$this->uid) {
            $this->error($this->translate['login_first'], 403);
        }
    }   
}
