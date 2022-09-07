<?php

class AprTask extends \C\L\Task
{
    public function itemAction()
    {
            $iamArray = $this->s_iam->searchPage(['status' => 'D', 'back_time' => time()], ['back_time' => '<='], [], 'id asc', 1, 50);
            foreach ($iamArray as $item) {
                try {
                    $il = $this->s_il->search($item['cid']);
                    if (empty($il)) {
                        continue;
                    }

                    $this->db->begin();

                    if (!$this->s_iam->update($item['id'], ['status' => 'Y', 'ok_time' => time()])) {
                        throw new Exception('更新iam失败');
                    }

                    $title = "{$il['name']}第{$item['apr_no']}期利息";
                    if (!$this->s_funds->add($item['uid'], $item['apr_money'], 'money', "item{$il['stype']}_apr", $title, $item['id'])) {
                        throw new Exception('添加funds_apr失败');
                    }

                    if ($il['max_apr_count'] <= $item['apr_no']) {


                        if (!$this->s_il->update($il['id'], ['status' => 'Y'])) {
                            throw new Exception('更新il失败');
                        }

                        if ($il['stype'] == 'dt' && $il['is_auto_apply'] == 'Y') {

                            $autoAddData = [
                                'uid' => $il['uid'],
                                'money' => $il['money'],
                                'type' => $il['type'],
                                'cid' => $il['id'],
                            ];

                            if (!$this->s_iaa->save($autoAddData)) {
                                throw new Exception('添加iaa连投失败');
                            }

                        } else {

                            if (!$this->s_funds->add($item['uid'], $il['money'], 'money', "item{$il['stype']}_money", $title, $item['id'])) {
                                throw new Exception('添加funds_money失败');
                            }

                        }

                    }

                    $this->db->commit();

                    $this->logs(['msg' => '成功', 'id' => $item['id']]);

                    $this->cache->rPush('sms_list', json_encode([
                        'uid' => $item['uid'],
                        'type' => 'item_apr',
                        'params' => date('Y.m.d H:i'),
                    ]));

                } catch (Exception $e) {
                    if ($this->db->isUnderTransaction()) {
                        $this->db->rollback();
                    }
                    $this->logs(['msg' => $e->getMessage(), 'id' => $item['id']]);
                   
                }
            }
    }
    //运动挑战赛
    public function packAction()
    {
        $this->runCheck('apr_pack');
        $time = strtotime('-1 day');
        $date = date('Ymd', $time);

        $i = 0;
        while ($i < 18) {

            $packArray = $this->s_packlist->searchPage(['status' => ['D', 'S'], 'no_date' => $date], ['status' => 'in'], [], 'id asc', 1, 500);
            foreach ($packArray as $item) {

                try {

                    $backMoney = 0;
                    $aprMoney = 0;
                    $status = 'Y';
                    if ($item['status'] == 'D' && $item['ok_step'] >= $item['set_step']) {
                        $aprMoney = bcmul($item['apr'] / 100, $item['money'], 2);
                        $backMoney = bcadd($aprMoney, $item['money'], 2);
                    } else {
                        $status = 'X';
                    }

                    if ($item['status'] == 'S') {
                        $status = 'X';
                        // $backMoney = bcmul($item['not_apr'] / 100, $item['money'], 2);
                    }

                    $update = [
                        'status' => $status,
                        'apr_money' => $aprMoney,
                        'back_money' => $backMoney,
                    ];

                    $this->db->begin();

                    if (!$this->s_packlist->update($item['id'], $update)) {
                        throw new Exception($this->translate['new_lang_update_packlist_failed']);
                    }

                    if (!$this->s_funds->add($item['uid'], $backMoney, 'money', 'pack_back', $this->translate['active_type_sport'] .$this->translate['new_lang_settlement'] . '-' . date('md', $time), $item['id'])) {
                        throw new Exception($this->translate['']);
                    }

                    $this->logs(['msg' => '成功', 'id' => $item['id']]);
                    $this->db->commit();

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

        return true;
    }

    //早起
    public function cloAction()
    {
        $this->runCheck('apr_clo');
        $time = time();
        $date = date('Ymd', $time);

        $i = 0;
        while ($i < 18) {

            $cloArray = $this->s_clolist->searchPage(['status' => ['S', 'D', 'N'], 'no_date' => $date], ['status' => 'in'], [], 'id asc', 1, 500);
            foreach ($cloArray as $item) {

                try {

                    $backMoney = 0;
                    $aprMoney = 0;
                    $status = 'Y';
                    if ($item['status'] == 'D') {
                        $aprMoney = bcmul($item['apr'] / 100, $item['money'], 2);
                        $backMoney = bcadd($aprMoney, $item['money'], 2);
                    }

                    if ($item['status'] == 'S' || $item['status'] == 'N') {
                        $status = 'X';
                        // $backMoney = bcmul($item['not_apr'] / 100, $item['money'], 2);
                    }

                    $update = [
                        'status' => $status,
                        'apr_money' => $aprMoney,
                        'back_money' => $backMoney,
                    ];

                    $this->db->begin();

                    if (!$this->s_clolist->update($item['id'], $update)) {
                        throw new Exception($this->translate['new_lang_update_packlist_failed']);
                    }

                    if (!$this->s_funds->add($item['uid'], $backMoney, 'money', 'clo_back', $this->translate['new_lang_morning_1'] .'-' . date('md'), $item['id'])) {
                        throw new Exception($this->translate['fin_add_error']);
                    }

                    $this->logs(['msg' => '成功', 'id' => $item['id']]);
                    $this->db->commit();

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

        return true;
    }
}