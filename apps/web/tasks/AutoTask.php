<?php

class AutoTask extends \C\L\Task
{

    public function packAction()
    {

        $this->runCheck('auto_run');

        $date = date('Ymd', strtotime('+1 day'));
        $pack = $this->s_pack->search(['no_date' => $date]);

        $autoAddNum = $pack['auto_add_num'];
        $autoAddUserMoney = $pack['auto_add_user_money'];

        $minAddNum = 0;
        $maxAddNum = 0;
        if (strstr($autoAddNum, ',')) {
            $autoAddNumArray = explode(',', $autoAddNum);
            $minAddNum = $maxAddNum = $autoAddNumArray[0];
            if (isset($autoAddNumArray[1])) {
                $maxAddNum = $autoAddNumArray[1];
            }
        }
        $minAddUserMoney = 0;
        $maxAddUserMoney = 0;
        if (strstr($autoAddNum, ',')) {
            $autoAddUserMoneyArray = explode(',', $autoAddUserMoney);
            $minAddUserMoney = $maxAddUserMoney = $autoAddUserMoneyArray[0];
            if (isset($autoAddUserMoneyArray[1])) {
                $maxAddUserMoney = $autoAddUserMoneyArray[1];
            }
        }

        $addNum = rand($minAddNum, $maxAddNum);

        $update = [
            'set_user_num' => $pack['set_user_num'] + $addNum,
        ];

        $addUserMoeny = $pack['set_all_money'];;
        for ($i = 0; $i < $addNum; $i++) {
            $addUserMoeny += rand($minAddUserMoney, $maxAddUserMoney);
            $update['set_all_money'] = $addUserMoeny;
            if (!$this->s_pack->update($pack['id'], $update)) {
                $this->logs(['msg' => '失败', 'money' => $addUserMoeny]);
            } else {
                $this->logs(['msg' => '成功', 'money' => $addUserMoeny]);
            }
        }
    }


    public function cloAction()
    {
        $this->runCheck('auto_clo');

        $date = date('Ymd', strtotime('+1 day'));

        $clo = $this->s_clo->search(['no_date' => $date]);

        $autoAddNum = $clo['auto_add_num'];
        $autoAddUserMoney = $clo['auto_add_user_money'];

        $minAddNum = 0;
        $maxAddNum = 0;
        if (strstr($autoAddNum, ',')) {
            $autoAddNumArray = explode(',', $autoAddNum);
            $minAddNum = $maxAddNum = $autoAddNumArray[0];
            if (isset($autoAddNumArray[1])) {
                $maxAddNum = $autoAddNumArray[1];
            }
        }
        $minAddUserMoney = 0;
        $maxAddUserMoney = 0;
        if (strstr($autoAddNum, ',')) {
            $autoAddUserMoneyArray = explode(',', $autoAddUserMoney);
            $minAddUserMoney = $maxAddUserMoney = $autoAddUserMoneyArray[0];
            if (isset($autoAddUserMoneyArray[1])) {
                $maxAddUserMoney = $autoAddUserMoneyArray[1];
            }
        }

        $addNum = rand($minAddNum, $maxAddNum);

        $update = [
            'set_user_num' => $clo['set_user_num'] + $addNum,
        ];

        $addUserMoeny = $clo['set_all_money'];
        for ($i = 0; $i < $addNum; $i++) {

            $addUserMoeny += rand($minAddUserMoney, $maxAddUserMoney);
            $update['set_all_money'] = $addUserMoeny;

            if (!$this->s_clo->update($clo['id'], $update)) {
                $this->logs(['msg' => '失败', 'money' => $addUserMoeny]);
            } else {
                $this->logs(['msg' => '成功', 'money' => $addUserMoeny]);
            }
        }
    }


}