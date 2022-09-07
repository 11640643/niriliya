<?php

namespace C\S\Pack;

use C\L\Service;

class Pack extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\Pack();
    }


    public function getStatusConfig()
    {
        return [
            'status' => [
                'D' =>  $this->translate['new_lang_status_1'],
                'S' =>  $this->translate['new_lang_status_3'],
                'Y' =>  $this->translate['new_lang_status_4'],
            ],
            'is_disable' => [
                'Y' => $this->translate['new_lang_status_7'],
                'N' => $this->translate['new_lang_status_8']
            ]
        ];
    }



    public function apply($uid, $key)
    {
        try {

            $time = strtotime('now +1 day');
            $date = date('Ymd', $time);

            $lockKey = "uid:$uid:pack";
            if (!$this->di['s_user']->lock($lockKey, 10)) {
                throw new \Exception($this->translate['do_later'] ) ;
            }

            $pack = $this->search(['no_date' => $date]);
            if (empty($pack) || !key_exists($key, $pack) || $pack[$key] == 0) {
                throw new \Exception($this->translate['new_lang_status_9']);
            }

            $no = substr($key, 13, 1);
            if ($this->di['s_packlist']->search(['uid' => $uid, 'no_date' => $date])) {
                throw new \Exception($this->translate['new_lang_status_10']);
            }

            $money = $pack['set_task_money' . $no];

            $add = [
                'uid' => $uid,
                'pack_id' => $pack['id'],
                'no_date' => $date,
                'money' => $money,
                'apr' => $pack['set_task_apr' . $no],
                'set_step' => $pack[$key],
                'step_task_key' => $key,
                'not_apr' => $pack['not_apr'],
                'min_money' => $pack['set_task_money' . $no],
            ];

            $this->di['db']->begin();

            $moneyArray = $this->di['s_user']->lockUpdate($uid, -$money, 'money');
            if ($moneyArray === false) {
                throw new \Exception($this->di['message']->getSerMsg());
            }

            if (!$cid = $this->di['s_packlist']->save($add)) {
                throw new \Exception($this->translate['new_lang_sys_busy']);
            }

            if (!$this->di['s_funds']->add($uid, -$money, 'money', 'pack_apply', $this->translate['active_type_sport'] . "-" . date('md', $time), $cid, 'Y', $moneyArray[0], $moneyArray[1])) {
                throw new \Exception($this->translate['new_lang_sys_busy']);
            }

            $this->di['db']->commit();
            return true;

        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());

            return false;
        }
    }
}
