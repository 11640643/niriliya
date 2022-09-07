<?php

namespace C\S\Item;

use C\L\Service;

class ItemAprMoney extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\ItemAprMoney();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'Y' => $this->translate['new_lang_status_4'],
                'D' => $this->translate['stationing']
            ],
            'type' => [
                'A' => $this->translate['item_type_a'],
                'B' => $this->translate['item_type_b'],
                'C' => $this->translate['item_type_c'],
                'D' => $this->translate['item_type_d'],
                'E' => $this->translate['item_type_e'],
            ],
        ];
    }
}
