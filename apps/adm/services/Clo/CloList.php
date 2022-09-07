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
                throw new \Exception('最小金额为' . $minMoney);
            }

            $maxMoney = 2;
            if ($money > $maxMoney) {
                throw new \Exception('最大金额为' . $maxMoney);
            }

            if (empty($clo)) {
                throw new \Exception('活动不存在');
            }

            if($this->get(['uid' => $uid, 'clo_id' => $cloId])){
                throw new \Exception('每期只能投注一次');
            }

            //第二道防
            $key = 'clo_' . date('Ymd') . '_' . $uid;
            if ($this->di['cache']->get($key)) {
                throw new \Exception('每期只能投注一次');
            }
            $this->di['cache']->set($key, true, 24 * 3600);

            $add = [
                'clo_id' => $cloId,
                'uid' => $uid,
                'money' => $money,
                'date' => time(),
            ];
            if (!$this->save($add)) {
                throw new \Exception('添加失败');
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
                'S' => '未打卡',
                'D' => '已打卡',
                'N' => '未达标',
                'C' => '结算中',
                'Y' => '已结算',
                'X' => '未达标'
            ],
        ];
    }
}
