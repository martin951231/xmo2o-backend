<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/11/18
 * Time: 18:50
 */

class Village_unpaid_payModel extends Model{

    public function unpaid_pay($info)
    {
        if (!$info || !$info['money'] || $info['money']<=0) {
            return false;
        }
        if (!$info['pay_type'] || !$info['uid'] || !$info['bind_id'] || !$info['village_id'] || !$info['payment_id']) {
            return false;
        }
        $village_id = $info['village_id'];
        $uid = $info['uid'];
        $bind_id = $info['bind_id'];
        $pay_type = $info['pay_type'];
        $pay_money = $info['money'];
        $payment_id = $info['payment_id'];
        $database_house_village_user_bind = D('House_village_user_bind');
        // 获取绑定用户信息
        $now_user_info = $database_house_village_user_bind->get_one_by_bindId($bind_id);
        if (empty($now_user_info)) {

            return false;
        }
        if ($uid>=0) {
            $uid = $now_user_info['uid'];
        }
        $where['parent_id|pigcms_id'] = $bind_id;
        $where['uid'] = $uid;
        $where['village_id'] = $village_id;
        // 校验是否为绑定小区业主或者家属
        $house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
        if (empty($house_village_user_bind_count)) {

            return false;
        }
        // 获取社区信息
        $now_village = D('House_village')->get_one($village_id);
        if (empty($now_village)) {
            return false;
        }

        //家属 获取业主的欠费信息
        if ($now_user_info['parent_id']) {
            $now_user_info = $this->get_user_village_info($now_user_info['parent_id']);
        }
        if($pay_type == 'property'){
            $res = M('House_village_user_paylist')->where(array('usernum'=>$now_user_info['usernum'],'ydate'=>date('Y',time()),'mdate'=>date('M',time())))->find();
            if(empty($res)){
                $data['village_id'] = $now_user_info['village_id'];
                $data['uid'] = $now_user_info['uid'];
                $data['name'] = $now_user_info['name'];
                if($now_user_info['phone']){
                    $data['phone'] = $now_user_info['phone'];
                }
                $data['housesize'] = $now_user_info['housesize'];
                $data['bind_id'] = $now_user_info['pigcms_id'];
                $data['ydate'] = date('Y',time());
                $data['mdate'] = date('M',time());
                $data['property_price'] = $pay_money;
                $data['addtime'] = time();
                M('House_village_user_paylist')->data($data)->add();
            }
        }elseif($pay_type == 'custom_payment'){//自定义缴费

           $data['bind_id'] = $info['bind_id'];//房间号
           $data['payment_paid_cycle'] = $info['payment_paid_cycle'];//已缴期数
           $data['village_id'] = $info['village_id'];//小区id
           $data['payment_id'] = $info['village_id'];//自定义名称id
           $data['payment_bind_id'] = $info['payment_bind_id'];//绑定的自定义缴费项
           $data['add_time'] = time();
            M('House_village_payment_standard_unpaid')->data($data)->add();
        }

    }
}