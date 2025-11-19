<?php
// 商家余额
class Merchant_money_listModel extends Model{
    //状态描述
    public function getStatusTxt($status)
    {
        if($status == 0){
            return '待申请';
        }
        if($status == 10){
            return '申请提现中';
        }
        if($status == 20){
            return '提现失败';
        }
        return '';
    }
    //提现失败描述
    public function getFailReasonTxt($fail_reason)
    {
        switch ($fail_reason){
            case 'ACCOUNT_FROZEN':
                return '该用户账户被冻结';
            case 'REAL_NAME_CHECK_FAIL':
                return '收款人未实名认证，需要用户完成微信实名认证';
            case 'NAME_NOT_CORRECT':
                return '收款人姓名校验不通过，请核实信息';
            case 'OPENID_INVALID':
                return 'Openid格式错误或者不属于商家公众账号';
            case 'TRANSFER_QUOTA_EXCEED':
                return '超过用户单笔收款额度，核实产品设置是否准确';
            case 'DAY_RECEIVED_QUOTA_EXCEED':
                return '超过用户单日收款额度，核实产品设置是否准确';
            case 'MONTH_RECEIVED_QUOTA_EXCEED':
                return '超过用户单月收款额度，核实产品设置是否准确';
            case 'DAY_RECEIVED_COUNT_EXCEED':
                return '超过用户单日收款次数，核实产品设置是否准确';
            case 'PRODUCT_AUTH_CHECK_FAIL':
                return '未开通该权限或权限被冻结，请核实产品权限状态';
            case 'OVERDUE_CLOSE':
                return '超过系统重试期，系统自动关闭';
            case 'ID_CARD_NOT_CORRECT':
                return '收款人身份证校验不通过，请核实信息';
            case 'ACCOUNT_NOT_EXIST':
                return '该用户账户不存在';
            case 'TRANSFER_RISK':
                return '该笔转账可能存在风险，已被微信拦截';
            case 'OTHER_FAIL_REASON_TYPE':
                return '其它失败原因';
            case 'REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED':
                return '请引导用户在微信支付查看详情';
            case 'RECEIVE_ACCOUNT_NOT_PERMMIT':
                return '请在产品设置中调整，添加该用户为收款人';
            case 'PAYEE_ACCOUNT_ABNORMAL':
                return '请联系用户完善其在微信支付的身份信息以继续收款';
            case 'PAYER_ACCOUNT_ABNORMAL':
                return '可前往商户平台获取解除功能限制指引';
            case 'TRANSFER_SCENE_UNAVAILABLE':
                return '该转账场景暂不可用，请确认转账场景ID是否正确';
            case 'TRANSFER_SCENE_INVALID':
                return '你尚未获取该转账场景，请确认转账场景ID是否正确';
            case 'TRANSFER_REMARK_SET_FAIL':
                return '转账备注设置失败， 请调整后重新再试';
            case 'RECEIVE_ACCOUNT_NOT_CONFIGURE':
                return '请前往商户平台-商家转账到零钱-前往功能-转账场景中添加';
            case 'BLOCK_B2C_USERLIMITAMOUNT_BSRULE_MONTH':
                return '超出用户单月转账收款20w限额，本月不支持继续向该用户付款';
            case 'BLOCK_B2C_USERLIMITAMOUNT_MONTH':
                return '用户账户存在风险收款受限，本月不支持继续向该用户付款';
            case 'MERCHANT_REJECT':
                return '商户员工（转账验密人）已驳回转账';
            case 'MERCHANT_NOT_CONFIRM':
                return '商户员工（转账验密人）超时未验密';
            default :
                return $fail_reason;
                break;
        }
    }
    //增加余额
    public function add_money($order_info, $other=''){
        $orderInfo = $order_info;
        // 只获取平台抽成金额
        $getSystemTakeMoney = $order_info['get_system_take_money'] ?? false;

        //无法复现购温馨平台抽成bug，记录日志跟踪
        fdump_sql(array('order_info' => $order_info, 'other' => $other, 'req_uri' => $_SERVER['REQUEST_URI']), 'ID1011227');
        fdump_api([$order_info, $other], 'systemBill/add_money', 1);
        if($order_info['real_orderid']){
            $has = $this->where(array('order_id'=>$order_info['real_orderid']))->find();
            if($has && $order_info['order_type'] != 'group'){
                return ;
            }
        }

        if ($order_info['order_type'] == 'appoint' && $order_info['order_id']) {
            if ($this->where(array('type' => 'appoint', 'order_id' => $order_info['order_id']))->find()) {
                return;
            }
        }

        $order_info['total_money'] = round($order_info['total_money'],2);
        $order_info['total_price'] = round($order_info['total_price'],2);
        //商家绑定用户
        $mer_id = $order_info['mer_id'];

        $desc = $order_info['desc'];
        $mer_user = D('Merchant')->get_merchant_user($mer_id);
        if(isset($order_info['store_id'])){
            $now_store = M('Merchant_store')->where(array('store_id'=>$order_info['store_id']))->find();
            $date['store_id'] = $order_info['store_id'];
            $union_data['store_id'] = $order_info['store_id'];
        }

        $now_merchant = D('Merchant')->get_info($mer_id);

        // 联盟商家
        $now_merchant_union = [];
        $order_info['union_mer_id'] && $now_merchant_union =  D('Merchant')->get_info($order_info['union_mer_id']);

        //日照银行担保支付确认
        $payment_money_order = M('Payment_money_order')->where(array('orderid' => $order_info['orderid'], 'pay_type' => 'ebankpay'))->find();
        if (!empty($payment_money_order)) {
            $payment_money_order['uid'] = $order_info['uid'];
            $payment_money_order['business']   = $payment_money_order['order_type'];
            $payment_money_order['paid_money'] = $payment_money_order['paid_money'] * 100;
            invoke_v20_service('app\pay\model\service\PayService/payEbankGuarantee', ['order' => $payment_money_order]);
        }

        //自有支付
        $own_percent_money = 0;
        if(isset($order_info['is_own'])&&$order_info['is_own']>0){
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $alias_name = $this->get_alias_c_name($order_info['order_type']);
            $store_name = L_('无');
            if (!empty($now_store)) {
                $store_name = $now_store['name'];
            }
            if($order_info['is_own']==2){
                $remark = L_('子商家支付商家余额不增加余额，请到子商家平台中查看');
                $title = L_("子商家");
            }else{
                $remark = L_('请到微信商家平台中查看');

                $title = L_("自有支付");
            }
            !$getSystemTakeMoney && $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $mer_user['openid'], 'first' => $title.'收款成功,' , 'keyword1' => $alias_name[$order_info['order_type']], 'keyword2' =>$order_info['payment_money'],'keyword3' =>$store_name, 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$order_info['order_id'],'remark' =>$remark), $mer_id);
            if(C('config.open_mer_own_percent')==1){
                $own_percent_money = $order_info['payment_money'];
            }
            $order_info['payment_money']=0;
        }

        $goods_take_a_percentage = 0;//订单中的商品抽成金额（前提：已设置商品抽成）
        $goods_take_a_percentage_total_money = 0;//订单中的商品金额（前提：已设置商品抽成）
        $is_complete = 1;// 订单是否完成
        $merchantCardGiveMoney = 0; //商家会员卡赠送抵扣余额，该部分参与平台抽成计算，但是不结算给商家
        switch($order_info['order_type']){
            case 'group_combine':
                // 团购优惠组合，以成本价对账不抽成
                $num =1;
                $money = $order_info['total_money'];
                $date['money'] = $money;
                $date['total_money'] = $money;
                break;
            case 'group':
                if(!empty($order_info['refund'])){
                    $num =1;
                    $money=$order_info['refund_money'];
                    $order_info['total_money'] =  $money;
                }elseif(!$order_info['verify_all']){
                    $num =1;
                    if($order_info['pay_type']=='offline'){
                        $count = D('Group_pass_relation')->get_pass_num($order_info['order_id'],1);
                        if($order_info['score_deducte']>$order_info['price']){
                            if($order_info['score_deducte']-$count*$order_info['price']>0){
                                $money =$order_info['price'];
                            }else{
                                $money = $order_info['score_deducte']-($count-1)*$order_info['price'];
                            }
                        }else{
                            if($count==1){
                                $money = $order_info['score_deducte'];
                            }else{
                                $money=0;
                                return array('error_code'=>false,'msg'=>L_('无收入记录'));
                            }
                        }
                    }else{
                        $pass_num = $order_info['pass_num']>1?intval($order_info['pass_num']):1; // 一份团购 对应生成核销券 份数
                        $pass_total_num = $order_info['num'] * $pass_num;
                        //$money = ($order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['merchant_balance'])/$pass_total_num*100/100;
                        $baseMoney = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['merchant_balance'];
                        $money = bcdiv($baseMoney, $pass_total_num, 2);

                        // 查询剩余未核销次数
                        $count = D('Group_pass_relation')->get_pass_num($order_info['order_id']);
                        $count += D('Group_pass_relation')->get_pass_num($order_info['order_id'],3);
                        if($count > 0){
                            $is_complete = 0;// 订单未完成
                        }else{
                            //如果全部核销了，最后一个金额直接总金额减去之前的金额
                            $money = bcsub($baseMoney, bcmul($money, $pass_total_num-1, 2), 2);
                        }
                    }

                    $order_info['total_money'] = $money+($own_percent_money/$pass_total_num*100/100);// 订单金额加上自有支付金额
                    $own_percent_money = $own_percent_money/$pass_total_num*100/100;

                }else{
                    $pass_num = $order_info['pass_num']>1?intval($order_info['pass_num']):1; // 一份团购 对应生成核销券 份数
                    $pass_total_num = $order_info['num'] * $pass_num; // 总的核销码数量
                    $unconsume_pass_num = $order_info['unconsume_pass_num']?$order_info['unconsume_pass_num']:$pass_total_num; // 未验证核销码数量
                    $num = $unconsume_pass_num;
                    $money_price = ($order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['merchant_balance'] + $order_info['card_give_money'])/$pass_total_num*100/100;
                    //拉取商品，算单个商品的抽成
                    $goods_detail = M("Group_order")->alias('a')->join('left join '.C('DB_PREFIX').'group as b on a.group_id=b.group_id')->field('b.group_id,b.score_percent')->where(['a.order_id'=>$order_info['order_id']])->select();
                    if($goods_detail['score_percent'] > 0){
                        D('User')->add_score($order_info['uid'], round($goods_detail['score_percent'] * $money_price),'购买团购验证消费获得' . C('config.score_name'),0,[
                            'mer_id' => $order_info['mer_id']??0,
                            'store_id' => $order_info['store_id']??0,
                            'order_id' => $order_info['order_id'],
                            'order_type' => 'group'
                        ]);
                    }
                    $money = $money_price * $unconsume_pass_num;
                    $order_info['total_money'] = $money+($own_percent_money/$pass_total_num*100/100)*$unconsume_pass_num;// 订单金额加上自有支付金额

                    // 自有支付本次核销的金额
                    $own_percent_money = ($own_percent_money/$pass_total_num*100/100)*$unconsume_pass_num;

                    if($order_info['real_orderid'] && $pass_total_num != 1){// 只有一个核销码的不验证
                        $has = $this->where(array('order_id'=>$order_info['real_orderid']))->find();
                        if ($has && $money && floatval($money) == floatval($has['total_money'])) {
                            fdump_sql(array('total_money' => $has['total_money'],'money' => $money,'order_info' => $order_info, 'other' => $other, 'has' => $has, 'req_uri' => $_SERVER['REQUEST_URI']), 'merchant_add_money_group');
                            return false;
                        }
                    }
                    if (isset($order_info['card_give_money'])) {
                        $merchantCardGiveMoney = $order_info['card_give_money'];
                    }
                }
                $order_info['express_fee'] = $order_info['total_money'] > 0 ? $order_info['express_fee'] / $order_info['total_money'] * $money * 100 / 100 : 0;
                $money -= $order_info['express_fee'];
                break;
            case 'meal':
                $num =$order_info['total'];
        
                // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
                if ($order_info['union_mer_id']) {
                    $merchant_balance = $order_info['merchant_balance'];
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price'];
                }else{
                    $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['merchant_balance'];
                }
                $order_info['total_money'] = $order_info['total_price'];
                $food_order = D('Foodshop_order')->where(['real_orderid'=>$order_info['order_id']])->find();
                if($food_order['order_id']){
                    //拉取商品，算单个商品的抽成
                    $goods_detail = M("Foodshop_order_detail")->alias('a')->join('left join '.C('DB_PREFIX').'foodshop_goods as b on a.goods_id=b.goods_id')->field('b.goods_id,b.take_a_percentage,b.score_percent,a.price,a.num')->where(['a.order_id'=>$food_order['order_id']])->select();
                    $food_goods_score = 0;
                    foreach ($goods_detail as $gdetail) {
                        if($gdetail['take_a_percentage'] > 0){
                            $goods_take_a_percentage += $gdetail['price']*$gdetail['num']*$gdetail['take_a_percentage']/100;
                            $goods_take_a_percentage_total_money += $gdetail['price']*$gdetail['num'];
                        }
                        if($gdetail['score_percent'] > 0){
                            $food_goods_score += $gdetail['price']*$gdetail['num']*$gdetail['score_percent'];
                        }
                    }
                    $goods_take_a_percentage = getFormatNumber($goods_take_a_percentage);
                    if(round($food_goods_score) > 0 && !$getSystemTakeMoney){
                        D('User')->add_score($order_info['uid'], round($food_goods_score),'购买餐饮商品消费获得' . C('config.score_name'),0,[
                            'mer_id' => $order_info['mer_id']??0,
                            'store_id' => $order_info['store_id']??0,
                            'order_id' => $food_order['order_id'],
                            'order_type' => 'group'
                        ]);
                    }
                }
                
                // 开启哈可定制 新餐饮
                if(C('config.open_meal_electronic_menu')==1){
                    //1 平台出 不抽成   0 商家出 抽成 
                    // 平台优惠不抽成 商家优惠抽成
                    $plat_pay_money = 0;
                    $merchant_pay_money = 0;                    
                    $plat_pay_money = $order_info['platform_plat'];
                    $merchant_pay_money  = $order_info['platform_merchant']+$order_info['merchant_reduce']+$order_info['merchant_coupon_price'];
                    // 平台抽成基数
                    $money_system_take = $money+floatval($merchant_pay_money);

                    // 加上平台满减优惠金额
                    $money += $plat_pay_money;
                }
                break;
            case 'shop':
            case 'mall':
                $num =$order_info['num'];

                if(C('config.open_meiyong_percent')==1){
                    //1 平台出 不抽成 但对账  0 商家出 抽成 不对账
                    // 平台优惠 商家优惠不抽成
                    if($order_info['discount_detail']){
                        $discount_detail = unserialize($order_info['discount_detail']);
                        $plat_pay_money = floatval($discount_detail['system_minus']['minus'])
                                        // +floatval($discount_detail['delivery']['plat_money'])
                                         +floatval($discount_detail['system_newuser']['minus']);
                                         +floatval($discount_detail['newuser']['plat_money']);

                        $merchant_pay_money  = floatval($discount_detail['system_minus']['merchant_money'])
                                             // +floatval($discount_detail['delivery']['merchant_money'])
                                              +floatval($discount_detail['newuser']['merchant_money'])
                                              +floatval($discount_detail['minus']['minus'])
                                              +floatval($discount_detail['system_newuser']['merchant_money']);
                    }

                    // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
                    if ($order_info['union_mer_id']) {
                        $merchant_balance = $order_info['merchant_balance'];
                        $price = $order_info['balance_pay']
                            +$order_info['payment_money']
                            +$order_info['score_deducte']
                            +$order_info['coupon_price']
			    +$order_info['qiye_pay'];
                    }else{
                        $price = $order_info['balance_pay']
                            +$order_info['payment_money']
                            +$order_info['score_deducte']
                            +$order_info['coupon_price']
                            +$order_info['merchant_balance']
			    +$order_info['qiye_pay'];
                    }                    

                    if(C('config.vip_discount_pay_for')==1){
                        $money_system_take = $price+floatval($discount_detail['minus']['minus'])-$order_info['vip_level_reduce_money'];
                        $money =$price-floatval($discount_detail['delivery']['merchant_money'])+floatval($discount_detail['system_minus']['plat_money'])+floatval($discount_detail['system_newuser']['plat_money']);
                    }else{
                        $money_system_take = $price+floatval($discount_detail['minus']['minus']);
                        $money = $price-floatval($discount_detail['delivery']['merchant_money'])-floatval($order_info['vip_level_reduce_money'])+floatval($discount_detail['system_minus']['plat_money'])+floatval($discount_detail['system_newuser']['plat_money']);
                    }

                    //打包费不抽成 抽成金额去掉打包费
                    if($now_merchant['package_fee_percent']==0){
                        $money_system_take = $money_system_take-$order_info['packing_charge'];
                    }

                    //平台配送不对账 0 平台配送 1 商家配送
                    if($order_info['is_pick_in_store']==1){
                       // $money = $money$order_info['freight_charge'];
                    }else{
                        $money = $money-$order_info['freight_charge'];
                        $money_system_take = $money_system_take-$order_info['freight_charge'];
                    }

                }else{
                    // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
                    if ($order_info['union_mer_id']) {
                        $merchant_balance = $order_info['merchant_balance'];
                        $price = $order_info['balance_pay']
                            +$order_info['payment_money']
                            +$order_info['score_deducte']
                            +$order_info['coupon_price']
                            +$order_info['balance_reduce']
                            +$order_info['qiye_pay'];
                    }else{
                        $price = $order_info['balance_pay']
                            +$order_info['payment_money']
                            +$order_info['score_deducte']
                            +$order_info['coupon_price']
                            +$order_info['balance_reduce']
                            +$order_info['merchant_balance']
                            +$order_info['qiye_pay'];
                    }

                    if($order_info['is_pick_in_store']==0&&($order_info['card_give_money']>0||$order_info['card_price']>0)&&$order_info['order_from']==0){//平台配送 （使用了商家赠送余额或商家优惠券）
                        $pay_for_system = $order_info['freight_charge'];
                        $money = $price-$order_info['no_bill_money'];
                        $money_ = $price;
                        if($money<=0){
                            $pay_for_system-=$money_;
                        }
                    }else if($order_info['is_own'] >0 && $order_info['is_pick_in_store']==0){
                        $pay_for_system = $order_info['freight_charge'];
                        if($now_merchant['express_fee']>0){
                            $pay_for_system = $now_merchant['express_fee'];
                        }
                        if ($order_info['union_mer_id']) {
                            $merchant_balance = $order_info['merchant_balance'];
                            $money = $order_info['balance_pay']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['balance_reduce'];
                        }else{
                            $money = $order_info['balance_pay']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['balance_reduce']+$order_info['merchant_balance'];
                        }

                    }else if($order_info['is_pick_in_store']==0&&$order_info['order_from']==1){//平台配送 （使用了商家赠送余额或商家优惠券）
                        $pay_for_system = $order_info['no_bill_money'];
                        $money = $price;


                    }else{
                        if ($order_info['is_pick_in_store'] == 0 && $order_info['is_own'] >0) {
                            $pay_for_system = min($order_info['extra_price'] + $order_info['freight_charge_original'], $order_info['no_bill_money']);
                        }

                        $money = $price;
                        if($now_merchant['express_fee']>0){
                            if($now_merchant['express_fee']>$order_info['freight_charge']){
                                $money = $money-($now_merchant['express_fee']- $order_info['freight_charge']);
                            }else if($now_merchant['express_fee']<$order_info['freight_charge']){
                                $money  = $money+$order_info['freight_charge']-$now_merchant['express_fee'];
                            } else {
                                // 实际配送费用与固定配送费一致 即扣除实际配送费即可
                            }
                        }
                    }
                   // if($order_info['is_pick_in_store']==0){
                   //     $money = $money-$order_info['other_money'];
                   // }
                    if($now_merchant['package_fee_percent']==0){
                        $money = $money-$order_info['packing_charge'];
                    }
                }
                $payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_info['orderid'],'order_type'=>'shop'))->find();
                if ($payment_money_order && $payment_money_order['service_charge'] > 0 && $money > 0) {
                    $money -= $payment_money_order['service_charge'];
                }

                //拉取商品，算单个商品的抽成
                $goods_detail = M("Shop_order_detail")->alias('a')->join('left join '.C('DB_PREFIX').'shop_goods as b on a.goods_id=b.goods_id')->field('b.goods_id,b.take_a_percentage,a.discount_price,a.num')->where(['a.order_id'=>$order_info['order_id']])->select();
                foreach ($goods_detail as $gdetail) {
                    if($gdetail['take_a_percentage'] > 0){
                        $goods_take_a_percentage += $gdetail['discount_price']*$gdetail['num']*$gdetail['take_a_percentage']/100;
                        $goods_take_a_percentage_total_money += $gdetail['discount_price']*$gdetail['num'];
                    }
                }
                $goods_take_a_percentage = getFormatNumber($goods_take_a_percentage);

                $order_info['total_money'] = $order_info['total_price'];

                // 实付金额
                $date['pay_money'] = $order_info['price'];
                break;
            case 'shop_offline':
                $num = 1;
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['balance_reduce']-$order_info['no_bill_money']+$order_info['merchant_balance'];
                $order_info['total_money'] = $order_info['total_price'];
                break;
            case 'appoint':
                $num = 1;
                $money = $order_info['money'];

                //拉取商品，算单个商品的抽成
                $goods_detail = M("Appoint_order")->alias('a')->join('left join '.C('DB_PREFIX').'appoint as b on a.appoint_id=b.appoint_id')->field('b.appoint_id,b.mer_id,b.take_a_percentage,a.appoint_price,a.product_id,a.product_price')->where(['a.order_id'=>$order_info['order_id']])->select();
                $goods_percent = 0;
                foreach ($goods_detail as $gdetail) {
                    if($gdetail['take_a_percentage'] > 0 && $gdetail['mer_id'] > 0 ){
                        $goods_percent = $gdetail['take_a_percentage'];
                        if($gdetail['product_id'] > 0){
                            $goods_take_a_percentage += $gdetail['product_price']*$gdetail['take_a_percentage']/100;
                            $goods_take_a_percentage_total_money += $gdetail['product_price'];
                        }else{
                            $goods_take_a_percentage += $gdetail['appoint_price']*$gdetail['take_a_percentage']/100;
                            $goods_take_a_percentage_total_money += $gdetail['appoint_price'];
                        }
                    }
                }
                $goods_take_a_percentage = getFormatNumber($goods_take_a_percentage);

                //修复order_info缺少orderid字段
                if (!isset($order_info['orderid']) && $order_info['order_id']) {
                    $appointOrder = M("Appoint_order")->where(['order_id' => $order_info['order_id']])->find();
                    $appointOrder && $order_info['orderid'] = $appointOrder['orderid'];
                }
                $appointSupply = M('Appoint_supply')
                    ->where(['order_id' => $order_info['order_id']])
                    ->find();
                $merchantWorkerServiceFee = floatval($appointSupply['commission']);
                break;
            case 'store':
                $num =1;
                $order_info['total_money'] = $order_info['total_price'];
                $money = $order_info['balance_pay'] + $order_info['payment_money'] + $order_info['merchant_balance'] + $order_info['coupon_price'] + $order_info['score_deducte'] + $order_info['qiye_pay'];
                $date['back_rate'] = $order_info['back_rate'] ?? 0;
                $date['back_price'] = $order_info['back_price'] ?? 0;
                $date['back_original_price'] = $order_info['back_original_price'] ?? 0;
                break;
            case 'cash':
                $num =1;
                $order_info['total_money'] = $order_info['total_price'];
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['merchant_balance']+$order_info['coupon_price']+$order_info['score_deducte'];
                break;
            case 'wxapp':
                $num =1;
                $order_info['total_money'] = $order_info['money'];
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['merchant_balance'];
                break;
            case 'weidian':
                $num =$order_info['order_num'];
                $order_info['total_money'] = $order_info['money'];
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['merchant_balance'];
                break;
            case 'withdraw':
                $num =1;
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            case 'yydb':
                $num =1;
                $money = $order_info['money'];
                $order_info['total_money']= $money;
                break;
            case 'coupon':
                $num =1;
                $money = $order_info['money'];
                $order_info['total_money']= $money;
                break;
            case 'spread':
                $num =1;
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $order_info['total_money'];
                break;
            case 'award':
                $num =1;
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $order_info['total_money'];
                break;
            case 'market':
            case 'market_spread':
            case 'market_integral_mall':
                $num =$order_info['num'];
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            case 'marketcancel':
                $num =1;
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            case 'sub_card':
                $num =1;
                $money = $order_info['money'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            case 'merrecharge':
                $num =1;
                $money = $order_info['pay_money'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            case 'village_group':
                $num =1;
                $money = $order_info['total_money'];
                $date['money']= $money;
                $date['total_money']= $money;

                //拉取商品，算单个商品的抽成
                $goods_detail = M("Village_group_order_detail")->alias('a')->join('left join '.C('DB_PREFIX').'village_group_goods as b on a.goods_id=b.goods_id')->field('b.goods_id,b.mer_id,b.take_a_percentage,b.score_percent,a.discount_price,a.num')->where(['a.order_id'=>$order_info['order_id']])->select();
                foreach ($goods_detail as $gdetail) {
                    if($gdetail['take_a_percentage'] > 0 && $gdetail['mer_id'] > 0 ){
                        $goods_take_a_percentage += $gdetail['discount_price']*$gdetail['num']*$gdetail['take_a_percentage']/100;
                        $goods_take_a_percentage_total_money += $gdetail['discount_price']*$gdetail['num'];
                    }
                }
                $goods_take_a_percentage = getFormatNumber($goods_take_a_percentage);
                break;
            case 'merchant_user_recharge':
                $num =1;
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['merchant_balance'];
                $date['money']= $money;
                $date['total_money']= $money;
                break;
            default:
                $money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['merchant_balance'];
                break;
        }
        if($order_info['order_type']=='group'){
            $percent = D('Percent_rate')->get_percent($mer_id,$order_info['order_type'],$money,$order_info['group_id'], $order_info['store_id'], true);
        }
        elseif($order_info['order_type']=='meal'&&$order_info['order_from']==1){
            if(C('config.is_open_merchant_foodshop_discount')==1){
                $where = array('real_orderid'=>$order_info['order_id']);
                $percent = D('Foodshop_order')->where($where)->getField('mer_table_scale');
            }else{
                $percent = 0;
            }
            if($percent==0 || $percent==100){
                $percent = D('Percent_rate')->get_percent($mer_id,'meal_scan',$money, '',$order_info['store_id'], true);
                if ($order_info['union_mer_id']) {
                    // 联盟商家抽成比例
                    $union_percent = D('Percent_rate')->get_percent($order_info['union_mer_id'],'meal_scan',$merchant_balance, '',$order_info['store_id'], true);
                }
            }
        } elseif('market'==$order_info['order_type'] || 'market_integral_mall' == $order_info['order_type']){
            $percent = D('Percent_rate')->get_market_percent($mer_id);
        } elseif($order_info['order_type']=='marketcancel' || $order_info['order_type']=='group_combine'){
            $percent = 0;
        } elseif($order_info['order_type']=='sub_card') {
            $percent = $order_info['percent'];
        } else if($order_info['order_type']=='meal'){
            $percent = D('Percent_rate')->get_percent($mer_id,$order_info['order_type'],$money, '',$order_info['store_id'], true);
            if ($order_info['union_mer_id']) {
                // 联盟商家抽成比例
                $union_percent = D('Percent_rate')->get_percent($order_info['union_mer_id'],$order_info['order_type'],$merchant_balance, '',$order_info['store_id'], true);
            }
        }elseif($order_info['order_type']=='shop' && $order_info['order_from']==1){ //分离商城抽成
            $order_info['order_type']='mall';
            $percent = D('Percent_rate')->get_percent($mer_id,'mall',$money, '',$order_info['store_id'], true);
            if ($order_info['union_mer_id']) {
                // 联盟商家抽成比例
                $union_percent = D('Percent_rate')->get_percent($order_info['union_mer_id'],'mall',$merchant_balance, '',$order_info['store_id'], true);
            }
        }else{
            $is_pick_in_store = isset($order_info['is_pick_in_store']) ? $order_info['is_pick_in_store'] : -1;
            $percent = D('Percent_rate')->get_percent($mer_id, $order_info['order_type'], $money+$own_percent_money, '', $order_info['store_id'], true, $is_pick_in_store);

            if ($order_info['union_mer_id']) {
                // 联盟商家抽成比例
                $union_percent = D('Percent_rate')->get_percent($order_info['union_mer_id'],$order_info['order_type'],$merchant_balance, '',$order_info['store_id'], true);
            }
        }

        if($now_merchant['fans_percent']>0 && C('config.open_fans_percent')==1){
            $percent = $now_merchant['fans_percent'];
        }


        $date['type']=$order_info['order_type'];
        $order_info['union_mer_id'] && $union_data['type']=$order_info['order_type'];

        if($date['type']=='group' || $date['type']=='group_combine' || $date['type']=='shop' || $date['type']=='mall') {
            $date['order_id'] = $order_info['real_orderid'];
            $order_info['union_mer_id'] && $union_data['order_id']=$order_info['real_orderid'];
        }else{
            $date['order_id'] = $order_info['order_id'];
            $order_info['union_mer_id'] && $union_data['order_id']=$order_info['order_id'];
        }

        $percent = floatval($percent);
        $union_percent = floatval($union_percent);

        // 杉德支付部分走杉德分账，其余走正常结算(sande)
        $payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_info['orderid']))->find();
        $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
        if($payment_money_order['pay_method'] == 'sande' && $payment_money_order['paid_money'] > 0 && $now_merchant['sande_merchant_fz'] != ''){
            $money -=$payment_money_order['paid_money'];
        }

        if($money<=0){
            if($pay_for_system!=0){
                if($order_info['is_own']==1) {
                    $desc_pay_for_system=L_("自有支付使用平台X1，系统扣除X2费",array("X1" => C('config.deliver_name'),"X2" => C('config.deliver_name'))); 
                }else if($order_info['order_from']==1){
                    $desc_pay_for_system=L_("商城订单快递配送转为X1，系统扣除X2费",array("X1" => C('config.deliver_name'),"X2" => C('config.deliver_name'))); 
                }else{
                    $desc_pay_for_system= L_("转X1，平台从商家余额中扣除订单的配送费",array("X1" => C('config.deliver_name'))); 
                }
                !$getSystemTakeMoney && $result_pay = $this->use_money($mer_id,$pay_for_system,$order_info['order_type'],$desc_pay_for_system,$order_info['real_orderid']);

            }
            $money  = 0;
        }
        $date['num'] = $num>0?$num:1;
        $date['mer_id']=$mer_id>0?$mer_id:1;

        // 商家联盟
        $union_data['num'] = $num>0?$num:1;
        $union_data['mer_id'] = $order_info['union_mer_id'];

        if($order_info['order_type']!='withdraw' && $order_info['order_type']!='merrecharge' && $order_info['order_type']!='merchant_user_recharge'){
            if(C('config.open_extra_price')==1){
                $date['score']=D('Percent_rate')->get_extra_money($order_info);
                $date['score_count']=$order_info['score_used_count'];
            }else {

                if(C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $order_info['score_discount_type']!=2)) {
                    if (C('config.open_score_get_percent') == 1) {
                        $score_get = C('config.score_get_percent') / 100;
                    } else {
                        $score_get = C('config.user_score_get');
                    }
                    if ($order_info['mer_id'] > 0) {
                        $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
                        if ($now_merchant['score_get_percent'] >= 0) {
                            $score_get = $now_merchant['score_get_percent'] / 100;
                        }
                    }
                    $date['score'] = round($money*$score_get);
                    $union_data['score'] = round($merchant_balance*$score_get);
                }
                $date['score_count']=$order_info['score_used_count']>0?$order_info['score_used_count']:0;
                $union_data['score'] = round($merchant_balance*$score_get);
            }
        }
        //除了提现，分佣，批发分佣，其他的收入要抽成
        if($order_info['order_type']!='withdraw' && $order_info['order_type']!='spread' && $order_info['order_type']!='merrecharge' && $order_info['order_type']!='market_spread' && $order_info['order_type']!='group_combine' && $order_info['order_type']!='merchant_user_recharge'){
            $date['total_money']= $order_info['total_money'];
            $union_data['total_money'] = $order_info['total_money'];

            if(C('config.open_meiyong_percent')==1 && ($order_info['order_type'] == 'shop' || $order_info['order_type'] == 'mall')){
                $date['system_take'] = round($money_system_take * $percent / 100, 2);
                if ($order_info['uid'] > 0 && C('config.add_score_by_percent') > 0 && round($date['system_take']) > 0) {
                    $date['score'] = round($date['system_take']);
                }
        
            }else {
                //平台满减优惠平台补贴不计算佣金，商家补贴计算佣金
                $orderDiscountDetail = $order_info['discount_detail'] ? unserialize($order_info['discount_detail']) : [];
                $systemMinusDiscount = is_array($orderDiscountDetail) && isset($orderDiscountDetail['system_minus']) ? $orderDiscountDetail['system_minus'] : [];
                $deliverMinusDiscount = is_array($orderDiscountDetail) && isset($orderDiscountDetail['delivery']) ? $orderDiscountDetail['delivery'] : [];
                //配送费折扣满减
                $deliverZhekou = $orderDiscountDetail['delivery_zhekou'] ?? [];
                if (empty($deliverMinusDiscount) && $deliverZhekou) {
                    $plat = $deliverZhekou['minus2'] * $deliverZhekou['plat_money'];
                    $merchant = $deliverZhekou['minus2'] - $plat;
                    $deliverMinusDiscount = [
                        'plat_money' => $plat > 0 ? $plat : 0,
                        'merchant_money' => $merchant > 0 ? $merchant : 0
                    ];
                }
                $merchantSubsidy = $platSubsidy = 0;
                if ($systemMinusDiscount) {
                    //满减
                    $merchantSubsidy += round($systemMinusDiscount['merchant_money'], 2);
                    $platSubsidy += round($systemMinusDiscount['plat_money'], 2);
                }

                if ($deliverMinusDiscount) {
                    //运费满减
                    $orderRealFreightCharge = $order_info['freight_charge'];
                    $freightChargeOriginal = $order_info['freight_charge_original'];
                    if ($orderRealFreightCharge == 0) {
                        //全部抵扣了，优先使用平台补贴，剩余使用商家补贴
                        if ($deliverMinusDiscount['plat_money'] >= $freightChargeOriginal) {
                            $deliverMinusDiscount['plat_money'] = $freightChargeOriginal;
                            $deliverMinusDiscount['merchant_money'] = 0;
                        } else {
                            $deliverMinusDiscount['merchant_money'] = $freightChargeOriginal - $deliverMinusDiscount['plat_money'];
                        }
                    }
                    $merchantSubsidy += round($deliverMinusDiscount['merchant_money'], 2);
                    //平台配送不参与计算佣金，已记录到no_bill_money字段减掉过了
                    if ($order_info['is_pick_in_store'] != 0) {
                        $platSubsidy += round($deliverMinusDiscount['plat_money'], 2);
                    }
                }

                //平台优惠券平台补贴的钱结算给商家，但是不参与平台抽成；商家补贴的钱不结算给商家，但是要参与平台抽成。
                $system_coupon_plat_money = 0;
                $system_coupon_merchant_money = 0;
                if ($order_info['coupon_id'] > 0 && $order_info['coupon_price'] > 0) {
                    $use_system_coupon = D('System_coupon')->get_coupon_hadpull(['s.uid' => $order_info['uid'], 's.id' => $order_info['coupon_id']]);
                    if ($use_system_coupon) {
                        if ($use_system_coupon['plat_money'] == 0 && $use_system_coupon['merchant_money'] == 0) {
                            $use_system_coupon['plat_money'] = 1;
                        }
                    }
                    $system_coupon_plat_money = getFormatNumber($order_info['coupon_price'] * ($use_system_coupon['plat_money'] / ($use_system_coupon['plat_money'] + $use_system_coupon['merchant_money'])));
                    $system_coupon_merchant_money = getFormatNumber($order_info['coupon_price'] - $system_coupon_plat_money);
                }

                if (C('config.open_store_percent_single') == 1 && ($order_info['order_type'] == 'store' || $order_info['order_type'] == 'cash')) {
                    $date['system_take'] = 0;
                } else {
                    //商家推广的用户在推广商家里消费外卖业务时，平台是否抽取该商家抽成。1：是  0：否
                    $spread_user_buy_no_commission = M('Merchant')->where(['mer_id' => $mer_id])->getField('spread_user_buy_no_commission');
                    if ($spread_user_buy_no_commission == 0 && in_array($order_info['order_type'], ['shop', 'mall']) && $order_info['order_from'] != 1 && D('Merchant_spread')->is_merchant_spread_user($mer_id, $order_info['uid'])) {
                        $date['system_take'] = 0;
                        $date['is_spread_user_buy'] = 1;
                    } else {
                        if($order_info['order_type'] == 'appoint'){
                            //针对预约订单已服务未付尾款情况特殊处理
                            $leftSystemTake = (($money - $goods_take_a_percentage_total_money - $platSubsidy - $system_coupon_plat_money) * $percent / 100);
                            $date['system_take'] = round(($leftSystemTake > 0 ? $leftSystemTake : 0) + $goods_take_a_percentage, 2);
                        }else{
                            $date['system_take'] = round((($money - $goods_take_a_percentage_total_money - $platSubsidy - $system_coupon_plat_money) * $percent / 100) + $goods_take_a_percentage, 2);
                        }
                        $merchant_balance = $merchant_balance ?: 0;
                        $union_data['system_take'] = round($merchant_balance * $union_percent / 100, 2);
                    }
                }
                fdump_api(['22'=>[
                    $money,
                    $goods_take_a_percentage_total_money,
                    $platSubsidy,
                    $system_coupon_plat_money,
                    $percent,
                    $goods_take_a_percentage,
                    $merchant_balance,
                    $union_percent
                ]],'user_score0930',1);
                
                if ($order_info['uid'] > 0 && C('config.add_score_by_percent') > 0 && round($date['system_take']) > 0) {
                    $date['score'] = round($date['system_take']);
                    $union_data['score'] = round($union_data['system_take']);
                }
                if($order_info['order_type'] == 'shop_offline'){
                    if ($now_merchant['package_fee_percent'] == 0) {
                        $money = $money + $order_info['packing_charge'];
                    } else if ($order_info['express_fee'] > 0) {
                        $money = $money + $order_info['express_fee'];
                    }
                }
            }


            $date['system_take'] < 0 && $date['system_take'] = 0;
            if(C('config.open_store_percent_single')==1 && $order_info['is_own']==0 && ($order_info['order_type']=='store'||$order_info['order_type']=='cash')) {

                $date['money'] = D('Percent_rate')->get_percent($mer_id,$order_info['order_type'],$order_info['total_money'], '',$order_info['store_id'], true);
            }else{
               // if($order_info['order_type']=='shop' && $order_info['merchant_reduce']>0){
               //     $date['money'] = $money -$order_info['merchant_reduce_total']- $date['system_take'];
               // }else{
                $date['money'] = $money - $merchantSubsidy - $system_coupon_merchant_money - $date['system_take'] - $merchantCardGiveMoney;
               // }
                $union_data['money'] = $merchant_balance - $union_data['system_take'];
            }
            $date['percent'] = $percent;
            $union_data['percent'] = $union_percent;
        }else{
            $date['percent'] = 0;
            $union_data['percent'] = 0;
        }

        //预约如果配置了单商品抽成比例，则记录单商品的比例
        if($order_info['order_type'] == 'appoint' && $goods_percent > 0){
            $date['percent'] = $goods_percent;
        }

        ($date['money'] < 0 || is_nan($date['money'])) && $date['money'] = 0;
        ($date['system_take'] < 0 || is_nan($date['system_take'])) && $date['system_take'] = 0;
        $date['income'] = 1;
        $date['use_time']= time();
        $date['desc']=  empty($desc)?'':$desc;

        $union_data['money'] < 0 && $union_data['money'] = 0;
        $union_data['system_take'] < 0 && $union_data['system_take'] = 0;
        $union_data['income'] = 1;
        $union_data['use_time'] = time();
        $union_data['desc'] =  empty($desc)?'':$desc;

        //快店订单重新计算一遍结算金额
        if (in_array($order_info['order_type'], ['shop', 'mall']) && $order_info['order_from'] != 1) {
            $computeData = $this->compute_merchant_settlement_money($order_info['order_id']);
            $date = array_merge($date, $computeData);
        }

        if(is_nan($date['system_take'])){
            $date['system_take']=0;
        }
        if(is_nan($date['score'])){
            $date['score']=0;
        }
        fdump('date','33333333333',1);
        fdump($date,'33333333333',1);

        //直播订单扣除佣金   先给主播加佣金，佣金从商家要增加的余额里面扣除            
        if(C("config.live_open") == '1' && ($order_info['is_liveshow'] == '1' || $order_info['is_livevideo'] == '1') && !$getSystemTakeMoney){
            if($order_info['is_liveshow'] == '1'){
                $live_info = M("Live_show")->where(['live_id'=>$order_info['liveshow_id']])->find();
            }
            elseif($order_info['is_livevideo'] == '1'){
                $live_info = M("Live_video")->where(['video_id'=>$order_info['livevideo_id']])->find();
            }
            //1、计算佣金
            $liveshow_money = 0;
            $desc = "";
            switch ($order_info['order_type']) {
                case 'mall':
                case 'shop':
                    $_where = ['order_id'=>$order_info['order_id']];
                    if($order_info['is_liveshow'] == '1'){
                        $_where['is_liveshow'] = 1;
                    }
                    else{
                        $_where['is_livevideo'] = 1;
                    }
                    $order_detail = M("Shop_order_detail")->where($_where)->field('id,liveshow_id,liveshow_percent,discount_price,num')->select();
                    foreach ($order_detail as $od) {
                        $goods_all_money = $od['num']*$od['discount_price'];
                        //减掉系统抽成
                        $goods_all_money = $goods_all_money - $goods_all_money*$date['percent']/100;
                        //再乘以设置的佣金比例就是主播抽成了
                        $goods_money = $goods_all_money * $od['liveshow_percent']/100;
                        M("Shop_order_detail")->where(['id'=>$od['id']])->data(['liveshow_money'=>$goods_money])->save();
                        $liveshow_money += $goods_money;
                    }
                    $date['money'] -= $liveshow_money;
                    M("Shop_order")->where(['order_id'=>$order_info['order_id']])->data(['live_merchant_money'=>$date['money'], 'live_author_money'=>$liveshow_money])->save();
                    if($order_info['is_liveshow'] == '1'){
                        $desc = "用户购买".($order_info['order_type'] == 'shop' ? C('config.shop_alias_name') : C('config.mall_alias_name'))."商品，增加佣金，直播ID".$live_info['live_no'];
                    }
                    else{
                        $desc = "用户购买".($order_info['order_type'] == 'shop' ? C('config.shop_alias_name') : C('config.mall_alias_name'))."商品，增加佣金，短视频ID".$live_info['video_id'];
                    }
                    break;
                case 'meal':
                    $_where = ['order_id'=>$order_info['order_id']];
                    if($order_info['is_liveshow'] == '1'){
                        $_where['is_liveshow'] = 1;
                    }
                    else{
                        $_where['is_livevideo'] = 1;
                    }
                    $order_detail = M("Foodshop_order_detail")->where($_where)->field('id,liveshow_id,liveshow_percent,price,num')->select();
                    $real_pay_all_money = $order_info['balance_pay']+$order_info['payment_money']+$order_info['score_deducte']+$order_info['coupon_price']+$order_info['merchant_balance'];

                    foreach ($order_detail as $od) {
                        $goods_all_money = $od['num']*$od['price'];
                        $p = $goods_all_money/$order_info['total_price'];//算出该商品在总价中的占比，就能知道他实际支付占比多少钱
                        $goods_all_money_real = $p*$real_pay_all_money;
                        //减掉系统抽成
                        $goods_all_money_real = $goods_all_money_real - $goods_all_money_real*$date['percent']/100;
                        //再乘以设置的佣金比例就是主播抽成了
                        $goods_money = $goods_all_money_real * $od['liveshow_percent']/100;
                        M("Foodshop_order_detail")->where(['id'=>$od['id']])->data(['liveshow_money'=>$goods_money])->save();
                        $liveshow_money += $goods_money;
                    }
                    $date['money'] -= $liveshow_money;
                    M("Foodshop_order")->where(['order_id'=>$order_info['order_id']])->data(['live_merchant_money'=>$date['money'], 'live_author_money'=>$liveshow_money])->save();
                    $desc = "用户购买".C('config.meal_alias_name')."商品，增加佣金，直播ID".$live_info['live_no'];
                    break;
                case 'group':
                    $liveshow_money = $money*$order_info['liveshow_percent']/100;
                    $date['money'] -= $liveshow_money;
                    M("Group_order")->where(['order_id'=>$order_info['order_id']])->data(['live_merchant_money'=>$date['money'], 'live_author_money'=>$liveshow_money])->save();
                    if($order_info['is_liveshow'] == '1'){
                        $desc = "用户购买".C('config.group_alias_name')."商品，增加佣金，直播ID".$live_info['live_no'];
                    }
                    else{
                        $desc = "用户购买".C('config.group_alias_name')."商品，增加佣金，短视频ID".$live_info['video_id'];
                    }
                    break;
            }
            if($liveshow_money > 0){
                D("User")->add_money($live_info['uid'], $liveshow_money, $desc, 0, 0, 0, 1);
                $date['live_money'] = $liveshow_money;
            }
        }

        //如果使用企业预存款，则按比例去除商家所得佣金
        $total_system_take = $date['system_take'];
        $total_merchant_take = $date['money'];
        $merchant_qiye_money_data = [];
        $order_price = $order_info['price'];
        $qiye_price = $order_info['qiye_pay'];
        $qiye_percent = round(($qiye_price / $order_price),2);
        if ($qiye_percent > 0 && $qiye_percent <= 1) {
            $qiye_money = $date['money'] * $qiye_percent;
            $qiye_system_take = $date['system_take'] * $qiye_percent;
            $date['money'] = $date['money'] - $qiye_money;
            $date['system_take'] = $date['system_take'] - $qiye_system_take;

            $merchant_qiye_money_data = [
                'mer_id' => isset($date['mer_id']) ? $date['mer_id'] : 0,
                'order_id' => isset($date['order_id']) ? $date['order_id'] : 0,
                'type' => isset($date['type']) ? $date['type'] : '',
                'money' => $qiye_money,
                'system_take' => $qiye_system_take,
                'percent' => $date['percent'],
                'total_money' => $qiye_price,
                'income' => $date['income'],
                'use_time' => $date['use_time'],
                'desc' => $date['desc'],
                'store_id' => isset($date['store_id']) ? $date['store_id'] : 0
            ];
        }

        // 自有支付抽成金额
        $own_system_take = 0;
        if(C('config.open_mer_own_percent') && $own_percent_money>0 ){
            $own_system_take = sprintf("%.2f",$own_percent_money*$percent/100);
        }

        // 抽成总额
        $systemTakeMoney = getFormatNumber($date['system_take']  + $union_data['system_take'] + $own_system_take);

        if($getSystemTakeMoney){
            // 只获取平台抽成金额
            return $systemTakeMoney;
        }

        // 订单支付信息
        $payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_info['orderid']))->find();
        $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
        $pay_config = $payment_money_order['pay_config'] ? json_decode($payment_money_order['pay_config'], true) : '';

        //养老金需求分账(sande)
        if($payment_money_order['pay_method'] == 'sande' && $payment_money_order['paid_money'] > 0 && $now_merchant['sande_merchant_fz'] != ''){
            //针对团购订单 全部核销才进行分账
            $sandeSettleResult = false;
            if ($order_info['order_type'] == 'group' && $order_info['verify_all'] != 1) {
                $leftPassCount = D('Group_pass_relation')->where(['order_id' => $order_info['order_id'], 'status' => 0])->count();
                if ($leftPassCount > 0) {
                    $payment_money_order['paid_money'] = 0;
                    $sandeSettleResult = true;
                }
            }

            import("@.ORG.pay.Sande");
            $pay = new Sande();
            $sande_order_no = $payment_money_order['order_type'].'_'.$payment_money_order['orderid'];
            $old_age_per = getFormatNumber(D("Percent_rate")->getOldAgePension($order_info['mer_id'], $order_info['order_type'], 0, true));
            $merchant_per = getFormatNumber(100 - getFormatNumber($date['percent']));
            $date['percent'] = $date['percent']-$old_age_per;
            if($date['percent'] < 0){
                $date['percent'] = 0;
            }

            //杉徳反馈，通过日志，发现咱们这边在杉徳的下单和分账是同时进行的，中间的间隔太短，有时候就会导致这种情况出现(订单不存在)，建议咱们这边在下单后延迟5秒再进行分账，就可以解决了。
            //写入到记录表，用计划任务执行分账逻辑
            $tm = time();
            $sandeSettlementData = [
                'order_no' => $sande_order_no,
                'platform_per' => getFormatNumber($date['percent']),
                'sande_merchant_fz' => $now_merchant['sande_merchant_fz'],
                'merchant_per' => $merchant_per,
                'old_age_statement_per' => $old_age_per,
                'uid' => $order_info['uid'],
                'payment_id' => $payment_money_order['id'] ?? 0,
                'create_time' => $tm,
                'plan_time' => $tm + 10
            ];
			usleep(1500000);
            if($sandeSettleResult || $pay->executeAccount($sande_order_no, getFormatNumber($date['percent']), $now_merchant['sande_merchant_fz'], $merchant_per, $old_age_per, $order_info['uid'], $payment_money_order)){
            // if (M('Sandpay_settlement')->add($sandeSettlementData)) {
                //$date['money'] = 0;
                $date['desc'] = $date['desc'] . " （养老金分账）";
            }
        }


        if(empty($payment_money_order) && $order_info['order_type'] == 'group' && $order_info['third_id']){// 团购订单使用新版支付 核销在老版代码 要支持新版支付
            $payment_money_order = M('Pay_order_info')->where(array('orderid'=>$order_info['third_id']))->find();
            if($payment_money_order){
                $pay_config = $payment_money_order['pay_config'] ? json_decode($payment_money_order['pay_config'], true) : '';
                $payment_money_order['paid_money'] = $payment_money_order['paid_money']/100;
                $payment_money_order['is_v20'] = 1;
                $payment_money_order['pay_type'] = $payment_money_order['pay_type'];
                $payment_money_order['pay_method'] = $payment_money_order['channel'];
                $payment_money_order['third_plat_data'] = $payment_money_order['paid_extra'];
                $order_info['third_id'] = $payment_money_order['paid_extra'];
            }
        }
        $chinaumsDistribution = false;

        //利楚扫呗(目前仅支持外卖)
        if(in_array($order_info['order_type'],['shop']) && $payment_money_order['pay_method'] == 'lichu' && $payment_money_order['paid_money'] > 0){
            //获取利楚商户参数
//            $merchant_info = M("Merchant")->where(['mer_id'=>$order_info['mer_id']])->field('lichu_merchant_no,lichu_terminal_id,lichu_token')->find();
            if(!isset($payment_money_order['source']) || $payment_money_order['source']=='merchant'){
                //商户参数配置了，不用增加余额
                $date['money'] = 0;
                $date['desc'] = $date['desc']." （利楚分账）";
            }
        }
        if(
            C('customization.open_yibao') &&
            $payment_money_order['pay_method'] == 'mallbook' &&
            $payment_money_order['paid_money'] > 0
        ){//mallbook通道支付
            $payAmount = M('mallbook_pay_info')
                ->where('mallbook_order_no', $payment_money_order['orderid'])
                ->find();
            $addMerMoney = $date['money'];
            if($date['money'] >= $payAmount['amount']/100){
                $date['money'] = $date['money'] - $payAmount['amount']/100;
            }else{
                $date['money'] = 0;
            }
            import('@.ORG.pay.Mallbookpay.MallbookPayService');
            (new MallbookPayService())->separateMoney($payment_money_order['orderid'], $order_info['mer_id'], $addMerMoney);
        }else
        //银联分账(chinaums)
        if($payment_money_order['pay_method'] == 'chinaums' && $payment_money_order['paid_money'] > 0){
            if(C('config.chinaums_platform_sub_mchid')){
                //平台通过子商户角色分账
                $chinaums_data = [
                    'mer_id' => $order_info['mer_id'],
                    'create_time' => time(),
                    'merchant_money' => $payment_money_order['chinaums_merchant_already_get'],
                    'platform_money' => $payment_money_order['chinaums_platform_already_get'],
                    'is_withdraw' => 0,
                    'plat_is_withdraw' => 0,
                    'pay_orderid' => $payment_money_order['orderid'],
                    'type' => 'settlement'
                ];
                if (M("Chinaums_fenzhang_record")->data($chinaums_data)->add()) {
                    $date['money'] = 0;
                    $date['system_take'] = $payment_money_order['chinaums_platform_already_get']/100;
                    $date['desc'] = sprintf($date['desc'] . " （银联分账:%.2f元）",getFormatNumber($payment_money_order['chinaums_merchant_already_get']/100));
                }
            } elseif (C('config.open_chinaums_without_asset') && in_array($payment_money_order['env'], ['wxMini', 'wechat_mini'])) {
                //不接资管
                import('@.ORG.pay.Chinaums');
                $pay = new Chinaums();
                if ($payment_money_order['is_v20']) {
                    $chinaumsOrderid = $payment_money_order['orderid'];
                    $chinaumsMoney = $payment_money_order['payment_money']*100;
                    $subMerNo = D('Merchant')->where(['mer_id' => $order_info['mer_id']])->getField('chinaums_merno');
                    $subOrders = [
                        ['merId' => $subMerNo, 'satAmt' => $payment_money_order['chinaums_merchant_already_get']],
                    ];
                    $result = (new Chinaums())->fenzhangConfirm($chinaumsOrderid, $chinaumsMoney, $subOrders);
                    if ($result) {
                        M('Pay_order_info')->where(array('id' => $payment_money_order['id']))->save(['async_confirm_result' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
                    }
                } else {
                    $chinaumsOrderid = $payment_money_order['orderid'];
                    $chinaumsMoney = $payment_money_order['paid_money'] * 100;
                    $subMerNo = D('Merchant')->where(['mer_id' => $order_info['mer_id']])->getField('chinaums_merno');
                    $subOrders = [
                        ['merId' => $subMerNo, 'satAmt' => $payment_money_order['chinaums_merchant_already_get']],
                    ];
                    $result = (new Chinaums())->fenzhangConfirm($chinaumsOrderid, $chinaumsMoney, $subOrders);
                    if ($result) {
                        M('Payment_money_order')->where(array('orderid' => $order_info['orderid']))->save(['async_confirm_result' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
                    }
                }
                $chinaumsMerMoney = getFormatNumber($payment_money_order['chinaums_merchant_already_get'] / 100);
                if (isset($order_info['is_own']) && $order_info['is_own'] > 0) {
                    $leftMoney = $date['money'];
                    $date['system_take'] += $payment_money_order['chinaums_platform_already_get'] / 100;
                } else {
                    $leftMoney = getFormatNumber($date['money'] - $chinaumsMerMoney);
                }
                $date['money'] = $leftMoney;
                $date['desc'] = sprintf($date['desc'] . " （银联分账:%.2f元）",getFormatNumber($payment_money_order['chinaums_merchant_already_get']/100));
                $chinaumsDistribution = true;
            }else{
                //除去商家全部分给平台
                $chinaums_data = [
                    'mer_id' => $order_info['mer_id'],
                    'create_time' => time(),
                    'merchant_money' => $payment_money_order['chinaums_merchant_already_get'],
                    //'platform_money' => ($payment_money_order['paid_money'] >= $date['money'])?round(($payment_money_order['paid_money']-$date['money'])*100):0,
                    'platform_money' =>$payment_money_order['chinaums_platform_already_get'],
                    'is_withdraw' => 0
                ];
                // if($payment_money_order['chinaums_merchant_already_get'] > 0){
                //     $date['money'] = 0;
                //     $date['desc'] = $date['desc']." （银联分账）"; 
                // }
                // else{
                    if (C('config.is_1238888_system') || M("Merchant_chinaums_record")->data($chinaums_data)->add()) {
                        $chinaumsMerMoney = getFormatNumber($payment_money_order['chinaums_merchant_already_get'] / 100);
                        $leftMoney = $date['money'] > $chinaumsMerMoney ? getFormatNumber($date['money'] - $chinaumsMerMoney) : 0;
                        $date['money'] = $leftMoney;
                        $date['desc'] = $date['desc'] . " （银联分账:" . $chinaumsMerMoney . "元）";
                    }
                // }
            }
        }elseif(($payment_money_order['pay_type'] == 'weixin' || $payment_money_order['pay_type'] == 'wechat') && $pay_config['open_weixin_profit_sharing'] && $payment_money_order['paid_money']>0){// 微信分账
            $order_info_new = $order_info;
            $order_info_new['system_take'] = $systemTakeMoney;
            $order_info_new['percent'] = $percent;
            $order_info_new['orderid'] = $payment_money_order['orderid'];

            if($order_info['order_type'] == 'group' && $pass_total_num>1){// 团购多个消费码需要多次分账
                $order_info_new['multi_profit_sharing'] = 1;
                $order_info_new['own_money'] = $own_percent_money;
                $order_info_new['is_complete'] = $is_complete;
            }

            // 微信分账
            $weixinLedgerRes = $this->save_weixin_profit_sharing($order_info_new, $payment_money_order);
            if(C('config.open_mer_own_percent') && $own_percent_money >0){
                $date['desc'] = $date['desc']." （微信分账".$weixinLedgerRes['merchant_get_money']."）"; 
            } 

            if($weixinLedgerRes['is_need'] && $weixinLedgerRes['error']){// 分账失败
                return array('error_code'=>true,'msg'=>L_('分账失败'));
            }
        }

        // 自有支付抽成
        if(C('config.open_mer_own_percent') && $own_percent_money>0 && !$getSystemTakeMoney && !$weixinLedgerRes['is_need'] && !$chinaumsDistribution){
            $desc_pay_for_system=L_("对商家自有支付抽成，在线自有支付支付金额：X1，扣除商家余额：X2",array("X1" => $own_percent_money,"X2" => sprintf("%.2f",$own_percent_money*$percent/100))); 
            $result_pay = $this->use_money($mer_id,sprintf("%.2f",$own_percent_money*$percent/100),$order_info['order_type'],$desc_pay_for_system,$date['order_id'],$percent,sprintf("%.2f",$own_percent_money*$percent/100),array(),$order_info['store_id']);
        }


        //商家自有支付不应该结算商家余额
        if(isset($order_info['is_own'])&&$order_info['is_own']>0){
            $date['money']=0;
        }


        if ($union_percent != 100 && $union_data['money']!=0 && !M('Merchant')->where(array('mer_id'=>$order_info['union_mer_id']))->setInc('money', $union_data['money'])) {
            return array('error_code'=>true,'msg'=>L_('增加联盟商家余额失败'));
        }
        if(is_nan($date['money'])){
            $date['money']=0;
        }

        //$data['money']  商家收入，data['system_take']  平台抽成  卡拉卡（钱账通）分账
        if ($payment_money_order['pay_method'] == 'lakala' && $payment_money_order['paid_money'] > 0) {
            //预约的特殊处理，存在定金分账
            if ($order_info['order_type'] == 'appoint' && $payment_money_order['order_type'] == 'balance-appoint') {
                //说明预约商品存在定金支付
                $depositPay = M('Payment_money_order')->where(['order_id' => $payment_money_order['order_id'], 'pay_method' => 'lakala', 'order_type' => 'appoint', 'paid_money' => ['gt', 0]])->find();
                $calResult = $this->calFzCommission($depositPay ? $depositPay['paid_money'] : 0, $payment_money_order['paid_money'], $date['money'], $date['system_take']);
                $merGet = $calResult['merBalanceSettlement'];
                $platGet = $calResult['platBalanceCommission'];
                $merDepositGet = $calResult['merDepositSettlement'];
                $platDepositGet = $calResult['platDepositCommission'];
                $date['money'] = bcsub($date['money'], $merGet, 2);
                $date['money'] = bcsub($date['money'], $merDepositGet, 2);
                $date['desc'] = sprintf($date['desc'] . " （【拉卡拉分账】商户：%s,平台：%s）", bcadd($merGet, $merDepositGet, 2), bcadd($platGet, $platDepositGet, 2));
                $date['platform_get'] = bcadd($platGet, $platDepositGet, 2);
                $date['merchant_get'] = bcadd($merGet, $merDepositGet, 2);
                if ($merGet > 0 || $platGet > 0) {
                    //尾款分账
                    try {
                        import('@.ORG.pay.Lakala');
                        (new Lakala([], json_decode($payment_money_order['pay_config'], true)))->fenzhang($payment_money_order['third_plat_data'], bcmul($merGet, 100), bcmul($platGet, 100));
                    } catch (\Throwable $th) {
                        fdump_api([$payment_money_order['third_plat_data'], $th->getMessage()], 'pay/LakalaFenzhangErr', 1);
                    }
                }
                if ($merDepositGet > 0 || $platDepositGet > 0) {
                    //定金分账
                    try {
                        import('@.ORG.pay.Lakala');
                        (new Lakala([], json_decode($depositPay['pay_config'], true)))->fenzhang($depositPay['third_plat_data'], bcmul($merDepositGet, 100), bcmul($platDepositGet, 100));
                    } catch (\Throwable $th) {
                        fdump_api([$depositPay['third_plat_data'], $th->getMessage()], 'pay/LakalaFenzhangErr', 1);
                    }
                }
            } else {
                $calResult = $this->calFzCommission(0, $payment_money_order['paid_money'], $date['money'], $date['system_take']);
                $merGet = $calResult['merBalanceSettlement'];
                $platGet = $calResult['platBalanceCommission'];
                $date['money'] = bcsub($date['money'], $merGet, 2);
                $date['desc'] = sprintf($date['desc'] . " （【拉卡拉分账】商户：%s,平台：%s）", $merGet, $platGet);
                $date['platform_get'] = $platGet;
                $date['merchant_get'] = $merGet;
                try {
                    import('@.ORG.pay.Lakala');
                    (new Lakala([], json_decode($payment_money_order['pay_config'], true)))->fenzhang($payment_money_order['third_plat_data']??$payment_money_order['paid_extra'], bcmul($merGet, 100), bcmul($platGet, 100));
                } catch (\Throwable $th) {
                    fdump_api([$payment_money_order['third_plat_data'], $th->getMessage()], 'pay/LakalaFenzhangErr', 1);
                }
            }
            $date['money'] < 0 && $date['money'] = 0;
        }

        if( $percent != 100 && $date['money']!=0 && !M('Merchant')->where(array('mer_id'=>$mer_id))->setInc('money', $date['money'])  ){
            return array('error_code'=>true,'msg'=>L_('增加商家余额失败'));
        }
        $newMerchant = M('Merchant')->where(array('mer_id'=>$mer_id))->field('money,status')->find();
        if($newMerchant && $newMerchant['money'] >= 0 && $newMerchant['status'] == 3){//修改欠款状态
            M('Merchant')->where(array('mer_id'=>$mer_id))->setField('status',1);
        }
        if($order_info['order_type']=='group'||$order_info['order_type']=='meal'||$order_info['order_type']=='shop' ||$order_info['order_type']=='mall' ||$order_info['order_type']=='appoint'||$order_info['order_type']=='store'||$order_info['order_type']=='cash'||$order_info['order_type']=='wxapp'||$order_info['order_type']=='weidian'){
            if($order_info['order_type']=='cash'){
                $date['type'] = 'store';
            }
            M(ucfirst($date['type']) . '_order')->where(array('order_id' => $order_info['order_id']))->setField('is_pay_bill', 1);
            $date['type'] = $order_info['order_type'];
        }

        $now_mer_money = M('Merchant')->field('money')->where(array('mer_id'=> $mer_id))->find();
        $now_union_mer_money = M('Merchant')->field('money')->where(array('mer_id'=> $order_info['union_mer_id']))->find();
        $date['now_mer_money'] = $now_mer_money['money'];
        $union_data['now_mer_money'] = $now_union_mer_money['money'];		

        //增加商家企业预存款
        if ($merchant_qiye_money_data && $percent != 100 && $merchant_qiye_money_data['money'] != 0) {
            M('Merchant')->where(array('mer_id' => $mer_id))->setInc('qiye_money', $merchant_qiye_money_data['money']);
        }

        $now_mer_money = M('Merchant')->field('money,qiye_money')->where(array('mer_id'=> $mer_id))->find();
        $date['now_mer_money'] = $now_mer_money['money'];
        if ($merchant_qiye_money_data) {
            $merchant_qiye_money_data['now_mer_qiye_money'] = $now_mer_money['qiye_money'];
        }
        // 商家当前余额
        $now_mer_money = getFormatNumber($now_merchant['money'] + $date['money']);
        // 联盟商家当前余额
        $now_union_mer_money = $now_merchant_union ? getFormatNumber($now_merchant_union['money'] + $union_data['money']) : 0;

        $date['now_mer_money'] = $now_mer_money;
        $union_data['now_mer_money'] = $now_union_mer_money;
		if($date['type'] == 'yydb' || $date['type'] == 'coupon'){
			$date['type'] = 'activity';
		}

        $union_data['union_mer_id'] = $mer_id;
        fdump($union_data,'union_data');
        if ($order_info['union_mer_id'] && !$this->add($union_data)) {
            return array('error_code'=>true,'msg'=>$desc.' ，保存联盟商家收入失败！');
        }

        try {
            if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
                // 获取平台抽成金额	
                $commission_money = $systemTakeMoney;
            }else{
                //区域管理员得到分佣（扣除平台抽成后的金额，计算分佣）
                if($date['pay_money'] > 0){
                    $commission_money = $date['pay_money'];
                }
                else{
                    $commission_money = $date['total_money'];
                }
            }
            
            //区域管理员得到分佣（扣除平台抽成后的金额，计算分佣）
            D("Area_commission")->addCommission($commission_money, $mer_id, $order_info['store_id'], $order_info['order_type'], $date['order_id']);
            //平台业务员得到分佣（扣除平台抽成后的金额，计算分佣）
            fdump_api([$commission_money, $systemTakeMoney, $date, $other], 'merchant_money_list/bd', 1);
            
            if(empty(C('config.open_bd_spread_new_commission')) && empty($date['system_take'])){
                fdump_api([
                    '当平台抽成为0的时候,业务经理和业务员不抽成',
                    $commission_money, $systemTakeMoney, $date, $other, $order_info
                ], 'merchant_money_list/bd', 1);
            }else if($other != 'reject'){
                D('Bd')->addCommission($commission_money, $mer_id, $order_info['store_id'], $order_info['order_type'], $date['order_id']);
                D('Bd')->addMerchantCommission($commission_money, $order_info['uid'], $order_info['store_id'], $order_info['order_type'], $date['order_id']);
            }
        }
        catch (Exception $e){
            fdump($e->getMessage(), "lumin0803", 1);
        }

        // 团购优惠组合订单
        if($order_info['order_type']  == 'group_combine'){
            $order_info['order_type'] = 'group';
            $date['type'] = 'group';
        }
        fdump($date , '2222222222222',1);

        //当平台支付用户实际支付的钱不足平台抽成金额的时候扣除商家余额
        if (in_array($order_info['order_type'], ['group', 'shop']) && $order_info['is_own'] == 0 && $order_info['payment_money'] > 0 && $order_info['payment_money'] < $date['system_take']) {
            $diffMoney = $date['system_take'] - $order_info['payment_money'];
            if (M('Merchant')->where(array('mer_id' => $mer_id))->setDec('money', $diffMoney)) {
                $date['now_mer_money'] -= $diffMoney;
            }
        }

        if(!$this->add($date)){
            return array('error_code'=>true,'msg'=>$desc.' ，保存商家收入失败！');
        }else{
            invoke_v20_service('\app\internal_card\model\service\InternalCardMoneyBillService/setServiceFee',[$order_info, $percent]);
            //预约服务费结算
            if($order_info['order_type'] == 'appoint' && $merchantWorkerServiceFee){
                $worksMoney = [//结算服务费
                    'merchant_worker_id' => $appointSupply['worker_id'],
                    'type' => 3,//3 结算成冻结金额
                    'money'=> $merchantWorkerServiceFee,
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'desc' => '当前订单已完成，结算服务费',
                    'admin_id' => 1,
                    'order_id' => $appointSupply['supply_id'],
                    'from_type' => 2,
                ];
                M('Merchant_workers_money')->add($worksMoney);
                fdump_api([$worksMoney, M('Merchant_workers_money')->_sql()],'appoint/appoint_service_fee',1);
                $orderOriginPrice = $appointOrder['product_price'] > 0?$appointOrder['product_price']:$appointOrder['appoint_price'];
                //结算费用从商家扣
                $this->use_money(
                    $mer_id,
                    $merchantWorkerServiceFee,
                    'appoint',
                    "当前订单结算服务费扣除商家金额(订单原价：{$orderOriginPrice}元)",
                    $order_info['order_id'],0,0,[],$appointSupply['store_id']);
            } elseif ($order_info['uid'] && $order_info['order_type'] == 'appoint') {
                D('Appoint_order')->giveUserCoupon($order_info['order_id']);
            }
            
            fdump_api($orderInfo,'rebate/error',true);
            if($orderInfo['order_type']=='shop'){
                //集单返券
                $model = '\app\shop\model\service\rebate\RebateService/addRebateUser';
                $params = ['orderInfo'=>$orderInfo];
                invoke_v20_service($model, $params);

                D('Shop_order')->finishTwOrder($orderInfo['order_id']);
            }
            //推广商商家收入获取佣金
            if ($date['income'] == 1) {
                $inviteRecord = M('Merchant_spread_invite_merchant')->where(['mer_id' => $mer_id])->find();
                if ($inviteRecord) {
                    $inviteUser = M('Merchant_spread_user')->where(['uid' => $inviteRecord['uid']])->find();
                    $commissionMoney = getFormatNumber($date['money'] * $inviteUser['commission'] / 100);
                    if ($inviteUser && $inviteUser['is_city_spread'] && $commissionMoney > 0) {
                        $commissionLog = [
                            'uid' => $inviteRecord['uid'],
                            'mer_id' => $mer_id,
                            'business_type' => $date['type'],
                            'business_id' => $date['order_id'],
                            'money' => $commissionMoney,
                            'consume_money' => $date['money'],
                            'commission' => $inviteUser['commission'],
                            'create_time' => time()
                        ];
                        M('Merchant_spread_commission')->add($commissionLog);
                        M('Merchant_spread_user')->where(['uid' => $inviteRecord['uid']])->setInc('spread_money', $commissionMoney);
                        D('User')->add_money($inviteRecord['uid'], $commissionMoney, L_('推广商佣金'));
                    }
                }

            //分销员分享抽成到账(新版商城分销到账走自动关闭接口)
            if ($order_info['order_type'] == 'group') {
                invoke_v20_service('\app\store_marketing\model\service\StoreMarketingRecordService/doArrival',[
                    'param' => [
                        'order_id' => $order_info['order_id'],
                        'goods_type' => 1
                    ]
                ]);
            }

            //重置date参数，因为下面都是根据订单总金额和总佣金发放积分等
            $date['system_take'] = $total_system_take;
            $date['money'] = $total_merchant_take;

            if ($merchant_qiye_money_data) {
                M('Merchant_qiye_money_list')->add($merchant_qiye_money_data);
            }

            if($order_info['order_type'] != 'withdraw'&& !empty($mer_user) && $mer_user['open_money_tempnews']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $alias_name = $this->get_alias_c_name($order_info['order_type']);
                $store_name = L_('无');
                if (!empty($now_store)) {
                    $store_name = $now_store['name'];
                }
                $model->sendTempMsg('OPENTM402026291', array('href' => '', 'wecha_id' => $mer_user['openid'], 'first' => L_("收款成功，当前商家余额：X1",array("X1" => $now_mer_money))  , 'keyword1' => $alias_name[$order_info['order_type']], 'keyword2' =>$money,'keyword3' =>$store_name, 'keyword4' => date("Y年m月d日 H:i"),'keyword5' =>$date['order_id'],'remark' => L_('请到商家中心商家余额中查看')), $mer_id);
            }
            if($order_info['order_type']!='merrecharge' && $order_info['order_type']!='merchant_user_recharge'){
                if ($order_info['union_mer_id']) {
                    // 商家联盟，使用联盟商家会员卡应当扣除联盟商家的会员卡余额
                    $this->merchant_card($order_info['union_mer_id'],$order_info);
                }else{
                    $this->merchant_card($mer_id,$order_info);
                }
            }

            //企业预付款支付订单结算
            D('Qiye')->settlementOrder($order_info['order_type'],$date['order_id']);

            //解冻用户奖励金额 定制
            if(C('config.free_recommend_awards_percent')>0 && $order_info['uid'] ){
                // 在线支付金额 平台余额 商家余额 商家会员卡赠送余额
                // 推广注册的用户 推广注册的商家
                $award_money  = $order_info['payment_money'] + $order_info['balance_pay']+$order_info['merchant_balance']+$order_info['card_give_money'];
                if($award_money > 0) {
                    $spread_users[]  = $order_info['uid'];
                    $User_model      = D('User');
                    $Fenrun_model      = D('Fenrun');
                    $now_user        = $User_model->get_user($order_info['uid']);
                    $where['_string'] = " uid = {$now_user['uid']}";

                    $now_user_spread = D('User_spread')->field(true)->where($where)->find();

                    if (!empty($now_user_spread)) {
                        $spread_user = $User_model->get_user($now_user_spread['spread_uid'], 'uid');

                        if ($spread_user && !in_array($spread_user['uid'], $spread_users)) {
                            $free_money = round(($award_money) * C('config.free_recommend_awards_percent') / 100, 2);

                            if ($free_money > 0 && $spread_user['frozen_award_money'] > 0) {
                                $Fenrun_model->free_user_recommend_awards($spread_user['uid'], $free_money,1,$order_info['uid']);
                            }
                        }
                    }
                    //如果该商家是被推荐注册的

                    if ($now_merchant['uid']) {
                        $now_merchant_user = $User_model->get_user($now_merchant['uid']);
                        $where['_string'] = " uid = {$now_merchant['uid']}";
                        $now_user_spread   = D('User_spread')->field(true)->where($where)->find();
                        if (!empty($now_user_spread)) {
                            $spread_user = $User_model->get_user($now_user_spread['spread_uid'], 'uid');
                            if ($spread_user && !in_array($spread_user['uid'], $spread_users)) {
                                $free_money = round(($award_money) * C('config.free_mer_award_percent') / 100, 2);
                                if ($free_money > 0 && $spread_user['frozen_award_money'] > 0) {
                                    $Fenrun_model->free_user_recommend_awards($spread_user['uid'], $free_money, 2,$mer_id);
                                }
                            }
                        }
                    }
                }
            }

            if(C('config.open_distributor')>0 && $order_info['mer_id'] &&C('config.agent_percent')>0){
                D('Distributor_agent')->agent_add_money($order_info['mer_id'],$money,$order_info['order_id']);
            }

            if((!$other || $other != 'reject') && $order_info['order_type']!='merrecharge' &&  $order_info['order_type']!='merchant_user_recharge' && C('config.open_c2b_spread')>0 && $order_info['mer_id'] &&C('config.c2b_first_rate')>0){
                D('UserSpreadMer')->user_add_money($order_info,$money);
            }

            if(C('config.share_coupon')==1 && $order_info['share_status']==1){
                $param['type'] = $order_info['order_type'];
                $param['order_id'] = $order_info['order_id'];
                $param['uid'] = $order_info['uid'];
                D('System_coupon')->share_coupon_rand_get_coupon($param);
            }
            /*if($pay_for_system!=0){
                if($order_info['order_from']==1){
                    $desc_pay_for_system=L_("商城订单快递配送转为X1，系统扣除X2费",array("X1" => C('config.deliver_name'),"X2" => C('config.deliver_name'))); 
                }else{
                    $desc_pay_for_system= L_("转X1，平台从商家余额中扣除订单的配送费",array("X1" => C('config.deliver_name'))); 
                }
                $this->use_money($mer_id,$pay_for_system,$order_info['order_type'],$desc_pay_for_system,$order_info['real_orderid'],$mer_user);
            }*/

           
            //增加积分按平台抽成算
            if(C('config.add_score_by_system_commission')==1){// 定制 积分按抽成算
                // 商家获得积分
                $merchant_get_score = round($systemTakeMoney*C('config.add_score_by_system_commission_merchant_percent')/100,4);
                D('Merchant_get_score')->addScoreLog($order_info['mer_id'], $merchant_get_score, $order_info);
            }
                fdump_api(['11'=>[
                    $order_info['uid'],
                    $systemTakeMoney
                ]],'user_score0930',1);
                
            $systemTakeMoney =  $date['system_take'];
            if($order_info['uid']>0 && $systemTakeMoney>0 && (C('config.open_score_discount')==0 || $order_info['score_discount_type']!=2) && (C('config.add_score_by_system_commission') || C('config.add_score_by_percent')==1)){
                $score_name = C('config.score_name');
                $now_user  = D('User')->get_user($order_info['uid']);

                switch($order_info['order_type']){
                    case 'store':
                    case 'cash':
                        $score_desc = L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $now_store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($order_info['total_money']) . C('config.Currency_txt'),"X4" => $score_name)); 
                        $spread_desc = L_("X1用户X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.cash_alias_name'))); 
                        $fund_desc = L_("X1用户X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.cash_alias_name'))); 
                        break;
                    case 'group':
                        $score_desc = L_("购买 X1 消费X2 获得X3",array("X1" => $order_info['order_name'],"X2" => floatval($order_info['total_money']) . C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.group_alias_name'))); 
                        $fund_desc = L_("X1用户购买X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.group_alias_name'))); 
                        break;
                    case 'shop':
                        $score_desc = L_("在 X1 中消费X2 获得X3",array("X1" => $now_store['name'],"X2" => floatval($order_info['total_money']) . C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.shop_alias_name'))); 
                        $fund_desc = L_("X1用户购买X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.shop_alias_name'))); 
                        break;
                    case 'mall':
                        $score_desc = L_("在 X1 中消费X2 获得X3",array("X1" => $now_store['name'],"X2" => floatval($order_info['total_money']) . C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.mall_alias_name'))); 
                        $fund_desc = L_("X1用户购买X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.mall_alias_name'))); 
                        break;
                    case 'meal':
                        $score_desc =L_("购买X1商品获得X2",array("X1" => C('config.meal_alias_name'),"X2" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.meal_alias_name'))); 
                        $fund_desc = L_("X1用户购买X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.meal_alias_name'))); 
                        break;
                    case 'shop_offline':
                        $score_desc = L_("在 X1 中消费X2 获得X3",array("X1" => $now_store['name'],"X2" => floatval($order_info['total_money']) . C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2线下零售获得佣金",array("X1" => $now_user['nickname'],"X2" => $this->config['shop_alias_name'])); 
                        $fund_desc = L_("X1用户购买X2线下零售获得备用金",array("X1" => $now_user['nickname'],"X2" => $this->config['shop_alias_name'])); 
                        break;
                    case 'appoint':
                        $score_desc = L_("购买X1商品获得X2",array("X1" => C('config.appoint_alias_name'),"X2" => $score_name)); 
                        $spread_desc = L_("X1用户购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.appoint_alias_name'))); 
                        $fund_desc = L_("X1用户购买X2获得备用金",array("X1" => $now_user['nickname'],"X2" => C('config.appoint_alias_name'))); 
                        break;
                    case 'wxapp':
                        $score_desc = L_("购买 X1 消费X2 获得X3",array("X1" => $order_info['order_id'],"X2" => floatval($order_info['money']).C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买营销获得佣金",array("X1" => $now_user['nickname'])); 
                        $fund_desc = L_("X1用户购买营销获得备用金",array("X1" => $now_user['nickname'])); 
                        break;
                    case 'weidian':
                        $score_desc = L_("购买 X1 消费X2 获得X3",array("X1" => $order_info['order_id'],"X2" => floatval($order_info['money']).C('config.Currency_txt'),"X3" => $score_name)); 
                        $spread_desc = L_("X1用户购买微店获得佣金",array("X1" => $now_user['nickname'])); 
                        $fund_desc = L_("X1用户购买微店获得备用金",array("X1" => $now_user['nickname'])); 
                        break;
                }
                fdump_api([
                    $systemTakeMoney,
                    C('config.open_score_pay_back'), 
                    C('config.score_get_times'),
                    $score_desc
                    ],'user_score0930',1);
                
                if(C('config.add_score_by_system_commission')==1){// 定制 积分按抽成算
                    // 用户获得积分
                    $user_get_score = round($systemTakeMoney*C('config.add_score_by_system_commission_user_percent')/100,4);
                    D('User')->add_score($order_info['uid'], $user_get_score,$score_desc,0,[
                        'mer_id' => $order_info['mer_id']??0,
                        'store_id' => $order_info['store_id']??0,
                        'order_id' => $order_info['order_id'],
                        'order_type' => $order_info['order_type']]);
                }elseif(C('config.open_score_pay_back')==1 && C('config.score_get_times')>0 && round($systemTakeMoney)>0){
                    $score_pay_back= $systemTakeMoney*C('config.score_get_times');
                    D('User')->add_score($order_info['uid'], $score_pay_back,$score_desc,0,[
                        'mer_id' => $order_info['mer_id']??0,
                        'store_id' => $order_info['store_id']??0,
                        'order_id' => $order_info['order_id'],
                        'order_type' => $order_info['order_type']]);
                } elseif (C('config.add_score_by_percent') == 1 && $systemTakeMoney > 0) {
                        if (C('config.open_score_get_percent') == 1) {
                            //百分比
                            $userGetScore = getFormatNumber($systemTakeMoney * C('config.score_get_percent') / 100);
                        } else {
                            //积分
                            $userGetScore = getFormatNumber($systemTakeMoney * C('config.user_score_get'));
                        }
                        D('User')->add_score($order_info['uid'], $userGetScore, $score_desc, 0, [
                            'mer_id' => $order_info['mer_id'] ?? 0,
                            'store_id' => $order_info['store_id'] ?? 0,
                            'order_id' => $order_info['order_id'],
                            'order_type' => $order_info['order_type']
                        ]);
                    }
                fdump_api([
                    D('User')->getLastSql()
                ],'user_score0930',1);
                
                // 商家获得积分按抽成算
                if(C('config.open_score_pay_back_mer')==1 && C('config.score_get_times_mer')>0 && round($systemTakeMoney)>0){
                    $score_pay_back_mer= $systemTakeMoney*C('config.score_get_times_mer');
                    D('Merchant')->add_system_score($order_info, $systemTakeMoney, $score_pay_back_mer,$score_desc);
                }

                // 商家推广佣金获得积分按抽成算
                if(C('config.system_take_spread_percent_mer')>0){
                    $res = D('Merchant_spread')->add_spread_list($order_info,$now_user,$order_info['order_type'],$spread_desc,$systemTakeMoney);
                }

                // 商家获得平台采购备用金
                if(C('config.reserve_fund_percent')>0){
                    $reserve_fund= $systemTakeMoney*C('config.reserve_fund_percent')/100;
                    $res = D('Merchant')->add_reserve_fund($order_info, $reserve_fund,$fund_desc);
                }

                D('Scroll_msg')->add_msg($order_info['type'],$order_info['uid'],L_('用户x1于x2购买 x3产品成功并消费获得x4',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>C('config.'.$order_info['order_type'].'_alias_name'),'x4'=>$score_name)));
            }

            if($order_info['uid']){
                if($order_info['order_type']=='shop' && $order_info['order_from']==1){
                    $spread_condition['order_type'] ='mall';
                }else{
                    $spread_condition['order_type'] =$order_info['order_type'];
                }

                if ($spread_condition['order_type'] == 'meal') {
                    $foodshop_order = M("Foodshop_order")->field('order_id')->where(array('real_orderid'=>$order_info['order_id']))->find();
                    $spread_condition['order_id'] = $foodshop_order['order_id'];
                }else{
                    $spread_condition['order_id'] = $order_info['order_id'] ;
                }

                $spread_condition['level'] = 0; // 有等级的在订单过了可申请售后时间再对账
                
                $now_spread = D('User_spread_list')->where($spread_condition)->select();
                
                foreach($now_spread as $v){
                    if(C('config.open_distributor')>0){
                        $res= D('Distributor_agent')->get_effective($v['uid'],1);
                        if($res['error_code']){
                            break;
                        }
                    }
                    $this->spread_check($v['uid'],$v['pigcms_id'], 1);

                }
                if(C('config.open_user_award')==1){
                    $award_percent = D('Percent_rate')->get_user_award($order_info['order_type']);
                    $award_percent = $award_percent['first_rate'];

                    if($award_percent>0){
                        if($order_info['order_type']=='group' && !$order_info['verify_all']){
                            $award_money = round(($order_info['payment_money']+$order_info['balance_pay'])/$count*$award_percent/100,2);
                        }else{
                            $award_money = round(($order_info['payment_money']+$order_info['balance_pay'])*$award_percent/100,2);
                        }
                    }
                    if($award_money>0){
                        D('User')->add_money($order_info['uid'],  $award_money, L_('用户消费奖励金'));
                    }
                }
                //校验该订单是否存在分佣的积分
                $this->spread_check_jifen($order_info['order_id'],$order_info['order_type']);
            }

            // 单个商品分销(定制)20190614

            if ($order_info['distributor_uid']) {
                if($order_info['order_type']=='shop' && $order_info['order_from']==1){
                    $spread_condition['order_type'] ='mall';
                }else{
                    $spread_condition['order_type'] =$order_info['order_type'];
                }

                if ($spread_condition['order_type'] == 'meal') {
                    $foodshop_order = M("Foodshop_order")->field('order_id')->where(array('real_orderid'=>$order_info['order_id']))->find();
                    $spread_condition['order_id'] = $foodshop_order['order_id'];
                }else{
                    $spread_condition['order_id'] = $order_info['order_id'] ;
                }

                $now_spread = D('Distributor_spread_list')->where($spread_condition)->select();
                foreach($now_spread as $v){
                    $this->distributor_spread_check($v['order_uid'],$v['pigcms_id']);
                }
            }

            // 平台用户给其他用户充值商家会员卡赠送余额
            if ($order_info['order_type'] == 'merchant_user_recharge' && $order_info['card_id']) {
                // 增加会员卡赠送余额
                $parm = [
                    'card_id' => $order_info['card_id'],
                    'type' => 1,
                    'money_add' => $date['money'],
                    'desc' => L_('用户X1给会员充值增加', ['X1' => $now_user['nickname']])
                ];
                if (!M('Card_userlist')->where(array('id' => $order_info['card_id']))->setInc('card_money_give', $date['money'])) {
                    return array('error_code' => true, 'msg' => '会员卡余额增加失败！');
                }
                D('Card_new')->add_row($parm);
            }
        
            /*---更新系统订单状态--*/
            if($order_info['order_type']=='shop' && $order_info['order_from']==1){
                D('System_order')->save_reg_order_info('mall',$order_info['order_id'],array(),2);
            }else{
                D('System_order')->save_reg_order_info($order_info['order_type'],$order_info['order_id'],array(),2);
            }
			
			if(C('config.nmgzhcs_appid') && $now_merchant['nmgzhcs_appid']){
				$this->nmgzhcs_money($now_merchant['nmgzhcs_appid'],$date['order_id'],$date['money'],$desc);
			}
            if($order_info['order_type']=='appoint'){
                $systemStatus = D('System_order')->where([
                    'type' => 'appoint',
                    'order_id' => $order_info['order_id'],
                ])->getField('system_status');
                if($systemStatus == 0){
                    D('System_order')->where([
                        'type' => 'appoint',
                        'order_id' => $order_info['order_id'],
                    ])->save(['system_status' => 2]);
                }
            }
			//翻牌活动外卖卡牌解封
			if($order_info['order_type']=='shop'){
                $res = D('Flipping_event_awards_log')->where(['order_id'=>$order_info['order_id'],'uid'=>$order_info['uid'],'awards_type'=>0])->save(['status'=>1]);
                fdump_api([$res,D('Flipping_event_awards_log')->getLastSql()],'flipping_event',1);
			}
			
            return array('error_code'=>false,'msg'=>$desc.' ，保存商家收入成功！');
        }
    }
}


    // 保存微信分账信息
    public function save_weixin_profit_sharing($order_info, $pay_order){
       
        $takeFromSytemTake = false; // 用户推广金额由抽成分账
        $sendSpread = true; // 用户推广金额是否分账
        $sendSytemTake = true; // 是否给平台抽成分账（非自有支付不需要给平台抽成分账）


        // 订单id
        if(in_array($order_info['order_type'], ['group', 'group_combine', 'shop', 'mall'])) {
            $order_id = $order_info['real_orderid'];
        }else{
            $order_id = $order_info['order_id'];
        }

        $own_money = 0;// 多次分账 本次分账的总金额
        if($order_info['multi_profit_sharing'] == 1){// 多次分账
            $order_num = M('Weixin_profit_sharing_record')->where(['order_type'=>$order_info['order_type'],'order_id'=>$order_id])->order('order_num desc')->getField('order_num');
            if($order_num){
                $order_num = $order_num + 1;
            }else{
                $order_num = 1;
            }

            $own_money = $order_info['own_money'];
        }

        // 订单可分账金额 微信分账不能超过30%
        $totalMoney = $pay_order['payment_money'] * 0.3;

        $profitSharingMoney = 0; // 记录分账金额

        $pay_config = $pay_order['pay_config'] ? json_decode($pay_order['pay_config'], true) : '';

        $pay_config['is_own'] = (empty($pay_config['is_own']) && $pay_order['is_own']) ? $pay_order['is_own'] : $pay_config['is_own'];

        if($pay_config['is_own'] && !C('config.open_spread_by_merchant')){// 商家自有支付且用户推广佣金由平台出，推广金额不得大于平台抽成，多出的金额不得获得分账
            $takeFromSytemTake = true;
        }elseif(!$pay_config['is_own'] && C('config.open_spread_by_merchant')){// 非自有支付且用户推广佣金由商家出 不需给用户分账
            $sendSpread = false; // 用户推广金额是否分账
        }
        
        if(!$pay_config['is_own']){// 非自有支付 只需给用户分账不给平台分账
            $sendSytemTake = false; // 是否给平台抽成分账（非自有支付不需要给平台抽成分账）
        }

        $receivers = [];
        $level = 0;
        $system_take = $order_info['system_take'];
        if($order_info['uid']){// 瞎看用户推广分佣信息
            if($order_info['order_type']=='shop' && $order_info['order_from']==1){
                $spread_condition['order_type'] ='mall';
            }else{
                $spread_condition['order_type'] =$order_info['order_type'];
            }

            if ($spread_condition['order_type'] == 'meal') {
                $foodshop_order = M("Foodshop_order")->field('order_id')->where(array('real_orderid'=>$order_info['order_id']))->find();
                $spread_condition['order_id'] = $foodshop_order['order_id'];
            }else{
                $spread_condition['order_id'] = $order_info['order_id'] ;
            }

            $spread_condition['status'] = 0;

            // 推广分佣列表
            $now_spread = D('User_spread_list')->where($spread_condition)->select();

            foreach($now_spread as $v){
                $level = $v['level'];
                if(C('config.open_distributor')>0){
                    $res= D('Distributor_agent')->get_effective($v['uid'],1);
                    if($res['error_code']){
                        break;
                    }
                }

                if($v['money'] > 0 && $v['spread_type'] == 0 && $sendSpread){// 保存分账信息 用户获得的分账
                    
                    if($totalMoney < $v['money']){// 不能大于微信最大分账金额
                        continue;
                    }

                    if($takeFromSytemTake && $system_take < $v['money']){// 平台抽成的钱小于用户推广退佣，则用户不会获得推广分佣的金额
                        continue;
                    }

                    // 获得分佣的用户
                    $spread_user = D('User')->get_user($v['uid']);
                    if($pay_order['is_mobile_pay'] == 3){// 小程序
                        $openid = $spread_user['wxapp_openid'];
                    }else{
                        $openid = $spread_user['openid'];
                    }
                    if($spread_user && $openid){// 存在微信openid
                        $system_take -= $v['money']; // 平台抽成减去用户分佣金额
                        $totalMoney -= $v['money'];
                        $receivers[] = [
                            'type' => 'PERSONAL_OPENID',
                            'account' => $openid,
                            'amount' => intval($v['money'] * 100),
                            'description' => '用户消费获得推广佣金',
                        ];
                        $profitSharingMoney += $v['money'];
                    } 
                }

            }
        }

        // 分账信息
        $addData = [
            'mer_id' => $order_info['mer_id'],
            'order_type' => $order_info['order_type'],
            'order_num' => $order_num ?? 1,
            'order_id' => $order_id,
            'third_id' => $order_info['third_id'],
            'orderid' => $order_info['orderid'],
            'percent' => $order_info['percent'],
            'is_multi' => $order_info['multi_profit_sharing'] ?? 0,// 是否多次分账
            'is_complete' => $order_info['is_complete'] ?? 0,// 是否订单完成
            'is_withdraw' => $level == 0 ? 1 : 0,// $level=0直接结算 否则佣金需要不可售后才能结算也要结算后才能分账
            'is_v20' => $pay_order['is_v20']?? 0,
            'create_time' => time(),
        ];

        $system_take = min($system_take, $totalMoney);// 不能大于微信最大分账金额

        if($system_take > 0 && $sendSytemTake){// 平台获得的分账 自有支付
            if($pay_order['is_v20']){
                $account = '';
                switch ($pay_order['env']) {
                    case 'wechat_h5'://微信端
                        $account = $pay_config['pay_weixin_mchid'];
                        break;
                    case 'wechat_mini':
                        $account = $pay_config['pay_wxapp_mchid'];
                        break;
                    case 'iosapp':
                    case 'androidapp':
                        $account = $pay_config['pay_weixinapp_mchid'];
                        break;
                    case 'h5':
                        $account = $pay_config['pay_weixinh5_mchid'];
                        break;
                }
            }else{
                $account = $pay_config['pay_weixin_mchid'];
            }
            $receivers[] = [
                'type' => 'MERCHANT_ID',
                'account' => $account,
                'amount' => intval($system_take * 100),
                'description' => '用户消费获得商家抽成',
            ];
            $profitSharingMoney += $system_take;
        }

        // 分账接收方列表
        $addData['receivers'] = $receivers ? json_encode($receivers) : '';
        $addData['paid_money'] = getFormatNumber($profitSharingMoney);

        // 记录分账记录
        $res = M('Weixin_profit_sharing_record')->add($addData);

        $returnArr = [];
        $returnArr['send_spread'] = $sendSpread;
        $returnArr['is_need'] = 1;// 是否需要分账
        $returnArr['error'] = $res ? 0 : 1;
        $returnArr['merchant_get_money'] = $own_money>0 ? getFormatNumber($own_money - $profitSharingMoney) : getFormatNumber($pay_order['payment_money'] - $profitSharingMoney);
        return $returnArr;
    }


    // 执行微信分账
    public function weixin_profit_sharing($info){
       
        // 分账列表
        $receivers = $info['receivers'] ? json_decode($info['receivers'], true) : [];

        // 支付订单信息
        $payment_money_order = M('Payment_money_order')->where(array('orderid'=>$info['orderid']))->find();
        $pay_config = $payment_money_order['pay_config'] ? json_decode($payment_money_order['pay_config'], true) : '';

        // 查询商家余额记录
        $money_where = [
            'order_id' => $info['order_id'],
            'type' => $info['order_type'],
            'income' => 1
        ];
        $merchant_money = $this->where($money_where)->order('id DESC')->find();

        import('@.ORG.pay.Weixin');
        $pay_class = new Weixin($info, 0, 'weixin', $pay_config, [], 3);
        if($receivers){// 单次分账
            // 添加分账接收方关系
            $result = $pay_class->profitsharingaddreceiver();

                    fdump($info,'AMOUNT_OVERDUE',1);
                    fdump($result,'AMOUNT_OVERDUE',1); 
            if(!$result['error']){

                if($info['is_multi']){// 多次分账
                    $go_pay_param = $pay_class->multiprofitsharing();

                }else{// 单次分账
                    $go_pay_param = $pay_class->profitsharing();
                }

                    fdump($go_pay_param,'AMOUNT_OVERDUE',1);
                if(!$go_pay_param['error']){
                    foreach($receivers as $value){
                        if($value['type'] == 'PERSONAL_OPENID'){ // 分账成功后扣除用户余额
                            $nowUser = D('User')->get_user($value['account'], 'openid');
                            if (empty($nowUser)) {
                                $nowUser = D('User')->get_user($value['account'], 'wxapp_openid');
                            }
                            D('User')->user_money($nowUser['uid'],$value['amount']/100,L_("微信分账扣除余额")); 
                        }
                    }

                    // 更新描述
                    if(strpos( $merchant_money['desc'],$go_pay_param['data']['order_id']) === false){ 
                        $desc = $merchant_money['desc'].'分账单号:'.$go_pay_param['data']['order_id'];
                        $this->where(['id'=>$merchant_money['id']])->data(['desc' => $desc])->save();
                    }
                   

                    if($info['is_multi'] && $info['is_complete']){// 多次分账左后一次需要调用完成订单
                        $go_pay_param = $pay_class->profitsharingfinish();
                    }
                    
                }else{
                     if($go_pay_param['err_code'] == 'AMOUNT_OVERDUE'){// 分账金额超出最大分账比例 
                        $amount = 0;
                        foreach($receivers as $value){
                            $amount += $value['amount'];
                        }

                        $desc_pay_for_system = L_("对商家自有支付抽成，在线自有支付支付金额：X1，扣除商家余额：X2",array("X1" => $payment_money_order['paid_money'],"X2" => $amount/100)); 

                        $result_pay = $this->use_money($info['mer_id'],$amount/100, $info['order_type'], $desc_pay_for_system,$info['order_id'],$info['percent'],$amount/100,array(),$merchant_money['store_id']);

                        M('Weixin_profit_sharing_record')->where(['id' => $info['id']])->data(['receivers' => '','paid_money'=>0])->save();
                    }
                }
            }else{
               
                return $result;
            }
        }else{// 完成分账
            $go_pay_param = $pay_class->profitsharingfinish();

            if(!$go_pay_param['error']){
                // 更新描述
                $desc = $merchant_money['desc'].'分账单号:'.$go_pay_param['data']['order_id'];
                $this->where($money_where)->data(['desc' => $desc])->save();
            }
        }
        return $go_pay_param;
    }

    public function merchant_card($mer_id,$order_info){
        $card = D('Card_new')->get_card_by_mer_id($mer_id);
        if($card){
            $uid = $order_info['uid'];
            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid,$mer_id);
            if(empty($card_info)&&$card['auto_get_buy']){
                // 非会员自动领卡(商家开启自动领卡功能)
                $res = D('Card_new')->auto_get($uid,$mer_id);
            }
            
            if (empty($card_info)) { //粉丝购买自动派券功能 自动领卡
                $fan = M('Merchant_user_relation')->join('as c left join '.C('DB_PREFIX').'user u ON c.openid = u.openid')->field('u.uid')->where(array('u.uid'=>$uid))->find();
            }

            if( $card_info || $fan ){ // 会员和粉丝 购买自动派券功能
                // 商家会员卡信息
                $mer_card_info = D('Card_new')->where(array('mer_id'=>$mer_id))->find();

                if ($mer_card_info['weixin_send'] && $order_info['pay_type']=='weixin' && $order_info['payment_money'] >= $mer_card_info['weixin_send_money']) {
                    //购买自动派券功能（派发的都是用户可以领取的功能）
                    $coupon_list = explode(',',$mer_card_info['weixin_send_couponlist']);

                    $model = D('Card_new_coupon');
                    foreach ($coupon_list as $item) {
                        $tmp = $model->had_pull($item,$uid);
                        switch($tmp['error_code']) {
                            case '0':
                                $error_msg = L_('领取成功');
                                break;
                            case '1':
                                $error_msg = L_('领取发生错误');
                                break;
                            case '2':
                                $error_msg = L_('优惠券已过期');
                                break;
                            case '3':
                                $error_msg = L_('优惠券已经领完了');
                                break;
                            case '4':
                                $error_msg = L_('只允许新用户领取');
                                break;
                            case '5':
                                $error_msg = L_('不能再领取了');
                                break;
                        }
                        $tmp['msg'] =L_('微信购买派发，').$error_msg;
                        $data['uid'] = $uid;
                        $data['mer_id']  = $mer_id;
                        $data['coupon_id'] = $item;
                        $data['error_code']  =$tmp['error_code'];
                        $data['msg']  =$tmp['msg'];
                        $data['add_time']  =time();
                        M('Card_coupon_send_history')->add($data);
                    }

                    // 购买自动派券 粉丝自动领卡
                    if ($fan) {
                        $res = D('Card_new')->auto_get($uid,$mer_id,'',true);
                    }
                }
            }

            if(!empty($card_info)){
                $data_score['card_id'] = $card_info['id'] ;
                $data_score['type'] = 1;
                if($order_info['order_type']=='group' && !$order_info['verify_all'] ){
                    $data_score['score_add'] = round($card_info['support_score']*($order_info['total_money']+$order_info['balance_pay']+$order_info['merchant_balance']+$order_info['card_give_money']));
                }else{
                    $data_score['score_add'] = round($card_info['support_score']*($order_info['payment_money']+$order_info['balance_pay']+$order_info['merchant_balance']+$order_info['card_give_money']));
                }
                if( $data_score['score_add']>0){
                    $data_score['desc'] = L_('消费获得会员卡积分');
                    $res = D('Card_new')->add_user_money($mer_id,$uid,0,0,$data_score['score_add'],'',$data_score['desc']);
                }
            }
        }

    }

    public function get_alias_c_name()
    {
        return array(
            'all'            => L_('选择分类'),
            'group'          => C('config.group_alias_name'),
            'shop'           => C('config.shop_alias_name'),
            'mall'           => C('config.mall_alias_name'),
            'meal'           => C('config.meal_alias_name'),
            'appoint'        => C('config.appoint_alias_name'),
            'waimai'         => L_('外卖'),
            'dining'         => L_('餐饮'),
            'store'          => C('config.cash_alias_name'),
            'cash'           => L_('到店支付'),
            'weidian'        => L_('微店'),
            'wxapp'          => L_('营销'),
            'withdraw'       => L_('提现'),
            'coupon'         => L_('优惠券'),
            'activity'       => L_('平台活动'),
            'spread'         => L_('商家推广'),
            'sub_card'       => L_('免单套餐'),
            'score_pay_back' => C('config.score_name') . L_('返现'),
            'gift'           => C('config.score_name') . L_('商城'),
            'village_group'  => C('config.village_group_alias'),
            'award'          => L_('奖励金'),
            'integral_mall'  => L_('商家积分商城'),
            'auth'           => L_('商家权限'),
            'scenic'         => C('config.life_tools_scenic_alias_name'),
            'sports'         => C('config.life_tools_sports_alias_name'),
        );
    }

    //减少余额
    //增加$extra数组参数，后来的同事请不要在额外增加其他参数了，全部在extra里进行拓展
    //extra.platform_get: 第三方分账系统平台抽成扣除分账金额。单位:默认为元
    //extra.merchant_get: 第三方分账系统商家扣除分账金额。单位:默认为元

    public function use_money($mer_id,$money,$type,$desc,$order_id,$percent=0,$system_take = 0,$mer_user=array(),$store_id=0,$extra = []){
        $date['mer_id']=$mer_id;
        $date['income'] = 2;
        $date['order_id'] = $order_id;
        if($store_id>0){
            $date['store_id'] = $store_id;
        } else if (in_array($type, ['shop', 'mall'])) {
            $storeId = M('Shop_order')->where(['real_orderid' => $order_id])->getField('store_id');
            $storeId > 0 && $date['store_id'] = $storeId;
        }

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
        if(M('Merchant')->where(array('mer_id'=>$mer_id))->setDec('money', $date['money']) === false){
            return array('error_code'=>true,'msg'=>$desc.L_('，使用失败！'));
        }
        $now_mer_money = M('Merchant')->field('money,mch_owe_money')->where(array('mer_id'=> $mer_id))->find();
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

        if (isset($extra['platform_get'])) {
            $date['platform_get'] = $extra['platform_get'];
        }
        if (isset($extra['merchant_get'])) {
            $date['merchant_get'] = $extra['merchant_get'];
        }

        if(!$this->add($date)){
            return array('error_code'=>true,'msg'=>$desc.'，'.L_('保存失败！'));
        }else{
            if($type=='shop'){
                //集单返券退券
                $model = '\app\shop\model\service\rebate\RebateService/refundRebateUser';
                $params = ['real_orderid'=>$order_id,'order_type'=>$type];
                invoke_v20_service($model, $params);
            }
            
			$now_merchant = D('Merchant')->get_info($mer_id);
			if(C('config.nmgzhcs_appid') && $now_merchant['nmgzhcs_appid']){
				$this->nmgzhcs_money($now_merchant['nmgzhcs_appid'],$date['order_id'],'-'.$money,$desc);
			}
            if ($type == 'appoint') {
                D('Appoint_order')->returnGiveUserCoupon($order_id);
            }
            return array('error_code'=>false,'msg'=>$desc.'，'.L_('保存成功！'));
        }
    }

    //提现
    public function withdraw($mer_id,$name,$money,$remark){
        $date['mer_id']=$mer_id;
        $withdraw_money = $money;
        fdump_api(['提现金额1' => [$mer_id,$name,$money,$remark]], 'withdraw/merchant', 1);
        if (C('config.company_pay_mer_percent') > 0 && C('config.merchant_withdraw_fee_type')==0) {
            $tmp_money  = $money;
            $withdraw_money = bcdiv(bcmul($tmp_money , (100-C('config.company_pay_mer_percent')),6),'100');
            $system_take = bcdiv(($tmp_money - $withdraw_money),'100', 2);
        }else if(C('config.merchant_withdraw_fee_type')==1&& C('config.company_pay_mer_money')>0){
           // $data_companypay['desc'] .= '|手续费 '.C('config.company_pay_mer_money').C('config.Currency_txt');
            $tmp_money  = $money;
            $withdraw_money = bcsub($tmp_money, bcmul(C('config.company_pay_mer_money'), '100', 2), 2);
            //$date['percent'] = C('config.company_pay_mer_percent');
            $system_take = C('config.company_pay_mer_money');
        }
        $withdraw_money = bcmul($withdraw_money, 1);//取整 以分存储
        if($withdraw_money<=0){
            return array('error_code'=>true,'msg'=>L_('您的提现金额不足以抵扣提现手续费，不能提现'));
        }

        $date['name']=$name;
        $date['money']=  $withdraw_money;
        $date['old_money']=  $money;
        $date['remark']=  empty($remark)?"":$remark;
        $date['withdraw_time'] = time();
        $date['withdraw_type'] = 'wx';
        fdump_api(['提现金额2' => $date], 'withdraw/merchant', 1);
        $res =M('Merchant_withdraw')->add($date);
        fdump_api(['提现金额3' => M('Merchant_withdraw')->getLastSql()], 'withdraw/merchant', 1);
        $this->withdraw_notice($res);
        if(!$res){
            return array('error_code'=>true,'msg'=>L_('保存失败！'));
        }else{
            $date['id'] = $res;
            //考虑兑现后减值
            $this->use_money($mer_id,$money/100,'withdraw',L_('商家提现减少金额'),$res,C('config.company_pay_mer_percent'),$system_take);
            return array('error_code'=>false,'msg'=>L_('保存成功！'),'data' => $date);
        }
    }

    // 提现到平台余额
    public function withdraw_system_money($mer_id,$name,$money,$remark,$user){
        // 提现到平台余额不收手续费 不需审核
        if (empty($user)) {
            return array('error_code'=>true,'msg'=>L_('商家未绑定管理账号'));
        }

        $data_companypay['money'] = $money*100;
        $data_companypay['old_money'] = $money * 100; 
        $data_companypay['desc'] = "商户提现对账订单|商户ID " . $mer_id . " |转账 " . $money . C('config.Currency_txt') . "";

        $now_merchant = M('Merchant')->where(array('mer_id'=>$mer_id))->find();
        //商家提现记录
        $date_mer['mer_id'] = $mer_id;
        $date_mer['name'] = $name;
        $date_mer['money'] = $data_companypay['money'];
        $date_mer['old_money'] = $data_companypay['old_money'];
        $date_mer['remark'] = $data_companypay['desc'] . '|' . $remark;
        $date_mer['withdraw_time'] = time();
        $date_mer['status'] = 1;
        $date_mer['withdraw_type'] = 'balance';
        $res = M('Merchant_withdraw')->add($date_mer);
        if (!$res) {
            return array('error_code'=>true,'msg'=>L_('申请失败！'));
        }

        $data_companypay['type'] = 'mer';
        $data_companypay['pay_type'] = 3;//0 银行卡，1 支付宝 2微信 3平台余额
        $data_companypay['pay_id'] = $now_merchant['mer_id'];
        $data_companypay['name'] = $now_merchant['name'];
        $data_companypay['truename'] = $name;
        $data_companypay['phone'] = $now_merchant['phone'];
        $data_companypay['money'] = $money*100;
        $data_companypay['old_money'] = $money * 100; 
        $data_companypay['status'] = 3;
        $data_companypay['add_time'] = time();
        $data_companypay['pay_time'] = time();
        $data_companypay['withdraw_id'] = $res;
        $withdraw_id = M('Withdraw_list')->add($data_companypay);

        // 减少商家余额
        $result = D('Merchant_money_list')->use_money($mer_id, $money, 'withdraw', $data_companypay['desc'] . '|' . $remark, $data_companypay['withdraw_id']);
        if (!$result['error_code']) {
            $desc = '商家提现，用户增加余额';
            $result = D('User')->add_money($user['uid'], $money, $desc);
            if (!$result['error_code']) {
                return array('error_code'=>false,'msg'=>L_('提现成功！'));
            }else{
                return array('error_code'=>true,'msg'=>L_('提现失败，请联系管理员！'));
            }
        }else{
            return array('error_code'=>true,'msg'=>L_('提现失败，请联系管理员！'));
        }
    }

    //拒绝提现 增加余额
    public function reject($mer_id,$withdraw_id,$reason){
        $res = M('Merchant_withdraw')->where(array('id'=>$withdraw_id,'mer_id'=>$mer_id))->find();
        if($res['status'] == 2){
            return false;
        }
        $date['status'] = 2;
        $date['remark'] = $reason;
        M('Merchant_withdraw')->where(array('id'=>$withdraw_id,'mer_id'=>$mer_id))->save($date);
        $desc = L_('驳回商家提现增加金额');
        $order_info['money'] = $res['old_money']/100;
        $order_info['order_type'] = 'withdraw';
        $order_info['mer_id'] = $mer_id;
        $order_info['order_id'] = $withdraw_id;
        $order_info['desc'] = $desc;
        $this->withdraw_status_notice($withdraw_id);
        if($res['status']==4){
            M('Withdraw_list')->where(array('withdraw_id'=>$withdraw_id,'type'=>'mer','pay_id'=>$mer_id))->setField('status',4);
        }
        return $this->add_money($order_info, 'reject');
    }

    //同意提现改变状态
    public function agree($mer_id,$money,$withdraw_id,$remark,$is_online=false){
        $date['status'] = 1;
        $date['remark'] = $remark;
        $date['online'] = $is_online;
        $date['money'] = $money;
        $res = M('Merchant_withdraw')->where(array('id'=>$withdraw_id,'mer_id'=>$mer_id))->find();
        if(in_array($res['status'],[4,10])){
            M('Withdraw_list')->where(array('withdraw_id'=>$withdraw_id,'type'=>'mer','pay_id'=>$mer_id))->setField('status',1);
        }
        $res = M('Merchant_withdraw')->where(array('id'=>$withdraw_id,'mer_id'=>$mer_id))->save($date);
        $this->withdraw_status_notice($withdraw_id);
        return $res;
    }

    //统计所有商家余额
    public function  get_all_mer_money($where){

        if(!empty($where)){
            return M('Merchant')->where($where)->sum('money');
        }
        return M('Merchant')->sum('money');
    }

    //获取提现列表
    public function get_withdraw_list($mer_id,$is_system = 0,$status=3,$time=''){
        if($is_system){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        $where['mer_id']= $mer_id;
        if($status!=3){
            $where['status']= $status;
        }
        if($status==0){
            $where['status'] = array('in','0,4,10,20');
        }
        if(!empty($time)){
            $where['_string'] = $time;
        }

        $count = M('Merchant_withdraw')->where($where)->count();
        $p = new Page($count, 20);
        $pagebar=$p->show();
        if($_GET['page']>$p->totalPage){
            return array('withdraw_list'=>array(),'page_num'=>$p->totalPage);
        }else{
            return array('withdraw_list'=>M('Merchant_withdraw')->where($where)->order('withdraw_time DESC')->limit($p->firstRow,$p->listRows)->select(),'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
        }

    }

    //获取商家收入列表
    public function get_income_list($mer_id,$is_system = 0,$where){
        if($_GET['page']){
            $where = $_SESSION['condition'];
        }

        if($is_system){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        unset($where['mer_id']);
        $where['l.mer_id']=array('in',(string)$mer_id);
        if($where['store_id']){
            $where['l.store_id'] = $where['store_id'];
            unset($where['store_id']);
        }
        if($where['type'] && !in_array($where['type'],['scenic','sports'])){
            $where['l.type'] = $where['type'];
            unset($where['type']);
        }
        if(in_array($where['type'],['scenic','sports'])){
            $where['l.type'] = 'lifetools';
            $where['lt.type'] = $where['type']=='scenic' ? 'scenic' : array('in','stadium,course');
            unset($where['type']);
        }
        $add_join = ' left join '.C('DB_PREFIX') . 'life_tools_order lto ON (lto.order_id = l.order_id or lto.real_orderid = l.order_id) left join ' . C('DB_PREFIX') . 'life_tools lt ON lt.tools_id = lto.tools_id';
        $total_back_price = 0;
        $total_back_order_money = 0;
        if(C('customization.cash_back')){
            $total_back_price = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where($where)->order('use_time DESC')->sum('back_price');
            //优惠买单返还金额原订单金额
            $total_back_order_money = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where($where)->where(['back_price'=>['neq', 0]])->order('use_time DESC')->sum('total_money');
        }
        $count = $income_count= M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($where)->count();
        $p = new Page($count, 20);
        $pagebar=$p->show();
        if($_GET['page']>$p->totalPage){
            return array('income_list'=>array(),'total'=>0,'page_num'=>$p->totalPage);
        }else {
            $income_list = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->field('ms.name as store_name,l.order_id,l.desc,l.use_time,l.num,l.money,l.type,l.id,l.income,l.now_mer_money,l.system_take,l.percent,l.store_id,l.score,l.score_count,l.total_money,l.pay_money,l.back_rate,l.back_price,l.back_original_price,lt.type as life_tools_type')->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();

            $income_sum= M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($where)->sum('l.money');

            //增加第三方分账的统计，目前仅记录了拉卡拉支付
            $third_pay_where = array_merge($where, array('l.mer_id' => $mer_id, 'l.type' => array('NOT IN', array('withdraw'))));
            $third_pay_income = M('Merchant_money_list')->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join ' . C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id ' . $add_join)
                ->where($third_pay_where)
                ->field("SUM(IF(l.income = 1,l.platform_get,l.platform_get*-1)) AS third_pay_platform_get,SUM(IF(l.income=1,l.merchant_get,l.merchant_get*-1)) AS third_pay_merchant_get")
                ->find();

            $total_where = array_merge($where,array('l.mer_id'=>$mer_id,'l.income'=>1,'l.type'=>array('NOT IN',array('withdraw'))));
            $total_income = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($total_where)->sum('l.money');
            $total_money_where = array_merge($where,array('l.mer_id'=>$mer_id));
            $total_money = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($total_money_where)->sum('pow(-1,l.income+1)*l.money');
            $total_money  = floatval(getFormatNumber(key($total_money)));
            $total_order_money_where =  array_merge($where,array('l.mer_id'=>$mer_id,'l.income'=>1,'l.type'=>array('NOT IN',array('merrecharge','withdraw'))));
            $total_order_money = M('Merchant_money_list')->field('SUM( CASE WHEN l.type = "group" THEN go.total_money ELSE l.total_money END ) AS total_money')
                ->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id ')
                ->join('left join '.C('DB_PREFIX') . 'group_order go ON go.real_orderid = l.order_id AND l.type = "group" '.$add_join)
                ->where($total_order_money_where)
                ->find();
            $total_order_money = $total_order_money['total_money'] ?? 0;
            $refund_where = array_merge($where,array('l.mer_id'=>$mer_id,'l.income'=>2,'l.type'=>array('NOT IN',array('withdraw'))));
            $total_refund = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($refund_where)->sum('l.money');

            $income_total_where = array_merge($where,array('l.mer_id'=>$mer_id,'l.income'=>1,'l.type'=>array('NOT IN',array('merrecharge','withdraw'))));
            $income_total = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($income_total_where)->sum('l.money');
            $income_total_where_ = array_merge($where,array('l.mer_id'=>$mer_id,'l.type'=>array('neq','withdraw')));
            $income_total_ = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($income_total_where_)->sum('l.money');
            $expend_total_where = array_merge($where, array('l.mer_id' => $mer_id, 'l.income' => 2));
            $expend_total = M('Merchant_money_list')->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join ' . C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id '.$add_join)->where($expend_total_where)->sum('l.money');
            $recharge_total = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where(array('l.mer_id'=>$mer_id,'l.income'=>1,'l.type'=>'merrecharge'))->sum('l.money');
            $total_score = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where($where)->order('use_time DESC')->sum('l.score');
            $system_take = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where($where)->order('use_time DESC')->sum('l.system_take');
            $where['is_spread_user_buy'] = 1;
            $system_no_take = M('Merchant_money_list')->join('as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id '.$add_join)->where($where)->order('use_time DESC')->sum('l.money');
            return array('income_list' => $income_list,
                'total'=>empty($total_income)?0:$total_income,
                'expend_total' => empty($expend_total) ? 0 : $expend_total,
                'income_total'=>empty($income_total)?0:$income_total,
                'income_total_'=>empty($income_total_)?0:$income_total_,
                'total_money'=>empty($total_money)?0:$total_money,
                'total_score'=>empty($total_score)?0:$total_score,
                'recharge_total'=>empty($recharge_total)?0:$recharge_total,
                'income_count' => $count,
                'income_sum' => $income_sum,
                'system_take' => empty($system_take)?0:$system_take,
                'system_no_take' => empty($system_no_take) ? 0 : $system_no_take,
                'pagebar' => $pagebar,
                'page_num' => $p->totalPage,
                'total_back_price' => $total_back_price ?: 0,
                'total_order_money' => $total_order_money ?: 0,
                'total_back_order_money' => $total_back_order_money ?: 0,
                'total_refund' => $total_refund ?: 0,
                'third_pay_platform_get' => $third_pay_income['third_pay_platform_get'] ?? 0,
                'third_pay_merchant_get' => $third_pay_income['third_pay_merchant_get'] ?? 0,
            );
        }
    }

    //获取所有商家提现列表 有提现的向前排列
    public function get_mer_withdraw_list($condition_merchant,$page_count=15){
        $database_merchant = M('Merchant');
        import('@.ORG.system_page');

        // $count_merchant =M('Merchant_withdraw')->where(array('status'=>array('in','0,4')))->count();
        $condition_merchant['w.status'] = array('in','0,4,10,20');
        $count_merchant =$database_merchant->join('as m left join '.'(SELECT  mer_id,SUM(money) AS  withdraw_money,withdraw_time,status  FROM pigcms_merchant_withdraw WHERE status in (0,4,10,20)  GROUP BY mer_id) w ON m.mer_id = w.mer_id ')
            ->field('m.mer_id,m.phone,m.name,m.money,w.withdraw_time,w.withdraw_money ')
            ->where($condition_merchant)->count();
        $p = new Page($count_merchant,$page_count);
        $mer_withdraw_list = $database_merchant->join('as m left join '.'(SELECT  id,mer_id,SUM(money) AS  withdraw_money,withdraw_time,status  FROM pigcms_merchant_withdraw WHERE status in (0,4,10,20)  GROUP BY mer_id) w ON m.mer_id = w.mer_id ')
            ->field('m.mer_id,m.phone,m.name,m.money,w.withdraw_time,w.withdraw_money,wl.account as bank,wl.remark as bank_detail')
            ->join('LEFT JOIN '.C('DB_PREFIX').'withdraw_list wl ON wl.withdraw_id = w.id')
            ->where($condition_merchant)
            ->order('w.withdraw_time DESC')
            ->group('m.mer_id')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();

        $pagebar = $p->show();
        return array('mer_withdraw_list'=>$mer_withdraw_list,'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
    }

    public function get_money_list($condition_merchant,$page_count=15){
        $database_merchant = M('Merchant');
        import('@.ORG.system_page');
       // if(isset($condition_merchant)){

            if($condition_merchant['province_id']){
                $province_id = $condition_merchant['province_id'];
                $where['a.area_pid'] = $condition_merchant['province_id'];
                unset($condition_merchant['province_id']);
                $where = array_merge($where,$condition_merchant);
                $count_merchant =M("Merchant" )->join("as m left join ".C('DB_PREFIX').'area as a ON m.city_id = a.area_id')->where($where)->count();
            }else{
                $count_merchant =M("Merchant as m" )->where($condition_merchant)->count();
            }

            $p = new Page($count_merchant,$page_count);
            if($province_id){
                $mer_withdraw_list = $database_merchant->join('as m left join '.'(SELECT  mer_id,SUM(money) AS  withdraw_money,withdraw_time,status as withdraw_status FROM pigcms_merchant_withdraw WHERE status in (0,4)  GROUP BY mer_id) w ON m.mer_id = w.mer_id left join '.C('DB_PREFIX').'area as a on a.area_id = m.city_id')
                    ->field('m.mer_id,m.phone,m.name,m.money,w.withdraw_time,w.withdraw_money ')
                    ->where($where)
                    ->order('m.money DESC')
                    ->group('m.mer_id')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
            }else{
                $mer_withdraw_list = $database_merchant->join('as m left join '.'(SELECT  mer_id,SUM(money) AS  withdraw_money,withdraw_time,status as withdraw_status FROM pigcms_merchant_withdraw WHERE status in (0,4)  GROUP BY mer_id) w ON m.mer_id = w.mer_id ')
                    ->field('m.mer_id,m.phone,m.name,m.money,w.withdraw_time,w.withdraw_money ')
                    ->where($condition_merchant)
                    ->order('m.money DESC')
                    ->group('m.mer_id')
                    ->limit($p->firstRow.','.$p->listRows)
                    ->select();
            }

        //}else{
        //
        //    $count_merchant = $database_merchant->where($condition_merchant)->count();
        //    $p = new Page($count_merchant,$page_count);
        //    $mer_withdraw_list = $database_merchant->join('as m left join '.'(SELECT  mer_id,SUM(money) AS  withdraw_money,withdraw_time,status as withdraw_status FROM pigcms_merchant_withdraw WHERE status =0  GROUP BY mer_id) w ON m.mer_id = w.mer_id ')
        //        ->field('m.mer_id,m.phone,m.name,m.money,w.withdraw_time,w.withdraw_money')
        //        ->where($condition_merchant)
        //        ->order('m.money DESC')
        //        ->limit($p->firstRow.','.$p->listRows)
        //        ->select();
        //}
        $pagebar = $p->show();
        return array('mer_withdraw_list'=>$mer_withdraw_list,'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
    }


    //抽成列表
    public function get_mer_percentage_list($condition_merchant,$page_count=15){
        $database_merchant = M('Merchant');
        $condition_merchant['province_id'] && $where['province_id'] = $condition_merchant['province_id'];
        $condition_merchant['city_id'] && $where['city_id'] = $condition_merchant['city_id'];
        $condition_merchant['area_id'] && $where['area_id'] = $condition_merchant['area_id'];
        $condition_merchant['street_id'] && $where['street_id'] = $condition_merchant['street_id'];
        $where['status'] = 1;
        $count_merchant = $database_merchant->where($where)->count();
        $time_condition = '';
        foreach($condition_merchant as $k=>$v){
            if($k!='_string'){
                $condition_merchant['m.'.$k] = $v;
            }else{
                $time_condition = 'WHERE '.$condition_merchant[$k];
            }
            unset($condition_merchant[$k]);
        }
        $extra_str ='';
        $extra_field ='';
        if(C('config.open_extra_price')==1){
            $tmp_time_condition  =  str_replace('use_time','add_time', $time_condition);
            $extra_str= 'LEFT JOIN (SELECT mer_id,SUM(score_count) as send_count FROM  '.C('DB_PREFIX').'merchant_score_send_log '.$tmp_time_condition.'  group by mer_id ) sl ON sl.mer_id = m.mer_id ';
            $extra_field = ' ,sl.send_count';
        }

        import('@.ORG.system_page');
        $p = new Page($count_merchant,$page_count);
        $mer_percentage_list = $database_merchant->join('as m left join '.'(SELECT  mer_id,SUM(system_take) AS  money ,SUM(score) as all_score FROM '.C('DB_PREFIX').'merchant_money_list '.$time_condition.' GROUP BY mer_id) w ON m.mer_id = w.mer_id  '.$extra_str)
            ->field('m.mer_id,m.money as now_money,m.name,m.phone,w.money,w.all_score'.$extra_field)
            ->where($condition_merchant)
            ->order('w.money DESC,w.all_score DESC')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();

        $pagebar = $p->show();
        return array('mer_percentage_list'=>$mer_percentage_list,'pagebar'=>$pagebar,'page_num'=>$p->totalPage);
    }

    //统计所有商家余额
    public function  get_all_percent_money($condition_merchant){
        foreach($condition_merchant as $k=>$v){
            if($k!='_string'){
                $condition_merchant['m.'.$k] = $v;
                unset($condition_merchant[$k]);
            }
        }
        return M('Merchant_money_list')->join('AS l left join '.C('DB_PREFIX') .'merchant as m ON m.mer_id = l.mer_id')->where($condition_merchant)->sum('l.system_take');
    }

    //统计所有送出的积分
    public function  get_all_score($condition_merchant){
        $all_socre=  0;
        if(C('config.open_extra_price')==1){
            $tmp_condition = $condition_merchant;
            foreach($tmp_condition as $k=>$v){
                if($k!='_string'){
                    $tmp_condition['m.'.$k] = $v;
                    unset($tmp_condition[$k]);
                }else{
                    $tmp_condition[$k] = str_replace('use_time','add_time', $tmp_condition[$k]);
                }
            }
            $all_socre = M('Merchant')->join('as m LEFT JOIN '.C('DB_PREFIX').'merchant_score_send_log sl ON sl.mer_id = m.mer_id')->where($tmp_condition)->sum('score_count');
        }
        foreach($condition_merchant as $k=>$v){
            if($k!='_string'){
                $condition_merchant['m.'.$k] = $v;
                unset($condition_merchant[$k]);
            }
        }
        $all_socre += M('Merchant_money_list')->join('AS l left join '.C('DB_PREFIX') .'merchant as m ON m.mer_id = l.mer_id')->where($condition_merchant)->sum('l.score');
        return $all_socre;
    }

    public  function get_system_income($condition){
        $condition['income']=1;
        $condition['type']=array('neq','merrecharge');

        $income_total = M('Merchant_money_list')
            ->where($condition)
            ->sum('system_take');

        return $income_total;
    }

    /**
     * @param $mall_type 默认$mall_type =1保证不影响原来的业务,$mall_type = 2新版商城3.0佣金任务
     * @param $not_add_money 是否增加余额 (微信分账直接打到用户微信余额不需增加平台余额)
     * @return bool
     */
       public function spread_check($uid,$id,$mall_type = 1){
        if(C('config.open_extra_price')==1){
            $money_name = C('config.extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }
        $where = array(
            'pigcms_id'=>$id,
            '_string'=>'uid='.$uid.' OR change_uid='.$uid
        );

        $now_spread = D('User_spread_list')->where($where)->find();

        if($now_spread && $now_spread['status'] == 0){
            if($now_spread['order_type'] == 'group'){
                $order_info = D('Group_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                if($order_info['status'] == '1' || $order_info['status'] == '2'){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                        //D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得佣金');
                        if($now_spread['change_uid']!=0){
                           // D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得'.$money_name.'('.$money_name.'过户)');
                            if ($now_spread['spread_type'] == 1) {
                                // 获得积分
                                D('User')->add_score($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => C('config.score_name'),"X2" => C('config.score_name')))); 
                            }else{

                                D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                            
                                // 是否开启推广佣由金商家支付
                                if (C('config.open_spread_by_merchant')==1) {
                                    $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order_info['order_id']); 
                                }
                            }
                        }else{
                            if(C('config.open_extra_price')==1){
                               // D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得'.$money_name);
                                D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                            }else{
                                if ($now_spread['spread_type'] == 1) {
                                    // 获得积分
                                    D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => C('config.score_name')))); 
                                }else{
                                   // D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得'.$money_name);
                                    D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                            
                                    // 是否开启推广佣由金商家支付
                                    if (C('config.open_spread_by_merchant')==1) {
                                        $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order_info['order_id']); 
                                    }

                                    D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
                                }
                            }
                        }
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info['status'] == '3' || $order_info['status'] == '6'){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else if ($now_spread['order_type']=='shop' || ($now_spread['order_type']=='mall' && $mall_type == 1)){
                $order_info = D('Shop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                if($order_info['status'] == '1' || $order_info['status'] == '2' || $order_info['status'] == '3'){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                        //D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.shop_alias_name').'商品获得佣金');

                        if($now_spread['change_uid']!=0){
                            D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                        
                            // 是否开启推广佣由金商家支付
                            if (C('config.open_spread_by_merchant')==1) {
                                $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order_info['order_id']); 
                            }
                           // D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.C('config.shop_alias_name').'商品获得'.$money_name.'('.$money_name.'过户)');
                        }else{
                            if(C('config.open_extra_price')==1){
                                D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                               // D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.shop_alias_name').'商品获得'.$money_name);
                            }else{
                                D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                               // D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.shop_alias_name').'商品获得'.$money_name);
                        
                                // 是否开启推广佣由金商家支付
                                if (C('config.open_spread_by_merchant')==1) {
                                    $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order_info['order_id']); 
                                }
                                D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
                            }
                        }
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info['status'] == '4'||$order_info['status'] == '5'){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else if ($now_spread['order_type']=='store'||$now_spread['order_type']=='cash'){
                $order_info = D('Store_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

                $alia_name = array('store'=>L_('优惠买单'),'cash'=>L_('到店付'));
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                    if($now_spread['change_uid']!=0){
                        D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                       // D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name.'('.$money_name.'过户)');
                        
                        // 是否开启推广佣由金商家支付
                        if (C('config.open_spread_by_merchant')==1) {
                            $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order_info['order_id']); 
                        }
                    }else{
                        if(C('config.open_extra_price')==1){
                            D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                           // D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name);
                        }else{

                            D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                           // D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.$alia_name[$now_spread['order_type']].'商品获得'.$money_name);
                        
                            // 是否开启推广佣由金商家支付
                            if (C('config.open_spread_by_merchant')==1) {
                                $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order_info['order_id']); 
                            }

                            D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
                        }
                    }
                    return true;
                }else{
                    return false;
                }

            }else if ($now_spread['order_type']=='meal'){
                $order_info = D('Foodshop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

                $alia_name = array('store'=>C('config.meal_alias_name'));
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                    //D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
                    if($now_spread['change_uid']!=0){
                        D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                       // D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买餐饮商品获得'.$money_name.'('.$money_name.'过户)');
                        
                        // 是否开启推广佣由金商家支付
                        if (C('config.open_spread_by_merchant')==1) {
                            $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order_info['order_id']); 
                        }
                    }else{
                        if(C('config.open_extra_price')==1){
                            D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                           // D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.meal_alias_name').'商品获得'.$money_name);
                        }else{
                            D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                           // D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.meal_alias_name').'商品获得'.$money_name);
                        
                            // 是否开启推广佣由金商家支付
                            if (C('config.open_spread_by_merchant')==1) {
                                $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order_info['order_id']); 
                            }
                            D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
                        }
                    }
                    return true;
                }else{
                    return false;
                }

            }else if ($now_spread['order_type']=='sub_card'){
                $order_info = D('Sub_card_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

                $alia_name = array('sub_card'=>L_('次卡套餐'));
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                    //D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
                    if($now_spread['change_uid']!=0){
                        D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                       // D('User')->add_money($now_spread['change_uid'],$now_spread['money'],'推广用户购买免单套餐获得'.$money_name.'('.$money_name.'过户)');
                        // }
                    }else{

                        D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                       // D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买免单套餐商品获得'.$money_name);
                        D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

                    }
                    return true;
                }else{
                    return false;
                }

            }else if ($now_spread['order_type']=='yuedan'){
                $order_info = D('Yuedan_service_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                $alia_name = array('yuedan'=>L_('约单'));
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                    //D('User')->add_money($now_spread['uid'],$now_spread['money'],'推广用户购买餐饮商品获得佣金');
                    if($now_spread['change_uid']!=0){
                        D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                    }else{

                        D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                        D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

                    }
                    return true;
                }else{
                    return false;
                }

            }else if ($now_spread['order_type']=='mall' && $mall_type == 2){
                $order_info = D('Mall_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                if($order_info['status'] >=10 &&  $order_info['status'] <=49){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1))->save()){
                        if($now_spread['change_uid']!=0){
                            D('User')->add_money($now_spread['change_uid'],$now_spread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name))); 
                        
                            // 是否开启推广佣由金商家支付
                            if (C('config.open_spread_by_merchant')==1) {
                                $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order_info['order_id']); 
                            }
                        }else{
                            if(C('config.open_extra_price')==1){
                                D('User')->add_score($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                            }else{
                                D('User')->add_money($now_spread['uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
                        
                                // 是否开启推广佣由金商家支付
                                if (C('config.open_spread_by_merchant')==1) {
                                    $this->use_money($order_info['mer_id'], $now_spread['money'], $now_spread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order_info['order_id']); 
                                }
                                D('Scroll_msg')->add_msg('spread',$now_spread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
                            }
                        }
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info['status'] >= 50){
                    if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }
        }
    } 


    // 单个商品分销(定制)
    public function distributor_spread_check($uid,$id){
    fdump('distributor_spread_check','add_money',1);
       
        $money_name = L_('佣金');
        
        $where = array(
            'pigcms_id'=>$id,
            'order_uid'=>$uid
        );

        $now_spread = D('Distributor_spread_list')->where($where)->find();

        if($now_spread && $now_spread['status'] == 0){
            if($now_spread['order_type'] == 'group'){
                $order_info = D('Group_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                if($order_info['status'] == '1' || $order_info['status'] == '2'){
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1,'check_time'=>time()))->save()){
                        $this->distributor_spread_give($now_spread,$money_name);
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info['status'] == '3' || $order_info['status'] == '6'){
                    // 退款
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2,'check_time'=>time()))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else if ($now_spread['order_type']=='shop' || $now_spread['order_type']=='mall'){
                $order_info = D('Shop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                if($order_info['status'] == '1' || $order_info['status'] == '2' || $order_info['status'] == '3'){
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1,'check_time'=>time()))->save()){                       
                        $this->distributor_spread_give($now_spread,$money_name);                        
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info['status'] == '4'||$order_info['status'] == '5'){
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2,'check_time'=>time()))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else if ($now_spread['order_type']=='appoint'){
                fdump('appoint','add_money',1);
                $order_info = D('Appoint_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                fdump($order_info,'add_money',1);
                if($order_info['service_status'] == '1'){
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1,'check_time'=>time()))->save()){                       
                        $this->distributor_spread_give($now_spread,$money_name);                        
                        return true;
                    }else{
                        return false;
                    }
                }else if($order_info["is_del"] != 0 || $order_info["paid"] == 3){
                    if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>2,'check_time'=>time()))->save()){
                        return true;
                    }else{
                        return false;
                    }
                }
            }else if ($now_spread['order_type']=='store'||$now_spread['order_type']=='cash'){
                $order_info = D('Store_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();

                $alia_name = array('store'=>L_('优惠买单'),'cash'=>L_('到店付'));
                if(D('Distributor_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1,'check_time'=>time()))->save()){
                    $this->distributor_spread_give($now_spread,$money_name);
                    return true;
                }else{
                    return false;
                }

            }else if ($now_spread['order_type']=='meal'){
                $order_info = D('Foodshop_order')->field(true)->where(array('order_id'=>$now_spread['order_id']))->find();
                $alia_name = array('store'=>C('config.meal_alias_name'));
                if(D('User_spread_list')->where(array('pigcms_id'=>$id))->data(array('status'=>1,'check_time'=>time()))->save()){
                    $this->distributor_spread_give($now_spread,$money_name);
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

    // 单个商品分销(定制)
    public function distributor_spread_give($now_spread=array(),$money_name='')
    {
        // if(C('config.open_extra_price')==1){
        //    // D('User')->add_score($now_spread['uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得'.$money_name);
        //     D('User')->add_score($now_spread['spread_uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name))); 
        // }else{
            if ($now_spread['spread_type'] == 1) {
                // 获得积分
                D('User')->add_score($now_spread['spread_uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => C('config.score_name')))); 
            }else{
               // D('User')->add_money($now_spread['spread_uid'],$now_spread['money'],'推广用户购买'.C('config.group_alias_name').'商品获得'.$money_name);
                D('User')->add_money($now_spread['spread_uid'],$now_spread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name)));

                M('Distributor')->where(array('uid'=>$now_spread['spread_uid']))->setInc('money',$now_spread['money']);
                fdump(M('Distributor')->_sql(),'lll',1);
               
                D('Scroll_msg')->add_msg('distributor_spread',$now_spread['spread_uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($now_spread['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));
            }
        // }
        return true;
    }


    //三级分佣积分返现
    public function spread_check_jifen($order_id=null,$order_type=null){
        if(empty($order_id) || empty($order_type)){
            return false;
        }
        $model=M('spread_jifen_list');
        $where=array();
        $where['order_type']=$order_type;
        $where['order_id']=$order_id;
        $list=$model->where($where)->select();
        if(empty($list)){
            return false;
        }
        $where=array();
        $where['order_id']=$order_id;
        switch ($order_type) {
            case 'group':
                $order_info=D('Group_order')->where($where)->find();
                break;
            case 'shop':
            case 'mall':
                $order_info=D('Shop_order')->where($where)->find();
                break;
            case 'store':
            case 'cash':
                $order_info=D('Store_order')->where($where)->find();                
                break;
            case 'meal':
                $order_info=D('Foodshop_order')->where($where)->find();
                break;
            case 'sub_card':
                $order_info=D('Sub_card_order')->where($where)->find();
                break;
            case 'yuedan':
                $order_info=D('Yuedan_service_order')->where($where)->find();
                break;
        }

        foreach ($list as $key => $value) {
            $id=$value['id'];
            /*信息*/
            $uid=$value['uid'];
            $jifen=$value['jifen'];
            $desc=L_('推广用户购买商品获得').C('config.score_name');

            $data=array();
            $data['id']=$id;
            $data['update_time']=time();
            if($order_type=='group'){                
                if($order_info['status']==1 || $order_info['status']==2){
                    $data['status']=1;
                    $res=$model->data($data)->save();
                    if($res){
                        D('User')->add_score($uid,$jifen,$desc);
                    }
                }elseif($order_info['status']==3 || $order_info['status']==6){
                    $data['status']=2;
                    $model->data($data)->save();
                }
            }elseif ($order_type=='shop' || $order_type=='mall') {
                if($order_info['status']==1 || $order_info['status']==2 || $order_info['status']==3){
                    $data['status']=1;
                    $res=$model->data($data)->save();
                    if($res){
                        D('User')->add_score($uid,$jifen,$desc);                        
                    }
                }elseif($order_info['status']==4 || $order_info['status']==5){
                    $data['status']=2;
                    $model->data($data)->save();
                }
            }elseif ($order_type=='store' || $order_type=='cash') {
                D('User')->add_score($uid,$jifen,$desc);
            }elseif ($order_type=='meal') {
                D('User')->add_score($uid,$jifen,$desc);
            }elseif ($order_type=='sub_card') {
                D('User')->add_score($uid,$jifen,$desc);                
            }elseif ($order_type=='yuedan') {
                D('User')->add_score($uid,$jifen,$desc);
            }
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
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        foreach ($send_to as $s) {
            $href ='';
            $model->sendTempMsg('OPENTM401300510', array('href' => $href,
                'wecha_id' => $s['openid'],
                'first' => L_('欠款通知'),
                'keyword1' =>"商家【{$merchant['name']}】",
                'keyword2' => L_('欠款').$merchant['money'],
                'remark' =>L_('时间X1,您的帐户余额已欠费，请尽快充值',date('Y-m-d H:i',time()))),
            0);
        }
    }

    public function withdraw_notice($withdraw_id = 0){
        $where['w.id'] = $withdraw_id;
        $withdraw = M('Merchant_withdraw')->field('w.*,m.area_id,m.city_id ,m.province_id,m.name as withdraw_name')->join('as w LEFT JOIN '.C('DB_PREFIX').'merchant m ON m.mer_id = w.mer_id ')->where($where)->find();

        $where_admin['openid']=array('neq','');
        $where_admin['withdraw_notice']=1;
        $admin_list = M('Admin')->where($where_admin)->select();
        $tmp_area = array(
            $withdraw['area_id'],$withdraw['city_id'],$withdraw['province_id']
        );
        foreach ($admin_list as $v) {
            if($v['level']==2 || $v['level']==0){
                $send_to[] = $v;
            }else if(in_array($v['area_id'],$tmp_area)){
                $send_to[] = $v;
            }
        }

        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        foreach ($send_to as $s) {
            $href ='';
            $model->sendTempMsg('OPENTM412319702', array('href' => $href,
                'wecha_id' => $s['openid'],
                'first' => L_('有一笔商家提现待审批，请尽快审核！'),
                'keyword1' =>$withdraw['withdraw_name'],
                'keyword2' => date('Y-m-d H:i',$withdraw['withdraw_time']),
                'keyword3' => C('config.Currency_symbol').floatval($withdraw['money']/100),
                'remark' =>L_('请尽快登录后台审核')),
                0);
        }
    }

    public function withdraw_status_notice($withdraw_id = 0,$is_user=0,$is_wexin=false){

        $withdraw_type  = array(
            '0' => L_('银行卡'),
            '1' => L_('支付宝'),
            '2' => L_('微信'),
        );
        $withdraw_status =array(
            0=>L_('未审核'),
            1=>L_('审核通过'),
            2=>L_('驳回'),
            3=>'',
            4=>L_('审核通过'),
        );
        $where['w.id'] = $withdraw_id;
        if($is_user){
            if($is_wexin){
                $withdraw =  M('Companypay')->join('as w LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = w.pay_id')->field('w.*,u.nickname as withdraw_name')->where(array('pigcms_id'=>$withdraw_id))->find();
                if($withdraw['status']==0){
                    $withdraw['status_txt'] = L_('审核不通过');
                }elseif($withdraw['status']==2){
                    $withdraw['status_txt'] = L_('恢复审核');
                }
                $withdraw['pay_type']=2;
            }else{

                $withdraw = M('Withdraw_list')->field('w.*,u.openid,u.nickname as withdraw_name')->join('as w LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = w.pay_id')->where($where)->find();
                if($withdraw['status']==2){
                    $withdraw['status_txt'] = L_('审核不通过');
                }elseif($withdraw['status']==0){
                    $withdraw['status_txt'] = L_('恢复审核');
                }else{
                    $withdraw['status_txt'] = L_('审核通过');
                }
            }

        }else{
            $withdraw = M('Merchant_withdraw')->field('w.*,u.openid,m.name as withdraw_name')->join('as w LEFT JOIN '.C('DB_PREFIX').'merchant m ON m.mer_id = w.mer_id LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = m.uid')->where($where)->find();

            $where_withdraw['withdraw_id'] = $withdraw['id'];
            $where_withdraw['mer_id'] = $withdraw['mer_id'];
            $withdraw_by_hand = M('Withdraw_list')->where($where_withdraw)->find();
            if(empty($withdraw_by_hand)){
                $withdraw['pay_type'] = 2;
            }else{
                $withdraw['pay_type'] = $withdraw_by_hand['pay_type'];
            }
            if($withdraw['status']==2 ){
                $withdraw['status_txt'] = $withdraw_status[$withdraw['status']].'(原因:'.$withdraw['remark'].')';
            }else{
                $withdraw['status_txt'] = $withdraw_status[$withdraw['status']];
            }

        }


        if($withdraw['openid']){
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href =$is_user?C('config.site_url').'/wap.php?c=My&a=transaction':C('config.site_url').'/packapp/merchant/apply_record.html';
            $model->sendTempMsg('OPENTM202425107', array('href' => $href,
                'wecha_id' => $withdraw['openid'],
                'first' => $withdraw['withdraw_name'].L_('，您好。您的提现申请已处理。'),
                'keyword1' =>C('config.Currency_symbol').floatval($withdraw['money']/100),
                'keyword2' =>L_('提现至').$withdraw_type[$withdraw['pay_type']],
                'keyword3' =>date('Y-m-d H:i',$is_user?$withdraw['add_time']:$withdraw['withdraw_time']),
                'keyword4' => $withdraw['status_txt'],
                'keyword5' =>date('Y-m-d H:i'),

                'remark' =>L_('点击查看详情')),
                0);
        }


    }

    public function get_system_take_by_condition($condition){
        if($condition){
            $where = $condition;
        }

        return $this->where($where)->sum('system_take');
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

    //获取联盟商家收支列表
    public function get_union_money_list($mer_id,$is_system = 0,$where,$pagesize = 20){
        if($is_system){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        unset($where['mer_id']);
        $where['l.mer_id']=array('in',(string)$mer_id);
        if($where['store_id']){
            $where['l.store_id'] = $where['store_id'];
            unset($where['store_id']);
        }
        if($where['type']){
            $where['l.type'] = $where['type'];
            unset($where['type']);
        }

        if($where['union_mer_id']){
            $where['l.union_mer_id'] = array('in',$where['union_mer_id']);
            unset($where['union_mer_id']);
        }
        if ($where['union_type']==1) {
            unset($where['union_type']);
            // 其他商家待兑现给我的统计
            $condition_join = 'as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id ';
        }else{       
            unset($where['union_type']);     
            // 我待兑现给其他商家的统计 
            $condition_join = 'as l left join '.C('DB_PREFIX') . 'merchant m ON m.mer_id = l.union_mer_id left join '.C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id ';
        }
        $count = $income_count= M('Merchant_money_list')->join($condition_join)->where($where)->count();
        $p = new Page($count, $pagesize);
        $pagebar=$p->show();
        if($_GET['page']>$p->totalPage){
            return array('income_list'=>array(),'total'=>0,'page_num'=>$p->totalPage);
        }else {
            $income_list = M('Merchant_money_list')->join($condition_join)->field('ms.name as store_name,l.order_id,l.desc,l.use_time,l.num,l.money,l.type,l.id,l.income,l.now_mer_money,l.system_take,l.percent,l.store_id,l.score,l.score_count,l.total_money,m.name')->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();

            // 支出总计
            $total_where = array_merge($where,array('l.income'=>2));
            $income_sum= M('Merchant_money_list')->join($condition_join)->where($total_where)->sum('l.money');

            // 收入总计
            $where['l.income'] = 1;
            $total_income = M('Merchant_money_list')->join($condition_join)->where($where)->sum('l.money');
            return array('income_list' => $income_list,
                'income_count' => $count,
                'income_sum' => getFormatNumber($total_income-$income_sum),
                'pagebar' => $pagebar,
                'page_num' => $p->totalPage);
        }
    }


    /**
     * 计算快店订单预结算金额
     * @param $orderId
     * @author: 张涛
     * @date: 2020/12/09
     */
    public function compute_merchant_settlement_money($orderId,$isReturn=false)
    {
        $order_info = M('Shop_order')->where(['order_id' => $orderId])->find();
        $order_info['order_type'] = 'shop';

        //商家绑定用户
        $mer_id = $order_info['mer_id'];
        $desc = $order_info['desc'];
        $mer_user = D('Merchant')->get_merchant_user($mer_id);
        if (isset($order_info['store_id'])) {
            $now_store = M('Merchant_store')->where(array('store_id' => $order_info['store_id']))->find();
            $date['store_id'] = $order_info['store_id'];
            $union_data['store_id'] = $order_info['store_id'];
        }

        $now_merchant = D('Merchant')->get_info($mer_id);

        // 联盟商家
        $now_merchant_union = [];
        $order_info['union_mer_id'] && $now_merchant_union =  D('Merchant')->get_info($order_info['union_mer_id']);
        //自有支付
        if (isset($order_info['is_own']) && $order_info['is_own'] > 0) {
            $order_info['payment_money'] = 0;
        }

        $num = $order_info['num'];
        if (C('config.open_meiyong_percent') == 1) {
            //1 平台出 不抽成 但对账  0 商家出 抽成 不对账
            // 平台优惠 商家优惠不抽成
            if ($order_info['discount_detail']) {
                $discount_detail = unserialize($order_info['discount_detail']);
                $plat_pay_money = floatval($discount_detail['system_minus']['minus'])
                // +floatval($discount_detail['delivery']['plat_money'])
                + floatval($discount_detail['system_newuser']['minus']);
                +floatval($discount_detail['newuser']['plat_money']);

                $merchant_pay_money  = floatval($discount_detail['system_minus']['merchant_money'])
                // +floatval($discount_detail['delivery']['merchant_money'])
                + floatval($discount_detail['newuser']['merchant_money'])
                + floatval($discount_detail['minus']['minus'])
                + floatval($discount_detail['system_newuser']['merchant_money']);
            }

            // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
            if ($order_info['union_mer_id']) {
                $merchant_balance = $order_info['merchant_balance'];
                $price = $order_info['balance_pay']
                + $order_info['payment_money']
                + $order_info['score_deducte']
                + $order_info['coupon_price'];
            } else {
                $price = $order_info['balance_pay']
                + $order_info['payment_money']
                + $order_info['score_deducte']
                + $order_info['coupon_price']
                + $order_info['merchant_balance'];
            }

            if (C('config.vip_discount_pay_for') == 1) {
                $money_system_take = $price + floatval($discount_detail['minus']['minus']) - $order_info['vip_level_reduce_money'];
                $money = $price - floatval($discount_detail['delivery']['merchant_money']) + floatval($discount_detail['system_minus']['plat_money']) + floatval($discount_detail['system_newuser']['plat_money']);
            } else {
                $money_system_take = $price + floatval($discount_detail['minus']['minus']);
                $money = $price - floatval($discount_detail['delivery']['merchant_money']) - floatval($order_info['vip_level_reduce_money']) + floatval($discount_detail['system_minus']['plat_money']) + floatval($discount_detail['system_newuser']['plat_money']);
            }

            //打包费不抽成 抽成金额去掉打包费
            if ($now_merchant['package_fee_percent'] == 0) {
                $money_system_take = $money_system_take - $order_info['packing_charge'];
            }

            //平台配送不对账 0 平台配送 1 商家配送
            if ($order_info['is_pick_in_store'] == 1) {
                // $money = $money$order_info['freight_charge'];
            } else {
                $money = $money - $order_info['freight_charge'];
                $money_system_take = $money_system_take - $order_info['freight_charge'];
            }
        } else {
            // 商家联盟定制功能，会员卡余额对账到使用的联盟商家余额
            if ($order_info['union_mer_id']) {
                $merchant_balance = $order_info['merchant_balance'];
                $price = $order_info['balance_pay']
                + $order_info['payment_money']
                + $order_info['score_deducte']
                + $order_info['coupon_price']
                + $order_info['balance_reduce'];
            } else {
                $price = $order_info['balance_pay']  //平台余额支付的金额
                    + $order_info['payment_money']  //在线支付的钱
                    + $order_info['score_deducte']  //积分抵扣金额
                    + $order_info['coupon_price']  //平台优惠券金额
                    + $order_info['balance_reduce']  //平台优惠的金额 应该是平台满减
                    + $order_info['merchant_balance']  //使用商户余额
                    + $order_info['card_price']  //商家优惠券的金额
                    + $order_info['merchant_reduce'];   //商家优惠的金额
            }

            if ($order_info['is_pick_in_store'] == 0 && ($order_info['card_give_money'] > 0 || $order_info['card_price'] > 0) && $order_info['order_from'] == 0) { //平台配送 （使用了商家赠送余额或商家优惠券）
                $pay_for_system = $order_info['freight_charge'];
                $money = $price - $order_info['no_bill_money'];
                $money_ = $price;
                if ($money <= 0) {
                    $pay_for_system -= $money_;
                }
            } else if ($order_info['is_own'] > 0 && $order_info['is_pick_in_store'] == 0) {
                $pay_for_system = $order_info['freight_charge'];
                if ($now_merchant['express_fee'] > 0) {
                    $pay_for_system = $now_merchant['express_fee'];
                }
                if ($order_info['union_mer_id']) {
                    $merchant_balance = $order_info['merchant_balance'];
                    $money = $order_info['balance_pay'] + $order_info['score_deducte'] + $order_info['coupon_price'] + $order_info['balance_reduce'];
                } else {
                    $money = $order_info['balance_pay'] + $order_info['score_deducte'] + $order_info['coupon_price'] + $order_info['balance_reduce'] + $order_info['merchant_balance'];
                }
            } else if ($order_info['is_pick_in_store'] == 0 && $order_info['order_from'] == 1) { //平台配送 （使用了商家赠送余额或商家优惠券）
                $pay_for_system = $order_info['no_bill_money'];
                $money = $price;
            } else {
                if ($order_info['is_pick_in_store'] == 0) {
                    $pay_for_system = $order_info['no_bill_money'];
                }

                //$money = $price - $order_info['no_bill_money'] + $order_info['platform_merchant'];
                $money = $price - $order_info['no_bill_money'];
                if ($now_merchant['express_fee'] > 0) {
                    if ($now_merchant['express_fee'] > $order_info['freight_charge']) {
                        $money = $money - ($now_merchant['express_fee'] - $order_info['freight_charge']);
                    } else if ($now_merchant['express_fee'] < $order_info['freight_charge']) {
                        $money  = $money + $order_info['freight_charge'] - $now_merchant['express_fee'];
                    } else {
                        // 实际配送费用与固定配送费一致 即扣除实际配送费即可
                    }
                }
            }
            // if($order_info['is_pick_in_store']==0){
            //     $money = $money-$order_info['other_money'];
            // }
            if ($now_merchant['package_fee_percent'] == 0) {
                $money = $money - $order_info['packing_charge'];
            }
        }


        //重新计算
        $price = $order_info['discount_price'] + $order_info['vip_level_reduce_money'] + $order_info['packing_charge'] + $order_info['freight_charge'];  //优惠前总计
        //自有支付
        if (isset($order_info['is_own']) && $order_info['is_own'] > 0) {
            $price = 0;
        } else if($order_info['pay_type'] == 'offline'){
            $price = 0;
        } else if ($order_info['change_price'] > 0) {
            $price = $order_info['price'];
        }
        //去除部分退款的金额
        $all_refund_pay_type = D('Shop_order_refund')
            ->field('sum(score_deducte) as score_deducte,sum(balance_pay) as balance_pay,sum(payment_money) as payment_money,sum(merchant_balance) as merchant_balance,sum(card_give_money) as card_give_money')
            ->where(['order_id'=>$orderId,'uid'=>$order_info['uid'],'status'=>1])->find();
        if($all_refund_pay_type){
            $price = $price - ($all_refund_pay_type['score_deducte']+$all_refund_pay_type['payment_money']+$all_refund_pay_type['balance_pay']+$all_refund_pay_type['merchant_balance']);
            $price = $price>0?$price:0;
            if($merchant_balance){
                $merchant_balance -= $all_refund_pay_type['merchant_balance'];
                $merchant_balance = $merchant_balance>0?$merchant_balance:0;
            }
        }
        $money = $price;
        if ($now_merchant['package_fee_percent'] == 0) {
            $money = $money - $order_info['packing_charge'];
        }
        if ($order_info['is_pick_in_store'] == 0) {
            $money -= $order_info['freight_charge'];
        }
        //计算商家补贴部分
        $goods_take_a_percentage = 0;//订单中的商品抽成金额（前提：已设置商品抽成）
        $goods_take_a_percentage_total_money = 0;//订单中的商品金额（前提：已设置商品抽成）
        $goods_detail = M("Shop_order_detail")->alias('a')->join('left join ' . C('DB_PREFIX') . 'shop_goods as b on a.goods_id=b.goods_id')->field('b.goods_id,b.take_a_percentage,a.discount_price,a.num')->where(['a.order_id' => $order_info['order_id']])->select();
        foreach ($goods_detail as $gdetail) {
            if ($gdetail['take_a_percentage'] > 0) {
                $goods_take_a_percentage += $gdetail['discount_price'] * $gdetail['num'] * $gdetail['take_a_percentage'] / 100;
                $goods_take_a_percentage_total_money += $gdetail['discount_price'] * $gdetail['num'];
            }
        }
        $goods_take_a_percentage = getFormatNumber($goods_take_a_percentage);

        $model = '\app\shop\model\service\order\ShopOrderService/computeMerchantExpendByOrderId';
        $params = [
            'orderId' => $order_info['order_id'],
            'withIndex' => true
        ];
        //$v20res = invoke_v20_service($model, $params);
        //$res = $v20res['data']['retval'] ?? [];
        $res = D('Shop_order')->computeMerchantExpendByOrderId($order_info['order_id']);
        $merchantReduce = $res['merchant_reduce'] ? $res['merchant_reduce']['amount'] : 0;
        $merchantFirstOrderReduce = $res['merchant_first_order_reduce'] ? $res['merchant_first_order_reduce']['amount'] : 0;
        $merchantCard = $res['merchant_card'] ? $res['merchant_card']['amount'] : 0;
        $merchantCoupon = $res['merchant_coupon'] ? $res['merchant_coupon']['amount'] : 0;
        $systemDeliver = $res['system_deliver'] ? $res['system_deliver']['amount'] : 0;
        $systemReduce = $res['system_reduce'] ? $res['system_reduce']['amount'] : 0;
        $systemCard = $res['system_card'] ? $res['system_card']['amount'] : 0;
        $systemCoupon = $res['system_coupon'] ? $res['system_coupon']['amount'] : 0;
        $cardGive = $res['card_give_money'] ? $res['card_give_money']['amount'] : 0;
        $systemReduce += ($res['system_newuser_reduce'] ? $res['system_newuser_reduce']['amount'] : 0);
        $systemReduce += ($res['plat_discount_reduce'] ? $res['plat_discount_reduce']['amount'] : 0);
        if($all_refund_pay_type&&$cardGive){
            $cardGive -= $all_refund_pay_type['card_give_money'];
            $cardGive = $cardGive>0?$cardGive:0;
        }

        $payment_money_order = M('Payment_money_order')->where(array('orderid' => $order_info['orderid'], 'order_type' => 'shop'))->find();
        if ($payment_money_order && $payment_money_order['service_charge'] > 0 && $money > 0) {
            $money -= $payment_money_order['service_charge'];
        }

        $order_info['total_money'] = $order_info['total_price'];

        // 实付金额
        $date['pay_money'] = $order_info['price'];

        $is_pick_in_store = isset($order_info['is_pick_in_store']) ? $order_info['is_pick_in_store'] : -1;
        $percent = D('Percent_rate')->get_percent($mer_id, $order_info['order_type'], $money, '', $order_info['store_id'], true, $is_pick_in_store);

        if ($order_info['union_mer_id']) {
            // 联盟商家抽成比例
            $union_percent = D('Percent_rate')->get_percent($order_info['union_mer_id'], $order_info['order_type'], $merchant_balance, '', $order_info['store_id'], true);
        }

        if ($now_merchant['fans_percent'] > 0 && C('config.open_fans_percent') == 1) {
            $percent = $now_merchant['fans_percent'];
        }


        $date['type'] = $order_info['order_type'];
        $order_info['union_mer_id'] && $union_data['type'] = $order_info['order_type'];

        if ($date['type'] == 'group' || $date['type'] == 'shop' || $date['type'] == 'mall') {
            $date['order_id'] = $order_info['real_orderid'];
            $order_info['union_mer_id'] && $union_data['order_id'] = $order_info['real_orderid'];
        } else {
            $date['order_id'] = $order_info['order_id'];
            $order_info['union_mer_id'] && $union_data['order_id'] = $order_info['order_id'];
        }

        $percent = floatval($percent);
        $union_percent = floatval($union_percent);

        if ($money <= 0) {
            $money  = 0;
        }
        $date['num'] = $num > 0 ? $num : 1;
        $date['mer_id'] = $mer_id > 0 ? $mer_id : 1;

        // 商家联盟
        $union_data['num'] = $num > 0 ? $num : 1;
        $union_data['mer_id'] = $order_info['union_mer_id'];
        $date['total_money'] = $order_info['total_money'];
        $union_data['total_money'] = $order_info['total_money'];

        if (C('config.open_meiyong_percent') == 1 && ($order_info['order_type'] == 'shop' || $order_info['order_type'] == 'mall')) {
            $date['system_take'] = round($money_system_take * $percent / 100, 2);
            if ($order_info['uid'] > 0 && C('config.add_score_by_percent') > 0 && round($date['system_take']) > 0) {
                $date['score'] = round($date['system_take']);
            }
        } else {
            //平台满减优惠平台补贴不计算佣金，商家补贴计算佣金
            $orderDiscountDetail = $order_info['discount_detail'] ? unserialize($order_info['discount_detail']) : [];
            $systemMinusDiscount = is_array($orderDiscountDetail) && isset($orderDiscountDetail['system_minus']) ? $orderDiscountDetail['system_minus'] : [];
            $deliverMinusDiscount = is_array($orderDiscountDetail) && isset($orderDiscountDetail['delivery']) ? $orderDiscountDetail['delivery'] : [];
            //配送费折扣满减
            $deliverZhekou = $orderDiscountDetail['delivery_zhekou'] ?? [];
            if (empty($deliverMinusDiscount) && $deliverZhekou) {
                $plat = $deliverZhekou['minus2'] * $deliverZhekou['plat_money'];
                $merchant = $deliverZhekou['minus2'] - $plat;
                $deliverMinusDiscount = [
                    'plat_money' => $plat > 0 ? $plat : 0,
                    'merchant_money' => $merchant > 0 ? $merchant : 0
                ];
            }
            $merchantSubsidy = $platSubsidy = 0;
            if ($systemMinusDiscount) {
                //满减
                $balanceReduce = $order_info['discount_price'] + $order_info['freight_charge'];
                if ($systemMinusDiscount['minus'] >= $balanceReduce) {
                    $sumMinus = $systemMinusDiscount['merchant_money'] + $systemMinusDiscount['plat_money'];
                    if ($sumMinus > 0) {
                        $merchantSubsidy += round($systemMinusDiscount['merchant_money'] * $balanceReduce / $sumMinus, 2);
                        $platSubsidy += round($systemMinusDiscount['plat_money'] * $balanceReduce / $sumMinus, 2);
                    }

                } else {
                    $merchantSubsidy += round($systemMinusDiscount['merchant_money'], 2);
                    $platSubsidy += round($systemMinusDiscount['plat_money'], 2);
                }
            }

            //平台配送不参与计算佣金，已记录到no_bill_money字段
            if ($deliverMinusDiscount) {
                //运费满减
                $orderRealFreightCharge = $order_info['freight_charge'];
                $freightChargeOriginal = $order_info['freight_charge_original'];
                if ($orderRealFreightCharge == 0) {
                    //全部抵扣了，优先使用平台补贴，剩余使用商家补贴
                    if ($deliverMinusDiscount['plat_money'] >= $freightChargeOriginal) {
                        $deliverMinusDiscount['plat_money'] = $freightChargeOriginal;
                        $deliverMinusDiscount['merchant_money'] = 0;
                    } else {
                        $deliverMinusDiscount['merchant_money'] = $freightChargeOriginal - $deliverMinusDiscount['plat_money'];
                    }
                }

                //平台配送不参与计算佣金，已记录到no_bill_money字段减掉过了
                if ($order_info['is_pick_in_store'] != 0) {
                    $merchantSubsidy += round($deliverMinusDiscount['merchant_money'], 2);
                    $platSubsidy += round($deliverMinusDiscount['plat_money'], 2);
                }
            }

            //平台优惠券平台补贴的钱结算给商家，但是不参与平台抽成；商家补贴的钱不结算给商家，但是要参与平台抽成。
            $system_coupon_plat_money = 0;
            $system_coupon_merchant_money = 0;
            if ($order_info['coupon_id'] > 0 && $order_info['coupon_price'] > 0) {
                $use_system_coupon = D('System_coupon')->get_coupon_hadpull(['s.uid' => $order_info['uid'], 's.id' => $order_info['coupon_id']]);
                if ($use_system_coupon) {
                    if ($use_system_coupon['plat_money'] == 0 && $use_system_coupon['merchant_money'] == 0) {
                        $use_system_coupon['plat_money'] = 1;
                    }
                }
                $system_coupon_plat_money = getFormatNumber($order_info['coupon_price'] * ($use_system_coupon['plat_money'] / ($use_system_coupon['plat_money'] + $use_system_coupon['merchant_money'])));
                $system_coupon_merchant_money = getFormatNumber($order_info['coupon_price'] - $system_coupon_plat_money);
            }

            if (C('config.open_store_percent_single') == 1 && ($order_info['order_type'] == 'store' || $order_info['order_type'] == 'cash')) {
                $date['system_take'] = 0;
            } else {
                //商家推广的用户在推广商家里消费外卖业务时，平台是否抽取该商家抽成。1：是  0：否
                $spread_user_buy_no_commission = M('Merchant')->where(['mer_id' => $mer_id])->getField('spread_user_buy_no_commission');
                if ($spread_user_buy_no_commission == 0 && in_array($order_info['order_type'], ['shop', 'mall']) && $order_info['order_from'] != 1 && D('Merchant_spread')->is_merchant_spread_user($mer_id, $order_info['uid'])) {
                    $date['system_take'] = 0;
                    $date['is_spread_user_buy'] = 1;
                } else {
                    //公式 【外卖商品折扣后总额 - 平台满减优惠商家补贴的钱 - 平台优惠券平台补贴的钱  - 商家会员卡余额支付的线下充值部分 - 商家满减优惠金额 - 商家优惠券金额-平台会员等级优惠 ± 打包费 + 商家配送费或者快递费】* 抽成比例
                    $handleTotalMoney = $money - $goods_take_a_percentage_total_money - $systemReduce - $system_coupon_plat_money - $merchantReduce - $merchantFirstOrderReduce - $merchantCoupon - $systemCard;

                    $date['system_take'] = round($handleTotalMoney * $percent / 100 + $goods_take_a_percentage, 2);
                    $union_data['system_take'] = round($merchant_balance * $union_percent / 100, 2);
                }

                //是否开启了平台对商家抽成按照商品原价和打包费抽成
                if (C('config.system_take_merchant_rule') == 1) {
                    if ($order_info['is_own'] > 1 || $order_info['pay_type'] == 'offline') {
                        $date['system_take'] = 0;
                    } else {
                        $settleMoney = $order_info['packing_charge'] + $order_info['goods_price'];
                        $date['system_take'] = round($settleMoney * $percent / 100, 2);
                    }
                }

                if($date['system_take'] < 0){
                    $date['system_take'] = 0;
                }
                if($union_data['system_take'] < 0){
                    $union_data['system_take'] = 0;
                }
            }
            if ($order_info['uid'] > 0 && C('config.add_score_by_percent') > 0 && round($date['system_take']) > 0) {
                $date['score'] = round($date['system_take']);
                $union_data['score'] = round($union_data['system_take']);
            }
            if ($now_merchant['package_fee_percent'] == 0) {
                $money = $money + $order_info['packing_charge'];
            } else if ($order_info['express_fee'] > 0) {
                $money = $money + $order_info['express_fee'];
            }
        }


        if (C('config.open_store_percent_single') == 1 && $order_info['is_own'] == 0 && ($order_info['order_type'] == 'store' || $order_info['order_type'] == 'cash')) {

            $date['money'] = D('Percent_rate')->get_percent($mer_id, $order_info['order_type'], $order_info['total_money'], '', $order_info['store_id'], true);
        } else {
            //【外卖商品折扣后总额 + 打包费 + 商家配送费或者快递费】- 【平台抽成金额 +平台配送费商家补贴部分+商家满减优惠+商家会员卡优惠+商家优惠券+平台优惠券商家补贴部分+平台满减优惠商家补贴部分+平台会员等级优惠+商家会员卡余额支付的线下充值部分】
            $date['money'] = $money - ($date['system_take'] + $merchantReduce + $merchantFirstOrderReduce +$merchantCard +$merchantCoupon+$systemDeliver+$systemReduce+$systemCard+$systemCoupon+$cardGive);
            $union_data['money'] = $merchant_balance - $union_data['system_take'];
        }


        $date['percent'] = $percent;
        $union_data['percent'] = $union_percent;

        $date['money'] < 0 && $date['money'] = 0;
        $date['system_take'] < 0 && $date['system_take'] = 0;
        $date['income'] = 1;
        $date['use_time'] = time();
        $date['desc'] =  empty($desc) ? '' : $desc;

        if($isReturn){
            return $date;
        }
        M('Shop_order')->where(['order_id' => $orderId])->save(['expect_settlement_money' => $date['money'], 'expect_settlement_percent' => $date['percent'], 'expect_system_take' => $date['system_take']]);
        return $date;
    }


    //$deposit:定金 、$balance：尾款 、 $merSettlement：商家抽成 、$platCommission：平台抽成  
    public function calFzCommission($deposit, $balance, $merSettlement, $platCommission)
    {
        $deposit = (string)$deposit;
        $balance = (string)$balance;
        $merSettlement = (string)$merSettlement;
        $platCommission = (string)$platCommission;

        // 计算总金额
        $totalOriginal = bcadd($deposit, $balance, 2);

        // 计算定金和尾款的比例
        $depositRatio = bcdiv($deposit, $totalOriginal, 2);
        $balanceRatio = bcdiv($balance, $totalOriginal, 2);

        // 按比例拆分结算给商家的金额
        $merDepositSettlement = bcmul($merSettlement, $depositRatio, 2);
        $merBalanceSettlement = bcmul($merSettlement, $balanceRatio, 2);

        // 按比例拆分平台抽成金额
        $platDepositCommission = bcmul($platCommission, $depositRatio, 2);
        $platBalanceCommission = bcmul($platCommission, $balanceRatio, 2);

        // 计算定金部分的总抽成金额
        $totalDepositSettlement = bcadd($merDepositSettlement, $platDepositCommission, 2);
        // 如果定金部分总抽成超出定金总金额，进行调整
        if (bccomp($totalDepositSettlement, $deposit, 2) > 0) {
            $adjustRatio = bcdiv($deposit, $totalDepositSettlement, 2);
            $merDepositSettlement = bcmul($merDepositSettlement, $adjustRatio, 2);
            $platDepositCommission = bcsub($deposit, $merDepositSettlement, 2);
        }

        // 计算尾款部分的总抽成金额
        $totalBalanceSettlement = bcadd($merBalanceSettlement, $platBalanceCommission, 2);
        // 如果尾款部分总抽成超出尾款总金额，进行调整
        if (bccomp($totalBalanceSettlement, $balance, 2) > 0) {
            $adjustRatio = bcdiv($balance, $totalBalanceSettlement, 2);
            $merBalanceSettlement = bcmul($merBalanceSettlement, $adjustRatio, 2);
            $platBalanceCommission = bcsub($balance, $merBalanceSettlement, 2);
        }
        return [
            'merDepositSettlement' => $merDepositSettlement > 0 ? $merDepositSettlement : 0,  //商家定金结算部分
            'platDepositCommission' => $platDepositCommission > 0 ? $platDepositCommission : 0, //平台定金抽成部分
            'merBalanceSettlement' => $merBalanceSettlement > 0 ? $merBalanceSettlement : 0, //商家尾款结算部分
            'platBalanceCommission' => $platBalanceCommission > 0 ? $platBalanceCommission : 0, //平台尾款抽成部分
        ];
    }
	
}
