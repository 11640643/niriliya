<?php

class RewardTask extends \C\L\Task
{
    public function itemAction()
    {

        try{
            while ($cid = $this->cache->lPop('item_reward',100)) {
                $cid  = $cid[1];
                try {
                    $il = $this->s_il->search($cid);
                    if (empty($il)) {
                        throw new Exception('未找到投入记录');
                    }
    
                    $user = $this->s_user->search($il['uid']);
                    if ($user['t_uid'] > 0) {
    
                        $this->db->begin();
    
                        if ($il['top_apr'] > 0) {
                            $money = bcmul($il['money'] / 100, $il['top_apr'], 2);
                            $funName = '好友投资分润奖励:' . $user['mobile'];
                            if (!$this->s_funds->add($user['t_uid'], $money, 'money', 'reward_item_f', $funName, $cid)) {
                                throw new Exception('奖励失败');
                            }
                        }
    
                        $topUser = $this->getTopUsers($user['t_uid']);
                        $config = $this->s_config->get('item_dq');
                        foreach ($topUser as $k => $v) {
    
                            if ($config[$k] <= 0) {
                                continue;
                            }
    
                            $money = bcmul($il['money'] / 100, $config[$k], 2);
                            $funName = '好友投资奖励:' . $user['mobile'];
                            if (!$this->s_funds->add($v, $money, 'money', 'reward_item', $funName, $cid)) {
                                throw new Exception('奖励失败');
                            }
                        }
    
                        $this->db->commit();
    
                    }
                    $this->logs(['msg' => '成功', 'id' => $cid]);
    
                } catch (Exception $e) {
                    if ($this->db->isUnderTransaction()) {
                        $this->db->rollback();
                    }
                    $this->cache->rPush($cid);
                    $this->logs(['msg' => $e->getMessage(), 'id' => $cid]);
                }
                sleep(3);
            }    
        }catch(Exception $err){
            $this->logs(['msg' => $err->getMessage(), 'id' => 0]);
            exit;
        }
    }


    public function investAction()
    {
        try {
            while ($cid = $this->cache->blPop('invest_reward',100)) {
                $cid  = $cid[1];
                try {
                    $invest = $this->s_invest->search(['id' => $cid, 'status' => 'Y']);
                    if (empty($invest)) {
                        throw new Exception('未找到充值记录');
                    }

                    $user = $this->s_user->search($invest['uid']);
                    if ($user['t_uid'] > 0) {

                        $topUser = $this->getTopUsers($user['t_uid']);
                        if (empty($topUser)) {
                            throw new Exception('未找到上级');
                        }

                        $this->db->begin();

                        $config = $this->s_config->get('reward');
                        foreach ($topUser as $k => $v) {
                            if ($config[$k] <= 0) {
                                continue;
                            }
                            $money = bcmul($invest['money'] / 100, $config[$k], 2);
                            if (!$this->s_funds->add($v, $money, 'money', 'reward_invest', '好友充值奖励', $cid)) {
                                throw new Exception('奖励失败');
                            }
                        }

                        $this->db->commit();
                    }

                    $this->logs(['msg' => '成功', 'id' => $cid]);

                } catch (Exception $e) {

                    if ($this->db->isUnderTransaction()) {
                        $this->db->rollback();
                    }
                    $this->cache->lPush('invest_reward',$cid);
                    $this->logs(['msg' => $e->getMessage(), 'id' => $cid]);
                    exit;
                }
                sleep(3);
            }
        }catch(Exception $err){
            $this->logs(['msg' => $err->getMessage(), 'id' => 0]);
            exit;
        }
    }

    private function getTopUsers($uid)
    {
        $topUser = [];
        $top1 = $this->s_user->search($uid);
        if (empty($top1)) {
            return $topUser;
        }

        $topUser['top1_apr'] = [
            'uid' => $top1['uid'],
        ];

        $top2 = $this->s_user->search($top1['t_uid']);
        if (empty($top2)) {
            return $topUser;
        }

        $topUser['top2_apr'] = [
            'uid' => $top2['uid'],
        ];
        $top3 = $this->s_user->search($top2['t_uid']);
        if (empty($top3)) {
            return $topUser;
        }

        $topUser['top3_apr'] = [
            'uid' => $top3['uid'],
        ];

        return $topUser;

    }
}
