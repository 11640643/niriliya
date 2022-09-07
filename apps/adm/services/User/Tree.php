<?php

namespace C\S\User;

use C\L\Service;

class Tree extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserTree();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'S' => '成长中',
                'D' => '已成熟',
                'Y' => '已采摘',
            ],
        ];
    }

    public function apply($uid, $type)
    {
        try {

            $tree = $this->di['s_tree']->search(['uid' => $uid, 'status' => 'S']);
            if (empty($tree)) {
                return $this->pluck($uid);
                // throw new \Exception('请先摘取果实。');
            }

            if (!in_array($type, ['water', 'manure'])) {
                throw new \Exception('非法请求');
            }

            $this->di['db']->begin();
	    	
            $numArray = $this->di['s_user']->lockUpdate($uid, -1, $type);
	    if (!$numArray) {
                throw new \Exception($type === 'water' ? '浇水次数不足' : '施肥次数不足');
            }

            if (!$this->di['s_funds']->add($uid, -1, $type, $type . '_apply', "树成长", $tree['id'], 'Y', $numArray[0], $numArray[1])) {
                throw new \Exception('流水添加失败');
            }
            $water = $tree['water'];
            $manure = $tree['manure'];
            if ($type == 'water') {
                $value = 1;
                $water += 1;
            } else {
                $value = 2;
                $manure += 1;
            }
            $value += $tree['value'];

            $status = 'S';
            if ($value >= $this->getTreeNum()) {
                $status = 'D';
            }

            if (!$this->di['s_tree']->update($tree['id'], ['value' => $value, 'manure' => $manure, 'water' => $water, 'status' => $status])) {
                throw new \Exception('成长失败');
            }


            $this->di['db']->commit();

            return $this->getTreeType($value);


        } catch (\Exception $e) {
            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }
            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function pluck($uid)
    {
        try {

            $tree = $this->di['s_tree']->search(['uid' => $uid, 'status' => 'D']);
            if (empty($tree)) {
                throw new \Exception('果实未成熟');
            }

            $num = $this->di['s_config']->get('tree')['num'];
            $this->di['db']->begin();

            if (!$this->di['s_funds']->add($uid, $num, 'fruit', 'tree_pluck', "果实采摘", $tree['id'])) {
                throw new \Exception('流水添加失败');
            }

            if (!$this->di['s_tree']->update($tree['id'], ['status' => 'Y'])) {
                throw new \Exception('更新失败');
            }

            if (!$this->di['s_tree']->save(['uid' => $uid])) {
                throw new \Exception('更新失败');
            }

            $this->di['db']->commit();

            return $num;


        } catch (\Exception $e) {

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    public function getTreeType($value)
    {
        $config = $this->di['s_config']->get('tree')['tree'];
        $type = 'tree0';
        ksort($config);

        $max = 0;
        foreach ($config as $k => $v) {

            $max += $v['value'];
            if ($max > $value) {
                continue;
            }

            $type = $k;
        }

        return $type;
    }

    public function getTreeValue($type)
    {
        $config = $this->di['s_config']->get('tree')['tree'];
        ksort($config);
        $value = 0;
        foreach ($config as $k => $v) {
            $value += $v['value'];
            if ($k == $type) {
                return $value;
            }
        }
        return $value;
    }

    public function getTreeNum()
    {
        $config = $this->di['s_config']->get('tree')['tree'];
        $num = 0;
        foreach ($config as $k => $v) {
            $num += $v['value'];
        }

        return $num;
    }

    /**
     * 计算用户的树苗进度
     * $tree_config       array     配置的  所需总次数
     * $user_take_part    array     用户实际参与的次数
     */
    public function countTreeProgress($tree_config, $user_take_part)
    {
        $watering = 1;  // 一次浇水算1次
        $to_manure = 2; // 一次施肥算2次
        $need_total_num = 0;   // 一共需要的总次数
        $user_take_part_num = 0;    // 用户实际参与次数
        foreach ($tree_config as $key => $phase) {
            $need_total_num += $phase['value'];
        }
        $user_take_part_num = ($user_take_part['water'] * $watering) + ($user_take_part['manure'] * $to_manure);
        $ratio = ($user_take_part_num / $need_total_num) * 100;
        return floor($ratio * 100) / 100;
    }

    /**
     * 获取广告icon
     * 
     */
    public function getAdvicon($uid)
    {
        // 获取设置的签到次数条件
        $item_dq = $this->di['s_config']->search(['type' => 'item_dq']);
        $set_sign_num = json_decode($item_dq['content'], true)['signin_num'];
        // 获取当前用户的签到次数
        $user_sign_num = $this->di['s_funds']->getCount(['uid' => $uid, 'title' => $this->translate['sign_awards']]);
        // 用户目前的进度
        $tree = $this->di['s_tree']->search(['uid' => $uid, 'status' => 'S']);
        // 获取广告icon配置
        $advicon = $this->di['s_advicon']->searchAll(['status' => 1]);
        $res = [];
        foreach ($advicon as $key => $value) {
            $condition = json_decode($value['condition'], true);
            if (isset($tree['value']) && $tree['value'] >= $condition['value']) {
                $res[] = $value;
                continue;
            }
            if ($user_sign_num >= $set_sign_num) {
                $res[] = $value;
                continue;
            }
        }
        return $res;
    }
}
