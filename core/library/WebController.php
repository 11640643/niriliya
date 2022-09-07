<?php

namespace C\L;

class WebController extends Controller
{
    protected function checkMaintain()
    {
        // $translate =  $this->getTranslation();
        if ($this->cache->get('web_is_maintain')) {
            $this->error($this->translate['loading'], 406);
        }
        return true;
    }
}
