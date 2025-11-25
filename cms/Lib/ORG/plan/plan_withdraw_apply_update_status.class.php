<?php

/**
 * Class plan_withdraw_apply_update_status
 * 更新1小时内的提现申请（商家、用户提现）（1小时内的申请）
 */
class plan_withdraw_apply_update_status extends plan_base{
	public function runTask(){
        $apply_time = time() - 3600;
		//商家提现
        $merchant = M('Merchant_withdraw')->where([
            'status'=>10,
            'apply_withdraw_time'=>['egt',$apply_time]
        ])->field('*,1 as is_mer')->order('id ASC')->limit('0, 15')->select();
        //用户提现
        $user = M('Companypay')->where([
            'status'=>10,
            'pay_type'=>'user',
            'apply_withdraw_time'=>['egt',$apply_time]
        ])->field('*,pigcms_id as id,0 as is_mer')->order('pigcms_id ASC')->limit('0, 15')->select();
        $list = array_merge($merchant,$user);

        foreach ($list as $v){
            $type = $v['is_mer'] ? 'merchant_withdraw' : 'companypay';
            $log_info = M('Companypay_log')->where(['type'=>$type,'business_id'=>$v['id'],'status'=>2])->order('pigcms_id desc')->find();
            if(!$log_info){
                fdump_api(['id'=>$v['id'],'error'=>'数据查询失败'],'withdraw_apply/update_status_error',1);
                continue;
            }
            $applyInfo = json_decode($log_info['result'],true);
            fdump_api(['申请提现信息'=>$applyInfo],'withdraw_apply/update_status',1);
            if(!isset($applyInfo['transfer_batch']['batch_id']) && !isset($applyInfo['partner_trade_no'])){
                fdump_api(['id'=>$v['id'],'error'=>'未查询到转账批次id'],'withdraw_apply/update_status_error',1);
            }
            //查询状态（先查询批次信息，v3:如果批次信息查询到失败，再查询明细信息获取打款失败原因）
            if($v['is_v3']){
                import('@.ORG.withdraw.WeixinPayV3');
                $weixinObj = new WeixinPayV3();
                $batch_id = $applyInfo['transfer_batch']['batch_id'];
            }else{
                import('@.ORG.withdraw.Weixin');
                $weixinObj = new Weixin();
                $batch_id = $applyInfo['partner_trade_no'];
            }
            $result = $weixinObj->checkWithdrawApplyInfo($batch_id);
            fdump_api(['提现结果查询'=>$result],'withdraw_apply/update_status',1);
            if($result['error']){//报错
                fdump_api(['id'=>$v['id'],'error'=>$result['msg']],'withdraw_apply/update_status_error',1);
                continue;
            }
            if($result['apply_status'] != $v['status']){
                try {
                    // 开启事务
                    $database = D();
                    $database->startTrans();
                    //状态更新
                    $update = ['status'=>$result['apply_status'],'fail_reason'=>$result['fail_reason']];
                    if(!$v['is_mer'] && $result['apply_status']==1){
                        $update['pay_time'] = time();
                    }
                    if($v['is_mer']){
                        M('Merchant_withdraw')->where(['id'=>$v['id']])->save($update);
                    }else{
                        M('Companypay')->where(['pigcms_id'=>$v['id']])->save($update);
                    }
                    //增加日志
                    $logData = [
                        'business_id' => $v['id'],
                        'type' => $type,
                        'admin_id' => 0,
                        'status' => $result['apply_status']==20 ? 1 : 0
                    ];
                    D('CompanypayLog')->addLog($logData, $result['result']);
                    if($v['is_mer'] && $result['apply_status'] == 1){//商家提现成功
                        D('Merchant_money_list')->withdraw_status_notice($v['id']);
                    }
                    if(!$v['is_mer'] & $v['deliver_withdraw_id'] > 0 && $result['apply_status'] == 1){//用户提现
                        $model = '\app\deliver\model\service\DeliverUserService/handleWithDrawAfter';
                        $params = [
                            'withdrawId' => $v['deliver_withdraw_id'],
                            'status' => 1,
                            'params' => ['admin_id' => 0]
                        ];
                        invoke_v20_service($model, $params);
                    }
                    $database->commit();
                }catch(\Exception $e){
                    $database->rollback();
                    fdump_api(['id'=>$v['id'],'error'=>$e->getMessage()],'withdraw_apply/update_status_error',1);
                }
            }
        }
		return true;
	}
}
?>