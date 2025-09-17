<?php
/*
    小区收费管理
 */
class CashierAction extends BaseAction{
    protected $village_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $this->role_id = $this->house_session['role_id']; //角色id
        $this->house_alias = D('House_village')->getHouseAlias(); //小区、楼栋、单元、房间别名
    }
    public $pay_list_type = array(
            'property'=>'物业费',
            'water'=>'水费',
            'electric'=>'电费',
            'gas'=>'燃气费',
            'park'=>'停车费',
            'custom'=>'其他缴费',
            'custom_payment'=>'自定义缴费',
    );


    /*-------------------------------------------------------收银台 start 20180615----------------------------------------------------------*/
    //水电燃气停车费 按照业主信息中来，物业费自定义费用按照订单来
    public function cashier(){
        //收银台-查看 权限
        if (!in_array(66, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_property = D('House_village_property');
        $where['village_id'] = $_SESSION['house']['village_id'];
        $where['status'] = 1;

        $list = $database_house_village_property->house_village_proerty_page_list($where , true , 'property_month_num desc' , 99999);

        if(!$list){
            $this->error_tips('数据处理有误！');
        }else{
            if($list['status']){
                $this->assign('list' , $list['list']);
            }else{
                $this->error_tips('请先添加缴费优惠。',U('Unit/preferential_add'));
            }
        }
        $see_qrcode_img = $this->config['site_url'] . "/index.php?g=Index&c=Recognition_wxapp&a=see_qrcode&type=cashier&img=1&id=".$_SESSION['house']['village_id'];
        $this->assign('qrcode',$see_qrcode_img);

        $this->display();
    }


    protected function get_wxapp_qrcode($path,$width){
        $wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
        $wxapp_access_token = $wxapp_access_token['access_token'];

        import('ORG.Net.Http');
        $http = new Http();
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$wxapp_access_token;
        $postData = array(
            'path'=>$path,
            'width'=>$width
        );
        $qrcode = $http->curlPostOwn($url,json_encode($postData));

        return $qrcode;
    }

    //已缴订单
    public function cashier_paid_list(){
        //已缴 权限
        if (!in_array(83, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition['name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'] ;
            } else if ($_GET['searchtype'] == 'usernum') {
                $condition['usernum'] =  trim($_GET['keyword']);
            }
        }

        if($_GET['searchstatus']){
            $condition['pay_type'] = $_GET['searchstatus'];
        }

        if ($_GET['begin_time']&&!$_GET['end_time']) {
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (pay_time > ".$period[0].")";
            $condition['pay_time_str']=$time_condition;
        }

        if (!$_GET['begin_time']&&$_GET['end_time']) {
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (pay_time < ".$period[1].")";
            $condition['pay_time_str']=$time_condition;
        }

        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $condition['pay_time_str']=$time_condition;
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $village_id = $this->village_id;

        //线下支付方式
        $pay_type_list = D('House_village_pay_type')->where(array('village_id'=>$village_id))->select();
        $this->assign('pay_type_list',$pay_type_list);

        if($village_id){
            // 历史缴费
            $condition['village_id'] = $village_id;

            $paid_list = D('House_village_pay_cashier_order')->get_limit_list_page($condition,20);
            $refund_cycle = D('House_village')->where(['village_id'=>$village_id])->getField('refund_cycle');
            $total = $totalMoney = 0;
            if($paid_list){
                $db_house_village_pay_order = D('House_village_pay_order');
                $park_system = D('Park_system')->where(['park_id' => $village_id])->find();
                foreach ($paid_list['order_list'] as $k=>&$v){
                    // 应缴金额
                    $whereTotalMoney = array('village_id'=>$village_id,'cashier_id'=>$v['cashier_id'],'order_status'=>1);
                    $total_money = $db_house_village_pay_order->field(' SUM(`money` ) AS totalMoney ')->where($whereTotalMoney)->find();
                    $v['total_money'] = $total_money['totalMoney'];
                    $totalMoney += $v['total_money'];
                    $total += $v['money'];                              //本页的总额
                    if ($pay_type_list) {
                        foreach ($pay_type_list as $key => $value) {
                            if ($v['pay_type'] == $value['id']) {
                                $v['pay_type_name'] ='线下支付-'. $value['name'];
                            }
                        }
                    }
                   $v['pay_type_name'] = $v['pay_type_name'] ? $v['pay_type_name'] : '扫码支付';
                    $timediff = time() - $v['pay_time'];
                    $days = intval($timediff / 86400);
                    if($days > $refund_cycle || $v['order_status'] == 2){
                        $paid_list['order_list'][$k]['is_refund'] = 0;
                    } else{
                        $paid_list['order_list'][$k]['is_refund'] = 1;
                    }
                    if ($v['order_status']==2) {
                        $order_status_txt = '已退款';
                        $order_status_title = '已退款';
                        if (isset($v['carSynStatus']) && $v['carSynStatus']==4) {
                            $order_status_txt = '已退款(同步退款成功)';
                        } elseif (isset($v['carSynStatus']) && $v['carSynStatus']==5) {
                            $order_status_txt = '已退款(同步退款失败)';
                            $order_status_title = isset($v['carSynRemarks'])?$v['carSynRemarks']:'已退款(同步退款失败)';
                        }
                        $paid_list['order_list'][$k]['order_status_txt'] = $order_status_txt;
                        $paid_list['order_list'][$k]['order_status_color'] = "red";
                        $paid_list['order_list'][$k]['order_status_title'] = $order_status_title;
                    } else {
                        $paid_list['order_list'][$k]['order_status_txt'] = "正常";
                        $paid_list['order_list'][$k]['order_status_color'] = "green";
                        $paid_list['order_list'][$k]['order_status_title'] = "正常";
                    }

                    $paid_list['order_list'][$k]['refundPark'] = 0;
                    if ($park_system&&$park_system['park_id'] && isset($v['order_status'])&&$v['order_status']==2) {
                        // 已退款查询下是否存在特殊情况
                        $wherePark = [
                            'village_id'=>$village_id,'cashier_id'=>$v['cashier_id'],'order_status'=>2,
                            'car_type' => 'stored_type','carSynStatus' => ['not in',[4,5]]
                        ];
                        $carSynParkCount = $db_house_village_pay_order->where($wherePark)->count();
                        if ($carSynParkCount&&$carSynParkCount>0) {
                            // ↑ 订单已经退款且没有同步到停车设备（当前只针对储值车） 可以再次同步
                            $paid_list['order_list'][$k]['refundPark'] = 1;
                        }
                    }
                }
            }
            $paid_list['total'] = $total;
            $paid_list['totalMoneyS'] = $totalMoney;
            $this->assign('paid_list',$paid_list);
        }
        //打印模板
        $database_house_village_print_template = D('House_village_print_template');
        $print_template = $database_house_village_print_template->where(array('village_id'=>$village_id))->select();
        $this->assign('print_template',$print_template);
        $this->assign('village_id',$village_id);
        $this->display();
    }

    //未缴 获得详情
    public function ajax_cashier_unpaid_detail(){
        //未缴 权限
        // if (!in_array(85, $this->house_session['menus'])) {
        //     $this->error('对不起，您没有权限执行此操作');
        // }

        $database_house_village_pay_order = D('House_village_pay_order');
        $village_id = $this->village_id;
        $pigcms_id = $_POST['pigcms_id']+0;

        if($village_id){
            $user_info = D('House_village_user_bind')->get_cashier_unpaid_list_new(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id),1,1);
            if(empty($user_info['list'][0])){
                $user_info['list'][0]['water_price'] = 0.00;
                $user_info['list'][0]['electric_price'] = 0.00;
                $user_info['list'][0]['gas_price'] = 0.00;
                $user_info['list'][0]['park_price'] = 0.00;
                $user_info['list'][0]['property_price'] = 0.00;
                $user_info['list'][0]['total'] = 0.00;
            }
            $user_info['list'][0]['cunstom_money']=round(floatval($user_info['list'][0]['cunstom_money']), 2);
            $user_info['list'][0]['property_price']=round(floatval($user_info['list'][0]['property_price']), 2);

            exit(json_encode(array('status'=>0,'data'=>$user_info['list'][0])));
        }
    }

    // 添加缴费订单
    public function owner_order_add(){
        //收银台-查看 权限
        if (!in_array(67, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){

            $type = $_POST['type'];
//            $bind_where['usernum'] = $_POST['usernum'];
            $bind_where['_string'] = "usernum='{$_POST['usernum']}' OR bind_number='{$_POST['usernum']}'";
            $database_house_village_user_bind = D('House_village_user_bind');
            $now_bind_info         = $database_house_village_user_bind->where($bind_where)->find();
            $now_village = D('House_village')->get_one($now_bind_info['village_id']);

            if (!$now_bind_info || !$now_bind_info['vacancy_id']) {
                $this->error('该物业编号不存在！');
            }

            if($type=='property') {
                $database_house_village_property  = D('House_village_property');
                $database_house_village_floor     = D('House_village_floor');
                $now_floor_info = $database_house_village_floor->get_floor_info($now_bind_info['floor_id']);

                $property_where['id'] = $_POST['property_id'] + 0;
                $now_property_info    = $database_house_village_property->house_village_property_detail($property_where);
                $now_property_info    = $now_property_info['detail'];

                if (!$now_property_info) {
                    $this->error('物业缴费周期不存在！');
                }

                //算物业费
                $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_bind_info['vacancy_id']])->find();
                $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$bind_info['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
                if(!$standard_id['standard_id']){
                    $total_price['all_price'] = 0;
                }else{
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $total_price = $this->get_all_fee(unserialize($date['date_month']),$bind_info['housesize'],strtotime(date('Y-m',time())),$now_property_info['property_month_num']);
                }

                $data['order_name']         = '物业费';
                $data['order_type']         = 'property';
                $data['village_id']         = $this->house_session['village_id'];
                $data['time']               = time();
                $data['property_month_num'] = $now_property_info['property_month_num'];
                $data['floor_type_name']    = $now_floor_info['name'] ? $now_floor_info['name'] : '';
                $data['house_size']         = $now_bind_info['housesize'];
                $data['bind_id']            = $now_bind_info['pigcms_id'];
                $data['uid']                = $now_bind_info['uid'];
                $data['diy_type']           = $now_property_info['diy_type'];
                if ($now_property_info['diy_type'] > 0) {
                    $data['diy_content'] = $now_property_info['diy_content'];
                } else {
                    $data['presented_property_month_num'] = $now_property_info['presented_property_month_num'] ? $now_property_info['presented_property_month_num'] : 0;
                }
                $data['money']        = $total_price['all_price'];
                $data['property_fee'] = $total_price['price']?$total_price['price']:0.00;
                $data['remarks'] = $_POST['remarks'];
                if($now_bind_info['property_endtime'] < time()) {
                    $data['is_owe'] = 1;
                }
                $order_id = D("House_village_pay_order")->add($data);
                if ($order_id) {
                    $this->success('添加成功');
                } else {
                    $this->error('订单创建失败，请重试');
                }
            }elseif($type=='park'){
                $database_house_village_parking_car = D('House_village_parking_car');
                $car_time = $database_house_village_parking_car->where(['car_id'=>$_POST['car_number']])->field('end_time')->find();
                $park_data = array();
                $park_data['order_name']         = '收银台：停车费';
                $park_data['order_type']         = 'park';
                $park_data['uid'] = $now_bind_info['uid'];
                $park_data['bind_id'] = $now_bind_info['pigcms_id'];
                $park_data['village_id']         = $this->house_session['village_id'];
                $park_data['money'] = $_POST['park_price'] ;
                $park_data['time']               = time();
                $park_data['score_can_use']               = $_POST['park_price']*100;
                $park_data['score_can_pay']               = $_POST['park_price'];
                $park_data['score_can_get']               = 0;
                $park_data['score_percent']               = 100;
                $park_data['house_size']         = $now_bind_info['housesize'];
                $park_data['car_type'] = $_POST['park_type'];
                $park_data['car_id'] = $_POST['car_number'];
                $park_data['presented_property_month_num'] = $_POST['presented_property_month_num'];
                $park_data['property_month_num'] = $_POST['property_month_num'];
                if($_POST['park_type'] == 'month_type'){
                    $num = ($_POST['property_month_num']+$_POST['presented_property_month_num']);
                    $park_data['month_end_time'] = strtotime('+'.$num.' month',strtotime(date('Y-m-d 23:59:59',$car_time['end_time'])));
                }
                $park_data['remarks'] = $_POST['remarks'];
                $order_id = D("House_village_pay_order")->add($park_data);
                //D("House_village_user_bind")->where(['pigcms_id'=>$now_bind_info['pigcms_id']])->setInc('park_price',$_POST['park_price']);
                if ($order_id) {
                    $this->success('添加成功');
                } else {
                    $this->error('订单创建失败，请重试');
                }
            } else{

                switch($type){
                    case 'water':
                        if(empty($now_village['water_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳水费');
                        $pay_money = $now_bind_info['water_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_water` AS `use`,`water_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用水 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '水费';
                        break;
                    case 'electric':
                        if(empty($now_village['electric_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳电费');
                        $pay_money = $now_bind_info['electric_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_electric` AS `use`,`electric_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用电 '.floatval($value['use']).' 千瓦时(度)，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '电费';
                        break;
                    case 'gas':
                        if(empty($now_village['gas_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳燃气费');
                        $pay_money = $now_bind_info['gas_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_gas` AS `use`,`gas_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '使用燃气 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '燃气费';
                        break;
                    case 'park':
                        if(empty($now_village['park_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳停车费');
                        $pay_money = $now_bind_info['park_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`park_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '停车费 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '停车费';
                        break;
                    case 'custom_payment':
                        $pay_money = $_POST['payment_price'];
                        $data_order['order_name'] = $_POST['payment_name'];
                        $data_order['payment_paid_cycle'] = $_POST['payment_paid_cycle'];
                        $data_order['payment_bind_id'] = $_POST['payment_bind_id'];
                        break;
                    case 'custom':
                        $pay_money = $_POST['custom_price'];
                        $data_order['order_name'] = '自定义缴费【'.$_POST['custom_remark'].'】';
                        break;
                }

                $data_order['money'] = $pay_money ;
                $data_order['uid'] = $now_bind_info['uid'];
                $data_order['bind_id'] = $now_bind_info['pigcms_id'];
                $data_order['village_id'] = $now_village['village_id'];
                $data_order['time'] = $_SERVER['REQUEST_TIME'];
                $data_order['paid'] = 0;
                $data_order['order_type'] = $type;
                $data_order['remarks'] = $_POST['remarks'];

                if($order_id = D('House_village_pay_order')->data($data_order)->add()){
                    $this->success('添加成功');
                }else{
                    $this->error('下单失败，请重试');
                }
            }
        }else{
            $database_house_village_property = D('House_village_property');
            $where['village_id'] = $_SESSION['house']['village_id'];
            $where['status'] = 1;

            //收银台过来
            if (!$_GET['pigcms_id']) {
                $this->error('数据处理有误！');
            }

            $user_info = D('House_village_user_bind')->get_one_by_bindId($_GET['pigcms_id']);
            $this->assign('user_info' , $user_info);
            $this->assign('pigcms_id' , $_GET['pigcms_id']);

            $is_property = D('House_village_property_standard_bind')->where(['pigcms_id'=>$user_info['vacancy_id'],'is_del'=>1])->find();
            if($is_property)
                $this->assign('is_property' , 1);
            else
                $this->assign('is_property' , 0);

            $config = D('House_village_park_config')->getConfig(['village_id'=>$_SESSION['house']['village_id']],'is_park_month_type,is_temporary_park_type,park_versions,visitor_money');

            $car_id = D('House_village_bind_car')->field('car_id')->where(['user_id'=>$_GET['pigcms_id']])->select();
            if(empty($config['park_versions']) || empty($car_id) || $config['park_versions'] == 1 || ($config['is_park_month_type'] == 0 && $config['is_temporary_park_type'] == 0)){
                $this->assign('is_parking',0);
            } else{
                $this->assign('is_parking',1);
                $this->assign('visitor_money',$config['visitor_money']);
                $park_config[] = $config['is_park_month_type'];
                $park_config[] = $config['is_temporary_park_type'];
                $this->assign('park_config',$park_config);
                $car = '';
                foreach ($car_id as $k=>$v){
                    $car .= $v['car_id'].',';
                }
                $car = rtrim($car,',');
                $where_car['car_id'] = array('in',$car);
                $car_lists = D('House_village_parking_car')->get_car_lists($where_car,'car_id,car_number,province');
                $this->assign('car_lists',$car_lists);
            }


            $list = $database_house_village_property->house_village_proerty_page_list($where , true , 'property_month_num desc' , 99999);

            if(!$list){
                $this->error('数据处理有误！');
            }else{
                if($list['status']){
                    $this->assign('list' , $list['list']);
                }else{
                    $this->error('请先添加缴费优惠。',U('Unit/preferential_add'));
                }
            }
            $this->display();
        }
    }

    public function get_fee()
    {
//        $bind_where['usernum'] = $_POST['usernum'];
        $bind_where['_string'] = "usernum='{$_POST['usernum']}' OR bind_number='{$_POST['usernum']}'";
        $database_house_village_user_bind = D('House_village_user_bind');
        $now_bind_info         = $database_house_village_user_bind->where($bind_where)->find();
        exit(json_encode(array('status'=>1,'fee'=>$now_bind_info)));
    }

    public function get_property()
    {
        if(empty($_POST['property_id']) || empty($_POST['usernum'])){
            $this->error('参数错误！');
        }
        $database_house_village_property  = D('House_village_property');
        $property_where['id'] = $_POST['property_id'] + 0;
        $now_property_info    = $database_house_village_property->house_village_property_detail($property_where);
        $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->find();
        $now_property_info    = $now_property_info['detail'];
        if (!$now_property_info || !$user_bind_info['vacancy_id']) {
            $this->error('物业缴费周期不存在！');
        }
        $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$user_bind_info['vacancy_id']])->find();
        $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$bind_info['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
        if(!$standard_id['standard_id']){
            $total_price['all_price'] = 0;
        }else{
            $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
            $total_price = $this->get_all_fee(unserialize($date['date_month']),$bind_info['housesize'],strtotime(date('Y-m',time())),$now_property_info['property_month_num']);
        }
        $now_property_info['total_price'] = $total_price['all_price'];
        $start_time = $user_bind_info['property_starttime']?$user_bind_info['property_starttime']:time();
        $end_time = $user_bind_info['property_endtime']?$user_bind_info['property_endtime']:time();
        $now_property_info['property_time_str'] = date('Y-m-d',$start_time) . '至' . date('Y-m-d',$end_time);
        exit(json_encode(array('status'=>1,'now_property_info'=>$now_property_info)));
    }

    public function get_payment()
    {
        if(empty($_POST['pigcms_id'])){
            $this->error('参数错误！');
        }
        $time = M('House_village_payment_standard_bind')
            ->join('AS b left join ' . C('DB_PREFIX') . 'house_village_payment p on p.payment_id =b.payment_id')
            ->field('b.start_time,b.end_time')
            ->where(['b.pigcms_id'=>$_POST['pigcms_id'],'p.payment_name'=>$_POST['order_name']])
            ->order('b.bind_id DESC')
            ->limit(1)
            ->find();
        $arr['info'] = date('Y-m-d',$time['start_time']) . '至' . date('Y-m-d',$time['end_time']);
        exit(json_encode(array('status'=>1,'payment'=>$arr)));
    }

    /**
     * 获取预缴物业费的费用
     * @author lijie
     * @date_time 2020/07/27
     * @param $chargingStandard
     * @param $housesize
     * @param $month
     * @param $num
     * @return float|int
     */
    public function get_all_fee($chargingStandard,$housesize,$month,$num)
    {
        foreach ($chargingStandard as $value){
            if(strtotime($value['date_month']) <= $month){
                $price = $value['price'];
            }
        }
        $property_price = $price*$housesize*$num;
        return ['all_price'=>$property_price,'price'=>$price];
    }

    public function ajax_user_list(){
        if(IS_AJAX){
            $find_type = $_POST['find_type'];
            $find_value = $_POST['find_value'];

            if ($find_value) {
                if ($find_type == 1) {
                    $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                } else if ($find_type == 2) {
                    $where['phone'] = array('like', '%' . $find_value . '%');
                } else if ($find_type == 3) {
//                    $where['usernum'] = array('like', '%' . $find_value . '%');
                    $where['_string'] = "usernum LIKE '%{$find_value}%' OR bind_number LIKE '%{$find_value}%'";
                }
            }
            $where['status'] = 1;
            if($_POST['usernum']){
//                $where['usernum'] = $_POST['usernum'];
                $where['_string'] = "usernum='{$_POST['usernum']}' OR bind_number='{$_POST['usernum']}'";
            }
            if($_POST['room_addrss']){
                $where['room_addrss'] = array('like', '%' . $_POST['room_addrss'] . '%');
            }
            if($_POST['pigcms_id']){
                $where['pigcms_id'] = array('like', '%' . $_POST['pigcms_id'] . '%');
            }
            if($_POST['type']){
                $type='true';
            }

            $village_id = $this->village_id;
            if (empty($where)) {
                $user_list = D('House_village_user_bind')->get_limit_list_page($village_id);
            } else {
                $user_list = D('House_village_user_bind')->get_limit_list_page($village_id, 20, $where,$type);
            }

            $user_list = $user_list['user_list'];

             $cycle_type_china = array(
                    'Y'=>'年',
                    'M'=>'月',
                    'D'=>'日',
                );

            foreach ($user_list as $key => $value) {
                $payment_list = array();
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                    C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                    C('DB_PREFIX').'house_village_payment'=>'p'))
                    ->where("psb.pigcms_id= '".$value['pigcms_id']."' AND psb.village_id= '".$village_id."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();

                $payment_list = $payment_list ? $payment_list : array();

                // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$value['pigcms_id']));
                //$payment_list = array_merge($payment_list, $position_payment_list);
                $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
                foreach ($payment_list as $kk => $vv) {
                    if ($vv['cycle_sum'] - $vv['paid_cycle'] <= 0) {
                       unset($payment_list[$kk]);
                       continue;
                    }
                    $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);
                    // 计算结束时间
                    $vv['end_time'] = strtotime(date('Y-m-d',$vv['start_time']).'+'.$vv['cycle_sum']*$vv['pay_cycle'].$cycle_type[$vv['cycle_type']])-1;

                    $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
                    $payment_list[$kk]['cycle_type'] = $cycle_type_china[$vv['cycle_type']];
                    if ($vv['garage_num']) {
                        $payment_list[$kk]['payment_name'] = $vv['payment_name'].'('.$vv['garage_num'].'-'.$vv['position_num'].')';
                    }
                }

                $user_list[$key]['payment_list'] = $payment_list;
                if(!empty($value['bind_number'])){
                    $user_list[$key]['usernum']=$value['bind_number'];
                }

            }

            if($user_list){
                exit(json_encode(array('status'=>1,'user_list'=>$user_list)));
            }else{
                exit(json_encode(array('status'=>0,'user_list'=>$user_list)));
            }

        }else{
            $this->error_tips('访问页面有误！');
        }
    }

    // 删除订单
    public function del_pay_order(){
        //收银台-删除 权限
        if (!in_array(69, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_AJAX){
            $order_id = intval($_POST['order_id']);
            $database_house_village_pay_order = D('House_village_pay_order');
            $pay_order = $database_house_village_pay_order->get_one($order_id);
            if (!$pay_order) exit(json_encode(array('status'=>1,'msg'=>'该订单信息不存在')));

            if ($pay_order['paid'] > 0 ) {
                exit(json_encode(array('status'=>1,'msg'=>'该订单已付款，不能删除')));
            }

            $res = $database_house_village_pay_order->where(array('order_id'=>$order_id))->delete();
            if($res){
                exit(json_encode(array('status'=>0,'msg'=>'删除成功')));
            }else{
                exit(json_encode(array('status'=>1,'msg'=>'删除失败')));
            }
        }else{
            $this->error_tips('访问页面有误！');
        }
    }

    //个人账单
    //水电燃气停车费 按照业主信息中来，物业费自定义费用按照订单来 可以删除
    public function personal_order_list(){
        //收银台-查看 权限
        if (!in_array(66, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $where['bind_id'] = $_GET['bind_id'];

        $village_id = $this->village_id;

        //线下支付方式
        $pay_type_list = D('House_village_pay_type')->where(array('village_id'=>$village_id))->select();

        if($village_id){
            $where['village_id'] = $village_id;
            $where['pay_bind_id'] = 0;
            $where['order_type'] = "'custom','custom_payment','property','park'";
            $where['paid'] = 0; //未付款
            $where['cashier_id'] = 0;
            $pay_list_order = D('House_village_pay_order')->get_limit_list_page($where,20,false,'`o`.`order_id` DESC');

            $totalmoney = 0;
            if($pay_list_order){
                foreach ($pay_list_order['order_list'] as $v){
                    $totalmoney += $v['money'];                              //本页的总额
                }
            }
            $pay_list_order['total'] = $totalmoney;

            $this->assign('pay_list_order',$pay_list_order);

            //小区信息
            $now_village = D('House_village')->get_one($village_id);
            //当前业主信息
            $database_house_village_user_bind = D('House_village_user_bind');
            $now_user_info = $database_house_village_user_bind->get_one_by_bindId($_GET['bind_id']);
            $this->assign('now_user_info',$now_user_info);
            //缴费
            $pay_list = array();
            $now_village['property_price'] = getFormatNumber($now_village['property_price']);
            $now_village['water_price'] = getFormatNumber($now_village['water_price']);
            $now_village['electric_price'] = getFormatNumber($now_village['electric_price']);
            $now_village['gas_price'] = getFormatNumber($now_village['gas_price']);
            $now_village['park_price'] = getFormatNumber($now_village['park_price']);
            //物业费
            // $pay_list[] = array(
            //         'type' => 'property',
            //         'name' => $this->pay_list_type['property'],
            //         'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'property')),
            //         'money'=>floatval($now_user_info['property_price']),
            // );
            // 水费
            if($now_village['water_price']&&$now_user_info['water_price']){
                $totalmoney += floatval($now_user_info['water_price']);
                $pay_list[] = array(
                        'type' => 'water',
                        'name' => $this->pay_list_type['water'],
                        'url' => $now_village['water_price'] ? U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'water')) : U('Lifeservice/query',array('type'=>'water')),
                        'money'=>floatval($now_user_info['water_price']),
                );
            }
            // 电费
            if($now_village['electric_price']&&$now_user_info['electric_price']){
                $totalmoney += floatval($now_user_info['electric_price']);
                $pay_list[] = array(
                        'type' => 'electric',
                        'name' => $this->pay_list_type['electric'],
                        'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'electric')),
                        'money'=>floatval($now_user_info['electric_price']),
                );
            }
            // 燃气费
            if($now_village['gas_price']&&$now_user_info['gas_price']){
                $totalmoney += floatval($now_user_info['gas_price']);
                $pay_list[] = array(
                        'type' => 'gas',
                        'name' => $this->pay_list_type['gas'],
                        'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'gas')),
                        'money'=>floatval($now_user_info['gas_price']),
                );
            }
            // 停车费
            if($now_village['park_price']&&$now_user_info['park_price']){
                $totalmoney += floatval($now_user_info['park_price']);
                $pay_list[] = array(
                        'type' => 'park',
                        'name' => $this->pay_list_type['park'],
                        'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'park')),
                        'money'=>floatval($now_user_info['park_price']),
                );
            }
            // 计算欠缴物业费
            $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$now_user_info['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
            if(!$standard_id['standard_id']){
                $property_price = 0;
            }elseif ($now_user_info['property_endtime'] && $now_user_info['property_endtime'] < time()){
                $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_user_info['vacancy_id']])->find();
                $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                $num = D('House_village_user_bind')->getMonthNum($now_user_info['property_endtime'] + 1, time());
                if(date('d',time()) > date('d',$now_user_info['property_endtime'] + 1))
                    $num+=1;
                $start_time = strtotime(date('Y-m',$now_user_info['property_endtime']));
                $dateMonth[] = $start_time;
                for ($i=1;$i<$num;$i++){
                    $dateMonth[] = strtotime("+ $i month",$start_time);
                }
                $property_price = $this->property_price($dateMonth,unserialize($date['date_month']),$bind_info['housesize']);
                $totalmoney += floatval($property_price);
            }else{
                $property_price = 0;
            }
            if ($standard_id['standard_id'] && $property_price>0) {
                $pay_list[] = array(
                    'type' => 'property',
                    'name' => $this->pay_list_type['property'],
                    'money' => round(floatval($property_price), 2)?round(floatval($property_price), 2):0,
                    'property_month_num'=> isset($dateMonth)?count($dateMonth):0,
                    'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'property')),
                );
            }
            //物业费

             // 自定义缴费
             if($now_village['has_custom_pay']){
                 $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                     C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                     C('DB_PREFIX').'house_village_payment'=>'p'))
                     ->where("psb.pigcms_id= '".$now_user_info['pigcms_id']."' AND psb.village_id= '".$village_id."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
                 // 车位缴费
                 //$position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$now_user_info['pigcms_id']));
                 //$payment_list = array_merge($payment_list, $position_payment_list);
                 $cycle_type_china = array(
                     'Y'=>'年',
                     'M'=>'月',
                     'D'=>'日',
                 );
                 $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
                 foreach ($payment_list as $kk => $vv) {
                     if ($vv['cycle_sum'] - $vv['paid_cycle'] <= 0) {
                         unset($payment_list[$kk]);
                         continue;
                     }
                     $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);
                     // 计算结束时间
                     $vv['end_time'] = strtotime(date('Y-m-d',$vv['start_time']).'+'.$vv['cycle_sum']*$vv['pay_cycle'].$cycle_type[$vv['cycle_type']]);

                     $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
                     $payment_list[$kk]['cycle_type'] = $cycle_type_china[$vv['cycle_type']];
                     if ($vv['garage_num']) {
                         $payment_list[$kk]['payment_name'] = $vv['payment_name'].'('.$vv['garage_num'].'-'.$vv['position_num'].')';
                     }
                     //$end_time = strtotime(date('Y-m-d', $vv['start_time']) . '+' . $vv['pay_cycle'] * $vv['paid_cycle'] . $cycle_type[$vv['cycle_type']]);
                     switch ($vv['cycle_type']) {
                         case 'Y':
                             $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400*365;
                             break;
                         case 'M':
                             $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400*30;
                             break;
                         case 'D':
                             $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400;
                             break;
                     }
                     if($end_time < time()){
                         $num = D('House_village_user_bind')->getTimeNum($end_time, time(), $vv['cycle_type']);
                         $num = ceil($num / $vv['pay_cycle']);
                         $num = min($num, $vv['cycle_sum']);
                         if($vv['pay_type'] == 1){
                             $money = round($num * $vv['pay_money'],2);
                         }else{
                             $money = round($vv['metering_mode_val'] * $vv['pay_money']*$num,2);
                         }
                     }else{
                         continue;
                     }
                     $pay_list[] = array(
                         'type' => 'custom_payment',
                         //'name' => $this->pay_list_type['custom_payment'],
                         'payment_bind_id'=>$vv['bind_id'],
                         'payment_paid_cycle'=>$num,
                         'name'=>$vv['payment_name'],
                         'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'custom_payment')),
                         'money'=> $money,
                     );
                     $totalmoney += floatval($money);

                 }
             }

             $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                     C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                     C('DB_PREFIX').'house_village_payment'=>'p'))
                     ->where("psb.pigcms_id= '".$this->village_bind['pigcms_id']."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
             $this->assign('payment_list',$payment_list);
             $this->assign('pay_list',$pay_list);
             $this->assign('totalmoney',round($totalmoney,2));
        }
        $this->assign('user_info',$now_user_info);
        $this->assign('pay_type_list',$pay_type_list);
        $this->assign('village_id',$village_id);
        $this->assign('pigcms_id',$_GET['bind_id']);

        $this->display();

    }

    //收银台历史账单
    public function history_cashier_order(){
        //收银台-查看 权限
        if (!in_array(66, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $where['bind_id'] = $_GET['bind_id'];

        $village_id = $this->village_id;

        //线下支付方式
        $pay_type_list = D('House_village_pay_type')->where(array('village_id'=>$village_id))->select();
        $this->assign('pay_type_list',$pay_type_list);

        if($village_id){
            // 历史缴费
            $where = array(
                'pigcms_id' => $_GET['bind_id'],
                'village_id' => $village_id,
                'paid' => 1,
            );

            $paid_list = D('House_village_pay_cashier_order')->get_limit_list_page($where,20);
            $refund_cycle = D('House_village')->where(['village_id'=>$village_id])->getField('refund_cycle');
            $total = 0;
            if($paid_list){
                $db_house_village_pay_order = D('House_village_pay_order');
                $park_system = D('Park_system')->where(['park_id' => $village_id])->find();
                foreach ($paid_list['order_list'] as $k=>&$v){
                    // 应缴金额
                    $whereTotalMoney = array('village_id'=>$village_id,'cashier_id'=>$v['cashier_id'],'order_status'=>1);
                    $total_money = $db_house_village_pay_order->field(' SUM(`money` ) AS totalMoney ')->where($whereTotalMoney)->find();
                    $v['total_money'] = $total_money['totalMoney'];

                    $total += $v['money'];                              //本页的总额
                    if ($pay_type_list) {
                        foreach ($pay_type_list as $key => $value) {
                            if ($v['pay_type'] == $value['id']) {
                                $v['pay_type_name'] = $value['name'];
                            }
                        }
                    }
                   $v['pay_type_name'] = $v['pay_type_name'] ? $v['pay_type_name'] : '扫码支付';
                    $timediff = time() - $v['pay_time'];
                    $days = intval($timediff / 86400);
                    if($days > $refund_cycle || $v['order_status'] == 2){
                        $paid_list['order_list'][$k]['is_refund'] = 0;
                    } else {
                        $paid_list['order_list'][$k]['is_refund'] = 1;
                    }
                    if ($v['order_status']==2) {
                        $order_status_txt = '已退款';
                        $order_status_title = '已退款';
                        if (isset($v['carSynStatus']) && $v['carSynStatus']==4) {
                            $order_status_txt = '已退款(同步退款成功)';
                        } elseif (isset($v['carSynStatus']) && $v['carSynStatus']==5) {
                            $order_status_txt = '已退款(同步退款失败)';
                            $order_status_title = isset($v['carSynRemarks'])?$v['carSynRemarks']:'已退款(同步退款失败)';
                        }
                        $paid_list['order_list'][$k]['order_status_txt'] = $order_status_txt;
                        $paid_list['order_list'][$k]['order_status_color'] = "red";
                        $paid_list['order_list'][$k]['order_status_title'] = $order_status_title;
                    } else {
                        $paid_list['order_list'][$k]['order_status_txt'] = "正常";
                        $paid_list['order_list'][$k]['order_status_color'] = "green";
                        $paid_list['order_list'][$k]['order_status_title'] = "正常";
                    }
                    $paid_list['order_list'][$k]['refundPark'] = 0;
                    if ($park_system&&$park_system['park_id'] && isset($v['order_status'])&&$v['order_status']==2) {
                        // 已退款查询下是否存在特殊情况
                        $wherePark = [
                            'village_id'=>$village_id,'cashier_id'=>$v['cashier_id'],'order_status'=>2,
                            'car_type' => 'stored_type','carSynStatus' => ['not in',[4,5]]
                        ];
                        $carSynParkCount = $db_house_village_pay_order->where($wherePark)->count();
                        if ($carSynParkCount&&$carSynParkCount>0) {
                            // ↑ 订单已经退款且没有同步到停车设备（当前只针对储值车） 可以再次同步
                            $paid_list['order_list'][$k]['refundPark'] = 1;
                        }
                    }
                }
            }
            $paid_list['total'] = $total;
            $this->assign('paid_list',$paid_list);
        }

        //当前业主信息
        $database_house_village_user_bind = D('House_village_user_bind');
        $now_user_info = $database_house_village_user_bind->get_one_by_bindId($_GET['bind_id']);

        //打印模板
        $database_house_village_print_template = D('House_village_print_template');
        $print_template = $database_house_village_print_template->where(array('village_id'=>$village_id,'is_new' => 0))->select();
        $this->assign('user_info',$now_user_info);
        $this->assign('print_template',$print_template);
        $this->assign('village_id',$village_id);
        $this->assign('pigcms_id',$_GET['bind_id']);
        $this->display();

    }


    //收银台 确认收款
    //水电燃气停车费 按照业主信息中来，物业费自定义费用按照订单来 可以删除
    public function ajax_cashier_pay(){
        //收银台-收款 权限
        if (!in_array(68, $this->house_session['menus'])) {
             exit(json_encode(array('status'=>1,'msg'=>'对不起，您没有权限执行此操作')));
        }
        $database_house_village_pay_order = D('House_village_pay_order');
        $pigcms_id = $_GET['pigcms_id'];

        $ids = $_POST['orderids'];
        $pay_type = $_POST['pay_type']; //支付方式
        if (!$pigcms_id || !$ids) {
            exit(json_encode(array('status'=>1,'msg'=>'数据处理有误！')));
        }

        $village_id = $this->village_id;
        $now_village = D('House_village')->get_one($village_id);

        if($village_id){
            $e_config = M('House_e_config')->where(array('village_id'=>$village_id))->find();
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }

        if (!$now_village) {
            exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不存在！')));
        }


        //当前业主信息
        $database_house_village_user_bind = D('House_village_user_bind');
        $now_bind_info = $database_house_village_user_bind->get_one_by_bindId($pigcms_id);
        if (!$now_bind_info) {
            exit(json_encode(array('status'=>1,'msg'=>'当前业主不存在！')));
        }

        $database_house_village_pay_order = D('House_village_pay_order');

        $aId = explode(',',trim($ids,',')); //缴费项
        $aOrderId = array(); // 所有订单id
        $aDataOrder = array(); //需要添加的订单数据
        $totalmoney = 0; //总金额

        // 验证自定义缴费是否已缴完毕
        $custom_payment_order = array();
        $custom_payment_ids = array();
        foreach ($aId as $key => $order) {
            $aOrder = explode('|', $order);
            if($aOrder[1]){
                $order_info = $database_house_village_pay_order->get_one($aOrder[1]);
                if($order_info){
                    $detail[$key]['goodsname'] = $order_info['order_name'];
                    $detail[$key]['price'] = $order_info['money'];
                    $detail[$key]['order_id'] = $order_info['order_id'];
                    if($order_info['order_type'] == 'property'){
                        $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$order_info['bind_id']])->find();
                        $start_time = $user_bind_info['property_endtime']?$user_bind_info['property_endtime']+1:time();
                        $end_time = strtotime('+'.($order_info['property_month_num']+$order_info['presented_property_month_num']).' month',$start_time);
                        $spec = date('Y-m-d',$start_time) . '至' . date('Y-m-d',$end_time);
                        $detail[$key]['rate'] = 0.06;
                    }else{
                        $detail[$key]['rate'] = 0.05;
                    }
                    $spec = isset($spec)?$spec:'';
                }
            }

            switch ($aOrder[0]) {
                case 'custom_payment':  // 自定义缴费标准
                    // 已有订单
                    if($aOrder[2]){
                        $aDataOrder[] = array(
                            'money' => $aOrder[3],
                            'uid' => $now_bind_info['uid'],
                            'bind_id' => $now_bind_info['pigcms_id'],
                            'village_id' => $village_id,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'paid' => 0,
                            'payment_bind_id'=>$aOrder[1],
                            'payment_paid_cycle'=>$aOrder[2],
                            'order_type' => $aOrder[0],
                            'order_name' => $this->pay_list_type['custom_payment'],
                            'remarks' => $_POST['remarks'],
                        );
                        $totalmoney += $aOrder[3];
                    }else{
                        if ($order_info) {
                            $totalmoney += $order_info['money'];
                            $aOrderId[] = $aOrder[1];
                        }

                        // 验证自定义缴费是否已缴完毕
                        $custom_payment_ids[] = $order_info['payment_bind_id'];
                        if ($custom_payment_order[$order_info['payment_bind_id']]) {
                            $custom_payment_order[$order_info['payment_bind_id']]['paid_cycle'] += $order_info['payment_paid_cycle'];
                        }else{
                            $custom_payment_order[$order_info['payment_bind_id']]['paid_cycle'] = $order_info['payment_paid_cycle'];
                            $custom_payment_order[$order_info['payment_bind_id']]['order_name'] = $order_info['order_name'];
                        }
                    }

                    break;
                case 'property': // 物业费
                    // 计算欠缴物业费
                    if(!$aOrder[1]){
                        $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_bind_info['vacancy_id']])->find();
                        $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$now_bind_info['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
                        if(!$standard_id['standard_id']){
                            $property_price = 0;
                        }elseif ($now_bind_info['property_endtime'] && $now_bind_info['property_endtime'] < time()){
                            $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                            $num = D('House_village_user_bind')->getMonthNum($now_bind_info['property_endtime'] + 1, time());
                            if(date('d',time()) > date('d',$now_bind_info['property_endtime'] + 1))
                                $num+=1;
                            $start_time = strtotime(date('Y-m',$now_bind_info['property_endtime']));
                            $dateMonth[] = $start_time;
                            for ($i=1;$i<$num;$i++){
                                $dateMonth[] = strtotime("+ $i month",$start_time);
                            }
                            $property_price = $this->property_price($dateMonth,unserialize($date['date_month']),$bind_info['housesize']);
                        }else{
                            $property_price = 0;
                        }
                        $aDataOrder[] = array(
                            'money' => $property_price,
                            'uid' => $now_bind_info['uid'],
                            'bind_id' => $now_bind_info['pigcms_id'],
                            'village_id' => $village_id,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'house_size'=>$bind_info['housesize'],
                            'paid' => 0,
                            'property_month_num'=>isset($num)?$num:0,
                            'order_type' => $aOrder[0],
                            'order_name' => $this->pay_list_type['property'],
                            'remarks' => $_POST['remarks'],
                        );
                        $totalmoney += $property_price;
                    }
                case 'custom': // 自定义缴费
                    // 已有订单
                    if ($order_info) {
                        $totalmoney += $order_info['money'];
                        $aOrderId[] = $aOrder[1];
                    }
                    break;

                case 'water':
                    if(empty($now_village['water_price']))
                        exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不支持缴纳水费')));

                    $totalmoney += $now_bind_info['water_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['water_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $this->pay_list_type['water'],
                        'remarks' => $_POST['remarks'],
                    );
                    break;
                case 'electric':
                    if(empty($now_village['electric_price']))
                        exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不支持缴纳电费')));

                    $totalmoney += $now_bind_info['electric_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['electric_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $this->pay_list_type['electric'],
                        'remarks' => $_POST['remarks'],
                    );
                    break;
                case 'gas':
                    if(empty($now_village['gas_price']))
                        exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不支持缴纳燃气费')));

                    $totalmoney += $now_bind_info['gas_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['gas_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $this->pay_list_type['gas'],
                        'remarks' => $_POST['remarks'],
                    );
                    break;
                case 'park':
                    $park_versions = M('House_village_park_config')->where(array('village_id'=>$village_id))->getField('park_versions');
                    if($park_versions && $park_versions == 2){
                        $aOrderId[] = $aOrder[1];
                        $totalmoney += $now_bind_info['park_price'];
                    }else{
                        if(empty($now_village['park_price']))
                            exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不支持缴停车费')));
                        $aDataOrder[] = array(
                            'money' => $now_bind_info['park_price'],
                            'uid' => $now_bind_info['uid'],
                            'bind_id' => $now_bind_info['pigcms_id'],
                            'village_id' => $village_id,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'paid' => 0,
                            'order_type' => $aOrder[0],
                            'order_name' => $this->pay_list_type['park'],
                            'remarks' => $_POST['remarks'],
                        );
                    }
                    break;
            }
        }

        // 验证自定义缴费是否已缴完毕
        if ($custom_payment_ids) {
            $custom_payment_order_list = D('House_village_payment_standard_bind')->where(array('bind_id'=>array('in',$custom_payment_ids)))->select();
            foreach ($custom_payment_order_list as $key => $value) {
                $unpay_cycle = max(0,$value['cycle_sum'] - $value['paid_cycle']);
                if ($unpay_cycle < $custom_payment_order[$value['bind_id']]['paid_cycle'] ) {
                    exit(json_encode(array('status'=>1,'msg'=>'缴费项【'.$custom_payment_order[$value['bind_id']]['order_name'].'】缴费周期不能大于未缴周期，未缴周期为'.$unpay_cycle.'周期！')));
                }
            }
        }
        //生成总订单
        $database_house_village_pay_cashier_order = D('House_village_pay_cashier_order');
        $data = array(
            'pay_type' => $_POST['pay_type'] ? $_POST['pay_type'] : 0,
            // 'money' => $totalmoney,
            'money' => $_POST['real_money'],
            'uid' => $now_bind_info['uid'],
            'pigcms_id' => $now_bind_info['pigcms_id'],
            'village_id' => $village_id,
            'time' => $_SERVER['REQUEST_TIME'],
            'paid' => 0,
            'remarks' => $_POST['remarks'],
            'role_id' => $this->role_id,
        );
        $cashier_id = $database_house_village_pay_cashier_order->data($data)->add();
        if (!$cashier_id) {
            exit(json_encode(array('status'=>1,'msg'=>'收款失败，请重试！')));
        }
        // 更新物业自定义缴费
        if ($aOrderId) {
            D('House_village_pay_order')->where(array('order_id'=>array('in' , $aOrderId)))->data(array('cashier_id'=>$cashier_id))->save();
        }

        // 生成水电燃气停车订单
        if ($aDataOrder) {
            foreach ($aDataOrder as $data_order) {
                $data_order['cashier_id'] = $cashier_id;
                $order_id = D('House_village_pay_order')->data($data_order)->add();
                $aOrderId[] = $order_id;
            }
        }

        //扫码支付
        if ($_POST['is_online'] == 1 && $_POST['real_money'] > 0) {
            exit(json_encode(array('status'=>0,'data'=>array('cashier_id'=>$cashier_id))));
        }else{
            // 支付
            $result = $database_house_village_pay_cashier_order->cashier_pay($cashier_id);
            if($result['error_code']==1){
                exit(json_encode(array('status'=>1,'msg'=>$result['msg'])));
            }else{
                if($auto){
                    $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $now_bind_info['name'] = !empty($now_bind_info['name'])?$now_bind_info['name']:'未知用户';
                    $res = $this->addInvoice($now_bind_info['address'].'-'.$now_bind_info['name'],$now_bind_info['phone'],$order_no,$e_config['duty_paragraph'],$detail,$now_village['village_name'],$spec,$now_village['property_id']);
                    if($res['status'] == '0000'){
                        $this->addERecord($e_config['duty_paragraph'],$now_bind_info['name'],$now_bind_info['phone'],$res['fpqqlsh'],$order_no,$detail,$now_bind_info['pigcms_id']);
                    }
                }
                exit(json_encode(array('status'=>0,'msg'=>'收款成功！')));
            }
        }
    }


    //收银台 查看订单详情
    public function cashier_detail(){
        //收银台-查看 权限
        if (!in_array(66, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $cashier_id = $_GET['cashier_id'];

        if (!$cashier_id) {
            exit(json_encode(array('status'=>1,'msg'=>'参数传递有误！')));
        }

        $village_id = $this->village_id;
        $now_village = D('House_village')->get_one($village_id);

        //查询总订单
        $database_house_village_pay_cashier_order = D('House_village_pay_cashier_order');
        $cashier_order = $database_house_village_pay_cashier_order->get_one($cashier_id);

        //当前业主信息
        $database_house_village_user_bind = D('House_village_user_bind');
        $now_bind_info = $database_house_village_user_bind->get_one_by_bindId($cashier_order['pigcms_id']);
        $condition_field = "`o`.*,`p`.`start_time` as p_start_time,`p`.`end_time` as p_end_time,`pp`.`start_time` as pp_start_time,`pp`.`end_time` as pp_end_time";
        $condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o');
        $condition_where = "`o`.`cashier_id` =".$cashier_id;
        $join = ' LEFT JOIN '.C('DB_PREFIX').'house_village_payment_paylist as p on `p`.`order_id` = `o`.`order_id` LEFT JOIN '.C('DB_PREFIX').'house_village_property_paylist as pp on `pp`.`order_id` = `o`.`order_id`';

        $order_list = D('')->field($condition_field)->table($condition_table)->join($join)->where($condition_where)->select();
        foreach ($order_list as $key => $value) {
            if ($value['p_start_time']) {
                $order_list[$key]['custom_time_str'] = date('Y-m-d',$value['p_start_time']).'至'.date('Y-m-d',$value['p_end_time']);
            }

            if ($value['pp_start_time']) {
                $order_list[$key]['property_time_str'] = date('Y-m-d',$value['pp_start_time']).'至'.date('Y-m-d',$value['pp_end_time']);
            }

            if($value['order_type'] == 'park'){
                $database_house_village_parking_car = D('House_village_parking_car');
                $car_time = $database_house_village_parking_car->where(['car_id'=>$value['car_id']])->field('end_time')->find();
                if(!$car_time['end_time']){
                    $order_list[$key]['park_time_str'] = '暂无';
                }else{
                    $order_list[$key]['park_time_str'] = date('Y-m-d',time()) . '至' . date('Y-m-d',$car_time['end_time']);
                }
                /*if(!$value['month_end_time']){
                    $order_list[$key]['park_time_str'] = '暂无';
                }else{
                    $order_list[$key]['park_time_str'] = date('Y-m-d',time()) . '至' . date('Y-m-d',$value['month_end_time']);
                }*/
            }

        }


        $totalmoney = $database_house_village_pay_order->field(' SUM(`money` ) AS totalMoney ')->where(array('cashier_id' => $cashier_id))->find();
        $totalmoney = $totalmoney['totalMoney'];
        $this->assign('order_list',$order_list);
        $this->assign('now_bind_info',$now_bind_info);
        $this->assign('cashier_order',$cashier_order);
        $this->assign('totalmoney',$totalmoney);
        $this->display();
    }

    /**
     * 选择小区模板
     * @author: liukezhu
     * @date : 2021/7/16
     */
    public function print_template_choice(){
        if(IS_POST){
            $village_id=$_POST['village_id'];
           if(empty($village_id)){
               $this->ajaxReturn(['code'=>0,'data'=>'缺少必要参数']);
           }
           $house_village_print_template=D('house_village_print_template')->where([
               'village_id'=>$village_id,'is_new' => 0
           ])->field('template_id,title')->select();
           if(!$house_village_print_template){
               $this->ajaxReturn(['code'=>0,'data'=>'暂无模板数据']);
           }
            $this->ajaxReturn(['code'=>1,'data'=>$house_village_print_template]);
        }
    }

    /**
     * 复制小区打印模板
     * @author: liukezhu
     * @date : 2021/7/16
     */
    public function print_template_copy(){
        if(IS_POST){
            $db_house_village_print_template=D('house_village_print_template');
            $db_house_village_print_custom=D('house_village_print_custom');
            $village_id=$_POST['village_id'];
            $template_id=$_POST['template_id'];
            if(empty($village_id) || empty($template_id)){
                $this->ajaxReturn(['code'=>0,'data'=>'缺少必要参数']);
            }
            $house_village_print_template=$db_house_village_print_template->where([
                'village_id'=>$village_id,
                'template_id'=>$template_id,
                'is_new' => 0
            ])->find();
            if(!$house_village_print_template){
                $this->ajaxReturn(['code'=>0,'data'=>'暂无该模板']);
            }
            $past_village_print_template=$db_house_village_print_template->where([
                'pid'=>$house_village_print_template['template_id'],
                'village_id'=>$this->house_session['village_id'],
                'is_new' => 0
            ])->where('pid <> 0')->field('template_id,pid')->find();
            if($past_village_print_template){
                $this->ajaxReturn(['code'=>0,'data'=>'该模板您已复制']);
            }
            $param=array(
                'uid'=>0,
                'village_id'=>$this->house_session['village_id'],
                'title'=>$house_village_print_template['title'],
                'time'=>time(),
                'desc'=>$house_village_print_template['desc'],
                'pid'=>$house_village_print_template['template_id'],
                'top_title'=>$house_village_print_template['top_title'],
                'col_num'=>$house_village_print_template['col_num'],
                'custom_field'=>$house_village_print_template['custom_field']
            );
            $add_template_id = $db_house_village_print_template->data($param)->add();
            if(!$add_template_id){
                $this->ajaxReturn(['code'=>0,'data'=>'提交失败']);
            }
            $print_custom=$db_house_village_print_custom->where([
                'template_id'=>$house_village_print_template['template_id'],
                'village_id'=>$house_village_print_template['village_id']
            ])->order('id asc')->select();
            if($print_custom){
                foreach ($print_custom as $v){
                    $param=array(
                        'template_id'=>$add_template_id,
                        'uid'=>$v['uid'],
                        'village_id'=>$this->house_session['village_id'],
                        'configure_id'=>$v['configure_id'],
                        'time'=>time(),
                        'desc'=>$v['desc']
                    );
                    $db_house_village_print_custom->data($param)->add();
                }
            }
            $this->ajaxReturn(['code'=>1,'data'=>'操作成功']);
        }else{
            $house_village=D('house_village')->where(['status'=>1,'property_id'=>$this->house_session['property_id'],'village_id'=>['neq',$this->house_session['village_id']]])->field('village_id,village_name')->select();
            $this->assign('house_village',$house_village);
            $this->display();
        }
    }

    //收银台 打印模板配置
    public function print_template_list(){
        //打印模板设置-查看 权限
        if (!in_array(86, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $village_id = $this->village_id;
        $now_village = D('House_village')->get_one($village_id);

        $where = array();
        $where['village_id'] = $village_id;
        $where['is_new'] = 0;

        $database_house_village_print_template = D('House_village_print_template');
        $list = $database_house_village_print_template->get_limit_list_page($where);

        $this->assign('list',$list);
        $this->display();
    }

    //打印模板配置 预览
    public function print_template_detail(){
        //打印模板设置-查看 权限
        if (!in_array(86, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;
        $database_house_village_print_template = D('House_village_print_template');

        if (!$template_id) {
            $this->error('参数不对');
        }
        $village_id = $this->village_id;
        $print_template = $database_house_village_print_template->get_one_new($template_id);
        if(!empty($print_template['top_title'])){
            $print_template['title'] = $print_template['top_title'];
        }
        // 宽度
        $col_num = $print_template['col_num'];
        $with = floor(100/$col_num).'%';
        $this->assign('with',$with);

        $this->assign('print_template',$print_template);
        $this->assign('template_id',$template_id);
        $this->display();
    }


    //收银台 打印模板配置
    public function print_template_add(){
        //打印模板设置-添加 查看 编辑 权限
        if (!in_array(87, $this->house_session['menus']) && !in_array(86, $this->house_session['menus']) && !in_array(88, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;
        $database_house_village_print_template = D('House_village_print_template');

        $village_id = $this->village_id;
        if ($template_id) {
            $print_template = $database_house_village_print_template->get_one_new($template_id);
            $this->assign('print_template',$print_template);
            $this->assign('template_id',$template_id);
        }

        $this->assign('print_template',$print_template);
        $this->assign('template_id',$template_id);
        $this->display();
    }

    //收银台 删除打印模板
    public function print_template_del(){
        //打印模板设置-删除 权限
        if (!in_array(89, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;
        $database_house_village_print_template = D('House_village_print_template');
        if ($template_id) {
            $result = $database_house_village_print_template->where(array('template_id'=>$template_id))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }

    }
    //收银台 保存打印模板
    public function print_template_save(){
        $template_id = $_GET['template_id'] + 0;

        $database_house_village_print_template = D('House_village_print_template');
        $village_id = $this->village_id;

        $data = array();
        $data['village_id'] = $village_id;
        $data['title'] = $_POST['title'];
        $data['desc'] = $_POST['desc'];
        $data['top_title'] = $_POST['top_title'];
        if(isset($_POST['col_num']) && $_POST['col_num']>0){
            $data['col_num'] = intval($_POST['col_num']);
        }
        $data['is_new'] = 0;
        if ($template_id) { // 编辑
            //打印模板设置-编辑 权限
            if (!in_array(88, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $res = $database_house_village_print_template->where(array('template_id'=>$template_id))->data($data)->save();
            if($res !== false ){
                exit(json_encode(array('status'=>0,'data'=>array('template_id'=>$template_id))));
            }else{
                exit(json_encode(array('status'=>1,'msg'=>'保存失败，请重试！')));
            }
        } else {
            //打印模板设置-添加 权限
            if (!in_array(87, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $data['time'] = $_SERVER['REQUEST_TIME'];
            $template_id = $database_house_village_print_template->data($data)->add();
            if($template_id){
                exit(json_encode(array('status'=>0,'data'=>array('template_id'=>$template_id))));
            }else{
                exit(json_encode(array('status'=>1,'msg'=>'保存失败，请重试！')));
            }
        }
    }
    // 打印模板自定义配置
    public function print_template_custom(){
        //打印模板设置-添加 编辑 权限
        if (!in_array(87, $this->house_session['menus']) && !in_array(88, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;

        $database_house_village_print_template = D('House_village_print_template');
        $village_id = $this->village_id;

        // 关键词
        $template_key_word = [];
        if(empty(S('template_key_word'))){
            $print_title_list = D('House_village_print_custom_configure')->where(array('is_new' => 0))->field('title')->select();
            $template_key_word = array_column($print_title_list,'title');
            S('template_key_word',$template_key_word);
        }else{
            $template_key_word = S('template_key_word');
        }

        //模板信息
        $print_template = $database_house_village_print_template->get_one_new($template_id);
        $lastIndex = 1;
        if ($print_template['custom']) {
            foreach ($print_template['custom'] as $value) {
                if($value['id'] > 0){
                    $lastIndex = $value['id'];
                }
            }
        }
        if ($print_template['desc']) {
            $print_template['desc'] = str_replace(array("\r\n", "\r", "\n"), "", $print_template['desc']);
        }
        // 宽度
        $col_num = $print_template['col_num'];
        $with = floor(100/$col_num).'%';
        $this->assign('with',$with);

        // 自定义关键词
        $template_key_word_custom = array_column($print_template['custom'],'title');
        $template_key_word = array_merge($template_key_word,$template_key_word_custom);
        $this->assign('templateKeyWord',implode(',',$template_key_word));

        //获得自定义项
        $print_custom = D('House_village_print_custom_configure')->where(array('is_hidden'=>0,'is_new' => 0))->order('sort desc,configure_id asc')->select();
        $this->assign('lastIndex',$lastIndex);
        $this->assign('template_id',$template_id);
        $this->assign('print_template',$print_template);
        $this->assign('print_custom',$print_custom);
        $this->display();
    }

    // 保存模板配置项
    public function save_custom(){
        //打印模板设置-添加 编辑 权限
        if (!in_array(87, $this->house_session['menus']) && !in_array(88, $this->house_session['menus'])) {
            exit(json_encode(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作')));
        }
        $ids = $_POST['ids'];
        $template_id = $_GET['template_id'] + 0;
        $database_house_village_print_custom = D('House_village_print_custom');
        //删除之前的
        $database_house_village_print_custom->where(array('template_id'=>$template_id,'village_id'=>$this->village_id))->delete();
        if (is_array($ids)&&$ids) {
            foreach ($ids as $key => $value) {
                if(is_numeric($value['id'])){ // 非自定义字段
                    $data = array(
                        'template_id' => $template_id,
                        'village_id' => $this->village_id,
                        'configure_id' => intval($value['id']),
                        'time' => $_SERVER['REQUEST_TIME'],
                    );
                    $insert = $database_house_village_print_custom->data($data)->add();
                }else{
                    // 防注入
                    $ids[$key]['title'] = remove_xss($value['title']);
                }
            }
            $json_ids = json_encode($ids);
        }else{
            $json_ids = '';
        }
        // 保存 转json 保存
        D('House_village_print_template')
            ->where(array('template_id' => $template_id,'village_id' => $this->village_id))
            ->data(array('custom_field' => $json_ids))
            ->save();
        exit(json_encode(array('status'=>0)));
    }

    // 打印
    public function print_start(){
        //收银台-打印 权限
        if (!in_array(70, $this->house_session['menus']) && !in_array(84, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;
        $cashier_id = $_GET['cashier_id'] + 0;

        $village_id = $this->village_id;

        $where = array(
            'cashier_id' => $cashier_id,
            'village_id' => $village_id,
        );
        //获取订单详情
        $cashier_info = D('House_village_pay_cashier_order')->get_cashier_print_detail($where);
        //获取模板详情
        $database_house_village_print_template = D('House_village_print_template');
        $print_template = $database_house_village_print_template->get_one_new($template_id);
        // cashier_info
        $cashier_info['desc'] = $print_template['desc'];
        if(!empty($print_template['top_title'])){
            $print_template['title'] = $print_template['top_title'];
        }

        // 宽度
        $col_num = $print_template['col_num'];
        $with = floor(100/$col_num).'%';
        $this->assign('with',$with);
       /* print_r($cashier_info);
        print_r($print_template);exit;*/
        $this->assign('cashier_info',$cashier_info);
        $this->assign('print_template',$print_template);

        // exit(json_encode($this->fetch()));
        // return $this->fetch();
        $this->display();
    }


    /*-------------------------------------------------------收银台 end-------------------------------------------------------------*/

    //缴费提醒
    public function payment_reminder(){
        $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('payment_reminder');
        if($sum > 0){
            M('House_village')->where(array('village_id'=>$this->village_id))->setField('payment_reminder',0);
            exit(json_encode(array('error'=>1,'msg'=>'您有 ('.$sum.') 条缴费提醒！')));
        }else{
            exit(json_encode(array('error'=>0,'msg'=>'暂无缴费信息')));
        }
    }


    //缴费项列表
    public function payment_item(){
        //收费设置-查看 权限
        if (!in_array(71, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $payment_list = D('House_village_payment')->where(array('village_id'=>$this->village_id))->select();
        foreach ($payment_list as $key => $value) {
            $payment_list[$key]['standard_sum'] = D('House_village_payment_standard')->where(array('payment_id'=>$value['payment_id']))->count();
        }
        $this->assign('payment_list',$payment_list);
        $this->display();
    }

    //添加缴费项
    public function payment_item_add(){
        //收费设置-收费项添加 权限
        if (!in_array(72, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $res = D('House_village_payment')->data(array('payment_name'=>$_POST['payment_name'],'village_id'=>$this->village_id))->add();
            if($res){
                $this->success('添加成功，正在跳转',U('payment_item'));
                die;
            }else{
                $this->error('添加失败');
                die;
            }
        }
        $this->display();
    }

    //编辑缴费项
    public function payment_item_edit(){
        //收费设置-收费项添查看 权限
        if (!in_array(71, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            //收费设置-收费项编辑 权限
            if (!in_array(74, $this->house_session['menus']) && !in_array(73, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $res = D('House_village_payment')->where(array('payment_id'=>$_POST['payment_id'],'village_id'=>$this->village_id))->data(array('payment_name'=>$_POST['payment_name']))->save();
            if($res){
                $this->success('修改成功，正在跳转',U('payment_item')); die;
            }else{
                $this->error('修改失败'); die;
            }
        }
        $info = D('House_village_payment')->where(array('payment_id'=>$_GET['payment_id'],'village_id'=>$this->village_id))->find();
        if(!is_array($info)){
            $this->error('数据异常');die;
        }
        $this->assign('info',$info);
        $this->display();
    }

    //删除收费项
    public function payment_item_del(){
        //收费设置-收费项删除 权限
        if (!in_array(74, $this->house_session['menus']) && !in_array(73, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(D('House_village_payment')->where(array('payment_id'=>$_GET['payment_id'],'village_id'=>$this->village_id))->delete()){
            D('House_village_payment_standard')->where(array('payment_id'=>$_GET['payment_id'],'village_id'=>$this->village_id))->delete();
            $this->success('删除成功',U('payment_item'));die;
        }else{
            $this->error('删除失败');die;
        }
    }


    //缴费标准 列表
    public function payment_standard(){
        //收费设置-收费标准 查看 权限
        if (!in_array(75, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $cycle_type = array(
                'Y'=>'年',
                'M'=>'月',
                'D'=>'日',
            );
        $standard_list = D('House_village_payment_standard')->where(array('village_id'=>$this->village_id,'payment_id'=>$_GET['payment_id']))->order('standard_id desc')->select();
        foreach ($standard_list as &$value) {
            $value['pay_icon'] = file_domain().$value['pay_icon'];
        }
        $this->assign('standard_list',$standard_list);
        $this->assign('cycle_type',$cycle_type);
        $this->display();
    }

    //添加标准
    public function payment_standard_add(){
        //收费设置-收费标准 添加 权限
        if (!in_array(76, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $data = $_POST;
            if($_POST['metering_mode_type'] == 1){
                $data['metering_mode'] = (D('House_village')->getHouseAlias('room_name')?:'房间').'面积';
            }
            if($_POST['metering_mode_type'] == 3){
                $data['metering_mode'] = '车位面积';
            }
            if ($_POST['pay_type'] == 1) {
                $data['metering_mode'] = '固定费用';
                $data['metering_mode_type'] = 0;
            }
            $data['village_id'] = $this->village_id;
            $res = D('House_village_payment_standard')->data($data)->add();
            if($res){
                $this->success('添加成功',U('payment_standard',array('payment_id'=>$_POST['payment_id'])));die;
            }else{
                $this->error('添加失败');die;
            }
        }
        $this->display();
    }

    //编辑收费标准
    public function payment_standard_edit(){
        //收费设置-收费标准 查看 权限
        if (!in_array(75, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            //收费设置-收费标准 编辑 权限
            if (!in_array(77, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $data = $_POST;
            if($_POST['metering_mode_type'] == 1){
                $data['metering_mode'] = (D('House_village')->getHouseAlias('room_name')?:'房间').'面积';
            }
            if($_POST['metering_mode_type'] == 3){
                $data['metering_mode'] = '车位面积';
            }
            if ($_POST['pay_type'] == 1) {
                $data['metering_mode'] = '固定费用';
                $data['metering_mode_type'] = 0;
            }
            $res = D('House_village_payment_standard')->where(array('payment_id'=>$_POST['payment_id'],'village_id'=>$this->village_id,'standard_id'=>$_POST['standard_id']))->data($data)->save();
            if($res){
                $this->success('修改成功',U('payment_standard',array('payment_id'=>$_POST['payment_id'])));die;
            }else{
                $this->error('修改失败');die;
            }
        }
        $info = D('House_village_payment_standard')->where(array('standard_id'=>$_GET['standard_id'],'village_id'=>$this->village_id,'payment_id'=>$_GET['payment_id']))->find();
        $info['pay_icon_txt'] = $info['pay_icon'];
        $info['pay_icon'] = file_domain().$info['pay_icon'];
        $this->assign('info',$info);
        $this->display();
    }

    //删除收费标准
    public function payment_standard_del(){
        //收费设置-收费标准 删除 权限
        if (!in_array(78, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(D('House_village_payment_standard')->where(array('payment_id'=>$_GET['payment_id'],'village_id'=>$this->village_id,'standard_id'=>$_GET['standard_id']))->delete()){
            $this->success('删除成功',U('payment_standard',array('payment_id'=>$_GET['payment_id'])));die;
        }else{
            $this->error('删除失败');die;
        }
    }

    //线下支付方式列表
    public function pay_type_list(){
        //收费设置-线下支付方式 查看 权限
        if (!in_array(79, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $paytype_list = D('House_village_pay_type')->where(array('village_id'=>$this->village_id))->select();
        foreach ($paytype_list as $key => $value) {
            $paytype_list[$key]['standard_sum'] = D('House_village_payment_standard')->where(array('payment_id'=>$value['payment_id']))->count();
        }
        $this->assign('paytype_list',$paytype_list);
        $this->display();
    }

    //添加线下支付方式
    public function pay_type_add(){

        if ($_GET['id']) {
            //收费设置-线下支付方式 查看 权限
            if (!in_array(79, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $pay_type = D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->find();
        }
        if(IS_POST){
            if ($_GET['id']) {
                //收费设置-线下支付方式 编辑 权限
                if (!in_array(81, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                if (!trim($_POST['name'])) {
                    $this->error('名称不能为空');
                }

                $res = D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->data(array('name'=>trim($_POST['name'])))->save();
            }else{
                //收费设置-线下支付方式 添加 权限
                if (!in_array(80, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                if (!trim($_POST['name'])) {
                    $this->error('名称不能为空');
                }

                $res = D('House_village_pay_type')->data(array('name'=>$_POST['name'],'village_id'=>$this->village_id))->add();
            }
            if($res!==false){
                $this->success('添加成功，正在跳转',U('pay_type_list'));
                die;
            }else{
                $this->error('添加失败');
                die;
            }
        }
        $this->assign('pay_type',$pay_type);
        $this->display();
    }

    //删除线下支付方式
    public function pay_type_del(){
        //收费设置-线下支付方式 删除 权限
        if (!in_array(82, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(D('House_village_pay_type')->where(array('id'=>$_GET['id'],'village_id'=>$this->village_id))->delete()){
            $this->success('删除成功',U('pay_type_list'));die;
        }else{
            $this->error('删除失败');die;
        }
    }

    // 上传图片
    public function ajax_upload_pic(){
        if ($_FILES['imgFile']['error'] != 4) {
            $upload_dir = './upload/house/village/'.date('Ymd').'/'.date('H').'/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif','image/webp','application/octet-stream');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $uploadList[0]['savename'];
                $url = $upload_dir.$title;
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => replace_file_domain($url))));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }

    public function ajax_user_info(){
        if(IS_AJAX){
            $find_type = $_POST['find_type'];
            $find_value = $_POST['find_value'];
            $where=array();
            if($find_type){
                if ($find_value || $find_value==='0') {
                    if ($find_type == 1) {
                        $where['name'] = array('like', '%' . trim($find_value) . '%');
                    } else if ($find_type == 2) {
                        $where['phone'] = array('like', '%' . trim($find_value) . '%');
                    } else if ($find_type == 3) {
                        $where['room'] = array('like', '%' . trim($find_value) . '%');
                    }
                }
            }else{
                $where=array();
            }

            $where['village_id'] = $this->village_id;
            $where['is_del'] = 0;
            $where['status'] = 3;
            $order ='add_time desc';
            $_GET['page'] = $_POST['page'];
            $user_list = D('House_village_user_vacancy')->house_village_user_vacancy_lists_new($where, '*', $order,5);
            if($user_list){
                exit(json_encode(array('status'=>1,'user_list'=>$user_list)));
            }else{
                exit(json_encode(array('status'=>0,'user_list'=>$user_list)));
            }

        }else{
            $this->error_tips('访问页面有误！');
        }
    }

    public function get_ajax_user_list(){
        if(IS_AJAX){
            $find_type = $_POST['find_type'];
            $find_value = $_POST['find_value'];
            $where=array();
            if($find_type){
                if ($find_value || $find_value==='0') {
                    if ($find_type == 1) {
                        $where['name'] = array('like', '%' . trim($find_value) . '%');
                    } else if ($find_type == 2) {
                        $where['phone'] = array('like', '%' . trim($find_value) . '%');
                    } else if ($find_type == 3) {
                        $where['room_addrss'] = array('like', '%' . trim($find_value) . '%');
                    }
                }
            }else{
                $where=array();
            }
            $where['parent_id']=0;
            $where['type']=array('in',array(0,3));
            $where['vacancy_id'] =array('gt',0);
            $where['status'] =1;
            $where['village_id'] = $this->village_id;
            $order ='pigcms_id desc';
            $_GET['page'] = $_POST['page'];
            $user_list = D('House_village_user_bind')->get_house_village_user_vacancys($where, '*', $order,10);
            if($user_list){
                exit(json_encode(array('status'=>1,'user_list'=>$user_list)));
            }else{
                exit(json_encode(array('status'=>0,'user_list'=>$user_list)));
            }

        }else{
            $this->error_tips('访问页面有误！');
        }
    }
public function meter_reading()
{
    $where = [];
    $find_type = $_GET['find_type'];
    $find_value = $_GET['find_value'];
    $this->assign('find_type',$_GET['find_type']);
    if ($find_value) {
        if ($find_type == 1) {
            $where['b.room'] = array('like', '%' . $find_value . '%');
        } else if ($find_type == 2) {
            $where['m.charge_name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
        } else if ($find_type == 3) {
            $where['f.floor_name'] = array('like', '%' . $find_value . '%');
        } else if ($find_type == 4) {
            $where['f.floor_layer'] = array('like', '%' . $find_value . '%');
        }
    }
    $single_id = $_GET['single_id'];
    $floor_id = $_GET['floor_id'];
    $layer_id = $_GET['layer_id'];
    $vacancy_id = $_GET['vacancy_id'];
    if($single_id){
        $where['m.single_id'] = $single_id;
    }
    if($floor_id){
        $where['m.floor_id'] = $floor_id;
    }
    if($layer_id){
        $where['m.layer_id'] = $layer_id;
    }
    if($vacancy_id){
        $where['m.layer_num'] = $vacancy_id;
    }
    if($_GET['charge_name']){
        $where['m.charge_name'] = $_GET['charge_name'];
    }
    $property_endtime_start = $_GET['property_endtime_start'];
    $property_endtime_end = $_GET['property_endtime_end'];
    $this->assign('property_endtime_start',$_GET['property_endtime_start']);
    $this->assign('property_endtime_end',$_GET['property_endtime_end']);
    if($property_endtime_start && $property_endtime_end){
        $start_time = strtotime($property_endtime_start);
        $end_time = strtotime($property_endtime_end.'23:59:59');
        $where['m.start_time'] = array('between',array($start_time,$end_time));
    }else if($property_endtime_start){
        $property_endtime_start = strtotime($property_endtime_start);
        $where['m.start_time'] = array('egt',$property_endtime_start);
    }else if($property_endtime_end){
        $property_endtime_end = strtotime($property_endtime_end.'23:59:59');
        $where['m.last_time'] = array('lt',$property_endtime_end);
    }

    $join=' left join pigcms_house_village_user_vacancy as b on m.layer_num=b.pigcms_id left join pigcms_house_village_floor as f on f.floor_id = m.floor_id left join pigcms_house_village_layer as l on m.layer_id = l.id left join pigcms_house_village_single as s on s.id = m.single_id';
    $field='m.*,b.room,b.usernum,f.floor_name,f.floor_layer,l.layer_name,s.single_name';

    import('@.ORG.merchant_page');
    $dataLists = M('House_village_meter_reading')->alias('m')->field($field)->join($join)->where($where)->where(array('m.village_id'=>$this->village_id))->count();
    $p = new Page($dataLists, 20, 'page');

    $dataList = M('House_village_meter_reading')->alias('m')->field($field)->join($join)->where($where)->where(array('m.village_id'=>$this->village_id))->order('m.id desc')->limit($p->firstRow.','.$p->listRows)->select();
    fdump(M('House_village_meter_reading')->getLastSql());

    $pagebar = $p->show();
    //楼栋
    $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort desc, id asc')->select();
    if($single_list){
        $this->assign('single_list',$single_list);
    }else{
        $this->assign('single_list',[]);
    }
    if($_GET['vacancy_id']){
        $this->assign('vacancy_id',$_GET['vacancy_id']);
    }else{
        $this->assign('vacancy_id','');
    }
    $this->assign('pagebar',$pagebar);
    $this->assign('dataList',$dataList);
    $this->display();
}

    public function meter_reading_add()
{
    //2020/8/24 start 新增
    $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->village_id);
    if(!$is_open_pay_management){
        //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
        $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
    }
    //2020/8/24 end 新增

    $database_house_village_user_vacancy = D('House_village_user_vacancy');
    $database_house_village_floor = D('House_village_floor');
    $vacancy_where['status'] = 1;
    $vacancy_where['village_id'] = $this->village_id;

    //先判断单元
    $floor_num = $database_house_village_floor->where(array('village_id'=>$this->village_id,'status'=>1))->count();
    if($floor_num <= 0){
        $this->error('请先添加'.(D('House_village')->getHouseAlias('floor_name')?:'单元'),U('Unit/index'));
    }
    if (IS_POST) {
        $start_time = strtotime($_POST['start_time'].'-01 00:00:00');
        $last_time = explode('-',$_POST['last_time']);

        if($last_time[1] == 1 || $last_time[1] == 3 || $last_time[1] == 5 || $last_time[1] == 7 || $last_time[1] == 8 || $last_time[1] == 10 || $last_time[1] == 12){
            $last_time = strtotime($_POST['last_time'].'-31 23:59:59');
        }elseif($last_time[1] == 2){
            if($last_time[0]%4 == 0 ){
                $last_time = strtotime($_POST['last_time'].'-29 23:59:59');
            }else{
                $last_time = strtotime($_POST['last_time'].'-28 23:59:59');
            }
        }else{
            $last_time = strtotime($_POST['last_time'].'-30 23:59:59');
        }

        if(empty($_POST['single_id'])){
            $this->error(($this->house_alias['single_name']?:'楼栋').'不能为空！');
        }

        if(empty($_POST['floor_id'])){
            $this->error((D('House_village')->getHouseAlias('floor_name')?:'单元').'不存在！');
        }

        if(empty($_POST['vacancy_id'])){
            $this->error((D('House_village')->getHouseAlias('room_name')?:'房间').'不能为空！');
        }

        $database_house_village_floor = D('House_village_floor');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $where['floor_id'] = $_POST['floor_id'] + 0;
        $where['status'] = 1;
        $where['village_id'] = $this->village_id;

        $house_village_floor_info = $database_house_village_floor->where($where)->find();
        if (!isset($house_village_floor_info)) {
            $this->error(($this->house_alias['floor_name']?:'单元').'不存在，请查看小区中心，'.($this->house_alias['floor_name']?:'单元').'管理-'.($this->house_alias['floor_name']?:'单元').'列表！');
        }
//            $vacancy_where['status'] = 1;
//            $vacancy_where['pigcms_id'] = $_POST['layer_num'] + 0;
//            $vacancy_info = $database_house_village_user_vacancy->where($vacancy_where)->find();
//
//            if (!isset($vacancy_info)) {
//                $this->error('该房间信息不存在！');
//            }
        $data['village_id'] = $this->village_id;
        $data['single_id'] = $_POST['single_id'];
        $data['floor_id'] = $_POST['floor_id'];
        $data['layer_id'] = $_POST['layer_id'];
        $data['layer_num'] = $_POST['vacancy_id'];
        $data['start_time'] = $start_time;
        $data['start_name'] = $_POST['start_time'];
        $data['last_time'] = $last_time;
        $data['last_name'] = $_POST['last_time'];
        $data['charge_name'] = $_POST['charge_name'];
        if(empty($data['charge_name'])){
            $this->error('请选择收费项');
        }
        $data['unit_price'] = $_POST['unit_price'];
        $data['start_ammeter'] = $_POST['start_ammeter'];
        $data['last_ammeter'] = $_POST['last_ammeter'];
        $data['rate'] = $_POST['rate'];
        $data['pool_ammeter'] = $_POST['pool_ammeter'];
        $data['note'] = $_POST['note'];
        // 金额 = （止度-起度+公摊）*倍率 * 单价
        $data['rate'] = $data['rate']?$data['rate']:1;
        $data['cost_num'] = ($data['last_ammeter'] - $data['start_ammeter'] +$data['pool_ammeter'])*$data['rate'];
        $data['cost_money'] = $data['unit_price'] * $data['cost_num'];
        $data['add_time'] = time();
        $insert_id = M('House_village_meter_reading')->data($data)->add();
        $res = M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['vacancy_id'],'status'=>1))->find();
        if($res){
            if ($start_time) {
                $ydate = date('Y', $start_time);
                $mdate = date('m', $start_time);
            } elseif($last_time) {
                $ydate = date('Y', $last_time);
                $mdate = date('m', $start_time);
            }
            $gas['ydate']= $electric['ydate']= $water['ydate'] = $ydate? $ydate : '';
            $gas['mdate']= $electric['mdate']= $water['mdate'] = $mdate? $mdate : '';
            $gas['type']= $electric['type']= $water['type'] = 1;
            $gas['village_id']= $electric['village_id']= $water['village_id'] = $this->village_id;
            $gas['uid']= $electric['uid']= $water['uid'] = $res['uid'];
            $gas['usernum']= $electric['usernum']= $water['usernum'] = $res['usernum'];
            $gas['name']= $electric['name']= $water['name'] = $res['name'];
            $gas['phone']= $electric['phone']= $water['phone'] = $res['phone'];
            $gas['bind_id']= $electric['bind_id']= $water['bind_id'] = $res['pigcms_id'];
            $gas['add_time']= $electric['add_time']= $water['add_time'] = time();
        }
        if($insert_id){
            if($_POST['charge_name'] == '电费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['vacancy_id']))->setInc('electric_price',$data['cost_money']);
                $electric['electric_price'] = $data['cost_money'];
                M('House_village_user_paylist')->data($electric)->add();
                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$data['cost_money'],'detail'=>'增加'.$data['cost_money'].'元','action'=>'抄表录入-增加','add_time'=>time(),'remark'=>$_POST['note'],'type'=>2]);
            }elseif($_POST['charge_name'] == '煤气费' || $_POST['charge_name'] == '燃气费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['vacancy_id']))->setInc('gas_price',$data['cost_money']);
                $gas['gas_price'] = $data['cost_money'];
                M('House_village_user_paylist')->data($gas)->add();
                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$data['cost_money'],'detail'=>'增加'.$data['cost_money'].'元','action'=>'抄表录入-增加','add_time'=>time(),'remark'=>$_POST['note'],'type'=>3]);
            }elseif($_POST['charge_name'] == '水费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['vacancy_id']))->setInc('water_price',$data['cost_money']);
                $water['water_price'] = $data['cost_money'];
                M('House_village_user_paylist')->data($water)->add();
                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$data['cost_money'],'detail'=>'增加'.$data['cost_money'].'元','action'=>'抄表录入-增加','add_time'=>time(),'remark'=>$_POST['note'],'type'=>1]);
            }
            //新增优化 start
            $address = D('House_village')->get_single_floor_room($res['single_id'],$res['floor_id'],$res['layer_id'],$res['vacancy_id'],$this->village_id);
            $openid = D('User')->where(['uid'=>$res['uid']])->getField('openid');
            $house_village_name = D('House_village')->where(['village_id'=>$this->village_id])->getField('village_name');
            if($openid){
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $keyword = '尊敬的业主，您有新的账单';
                $remark = '您的待缴总额剩余：￥'.$data['cost_money'].'元，点击缴费！';

                $param = [
                    'pageName' => 'payMentlist',
                    'isAll' => true,
                    'urlParam' => 'village_id='.$this->village_id.'&pigcms_id='.$res['pigcms_id'],
                ];
                $payMentlistUrl = D('House_village')->villagePagePath('','',$param);
                $href = $payMentlistUrl;
                $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
                if($getIsAihorseCustomized){
                    $village_info_arr= M('House_village_info')->where(['village_id'=>$this->village_id])->field('third_pay_url,third_ccb_pay_url')->find();
                    $href = isset($village_info_arr['third_pay_url']) && $village_info_arr['third_pay_url'] ? $village_info_arr['third_pay_url'] : $href;
                    $href = isset($village_info_arr['third_ccb_pay_url']) && $village_info_arr['third_ccb_pay_url'] ? $village_info_arr['third_ccb_pay_url'] : $href;
                }
                $model->sendTempMsg('TM01008',//todo 类目模板TM01008
                    array(
                        'href' => $href,
                        'wecha_id' => $openid,
                        'first' => $keyword,
                        'keynote1' => $house_village_name,
                        'keynote2' => $address,
                        'remark' => $remark,
                        'new_info' => [//新版本发送需要的信息
                            'type'=>0,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                            'thing2'=>$res['name'],//业主姓名
                            'thing3'=>$address,//地址
                            'thing9'=>$_POST['charge_name']?:'物业费',//缴费类型
                            'thing10'=>$_POST['charge_name']?:'物业费',//账单名称
                            'amount4'=>$data['cost_money'].'元',//账单金额
                        ],
                    )
                );
            }
            //新增优化 end
            $this->success('添加成功！',U('meter_reading'));
        }else{
            $this->error('添加失败！');
        }
    } else {
        $database_house_village_floor = D('House_village_floor');
        $condition['village_id'] = $this->village_id;
        $condition['status'] = 1;
        $floor_list = $database_house_village_floor->house_village_floor_page_list($condition , true ,'floor_id desc' , 99999);

        if(!$floor_list){
            $this->error('数据处理有误！');
        }
        if(!$floor_list['status']){
            $this->error($floor_list['msg']);
        }else{

            if(!count($floor_list['list']['list'])){
                $this->error(($this->house_alias['floor_name']?:'单元').'不存在，请先添加！');
            }

            $this->assign('floor_list' ,$floor_list['list']);

        }

        $village_id = $this->village_id;
        if($village_id) {
            $village_info = D('House_village')->field(true)->where(array('village_id' => $village_id))->find();
            $dataList = array();
            if($village_info['water_price'] > 0){
                $dataList[] = array(
                    'title'=>'水费',
                    'nickname'=>'水费---'.$village_info['water_price'],
                    'money'=>$village_info['water_price']
                );
            }
            if($village_info['electric_price'] > 0){
                $dataList[] = array(
                    'title'=>'电费',
                    'nickname'=>'电费---'.$village_info['electric_price'],
                    'money'=>$village_info['electric_price']
                );
            }
            if($village_info['gas_price'] > 0){
                $dataList[] = array(
                    'title'=>'煤气费',
                    'nickname'=>'煤气费---'.$village_info['gas_price'],
                    'money'=>$village_info['gas_price']
                );
            }
            $this->assign('dataList',$dataList);
        }

        //楼栋
        $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->select();
        if($single_list){
            $this->assign('single_list',$single_list);
        }else{
            $this->assign('single_list',[]);
        }
        $this->display();
    }
}

    /**
     * 获取移动抄表项目
     * @author lijie
     * @date_time 2020/12/14
     */
    public function get_unit_fee()
    {
        $floor_id = $_POST['floor_id'];
        $dataList = array();
        if($floor_id){
            $village_id = $this->village_id;
            $floor_info = M('House_village_floor')->where(['floor_id'=>$floor_id])->find();
            $village_info = D('House_village')->field(true)->where(array('village_id' => $village_id))->find();
            if($floor_info['water_fee'] > 0 )
                $water_fee = $floor_info['water_fee'];
            else
                $water_fee = $village_info['water_price'];
            if($floor_info['electric_fee'] > 0 )
                $electric_fee = $floor_info['electric_fee'];
            else
                $electric_fee = $village_info['electric_price'];
            if($floor_info['gas_fee'] > 0 )
                $gas_fee = $floor_info['gas_fee'];
            else
                $gas_fee = $village_info['gas_price'];
            if($water_fee > 0){
                $dataList[] = array(
                    'title'=>'水费',
                    'nickname'=>'水费---'.$water_fee,
                    'money'=>$water_fee
                );
            }
            if($electric_fee > 0){
                $dataList[] = array(
                    'title'=>'电费',
                    'nickname'=>'电费---'.$electric_fee,
                    'money'=>$electric_fee
                );
            }
            if($gas_fee > 0){
                $dataList[] = array(
                    'title'=>'燃气费',
                    'nickname'=>'燃气费---'.$gas_fee,
                    'money'=>$gas_fee
                );
            }
        }
        exit(json_encode(array('status'=>1,'list'=>$dataList)));
    }

    //删除
    public function meter_reading_del(){

    $res = M('House_village_meter_reading')->where(array('id'=>$_GET['id']))->delete();
    if($res){
        $this->success('删除成功',U('meter_reading'));die;
    }else{
        $this->error('删除失败');die;
    }
}
    public function ajax_user_bind(){
    //检测用户是否已存在
    $Map['usernum'] =  $this->village_id . '-' . $_POST['usernum'];
    $database_house_village_user_bind = D('House_village_user_bind');
    if ($database_house_village_user_bind->field('`usernum`')->where($Map)->find()) {
        exit(json_encode(array('status'=>1)));
    }else{
        exit(json_encode(array('status'=>0)));
    }
}


    public function ajax_get_layer(){
    if(IS_AJAX){
        $floor_id = $_POST['floor_id'] + 0;
        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $where['floor_id'] = $floor_id;
//            $where['status'] = 1;
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where ,true ,'pigcms_id desc' , 999999999);

        if(!$result){
            exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
        }else{
            if(!$result['status']){
                exit(json_encode(array('status'=>0,'msg'=>$result['msg'])));
            }else{
                exit(json_encode(array('status'=>1,'list'=>$result['result']['list'])));
            }
        }
    }else{
        $this->error('访问页面有误！~~~~');
    }
}
    public function meter_reading_import()
{
    //2020/8/24 start 新增
    $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->village_id);
    if(!$is_open_pay_management){
        //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
        $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
    }
    //2020/8/24 end 新增

    if (IS_POST) {

        if ($_FILES['file']['error'] != 4) {
            set_time_limit(0);

            import('ORG.Util.Dir');
            Dir::delDirnotself('./upload/excel/villageuser');

            $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 10 * 1024 * 1024;
            $upload->allowExts = array('xls', 'xlsx');
            $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
            $upload->savePath = $upload_dir;
            $upload->thumb = false;
            $upload->thumbType = 0;
            $upload->imageClassPath = '';
            $upload->thumbPrefix = '';
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                $objReader = PHPExcel_IOFactory::createReader($fileType);
                $excelObj = $objReader->load($path);
                $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                unlink($path);    //删除上传的excel表格

                $error_arr = array();
                if (!empty($result) && is_array($result)) {
                    unset($result[1]);
                    $last_user_id = 0;
                    $err_msg = '';
                    $db_house_village_single = D('House_village_single');
                    $db_house_village_floor = D('House_village_floor');
                    $db_house_village_layer = D('House_village_layer');
                    $db_house_village_user_vacancy = D('House_village_user_vacancy');
                    foreach ($result as $kk => $vv) {
                        if ($vv['A'] === null &&
                            $vv['B'] === null &&
                            $vv['C'] === null &&
                            $vv['D'] === null &&
                            $vv['E'] === null &&
                            $vv['F'] === null &&
                            $vv['G'] === null &&
                            $vv['H'] === null &&
                            $vv['I'] === null &&
                            $vv['J'] === null &&
                            $vv['K'] === null &&
                            $vv['L'] === null)
                            continue;
                        if (array_sum($vv) == 0) {
                            continue;
                        }
                        if(empty($vv['J'])){
                            $vv['J'] = 1;
                        }
                        if ($vv['B'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null) continue;
                        $b_val = trim($vv['B']);
                        if (empty($b_val)) {
                            $vv['M'] = '请填写楼号！';
                            $error_arr[] = $vv;
                            continue;
                        }
                        $c_val = trim($vv['C']);
                        if (empty($c_val)) {
                            $vv['M'] = '请填写'.($this->house_alias['floor_name']?:'单元').'名称！';
                            $error_arr[] = $vv;
                            continue;
                        }
                        $d_val = trim($vv['D']);
                        if (empty($d_val)) {
                            $vv['M'] = '请填写层号！';
                            $error_arr[] = $vv;
                            continue;
                        }
                        $e_val = trim($vv['E']);
                        if (empty($e_val)) {
                            $vv['M'] = '请填写'.(D('House_village')->getHouseAlias('room_name')?:'房间').'号！';
                            $error_arr[] = $vv;
                            continue;
                        }

                        //搜楼号号id
                        if ($b_val) {
                            $where_single = [
                                'village_id' => $this->village_id,
                                'single_name' => $b_val,
                                'status'=>1
                            ];
                            $single_id = $db_house_village_single->where($where_single)->getField('id');
                            if (!$single_id) {
                                $vv['M'] = '填写的楼号不存在【注意需要和对应'.($this->house_alias['single_name']?:'楼栋').'名称一致】！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $user_vacancy['single_id'] = $single_id;
                        }
                        if ($c_val) {
                            $where_floor = [
                                'village_id' => $this->village_id,
                                'single_id' => $single_id,
                                'floor_name' => $c_val,
                                'status'=>1
                            ];
                            $floor_id = $db_house_village_floor->where($where_floor)->getField('floor_id');
                            if (!$floor_id) {
                                $vv['M'] = '填写的'.($this->house_alias['floor_name']?:'单元').'名称不存在【注意需要和对应'.($this->house_alias['floor_name']?:'单元').'名称一致】！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $user_vacancy['floor_id'] = $floor_id;
                        }
                        if ($d_val) {
                            $where_layer = [
                                'village_id' => $this->village_id,
                                'single_id' => $single_id,
                                'floor_id' => $floor_id,
                                'layer_name' => $d_val,
                                'status'=>1
                            ];
                            $layer_id = $db_house_village_layer->where($where_layer)->getField('id');
                            if (!$layer_id) {
                                $vv['M'] = '填写的层号不存在【注意需要和对应楼层名称一致】！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $user_vacancy['layer_id'] = $layer_id;
                        }
                        if ($e_val) {
                            $user_vacancy['room'] = $e_val;
                        }
                        $user_vacancy['village_id'] = $this->village_id;
                        $house_village_user_vacancy = $db_house_village_user_vacancy->where($user_vacancy)->find();
                        if (empty($house_village_user_vacancy['pigcms_id'])) {
                            fdump($user_vacancy,'$house_village_user_vacancy');
                            $vv['M'] = (D('House_village')->getHouseAlias('room_name')?:'房间').'不存在。';
                            $error_arr[] = $vv;
                            $err_msg .= (D('House_village')->getHouseAlias('room_name')?:'房间').'不存在。' . PHP_EOL;
                            continue;
                        }
                        $h_val = trim($vv['H']);
                        $last_ammeter = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                        $charge_name = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                        if (!is_numeric($h_val)) {
                            if ($house_village_user_vacancy['pigcms_id']) {
                                $where_record = array(
                                    'layer_num' => $house_village_user_vacancy['pigcms_id'],
                                    'floor_id' => $floor_id,
                                    'charge_name' => $charge_name,
                                );
                                $res = D('House_village_meter_reading')->where($where_record)->order('id DESC')->find();
                                if ($res['last_ammeter']) {
                                    $ammeter = $res['last_ammeter'];
                                    if ($ammeter>$last_ammeter) {
                                        $vv['M'] = "填写止度小于上一次录入的止度 {$ammeter}！";
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                } else {
                                    $ammeter = 0;
                                }
                            }
                        } else {
                            $ammeter = $h_val;
                        }
                        if ($ammeter>$last_ammeter) {
                            $vv['M'] = '填写止度小于起度！';
                            $error_arr[] = $vv;
                            continue;
                        }

                        $tmpdata = array();
                        $start_name = '';
                        $start_time = '';
                        $last_name = '';
                        $last_time = '';
                        if ($_POST['time_start']) {
                            $start_time = strtotime($_POST['time_start'] . '-01 00:00:00');
                            $start_name = $_POST['time_start'];
                            $last_time = explode('-', $_POST['time_start']);
                            $last_name = $_POST['time_start'];
                            if ($last_time[1] == 1 || $last_time[1] == 3 || $last_time[1] == 5 || $last_time[1] == 7 || $last_time[1] == 8 || $last_time[1] == 10 || $last_time[1] == 12) {
                                $last_time = strtotime($_POST['last_time'] . '-31 23:59:59');
                            } elseif ($last_time[1] == 2) {
                                if ($last_time[0] % 4 == 0) {
                                    $last_time = strtotime($_POST['last_time'] . '-29 23:59:59');
                                } else {
                                    $last_time = strtotime($_POST['last_time'] . '-28 23:59:59');
                                }
                            } else {
                                $last_time = strtotime($_POST['last_time'] . '-30 23:59:59');
                            }
                        } elseif ($vv['A']) {
                            $start_time = strtotime($vv['A'] . '-01 00:00:00');
                            $start_name = $vv['A'];
                            $last_time = explode('-', $vv['A']);
                            if (count($last_time)!=2) {
                                $vv['M'] = '【月份(可不填)】列格式错误，请按照注意事项填写！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $last_name = $vv['A'];
                            if ($last_time[1] == 1 || $last_time[1] == 3 || $last_time[1] == 5 || $last_time[1] == 7 || $last_time[1] == 8 || $last_time[1] == 10 || $last_time[1] == 12) {
                                $last_time = strtotime($_POST['last_time'] . '-31 23:59:59');
                            } elseif ($last_time[1] == 2) {
                                if ($last_time[0] % 4 == 0) {
                                    $last_time = strtotime($_POST['last_time'] . '-29 23:59:59');
                                } else {
                                    $last_time = strtotime($_POST['last_time'] . '-28 23:59:59');
                                }
                            } else {
                                $last_time = strtotime($_POST['last_time'] . '-30 23:59:59');
                            }
                        }

                        $tmpdata['village_id'] = $this->village_id;
                        $tmpdata['single_id'] = $single_id;
                        $tmpdata['floor_id'] = $floor_id;
                        $tmpdata['layer_id'] = $layer_id;
                        $tmpdata['layer_num'] = $house_village_user_vacancy['pigcms_id'];
                        $tmpdata['start_time'] = htmlspecialchars(trim($start_time), ENT_QUOTES);
                        $tmpdata['start_name'] = $start_name;
                        $tmpdata['last_time'] = htmlspecialchars(trim($last_time), ENT_QUOTES);
                        $tmpdata['last_name'] = $last_name;
                        $tmpdata['charge_name'] = $charge_name;

                        $tmpdata['unit_price'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);//收费单价
                        $tmpdata['start_ammeter'] = $ammeter;
                        $tmpdata['last_ammeter'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);

                        $tmpdata['rate'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                        $tmpdata['pool_ammeter'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                        $tmpdata['note'] = htmlspecialchars(trim($vv['L']), ENT_QUOTES);

                        $tmpdata['cost_num'] = ($vv['I'] - $ammeter + $vv['K']) * $vv['J'];
                        $tmpdata['cost_money'] = round($vv['G'] * $tmpdata['cost_num'],2);
                        $tmpdata['add_time'] = time();
                        // 去除重复导入
                        $whereRepeat = [
                            'village_id' => $tmpdata['village_id'],
                            'single_id' => $tmpdata['single_id'],
                            'floor_id' => $tmpdata['floor_id'],
                            'layer_id' => $tmpdata['layer_id'],
                            'layer_num' => $tmpdata['layer_num'],
                            'charge_name' => $tmpdata['charge_name'],
                        ];
                        if ($tmpdata['unit_price']) {
                            $whereRepeat['unit_price'] = $tmpdata['unit_price'];
                        }
                        if ($tmpdata['last_ammeter']) {
                            $whereRepeat['last_ammeter'] = $tmpdata['last_ammeter'];
                        }
                        if ($tmpdata['rate']) {
                            $whereRepeat['rate'] = $tmpdata['rate'];
                        }
                        $repectId = D('House_village_meter_reading')->where($whereRepeat)->getField('id');
                        if ($repectId) {
                            $vv['M'] = "该条抄表录入重复导入（编号{$repectId}）！";
                            $error_arr[] = $vv;
                            continue;
                        }

                        $last_user_id = D('House_village_meter_reading')->data($tmpdata)->add();
                        $where['vacancy_id'] = $tmpdata['layer_num'];
                        $where['type'] = array('in','0,3');
                        $where['status'] = 1;
                        $res = D('House_village_user_bind')->where($where)->find();
                        if ($start_time) {
                            $ydate = date('Y', $start_time);
                            $mdate = date('m', $start_time);
                        } elseif($last_time) {
                            $ydate = date('Y', $last_time);
                            $mdate = date('m', $start_time);
                        }
                        $gas['ydate']= $electric['ydate']= $water['ydate'] = $ydate? $ydate : '';
                        $gas['mdate']= $electric['mdate']= $water['mdate'] = $mdate? $mdate : '';
                        $gas['type']= $electric['type']= $water['type'] = 1;
                        $gas['village_id']= $electric['village_id']= $water['village_id'] = $this->village_id;
                        $gas['uid']= $electric['uid']= $water['uid'] = $res['uid'];
                        $gas['usernum']= $electric['usernum']= $water['usernum'] = $res['usernum'];
                        $gas['name']= $electric['name']= $water['name'] = $res['name'];
                        $gas['phone']= $electric['phone']= $water['phone'] = $res['phone'];
                        $gas['bind_id']= $electric['bind_id']= $water['bind_id'] = $res['pigcms_id'];
                        $gas['add_time']= $electric['add_time']= $water['add_time'] = time();
                        if ($last_user_id && $res) {
                            //生成未缴费账单
                            if (trim($vv['F']) == '电费') {
                                M('House_village_user_bind')->where(array('pigcms_id' => $res['pigcms_id']))->setInc('electric_price', $tmpdata['cost_money']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$tmpdata['cost_money'],'detail'=>'增加'.$tmpdata['cost_money'].'元','action'=>'导入抄表单-增加','add_time'=>time(),'remark'=>$tmpdata['note'],'type'=>2]);
                                $electric['electric_price'] = $tmpdata['cost_money'];
                                M('House_village_user_paylist')->data($electric)->add();
                                fdump('电费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            } elseif (trim($vv['F']) == '煤气费') {
                                M('House_village_user_bind')->where(array('pigcms_id' => $res['pigcms_id']))->setInc('gas_price' , $tmpdata['cost_money']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$tmpdata['cost_money'],'detail'=>'增加'.$tmpdata['cost_money'].'元','action'=>'导入抄表单-增加','add_time'=>time(),'remark'=>$tmpdata['note'],'type'=>3]);
                                $gas['gas_price'] = $tmpdata['cost_money'];
                                M('House_village_user_paylist')->data($gas)->add();
                                fdump('煤气费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            } elseif (trim($vv['F']) == '水费') {
                                M('House_village_user_bind')->where(array('pigcms_id' => $res['pigcms_id']))->setInc('water_price' , $tmpdata['cost_money']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$res['pigcms_id'],'money'=>$tmpdata['cost_money'],'detail'=>'增加'.$tmpdata['cost_money'].'元','action'=>'导入抄表单-增加','add_time'=>time(),'remark'=>$tmpdata['note'],'type'=>1]);
                                $water['water_price'] = $tmpdata['cost_money'];
                                M('House_village_user_paylist')->data($water)->add();
                                fdump('水费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            }
                        }

                        if (!$last_user_id) {
                            $err_msg = '抄表单导入失败！';
                            $error_arr[] = $vv;
                            $vv['M'] = $err_msg;
                        }
                    }
                    if (empty($error_arr)) {
                        $this->success('导入成功');
                        exit;
                    } else {

                        //导出失败信息
                        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                        error_reporting(E_ALL);
                        date_default_timezone_set('Europe/London');
                        $objExcel = new PHPExcel();

                        $title = $this->village['village_name'] . ($this->house_alias['house_name']?:'小区').'-抄表导入失败列表';
                        /*以下是一些设置 ，什么作者  标题之类的*/
                        $objExcel->getProperties()->setCreator($title)
                            ->setLastModifiedBy($title)
                            ->setTitle($title)
                            ->setSubject("失败信息导出")
                            ->setDescription("备份数据")
                            ->setKeywords("excel")
                            ->setCategory("result file");

                        $i = 0;
                        $objExcel->createSheet();
                        $objExcel->setActiveSheetIndex($i);
                        $objExcel->getActiveSheet()->setTitle('抄表列表');
                        $objActSheet = $objExcel->getActiveSheet();
                        $objActSheet->setCellValue('A1', '月份');
                        $objActSheet->setCellValue('B1', '楼号');
                        $objActSheet->setCellValue('C1', ($this->house_alias['floor_name']?:'单元').'名称');
                        $objActSheet->setCellValue('D1', '层号');
                        $objActSheet->setCellValue('E1', (D('House_village')->getHouseAlias('room_name')?:'房间').'号');
                        $objActSheet->setCellValue('F1', '收费项目');
                        $objActSheet->setCellValue('G1', '单价');
                        $objActSheet->setCellValue('H1', '起度');
                        $objActSheet->setCellValue('I1', '止度');
                        $objActSheet->setCellValue('J1', '倍率');
                        $objActSheet->setCellValue('K1', '公摊');
                        $objActSheet->setCellValue('L1', '备注');
                        $objActSheet->setCellValue('M1', '失败原因');
                        $index = 2;
                        foreach ($error_arr as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                            $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                            $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                            $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                            $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                            $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                            $objActSheet->setCellValueExplicit('K' . $index, $value['K']);
                            $objActSheet->setCellValueExplicit('L' . $index, $value['L']);
                            $objActSheet->setCellValueExplicit('M' . $index, $value['M']);
                            $index++;
                        }

                        //输出
                        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                        ob_end_clean();
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type:application/force-download");
                        header("Content-Type:application/vnd.ms-execl");
                        header("Content-Type:application/octet-stream");
                        header("Content-Type:application/download");
                        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                        header("Content-Transfer-Encoding:binary");
                        $objWriter->save('php://output');
                        exit();
                    }
                }
            } else {
                $this->error('导入失败');
                exit;
            }
        }
        $this->error('文件上传失败');
        exit;
    } else {
        $this->display();
    }
  }

    public function user_export(){
        //业主-导出 权限
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $is_platform = $_GET['is_platform'] + 0;
        if ($find_value) {
            if ($find_type == 1) {
//                $where['usernum'] = array('like', '%' . $find_value . '%');
                $where['_string'] = "b.usernum LIKE '%{$find_value}%' OR b.property_number LIKE '%{$find_value}%'";
            } else if ($find_type == 2) {
                $where['b.name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['b.phone'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 4) {
                $where['b.address'] = array('like', '%' . $find_value . '%');
            }
        }

        if($is_platform == 1){
            $where['uid'] = array('neq',0);
        }elseif ($is_platform == 2) {
            $where['uid'] = array('eq',0);
        }
        $single_id = $_GET['single_id'];
        $floor_id = $_GET['floor_id'];
        $layer_id = $_GET['layer_id'];
        $vacancy_id = $_GET['vacancy_id'];
        if($single_id){
            $where['m.single_id'] = $single_id;
        }
        if($floor_id){
            $where['m.floor_id'] = $floor_id;
        }
        if($layer_id){
            $where['m.layer_id'] = $layer_id;
        }
        if($vacancy_id){
            $where['m.vacancy_id'] = $vacancy_id;
        }
        if($_GET['charge_name']){
            $where['m.charge_name'] = $_GET['charge_name'];
        }
        $property_endtime_start = $_GET['property_endtime_start'];
        $property_endtime_end = $_GET['property_endtime_end'];

        if($property_endtime_start && $property_endtime_end){
            $start_time = strtotime($property_endtime_start);
            $end_time = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('between',array($start_time,$end_time));
        }else if($property_endtime_start){
            $property_endtime_start = strtotime($property_endtime_start);
            $where['property_endtime'] = array('egt',$property_endtime_start);
        }else if($property_endtime_end){
            $property_endtime_end = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('lt',$property_endtime_end);
        }

        //2020/8/24 start 新增
        $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->village_id);
        if(!$is_open_pay_management){
            //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
            $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
        }
        //2020/8/24 end 新增

        $village_id = $this->village_id;
        $where['m.village_id'] = $village_id;

        $join=' left join pigcms_house_village_user_vacancy as b on m.layer_num=b.pigcms_id left join pigcms_house_village_floor as f on f.floor_id = m.floor_id left join pigcms_house_village_single as s on f.single_id=s.id left join pigcms_house_village_layer as l on l.id=m.layer_id';
        $field='m.*,b.room,b.usernum,b.property_number,f.floor_name,f.floor_layer,s.single_name,l.layer_name';
        $user_list = M('House_village_meter_reading')->alias('m')->field($field)->join($join)->where($where)->select();

        if(count($user_list) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '抄表单列表';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($user_list)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('抄表单列表');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '月份');
            $objActSheet->setCellValue('B1', '楼号');
            $objActSheet->setCellValue('C1', ($this->house_alias['floor_name']?:'单元').'名称');
            $objActSheet->setCellValue('D1', '楼层');
            $objActSheet->setCellValue('E1', (D('House_village')->getHouseAlias('room_name')?:'房间').'号');
            $objActSheet->setCellValue('F1', '收费项目');
            $objActSheet->setCellValue('G1', '收费单价');
            $objActSheet->setCellValue('H1', '起度');
            $objActSheet->setCellValue('I1', '止度');
            $objActSheet->setCellValue('J1', '倍率');
            $objActSheet->setCellValue('K1', '公摊');
            $objActSheet->setCellValue('L1', '添加时间');
            $objActSheet->setCellValue('M1', '备注');
            if (!empty($user_list)) {
                $index = 2;

                $cell_list = range('A','M');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(40);
                }

                foreach ($user_list as $value) {
                    if($value['property_number']) {
                        $usernum = $value['property_number'];
                    } else {
                        $usernum = $value['usernum'];
                    }
                    if($value['add_time'] && $value['add_time'] != 0){
                        $add_time = date('Y-m-d H:i:s',$value['add_time']);
                    }else{
                        $add_time = '';
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, $value['start_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['single_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['floor_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['layer_name']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['room']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['charge_name']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['unit_price']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['start_ammeter']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['last_ammeter']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['rate']);
                    $objActSheet->setCellValueExplicit('K' . $index, $value['pool_ammeter']);
                    $objActSheet->setCellValueExplicit('L' . $index, $add_time);
                    $objActSheet->setCellValueExplicit('M' . $index, $value['note']);
                    $index++;
                }
            }
//            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    public function user_download(){
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if ($is_new_charge){
            $user_list[] = array(
                '小黑','13212345679','30栋','三单元','12','1102',
                '342417************','123456','皖A12345','A车库','001',
                '家中有狗','2020-03-24', '2050-07-24'
            );

        }else{
            $user_list[] = array(
            '小黑', '13212345679','11120.1',
            '1120.1','1120.1','1',
            '1120.1','30栋','三单元',
            '12','1102','2017-07-24',
            '2018-07-24','342417************','123456',
            '皖A12345','A车库','001','家中有狗',
        );

        }

        $title = '导入业主模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = 1;

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('导入业主模板');
            $objActSheet = $objExcel->getActiveSheet();

            if ($is_new_charge) {
                $objActSheet->setCellValue('A1', '业主名(必填)');
                $objActSheet->setCellValue('B1', '手机号(必填)');
                $objActSheet->setCellValue('C1', '所属楼栋(必填)');
                $objActSheet->setCellValue('D1', '所属单元(必填)');
                $objActSheet->setCellValue('E1', '所属楼层(必填)');
                $objActSheet->setCellValue('F1', '房间号(必填)');
                $objActSheet->setCellValue('G1', '身份证号(选填)');
                $objActSheet->setCellValue('H1', 'IC卡号(选填)');
                $objActSheet->setCellValue('I1', '车辆(选填)');
                $objActSheet->setCellValue('J1', '车库(选填)');
                $objActSheet->setCellValue('K1', '车位(选填)');
                $objActSheet->setCellValue('L1', '备注(选填)');
                $objActSheet->setCellValue('M1', '缴费开始时间(选填)');
                $objActSheet->setCellValue('N1', '缴费结束时间(选填)');
            }else{
                $objActSheet->setCellValue('A1', '业主名');
                $objActSheet->setCellValue('B1', '手机号');
                $objActSheet->setCellValue('C1', '水费总欠费');
                $objActSheet->setCellValue('D1', '电费总欠费');
                $objActSheet->setCellValue('E1', '燃气费总欠费');
                $objActSheet->setCellValue('F1', '是否有停车位（1有0无）');
                $objActSheet->setCellValue('G1', '停车费总欠费');
                //2020/8/6 优化  start
                $objActSheet->setCellValue('H1', '所属'.($this->house_alias['single_name']?:'楼栋'));
                $objActSheet->setCellValue('I1', '所属'.($this->house_alias['floor_name']?:'单元'));
                $objActSheet->setCellValue('J1', '所属楼层');
                $objActSheet->setCellValue('K1', (D('House_village')->getHouseAlias('room_name')?:'房间').'号');
                $objActSheet->setCellValue('L1', '物业开始时间');
                $objActSheet->setCellValue('M1', '物业结束时间');
		        $objActSheet->setCellValue('N1', '身份证号');
                $objActSheet->setCellValue('O1', 'IC卡号');
                $objActSheet->setCellValue('P1', '车辆');
                $objActSheet->setCellValue('Q1', '车库');
                $objActSheet->setCellValue('R1', '车位');
                $objActSheet->setCellValue('S1', '备注');
            }

            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A','T');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                foreach ($user_list as $value) {
                        if ($is_new_charge) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                            $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                            $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                            $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                            $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                            $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                            $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                            $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                            $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                            $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                            $objActSheet->setCellValueExplicit('K' . $index, $value[10]);
                            $objActSheet->setCellValueExplicit('L' . $index, $value[11]);
                            $objActSheet->setCellValueExplicit('M' . $index, $value[12]);
                            $objActSheet->setCellValueExplicit('N' . $index, $value[13]);
                        }else{
                            $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                            $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                            $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                            $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                            $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                            $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                            $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                            $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                            $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                            $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                            $objActSheet->setCellValueExplicit('K' . $index, $value[10]);
                            $objActSheet->setCellValueExplicit('L' . $index, $value[11]);
                            $objActSheet->setCellValueExplicit('M' . $index, $value[12]);
                            $objActSheet->setCellValueExplicit('N' . $index, $value[13]);
                            $objActSheet->setCellValueExplicit('O' . $index, $value[14]);

                            $objActSheet->setCellValueExplicit('P' . $index, $value[15]);
                            $objActSheet->setCellValueExplicit('Q' . $index, $value[16]);
                            $objActSheet->setCellValueExplicit('R' . $index, $value[17]);
                            $objActSheet->setCellValueExplicit('S' . $index, $value[18]);

                        }
                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    // 导入业主
    public function user_import()
    {
        //业主-添加 权限
        if (!in_array(92, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);	//删除物业上传的excel表格
                    $database_user = D('User');
                    $error_arr = array();
                    $where_village['village_id'] = $this->village_id;
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        $property_id = D('House_village')->where($where_village)->getField('property_id');
                        $where_data_config = array(
                            'village_id'=>0,
                            'property_id'=>$property_id,
                            'is_display'=>1,
                        );
                        $village_data_config = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
                        $village_data_config_count = D('House_village_data_config')->where($where_data_config)->count();
                        if (!$village_data_config) {
                            $where_data_config = array(
                                'village_id'=>$this->village_id,
                                'is_display'=>1,
                            );
                            $village_data_config = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
                            $village_data_config_count = D('House_village_data_config')->where($where_data_config)->count();
                        }
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null && $vv['L'] === null && $vv['M'] === null && $vv['N'] === null) continue;

                            //增加导入资料字段控制=>
                            $test = array('R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG');
                            $data_post = [];
                            if($village_data_config){
                                foreach($village_data_config as $kkk=>$vvv){
//                                    var_dump($vv[$test[$kkk]]);
                                    if($vv[$test[$kkk]]){
                                        $arr_post_value[$vvv['key']] = array(
                                            'type'=>$vvv['type'],
                                            'is_must'=>$vvv['is_must'],
                                            'key'=>$vvv['key'],
                                            'title'=>$vvv['title'],
                                            'value'=>$vv[$test[$kkk]],

                                        );
                                    }else{
                                        $arr_post_value[$vvv['key']] = array(
                                            'type'=>$vvv['type'],
                                            'is_must'=>$vvv['is_must'],
                                            'key'=>$vvv['key'],
                                            'title'=>$vvv['title'],
                                            'value'=>'',

                                        );
                                    }
                                    $data_post = $arr_post_value;
                                }
                            }

                            // $vv['N'] = floatval($vv['N']);
                            if (empty($vv['A'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写业主编号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写业主编号！';
                                    continue;
                                }else{
                                    $vv['R'] = '请填写业主编号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写业主编号！';
                                    continue;
                                }

                            }
                            if (empty($vv['B'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写业主名！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写业主编号！';
                                    continue;
                                }else{
                                    $vv['R'] = '请填写业主名！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写业主名！';
                                    continue;
                                }

                            }
                            if (empty($vv['C'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写手机号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写手机号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写手机号！';
                                    continue;
                                }
                            }

                            if(!preg_match('/^[0-9]{11}$/',$vv['C'])){
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写正确的手机号！';
                                    $error_arr[] = $vv;

                                }else {
                                    $vv['R'] = '请填写正确的手机号！';
                                    $error_arr[] = $vv;
                                }
                            }

                            if (empty($vv['I'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写'.($this->house_alias['floor_name']?:'单元').'名称！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写单'.($this->house_alias['floor_name']?:'单元').'名称！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写单元名称！';
                                    continue;
                                }
                            }

                            if (empty($vv['J'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写楼号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写楼号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写楼号！';
                                    continue;
                                }
                            }


                            if (empty($vv['K'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写层号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写层号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写层号！';
                                    continue;
                                }
                            }


                            if (empty($vv['L'])) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写门牌号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写门牌号！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写门牌号！';
                                    continue;
                                }
                            }

                            // if (empty($vv['M'])) {
                            //     $err_msg = '请填写房子平方！';
                            //     continue;
                            // }

                            $floor_name = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            $floor_layer = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                            $where['floor_name'] = $floor_name;
                            $where['floor_layer'] = $floor_layer;
                            $where['status'] = 1;
                            $where['village_id'] = $this->village_id;
                            $database_house_village_floor = D('House_village_floor');
                            $house_village_floor_info = $database_house_village_floor->where($where)->find();
                            if (!$house_village_floor_info) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = ($this->house_alias['floor_name']?:'单元').'不存在，请查看小区中心，'.($this->house_alias['floor_name']?:'单元').'管理-'.($this->house_alias['floor_name']?:'单元').'列表！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = ($this->house_alias['floor_name']?:'单元').'不存在，请查看小区中心，'.($this->house_alias['floor_name']?:'单元').'管理-'.($this->house_alias['floor_name']?:'单元').'列表!';
                                    $error_arr[] = $vv;
                                    // $err_msg = '单元不存在，请查看社区中心，单元管理-单元列表！';
                                    continue;
                                }
                            }

                            //检查房间是否存在或已绑定
                            $vwhere['village_id'] = $this->village_id;
                            $vwhere['floor_id'] =  $house_village_floor_info['floor_id'];
                            $vwhere['layer'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                            $vwhere['room'] = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                            $vwhere['type'] = 0;
                            $room_info = D('House_village_user_vacancy')->where($vwhere)->find();

                            if(!$room_info){
                                $err_msg = htmlspecialchars(trim($vv['I']), ENT_QUOTES).htmlspecialchars(trim($vv['J']), ENT_QUOTES). htmlspecialchars(trim($vv['K']), ENT_QUOTES).htmlspecialchars(trim($vv['L']), ENT_QUOTES).(D('House_village')->getHouseAlias('room_name')?:'房间')."不存在";
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }elseif($room_info['status']!=1 || $room_info['is_del']==1 || ($room_info['name']!="" && $room_info['phone']!="")){
                                $err_msg = htmlspecialchars(trim($vv['J']), ENT_QUOTES).htmlspecialchars(trim($vv['I']), ENT_QUOTES). htmlspecialchars(trim($vv['K']), ENT_QUOTES).'层'.htmlspecialchars(trim($vv['L']), ENT_QUOTES)."可能被绑定/禁用/删除";
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }


                            $tmpdata = array();
                            $tmpdata['usernum'] =$this->village_id . '-' . htmlspecialchars(trim($vv['A']), ENT_QUOTES);

                            //检测用户是否已存在
                            if (D('House_village_user_bind')->field('`usernum`')->where(array('usernum' => $tmpdata['usernum']))->find()) {
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '业主名为' . $vv['B'] . ' 已存在！物业编号重复';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $err_msg = '业主名为' . $vv['B'] . ' 已存在！物业编号重复';
                                    $vv['R'] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }



                            // 属性
                            $attribute_name = htmlspecialchars(trim($vv['Q']), ENT_QUOTES);
                            if ($attribute_name) {
                                $attribute = M('House_village_user_attribute')->where(array('village_id'=>$this->village_id,'name'=>$attribute_name))->find();
                                if ($attribute) {
                                    $tmpdata['attribute'] = $attribute['pigcms_id'];
                                }
                            }
                            $tmpdata['name'] = htmlspecialchars(str_replace(array(' ',PHP_EOL,"\t"),'',$vv['B']), ENT_QUOTES);
                            $tmpdata['phone'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['water_price'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['electric_price'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['gas_price'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            $tmpdata['park_flag'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            $tmpdata['park_price'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                            $tmpdata['layer_num'] = $vv['K'];
                            $room_addrss = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                            $tmpdata['room_addrss'] = $room_addrss;
                            $tmpdata['address'] = $floor_layer . $floor_name . htmlspecialchars(trim($vv['K']), ENT_QUOTES)  . '#' . $room_addrss;
                            $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];




                            //$van = M('House_village_user_vacancy')->where(array('floor_id'=>$tmpdata['floor_id'],'room'=>$room_addrss,'village_id'=>$this->village_id))->find();
                            //$tmpdata['vacancy_id'] = $van['pigcms_id']?$van['pigcms_id']:0;
                            $tmpdata['village_id'] = $this->village_id;
                            if($memo = htmlspecialchars(trim($vv['M']), ENT_QUOTES)){
                                $tmpdata['memo'] = $memo;
                            }


                            $user = $database_user->get_user($tmpdata['phone'], 'phone');
                            if ($user) {
                                $tmpdata['uid'] = $user['uid'];
                            }

                            $tmpdata['add_time'] = time();

                            if($_POST['property_starttime'] > $_POST['property_endtime']){
                                $error_arr[] = $vv;
                                $vv['R'] = '请填写正确的物业时间！';
                                // $this->error('请填写正确的物业时间！');
                            }
                            $tmpdata['property_starttime'] = strtotime(trim($vv['N']));
                            $tmpdata['property_endtime'] = strtotime(trim($vv['O']));
                            $tmpdata['vacancy_id'] = $room_info['pigcms_id'];
                            if($room_info['housesize'] > 0.00){
                                $tmpdata['housesize'] = round($room_info['housesize'],2);
                            }else{
                                $tmpdata['housesize'] = round(htmlspecialchars(trim($vv['P']), ENT_QUOTES),2);
                            }
                            if($data_post){
                                $tmpdata['authentication_field'] = serialize($data_post);
                            }else{
                                $tmpdata['authentication_field'] = '';
                            }

//                            echo '<pre>';
//                            var_dump($tmpdata);
//                            die;
                            $last_user_id = D('House_village_user_bind')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                if($village_data_config_count > 0){

                                    $err_msg = '业主名为' . $vv['B'] . ' 导入失败！';
                                    $error_arr[] = $vv;
                                    $vv[$test[$village_data_config_count]] = $err_msg;
                                }else {
                                    $err_msg = '业主名为' . $vv['B'] . ' 导入失败！';
                                    $error_arr[] = $vv;
                                    $vv['R'] = $err_msg;
                                }
                            }else{
                                //导入成功 绑定此房间
                                $data_vacancy = array();
                                $data_vacancy['uid'] = $tmpdata['uid'] ? $tmpdata['uid'] : 0;
                                $data_vacancy['status'] = 3;
                                $data_vacancy['name'] = $tmpdata['name'];
                                $data_vacancy['phone'] = $tmpdata['phone'];
                                $data_vacancy['housesize'] = $tmpdata['housesize'];
                                $data_vacancy['park_flag'] = $tmpdata['park_flag'];
                                D('House_village_user_vacancy')->where($vwhere)->data($data_vacancy)->save();
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $test = array('R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG');
                            $num = count($error_arr);
                            // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = '导入失败列表';
                            // $title = $this->village['village_name'] . '社区-导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle($title);
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '业主编号');
                            $objActSheet->setCellValue('B1', '业主名');
                            $objActSheet->setCellValue('C1', '手机号');
                            $objActSheet->setCellValue('D1', '水费总欠费');
                            $objActSheet->setCellValue('E1', '电费总欠费');
                            $objActSheet->setCellValue('F1', '燃气费总欠费');
                            $objActSheet->setCellValue('G1', '是否有停车位（1有0无）');
                            $objActSheet->setCellValue('H1', '停车费总欠费');
                            $objActSheet->setCellValue('I1', ($this->house_alias['floor_name']?:'单元').'名称');
                            $objActSheet->setCellValue('J1', '楼号');
                            $objActSheet->setCellValue('K1', '层号');
                            $objActSheet->setCellValue('L1', '门牌号');
                            $objActSheet->setCellValue('M1', '备注');
                            $objActSheet->setCellValue('N1', '物业开始时间');
                            $objActSheet->setCellValue('O1', '物业结束时间');
                            $objActSheet->setCellValue('P1', '房子平方(计算物业费使用)');
                            $objActSheet->setCellValue('Q1', '属性');
                            if($village_data_config){
                                foreach($village_data_config as $kk=>$vv){
                                    $objActSheet->setCellValue($test[$kk].'1', $vv['title']);
                                }
                                $objActSheet->setCellValue($test[$village_data_config_count].'1', '失败信息');
                            }else{
                                $objActSheet->setCellValue('R1', '失败信息');
                            }

                            $index = 2;
                            foreach ($error_arr as  $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                                $objActSheet->setCellValueExplicit('K' . $index, $value['K']);
                                $objActSheet->setCellValueExplicit('L' . $index, $value['L']);
                                $objActSheet->setCellValueExplicit('M' . $index, $value['M']);
                                $objActSheet->setCellValueExplicit('N' . $index, $value['N']);
                                $objActSheet->setCellValueExplicit('O' . $index, $value['O']);
                                $objActSheet->setCellValueExplicit('P' . $index, number_format($value['P'],2));
                                $objActSheet->setCellValueExplicit('Q' . $index, $value['Q']);

                                if($village_data_config){
                                    foreach($village_data_config as $kk=>$vv){
                                        $objActSheet->setCellValueExplicit($test[$kk] . $index, $value[$test[$kk]]);
                                    }
                                    $objActSheet->setCellValueExplicit($test[$village_data_config_count] . $index, $value[$test[$village_data_config_count]]);
                                }else{
                                    $objActSheet->setCellValueExplicit('R' . $index, $value['R']);
                                }

                                $index++;
                            }


                            //输出
                            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                            ob_end_clean();
                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type:application/force-download");
                            header("Content-Type:application/vnd.ms-execl");
                            header("Content-Type:application/octet-stream");
                            header("Content-Type:application/download");
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }

                        // if (!empty($last_user_id)) {
                        //     $this->success('导入成功');
                        //     exit;
                        // } else {
                        //     $this->error('导入失败！原因：' . $err_msg);
                        //     exit;
                        // }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->display();
        }
    }

    /**
     * 获取停车位续费后的到期时间
     * @author lijie
     * @date_time 2020/08/17
     * @return false|string
     */
    public function ajax_get_car_end_time()
    {
        $database_house_village_parking_car = D('House_village_parking_car');
        $car_time = $database_house_village_parking_car->where(['car_id'=>$_POST['car_number']])->field('end_time')->find();
        if(!$car_time['end_time']){
            $end_time = '暂无';
            exit(json_encode(array('status'=>1,'end_time'=>$end_time)));
        }
        $end_time = date('Y-m-d H:i:s',strtotime("+" .$_POST['num']. " months", $car_time['end_time']));
        $end_time = date('Y-m-d H:i:s',time()).'至'.$end_time;
        exit(json_encode(array('status'=>1,'end_time'=>$end_time)));
    }

    // 业主每月账单明细-模板
    public function payment_list(){
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '模板-业主每月账单明细';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(40);


        $objActSheet->setCellValue('A1', '业主编号');
        $objActSheet->setCellValue('B1', '业主名');
        $objActSheet->setCellValue('C1', '手机号');
        $objActSheet->setCellValue('D1', '用水(立方米)');
        $objActSheet->setCellValue('E1', '用电(千瓦时[度])');
        $objActSheet->setCellValue('F1', '燃气(立方米)');
        // $objActSheet->setCellValue('G1', '是否需要缴物业费（1需要，0不需要）');
        $objActSheet->setCellValue('G1', '是否需要缴停车费（1需要，0不需要）');

        //筛选数据
        $where['status'] = 1;
        $where['village_id'] = $this->village_id;
        $where['parent_id'] = 0;
        $where['type'] =0 ;
        $bind_user = M('House_village_user_bind')->field('usernum,bind_number,name,phone')->where($where)->select();
        $index = 2;
        foreach ($bind_user as $value) {
            if ($value['bind_number']) {
                $usernum = $value['bind_number'];
            } else {
                $usernum = $value['usernum'];
            }
            $objActSheet->setCellValueExplicit('A' . $index, $usernum);
            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
            $objActSheet->setCellValueExplicit('D' . $index, 0);
            $objActSheet->setCellValueExplicit('E' . $index,0);
            $objActSheet->setCellValueExplicit('F' . $index, 0);
            $objActSheet->setCellValueExplicit('G' . $index, 0);
            // $objActSheet->setCellValueExplicit('H' . $index,0);
            $index++;
        }

        if (!$bind_user) {
            $objActSheet->setCellValueExplicit('A' . $index, '');
            $objActSheet->setCellValueExplicit('B' . $index, '');
            $objActSheet->setCellValueExplicit('C' . $index, '');
            $objActSheet->setCellValueExplicit('D' . $index, '');
            $objActSheet->setCellValueExplicit('E' . $index,'');
            $objActSheet->setCellValueExplicit('F' . $index, '');
            $objActSheet->setCellValueExplicit('G' . $index, '');
            // $objActSheet->setCellValueExplicit('H' . $index,'');
        }

        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    public function payment_user_add() {
        //业主-添加缴费项 权限
        if (!in_array(97, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $payment_id = I('payment_id');
        $standard_id = I('standard_id');
        if (IS_POST) {
            $standard_info = D('House_village_payment_standard')->where(array('payment_id'=>$_POST['payment_id'],'standard_id'=>$_POST['standard_id']))->find();

            $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day', );
            $data['payment_id'] = $_POST['payment_id'];
            $data['standard_id'] = $_POST['standard_id'];

            $data['village_id'] = $this->village_id;
            if($_POST['start_time'] == ''){
                $_POST['start_time'] = date('Y-m-d',time());
            }

            if($_POST['cycle_sum'] == ''){
                $_POST['cycle_sum'] = $standard_info['max_cycle'];
            }
            $data['start_time'] = strtotime($_POST['start_time']);
            $data['end_time'] = strtotime($_POST['start_time'].'+'.$_POST['cycle_sum']*$standard_info['pay_cycle'].$cycle_type[$standard_info['cycle_type']])-1;
            $data['cycle_sum'] = $_POST['cycle_sum'];
            $data['pay_cycle'] = 0;
            $data['remarks'] = $_POST['remarks'];

            $database_house_village_user_bind = D('House_village_user_bind');

            $where_bind = array(
                'status' => 1,
                'type' => array('in', array(0,3)),
                'village_id'=>$this->village_id,
            );
            $bind_user = $database_house_village_user_bind->where($where_bind)->field('pigcms_id,housesize')->select();
            foreach ($bind_user as $key => $value) {
                if($value['pigcms_id']){
                    $data['pigcms_id'] = $value['pigcms_id'];
                    $user_arr[] = $value['pigcms_id'];
                    if($_POST['metering_mode_type'] == 1){
                        $housesize = $value['housesize'];
                        $data['metering_mode_val'] = $housesize;
                    } elseif($_POST['metering_mode_type'] == 3){ //车位面积
                        $condition_table  = array(C('DB_PREFIX').'house_village_bind_position'=>'b',C('DB_PREFIX').'house_village_parking_position'=>'p');
                        $condition_where = " `p`.`position_id` = `b`.`position_id` AND `b`.`user_id` =".$value['pigcms_id'];
                        $condition_field = '`p`.`position_area`';
                        $position_list = D()->field($condition_field)->table($condition_table)->where($condition_where)->select();
                        if ($position_list) {
                            $position_area = 0;
                            foreach ($position_list as $key => $value) {
                                $position_area += $value['position_area'];
                            }
                            $data['metering_mode_val'] = $position_area;
                        }else{
                            $data['metering_mode_val'] = 0;
                        }
                    }else{
                        $data['metering_mode_val'] = 0;
                    }
                    if($standard_info['pay_type'] == 1){
                        $data['metering_mode_val'] = '';
                    }
                    $where_standard_bind = array(
                        'village_id' => $this->village_id,
                        'payment_id' => $_POST['payment_id'],
                        'standard_id' => $_POST['standard_id'],
                        'pigcms_id' => $value['pigcms_id'],
                    );
                    $bind_id = D('House_village_payment_standard_bind')->where($where_standard_bind)->getField('bind_id');
                    if ($bind_id) {
                        $res = D('House_village_payment_standard_bind')->where(array('bind_id' => $bind_id))->data($data)->save();
                    } else {
                        $res = D('House_village_payment_standard_bind')->data($data)->add();
                    }
                }
            }
            // 发送微信通知
            if ($_POST['start_time']<time()) {
                $bind_condition['pigcms_id'] = implode(',',$user_arr);
                $bind_condition['village_id'] = $this->village_id;
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);
                if($user_bind_list['list']){
                    $database_house_village_user_bind->send_weixin_pay($this->village_id,$user_bind_list['list']);
                }
            }

            if($res){
                exit('<html><head><script>window.parent.close_art();</script></head></html>');
            }else{
                $this->error('操作失败');
            }
        } else {
            $payment_info = D('House_village_payment')->where(array('payment_id'=>$payment_id))->find();
            $this->assign('payment_info',$payment_info);
            $standard_info = D('House_village_payment_standard')->where(array('payment_id'=>$payment_id,'standard_id'=>$standard_id))->find();
            $this->assign('standard_info',$standard_info);
            if ($standard_info) {
                $cycle_type = array(
                    'Y'=>'年',
                    'M'=>'月',
                    'D'=>'日',
                );
                $txt = '';
                if($standard_info['pay_type'] == 1){
                    $txt .= '缴费模式: 固定费用; ';
                }else{
                    $txt .= '缴费模式: 按金额*数量 ;';
                    $txt .= '计量方式: '.$standard_info['metering_mode'].'; ';
                }
                $txt .= '缴费金额: '.$standard_info['pay_money'].';  ';
                $txt .= '周期类型: '.$cycle_type[$standard_info['cycle_type']].'; ';
                $txt .= '缴费周期: '.$standard_info['pay_cycle'].'('.$cycle_type[$standard_info['cycle_type']].') / 周期; ';
                $txt .= '周期上限: '.$standard_info['max_cycle'].' (周期) ; ';
                $this->assign('standard_txt',$txt);
            }
            $this->display();
        }
    }

    public function ajax_month_pay_list()
    {
        if(IS_AJAX){
            $car_id = $_POST['car_id'];
            $left_join = 'as c LEFT JOIN '.C('DB_PREFIX').'house_village_parking_position as p ON c.car_position_id=p.position_id';
            $where['c.car_id'] = $car_id;
            $info = D('House_village_parking_car')->get_month_type('p.month_charge_type,c.end_time',$left_join,$where);
            $park_month = D('House_village_park')->where(['park_month_id'=>$info['month_charge_type']])->select();
            if($park_month){
                if(!$info['end_time']){
                    $park_month[0]['end_time'] = '暂无';
                }else{
                    $park_month[0]['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
                }
                exit(json_encode(array('status'=>1,'park_month'=>$park_month)));
            } else{
                exit(json_encode(array('status'=>0,'park_month'=>$park_month)));
            }
        }
    }

    /**
     * ajax获取月租车详情
     * @author lijie
     * @date_time 2020/08/07
     */
    public function ajax_get_park()
    {
        if(IS_AJAX){
            $id = $_POST['id'];
            $park_month = D('House_village_park')->where(['id'=>$id])->find();
            if($park_month){
                exit(json_encode(array('status'=>1,'park'=>$park_month)));
            } else{
                exit(json_encode(array('status'=>0,'park'=>$park_month)));
            }
        }
    }

    public function owner_orders_add()
    {
        //收银台-查看 权限
        if (!in_array(67, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $type = $_POST['type'];
//            $bind_where['usernum'] = $_POST['usernum'];
            $bind_where['_string'] = "usernum='{$_POST['usernum']}' OR bind_number='{$_POST['usernum']}'";
            $database_house_village_user_bind = D('House_village_user_bind');
            $now_bind_info         = $database_house_village_user_bind->where($bind_where)->find();
            $now_village = D('House_village')->get_one($now_bind_info['village_id']);

            if (!$now_bind_info || !$now_bind_info['vacancy_id']) {
                exit(json_encode(array('status'=>0,'info'=>'物业编号不存在')));
            }

            if($type=='property') {
                $database_house_village_property  = D('House_village_property');
                $database_house_village_floor     = D('House_village_floor');



                $now_floor_info = $database_house_village_floor->get_floor_info($now_bind_info['floor_id']);

                $property_where['id'] = $_POST['property_id'] + 0;
                $now_property_info    = $database_house_village_property->house_village_property_detail($property_where);
                $now_property_info    = $now_property_info['detail'];

                if (!$now_property_info) {
                    $this->error('物业缴费周期不存在！');
                    exit(json_encode(array('status'=>0,'info'=>'物业缴费周期不存在！')));
                }

                //算物业费
                $bind_info = M("House_village_user_vacancy")->where(['pigcms_id'=>$now_bind_info['vacancy_id']])->find();
                $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$bind_info['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
                if(!$standard_id['standard_id']){
                    $total_price['all_price'] = 0;
                }else{
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $total_price = $this->get_all_fee(unserialize($date['date_month']),$bind_info['housesize'],strtotime(date('Y-m',time())),$now_property_info['property_month_num']);
                }
                $data['order_name']         = '物业费';
                $data['order_type']         = 'property';
                $data['village_id']         = $this->house_session['village_id'];
                $data['time']               = time();
                $data['property_month_num'] = $now_property_info['property_month_num'];
                $data['floor_type_name']    = $now_floor_info['name'] ? $now_floor_info['name'] : '';
                $data['house_size']         = $now_bind_info['housesize'];
                $data['bind_id']            = $now_bind_info['pigcms_id'];
                $data['uid']                = $now_bind_info['uid'];
                $data['diy_type']           = $now_property_info['diy_type'];
                if ($now_property_info['diy_type'] > 0) {
                    $data['diy_content'] = $now_property_info['diy_content'];
                } else {
                    $data['presented_property_month_num'] = $now_property_info['presented_property_month_num'] ? $now_property_info['presented_property_month_num'] : 0;
                }
                $data['money']        = $total_price['all_price'];
                $data['property_fee'] = $total_price['price']?$total_price['price']:0.00;
                $data['remarks'] = $_POST['remarks'];
                if($now_bind_info['property_endtime'] < time()) {
                    $data['is_owe'] = 1;
                }
                $order_id = D("House_village_pay_order")->add($data);
                if ($order_id) {
                    exit(json_encode(array('status'=>1,'info'=>'添加成功！')));
                } else {
                    exit(json_encode(array('status'=>0,'info'=>'订单创建失败，请重试！')));
                }
            }elseif($type=='park'){
                $database_house_village_parking_car = D('House_village_parking_car');
                $car_time = $database_house_village_parking_car->where(['car_id'=>$_POST['car_number']])->field('end_time')->find();
                $park_data = array();
                $park_data['order_name']         = '收银台：停车费';
                $park_data['order_type']         = 'park';
                $park_data['uid'] = $now_bind_info['uid'];
                $park_data['bind_id'] = $now_bind_info['pigcms_id'];
                $park_data['village_id']         = $this->house_session['village_id'];
                $park_data['money'] = $_POST['park_price'] ;
                $park_data['time']               = time();
                $park_data['score_can_use']               = $_POST['park_price']*100;
                $park_data['score_can_pay']               = $_POST['park_price'];
                $park_data['score_can_get']               = 0;
                $park_data['score_percent']               = 100;
                $park_data['house_size']         = $now_bind_info['housesize'];
                $park_data['car_type'] = $_POST['park_type'];
                $park_data['car_id'] = $_POST['car_number'];
                $park_data['presented_property_month_num'] = $_POST['presented_property_month_num'];
                $park_data['property_month_num'] = $_POST['property_month_num'];
                if($_POST['park_type'] == 'month_type')
                    $park_data['month_end_time'] = strtotime('+'.$_POST['num'].' month',$car_time['end_time']);
                $park_data['remarks'] = $_POST['remarks'];
                $order_id = D("House_village_pay_order")->add($park_data);
                //D("House_village_user_bind")->where(['pigcms_id'=>$now_bind_info['pigcms_id']])->setInc('park_price',$_POST['park_price']);
                if ($order_id) {
                    $this->success('添加成功');
                    exit(json_encode(array('status'=>1,'info'=>'添加成功！')));
                } else {
                    exit(json_encode(array('status'=>0,'info'=>'订单创建失败，请重试！')));
                }
            } else{

                switch($type){
                    case 'water':
                        if(empty($now_village['water_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳水费');
                        $pay_money = $now_bind_info['water_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_water` AS `use`,`water_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用水 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '水费';
                        break;
                    case 'electric':
                        if(empty($now_village['electric_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳电费');
                        $pay_money = $now_bind_info['electric_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_electric` AS `use`,`electric_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用电 '.floatval($value['use']).' 千瓦时(度)，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '电费';
                        break;
                    case 'gas':
                        if(empty($now_village['gas_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳燃气费');
                        $pay_money = $now_bind_info['gas_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_gas` AS `use`,`gas_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '使用燃气 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '燃气费';
                        break;
                    case 'park':
                        if(empty($now_village['park_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳停车费');
                        $pay_money = $now_bind_info['park_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`park_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '停车费 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '停车费';
                        break;
                    case 'custom_payment':
                        $pay_money = $_POST['payment_price'];
                        $data_order['order_name'] = $_POST['payment_name'];
                        $data_order['payment_paid_cycle'] = $_POST['payment_paid_cycle'];
                        $data_order['payment_bind_id'] = $_POST['payment_bind_id'];
                        break;
                    case 'custom':
                        $pay_money = $_POST['custom_price'];
                        $data_order['order_name'] = '自定义缴费【'.$_POST['custom_remark'].'】';
                        break;
                }

                $data_order['money'] = $pay_money ;
                $data_order['uid'] = $now_bind_info['uid'];
                $data_order['bind_id'] = $now_bind_info['pigcms_id'];
                $data_order['village_id'] = $now_village['village_id'];
                $data_order['time'] = $_SERVER['REQUEST_TIME'];
                $data_order['paid'] = 0;
                $data_order['order_type'] = $type;
                $data_order['remarks'] = $_POST['remarks'];

                if($order_id = D('House_village_pay_order')->data($data_order)->add()){
                    exit(json_encode(array('status'=>1,'info'=>'添加成功！')));
                }else{
                    exit(json_encode(array('status'=>0,'info'=>'下单失败，请重试！')));
                }
            }
        }else{
            if (!$_GET['pigcms_id']) {
                $this->error('数据处理有误！');
            }
            $user_info = D('House_village_user_bind')->get_one_by_bindId($_GET['pigcms_id']);
            $this->assign('user_info' , $user_info);
            $this->assign('pigcms_id' , $_GET['pigcms_id']);
            $this->display();
        }
    }

    public function owner_add()
    {
        $database_house_village_property = D('House_village_property');
        $where['village_id'] = $_SESSION['house']['village_id'];
        $where['status'] = 1;

        //收银台过来
        if (!$_GET['pigcms_id']) {
            $this->error('数据处理有误！');
        }

        $user_info = D('House_village_user_bind')->get_one_by_bindId($_GET['pigcms_id']);
        $this->assign('user_info' , $user_info);
        $this->assign('pigcms_id' , $_GET['pigcms_id']);
        $is_property = D('House_village_property_standard_bind')->where(['pigcms_id'=>$user_info['vacancy_id'],'is_del'=>1])->find();
        if($is_property)
            $this->assign('is_property' , 1);
        else
            $this->assign('is_property' , 0);
        $config = D('House_village_park_config')->getConfig(['village_id'=>$_SESSION['house']['village_id']],'is_park_month_type,is_temporary_park_type,park_versions,visitor_money');
        $car_id = D('House_village_bind_car')->field('car_id')->where(['user_id'=>$_GET['pigcms_id']])->select();
        if(empty($config['park_versions']) || empty($car_id) || $config['park_versions'] == 1 || ($config['is_park_month_type'] == 0 && $config['is_temporary_park_type'] == 0)){
            $this->assign('is_parking',0);
        } else{
            $this->assign('is_parking',1);
            $this->assign('visitor_money',$config['visitor_money']);
            $park_config[] = $config['is_park_month_type'];
            $park_config[] = $config['is_temporary_park_type'];
            $this->assign('park_config',$park_config);
            $car = '';
            foreach ($car_id as $k=>$v){
                $car .= $v['car_id'].',';
            }
            $car = rtrim($car,',');
            $where_car['car_id'] = array('in',$car);
            $car_lists = D('House_village_parking_car')->get_car_lists($where_car,'car_id,car_number,province');
            $this->assign('car_lists',$car_lists);
        }


        $list = $database_house_village_property->house_village_proerty_page_list($where , true , 'property_month_num desc' , 99999);

        if(!$list){
            $this->error('数据处理有误！');
        }else{
            if($list['status']){
                $this->assign('list' , $list['list']);
            }else{
                $this->error('请先添加缴费优惠。',U('Unit/preferential_add'));
            }
        }
        $this->display();
    }

    /**
     * 收银台退款
     * @author lijie
     * @date_time 2020/08/14
     */
    public function drawback()
    {
        $cashier_id = $_POST['cashier_id'];
        $village_id = $_SESSION['house']['village_id'];
        $where['village_id'] = $village_id;
        $village_money = D('House_village')->where($where)->getField('money');
        $cashier_money = D('House_village_pay_cashier_order')->where(['cashier_id'=>$cashier_id])->getField('money');
        $pay_type = D('House_village_pay_cashier_order')->where(['cashier_id'=>$cashier_id])->getField('pay_type');
        /*if($pay_type == 0){
            if($village_money < $cashier_money)
                exit(json_encode(array('status'=>0,'info'=>'余额不足')));
        }*/
        $order_list = D('House_village_pay_order')->where(['cashier_id'=>$cashier_id])->select();

        foreach ($order_list as $k=>$v){
            if($v['order_type'] == 'property' || $v['order_type'] == 'custom_payment' || $v['order_type'] == 'park'){
                $oId = D('House_village_pay_order')->where(['bind_id'=>$v['bind_id'],'order_status'=>1,'village_id'=>$village_id,'order_type'=>$v['order_type']])->select();
                foreach ($oId as $key=>$value){
                    if($value['pay_time'] > $v['pay_time'])
                        exit(json_encode(array('status'=>0,'info'=>'周期性的服务费用需按收款顺序进行退款，后收款的先退款')));
                }
            }
        }
        //将收银台订单改为已退款
        $res = D('House_village_pay_cashier_order')->where(['cashier_id'=>$cashier_id])->save(['order_status'=>2]);
        if($res){
            $db_house_village_property_paylist = D('House_village_property_paylist');
            $park_system = D('Park_system')->where(['park_id' => $village_id])->find();
            foreach ($order_list as $k=>$v){
                /*if($v['pay_type'] == 0){
                    D('House_village')->where($where)->setDec('money',$cashier_money);
                    D('Village_money_list')->data(['village_id'=>$_SESSION['house']['village_id'],'order_id'=>$v['order_id'],'type'=>'village_pay','money'=>$v['money'],'system_take'=>0,'total_money'=>$v['money'],'income'=>2,'use_time'=>time(),'desc'=>'收银台缴费退款','now_village_money'=>$village_money-$cashier_money])->add();
                }*/
                D('House_village_pay_order')->where(['order_id'=>$v['order_id']])->data(['order_status'=>2])->save();
                switch($v['order_type']){
                    case 'property':
                        $bind_field = 'property_price';
                        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($v['bind_id']);
                        $paylist_data['end_time'] = strtotime("-" .($v['property_month_num'] + $v['presented_property_month_num']). " months", $now_user_info['property_endtime']);
                        $where['village_id'] = $v['village_id'];
                        $where['pigcms_id'] = $v['bind_id'];
                        M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));
                        $db_house_village_property_paylist->where(['order_id'=>$v['order_id']])->delete();
                        $property['property_price'] = array('neq', 0);
                        $property['order_id'] = array('eq', 0);
                        //M('House_village_user_paylist')->where(array('village_id'=>$v['village_id'],'uid'=>$v['uid'],'bind_id'=>$v['bind_id']))->where($property)->save(array('order_id'=>$v['order_id']));
                        break;
                    case 'water':
                        $bind_field = 'water_price';
                        $water['water_price'] = array('neq', 0);
                        $water['order_id'] = array('eq', 0);
                        //M('House_village_user_paylist')->where(array('village_id'=>$v['village_id'],'uid'=>$v['uid'],'bind_id'=>$v['bind_id']))->where($water)->save(array('order_id'=>$v['order_id']));
                        break;
                    case 'electric':
                        $bind_field = 'electric_price';
                        $electric['electric_price'] = array('neq', 0);
                        $electric['order_id'] = array('eq', 0);
                        //M('House_village_user_paylist')->where(array('village_id'=>$v['village_id'],'uid'=>$v['uid'],'bind_id'=>$v['bind_id']))->where($electric)->save(array('order_id'=>$v['order_id']));
                        break;
                    case 'gas':
                        $bind_field = 'gas_price';
                        $gas['gas_price'] = array('neq', 0);
                        $gas['order_id'] = array('eq', 0);
                        //M('House_village_user_paylist')->where(array('village_id'=>$v['village_id'],'uid'=>$v['uid'],'bind_id'=>$v['bind_id']))->where($gas)->save(array('order_id'=>$v['order_id']));
                        break;
                    case 'park':
                        $bind_field = 'park_price';
                        $park['park_price'] = array('neq', 0);
                        $park['order_id'] = array('eq', 0);
                        $car_info = D('House_village_parking_car')->where(['car_id'=>$v['car_id']])->find();
                        if ($car_info['end_time'] && ($v['property_month_num'] || $v['presented_property_month_num'])) {
                            $end_time = strtotime("-" .($v['property_month_num'] + $v['presented_property_month_num']). " months", $car_info['end_time']);
                            D('House_village_parking_car')->where(['car_position_id'=>$car_info['car_position_id']])->save(['end_time'=>$end_time]);
                            D('House_village_parking_position')->where(['position_id'=>$car_info['car_position_id']])->save(['end_time'=>$end_time]);
                        }
                        //M('House_village_user_paylist')->where(array('village_id'=>$v['village_id'],'uid'=>$v['uid'],'bind_id'=>$v['bind_id']))->where($park)->save(array('order_id'=>$v['order_id']));
                        // 如果是储值 余额退回
                        if($park_system && $park_system['park_id'] && 'stored_type'==$v['car_type']) {
                            $this->sysParkDeviceOrder($v,$car_info);
                        }
                        break;
                    case 'custom_payment':
                        $bind_field = '';
                        $tmp_order['desc']= $v['order_name'];
                        // 计算缴费时间
                        //M('House_village_payment_standard_unpaid')->where(array('village_id'=>$v['village_id'],'bind_id'=>$v['bind_id'],'payment_bind_id'=>$v['payment_bind_id'],'uid'=>$v['uid']))->save(array('order_id'=>$v['order_id']));
                        D('House_village_payment_paylist')->data(['order_id'=>$v['order_id']])->delete();
                        //修改绑定缴费项目已缴周期
                        M('House_village_payment_standard_bind')->where(array('bind_id'=>$v['payment_bind_id']))->setDec('paid_cycle',$v['payment_paid_cycle']);
                    default:
                        $bind_field = '';
                }
                /*if(!empty($bind_field)){
                    $now_user_info = D('House_village_user_bind')->get_one($v['village_id'],$v['bind_id'],'pigcms_id');
                    $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
                    $data_bind[$bind_field] = $now_user_info[$bind_field] + $v['money'];
                    D('House_village_user_bind')->data($data_bind)->save();
                }*/
            }
            exit(json_encode(array('status'=>1,'info'=>'退款成功')));
        }else{
            exit(json_encode(array('status'=>0,'info'=>'系统异常')));
        }
    }


    public function refundPark()
    {
        $cashier_id = $_POST['cashier_id'];
        $village_id = $_SESSION['house']['village_id'];
        $where = [];
        $where['village_id'] = $village_id;
        $where['cashier_id'] = $cashier_id;
        $where['order_status'] = 2;
        $where['order_type'] = 'park';
        $where['car_type'] = 'stored_type';
        $order_list = D('House_village_pay_order')->where($where)->select();
        $park_system = D('Park_system')->where(['park_id' => $village_id])->find();
        if(!$park_system || !$park_system['park_id']) {
            exit(json_encode(array('status'=>1,'info'=>'请先配置好智慧停车')));
        }
        foreach ($order_list as $k=>$v){
            switch($v['order_type']){
                case 'park':
                    $park['park_price'] = array('neq', 0);
                    $park['order_id'] = array('eq', 0);
                    $car_info = D('House_village_parking_car')->where(['car_id'=>$v['car_id']])->find();
                    // 如果是储值 余额退回
                    if('stored_type'==$v['car_type']) {
                        $this->sysParkDeviceOrder($v,$car_info);
                    }
                    break;
                default:
                    continue;
            }
        }
        exit(json_encode(array('status'=>1,'info'=>'同步命令下发')));
    }

    // 余额退回
    private function sysParkDeviceOrder($item,$car_info) {
        if ($item['village_id']!=12) {
            return true;
        }
        $orderParam = [
            'park_id' => $item['village_id'],
            'car_number' => isset($v['car_number'])&&trim($item['car_number'])?trim($item['car_number']):trim($car_info['province'].$car_info['car_number']),
            'card_id' => $car_info['stored_card'],
            'pay_time' => $item['pay_time'],
            'pay_type' => $item['pay_type']==0?'线上支付':'现金',
            'total_price' => -$item['money'],
            'mobile' => $car_info['car_user_phone'],
            'name' => $car_info['car_user_name'],
            'account_type' => 0,
            'actual_money' => -$item['money'],
            'order_id' => $item['order_id'],
            'car_id' => $car_info['car_id'],
        ];
        $carSync = D('House_village_park')->kumikeParkPrepayCardTradeSync($orderParam);
        if ($carSync) {
            $saveOrder = [
                'carSynStatus' => 1,
                'carSynTimes' => time(),
                'carSynRemarks' => '',
            ];
            D('House_village_pay_order')->where(['order_id'=>$item['order_id']])->save($saveOrder);
        }
        return true;
    }

    /**
     * 提交发票申请
     * @author lijie
     * @date_time 2020/07/21
     * @param $name
     * @param $phone
     * @param $order_no
     * @param $duty_paragraph
     * @param $detail
     * @param $village_name
     * @param $property_id
     * @return mixed
     */
    public function addInvoice($name,$phone,$order_no,$duty_paragraph,$detail,$village_name,$spec,$property_id=0)
    {
        if(!$property_id)
            return false;
        $e_config = M('House_property')->where(['id'=>$property_id])->field('identity,house_e_invoice_url,house_e_pdf_url')->find();
        $arr['identity'] = $e_config['identity'];
        $order['buyername'] = $name;
        //$order['taxnum'] = '339901999999142';
        $order['phone'] = $phone;
        $order['order_no'] = $order_no;
        $order['invoicedate'] = date('Y-m-d H:i:s',time());
        $order['clerk'] = 'name';
        $order['saletaxnum'] = $duty_paragraph;
        $order['kptype'] = 1;
        $order['message'] = $village_name;
        foreach ($detail as $k=>$v){
            $order['detail'][$k]['goodsname'] = $v['goodsname'];
            $order['detail'][$k]['hsbz'] = 1;
            $order['detail'][$k]['taxrate'] = $v['rate'];
            $order['detail'][$k]['spbm'] = 304080101;
            $order['detail'][$k]['fphxz'] = 0;
            /*$order['detail'][$k]['num'] = 1;
            $order['detail'][$k]['price'] = $v['price'];*/
            $order['detail'][$k]['taxamt'] = round($v['price'],2);
            $order['detail'][$k]['tax'] = round($v['price']*$v['rate']/(1+$v['rate']),2);
            $order['detail'][$k]['taxfreeamt'] = round($order['detail'][$k]['taxamt'] - $order['detail'][$k]['tax'],2);
            if($spec){
                $order['detail'][$k]['spec']  = $spec;
            }
        }
        $arr['order'] = $order;
        $res = $this->sendInvoice($arr,$e_config['house_e_invoice_url']);
        return $res;
    }

    /**
     * 调开具电子发票接口
     * @author lijie
     * @date_time 2020/07/08
     * @param $order
     * @param $url
     * @return mixed
     */
    public function sendInvoice($order,$url)
    {
        $order = $this->encrypt($order);
        $response = httpRequest($url,'POST',['order'=>$order]);
        return json_decode($response[1],true);
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        $srcKey = base64_decode('LmMGStGtOpF4xNyvYt54EQ==');
        $content = json_encode($data,JSON_UNESCAPED_UNICODE);
        $srcBuf = md5($content,true) . $content;
        // $result = base64_encode(
        //     openssl_encrypt($srcBuf,'DES-CBC',$this->key,0,$this->iv)
        // );
        $result = openssl_encrypt($srcBuf,'DES-CBC',substr($srcKey, 0, 8),0, substr($srcKey,8));
        return $result;
    }

    /**
     * 添加开发票记录
     * @author lijie
     * @date_time 2020/07/21
     * @param $duty_paragraph
     * @param $name
     * @param $phone
     * @param $fpqqlsh
     * @param $order_no
     * @param $detail
     * @param $pigcms_id
     */
    public function addERecord($duty_paragraph,$name,$phone,$fpqqlsh,$order_no,$detail,$pigcms_id)
    {
        $money = 0;
        $order_name = '*';
        foreach ($detail as $k=>$v){
            $money += $v['price'];
            $order_name .= $v['goodsname'].'*';
        }
        $post['duty_paragraph'] = $duty_paragraph;
        $post['create_time'] = time();
        $post['head_up_type'] = 1;
        $post['money'] = $money;
        $post['pigcms_id'] = $pigcms_id;
        $post['receive_tel'] = $phone;
        $post['name'] = $name;
        $post['fpqqlsh'] = $fpqqlsh;
        $post['order_no'] = $order_no;
        $post['property_type'] = rtrim($order_name,'*');
        $id = D('House_village_e_record')->insertData($post);
        foreach ($detail as $k=>$v){
            $insertAll['order_id'] = $v['order_id'];
            $insertAll['property_type'] = $v['goodsname'];
            $insertAll['price'] = $v['price'];
            $insertAll['e_record_id'] = $id;
            $insertAll['create_time'] = time();
            D('House_village_detail_record')->insertData($insertAll);
        }
    }
    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $property_price = 0;
        foreach ($dateMonths as $value){
            foreach ($chargingStandard as $v){
                if($value >= strtotime($v['date_month'])){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }
}
?>

