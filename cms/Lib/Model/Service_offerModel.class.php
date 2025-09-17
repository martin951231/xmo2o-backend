<?php
// 修改订单状态
class Service_offerModel extends Model{

	// 配送员接收订单
	public function add_offer($publish_id,$puid, $admin_id=0, $third_deliver_user_info = []){

    	$publishInfo = D("Service_user_publish")->where(array('publish_id'=>$publish_id))->field('publish_id,uid,cid,order_sn,is_third,third_id,notifyurl')->find();

        $offer_info = $this->where(array('publish_id'=>$publish_id))->find();
        if($offer_info){
            return false;
        }
        
        $offer_data['publish_id'] = $publishInfo['publish_id'];
        $offer_data['price'] = 0;
        $offer_data['uid'] = $publishInfo['uid'];
        $offer_data['cid'] = $publishInfo['cid'];
        $offer_data['add_time'] = time();
        $offer_data['order_sn'] = date("ymdHis").rand(10,99).sprintf("%08d",$this->user_session['uid']);
        $offer_data['p_uid'] = $puid;
        $offer_data['status'] = 8;
        $offer_data['deliver_type'] = 1;

        $offer_id = $this->data($offer_data)->add();
        if($offer_id){
            $now_user = D('User')->get_user($publishInfo['uid']);
            if ($now_user['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $publish_id;
                // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
                $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $now_user['nickname'] . L_('您好！'), 'OrderSn' => $publishInfo['order_sn'], 'OrderStatus' =>L_('您发布的需求配送员已接单，请查看。'), 'remark' => date('Y.m.d H:i:s')));
            }
            // 进行极光App推送
            if ($now_user) {
                if (!$publishInfo['order_sn']) $publishInfo['order_sn'] = $publish_id;
                import('@.ORG.Apppush');
                $order['status'] = 8;
                $order['order_id'] = $publish_id;
                $order['order_sn'] = $publishInfo['order_sn'];
                $order['uid'] = $publishInfo['uid'];
                $order['catgory_type'] = $publishInfo['catgory_type'];
                $apppush = new Apppush();
                $apppush->send($order, 'publish');
            }
            
            D('Service_offer_record')->data(array('offer_id'=>$offer_id,'publish_id'=>$publish_id,'add_time'=>time(),'remarks'=>L_(($deliver_user['msg']??'配送员已接单')).($third_deliver_user_info['note']??'')))->add();
            D("Service_user_publish")->where(array('publish_id'=>$publish_id))->save(array('status' => 8));

            // 处理返回信息
            // 获取配送员信息
            if($puid==0&&$third_deliver_user_info){ //跑腿第三方配送
                fdump_api($third_deliver_user_info,'mall_third_deliver/changeStatusOrder',1);
                $user = $third_deliver_user_info;
            }else {
                $user = D('Deliver_user')->field(true)->where(array('uid' => $puid))->find();
            }
            // 添加记录
            $log = array(
                'publish_id' => $publishInfo['publish_id'],
                'uid' => $user['uid']?$user['uid']:0,
                'status' => 2,
                'name' => $user?$user['name']:'',
                'phone' => $user?$user['phone']:'',
                'remark' => $deliver_user['msg']??'配送员已经接单，待取货',
                'type' => $admin_id>0?2:1,
                'admin_id' => $admin_id>0?$admin_id:0,
            );
            if($puid==0&&$third_deliver_user_info){
                if(!empty($third_deliver_user_info['lng'])&&!empty($third_deliver_user_info['lat'])){
                    $log['lng'] = $third_deliver_user_info['lng'];
                    $log['lat'] = $third_deliver_user_info['lat'];
                }
            }
            D('Service_user_publish_log')->add_log($log);
            if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                // 回调信息
                $notifyurl = $publishInfo['notifyurl'];
                $notify_data = array(
                    'third_id' => $publishInfo['third_id'],
                    'publish_id' => $publishInfo['publish_id'],
                    'status' => 2,
                    'name' => $user?$user['name']:'',
                    'phone' => $user?$user['phone']:'',
                    'msg' => $deliver_user['msg']??'配送员已经接单，待取货',
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }


            /*-----记录系统订单信息 配送员接单-即 19/1/24--服务快派-*/
            $order_id = M('Plat_order')->where(array('business_type' => 'service', 'business_id'=> $publish_id))->getField('order_id');
            $set_data = array(
                'real_orderid' => $offer_data['order_sn'],
                'system_status' => 1
            );
            D("System_order")->save_order_info('service',$order_id, $set_data);

			return $offer_id;
		}else{
			return false;
		}
    }

    // 修改订单状态
    public function offer_save_status($publish_id,$puid,$offer_id,$status, $admin_id=0, $third_deliver_user_info = []){
        if($puid==0&&$third_deliver_user_info){ //跑腿第三方配送
            $offer_info = $this->where(['deliver_type'=>1,'publish_id'=>$publish_id])->find();
            if(!$offer_info){
                fdump_api([$offer_info,$publish_id],'mall_third_deliver/changeStatusOrder',1);
                return false;
            }
            $offer_id = $offer_info['offer_id'];
        }
    	$user_publish_where['publish_id']  = $publish_id;
    	D("Service_user_publish")->where($user_publish_where)->save(array('status' => $status));

        $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,uid,cid,order_sn,is_third,third_id,notifyurl')->find();
        
    	$offer_where['p_uid'] = $puid;
    	$offer_where['offer_id'] = $offer_id;
    	$offer_where['deliver_type'] = 1;
    	fdump_api([$publish_id,$puid,$offer_id,$status, $admin_id], 'deliver/legwork', 1);
        if($status == 4){
            $remarks = L_('订单已完成').($third_deliver_user_info['note']??'');
        }else if($status == 9){
            $remarks = L_('配送员送货中').($third_deliver_user_info['note']??'');
        }else if($status == 8){
            $remarks = $third_deliver_user_info['msg'] ?? L_('配送员已接单');
        }
        $res_info = $this->where($offer_where)->save(array('status' => $status));
    	if($res_info!==false){

            D('Service_offer_record')->data(array('offer_id'=>$offer_id,'publish_id'=>$publish_id,'add_time'=>time(),'remarks'=>$remarks))->add();
            $now_user = D('User')->get_user($publishInfo['uid']);
            if ($now_user['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $publish_id;
                // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
                $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $now_user['nickname'] . L_('您好！'), 'OrderSn' => $publishInfo['order_sn'], 'OrderStatus' =>L_('您发布的需求').$remarks.L_('，请查看。'), 'remark' => date('Y.m.d H:i:s')));
//                $aaaa = '您发布的需求'.$remarks.'，请查看。';
            }
            // 进行极光App推送
            if ($status != 0 && $status != 1) {
                if (!$publishInfo['order_sn']) $publishInfo['order_sn'] = $publish_id;
                import('@.ORG.Apppush');
                $order['status'] = $status;
                $order['order_id'] = $publish_id;
                $order['order_sn'] = $publishInfo['order_sn'];
                $order['uid'] = $publishInfo['uid'];
                $order['catgory_type'] = $publishInfo['catgory_type'];
                $apppush = new Apppush();
                $apppush->send($order, 'publish');
            }
            /*-----记录系统订单信息 配送员接单-即 19/1/24--服务快派-*/
            $order_id = M('Plat_order')->where(array('business_type' => 'service', 'business_id'=> $publish_id))->getField('order_id');
            if ($status == 4) {
                $set_data = array(
                    'real_orderid' => $publishInfo['order_sn'],
                    'system_status' => 6
                );
                D("System_order")->save_order_info('service',$order_id, $set_data);
            } elseif ($status == 8 || $status == 9) {
                $set_data = array(
                    'real_orderid' => $publishInfo['order_sn'],
                    'system_status' => 1
                );
                D("System_order")->save_order_info('service',$order_id, $set_data);
            }


            // 处理返回信息
            // 获取配送员信息
            if($puid==0&&$third_deliver_user_info){
                $user = $third_deliver_user_info;
            }else {
                $user = D('Deliver_user')->field(true)->where(array('uid' => $puid))->find();
            }
            if ($status==8) {
                $log_status = 2;
                $msg = $third_deliver_user_info['msg'] ?? '配送员已经接单，待取货';
            } elseif ($status==9) {
                $log_status = 3;
                $msg = '配送员取货，配送中';
            } elseif ($status==4) {
                $log_status = 4;
                $msg = '配送员送达，订单完成';
            }

            // 添加记录
            $log = array(
                'publish_id' => $publishInfo['publish_id'],
                'uid' => $user['uid']?$user['uid']:0,
                'status' => $log_status,
                'name' => $user?$user['name']:'',
                'phone' => $user?$user['phone']:'',
                'remark' => $msg,
                'type' => $admin_id>0?2:1,
                'admin_id' => $admin_id>0?$admin_id:0,
            );
            if(!empty($offer_info)){
                if(!empty($third_deliver_user_info['lng'])&&!empty($third_deliver_user_info['lat'])){
                    $log['lng'] = $third_deliver_user_info['lng'];
                    $log['lat'] = $third_deliver_user_info['lat'];
                }
            }
            D('Service_user_publish_log')->add_log($log);
            if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                // 回调信息
                $notifyurl = $publishInfo['notifyurl'];
                $notify_data = array(
                    'third_id' => $publishInfo['third_id'],
                    'publish_id' => $publishInfo['publish_id'],
                    'status' => $log_status,
                    'name' => $user?$user['name']:'',
                    'phone' => $user?$user['phone']:'',
                    'msg' => $msg,
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }

            //帮我买帮我送订单送达，结算给配送员费用和小费
            if ($status == 4 && $publishInfo['is_third_deliver']!=1) {
                $supply = D('Deliver_supply')->where(['item' => 3, 'order_id' => $publish_id, 'is_settlement' => 0])->find();
                if ($supply && $supply['uid'] > 0) {
                    $model = '\app\deliver\model\service\DeliverUserService/addMoney';
                    $params = [
                        'uid' => $supply['uid'],
                        'businessType' => 'service',
                        'businessId' => $supply['order_id'],
                        'money' => $supply['deliver_user_fee'] > 0 ? $supply['deliver_user_fee'] : 0,
                        'desc' => '配送收入',
                        'tipMoney' => $supply['tip_price'] > 0 ? $supply['tip_price'] : 0,
                        'tipDesc' => '小费收入',
                    ];
                    $result = invoke_v20_service($model, $params);
                    D('Deliver_supply')->where(['supply_id' => $supply['supply_id']])->save(['is_settlement' => 1]);
					$params['puid'] = $puid;
					$params['result'] = $result;
					$params['create_time'] = date('Y-m-d H:i:s');
					fdump_sql($params,'deliver_user_paotui_finish');
                }
            }

			return true;
		}else{
			return false;
		}
    }

    public function cancel_order($publish_id, $puid=0){
        if($this->where(array('publish_id'=>$publish_id))->delete()){
            D("Service_user_publish")->where(array('publish_id'=>$publish_id))->data(array('status'=>2))->save();
            $user_publish_where['publish_id']  = $publish_id;
            $publishInfo = D("Service_user_publish")->where($user_publish_where)->field('publish_id,is_third,third_id,notifyurl')->find();

            // 处理返回信息
            // 获取配送员信息
            $user = D('Deliver_user')->field(true)->where(array('uid' => $puid))->find();

            // 添加记录
            $log = array(
                'publish_id' => $publishInfo['publish_id'],
                'uid' => $user['uid']?$user['uid']:0,
                'status' => 13,
                'name' => $user?$user['name']:'',
                'phone' => $user?$user['phone']:'',
                'remark' => '配送员取消配送扔回',
                'type' => 1,
            );
            D('Service_user_publish_log')->add_log($log);
            if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                // 回调信息
                $notifyurl = $publishInfo['notifyurl'];
                $notify_data = array(
                    'third_id' => $publishInfo['third_id'],
                    'publish_id' => $publishInfo['publish_id'],
                    'status' => 13,
                    'name' => $user?$user['name']:'',
                    'phone' => $user?$user['phone']:'',
                    'msg' => '配送员取消配送扔回',
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }
            /*-----记录系统订单信息 配送员接单-即 19/1/24--服务快派-取消订单，改为已支付待服务状态-*/
            $order_id = M('Plat_order')->where(array('business_type' => 'service', 'business_id'=> $publish_id))->getField('order_id');
            D("System_order")->save_order_status('service',$order_id, 0);
            return true;
        }else{
            return false;
        }
    }

}