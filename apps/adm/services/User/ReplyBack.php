<?php

namespace C\S\User;

use C\L\Service;

class ReplyBack extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserReplyBack();
    }

    public function getStatusConfig()
    {
        return [
            'type' => [
                'A' => '咨询',
                'B' => '投诉',
                'C' => '意见/建议',
		'D'=>'其它'
            ],
        ];
    }
    public function add($type,$content,$image,$name,$mobile,$uid)
    {
	try{
		if (!$this->di['public']->checkMobile($mobile)) {
                	throw new \Exception('手机号有误');
       		 }	
		$data = [
			'type'=>$type,
			'content'=>$content,
			'image'=>$image,
			'name'=>$name,
			'mobile'=>$mobile,
			'uid'=>$uid
		];
		if(!$id = $this->save($data)){
			throw new \Exception('操作失败');
		}
		return true;
	} catch (\Exception $e) {
		$this->di['message']->setSerMsg($e->getMessage());
            	return false;
	}

    }
    public function reply($content,$reply_time,$reply_id)
    {
        try {
            $reply_back = $this->di['s_replyback']->search(['id' => $reply_id]);
            if (!empty($reply_back['reply_content'])) {
                throw new \Exception('已经回复过');
            }
	   #var_dump(['reply_content'=>$content,'reply_time'=>$reply_time]);exit;
            if(!$this->di['s_replyback']->update($reply_id,['reply_content'=>$content,'reply_time'=>$reply_time])){
		 throw new \Exception('回复失败');
	    }
	    return true;

        } catch (\Exception $e) {
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
