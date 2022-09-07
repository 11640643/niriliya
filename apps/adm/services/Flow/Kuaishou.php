<?php

namespace C\S\Flow;

use C\L\Service;

class Kuaishou extends Service
{
    //https://www.9o5x89.com/api/flow/juliang?aid=__AID__&dname=__AID_NAME__&cid=__CID__&os=__OS__&imei=__UUID__&idfa=__IDFA__&ip=__IP__&time=__TS__&call_url=__CALLBACK_URL__

    protected function setModel()
    {
        $this->model = new \C\M\FlowKuaishou();
    }

}
