<?php

/**
 * IM排队队列钩子
 */
class ImQueue
{
    public function consume(array $params)
    {
        $storeid = isset($params['store_id']) ? intval($params['store_id']) : 0;
        $is_village = isset($params['is_village']) ? intval($params['is_village']) : 0;
        if ($storeid < 1) {
            return false;
        } else {
            $log = M('Im_queue')->where(array('store_id' => $storeid))->order('id ASC')->find();
            if ($log) {
                if($is_village){
                    $fromUser = Im_userModel::PREFIX_VILLAGE_USER . '_' . $log['uid'];
                }else{
                    $fromUser = Im_userModel::PREFIX_USER . '_' . $log['uid'];
                }
                if($log['store_id'] == 0){
                    $toUser = Im_userModel::PREFIX_PLAT . '_' . $log['store_id'];
                }else{
                    if($is_village){
                        $toUser = Im_userModel::PREFIX_VILLAGE . '_' . $log['store_id'];
                    }else{
                        $toUser = Im_userModel::PREFIX_STORE . '_' . $log['store_id'];
                    }
                }
                if($is_village){
                    $rs = D('Merchant_store_kefu')->assignVillageKefu($fromUser, $toUser, '', '');
                }else{
                    $rs = D('Merchant_store_kefu')->assignKefu($fromUser, $toUser, '', '');
                }
                if ($rs['code'] == 0) {
                    //分配客服成功，创建会话增加连接数,从排队中删除
                    D('Im_conversation')->build($fromUser, $toUser, Im_conversationModel::TYPE_SINGLE);
                    M('Merchant_store_kefu')->where(array('username' => $rs['retval']['username']))->setInc('cur_receive');
                    M('Im_queue')->where(array('id'=>$log['id']))->delete();
                }
            }
        }
    }
}