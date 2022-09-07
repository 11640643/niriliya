<?php

class FundsTask extends \C\L\Task
{
    //流水队列
    public function runAction()
    {
        #$this->runCheck('funds_run');
        $i = 0;
        while ($i<5) {
            $funds = $this->s_funds->searchPage(['status' => 'D'], [], [], 'id asc', 1, 50);
            foreach ($funds as $item) {
                try {
                    $moneyArray = $this->s_user->lockUpdate($item['uid'], $item['money'], $item['type']);
                    if ($moneyArray === false) {
                        throw new Exception($this->message->getSerMsg());
                    }
                    $update = [
                        'status' => 'Y',
                        'befor_money' => empty($moneyArray[0])?0:$moneyArray[0],
                        'after_money' => $moneyArray[1],
                    ];
                    $this->db->begin();
                    if (!$this->s_funds->update($item['id'], $update)) {
                        throw new Exception('更新funds失败');
                    }
                    $this->db->commit();
                    $this->logs(['msg' => '成功', 'id' => $item['id']]);

                } catch (Exception $e) {
                    if ($this->db->isUnderTransaction()) {
                        $this->db->rollback();
                    }
                    $this->logs(['msg' => $e->getMessage(), 'id' => $item['id']]);
                }
            }
            $i++;
            sleep(3);
        }
        
    }
}