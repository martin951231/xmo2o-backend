<?php

class Percent_rateModel extends Model
{
    //获取抽成比例
    public function get_percent($mer_id,$type,$money,$group_id='',$store_id='', $is_relation = false, $is_pick_in_store = -1)
    {
        if(C('config.is_open_merchant_foodshop_discount')==1 && ($type=='foodshop'||$type=='meal')){
            $where = array('real_orderid'=>$group_id);
            return D('Foodshop_order')->where($where)->getField('mer_table_scale');
        }

        if($type=='village_group'){
            return 0;
        }
        if($type=='award'){
            return 0;
        }
		if($type == 'yydb' || $type == 'coupon'){
			$type = 'activity';
		}
        if(C('config.open_meal_scan_percent')==0 && $type=='meal_scan'){
            $type='meal';
        }
        if($group_id>0 && C('config.open_group_percent')==1){
            $now_group = M('Group')->where(array('group_id'=>$group_id))->find();
            if($now_group['percent']>=0){
                return $now_group['percent'];
            }
        }
        if(C('config.open_store_percent_single')==1 && ($type=='store'||$type=='cash')){
            $where['mer_id'] = $mer_id;
            $now_mer = M('Merchant')->where($where)->find();
            if($now_mer['store_percent_single']>0){
                if( $now_mer['store_percent_single_type']==1){
                    return $now_mer['store_percent_single'];
                }else{

                    return $now_mer['store_percent_single']*$money/100;
                }
            }else if( C('config.store_percent_single_value')>0){
                if( C('config.store_percent_single_type')==1){
                    return C('config.store_percent_single_value');
                }else{

                    return C('config.store_percent_single_value')*$money/100;
                }
            }

        }

        if (!$is_relation || !$store_id) {
            if($store_id){
                $where['store_id'] = $store_id;
                $now_mer_pr = M('Store_percent_rate')->where($where)->find();
            }else{
                $where['mer_id'] = $mer_id;
                $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
            }

            if ($now_mer_pr) {

                if ($now_mer_pr[$type . '_percent'] >= 0 && $now_mer_pr[$type .'_percent']!='') {
                    $percent = $this->percent_detail($mer_id,$type,'mer_type',$money,$store_id);
                    if(empty($percent)){
                        return $now_mer_pr[$type . '_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ($now_mer_pr['merchant_percent'] >= 0 &&$now_mer_pr['merchant_percent']!='') {
                    $percent = $this->percent_detail($mer_id,$type,'merchant',$money,$store_id);

                    if(empty($percent)){
                        return $now_mer_pr['merchant_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ($now_mer_pr['merchant_store_percent'] >= 0 &&$now_mer_pr['merchant_store_percent']!='') {
                    $percent = $this->percent_detail($mer_id,$type,'merchant_store',$money,$store_id);

                    if(empty($percent)){
                        return $now_mer_pr['merchant_store_percent'];
                    }else{
                        return $percent;
                    }
                }elseif ( C('config.open_meal_scan_percent')==1 && $type=='meal_scan' && C('config.meal_scan_percent') >= 0 ) {

                    return C('config.' . $type . '_percent');

                }elseif ( C('config.' . $type . '_percent') >= 0 || ($type=='meal_scan' && C('config.meal_percent') >= 0 ) ) {
                    if($type=='meal_scan'){
                        $type='meal';
                    }
                    $percent = $this->percent_detail($mer_id,$type,'sys_type',$money,$store_id);
                    if(empty($percent)){
                        return C('config.' . $type . '_percent');

                    }else{

                        return $percent;
                    }
                } elseif ( C('config.platform_get_merchant_percent') >= 0) {
                    $percent = $this->percent_detail($mer_id,$type,'system',$money,$store_id);
                    if(empty($percent)){
                        return C('config.platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
                } else {
                    return 0;
                }
            } else {

                if (C('config.' . $type . '_percent') >= 0) {
                    return C('config.' . $type . '_percent');
                } elseif (C('config.platform_get_merchant_percent') >= 0) {
                    $percent = $this->percent_detail(0,$type,'system',$money,0);
                    if(empty($percent)){
                        return C('config.platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
//                    return C('config.platform_get_merchant_percent');
                } else {
                    return 0;
                }
            }
        } elseif($is_relation && $store_id>0){
            $where['store_id'] = $store_id;
            $now_store_pr = M('Store_percent_rate')->where($where)->find();
            if (!$now_store_pr) $now_store_pr = array();

            $where['mer_id'] = $mer_id;
            $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
            if (!$now_mer_pr) $now_mer_pr = array();

            //快店根据配送方式获取抽成比例
            $rate_field = '';
            if ($type == 'shop' && $is_pick_in_store >= 0) {
                switch ($is_pick_in_store) {
                    case 0:
                        $rate_field = 'shop_plat_deliver_percent';
                        break;
                    case 1:
                        $rate_field = 'shop_merchant_deliver_percent';
                        break;
                    case 2:
                        $rate_field = 'shop_self_pick_percent';
                        break;
                    case 3:
                        $rate_field = 'shop_express_deliver_percent';
                        break;
                    default:
                        break;
                }
            }

            if ($now_mer_pr || $now_store_pr) {
                if ($now_store_pr) {
                    if ($now_store_pr[$type . '_percent'] >= 0 && $now_store_pr[$type . '_percent'] != '') {
                        $percent = $this->percent_detail($mer_id, $type, 'mer_type', $money, $store_id);
                        if (empty($percent)) {
                            return $now_store_pr[$type . '_percent'];
                        } else {
                            return $percent;
                        }
                    }
                    if ($now_store_pr['merchant_store_percent'] >= 0 && $now_store_pr['merchant_store_percent'] != '') {
                        $percent = $this->percent_detail($mer_id, $type, 'merchant_store', $money, $store_id);
                        if (empty($percent)) {
                            return $now_store_pr['merchant_store_percent'];
                        } else {
                            return $percent;
                        }
                    }
                }

                if ($now_mer_pr) {
                    if ($type == 'shop' && $rate_field && $now_mer_pr && $now_mer_pr[$rate_field] >= 0) {
                        return $now_mer_pr[$rate_field];
                    }
                    if ($now_mer_pr[$type . '_percent'] >= 0 && $now_mer_pr[$type . '_percent'] != '') {
                        $percent = $this->percent_detail($mer_id, $type, 'mer_type', $money, 0);
                        if (empty($percent)) {
                            return $now_mer_pr[$type . '_percent'];
                        } else {
                            return $percent;
                        }
                    }
                    if ($now_mer_pr['merchant_percent'] >= 0 && $now_mer_pr['merchant_percent'] != '') {
                        $percent = $this->percent_detail($mer_id, $type, 'merchant', $money, 0);
                        if (empty($percent)) {
                            return $now_mer_pr['merchant_percent'];
                        } else {
                            return $percent;
                        }
                    }
                }
                if ($type == 'shop' && $rate_field && C('config.' . $rate_field) >= 0) {
                    return C('config.' . $rate_field);
                }

               if ( C('config.open_meal_scan_percent')==1 && $type=='meal_scan' && C('config.meal_scan_percent') >= 0 ) {

                    return C('config.' . $type . '_percent');

                }elseif ( C('config.' . $type . '_percent') >= 0 || ($type=='meal_scan' && C('config.meal_percent') >= 0 ) ) {
                    if($type=='meal_scan'){
                        $type='meal';
                    }
                    $percent = $this->percent_detail($mer_id,$type,'sys_type',$money,$store_id);
                    if(empty($percent)){
                        return C('config.' . $type . '_percent');

                    }else{

                        return $percent;
                    }
                } elseif ( C('config.platform_get_merchant_percent') >= 0) {
                    $percent = $this->percent_detail($mer_id,$type,'system',$money,$store_id);
                    if(empty($percent)){
                        return C('config.platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
                } else {
                    return 0;
                }
            } else {
                if ($type == 'shop' && $rate_field && C('config.' . $rate_field) >= 0) {
                    return C('config.' . $rate_field);
                }else if (C('config.' . $type . '_percent') >= 0) {
                    return C('config.' . $type . '_percent');
                } elseif (C('config.platform_get_merchant_percent') >= 0) {
                    $percent = $this->percent_detail(0,$type,'system',$money,0);
                    if(empty($percent)){
                        return C('config.platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
//                    return C('config.platform_get_merchant_percent');
                } else {
                    return 0;
                }
            }
        }
    }

    //get_old_age_pension  获取养老金比例
    public function getOldAgePension($mer_id, $type, $money = 0, $retPer = false){
        if(empty($mer_id) || empty($type)) return 0;
        $merchant_config = M('Merchant_percent_rate')->where(['mer_id' => $mer_id])->find();
        $percent = 0;
        if($merchant_config && $merchant_config['oap_'.$type.'_percent'] > 0){
            $percent = $merchant_config['oap_'.$type.'_percent'];
        }
        elseif ($merchant_config && $merchant_config['oap_merchant_percent'] > 0) {
            $percent = $merchant_config['oap_merchant_percent'];
        }
        elseif(C("config.oap_".$type."_percent") > 0){
            $percent = C("config.oap_".$type."_percent");
        }
        elseif(C("config.oap__percent") > 0){
            $percent = C("config.oap__percent");
        }
        return !$retPer ? getFormatNumber($money * $percent/100) : $percent;
    }

    //批发抽成
    public function get_market_percent($mer_id){

        $now_merchant = D('Merchant')->get_info($mer_id);
        if($now_merchant['market_percent']>0){
            return  $now_merchant['market_percent'];
        }else{
            return   C('config.platform_get_merchant_percent');
        }
    }

    //抽成细则的筛选
    public function percent_detail($mer_id,$type,$level,$money,$store_id=''){
        if($type=='meal_scan') return;
        $percent_detail = M('Percent_detail')->select();
        if(!empty($percent_detail)){
            $type_model = M('Percent_detail_by_type');
            $model = M('Percent_detail_by_store_type');
            if($store_id){
                $system_percent = $model->where(array('fid'=>0))->find();
                $mer_percent = $model->where(array('fid'=>$store_id))->find();
                if (!$system_percent) {
                    $system_percent = $type_model->where(array('fid'=>0))->find();
                }
                if (!$mer_percent) {
                    $mer_percent = $type_model->where(array('fid'=>$mer_id))->find();
                }
            }else{
                $system_percent = $type_model->where(array('fid'=>0))->find();
                $mer_percent = $type_model->where(array('fid'=>$mer_id))->find();
            }
            $percent  = 0;
            $i = 0;
            $in_detail = false;
            switch($level){
                case 'mer_type':
                    $percent_arr = explode(',',$mer_percent[$type.'_percent_detail']);
                    break;
                case 'merchant';
                    $percent_arr = explode(',',$mer_percent['merchant_percent_detail']);
                    break;
                case 'merchant_store';
                    $percent_arr = explode(',',$mer_percent['merchant_store_percent_detail']);
                    break;
                case 'sys_type':
                    $percent_arr = explode(',',$system_percent[$type.'_percent_detail']);
                    break;
            }

            foreach ($percent_detail as $pv) {
                if (bccomp($money, $pv['money_start'], 2) >= 0 && bccomp($pv['money_end'], $money, 2) >= 0) {
                    if($percent_arr[$i] >= 0&&$percent_arr[$i]!=''){
                        $percent = $percent_arr[$i];
                    }else{
                        $percent = $pv['percent'];
                    }
                    $in_detail = true;
                }
                $i++;
            }
            if($in_detail){
                return $percent;
            }else{
                return;
            }
        }else{
            return ;
        }
    }

    // 

    /*
     *获得用户推广分佣区间配置
     * @param int $mer_id 商家id
     * @param string $type 业务类型
     * @param string $level 分佣类型： mer_type-商家配置 sys_type-平台配置
     * @param int $uid 下单人uid
    */
    public function get_use_spread_percent_detail($mer_id,$type,$level,$uid,$openid=''){
        $percent_detail = M('Percent_spread_detail')->select();
        if(empty($percent_detail)){
            return '';
        }

        $type_model = M('Percent_spread_detail_by_type');
        $model = M('Percent_detail_by_store_type');

        // 平台设置
        $system_percent = $type_model->where(array('fid'=>0))->find();

        // 商家设置
        $mer_percent = $type_model->where(array('fid'=>$mer_id))->find();
        
        $percent  = 0;
        $i = 0;
        $in_detail = false;
        switch($level){
            case 'mer_type':
                $percent_arr = explode(',',$mer_percent[$type.'_percent_detail']);
                break;
            case 'sys_type':
                $percent_arr = explode(',',$system_percent[$type.'_percent_detail']);
                break;
        }

        // 推广者
        $spread_where['_string'] = "uid = '".$uid."'";
        if($openid){
            $spread_where['_string'] += " OR openid = '".$openid."'";
        }
        $now_user_spread = D('User_spread')->where($spread_where)->find();

        // 获得用户推广人数
        $spreadCount = M('User_spread')->where(['spread_uid'=>$now_user_spread['spread_uid']])->count();

        foreach ($percent_detail as $pv) {
            if (bccomp($spreadCount, $pv['money_start'], 2) >= 0 && bccomp($pv['money_end'], $spreadCount, 2) >= 0) {
                if($percent_arr[$i] >= 0&&$percent_arr[$i]!=''){
                    $percent = $percent_arr[$i];
                }else{
                    $percent = $pv['percent'];
                }
                $in_detail = true;
            }
            $i++;
        }
        if($in_detail){
            return $percent;
        }else{
            return '';
        }
    }
    //获取分佣比例
    public function get_rate($mer_id, $type, $user = [])
    {

        if(C('config.system_take_percent_mer')>0 ){ //商家推广佣金比例
            return C('config.system_take_percent_mer');
        }

        // 获得商家推广分佣区间配置
        $platformMerchantRate = 0;
        if(C('config.platform_get_merchant_rate') >= 0){
            $percent = $this->get_merchant_spread_percent_detail($mer_id,'','sys_type', $user);
            $platformMerchantRate = $percent !== '' ? $percent : C('config.platform_get_merchant_rate');
        }

        $where['mer_id'] = $mer_id;
        $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
        if ($mer_id>0 && $now_mer_pr) {
            if ($now_mer_pr[$type . '_rate'] >= 0 &&$now_mer_pr[$type . '_rate']!='') {
                return $now_mer_pr[$type . '_rate'];
            } elseif ( $now_mer_pr['merchant_rate'] >= 0 &&$now_mer_pr['merchant_rate']!='') {
                $percent = $this->get_merchant_spread_percent_detail($mer_id,'merchent','merchant', $user);
                return $percent !== '' ? $percent : $now_mer_pr['merchant_rate'];
            } elseif (C('config.' . $type . '_rate') >= 0) {
                return C('config.' . $type . '_rate');
            } elseif ( C('config.platform_get_merchant_rate') >= 0) {
                return $platformMerchantRate;
            } else {
                return 0;
            }
        } else {
            if (C('config.' . $type . '_rate') >= 0 ) {
                return C('config.' . $type . '_rate');
            } elseif (C('config.platform_get_merchant_rate') >= 0) {
                return $platformMerchantRate;
            } else {
                return 0;
            }
        }
    }

    /*
     *获得商家推广分佣区间配置
     * @param int $mer_id 商家id
     * @param string $type 业务类型
     * @param string $level 分佣类型： merchant-商家配置 sys_type-平台配置 
     * @param array $user 下单人
    */
    public function get_merchant_spread_percent_detail($mer_id,$type,$level,$user=''){
        $percent_detail = M('Percent_spread_merchant_detail')->select();
        if(empty($percent_detail)){
            return '';
        }

        $type_model = M('Percent_spread_merchant_detail_by_type');

        // 平台设置
        $system_percent = $type_model->where(array('fid'=>0))->find();

        // 商家设置
        $mer_percent = $type_model->where(array('fid'=>$mer_id))->find();
        
        $percent  = 0;
        $i = 0;
        $in_detail = false;
        switch($level){
            case 'merchant':
                $percent_arr = explode(',',$mer_percent['merchant_percent_detail']);
                break;
            case 'sys_type':
                $percent_arr = explode(',',$system_percent[$type.'_percent_detail']);
                break;
        }

        // 推广者
        $spread_user= M('Merchant_spread')->where(array('openid'=>$user['openid']))->find();

        // 获得用户推广人数
        $spreadCount = M('Merchant_spread')->where(['mer_id'=>$spread_user['mer_id']])->count();

        foreach ($percent_detail as $pv) {
            if (bccomp($spreadCount, $pv['money_start'], 2) >= 0 && bccomp($pv['money_end'], $spreadCount, 2) >= 0) {
                if($percent_arr[$i] >= 0&&$percent_arr[$i]!=''){
                    $percent = $percent_arr[$i];
                }else{
                    $percent = $pv['percent'];
                }
                $in_detail = true;
            }
            $i++;
        }
        if($in_detail){
            return $percent;
        }else{
            return '';
        }
    }

    //判断是否可以使用线下支付
    public function pay_offline($mer_id,$type){
        $where['mer_id'] = $mer_id;
        $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
        if ($now_mer_pr) {
            if($type=='group'||$type=='shop' || $type=='mall' ||$type=='meal'){
                return $now_mer_pr[$type.'_offline']&&$now_mer_pr['merchant_offline']&&C('config.'.$type.'_offline')&&C('config.pay_offline_open');
            }else{
                return $now_mer_pr['merchant_offline']&&C('config.'.$type.'_offline')&&C('config.pay_offline_open');
            }
        } else {
            return C('config.'.$type.'_offline')&&C('config.pay_offline_open');
        }
    }

    /**
     * @desc判断是否可以使用线上支付
     * @param $mer_id
     * @return bool
     */
    public function pay_online($mer_id){
        $where['mer_id'] = $mer_id;
        $merchant_online = M('Merchant_percent_rate')->where($where)->getField('merchant_online');
		return $merchant_online === NULL ? 1 : $merchant_online;
    }

    //获取用户分佣比例
    public function get_user_spread_rate($mer_id,$type,$group_id='',$now_order=[]){
        $uid = $now_order['uid'] ?? 0;// 下单人uid
        $where['mer_id'] = $mer_id;
        $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
        $first_rate='';
        $second_rate='';
        $third_rate='';
        $rate_type='';
//        if(C('config.open') && C('config.system_take_spread_percent')>0 && $all_percent = (C('config.system_take_first_percent') + C('config.system_take_second_percent') + C('config.system_take_third_percent'))>0){
//            $spread_money_take =  C('config.system_take_spread_percent');
//            return array(
//                'first_rate'=>$spread_money_take*C('config.system_take_first_percent')/$all_percent,
//                'second_rate'=>$spread_money_take*C('config.system_take_second_percent')/$all_percent,
//                'third_rate'=>$spread_money_take*C('config.system_take_third_percent')/$all_percent,
//                'type'=>'system_take',
//            );
//        }

        if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ){
            $spread_money_take =  C('config.system_take_spread_percent');
            return array(
                'first_rate'=>$spread_money_take*C('config.system_take_first_percent')/100,
                'second_rate'=>$spread_money_take*C('config.system_take_second_percent')/100,
                'third_rate'=>$spread_money_take*C('config.system_take_third_percent')/100,
                'type'=>'system_take',
            );
        }
        
        $systemFirstPercent  = C('config.user_spread_rate');
        if(C('config.user_spread_rate')>=0){// 定制一级分佣区间设置佣金
            // 平台一级分佣比例
            $percent = $this->get_use_spread_percent_detail($mer_id,'','sys_type',$uid);
            $systemFirstPercent = $percent !== '' ? $percent : C('config.user_spread_rate');
        }

        switch($type) {
            case 'recruit':
                // 招聘
                if (C('config.recruit_spread_rate')>=0){
                    $first_rate=C('config.recruit_spread_rate');
                    $second_rate=C('config.recruit_second_spread_rate')>0?C('config.recruit_second_spread_rate'):0;
                    $third_rate=C('config.recruit_third_spread_rate')>0?C('config.recruit_third_spread_rate'):0;
                    $rate_type='recruit';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }
                break;
            case 'recharge':
                if (C('config.recharge_spread_rate')>=0){
                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'sys_type',$uid);
                    $first_rate = $percent !== '' ? $percent : C('config.recharge_spread_rate');
                    $second_rate=C('config.recharge_first_spread_rate')>0?C('config.recharge_first_spread_rate'):0;
                    $third_rate=C('config.recharge_second_spread_rate')>0?C('config.recharge_second_spread_rate'):0;
                    $rate_type='system';
                }elseif(C('config.user_spread_rate')>=0){
                    $first_rate = $systemFirstPercent;
                    $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
                    $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
                    $rate_type='system';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }

                break;
            case 'classifynew':
                if (C('config.classifynew_first_rate')>=0){
                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'sys_type',$uid);
                    $first_rate = $percent !== '' ? $percent : C('config.classifynew_first_rate');
                    $second_rate=C('config.classifynew_second_rate')>0?C('config.classifynew_second_rate'):0;
                    $third_rate=C('config.classifynew_third_rate')>0?C('config.classifynew_third_rate'):0;
                    $rate_type='classifynew';
                }elseif(C('config.user_spread_rate')>=0){
                    $first_rate = $systemFirstPercent;
                    $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
                    $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
                    $rate_type='system';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }

            break;
            case 'group':
                $now_group = M('Group')->where(array('group_id'=>$group_id))->find();
                //if($now_group['spread_rate']&&$now_mer_pr['group_first_rate']&&C('config.group_first_rate')&&C('config.user_spread_rate')){
                if($now_group['spread_rate']>=0){
                    $first_rate=$now_group['spread_rate'];
                    $second_rate=$now_group['sub_spread_rate']>0?$now_group['sub_spread_rate']:0;
                    $third_rate=$now_group['third_spread_rate']>0?$now_group['third_spread_rate']:0;
                    $rate_type='group';
                }elseif($now_mer_pr['group_first_rate']>=0&&$now_mer_pr['group_first_rate']!=''){
                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'mer_type',$uid);  
                    $first_rate= $percent !== '' ? $percent : $now_mer_pr['group_first_rate'];
                    $second_rate=$now_mer_pr['group_second_rate']>0?$now_mer_pr['group_second_rate']:0;
                    $third_rate=$now_mer_pr['group_third_rate']>0?$now_mer_pr['group_third_rate']:0;
                    $rate_type='merchant';
                }elseif(C('config.group_first_rate')>=0 || C('config.group_second_rate')>=0 || C('config.group_third_rate')>=0){
//                    $first_rate=C('config.group_first_rate');

                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'sys_type',$uid);
                    $first_rate= $percent !== '' ? $percent : (C('config.group_first_rate')>=0?C('config.group_first_rate'):$systemFirstPercent);
                    $second_rate=C('config.group_second_rate')>=0?C('config.group_second_rate'):C('config.user_first_spread_rate');
                    $third_rate=C('config.group_third_rate')>=0?C('config.group_third_rate'):C('config.user_second_spread_rate');
                    $rate_type='system_group';
                }elseif(C('config.user_spread_rate')>=0){
                    $first_rate = $systemFirstPercent;
                    $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
                    $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
                    $rate_type='system';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }
                //  }
                break;
            case 'shop':
            case 'mall':
                //$type='shop';   //升级商城用户分佣后去除
                $goods_list = M('Shop_order')->field('d.id,d.goods_id,d.price,d.discount_price,d.num,g.third_spread_rate,g.sub_spread_rate,g.spread_rate,o.payment_money,o.balance_pay,o.freight_charge')->join('as o left join '.C('DB_PREFIX').'shop_order_detail as d ON o.order_id = d.order_id left join '.C('DB_PREFIX').'shop_goods as g ON d.goods_id = g.goods_id')->where(array('o.order_id'=>$group_id))->select();
                if (C('config.shop_goods_spread_edit')==1 && $group_id) {//快店商品推广佣金获得单独设置
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    //商品分佣
                    $goods_percent=1;
                    foreach ($goods_list as $_good) {
                        // 已退款商品不分佣
                        if ($_good['refundNum']) {
                            $_good['num'] = $_good['num']-$_good['refundNum'];
                            if ( $_good['num']<=0) {
                                continue;
                            }
                        }
                        $total_money = $_good['balance_pay'] + $_good['payment_money'];
                        if($_good['spread_rate']>=0){
                            $goods_toal_price=$_good['price']*$_good['num'];
                            $first_money=round($goods_toal_price*($_good['spread_rate']/100),2);
                            $second_money=$_good['sub_spread_rate']>0?round($goods_toal_price*($_good['sub_spread_rate']/100),2):0;
                            $third_money=$_good['third_spread_rate']>0?round($goods_toal_price*($_good['third_spread_rate']/100),2):0;
                        }else{  //存在未设置商品分佣的跳出商品分佣
                            $goods_percent=0;
                            break;
                        }
                        $first_rate += $first_money;
                        $second_rate += $second_money;
                        $third_rate += $third_money;
                    }
                    $rate_type=$type;
                }
                /*不走商品分佣使用系统的*/
                if($goods_percent!=1){
                    if($now_mer_pr[$type.'_first_rate']>=0&&$now_mer_pr[$type.'_first_rate']!=''){
                        $percent = $this->get_use_spread_percent_detail($mer_id,$type,'mer_type',$uid);  
                        $first_rate=  $percent !== '' ? $percent : $now_mer_pr[$type.'_first_rate'];
                        $second_rate=$now_mer_pr[$type.'_second_rate']>0?$now_mer_pr[$type.'_second_rate']:0;
                        $third_rate=$now_mer_pr[$type.'_third_rate']>0?$now_mer_pr[$type.'_third_rate']:0;
                        $rate_type='merchant';
                    }elseif((C('config.'.$type.'_first_rate')>=0 || C('config.'.$type.'_second_rate')>=0 || C('config.'.$type.'_third_rate')) && (C('config.'.$type.'_first_rate')!='' || C('config.'.$type.'_second_rate')!='' || C('config.'.$type.'_third_rate')!='')){

                        $percent = $this->get_use_spread_percent_detail($mer_id,$type,'sys_type',$uid);  
                        $first_rate= $percent !== '' ? $percent : (C('config.'.$type.'_first_rate')>=0?C('config.'.$type.'_first_rate'):$systemFirstPercent);;
                        $second_rate=C('config.'.$type.'_second_rate')>=0?C('config.'.$type.'_second_rate'):C('config.user_first_spread_rate');
                        $third_rate=C('config.'.$type.'_third_rate')>=0?C('config.'.$type.'_third_rate'):C('config.user_second_spread_rate');
                        $rate_type='system_'.$type;
                    }elseif(C('config.user_spread_rate')>=0){
                        $first_rate=$systemFirstPercent;
                        $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
                        $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
                        $rate_type='system';
                    }else{
                        $first_rate=0;
                        $second_rate=0;
                        $third_rate=0;
                        $rate_type='';
                    }
                }
                break;
            default:
//                if($now_mer_pr){
//                    if($now_mer_pr[$type.'_first_rate']>=0){
//                        $first_rate=$now_mer_pr[$type.'_first_rate'];
//                        $second_rate=$now_mer_pr[$type.'_second_rate']>0?$now_mer_pr[$type.'_second_rate']:0;
//                        $third_rate=$now_mer_pr[$type.'_third_rate']>0?$now_mer_pr[$type.'_third_rate']:0;
//                        $rate_type='merchant';
//                    }elseif(C('config.'.$type.'_first_rate')>=0){
//                        $first_rate=C('config.'.$type.'_first_rate');
//                        $second_rate=C('config.'.$type.'_second_rate')>0?C('config.'.$type.'_second_rate'):0;
//                        $third_rate=C('config.'.$type.'_third_rate')>0?C('config.'.$type.'_third_rate'):0;
//                        $rate_type='system_'.$type;
//                    }elseif(C('config.user_spread_rate')>=0){
//                        $first_rate=C('config.user_spread_rate');
//                        $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
//                        $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
//                        $rate_type = 'system';
//                    }else{
//                        $first_rate=0;
//                        $second_rate=0;
//                        $third_rate=0;
//                        $rate_type = '';
//                    }
//                }else{
//                    $first_rate=C('config.user_spread_rate');
//                    $second_rate=C('config.user_first_spread_rate');
//                    $third_rate=C('config.user_second_spread_rate');
//                    $rate_type = 'system';
//                }
                $openid = '';
                $openid = $now_order['openid'] ?? '';
                if($now_mer_pr && $now_mer_pr[$type.'_first_rate']>=0 && $now_mer_pr[$type.'_first_rate'] !== ""){
                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'mer_type',$uid,$openid);  
                    $first_rate = $percent !== '' ? $percent : $now_mer_pr[$type.'_first_rate'];
                    $second_rate=$now_mer_pr[$type.'_second_rate']>0?$now_mer_pr[$type.'_second_rate']:0;
                    $third_rate=$now_mer_pr[$type.'_third_rate']>0?$now_mer_pr[$type.'_third_rate']:0;
                    $rate_type='merchant';
                }elseif(C('config.'.$type.'_first_rate')>=0 && C('config.'.$type.'_first_rate') !== "" || C('config.'.$type.'_second_rate')>=0 || C('config.'.$type.'_third_rate') >=0){   
                    $percent = $this->get_use_spread_percent_detail($mer_id,$type,'sys_type',$uid,$openid);
                    $first_rate =  $percent !== '' ? $percent : (C('config.'.$type.'_first_rate')>0?C('config.'.$type.'_first_rate'):$systemFirstPercent);
                    $second_rate=C('config.'.$type.'_second_rate')>0?C('config.'.$type.'_second_rate'):C('config.user_first_spread_rate');
                    $third_rate=C('config.'.$type.'_third_rate')>0?C('config.'.$type.'_third_rate'):C('config.user_second_spread_rate');
                    $rate_type='system_'.$type;
                }elseif(C('config.user_spread_rate')>=0 && C('config.user_spread_rate') !== ""){
                    $first_rate=$systemFirstPercent;
                    $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
                    $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
                    $rate_type = 'system';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type = '';
                }
                break;

        }

        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate,
            'goods_percent'=>$goods_percent,
            'type'=>$rate_type,
        );
    }


    //获取用户分佣积分比例
    public function get_user_score_spread_rate($mer_id,$type,$group_id=''){
        $first_rate='';
        $second_rate='';
        $third_rate='';
        $rate_type='';

        switch($type) {
            case 'group':
                $now_group = M('Group')->where(array('group_id'=>$group_id))->find();
                // 开启团购单个商品积分分佣
                if($now_group['score_spread_rate']>=0 && C('config.open_score_spread_rate')==1){
                    $first_rate=$now_group['score_spread_rate'];
                    $second_rate=$now_group['sub_score_spread_rate']>0?$now_group['sub_score_spread_rate']:0;
                    $third_rate=$now_group['third_score_spread_rate']>0?$now_group['third_score_spread_rate']:0;
                    $rate_type='group';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }
                break;
        }

        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate,
            'type'=>$rate_type,
        );
    }

    //用户奖励比例 依据分佣第一级比例
    public function get_user_award($type){
        if(C('config.'.$type.'_first_rate')>=0){
            $first_rate=C('config.'.$type.'_first_rate');
            $second_rate=C('config.'.$type.'_second_rate')>0?C('config.'.$type.'_second_rate'):0;
            $third_rate=C('config.'.$type.'_third_rate')>0?C('config.'.$type.'_third_rate'):0;
            $rate_type='system_'.$type;
        }elseif(C('config.user_spread_rate')>=0){
            $first_rate=C('config.user_spread_rate');
            $second_rate=C('config.user_first_spread_rate')>0?C('config.user_first_spread_rate'):0;
            $third_rate=C('config.user_second_spread_rate')>0?C('config.user_second_spread_rate'):0;
            $rate_type = 'system';
        }

        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate,
            'type'=>$rate_type,
        );
    }

    //获取快店每个商品分佣比例
    public function get_user_spread_rate_by_goods($goods_id=0,$type){
        // $where['mer_id'] = $mer_id;
        // $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();
        $first_rate=0;
        $second_rate=0;
        $third_rate=0;
        switch($type) {
            case 'shop':
            case 'mall':
                $now_goods = M('Shop_goods')->where(array('goods_id'=>$goods_id))->find();
                if (C('config.shop_goods_spread_edit')==1&&$goods_id) {//快店商品推广佣金获得单独设置
                    if($now_goods['spread_rate']>=0){
                        $first_rate=$now_goods['spread_rate'];
                        $second_rate=$now_goods['sub_spread_rate']>0?$now_goods['sub_spread_rate']:0;
                        $third_rate=$now_goods['third_spread_rate']>0?$now_goods['third_spread_rate']:0;
                    }
                    $rate_type=$type;
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                }
                break;
        }
        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate
        );
    }


    //积分最大使用量
    public function get_max_core_use($mer_id,$type,$money=0,$order_info=array()){
        $where['mer_id'] = $mer_id;
        $now_mer_pr = M('Merchant_percent_rate')->where($where)->find();

        //修正type参数值
        if($type == 'shop' && $order_info['order_from'] == 1){
            $type = 'mall';
        }

        $user_score_use_percent = C('config.user_score_use_percent');
        $use_condition =C('config.user_score_use_condition');
        //商城的去除配置的限制
        if(((C('config.shop_goods_score_edit')==1 && ($type=='shop'|| $type=='mall'))) && $order_info){
            if($order_info['total_money']<$use_condition){
                return  0;
            }
            $goods_list = $order_info['order_content'];
            $all_score_max = 0;
            foreach ($goods_list as $v) {
                if($v['score_max']>0){
                    $score_max_deducte = bcdiv($v['score_max'], $user_score_use_percent, 2);
                    if($score_max_deducte>$v['discount_price']){
                        $score_max_deducte = $v['discount_price'];
                        $v['score_max'] =  intval(round($score_max_deducte*$user_score_use_percent));
                    }
                    $order_info['total_money'] -= $score_max_deducte;
                    if($order_info['total_money']<$use_condition){
                        $tmp_score_deducte = $use_condition-$order_info['total_money'];
                        $tmp_score_max =  intval(round($tmp_score_deducte*$user_score_use_percent));
                        return $all_score_max + $tmp_score_max;
                    }
                    $all_score_max +=$v['score_max']*($v['num']??$v['min_num']);
                }
            }
            return $all_score_max;
        }else if ($now_mer_pr) {
            $oldType = $type;
            $type=($type=='mall')?'shop':$type;
            if(strpos($now_mer_pr['merchant_'.$type . '_score_max'],'%')){
                $now_mer_pr['merchant_'.$type . '_score_max'] = $user_score_use_percent*$money*floatval(str_replace('%','',$now_mer_pr['merchant_'.$type . '_score_max']))/100;
            }

            if(strpos($now_mer_pr['merchant_score_max'],'%')){
                $now_mer_pr['merchant_score_max'] = $user_score_use_percent*$money*floatval(str_replace('%','',$now_mer_pr['merchant_score_max']))/100;
            }

            if(strpos(C('config.' . $oldType . '_score_max'),'%')){
                C('config.' . $oldType . '_score_max',$user_score_use_percent*$money*floatval(str_replace('%','',C('config.' . $oldType . '_score_max')))/100);
            }

            if(strpos( C('config.user_score_max_use'),'%')){
                C('config.user_score_max_use',$user_score_use_percent*$money*floatval(str_replace('%','', C('config.user_score_max_use')))/100);
            }

            if ($now_mer_pr['merchant_'.$type . '_score_max'] >= 0 && $now_mer_pr['merchant_'.$type . '_score_max']!='') {
                return $now_mer_pr['merchant_'.$type . '_score_max'];
            } elseif ( $now_mer_pr['merchant_score_max'] >= 0 && $now_mer_pr['merchant_score_max']!='') {
                return $now_mer_pr['merchant_score_max'];
            } elseif (C('config.' . $oldType . '_score_max') >= 0 ) {
                return C('config.' . $oldType . '_score_max');
            } elseif ( C('config.user_score_max_use') >= 0) {
                return C('config.user_score_max_use');
            } else {
                return 0;
            }
        } else {
            if(strpos(C('config.' . $type . '_score_max'),'%')){
                C('config.' . $type . '_score_max',$user_score_use_percent*$money*floatval(str_replace('%','',C('config.' . $type . '_score_max')))/100);
            }

            if(strpos( C('config.user_score_max_use'),'%')){
                C('config.user_score_max_use',$user_score_use_percent*$money*floatval(str_replace('%','', C('config.user_score_max_use')))/100);
            }

            if (C('config.' . $type . '_score_max')!='' && C('config.' . $type . '_score_max') >= 0 ) {
                return C('config.' . $type . '_score_max');
            } elseif (C('config.user_score_max_use') >= 0) {
                return C('config.user_score_max_use');
            } else {
                return 0;
            }
        }
    }
    //元宝定制
    public function get_extra_money($order){
        switch($order['order_type']){
            case 'group':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'meal':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'shop':
            case 'mall':
            case 'shop_offline':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'appoint':

                if($order['paid'] == 3){
                    $total_money = $order['product_price'];
                }else{
                    if($order['is_initiative']==1){
                        //剩余钱的逻辑
                        if($order['product_id']){
                            //剩余钱的逻辑
                            $total_money = $order['product_balance_pay']+$order['user_pay_money']+$order['product_score_deducte']+$order['product_coupon_price'] + $order['product_payment_price'];

                        }else{
                            $total_money =$order['balance_pay'] + $order['pay_money'] + $order['product_balance_pay']+$order['user_pay_money']+$order['product_score_deducte']+$order['product_coupon_price'] + $order['product_payment_price'];
                        }
                    }else{
                        if($order['product_id']){
                            $money = $order['product_payment_price'];
                        }else{
                            $money = $order['payment_money'];
                        }
                        $total_money  = $money;
                    }

                }



                break;
            case 'store':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'cash':
                $total_money = $order['total_price'];
                break;
        }

        $now_merchant = M('Merchant')->where(array('mer_id'=>$order['mer_id']))->find();
        if($now_merchant['score_get']>=0){
            $score_percent = $now_merchant['score_get'];
        }else{
            $score_percent = C('config.user_score_get');
        }

        if($order['extra_price']>0){
            if($order['score_used_count']>0){
                if($order['score_used_count']<$order['extra_price']){
                    $give_money =$order['extra_price']-$order['score_used_count'];
                }else{
                    $give_money =0 ;
                }
            }else{
                $give_money =bcmul($total_money,$score_percent,2) ;
            }
        }else{
            $give_money =bcmul($total_money,$score_percent,2) ;
        }
        fdump_api(['get_extra_money' => [$order, $score_percent, $give_money]], 'shop_order/get_extra_money', 1);
        return $give_money;
    }

    public function shop_get_score($order){
        $where['o.order_id'] = $order['order_id'];
        $goods_list = M('Shop_order')->field('o.order_from,d.id,d.sell_type,d.code_weight,d.goods_weight,d.goods_id,g.score_max,d.price,g.score_percent,d.num,d.discount_price')->join('as o left join '.C('DB_PREFIX').'shop_order_detail as d ON o.order_id = d.order_id left join '.C('DB_PREFIX').'shop_goods as g ON d.goods_id = g.goods_id')->where($where)->select();
        $now_merchant = D('Merchant')->get_info($order['mer_id']);
        if($now_merchant['score_get_percent']>=0){
            $score_get_percent = $now_merchant['score_get_percent']/100;
        }else{
            if(C('config.open_score_get_percent')==1){
                $score_get_percent =C('config.user_score_get')/100;
            }else{
                $score_get_percent =  C('config.user_score_get');
            }
        }
        $all_score_get = 0;
        foreach($goods_list as $v){
            //线下零售修复称重商品数量num=0,出现num=0都是线下零售扫条形码的，条形码金额可以自定义无法换算成对应商品数量
            /*if ($v['sell_type'] == 2 && $v['order_from'] == 6 && $v['num'] == 0) {
                $v['num'] = $v['goods_weight'] != 0 ? intval($v['code_weight'] / $v['goods_weight']) : 0;
            }*/

            if($v['score_percent']!='' && $v['score_percent']!= '-1' && $v['score_percent']!='0'){
                if(strpos($v['score_percent'],'%')){
                    $score_get = round($v['discount_price']*floatval(str_replace('%','',$v['score_percent']))/100, 2)*$v['num'];
                }else{
                    $score_get = round($v['discount_price']*$v['score_percent'], 2)*$v['num'];
                }
            }else{
                $score_get = floatval($v['discount_price']*$score_get_percent*$v['num']);
            }
            $all_score_get+=$score_get;
        }
        return $all_score_get;
    }

    public function get_user_spead_money($uid,$mer_id,$order_type,$spread_money=0){
        $user = D('User')->get_user($uid);
        $spread_list = D('User_spread')->get_spread_list($uid);
        $spread_rate = $this->get_user_spread_rate($mer_id,$order_type,0,['uid'=>$uid]);
        $spread_money_array = array();
        foreach($spread_list as $key=>$v){
            if($key==1){
                $spread_money_array[$key] = round($spread_money * $spread_rate['first_rate'] / 100, 2);
            }else if($key==2){
                $spread_money_array[$key] = round($spread_money * $spread_rate['second_rate'] / 100, 2);
            }else if($key==3){
                $spread_money_array[$key] = round($spread_money * $spread_rate['third_rate'] / 100, 2);
            }
        }
        return $spread_money_array;
    }

    public function get_percent_money($order_type,$pay_money,$order_info){
        if($order_type=='group'){
            $percent = D('Percent_rate')->get_percent($order_info['mer_id'],$order_info['order_type'],$pay_money,$order_info['group_id'],$order_info['store_id'], true);
        } elseif($order_type=='meal'&&$order_info['order_from']==1){
            $percent = D('Percent_rate')->get_percent($order_info['mer_id'],'meal_scan',$pay_money, '',$order_info['store_id'], true);
        } elseif($order_type=='market'){
            $percent = D('Percent_rate')->get_market_percent($order_info['mer_id']);
        } elseif($order_type=='marketcancel'){
            $percent = 0;
        } elseif($order_type=='sub_card') {
            $percent = $order_info['percent'];
        } else {
            $percent = D('Percent_rate')->get_percent($order_info['mer_id'],$order_info['order_type'],$pay_money, '',$order_info['store_id'], true);
        }
        $percent = floatval($percent);
        return ($pay_money*$percent/100);
    }

    public function get_user_add_score_times($score,$uid){
        $now_user = D('User')->get_user($uid);
        $today_md = date('m-d');
        $now_year = date('Y').'-';

        $today_sec = strtotime($now_year.$today_md);
        if( C('config.vip_day')>=0 && C('config.vipday_score_times')>0){
            if (C('config.vip_day_type') == 1) { // 每周几积分倍数获取形式
                $today_week = date('w');
                if ($today_week == C('config.vip_day')) {
                    $score = $score*C('config.vipday_score_times');
                }
            }elseif (C('config.vip_day_type') == 2) { // 每月几号积分倍数获取形式
                $today_day = date('d');
                if ($today_day>9) {
                    $today_day = substr($today_day, -1);
                }
                if ($today_day == C('config.vip_day')) {
                    $score = $score*C('config.vipday_score_times');
                }
            }
        }

        if(date('m-d',strtotime($now_user['birthday']))==$today_md && C('config.birthday_score_times')>0){
            $score = $score*C('config.birthday_score_times');
        }

        $spec = M('Special_day_score_times')->order('value DESC')->select();
        foreach ($spec as $v) {
            if($today_sec >= strtotime($now_year.$v['start']) &&  $today_sec<=strtotime($now_year.$v['end'])){
                $score = $score*$v['value'];
                break;
            }
        }
        return $score;
    }


    //记录积分分佣日志
    public function add_spread_jifen_log($param=null,$order_type='',$third_id=0){
        if(empty($param)){
            return false;
        }

        $spread_total_money=$param['spread_total_money'];
        if($spread_total_money<=0){
            return false;
        }

        $now_time=time();
        $data=array();
        $arr=array();
        $arr['order_id']=$param['order_id'];
        $arr['get_uid']=$param['get_uid'];
        $arr['order_type']=$order_type;
        $arr['spread_total_money']=$spread_total_money;

        $spread_users=array();
        $spread_users[]=$param['get_user'];
        //上一级分佣
        $first_jifen_percent=C('config.first_jifen_percent');
        if(!empty($param['first_uid']) && $first_jifen_percent>0  && !in_array($param['first_uid'],$spread_users)){
            $first_jifen=floor(($spread_total_money*$first_jifen_percent)/100);
            if($first_jifen>0){
                $arr['jifen']=$first_jifen;
                $arr['uid']=$param['first_uid'];
                $arr['spread_uid']=0;
                $arr['third_id']=$third_id;
                $arr['status']=0;
                $arr['add_time']=$now_time;
                $arr['update_time']=$now_time;
                $data[]=$arr;
                $spread_users[]=$param['first_uid'];
            }
        }elseif(empty($param['first_uid'])){
            if(!empty($data)){
                M('spread_jifen_list')->addAll($data);
            }
            return false;
        }
        //上二级分佣
        $second_jifen_percent=C('config.second_jifen_percent');
        if(!empty($param['second_uid']) && $second_jifen_percent>0 && !in_array($param['second_uid'],$spread_users)){
            $second_jifen=floor(($spread_total_money*$second_jifen_percent)/100);
            if($second_jifen>0){
                $arr['jifen']=$second_jifen;
                $arr['uid']=$param['second_uid'];
                $arr['spread_uid']=$param['first_uid'];
                $arr['third_id']=$third_id;
                $arr['status']=0;
                $arr['add_time']=$now_time;
                $arr['update_time']=$now_time;
                $data[]=$arr;
                $spread_users[]=$param['second_uid'];
            }
        }elseif(empty($param['second_uid'])){
            if(!empty($data)){
                M('spread_jifen_list')->addAll($data);
            }
            return false;
        }
        //上三级分佣
        $third_jifen_percent=C('config.third_jifen_percent');
        if(!empty($param['third_uid']) && $third_jifen_percent>0  && !in_array($param['third_uid'],$spread_users)){
            $third_jifen=floor(($spread_total_money*$third_jifen_percent)/100);
            if($third_jifen>0){
                $arr['jifen']=$third_jifen;
                $arr['uid']=$param['third_uid'];
                $arr['spread_uid']=$param['second_uid'];
                $arr['third_id']=$third_id;
                $arr['status']=0;
                $arr['add_time']=$now_time;
                $arr['update_time']=$now_time;
                $data[]=$arr;
                $spread_users[]=$param['third_uid'];
            }
        }elseif(empty($param['third_uid'])){
            if(!empty($data)){
                M('spread_jifen_list')->addAll($data);
            }
            return false;
        }
    }

    /**
     * 获取新大陆POS机抽成比例
     * @param $mer_id
     */
    public function get_newlandpos_rate($mer_id)
    {
        $percent = C('config.platform_get_newlandpos_percent') ?: 0;
        if ($mer_id > 0) {
            $merchant_percent = M('Merchant_percent_rate')->where(['mer_id' => $mer_id])->getField('newlandpos_percent');
            if ($merchant_percent > 0) {
                $percent = $merchant_percent;
            }
        }
        return $percent;
    }

    public function scorePercent(string $goodsType, int $goodsId, int $merId)
    {
        $type = [
            'group'   => 'Group',
            'mall'    => 'Mall_goods',
            'appoint' => 'Appoint',
            'shop'    => 'Shop_goods'
        ];

        if(!isset($type[$goodsType])){
            throw_exception(L_('商品类型错误'));
        }
        $scorePercent = D($type[$goodsType])->getGoodsScore($goodsId);
        if($scorePercent == -1){//-1 不使用单品设置积分比例
            $merchantPercent = D('Merchant')->where(['mer_id' => $merId])->getField('score_get_percent');
            $merchantPercent = floatval($merchantPercent);
            if($merchantPercent >= 0){//使用商家设置积分比例
                $scorePercent = $merchantPercent/100;
            }else{
                if(C('config.open_score_get_percent') == 1){//平台开启积分比例
                    $scorePercent = C('config.score_get_percent')/100;
                }else{
                    $scorePercent = C('config.user_score_get');
                }
            }
        }

        return $scorePercent;
    }
};