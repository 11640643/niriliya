<?php

namespace C\S\Item;

use C\L\Service;

class ItemAutoApply extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\ItemAutoApply();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'Y' => $this->tranlate['continuous_investment_succeeded'],
                'D' => $this->tranlate['lang_cfg_S'],
                'N' => $this->tranlate['continuous_investment_failed'],
            ],
        ];
    }
}
