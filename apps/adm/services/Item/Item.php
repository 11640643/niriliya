<?php

namespace C\S\Item;

use C\L\Service;

class Item extends Service
{

    protected function setModel()
    {
        $this->model = new \C\M\Item();
    }

    public function getStatusConfig()
    {
       
        $translate =  $this->getTranslation();
        return [
            'if_open_vouchers' => [
                'Y' => $translate['item_if_open_vouchers_y'],
                'N' => $translate['item_if_open_vouchers_n'],
            ],
            'is_low_risk' => [
                'Y' => $translate['item_is_low_risk_y'],
                'N' => $translate['item_is_low_risk_n'],
            ],
            'is_show_index' => [
                'Y' => $translate['item_is_show_index_y'],
                'N' => $translate['item_is_show_index_n'],
            ],
            'status' => [
                'Y' => $translate['item_status_y'],
                'N' => $translate['item_status_n'],
            ],
            'type' => [
                'A' => $translate['item_type_a'],//'电费日结，到期退还押金',
                'B' => $translate['item_type_b'],//'电费周结,到期退还押金',
                'C' => $translate['item_type_c'],//'每月结算,到期还本',
                'D' => $translate['item_type_d'],//'每天复利,到期还本',
                'E' => $translate['item_type_e'],//'到期还本付息',
            ],
        ];
    }

    public function getShowItem($uid)
    {

        $config = $this->di['s_config']->get('item_dq');

        if ($config['is_disable'] == 'N') {
            return true;
        }

        $user = $this->di['s_user']->search($uid);
        if ($config['is_top_open'] == 'Y') {

            if ($user['t_uid'] > 0) {
                return true;
            }
        }

        $set_sign_num = $config['signin_num'] ?? 0;
        $user_sign_num = $this->di['s_funds']->getCount([
            'uid' => $uid,
            'stype' => 'checkin',
        ]);

        if ($user_sign_num >= $set_sign_num) {
            return true;
        }

        return false;
    }

    public function backMoney($id)
    {
        try {

            $item = $this->search($id);
            if (empty($item)) {
                throw new \Exception('未找到项目');
            }

            if ($item['schedule'] < 100) {
                throw new \Exception('项目未满标');
            }

            $iteml = $this->di['s_il']->searchAll(['cid' => $id]);
            if (empty($iteml)) {
                throw new \Exception('当前项目无投资记录');
            }

            $time = time();
            $adds = [];
            foreach ($iteml as $l) {

                if ($this->di['s_funds']->search(['uid' => $l['uid'], 'stype' => 'back_money', 'cid' => $l['id']])) {
                    continue;
                }

                $adds[] = [
                    'cid' => $l['id'],
                    'uid' => $l['uid'],
                    'money' => $l['money'],
                    'type' => 'money',
                    'stype' => 'back_money',
                    'title' => $l['name'] . ',满返本金',
                    'status' => 'D',
                    'uptime' => $time,
                    'addtime' => $time,
                ];
            }

            if(empty($adds)){
                throw new \Exception('无需返的用户');
            }

            if (!$this->di['s_funds']->saves($adds)) {
                throw new \Exception('添加失败');
            }

            return true;

        } catch (\Exception $e) {

            $this->di['message']->setSerMsg($e->getMessage());
            return false;
        }
    }

    public function resetFooter($uid)
    {
        $footer = $this->di['ssid']->get('footer');
        if ($footer == 'n1' && $this->getShowItem($uid)) {
            $this->di['s_user']->setUserSsid(
                [
                    'footer' => 'n2',
                ]
            );
        }
    }

    /**
     * 渠道推广的直接设置n2
     */
    public function promotionFooter($mobile) 
    {
        $promotion_host1 = @(array)$this->di['config']->get('config')->promotion_host1;
        $promotion_host2 = @(array)$this->di['config']->get('config')->promotion_host2;
        $promotion_host3 = @(array)$this->di['config']->get('config')->promotion_host3;
        $promotion_host = array_merge($promotion_host1, $promotion_host2, $promotion_host3);
        $footer = $this->di['ssid']->get('footer');
        $host = $this->di['cache']->get('promotion:'.$mobile) ?? '';
        if ($footer == 'n1' && in_array($host, $promotion_host)) {
            $this->di['s_user']->setUserSsid(
                [
                    'footer' => 'n2',
                ]
            );
            // $this->di['s_user']->updates(['channel' => 'wxpop'], ['mobile' => $mobile]);
        }
    }
}
