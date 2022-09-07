<?php

namespace C\S\Clo;

use C\L\Service;

class CloList extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\CloList();
    }


    //用户签到
    public function clo($uid, $cloId, $money)
    {
        try {

            $clo = $this->di['s_clo']->get([
                'id' => $cloId
            ]);

            $minMoney = 1;
            if ($money < $minMoney) {
                throw new \Exception($this->translate['new_lang_money_min_1']. $minMoney);
            }

            $maxMoney = 2;
            if ($money > $maxMoney) {
                throw new \Exception($this->translate['new_lang_money_max_1'] . $maxMoney);
            }

            if (empty($clo)) {
                throw new \Exception($this->translate['new_lang_activity_1']);
            }

            if($this->get(['uid' => $uid, 'clo_id' => $cloId])){
                throw new \Exception($this->translate['new_lang_week_1']);
            }

            //第二道防
            $key = 'clo_' . date('Ymd') . '_' . $uid;
            if ($this->di['cache']->get($key)) {
                throw new \Exception($this->translate['']);
            }
            $this->di['cache']->set($key, true, 24 * 3600);

            $add = [
                'clo_id' => $cloId,
                'uid' => $uid,
                'money' => $money,
                'date' => time(),
            ];
            if (!$this->save($add)) {
                throw new \Exception($this->translate['add_error']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'S' => $this->translate['new_lang_status_1'],
                'D' => $this->translate['new_lang_status_2'],
                'C' => $this->translate['new_lang_status_3'],
                'Y' =>$this->translate['new_lang_status_4'],
                'N' => $this->translate['has_unable'],
                'X' => $this->translate['new_lang_status_6']
            ],
        ];
    }
}


