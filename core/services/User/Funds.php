<?php

namespace C\S\User;

use C\L\Service;

class Funds extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserFunds();
    }

    public function add($uid, $money, $type, $stype, $title, $cid, $status = 'D', $beforMoney = 0, $afterMoney = 0, $remark = '')
    {
        $add = [
            'cid' => $cid,
            'uid' => $uid,
            'money' => $money,
            'type' => $type,
            'stype' => $stype,
            'title' => $title,
            'status' => $status,
            'befor_money' => $beforMoney,
            'after_money' => $afterMoney,
            'btype' => $money < 0 ? 'sub' : 'add',
            'remark' => $remark
        ];

        return $this->save($add);
    }

    public function getStatusConfig()
    {
       
        return [
            'status' => [
                'D' => $this->translate['lang_cfg_D'],
                'S' => $this->translate['lang_cfg_S'],
                'Y' => $this->translate['lang_cfg_Y'],
            ],
            'type' => [
                'money' => $this->translate['lang_cfg_money'],
                'credit' =>  $this->translate['lang_cfg_credit'],
                'prize_num' => $this->translate['lang_cfg_prize_num'],
                'exchange_credit' => $this->translate['lang_cfg_exchange_credit'],
                // 'manure' => '肥料',
                // 'water' => '浇水',
                // 'fruit' => '果实',
             //   'anwser_num' => '答题',
            ],
            'stype' => [
                'bank_invest_back' =>       $this->translate['lang_cfg_bank_invest_back'],     
                'back_money' =>             $this->translate['lang_cfg_back_money'],     
                'pack_send' =>              $this->translate['lang_cfg_pack_send'],     
                'prize_money' =>            $this->translate['lang_cfg_prize_money'],     
                'prize_num_sub' =>          $this->translate['lang_cfg_prize_num_sub'],     
                'prize_num_add_item' =>     $this->translate['lang_cfg_prize_num_add_item'],     
                'sys_prize_num' =>          $this->translate['lang_cfg_sys_prize_num'],     
                'sys_money' =>              $this->translate['lang_cfg_sys_money'],     
                'sys_anwser_num' =>         $this->translate['lang_cfg_sys_anwser_num'],     
                'item_pack' =>              $this->translate['lang_cfg_item_pack'],     
                'item_credit' =>            $this->translate['lang_cfg_item_credit'],     
                'itemdq_apr' =>             $this->translate['lang_cfg_itemdq_apr'],     
                'itemdq_money' =>           $this->translate['lang_cfg_itemdq_money'],     
                'invest_wx' =>              $this->translate['lang_cfg_invest_wx'],     
                'invest_alipay' =>          $this->translate['lang_cfg_invest_alipay'],     
                'invest_bank' =>            $this->translate['lang_cfg_invest_bank'],     
                'cost' =>                   $this->translate['lang_cfg_cost'],     
                'invest_credit' =>          $this->translate['lang_cfg_invest_credit'],     
                'itemdq_apply' =>           $this->translate['lang_cfg_itemdq_apply'],     
                'cost_back' =>              $this->translate['lang_cfg_cost_back'],     
                'share_money' =>            $this->translate['lang_cfg_share_money'],     
                'reg_money' =>              $this->translate['lang_cfg_reg_money'],     
                'huzhuan_out' =>            $this->translate['lang_cfg_huzhuan_out'],     
                'huzhuan_in' =>             $this->translate['lang_cfg_huzhuan_in'],     
                'checkin' =>                $this->translate['lang_cfg_checkin'],     
                'reward_invest' =>          $this->translate['lang_cfg_reward_invest'],     
                'reward_item' =>            $this->translate['lang_cfg_reward_item'],     
                'reward_item_f' =>          $this->translate['lang_cfg_reward_item_f'],     
                'apply_item' =>             $this->translate['lang_cfg_apply_item'],     
                // 'manure_apply' =>        $this->translate['lang_cfg_manure_apply'],     
                // 'water_apply' =>         $this->translate['lang_cfg_water_apply'],     
                // 'tree_pluck' =>          $this->translate['lang_cfg_tree_pluck'],     
                'goods_apply' =>            $this->translate['lang_cfg_goods_apply'],     
                'clo' =>                    $this->translate['lang_cfg_clo'],     
                'pack' =>                   $this->translate['lang_cfg_pack'],     
                // 'yeb' =>                 $this->translate['lang_cfg_yeb'],     
                // 'clos' =>                $this->translate['lang_cfg_clos'],     
                'auth' =>                   $this->translate['lang_cfg_auth'],     
                'task_item' =>              $this->translate['lang_cfg_task_item'],     
                // 'pack_step' =>           $this->translate['lang_cfg_pack_step'],     
                'pack_apply' =>             $this->translate['lang_cfg_pack_apply'],     
                'pack_back' =>              $this->translate['lang_cfg_pack_back'],     
                'clo_apply' =>              $this->translate['lang_cfg_clo_apply'],     
                'clo_back' =>               $this->translate['lang_cfg_clo_back'],     
                // 'login_manure' =>        $this->translate['lang_cfg_login_manure'],     
                // 'login_water' =>         $this->translate['lang_cfg_login_water'],     
                'cumulative_sign' =>        $this->translate['lang_cfg_cumulative_sign'],     
            //    'anwser' =>               $this->translate['lang_cfg_anwser'],     
            //    'anwser_num_sub' =>       $this->translate['lang_cfg_anwser_num_sub'],     
            ],
        ];
        
     
    }

    public function applyPack($name, $vip, $money)
    {
        try {

            if (empty($name)) {
                throw new \Exception($this->translate['new_lang_hbm_empty']);
            }

            if ($money < 0.01) {
                throw new \Exception($this->translate['new_lang_hb_min_01']);
            }

            $vipConfig = $this->di['s_level']->search($vip);
            if (empty($vipConfig)) {
                throw new \Exception($this->translate['new_lang_current_vip_setting_not_find']);
            }

            $data = [];
            $scores = $this->di['s_level']->getNextLevelScore($vip);
            if ($scores[1] > 0) {
                $scores[1] -= 1;
                $data['data']['credit'] = $scores;
                $data['data_type']['credit'] = 'between';
            } else {
                $data['data']['credit'] = $scores[0];
                $data['data_type']['credit'] = '>=';
            }

            if (empty($data)) {
                throw new \Exception($this->translate['new_lang_add_failed']);
            }

            $users = $this->di['s_user']->searchAll($data['data'], $data['data_type'], ['uid', 'credit']);
            if (empty($users)) {
                throw new \Exception($this->translate['new_lang_current_vip_not_user']);
            }

            $adds = [];
            $time = time();
            foreach ($users as $user) {
                $adds[] = [
                    'cid' => $user['uid'],
                    'uid' => $user['uid'],
                    'money' => $money,
                    'type' => 'money',
                    'stype' => 'pack_send',
                    'title' => $name,
                    'status' => 'D',
                    'uptime' => $time,
                    'addtime' => $time,
                ];
            }

            if (!$this->saves($adds)) {
                throw new \Exception($this->translate['new_lang_add_failed']);
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

}
