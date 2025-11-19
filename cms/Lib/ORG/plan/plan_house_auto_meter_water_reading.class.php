<?php


class plan_house_auto_meter_water_reading extends plan_base
{
    public function runTask()
    {
        fdump_api(['进来了=='.__LINE__],'water_meter/start_water_reading',true);
        if(!C('config.customized_meter_reading')){
            return true;
        }
 
        invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/getMeterWaterReading');
        return true;
    }

    /**
     * 生成订单
     * @author lijie
     * @date_time 2021/08/06
     * @param int $pigcms_id
     * @param int $village_id
     * @param float $money
     * @param int $uid
     * @param $type
     * @return bool
     */
    public function all_go_pay($pigcms_id=0,$village_id=0,$money=0.00,$uid=0,$type='water')
    {
        $login_user_info = $this->get_user_village_info($pigcms_id, $village_id, $uid);//==>郭先森==>获取当前社区我的信息
        //家属 获取业主的欠费信息
        if ($login_user_info['parent_id']) {
            $now_user_info = $this->get_user_village_info($login_user_info['parent_id'], $village_id, $uid);
        } else {
            $now_user_info = $login_user_info;
        }
        $data_order['money'] = $money;
        // uid 和 bind_id 取业主
        $data_order['uid'] = $now_user_info['uid'];
        $data_order['bind_id'] = $now_user_info['pigcms_id'];
        // pay_uid 和 pay_bind_id 取当前支付人员信息
        $data_order['pay_uid'] = $uid;
        $data_order['pay_bind_id'] = $login_user_info['pigcms_id'];
        $data_order['village_id'] = $village_id;
        $data_order['time'] = time();
        $data_order['paid'] = 0;
        $data_order['order_type'] = $type;
        $data_order['order_name'] = '水费';
        $use_total_score_count = 0;
        $generate_integral = 0;
        $userinfo = D('User')->get_user($uid);
        $property_info = D('House_village_pay_order')->get_score($data_order, $userinfo);
        $use_total_score_count += $property_info['use_total_score_count'];
        $generate_integral += $property_info['generate_integral'];
        $data_order['score_can_use'] = $property_info['use_total_score_count'];
        $data_order['score_can_pay'] = $property_info['use_total_money'];
        $data_order['score_percent'] = $property_info['score_percent'];
        $data_order['score_can_get'] = $property_info['generate_integral'];
        $order_id = D('House_village_pay_order')->data($data_order)->add();
        $score_data['score'] = $use_total_score_count;
        $score_data['desc'] = "缴纳缴纳总费用" . $money . C('config.Currency_txt') . " 支出积分";
        $score_data['generate_integral'] = $generate_integral;
        $score_data['generate_integral_desc'] = "缴纳总费用" . I('money') . C('config.Currency_txt') . " 增加积分";
        $now_order = D('House_village_pay_order')->field(true)->where(array('order_id' => $order_id))->find();
        $pay_order_param = array(
            'business_type' => 'house_village_pay',
            'business_id' => $now_order['order_id'],
            'order_name' => $now_order['order_name'],
            'uid' => $now_order['uid'],
            'total_money' => $now_order['money'],
            'wx_cheap' => 0,
        );
        $pay_order_param['is_own'] = 4;
        $plat_order_result = D('Plat_order')->add_order($pay_order_param);
        if ($plat_order_result['error_code']) {
            return ['error'=>1];
        }
        return ['error'=>0,'order_id'=>$order_id,'plat_order_info'=>$pay_order_param];
    }

    public function after_pay($order_id,$plat_order_info,$uid,$money,$order_type)
    {
        $order_info = D('House_village_pay_order')->get_one($order_id);
        if($order_info['village_id']){
            $e_config = M('House_e_config')->where(array('village_id'=>$order_info['village_id']))->find();
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }
        $now_order = D('House_village_pay_order')->get_one($order_id);
        $date_order['pay_time'] = time();
        $date_order['paid'] = 1;
        $date_order['score_used_count'] = 0;
        $date_order['score_deducte'] = 0;
        $user = D('User')->where(array('uid'=>$uid))->find();
        $scroe = D('House_village_pay_order')->get_score($now_order,$user,false);
        $score_can_get = $scroe['generate_integral'];
        $date_order['score_can_get'] = $score_can_get;
        D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
        $tmp_order = $now_order;
        $tmp_order['is_own'] = $plat_order_info['is_own'];
        $tmp_order['score_used_count'] = $date_order['score_used_count'] ? $date_order['score_used_count'] : 0;
        $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
        $tmp_order['payment_money'] = 0;
        $tmp_order['score_deducte'] = 0;
        $tmp_order['balance_pay'] = $money;
        $rate = 0.05;
        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
        switch($order_type){
            case 'water':
                $bind_field = 'water_price';
                $tmp_order['desc'] = '用户交水费';
                $where['water_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            case 'electric':
                $bind_field = 'electric_price';
                $tmp_order['desc'] = '用户交电费';
                $where['electric_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            case 'gas':
                $bind_field = 'gas_price';
                $tmp_order['desc'] = '用户交燃气费';
                $where['gas_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            default:
                $bind_field = '';
                $tmp_order['desc']= $plat_order_info['order_name'];
        }
        fdump_api(['参数=='.__LINE__,$order_id,$plat_order_info,$uid,$money,$order_type,$date_order,$now_user_info,$bind_field],'water_meter/after_pay',true);

        M('House_village_pay_order')->where(array('order_id'=>$order_id))->save($date_order);

        if(!empty($bind_field)){
            $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
            if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
            }else{
                $data_bind[$bind_field] = 0;
            }
            $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
            fdump_api(['编辑参数=='.__LINE__,$now_order,$data_bind],'water_meter/after_pay',true);
            D('House_village_user_bind')->data($data_bind)->save();

        }
        $tmp_order['order_type'] = 'village_pay';
        if($plat_order_info['is_own']==0)
            $plat_order_info['is_own']+=4;
        //社区对账
        $res = D('SystemBill')->bill_method($plat_order_info['is_own'],$tmp_order);

        if(!$res['error_code']){
            M('House_village_pay_order')->where(array('order_id'=>$order_id))->setField('is_pay_bill',2);
            M('House_village_pay_order')->where(array('order_id'=>$now_order['order_id']))->setField('is_pay_bill',2);
        }
        $now_user = D('User')->get_user($now_order['uid']);
        $village_info = D('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
        if(!empty($now_user['openid'])){
            if ($order_info['order_type']=='pile'){
                $href=get_base_url('pages/village/smartCharge/historyOrderDetails');
            }else{
                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$now_order['village_id'];
            }
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 缴费成功提醒', 'keynote1' =>$now_order['order_name'], 'keynote2' =>'物业号 '. $now_user_info['usernum'], 'remark' => '缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
        }
        $village_bind = D('House_village_user_bind')->where(array('pigcms_id'=>$now_order['bind_id']))->find();
        if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
            $address = D('House_village')->get_single_floor_room($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
            if ($address) {
                $village_bind['address'] = $address;
            }
            $first =  ' 业主 '.$village_bind['name'].'( '.$village_bind['address'].' )缴费成功提醒';
            $keynote2 = ' 业主 '.$village_bind['name'].'( '.$village_bind['phone'].' )';
        } elseif ($now_order['car_number']) {
            $where_car = [];
            $where_car['_string'] = "concat(`province`,'',`car_number`)='{$now_order['car_number']}'";
            $infos_car_info = D('House_village_parking_car')->where($where_car)->field('car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
            if ($infos_car_info) {
                $name = $infos_car_info['car_user_name'];
                $address = $infos_car_info['car_user_phone'];
                $first =  ' 业主 '.$name.'( '.$address.' )缴费成功提醒';
                $keynote2 = ' 业主 '.$name.'( '.$address.' )';
            } else {
                $first =  ' 车辆 '.$now_order['car_number'].'缴费成功提醒';
                $keynote2 = '车辆('.$now_order['car_number'].')';
            }
        } else {
            $first =  $now_order['order_name'].'缴费成功提醒';
            $keynote2 = $now_order['order_name'];
        }
        if($village_info['openid']){
            $property_name = M('House_property')->where(['id'=>$village_info['property_id']])->getField('property_name');
            $village_info['property_name'] = $property_name;
            $href = "";
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $first, 'keynote1' =>$village_info['property_name'], 'keynote2' =>$keynote2, 'remark' => '缴费周期： '.($now_order['property_month_num'] ? $now_order['property_month_num'] : ($now_order['payment_paid_cycle'] ? $now_order['payment_paid_cycle'] : 0)).' 个月\n缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
        }
        //判断是否自动开具电子发票
        if($auto){
            $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $name = !empty($village_bind['name'])?$village_bind['name']:$now_order['order_name'];
            $res = D('House_village_pay_order')->addInvoice($name,$user['phone'],$order_no,$e_config['duty_paragraph'],$now_order['order_name'],$now_order['money'],$rate,$village_bind['property_id']);
            fdump($res,1,true);
            if($res['status'] == '0000'){
                D('House_village_pay_order')->addERecord($e_config['duty_paragraph'],$name,$now_order['money'],$user['phone'],$res['fpqqlsh'],$order_no,$now_order['order_name'],$order_id,$now_order['bind_id']);
            }
        }
    }

    protected function get_user_village_info($bind_id,$village_id,$uid, $is_my=false){
        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($bind_id);
        if(empty($now_user_info)){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        $database_house_village_user_bind = D('House_village_user_bind');
        $where['parent_id|pigcms_id'] = $bind_id;
        $where['uid'] = $uid;
        $where['village_id'] = $village_id;
        $house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
        if(!$house_village_user_bind_count){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        return $now_user_info;
    }

    public function curlGet($url){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

}