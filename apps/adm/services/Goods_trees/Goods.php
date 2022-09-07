<?php

namespace C\S\Goods;

use C\L\Service;
class Goods extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\Goods();
    }

    public function getStatusConfig()
    {
        return [
            'status' => [
                'N' => '已下架',
                'Y' => '上架中',
                // 'O' => '金币不足'
            ],
        ];
    }
    public function user($uid){
        return $userInfo = $this->di['s_user']->search(['uid'=>$uid]);
    }

    public function list($uid)
    {
        $goods = $this->di['s_goods']->searchall();
        $category = new \C\M\GoodsCategory();
        $data =  [];
        foreach($goods as $key=>$val){          
            $categoryInfo = $category->get(['id'=>$val['category_id']]);
            if(!$categoryInfo){
                continue;
            }    
            if(!isset($data[$val['category_id']])){
                $data[$val['category_id']]['category_name'] =  $categoryInfo['name'];
                $data[$val['category_id']]['category_image'] = $categoryInfo['image'];
            }
            if(!empty($val['vip_exchange'])){
                $val['vip_exchange'] = json_decode($val['vip_exchange'],true);
            }
            $data[$val['category_id']]['list'][] = $val;
        }
        return $data;
    }

    public function apply($id, $uid,$num,$vip_level)
    {
        try{
            $num = abs(intval($num));
            if(empty($num) || $num  <= 0 ){
                throw new \Exception('系统异常,稍后再试');
            }
            $goods = $this->di['s_goods']->search($id);            
            if(empty($goods) || $goods['status'] != 'Y'){
                throw new \Exception('商品不存在');
            }
          
            if(empty($goods['vip_exchange'])){
                throw new \Exception('商品单价异常');
            }

            $user_address = $this->di['s_address']->search(['uid'=>$uid, 'is_default' => 'Y']);
            if(empty($user_address)){
                throw new \Exception('还未添加收货地址');
            }

            if (!$this->checkGoodsExchange($uid)) {
                throw new \Exception("兑换积分不足");
            }

            $money = json_decode($goods['vip_exchange'],true)[$vip_level];
            $this->di['db']->begin();
            $total_money  = $money*$num ;
            $numArray = $this->di['s_user']->lockUpdate($uid, -$total_money, 'fruit');
            if(!$numArray){
                throw new \Exception('果实不足，请稍后重试');
            }

            $add = [
                'uid' => $uid,
                'gid' => $goods['id'],
                'name' => $goods['name'],
                'money' => $money,
                'num'=>$num,
                'thumb' => $goods['thumb'],
                'remark' => '当前用户等级为：'.$vip_level.' '.$goods['remark'],
                'address_id' => $user_address['id'],
                'address' => "{$user_address['name']} {$user_address['tel']} {$user_address['province']} {$user_address['city']} {$user_address['county']} {$user_address['address']} {$user_address['postal_code']}",
            ];

            if(!$oid = $this->di['s_gorder']->save($add)){
                throw new \Exception('订单添加失败');
            }

            if (!$this->di['s_funds']->add($uid, -$total_money, 'fruit', 'goods_apply', "兑换:{$goods['name']}", $oid, 'Y', $numArray[0], $numArray[1])) {
                throw new \Exception('流水添加失败');
            }

            $this->di['db']->commit();

            return true;

        }catch (\Exception $e){

            if ($this->di['db']->isUnderTransaction()) {
                $this->di['db']->rollback();
            }

            $this->di['message']->setSerMsg($e->getMessage());
            return false;

        }
    }

    /**
     * 校验用户免费兑换次数是否足够
     */
    public function checkGoodsExchange($uid)
    {
        // 获取后台商品兑换设置
        $goods_ex_config = $this->di['s_config']->get('goods_exchange');
        if (empty($goods_ex_config)) {
            // throw new \Exception("商品免费兑换次数未设置，请联系客服");
            return false;
        }
        $goods_ex_config = json_decode($goods_ex_config['ex_rule'], true);
        // 获取用户的兑换次数
        $user_ex_nums = $this->di['s_gorder']->getCount(['uid' => $uid]);
        if ($user_ex_nums < $goods_ex_config['free_ex_num']) {
            return true;
        }
        // 获取用户积分
        $user_credits = $this->di['s_user']->search($uid);
        if (empty($user_credits)) {
            // throw new \Exception('获取用户积分失败');
            return false;
        }
        // 校验用户积分
        if ($user_credits['credit'] < $goods_ex_config['ex_credits']) {
            // throw new \Exception('用户积分不足');
            return false;
        }
        return true;
    }
}
