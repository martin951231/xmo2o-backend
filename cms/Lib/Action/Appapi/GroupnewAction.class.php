<?php

//新版团购
class GroupnewAction extends BaseAction
{
    /**
     * 分享团购券
     */
    public function share_coupon()
    {
        $share_id = intval($_POST['share_id']);
        $group_pass = intval($_POST['group_pass']);
        $share_find = D('Card_new_coupon_share')->where(['id'=>$share_id])->find();
        $relation = D('Group_pass_relation')->where(array('group_pass'=>$group_pass,'order_id'=>$share_find['order_id']))->find();
        $order_id = intval($share_find['order_id']);
        $group_pass = $relation['group_pass'];

        $now_order = D('Group_order')->get_order_detail_by_id($this->_uid, $order_id, true);
        $user = D('User')->where(['uid' => $this->_uid])->find();
        if (empty($now_order)) {
            $this->returnCode(1, array(), L_('此订单购买者才可以分享！'));
        }
        $now_group = D('Group')->get_group_by_groupId($now_order['group_id'], 'hits-setInc');
        $group_data = [
            'title' => $user['nickname'] . "送你一份团购商品券",
            'mer_id' => $now_group['mer_id'],
            'order_id' => $order_id,
            'group_pass' => $group_pass,
            'desc' => $now_group['name'],
            'group_image' => thumb_img($now_group['pic'], 240, 240)
        ];
        $coupon=D('Group_coupon_link')->where(['order_id'=>$order_id,'group_pass'=>$group_pass])->find();
        if(empty($coupon)){
            $data['uid']=$this->_uid;
            $data['group_pass']=$group_pass;
            $data['order_id']=$order_id;
            $data['add_time']=time()+86400;
            D('Group_coupon_link')->data($data)->add();
        }

        $pass=D('Group_pass_get')->where(['order_id'=>$order_id,'group_pass'=>$group_pass])->find();
        if(!empty($pass)){
            $group_data['give_status']=0;//已赠送不可赠
        }else{
            $group_data['give_status']=1;//可赠送
            //更新分享券pass_id
            D('Card_new_coupon_share')->where(['id'=>$share_id])->save(['pass_id'=>$relation['id']]);
        }
        $this->returnCode(0, $group_data);
    }

    /**
     *点击---弹框
     */
    public function share_coupon_get_win()
    {
        if (empty($this->_uid)) {
            //$this->returnCode(1, array(), L_('请先进行登录！'));
        }

        $share_id = intval($_POST['share_id']);
        $group_pass = intval($_POST['group_pass']);
        $share_find = D('Card_new_coupon_share')->where(['id'=>$share_id])->find();
        $relation_where['order_id'] = $share_find['order_id'];
        if($group_pass){
            $relation_where['group_pass'] = $group_pass;
        }else{
            $relation_where['id'] = $share_find['pass_id'];
        }
        $relation = D('Group_pass_relation')->where($relation_where)->find();
        if(!$share_find||!$relation){
            $this->returnCode(1, array(), L_('分享团购券不存在！'));
        }
        $order_id = intval($share_find['order_id']);
        $group_pass = $relation['group_pass'];

        $orderData = D('Group_order')->where(['order_id' => $order_id])->find();
        $user = D('User')->where(['uid' => $orderData['uid']])->find();
        $group = D('Group')->where(['group_id' => $orderData['group_id']])->find();
        if($group['is_give']!=1){
            $this->returnCode(1, array(), L_('此团购券不支持转赠！'));
        }
        $mer=D('Merchant')->where(['mer_id' => $orderData['mer_id']])->find();
        if($group['effective_type'] == 1){
            $deadline_time = '领取后'.$group['deadline_time'].'天过期';
        }else{
            $deadline_time = date("Y-m-d",$group['deadline_time']);
        }
        //获取商家绑定diypage自定义页面
        $diypage_store = D('Weixin_app_bind')->where(['other_id'=>$orderData['mer_id'],'bind_type'=>0])->find();

        $retData = [
            'mer_image'=>thumb_img($mer['logo'], 150, 150),
            'mer_name'=>$mer['name'],
            'coupon_title'=>"团购商品券",
            'user_logo' => thumb_img($user['avatar'], 150, 150),
            'title' => $user['nickname'] . '送你1份团购券',
            'group_image' => thumb_img($group['pic'], 240, 240),
            'desc'=>$group['name'],
            'over_date'=>$deadline_time,//过期时间
            'order_id'=>$order_id,
            'group_pass'=>$group_pass,
            'diypage_url'=>$diypage_store?(C('config.site_url') . '/wap.php?c=Diypage&a=page&page_id='.$diypage_store['mer_index_page']):''
        ];
        $coup=D('Group_pass_get')->where(['order_id' => $order_id, 'group_pass' => $relation['group_pass']])->find();
        if (empty($coup)) {//可以领
            $retData['btn_status']=1;
            $retData['btn_txt']="立即领取";
        } else {
            $retData['btn_status']=2;
            $retData['btn_txt']="该商品券已被领取";
        }

        if($group['is_give_limit']){
            $user_order=D('Group_order')->where(['uid' => $this->_uid,'group_id'=>$orderData['group_id'],'paid'=>1])->sum('num');
            if($user_order>=$group['once_max']){
                $retData['btn_status']=4;
                $retData['btn_txt']="该商品券数量您已达上限 无法领取";
            }
        }
        $share_time = $share_find['share_time'] + 60*60*24;
        if($share_time < time()){
            $retData['btn_status']=3;
            $retData['btn_txt']="该分享链接已经过期";
        }
        //更新分享券pass_id
        D('Card_new_coupon_share')->where(['id'=>$share_id])->save(['pass_id'=>$relation['id']]);
        $this->returnCode(0, $retData);
    }


    /**
     * 分享领取后商品券领取
     */
    public function share_coupon_get()
    {
        if (empty($this->_uid)) {
            $this->returnCode(1, array(), L_('请先进行登录！'));
        }
        $share_id = intval($_POST['share_id']);
        $share_find = D('Card_new_coupon_share')->where(['id'=>$share_id])->find();
        $relation = D('Group_pass_relation')->where(array('id'=>$share_find['pass_id']))->find();
        $order_id = intval($share_find['order_id']);
        $group_pass = $relation['group_pass'];

        $orderData = D('Group_order')->where(['order_id' => $order_id])->find();
        if($orderData['status']==1){
            $this->returnCode(1, array(), L_('此券已消费无法领取！'));
        }
        $uid =$this->_uid;
        if($orderData['uid']==$uid){
            $this->returnCode(1, array(), L_('无法领取自己分享的团购券！'));
        }
        $group = D('Group')->where(['group_id' => $orderData['group_id']])->find();
        $coup=D('Group_pass_get')->where(['order_id' => $order_id, 'group_pass' => $group_pass])->find();
        if(C('config.group_share_limit')!=0){
            $share_time = $share_find['share_time'] + C('config.group_share_limit')*60*60;
            if($share_time < time()){
                $this->returnCode(1, array(), L_('该分享链接已经过期！'));
            }
        }
        if(empty($coup)){
            $hasGet = D('Group_pass_get')->where(['group_pass' => $group_pass, 'uid' => ['gt', 0]])->find();
            if ($hasGet) {
                $this->returnCode(1, array(), L_('该商品券已被领取'));
            }

            if(!empty($group) && !empty($orderData)){
                $data['order_id']=$order_id;
                $data['uid']=$uid;
                $data['group_pass']=$group_pass;
                D('Group_pass_get')->data($data)->add();
                $time = time();
                $dataHadpull['use_time'] = date('Y-m-d H:i:s', $time);
                if($group['effective_type']==1){
                    // 领取后N天后过期
                    $deadline_time = $group['deadline_time'] * 60*60*24;
                    $end_time = $orderData['pay_time'] + $deadline_time;
                    $dataHadpull['receive_time'] = date('Y-m-d H:i:s',$deadline_time);    // 天数
                }else{
                    $end_time = $group['deadline_time'];
                    // 固定日期过期
                    $dataHadpull['receive_time'] = date('Y-m-d H:i:s', $group['deadline_time']);    // 时间戳
                }
                $dataCoupon = array(
                    'mer_id' => $orderData['mer_id'],
                    'pass_id' => $share_find['pass_id'],
                    'order_id' => $order_id,
                    'name' => $group['name'],
                    'image' => thumb_img($group['pic'], 240, 240),
                    'uid' => $uid,
                    'des' => $group['prefix_title'],
                    'des_detial' => $group['intro'],
                    'num' => 1,
                    'use_limit' => 1,
                    'start_time' => $time,
                    'end_time' => $end_time,
                    'receive_time' => $time,
                    'coupon_url' => 'c=Groupnew&a=eticket&order_id='.$order_id.'&group_pass='.$group_pass.'&own_id='.$orderData['uid'].'&path=coupon',
                );
                // 写入优惠券
                D('Card_new_coupon_give')->add($dataCoupon);

                $this->returnCode(0, '领取成功！');
            }else{
                $this->returnCode(1, array(), L_('没有此团购订单信息！'));
            }
        }else{
            $this->returnCode(1, array(), L_('商品券已被领取！'));
        }
    }


    /**
     * 优惠券状态更新接口
     */
    public function share_coupon_used()
    {
        $id = intval($_POST['id']);
        $status = intval($_POST['status']);
        if(!empty($id)){
            // 修改状态
            D('Card_new_coupon_give')->data(['id'=>$id])->save(['is_use'=>$status]);
            $this->returnCode(0, '操作成功！');
        }else{
            $this->returnCode(1, array(), L_('缺少优惠券参数！'));
        }
    }
}

?>