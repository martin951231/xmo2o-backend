<?php
// 社区余额
    class Village_money_listModel extends Model{
        //增加余额
        public function add_money($order_info,$is_fenzhang=0,$is_new_charge=0){
            //社区绑定用户
            $timeDate=date('dH');
            fdump_api(['小区支付'.__LINE__,$order_info,$is_fenzhang],'VillageMoneyListLog/add_money'.$timeDate,1);
            if(!isset($order_info['money'])){
                $order_info['money'] = isset($order_info['pay_money'])?$order_info['pay_money']:0;
            }
            $order_money = $order_info['money'];
            $score_deducte_money=0;
            if(isset($order_info['score_deducte_money']) && $order_info['score_deducte_money']>0){
                $score_deducte_money=$order_info['score_deducte_money'];
            }
            $add_money_extra='';
            if(isset($order_info['add_money_extra']) && !empty($order_info['add_money_extra'])){
                $add_money_extra=$order_info['add_money_extra'];
            }
            $business_order_id=0;
            $pay_order_info='';
            if($is_new_charge == 1){
                //todo 新版收费订单表
                $amount_money = $order_money;
                if(isset($order_info['plat_order_id']) && $order_info['plat_order_id']>0){
                    $business_order_id=$order_info['plat_order_id'];
                    $plat_info = M('Plat_order')->where(['order_id'=>$order_info['plat_order_id']])->find();
                    $where_order = [];
                    $where_order['business_order_id'] = $order_info['plat_order_id'];
                    $where_order['business'] = 'village_new_pay';
                    $where_order['paid_money'] = array('gt',0);
                    $info = M('Pay_order_info')->where($where_order)->field('*')->order('id DESC')->find();
                    $pay_order_info=$info;
                    if($info && $info['channel'] == 'chinaums'){
                        $chinaums_merchant_already_get = getFormatNumber(($order_money/$info['paid_money'])*($info['chinaums_merchant_already_get']/100));
                        $order_info['money'] = getFormatNumber($order_info['money'] - $chinaums_merchant_already_get);
                    }
                    if($order_info['money']<0){
                        $order_info['money'] = 0;
                    }
                    //新版缴费
                    if($info && $info['is_own'] > 0){
                        $amount_money = $info['current_score_deducte'] + $info['current_system_balance'];
                        if($amount_money > 0){
                            $amount_money = getFormatNumber(($order_money/$plat_info['total_money'])*($amount_money/100));
                        }else{
                            $amount_money = 0;
                        }
                        if($score_deducte_money>0){
                            $amount_money =$score_deducte_money;
                        }
                    }else{
                        $amount_money = $order_money;
                    }
                }
                $order_info['money'] = $amount_money;
                fdump_api(['小区支付'.__LINE__,'amount_money'=>$amount_money,$order_info,'info'=>$info],'VillageMoneyListLog/add_money'.$timeDate,1);
               /* if($info['channel'] == 'scrcu'){
                    $order_info['money'] = 0;
                }*/
            }
            else{
                if(isset($order_info['plat_order_id'])){
                    $business_order_id=$order_info['plat_order_id'];
                    $plat_info = M('Plat_order')->where(['order_id'=>$order_info['plat_order_id']])->find();
                    if(isset($plat_info['orderid']) && $plat_info['orderid']){
                        $where_order_info = [];
                        $where_order_info['paid_money'] = array('gt',0);
                        $where_order_info['orderid'] = $plat_info['orderid'];
                        $info = M('Pay_order_info')->where($where_order_info)->field('*')->find();
                        $pay_order_info=$info;
                        if(empty($info)){
                            $where_order = [];
                            $where_order['orderid'] = $plat_info['orderid'];
                            $where_order['order_type'] = 'plat';
                            $where_order['paid_money'] = array('gt',0);
                            $info = M('Payment_money_order')->where($where_order)->field('pay_method,paid_money,chinaums_merchant_already_get,is_own')->order('id DESC')->find();
                        }
                    }else{
                        $info = [];
                    }
                    //$order_info['money'] = $order_info['money'] - ceil($order_info['money']*100*$percent)/100;
                    if($info && isset($info['pay_method'])){
                        //老版缴费
                        if($info['is_own'] > 0){
                            $amount_money = $plat_info['system_balance'] + $plat_info['system_score_money'];
                            if($amount_money > 0){
                                $amount_money = getFormatNumber(($order_money/$plat_info['total_money'])*$amount_money);
                            }else{
                                $amount_money = 0;
                            }
                        }else{
                            $amount_money = $order_money;
                        }
                        $order_info['money'] = $amount_money;
                    }

                    if($info && isset($info['channel'])){
                        //新版缴费
                        if($info['is_own'] > 0){
                            $amount_money = $info['current_score_deducte'] + $info['current_system_balance'];
                            if($amount_money > 0){
                                $amount_money = getFormatNumber(($order_money/$plat_info['total_money'])*($amount_money/100));
                            }else{
                                $amount_money = 0;
                            }
                        }else{
                            $amount_money = $order_money;
                        }
                        $order_info['money'] = $amount_money;
                    }
                    fdump_api(['小区支付2'.__LINE__,'amount_money'=>$amount_money,$order_info,'info'=>$info],'VillageMoneyListLog/add_money'.$timeDate,1);
                }
            }
            fdump_api(['小区支付'.__LINE__,'business_order_id'=>$business_order_id],'VillageMoneyListLog/add_money'.$timeDate,1);
            $village_info = D("House_village")->get_village_info($order_info['village_id'],'property_id,percent');
            if($village_info['percent'] > 0)
                $percent = $village_info['percent'];
            else
                $percent = C('config.platform_get_village_percent');
            $percent = $percent/100;
       
            if($order_info['money']<0){
                $order_info['money'] = 0;
            }
            $date=array();
            $village_id = $order_info['village_id'];
            if($is_fenzhang)
                $desc = '【随行付--分账收入】'.$order_info['desc'];
            else
                $desc = $order_info['desc'];
            $now_village = D('House_village')->get_one($order_info['village_id']);
            $date['village_id'] = $order_info['village_id'];
            $order_info['is_own'] -=4;

            //自有支付
            if(isset($order_info['is_own'])&&$order_info['is_own']>0){
                //$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                //$alias_name = $this->get_alias_c_name($order_info['order_type']);
                //$store_name = '无';
                //$remark = '请到社区平台中查看';
                //$model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $village_user['openid'], 'first' => '收款成功,' , 'keyword1' => $alias_name[$order_info['order_type']], 'keyword2' =>$order_info['payment_money'],'keyword3' =>$store_name, 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' =>$remark), $village_id);

                if(C('config.open_village_own_percent')==1){
                    $own_percent_money = $order_info['payment_money'];
                }
                $order_info['payment_money']=0;
                $order_info['money']=0;
            }

            $income = 1;
            switch($order_info['order_type']){
                case 'sqrecharge':
                    $num =1;
                    $money = $order_info['pay_money'];
                    $date['money']= $money;
                    $date['total_money']= $money;
                    break;
                case 'village_pay':
                    $village_pay_info=M('house_village_pay_order')->where(['order_id'=>$order_info['order_id']])->field('order_id,order_type,order_name')->find();
                    if (!empty($village_pay_info)){
                        $date['fromType']= $village_pay_info['order_type'];
                        $date['fromId']= $village_pay_info['order_id'];
                        $date['fromTable']= 'house_village_pay_order';
                    }
                    $num =1;
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte'];
                    $date['money']= $order_info['money'];
                    $date['total_money']= $money;
                    break;
                case 'village_new_pay':
                    $num =1;
                    if(isset($order_info['order_type_special']) && ($order_info['order_type_special']=='village_balance_recharge')){
                        $date['money']= $order_info['money'];
                        $date['total_money']= $order_info['money'];
                    }else{
                        $village_pay_info=M('House_new_pay_order')->where(['order_id'=>$order_info['order_id']])->field('order_id,order_type,order_name')->find();
                        if (!empty($village_pay_info)){
                            $date['fromType']= $village_pay_info['order_type'];
                            $date['fromId']= $village_pay_info['order_id'];
                            $date['fromTable']= 'house_new_pay_order';
                        }
                        $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['village_balance'];
                        $date['money']= $order_info['money'];
                        $date['total_money']= $money;
                    }
                    break;
                case 'scanpay':
                    $village_pay_info=M('house_new_pay_order')->where(['order_id'=>$order_info['order_id']])->field('order_id,order_type,order_name')->find();
                    if (!empty($village_pay_info)){
                        $date['fromType']= $village_pay_info['order_type'];
                        $date['fromId']= $village_pay_info['order_id'];
                        $date['fromTable']= 'house_new_pay_order';
                    }
                    $num =1;
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte'];
                    $date['money']= $order_info['money'];
                    $date['total_money']= $money;
                    break;
                case 'new_village_refund':
                case 'pile_refund':
                    $num =1;
                    $income = 2;
                    $money = $order_info['refund_money'];
                    $date['money']= $order_info['refund_money'];
                    $date['total_money']= $money;
                    $date['type'] = 'pile_refund';
                    $order_info['score_used_count']=0;
                    $order_info['score_can_get']=0;
                    break;
                case 'village_pay_cashier':
                    $village_pay_info=M('house_village_pay_order')->where(['cashier_id'=>$order_info['order_id']])->field('order_id,order_type,order_name')->find();
                    if (!empty($village_pay_info)){
                        $date['fromType']= $village_pay_info['order_type'];
                        $date['fromId']= $village_pay_info['order_id'];
                        $date['fromTable']= 'house_village_pay_order';
                    }
                    $num =1;
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte'];
                    $date['money']= $order_info['money'];
                    $date['total_money']= $money;
                    break;
                case 'express':
                    $num =1;
                    $money = $order_info['balance_pay']+$order_info['payment_money'];
                    $date['money']= $money;
                    $date['total_money']= $money;
                    break;
                case 'withdraw':
                    $num =1;
                    $money = $order_info['money'];
                    $date['money']= $money;
                    $date['total_money']= $money;
                    break;
                case 'custom':
                    $num =1;
                    $money = $order_info['money'];
                    $date['money']= $money;
                    $date['total_money']= $money;
                    break;
                case 'pile':
                    $num =1;
                    $money = $order_info['pay_money'];
                    $date['money']= $money;
                    $date['total_money'] = $money ;
                    break;
                case 'village_pile_pay':
                    $village_pay_info=M('house_village_pay_order')->where(['order_id'=>$order_info['order_id']])->field('order_id,order_type,order_name')->find();
                    if (!empty($village_pay_info)){
                        $date['fromType']= $village_pay_info['order_type'];
                        $date['fromId']= $village_pay_info['order_id'];
                        $date['fromTable']= 'house_village_pay_order';
                    }
                    $num =1;
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte'];
                    $date['money']= $order_info['money'];
                    $date['total_money']= $money;
                    break;
                case 'sqsmsbuy':
                    $money = $order_info['pay_money'];
                    $date['money']= 0;
                    $date['total_money'] = 0 ;
                    break;
                default:
                    $money =0;
                    $date['money']= $money;
                    break;
            }
            if($now_village['percent']>0){
                $percent = $now_village['percent'];
            }else{
                $percent = C('config.platform_get_village_percent');
            }

            $village_money_info=$this->where(['village_id'=>$date['village_id'],'order_id'=>$order_info['order_id'],'income'=>$income,'type'=>$order_info['order_type']])->find();
            if (!empty($village_money_info)){
                return array('error_code'=>true,'msg'=>$desc.' ，保存社区收入失败！');
            }
            if(C('config.open_village_own_percent') && $own_percent_money>0){
                $desc_pay_for_system=L_('对社区自有支付抽成，在线自有支付支付金额：').$own_percent_money.',扣除社区余额：'.sprintf("%.2f",$own_percent_money*$percent/100);
                $result_pay = $this->use_money($village_id,sprintf("%.2f",$own_percent_money*$percent/100),$order_info['order_type'],$desc_pay_for_system,$order_info['order_id'],$percent,sprintf("%.2f",$own_percent_money*$percent/100));

            }

            $date['num'] = $num>0?$num:1;
            $date['type']=$order_info['order_type'];

            $date['order_id'] = $order_info['order_id'];
            
            if($order_info['order_type']!='withdraw'  && $order_info['order_type']!='sqrecharge' && $is_fenzhang == 0){
                $date['system_take']= ($date['money']*$percent/100);
                $date['money']= sprintf("%.2f",$date['money']*(100-$percent)/100);
                $date['percent'] = $percent;
            }else{
                $date['percent'] = 0;
            }
            $extra_data=array();
            if ($business_order_id>0){
                $date['business_order_id']=$business_order_id;
               // $moneyInfo=$this->where(array('business_order_id'=>$business_order_id))->find();
                if( $pay_order_info && $pay_order_info['channel'] == 'chinaums' && (empty($add_money_extra)|| !isset($add_money_extra['is_chinaums_divide'])) ) {
                    $village_pay_config = M('House_village_pay_config')->where(array('village_id'=>$village_id))->find();
                    if($village_pay_config && $village_pay_config['chinaums_zg_mid'] && $pay_order_info['chinaums_merchant_already_get']>0){
                        $date['money']=$score_deducte_money;
                        if(isset($order_info['balance_pay'])){
                            $date['money']=$score_deducte_money+$order_info['balance_pay'];
                        }
                        $desc= $desc.'（银联支付分账）';
                    }
                }
            }
            if(isset($order_info['is_chinaums_divide']) && $order_info['is_chinaums_divide']==1){
                $date['money']=$score_deducte_money;
                if(isset($order_info['balance_pay'])){
                    $date['money']=$score_deducte_money+$order_info['balance_pay'];
                }
                if(isset($order_info['chinaums_system_take']) && $order_info['chinaums_system_take']){
                    $date['system_take']=$order_info['chinaums_system_take'];
                }
                $desc= $desc.'（银联分账）';
            }
            if(isset($order_info['is_pay_need_divide']) && $order_info['is_pay_need_divide']==1){
                $date['money']=$score_deducte_money;
                if(isset($order_info['balance_pay'])){
                    $date['money']=$score_deducte_money+$order_info['balance_pay'];
                }
                if(isset($order_info['divide_system_take']) && $order_info['chinaums_system_take']){
                    $date['system_take']=$order_info['divide_system_take'];
                }
                if(isset($order_info['divide_desc']) && $order_info['divide_desc']){
                    $desc= $order_info['divide_desc'];
                }else{
                    $desc= $desc.'（社区分账）';
                }
               
            }
            if(!empty($add_money_extra) && isset($add_money_extra['is_chinaums_divide']) && $add_money_extra['is_chinaums_divide']>0){
                $date['money']=$score_deducte_money;
                if(isset($order_info['balance_pay'])){
                    $date['money']=$score_deducte_money+$order_info['balance_pay'];
                }
                $date['system_take']= isset($add_money_extra['system_take']) && $add_money_extra['system_take'] ? $add_money_extra['system_take']:0;
                if(isset($add_money_extra['desc']) && $add_money_extra['desc'] ){
                    $desc= $desc.' '.$add_money_extra['desc'];
                }
               $extra_data['chinaums_divide_new']=json_encode($add_money_extra,JSON_UNESCAPED_UNICODE);
            }

            if(!empty($add_money_extra) && isset($add_money_extra['is_lakala_divide']) && $add_money_extra['is_lakala_divide']>0){
                $date['money']=$score_deducte_money;
                if(isset($order_info['balance_pay'])){
                    $date['money']=$score_deducte_money+$order_info['balance_pay'];
                }
                $date['system_take']= isset($add_money_extra['system_take']) && $add_money_extra['system_take'] ? $add_money_extra['system_take']:0;
                if(isset($add_money_extra['desc']) && $add_money_extra['desc'] ){
                    $desc= $desc.' '.$add_money_extra['desc'];
                }
                $extra_data['lakala_divide_info']=json_encode($add_money_extra,JSON_UNESCAPED_UNICODE);
            }
            
            if(!empty($extra_data)){
                $date['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
            }
            $date['income'] = $income;
            $date['use_time']= time();
            $date['desc']=  empty($desc)?'':$desc;
            $date['score_count'] = $order_info['score_used_count'] ? $order_info['score_used_count'] : 0;
            $date['score'] = $order_info['score_can_get'] ? $order_info['score_can_get'] : 0;
            $use_time_min=$date['use_time']-2;
            $use_time_max=$date['use_time']+1;
            $whereMoneyStr='village_id='.$date['village_id'].' AND order_id="'.$date['order_id'].'" AND type="'.$date['type'].'" AND income='.$income.' AND use_time>='.$use_time_min.' AND use_time<='.$use_time_max;
            $moneyInfo=$this->where($whereMoneyStr)->find();
            if(!empty($moneyInfo) && isset($moneyInfo['id']) && $moneyInfo['id']){
                fdump_api([__LINE__,'date'=>$date,'is_fenzhang'=>$is_fenzhang,'whereMoneyStr'=>$whereMoneyStr,'moneyInfo'=>$moneyInfo],'VillageMoneyListLog/add_money'.$timeDate,1);
                return array('error_code'=>false,'msg'=>$desc.' ，保存社区收入成功了！');
            }
            $village_info_tmp = M('House_village')->where(array('village_id'=> $village_id))->field('village_id,village_name,village_address,money')->find();
            fdump_api([__LINE__,'date'=>$date,'is_fenzhang'=>$is_fenzhang,'village_info_tmp'=>$village_info_tmp],'VillageMoneyListLog/add_money'.$timeDate,1);
            if(isset($date['money']) && $date['money']>0){
                $date['now_village_money'] = $village_info_tmp['money']+$date['money'];
                if($income==2){
                    $date['now_village_money'] = $village_info_tmp['money']-$date['money'];
                }
            }elseif(isset($date['money']) && $date['money']<0){
                $date['now_village_money'] = $village_info_tmp['money']+$date['money'];
            }else{
                $date['now_village_money'] = $village_info_tmp['money'];
            }
            fdump_api([__LINE__,'date'=>$date,'is_fenzhang'=>$is_fenzhang,'village_info_tmp'=>$village_info_tmp],'VillageMoneyListLog/add_money'.$timeDate,1);
            $insert_ret=$this->add($date);
            if(!$insert_ret){
                return array('error_code'=>true,'msg'=>$desc.' ，保存社区收入失败！');
            }else{
                if($is_fenzhang){
                    M('House_village_pay_order')->where(array('order_id' => $order_info['order_id']))->setField('is_pay_bill', 2);
                    $date['type'] = $order_info['order_type'];
                }else{
                    $ret=M('House_village')->where(array('village_id'=>$village_id))->setInc('money', $date['money']);
                    if(  !$ret  ){
                        //return array('error_code'=>true,'msg'=>L_('增加社区余额失败'));
                    }elseif($order_info['order_type']=='village_pay'){
                        M('House_village_pay_order')->where(array('order_id' => $order_info['order_id']))->setField('is_pay_bill', 2);
                        $date['type'] = $order_info['order_type'];
                    }elseif($order_info['order_type']=='pile_refund'){
                        M('House_village')->where(array('village_id'=>$village_id))->setDec('score', $date['score']*2);
                        M('House_village')->where(array('village_id'=>$village_id))->setDec('score_used_count', $date['score_used_count']*2);
                        M('House_village')->where(array('village_id'=>$village_id))->setDec('money', $date['money']*2);
                        M('House_village_pay_order')->where(array('order_id' => $order_info['order_id']))->setField('is_pay_bill', 2);
                    }
                }
                $now_village_money = M('House_village')->where(array('village_id'=> $village_id))->field('village_id,village_name,village_address,money')->find();
                fdump_api([__LINE__,'date'=>$date,'now_village_money'=>$now_village_money],'VillageMoneyListLog/add_money'.$timeDate,1);
                return array('error_code'=>false,'msg'=>$desc.' ，保存社区收入成功！');
            }
        }



        public  function get_alias_c_name(){
            return array(
                'all'=>L_('选择分类'),
                'withdraw'=>L_('提现'),
                'sqrecharge'=>L_('充值'),
                'village_pay'=>L_('社区缴费'),
                'express'=>L_('快递代送'),
                'village_pay_cashier'=>L_('社区收银台缴费'),
                'village_pile_pay'=>L_('充电桩充电支付'),
                'pile_refund'=>L_('充电桩充电退款'),
            );
        }


        //减少余额
        public function use_money($village_id,$money,$type,$desc,$order_id,$percent=0,$system_take = 0,$other_data=array()){
            $date['village_id']=$village_id;
            $date['income'] = 2;
            $date['order_id'] = $order_id;
            if($percent){
                $date['percent'] = $percent;
                $date['system_take'] = $system_take;
            }
            $date['use_time']= time();
            $date['type']= $type;
            $date['desc']=  $desc;
            $date['money']=  $money;
            if($other_data && isset($other_data['business_order_id'])){
                $date['business_order_id']=  $other_data['business_order_id'];
            }
            if(!M('House_village')->where(array('village_id'=>$village_id))->setDec('money', $date['money'])){
                return array('error_code'=>true,'msg'=>$desc.L_('，使用失败！'));
            }
            $now_village_money = M('House_village')->field('money,village_owe_money')->where(array('village_id'=> $village_id))->find();
            //$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $village_status = 1;
            if(C('config.open_village_owe_money')==0){
                $now_village_money['village_owe_money'] = 0;
            }
            if($now_village_money['money']<$now_village_money['village_owe_money']){
                M('House_village')->where(array('village_id'=>$village_id))->setField('status',2);
                $village_status = 2;
            }
            //if(!empty($other_data['openid'])){
            //    if($village_status==3){
            //        $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $now_village_money['openid'], 'first' => $desc.'，当前社区余额:'.$now_village_money['money'].',您的社区状态为欠费，您的社区业务状态为禁止状态，请及时充值' , 'keyword1' => '社区余额使用', 'keyword2' =>$money,'keyword3' =>'', 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => '请到社区中心社区余额中查看'), $village_id);
            //    }else{
            //        $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $now_village_money['openid'], 'first' => $desc.'，当前社区余额:'.$now_village_money['money'] , 'keyword1' => '社区余额使用', 'keyword2' =>$money,'keyword3' =>'', 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => '请到社区中心社区余额中查看'), $village_id);
            //
            //    }
            //}

            $date['now_village_money'] = $now_village_money['money'];
            if(!$this->add($date)){
                return array('error_code'=>true,'msg'=>$desc.L_('，保存失败！'));
            }else{
                return array('error_code'=>false,'msg'=>$desc.L_('，保存成功！'));
            }
        }

        //统计所有社区余额
        public function  get_all_money($where){
            if(!empty($where)){
                return M('House_village')->where($where)->sum('money');
            }
            return M('House_village')->sum('money');
        }

        //提现
        public function withdraw($village_id,$name,$money,$remark){
            $date['village_id']=$village_id;
            $withdraw_money = $money;
            if(C('config.company_pay_village_percent')>0){
                $tmp_money  = $money;
                $withdraw_money = floor ($tmp_money * (100-C('config.company_pay_village_percent'))/100);
                //$date['percent'] = C('config.company_pay_village_percent');
                $system_take = floor($tmp_money * (C('config.company_pay_village_percent'))/100)/100;
            }
            $date['name']=$name;
            $date['money']=  $withdraw_money;
            $date['old_money']=  $money;
            $date['remark']=  $remark;
            $date['withdraw_time'] = time();

            $res =M('Village_withdraw')->add($date);
            if(!$res){
                return array('error_code'=>true,'msg'=>L_('保存失败！'));
            }else{
                $date['id']=  $res;
                //考虑兑现后减值
                $this->use_money($village_id,$money/100,'withdraw',L_('社区提现减少金额'),$res,C('config.company_pay_village_percent'),$system_take);
                return array('error_code'=>false,'msg'=>L_('保存成功！'),'data' => $date);
            }
        }

        public function get_income_list($villiage_id,$is_system = 0,$where){
            if($_GET['page']){
                $where = $_SESSION['condition'];
            }

            if($is_system){
                import('@.ORG.system_page');
            }else{
                import('@.ORG.village_page');
            }
            $where['village_id']=$villiage_id;

            $count = M('Village_money_list')->where( $where)->count();
            unset($where['village_id']);
            $where['l.village_id']=$villiage_id;
           
            $p = new Page($count, 20);
            $pagebar=$p->show();
            if($_GET['page']>$p->totalPage){
                return array('income_list'=>array(),'total'=>0,'page_num'=>$p->totalPage);
            }else {
                $income_list = M('Village_money_list')->join('as l left join '.C('DB_PREFIX') . 'house_village m ON m.village_id = l.village_id ')->field('l.order_id,l.desc,l.use_time,l.num,l.money,l.type,l.id,l.income,l.now_village_money,l.system_take,l.percent,l.score,l.score_count')->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();

                if ($income_list) {
                    $db_house_village_pay_order = M('House_village_pay_order');
                    $db_house_village_pay_cashier_order = M('House_village_pay_cashier_order');
                    foreach ($income_list as &$val) {
                        if (!isset($val['score']) || (floatval($val['score'])<=0 && floatval($val['score_count'])<=0)) {
                            if ($val['type'] && 'village_pay'==$val['type']) {
                                $where_village_pay = [
                                    'order_id' => $val['order_id'],
                                ];
                                $village_pay_info = $db_house_village_pay_order->where($where_village_pay)->field('order_id,score_can_get,score_used_count')->find();
                                if ($village_pay_info) {
                                    $val['score'] = $village_pay_info['score_can_get'];
                                    $val['score_count'] = $village_pay_info['score_used_count'];
                                }
                            } elseif ($val['type'] && 'village_pay_cashier'==$val['type']) {
                                $where_pay_cashier_order = [
                                    'cashier_id' => $val['order_id'],
                                ];
                                $pay_cashier_order_info = $db_house_village_pay_cashier_order->where($where_pay_cashier_order)->field('cashier_id,score_can_get,score_used_count')->find();
                                if ($pay_cashier_order_info) {
                                    $val['score'] = $pay_cashier_order_info['score_can_get'];
                                    $val['score_count'] = $pay_cashier_order_info['score_used_count'];
                                }
                            }
                        }
                    }
                }


                $total = M('Village_money_list')->join('as l left join '.C('DB_PREFIX') . 'house_village m ON m.village_id = l.village_id ')->where(array('l.village_id'=>$villiage_id,'l.income'=>1))->order('use_time DESC')->limit($p->firstRow, $p->listRows)->sum('l.money');
                $income_total = M('Village_money_list')->join('as l left join '.C('DB_PREFIX') . 'house_village m ON m.village_id = l.village_id ')->where(array('l.village_id'=>$villiage_id,'l.income'=>1,'l.type'=>array('neq','sqrecharge')))->order('use_time DESC')->limit($p->firstRow, $p->listRows)->sum('l.money');
                $recharge_total = M('Village_money_list')->join('as l left join '.C('DB_PREFIX') . 'house_village m ON m.village_id = l.village_id ')->where(array('l.village_id'=>$villiage_id,'l.income'=>1,'l.type'=>'sqrecharge'))->order('use_time DESC')->limit($p->firstRow, $p->listRows)->sum('l.money');
              //  $total_score = M('Village_money_list')->join('as l left join '.C('DB_PREFIX') . 'house_village m ON m.village_id = l.village_id ')->where($where)->order('use_time DESC')->limit($p->firstRow, $p->listRows)->sum('l.score');
                return array('income_list' => $income_list,'total'=>empty($total)?0:$total,'income_total'=>empty($income_total)?0:$income_total, 'total_score'=>empty($total_score)?0:$total_score,'recharge_total'=>empty($recharge_total)?0:$recharge_total,'pagebar' => $pagebar, 'page_num' => $p->totalPage);
            }
        }

        public function get_village_withdraw_list($condition_village,$page_count=15,$searchstatus=0){
            $database_village = M('House_village');
            import('@.ORG.system_page');

            if(isset($condition_village)){

                foreach($condition_village as $k=>$v){
                    if(strpos($k,'status')){
                        continue;
                    }
                    $condition_village['m.'.$k] = $v;
                    unset($condition_village[$k]);
                }
                $new_village_withdraw=array();
                $villageIds = array();
                $village_withdraw = M('Village_withdraw')->where(array('village_id' => array('gt', 0), 'status' => array('in', [0, 4])))->field('village_id,SUM(money) AS withdraw_money,withdraw_time,status as v_status ')->group('village_id')->select();
                if ($village_withdraw) {
                    foreach ($village_withdraw as $vw) {
                        $new_village_withdraw[$vw['village_id']] = $vw;
                        $villageIds[] = $vw['village_id'];
                    }
                }
                if ($searchstatus == 1) {
                    $condition_village['village_id'] = array('in', $villageIds);
                }
                $count_village = $database_village->alias('m')->where($condition_village)->count();
                //$count_village = $database_village->join('as m left join (SELECT  village_id,SUM(money) AS  withdraw_money,withdraw_time,status  FROM '.C('DB_PREFIX').'village_withdraw WHERE status in (0,4)  GROUP BY village_id) w  ON m.village_id = w.village_id ')->where($condition_village)->count();
                $p = new Page($count_village,$page_count);
                $village_withdraw_list = $database_village->alias('m')->field('m.village_id,m.property_phone as phone,m.village_name as name,m.money')
                    ->where($condition_village)
                    ->order('m.money DESC')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
                if($village_withdraw_list){
                     foreach ($village_withdraw_list as $vkk=>$vwl){
                         $village_withdraw_list[$vkk]['withdraw_time']='';
                         $village_withdraw_list[$vkk]['withdraw_money']=0;
                         if(isset($new_village_withdraw[$vwl['village_id']])){
                             $village_withdraw_list[$vkk]=array_merge($vwl,$new_village_withdraw[$vwl['village_id']]);
                         }
                     }
                }
                /*
                $village_withdraw_list = $database_village->join('as m left join (SELECT  village_id,SUM(money) AS  withdraw_money,withdraw_time,status  FROM '.C('DB_PREFIX').'village_withdraw WHERE status in (0,4)  GROUP BY village_id) w  ON m.village_id = w.village_id ')
                    ->field('m.village_id,m.property_phone as phone,m.village_name as name,m.money,w.withdraw_time,w.withdraw_money as withdraw_money')
                    ->where($condition_village)
                    ->order('m.money DESC')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
                */

            }else{

                foreach($condition_village as $k=>$v){
                    $condition_village['m.'.$k] = $v;
                    unset($condition_village[$k]);
                }
                $count_village = $database_village->where($condition_village)->count();
                $p = new Page($count_village,$page_count);
                $village_withdraw_list = $database_village->join('as m left join (SELECT  village_id,SUM(money) AS  withdraw_money,withdraw_time,status  FROM pigcms_village_withdraw WHERE status in (0,4)  GROUP BY village_id) w ON m.village_id = w.village_id ')
                    ->field('m.village_id,m.property_phone as phone,m.village_name as name,m.money,w.withdraw_time,w.withdraw_money')
                    ->where($condition_village)
                    ->order('m.money DESC')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
            }
            $pagebar = $p->show();
            return array('village_withdraw_list'=>$village_withdraw_list,'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
        }


        //抽成列表
        public function get_village_percentage_list($condition_village,$page_count=15){
            $database_village = M('Merchant');
            $where['area_id'] = $condition_village['area_id'];
            $count_village = $database_village->where($where)->count();
            $time_condition = '';
            foreach($condition_village as $k=>$v){
                if($k!='_string'){
                    $condition_village['m.'.$k] = $v;
                }else{
                    $time_condition = 'WHERE '.$condition_village[$k];
                }
                unset($condition_village[$k]);
            }
            $extra_str ='';
            $extra_field ='';


            import('@.ORG.system_page');
            $p = new Page($count_village,$page_count);
            $village_percentage_list = $database_village->join('as m left join '.'(SELECT  village_id,SUM(system_take) AS  money ,SUM(score) as all_score FROM '.C('DB_PREFIX').'village_money_list '.$time_condition.' GROUP BY village_id) w ON m.village_id = w.village_id  '.$extra_str)
                ->field('m.village_id,m.name,m.phone,w.money,w.all_score'.$extra_field)
                ->where($condition_village)
                ->order('w.money DESC,w.all_score DESC')
                ->limit($p->firstRow.','.$p->listRows)
                ->select();

            $pagebar = $p->show();
            return array('village_percentage_list'=>$village_percentage_list,'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
        }

        //统计所有商家余额
        public function  get_all_percent_money($condition_village){
            foreach($condition_village as $k=>$v){
                if($k!='_string'){
                    $condition_village['m.'.$k] = $v;
                    unset($condition_village[$k]);
                }
            }
            return M('Merchant_money_list')->join('AS l left join '.C('DB_PREFIX') .'village as m ON m.village_id = l.village_id')->where($condition_village)->sum('l.system_take');
        }

        //统计所有送出的积分
        public function  get_all_score($condition_village){
            $all_socre=  0;

            foreach($condition_village as $k=>$v){
                if($k!='_string'){
                    $condition_village['m.'.$k] = $v;
                    unset($condition_village[$k]);
                }
            }
            $all_socre += M('Merchant_money_list')->join('AS l left join '.C('DB_PREFIX') .'village as m ON m.village_id = l.village_id')->where($condition_village)->sum('l.score');
            return $all_socre;
        }

        public function get_withdraw_list($village_id,$is_system = 0,$status=3,$time=''){
            if($is_system){
                import('@.ORG.system_page');
            }else{
                import('@.ORG.merchant_page');
            }
            $where['village_id']= $village_id;
            if($status!=3){
                $where['status']= $status;
            }
            if($status==0){
                $where['status'] = array('in','0,4');
            }
            if(!empty($time)){
                $where['_string'] = $time;
            }

            $count = M('Village_withdraw')->where($where)->count();
            $p = new Page($count, 20);
            $pagebar=$p->show();
            if($_GET['page']>$p->totalPage){
                return array('withdraw_list'=>array(),'page_num'=>$p->totalPage);
            }else{
                return array('withdraw_list'=>M('Village_withdraw')->where($where)->order('withdraw_time DESC')->limit($p->firstRow,$p->listRows)->select(),'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
            }

        }

        public function get_all_village_money($where){

                return M('House_village')->where($where)->sum('money');

        }

        public function agree($village_id,$money,$withdraw_id,$remark,$is_online=0){
            $date['status'] = 1;
            $date['remark'] = $remark;
            $date['online'] = $is_online;
            $date['money'] = $money;

            $res = M('Village_withdraw')->where(array('id'=>$withdraw_id,'village_id'=>$village_id))->find();
            if($res['status']==4){
                M('Withdraw_list')->where(array('withdraw_id'=>$withdraw_id,'type'=>'village','pay_id'=>$village_id))->setField('status',1);
            }
            $res = M('Village_withdraw')->where(array('id'=>$withdraw_id,'village_id'=>$village_id))->save($date);
            return $res;
        }
        
        //拒绝提现 增加余额
        public function reject($village_id,$withdraw_id,$reason){
            $res = M('Village_withdraw')->where(array('id'=>$withdraw_id,'village_id'=>$village_id))->find();
            $date['status'] = 2;
            $date['remark'] = $reason;
            M('Village_withdraw')->where(array('id'=>$withdraw_id,'village_id'=>$village_id))->save($date);
            $desc = L_('驳回社区提现增加金额');
            $order_info['money'] = $res['old_money']/100;
            $order_info['order_type'] = 'withdraw';
            $order_info['village_id'] = $village_id;
            $order_info['order_id'] = $withdraw_id;
            $order_info['desc'] = $desc;
            if($res['status']==4){
                M('Withdraw_list')->where(array('withdraw_id'=>$withdraw_id,'type'=>'village','pay_id'=>$village_id))->setField('status',4);
            }
            return $this->add_money($order_info);
        }





    }