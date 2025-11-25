<?php
// 商家余额
class merchant_money_list{
    //减少余额
    public function use_money($mer_id,$money,$type,$desc,$order_id,$percent=0,$system_take = 0,$mer_user=array(),$store_id=0){
        $date['mer_id']=$mer_id;
        $date['income'] = 2;
        $date['order_id'] = $order_id;
        $date['store_id'] = $store_id;
        if($percent){
            $date['percent'] = $percent;
            $date['system_take'] = $system_take;
        } elseif ($system_take && C('config.merchant_withdraw_fee_type')==1&& C('config.company_pay_mer_money')>0) {
            $date['system_take'] = $system_take;
        }
        $date['use_time']= time();
        $date['type']= $type;
        $date['desc']=  $desc;
        $date['money']=  $money;
        if(!M('Merchant')->where(array('mer_id'=>$mer_id))->setDec('money', $date['money'])){
            return array('error_code'=>true,'msg'=>$desc.L_('，使用失败！'));
        }
        $now_mer_money = M('Merchant')->field('money,mch_owe_money')->where(array('mer_id'=> $mer_id))->find();
        loadSystemOrg('templateNews');
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $mer_status = 1;
        if(C('config.open_mer_owe_money')==0){
            $now_mer_money['mch_owe_money'] = 0;
        }

        if($now_mer_money['money']<$now_mer_money['mch_owe_money']*(-1)){
            M('Merchant')->where(array('mer_id'=>$mer_id))->setField('status',3);
            $mer_status = 3;
            $this->merchant_owe_money_notice($mer_id);
        }else  if($now_mer_money['money']<0){
            $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $now_mer_money['openid'], 'first' => L_("X1，当前商家余额：X2，您的商家状态为欠费，您的商家业务状态为禁止状态，请及时充值",array("X1" => $desc,"X2" => $now_mer_money['money'])) , 'keyword1' => L_('商家余额使用'), 'keyword2' =>$money,'keyword3' =>'', 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => L_('请到商家中心商家余额中查看')), $mer_id);

        }
        if(!empty($mer_user['openid'])){
            if($mer_status==3){
                $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $now_mer_money['openid'], 'first' => L_("X1，当前商家余额：X2，您的商家状态为欠费，您的商家业务状态为禁止状态，请及时充值",array("X1" => $desc,"X2" => $now_mer_money['money']))  , 'keyword1' => L_('商家余额使用'), 'keyword2' =>$money,'keyword3' =>'', 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => L_('请到商家中心商家余额中查看')), $mer_id);
            }else{
                $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $now_mer_money['openid'], 'first' => L_("X1，当前商家余额：X2",array("X1" => $desc,"X2" => $now_mer_money['money']))  , 'keyword1' => L_('商家余额使用'), 'keyword2' =>$money,'keyword3' =>'', 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => L_('请到商家中心商家余额中查看')), $mer_id);

            }
        }

        $date['now_mer_money'] = $now_mer_money['money'];
        if(!$this->add($date)){
            return array('error_code'=>true,'msg'=>$desc.'，'.L_('保存失败！'));
        }else{
			$now_merchant = plugin_model('Merchant')->get_info($mer_id);
			if(C('config.nmgzhcs_appid') && $now_merchant['nmgzhcs_appid']){
				$this->nmgzhcs_money($now_merchant['nmgzhcs_appid'],$date['order_id'],'-'.$money,$desc);
			}
            return array('error_code'=>false,'msg'=>$desc.'，'.L_('保存成功！'));
        }
    }

    public function merchant_owe_money_notice($mer_id){
        $where['mer_id'] = $mer_id;
        $merchant = M('Merchant')->field('name,area_id,city_id ,province_id')->where($where)->find();

        $where_admin['openid']=array('neq','');
        $where_admin['withdraw_notice']=1;
        $admin_list = M('Admin')->where($where_admin)->select();
        $tmp_area = array(
            $merchant['area_id'],$merchant['city_id'],$merchant['province_id']
        );
        foreach ($admin_list as $v) {
            if($v['level']==2 || $v['level']==0){
                $send_to[] = $v;
            }else if(in_array($v['area_id'],$tmp_area)){
                $send_to[] = $v;
            }
        }
        loadSystemOrg('templateNews');
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        foreach ($send_to as $s) {
            $href ='';
            $model->sendTempMsg('OPENTM401300510', array('href' => $href,
                'wecha_id' => $s['openid'],
                'first' => L_('欠款通知'),
                'keyword1' =>"商户【{$merchant['name']}】",
                'keyword2' => L_('欠款').$merchant['money'],
                'remark' =>L_('时间X1,您的帐户余额已欠费，请尽快充值',date('Y-m-d H:i',time()))),
            0);
        }
    }

	public function nmgzhcs_money($business_appid,$order_id,$money,$desc){
		$appid = C('config.nmgzhcs_appid') ;
		$appsecret = C('config.nmgzhcs_appsecret');

		$url=C('config.nmgzhcs_base_url').'/api/businessmoney/index';

		$params=array(
			'appid'=>$appid,
			'business_appid'=>$business_appid,
			'money'=>$money,
			'order_code'=>$order_id,
			'xnote'=>$desc,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);
		fdump($params,'merchant_money',true);
		fdump($res,'merchant_money',true);
	}
	
}
