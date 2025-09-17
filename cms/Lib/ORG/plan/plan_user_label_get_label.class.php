<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/7/15.
 * Time: 16:23.
 */

/**
 *  自动标签绑定用户->子计划任务 用户打标签
 *
* */
class plan_user_label_get_label extends plan_base{

    public function runTask($param = array()){
       if(!$param['label_id'] || !$param['uid']){
            return true;
        }
        // 查询标签
        $label = M('User_label')->where(array('id'=>$param['label_id']))->find();

        // 查询用户
        $user = M('User')->field('uid,openid,label_id')->where(array('uid'=>$param['uid']))->find();

        if (!$label || !$user) {
            return true;
        }
        
        // 是否符合条件，默认不符合
        $condition_yes = 0;
        
        //记录是否所有条件都满足
        $condition_yes_all = 1;
        
        //记录是否一个条件都不满足
        $condition_yes_none = 1;

        // 没有勾选条件，不打标签
        if (!$label['behavior']&&!$label['times']&&!$label['money']&&!$label['spread_num']&&!$label['has_card']&&!$label['buy_goods']) {
            $condition_yes_all = 0;
        }
        
        

        // 消费行为
        if ($label['behavior']) {
            $where_condition = array();
            $where_condition['uid'] =  $user['uid'];
            $where_condition['paid'] =  1;
            $money_list = M('System_order')->where($where_condition)->order('id DESC')->find();

            if ($label['behavior_type']) {
                if ($money_list['create_time'] >= $label['behavior_start_time'] && $money_list['create_time'] <= $label['behavior_end_time']) {
                    $condition_yes = 1;
                    $condition_yes_none = 0;
                } else {
                    $condition_yes_all = 0;
                }
            } else {
                if ($money_list['create_time'] <= time() - $label['behavior_day'] * 86400) {
                    $condition_yes = 1;
                    $condition_yes_none = 0;
                } else {
                    $condition_yes_all = 0;
                }
            }
        }

        // 累计消费次数
        if ($label['times']) {
            $condition_yes = 0;
            $where_condition = array();
            $where_condition['uid'] =  $user['uid'];
            $where_condition['paid'] =  1;
            $money_list_count = M('System_order')->where($where_condition)->count();
            if ($money_list_count >= $label['times_min'] && $money_list_count <= $label['times_max']) {
                $condition_yes = 1;
                $condition_yes_none = 0;
            } else {
                $condition_yes_all = 0;
            }
        }

        // 累计消费金额
        if ($label['money']) {
            $condition_yes = 0;
            $where_condition = array();
            $where_condition['uid'] =  $user['uid'];
            $where_condition['paid'] =  1;
            $money_count = M('System_order')->field('sum(price) as money_count')->where($where_condition)->find();
            if ($money_count['money_count'] >= $label['money_min'] && $money_count['money_count'] <= $label['money_max']) {
                $condition_yes = 1;
                $condition_yes_none = 0;
            } else {
                $condition_yes_all = 0;
            }
        }

        // 推广用户数区间
        if ($label['spread_num']) {
            $condition_yes = 0;
            $count = D('User_spread')->get_spreaduser_count($user['openid'],$user['uid']);
            if ($count>=$label['spread_num_min']&&$count<=$label['spread_num_max']) {
                $condition_yes = 1;
                $condition_yes_none = 0;
            }else{
                $condition_yes_all = 0;
            }
        }

        // 领取过商家会员卡 
        if ($label['has_card']) {
            $condition_yes = 0;
            $where_condition = array();
            $where_condition['uid'] =  $user['uid'];
            if($card = M('Carduserlist')->where($where_condition)->find()){
                $condition_yes = 1;
                $condition_yes_none = 0;
            }else{
                $condition_yes_all = 0;
            }
        }

        // 购买指定商品
        if ($label['buy_goods']) {
            $condition_yes = 0;
            $label['buy_goods_list'] = unserialize($label['buy_goods_list']); 
            if(!empty($label['buy_goods_list'])){
                if($label['buy_goods_list']['mall']){//商城商品
                    $where_condition = array();
                    $where_condition['o.uid'] =  $user['uid'];
                    $where_condition['o.order_from'] =  1;
                    $where_condition['o.paid'] =  1;
                    $where_condition['d.goods_id'] = array('in',$label['buy_goods_list']['mall']);
                    $mall_order =  M('Shop_order')->join(' as o left join '.C('DB_PREFIX').'shop_order_detail as d on o.order_id = d.order_id')->where($where_condition)->find();
                    if ($mall_order) {
                        $condition_yes = 1;
                        $condition_yes_none = 0;
                    }else{
                        $condition_yes_all = 0;
                    }
                   
                }
                if($label['buy_goods_list']['shop']){
                    $where_condition = array();
                    $where_condition['o.uid'] =  $user['uid'];
                    $where_condition['o.order_from'] =  array('neq',1);
                    $where_condition['o.paid'] =  1;
                    $where_condition['d.goods_id'] = array('in',$label['buy_goods_list']['shop']);
                    $shop_order =  M('Shop_order')->join(' as o left join '.C('DB_PREFIX').'shop_order_detail as d on o.order_id = d.order_id')->where($where_condition)->find();
                    if ($shop_order) {
                        $condition_yes = 1;
                        $condition_yes_none = 0;
                    }else{
                        $condition_yes_all = 0;
                    }
                }
                if($label['buy_goods_list']['group']){
                    $where_condition = array();
                    $where_condition['uid'] =  $user['uid'];
                    $where_condition['paid'] =  1;
                    $where_condition['group_id'] = array('in',$label['buy_goods_list']['group']);
                    $group_order =  M('Group_order')->where($where_condition)->find();
                    if ($group_order) {
                        $condition_yes = 1;
                        $condition_yes_none = 0;
                    }else{
                        $condition_yes_all = 0;
                    }
                }
            }              
        } 
        
        $res = $this->modifylabel($condition_yes,$user,$label,$condition_yes_all,$condition_yes_none);
        if ($res) {
            return true;
        }
        return true;
    }


    public function modifylabel($condition_yes,$user=array(),$label=array(),$condition_yes_all,$condition_yes_none)
    {    
        $this->keepThread();
        // condition_type 0-满足任意一个被选中的条件即可，条件满足 1-必须满足所有被选中的条件，
        if (($label['condition_type'] == 0 && $condition_yes_none == 0) || ($label['condition_type'] == 1 && $condition_yes_all == 1)) {
            // 满足条件加入标签
            if (!D('User_label_bind')->where(array('uid'=>$user['uid'],'label_id'=>$label['id']))->find()) {
                D('User_label_bind')->add(array('uid'=>$user['uid'],'label_id'=>$label['id']));
                
                $label_id_arr = explode(',',$user['label_id']);
                if (!in_array($label['id'], $label_id_arr)) {
                    $label_id_arr[] = $label['id'];
                    D('User')->save(array('uid'=>$user['uid'],'label_id'=>implode(',',$label_id_arr)));
                }
                return true;
            }
        } else {
            // 满不足条件删除标签
            if (D('User_label_bind')->where(array('uid'=>$user['uid'],'label_id'=>$label['id']))->find()) {
                D('User_label_bind')->where(array('uid'=>$user['uid'],'label_id'=>$label['id']))->delete();
                $label_id_arr = explode(',',$user['label_id']);
                if (in_array($label['id'], $label_id_arr)) {
                    foreach ($label_id_arr as $label_k => $label_v) {
                        if ($label_v==$label['id']) {
                            unset($label_id_arr[$label_k]);
                        }
                    }
                    D('User')->save(array('uid'=>$user['uid'],'label_id'=>implode(',',$label_id_arr)));
                }
                return true;
            }
        }
        return false;
    }
}
?>