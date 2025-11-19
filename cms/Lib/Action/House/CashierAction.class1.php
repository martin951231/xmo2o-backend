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
            $total = 0;
            if($paid_list){
                foreach ($paid_list['order_list'] as &$v){
                    // 应缴金额
                    $total_money = D('House_village_pay_order')->field(' SUM(`money` ) AS totalMoney ')->where(array('village_id'=>$village_id,'cashier_id'=>$v['cashier_id']))->find();
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
                    
                }
            }
            $paid_list['total'] = $total;
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
            $user_info = D('House_village_user_bind')->get_cashier_unpaid_list(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id),1,1);
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
            $bind_where['usernum'] = $_POST['usernum'];
            $database_house_village_user_bind = D('House_village_user_bind');
            $now_bind_info         = $database_house_village_user_bind->where($bind_where)->find();
            $now_village = D('House_village')->get_one($now_bind_info['village_id']);

            if (!$now_bind_info) {
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

                if (($now_floor_info['property_fee'] != '0.00') && isset($now_floor_info['property_fee'])) {
                    $data['money']        = $now_floor_info['property_fee'] * $now_bind_info['housesize'] * $now_property_info['property_month_num'];
                    $data['property_fee'] = $now_floor_info['property_fee'];
                } else {
                    $data['money']        = $now_village['property_price'] * $now_bind_info['housesize'] * $now_property_info['property_month_num'];
                    $data['property_fee'] = $now_village['property_price'];
                }
                $data['remarks'] = $_POST['remarks'];
                $order_id = D("House_village_pay_order")->add($data);
                if ($order_id) {
                    $this->success('添加成功');
                } else {
                    $this->error('订单创建失败，请重试');
                }
            }else{

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

    public function ajax_user_list(){
        if(IS_JAX){
            $find_type = $_POST['find_type'];
            $find_value = $_POST['find_value'];

            if ($find_value) {
                if ($find_type == 1) {
                    $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                } else if ($find_type == 2) {
                    $where['phone'] = array('like', '%' . $find_value . '%');
                } else if ($find_type == 3) {
                    $where['usernum'] = array('like', '%' . $find_value . '%');
                }
            }
            if($_POST['usernum']){
                $where['usernum'] = $_POST['usernum'];
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
                ->where("psb.pigcms_id= '".$value['pigcms_id']."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
                $payment_list = $payment_list ? $payment_list : array();

                // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$value['pigcms_id']));
                $payment_list = array_merge($payment_list, $position_payment_list);

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
                }
                
                $user_list[$key]['payment_list'] = $payment_list;

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
        if(IS_JAX){
            $order_id = intval($_POST['order_id']);
            $database_house_village_pay_order = D('House_village_pay_order');
            $pay_order = $database_house_village_pay_order->get_one($order_id);
            if (!$pay_order) exit(json_encode(array('status'=>1,'msg'=>'该订单信息不存在')));
            
            if ($pay_orde['paid'] > 0 ) {
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
            $where['order_type'] = "'custom','custom_payment','property'";
            $where['paid'] = 0; //未付款
            $where['cashier_id'] = 0;
            $pay_list_order = D('House_village_pay_order')->get_limit_list_page($where,20,false,'`o`.`order_id` ASC');

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
            // // 自定义缴费
            // if($now_village['has_custom_pay']){
            //     $pay_list[] = array(
            //             'type' => 'custom',
            //             'name' => $this->pay_list_type['custom'],
            //             'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'custom')),
            //             'money'=> -1,
            //     );
            // }

            // $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
            //         C('DB_PREFIX').'house_village_payment_standard'=>'ps',
            //         C('DB_PREFIX').'house_village_payment'=>'p'))
            //         ->where("psb.pigcms_id= '".$this->village_bind['pigcms_id']."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();

            // $this->assign('payment_list',$payment_list);
            $this->assign('pay_list',$pay_list);
            $this->assign('totalmoney',$totalmoney);


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
            $total = 0;
            if($paid_list){
                foreach ($paid_list['order_list'] as &$v){
                    // 应缴金额
                    $total_money = D('House_village_pay_order')->field(' SUM(`money` ) AS totalMoney ')->where(array('village_id'=>$village_id,'cashier_id'=>$v['cashier_id']))->find();
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
        $print_template = $database_house_village_print_template->where(array('village_id'=>$village_id))->select();
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
            switch ($aOrder[0]) {
                case 'custom_payment':  // 自定义缴费标准
                    // 已有订单
                    if(!$aOrder[1]){
                        exit(json_encode(array('status'=>1,'msg'=>'传递参数有误！')));
                    }
                    $order_info = $database_house_village_pay_order->get_one($aOrder[1]);
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

                    break;
                case 'property': // 物业费
                case 'custom': // 自定义缴费
                    // 已有订单
                    if(!$aOrder[1]){
                        exit(json_encode(array('status'=>1,'msg'=>'传递参数有误！')));
                    }
                    $order_info = $database_house_village_pay_order->get_one($aOrder[1]);
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
                    if(empty($now_village['park_price'])) 
                        exit(json_encode(array('status'=>1,'msg'=>'当前'.C('config.house_name').'不支持缴停车费')));
                    
                    $totalmoney += $now_bind_info['park_price'];
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

        }


        $totalmoney = $database_house_village_pay_order->field(' SUM(`money` ) AS totalMoney ')->where(array('cashier_id' => $cashier_id))->find();
        $totalmoney = $totalmoney['totalMoney'];
        $this->assign('order_list',$order_list);
        $this->assign('now_bind_info',$now_bind_info);
        $this->assign('cashier_order',$cashier_order);
        $this->assign('totalmoney',$totalmoney);
        $this->display();
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
        $print_template = $database_house_village_print_template->get_one($template_id);
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
            $print_template = $database_house_village_print_template->get_one($template_id);
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

        //模板信息
        $print_template = $database_house_village_print_template->get_one($template_id);
        $lastIndex = 1;
        if ($print_template['custom']) {
            foreach ($print_template['custom'] as $value) {
                $lastIndex = $value['id'];
            }
        }

        //获得自定义项
        $print_custom = D('House_village_print_custom_configure')->where(array('is_hidden'=>0))->order('sort desc,configure_id asc')->select();
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
        if (is_array($ids)&&$ids) {
            //删除之前的
            $database_house_village_print_custom->where(array('template_id'=>$template_id,'village_id'=>$this->village_id))->delete();
            foreach ($ids as $key => $value) {
                $data = array(
                    'template_id' => $template_id,
                    'village_id' => $this->village_id,
                    'configure_id' => intval($value),
                    'time' => $_SERVER['REQUEST_TIME'],
                );
                $insert = $database_house_village_print_custom->data($data)->add();
            }
        }
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
        $print_template = $database_house_village_print_template->get_one($template_id);
        // cashier_info
        $cashier_info['desc'] = $print_template['desc'];
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
            if (!in_array(73, $this->house_session['menus'])) {
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
        if (!in_array(74, $this->house_session['menus'])) {
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
                $data['metering_mode'] = '房间面积'; 
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
                $data['metering_mode'] = '房间面积'; 
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
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif','application/octet-stream');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $uploadList[0]['savename'];
                $url = $upload_dir.$title;
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }

    public function ajax_user_info(){
        if(IS_JAX){
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
            $user_list = D('House_village_user_vacancy')->house_village_user_vacancy_lists_new($where, true, $order,5);
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

    $join=' left join pigcms_house_village_user_vacancy as b on m.layer_num=b.pigcms_id left join pigcms_house_village_floor as f on f.floor_id = m.floor_id left join pigcms_house_village_layer as l on m.layer_id = l.id';
    $field='m.*,b.room,b.usernum,f.floor_name,f.floor_layer,l.layer_name';
    $dataList = M('House_village_meter_reading')->alias('m')->field($field)->join($join)->where($where)->where(array('m.village_id'=>$this->village_id))->order('m.id desc')->select();
    fdump(M('House_village_meter_reading')->getLastSql());

    import('@.ORG.merchant_page');
    $dataLists = M('House_village_meter_reading')->alias('m')->field($field)->join($join)->where($where)->where(array('m.village_id'=>$this->village_id))->count();
    $p = new Page($dataLists, 20, 'page');
    $pagebar = $p->show();
    $this->assign('pagebar',$pagebar);
    $this->assign('dataList',$dataList);
    $this->display();
}

    public function meter_reading_add()
{
    $database_house_village_user_vacancy = D('House_village_user_vacancy');
    $database_house_village_floor = D('House_village_floor');
    $vacancy_where['status'] = 1;
    $vacancy_where['village_id'] = $this->village_id;

    //先判断单元
    $floor_num = $database_house_village_floor->where(array('village_id'=>$this->village_id,'status'=>1))->count();
    if($floor_num <= 0){
        $this->error('请先添加单元',U('Unit/index'));
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
            $this->error('楼栋不能为空！');
        }

        if(empty($_POST['floor_id'])){
            $this->error('单元不存在！');
        }

        if(empty($_POST['vacancy_id'])){
            $this->error('房间不能为空！');
        }

        $database_house_village_floor = D('House_village_floor');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $where['floor_id'] = $_POST['floor_id'] + 0;
        $where['status'] = 1;
        $where['village_id'] = $this->village_id;

        $house_village_floor_info = $database_house_village_floor->where($where)->find();
        if (!isset($house_village_floor_info)) {
            $this->error('单元不存在，请查看小区中心，单元管理-单元列表！');
        }
//            $vacancy_where['status'] = 1;
//            $vacancy_where['pigcms_id'] = $_POST['layer_num'] + 0;
//            $vacancy_info = $database_house_village_user_vacancy->where($vacancy_where)->find();
//
//            if (!isset($vacancy_info)) {
//                $this->error('该房间信息不存在！');
//            }
        $data['village_id'] = $this->village_id;
        $data['floor_id'] = $_POST['floor_id'];
        $data['layer_id'] = $_POST['layer_id'];
        $data['layer_num'] = $_POST['vacancy_id'];
        $data['start_time'] = $start_time;
        $data['start_name'] = $_POST['start_time'];
        $data['last_time'] = $last_time;
        $data['last_name'] = $_POST['last_time'];
        $data['charge_name'] = $_POST['charge_name'];
        $data['unit_price'] = $_POST['unit_price'];
        $data['start_ammeter'] = $_POST['start_ammeter'];
        $data['last_ammeter'] = $_POST['last_ammeter'];
        $data['rate'] = $_POST['rate'];
        $data['pool_ammeter'] = $_POST['pool_ammeter'];
        $data['note'] = $_POST['note'];
        $data['cost_num'] = ($data['last_ammeter'] - $data['start_ammeter'] +$data['pool_ammeter'])*$data['rate'];
        $data['cost_money'] = $data['unit_price'] * $data['cost_num'];

        $insert_id = M('House_village_meter_reading')->data($data)->add();
        if($insert_id){
            if($_POST['charge_name'] == '电费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['layer_num']))->data(array('electric_price'=>$data['cost_money']))->save();
            }elseif($_POST['charge_name'] == '煤气费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['layer_num']))->data(array('gas_price'=>$data['cost_money']))->save();
            }elseif($_POST['charge_name'] == '水费'){
                M('House_village_user_bind')->where(array('floor_id'=>$_POST['floor_id'],'vacancy_id'=>$_POST['layer_num']))->data(array('water_price'=>$data['cost_money']))->save();
            }



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
                $this->error('单元不存在，请先添加！');
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
                    foreach ($result as $kk => $vv) {
                        if (array_sum($vv) == 0) {
                            continue;
                        }
                        if ($vv['B'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null) continue;
                        if (empty($vv['B'])) {
                            $vv['L'] = '请填写单元id编号！';
                            $error_arr[] = $vv;

                            continue;
                        }

                        if (empty($vv['D'])) {
                            $vv['L'] = '请填写房间号！';
                            $error_arr[] = $vv;

                            continue;
                        }
                        //搜房间号id
                        if ($vv['B']) {
                            $user_vacancy['floor_id'] = $vv['B'];
                        }
//                        if ($vv['C']) {
//                            $user_vacancy['layer'] = $vv['C'];
//                        }
                        if ($vv['D']) {
                            $user_vacancy['room'] = $vv['D'];
                        }
                        $user_vacancy['village_id'] = $this->village_id;
                        $house_village_user_vacancy = D('House_village_user_vacancy')->where($user_vacancy)->find();
                        if (empty($house_village_user_vacancy['pigcms_id'])) {
                            $vv['L'] = '房间不存在。';
                            $error_arr[] = $vv;
                            $err_msg .= '房间不存在。' . PHP_EOL;
                            continue;
                        }
                        if (trim($vv['G']) == '') {
                            if ($house_village_user_vacancy['pigcms_id']) {
                                $res = D('House_village_meter_reading')->where(array('layer_num' => $house_village_user_vacancy['pigcms_id'], 'floor_id' => $vv['B']))->order('id DESC')->find();
//                                    fdump('123456','guojie19931113',1);
//                                    fdump(D('House_village_meter_reading')->_sql(),'guojie19931113',1);
                                if ($res['last_ammeter']) {
                                    $ammeter = $res['last_ammeter'];
                                } else {
                                    $ammeter = 0;
                                }
                            }
                        } else {
                            $ammeter = $vv['G'];
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
                        $tmpdata['floor_id'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                        $tmpdata['layer_num'] = $house_village_user_vacancy['pigcms_id'];
                        $tmpdata['start_time'] = htmlspecialchars(trim($start_time), ENT_QUOTES);
                        $tmpdata['start_name'] = $start_name;
                        $tmpdata['last_time'] = htmlspecialchars(trim($last_time), ENT_QUOTES);
                        $tmpdata['last_name'] = $last_name;
                        $tmpdata['charge_name'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);

                        $tmpdata['unit_price'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);//收费单价
                        $tmpdata['start_ammeter'] = $ammeter;
                        $tmpdata['last_ammeter'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);

                        $tmpdata['rate'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                        $tmpdata['pool_ammeter'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                        $tmpdata['note'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);

                        $tmpdata['cost_num'] = ($vv['H'] - $vv['G'] + $vv['J']) * $vv['I'];
                        $tmpdata['cost_money'] = $vv['F'] * $tmpdata['cost_num'];

                        $last_user_id = D('House_village_meter_reading')->data($tmpdata)->add();

                        if ($last_user_id) {
                            //生成未缴费账单
                            if (trim($vv['E']) == '电费') {
                                M('House_village_user_bind')->where(array('floor_id' => $tmpdata['floor_id'], 'vacancy_id' => $tmpdata['layer_num']))->data(array('electric_price' => $tmpdata['cost_money']))->save();
                                fdump('电费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            } elseif (trim($vv['E']) == '煤气费') {
                                M('House_village_user_bind')->where(array('floor_id' => $tmpdata['floor_id'], 'vacancy_id' => $tmpdata['layer_num']))->data(array('gas_price' => $tmpdata['cost_money']))->save();
                                fdump('煤气费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            } elseif (trim($vv['E']) == '水费') {
                                M('House_village_user_bind')->where(array('floor_id' => $tmpdata['floor_id'], 'vacancy_id' => $tmpdata['layer_num']))->data(array('water_price' => $tmpdata['cost_money']))->save();
                                fdump('水费', 'guojie19931113', 1);
                                fdump(M('House_village_user_bind')->_sql(), 'guojie19931113', 1);
                            }
                        }

                        if (!$last_user_id) {
                            $err_msg = '抄表单导入失败！';
                            $error_arr[] = $vv;
                            $vv['L'] = $err_msg;
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

                        $title = $this->village['village_name'] . '小区-抄表导入失败列表';
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
                        $objActSheet->setCellValue('B1', '单元ID名称');
                        $objActSheet->setCellValue('C1', '楼号');
                        $objActSheet->setCellValue('D1', '房间号');
                        $objActSheet->setCellValue('E1', '收费项目');
                        $objActSheet->setCellValue('F1', '单价');
                        $objActSheet->setCellValue('G1', '起度');
                        $objActSheet->setCellValue('H1', '止度');
                        $objActSheet->setCellValue('I1', '倍率');
                        $objActSheet->setCellValue('J1', '公摊');
                        $objActSheet->setCellValue('K1', '备注');
                        $objActSheet->setCellValue('L1', '失败原因');
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
                $where['usernum'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 2) {
                $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 4) {
                $where['address'] = array('like', '%' . $find_value . '%');
            }
        }

        if($is_platform == 1){
            $where['uid'] = array('neq',0);
        }elseif ($is_platform == 2) {
            $where['uid'] = array('eq',0);
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


        $village_id = $this->village_id;

        $join=' left join pigcms_house_village_user_vacancy as b on m.layer_num=b.pigcms_id left join pigcms_house_village_floor as f on f.floor_id = m.floor_id';
        $field='m.*,b.room,b.usernum,f.floor_name,f.floor_layer';
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
            $objActSheet->setCellValue('B1', '单元名称');
            $objActSheet->setCellValue('C1', '楼号');
            $objActSheet->setCellValue('D1', '房间号');
            $objActSheet->setCellValue('E1', '收费项目');
            $objActSheet->setCellValue('F1', '收费金额');
            $objActSheet->setCellValue('G1', '起度');
            $objActSheet->setCellValue('H1', '止度');
            $objActSheet->setCellValue('I1', '倍率');
            $objActSheet->setCellValue('J1', '公摊');
            $objActSheet->setCellValue('K1', '备注');

            if (!empty($user_list)) {
                $index = 2;

                $cell_list = range('A','K');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(40);
                }

                foreach ($user_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['start_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['floor_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['floor_layer']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['usernum']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['charge_name']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['unit_price']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['start_ammeter']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['last_ammeter']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['rate']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['pool_ammeter']);
                    $objActSheet->setCellValueExplicit('K' . $index, $value['note']);
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
        $user_list[] = array('12345235','小黑','13212345679','11120.1','1120.1','1120.1','1','1120.1','30栋','三单元','12','1102','家中有狗','2017-07-24','2018-07-24','100','职工');

        $test = array('R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG');
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



            $objActSheet->setCellValue('A1', '业主编号');
            $objActSheet->setCellValue('B1', '业主名');
            $objActSheet->setCellValue('C1', '手机号');
            $objActSheet->setCellValue('D1', '水费总欠费');
            $objActSheet->setCellValue('E1', '电费总欠费');
            $objActSheet->setCellValue('F1', '燃气费总欠费');
            $objActSheet->setCellValue('G1', '是否有停车位（1有0无）');
            $objActSheet->setCellValue('H1', '停车费总欠费');
            $objActSheet->setCellValue('I1', '楼栋号');
            $objActSheet->setCellValue('J1', '单元名称');
            $objActSheet->setCellValue('K1', '层号');
            $objActSheet->setCellValue('L1', '门牌号');
            $objActSheet->setCellValue('M1', '备注');
            $objActSheet->setCellValue('N1', '物业开始时间');
            $objActSheet->setCellValue('O1', '物业结束时间');
            $objActSheet->setCellValue('P1', '房子平方(计算物业费使用)');
            $objActSheet->setCellValue('Q1', '属性');
            $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
            if($village_data_config){
                foreach($village_data_config as $kk=>$vv){
                    if($vv['title']){
                        if($vv['is_must'] == 1){
                            if($vv['type'] ==2){
                                $objActSheet->setCellValue($test[$kk].'1', $vv['title'].'(必填项)'.'请选择'.' '.$vv['use_field']);
                            }else{
                                $objActSheet->setCellValue($test[$kk].'1', $vv['title'].'(必填项)');
                            }
                        }else{
                            if($vv['type'] ==2){
                                $objActSheet->setCellValue($test[$kk].'1', $vv['title'].'请选择'.' '.$vv['use_field']);
                            }else{
                                $objActSheet->setCellValue($test[$kk].'1', $vv['title']);
                            }

                        }

                    }
                }
            }

            if (!empty($user_list)) {
                $index = 2;
                if($village_data_config){

                    foreach ($village_data_config as $kkk=>$vvv) {
                        $objActSheet->getColumnDimension($test[$kkk])->setWidth(30);
                    }
                }else{
                    $cell_list = range('A','Q');
                    foreach ($cell_list as $cell) {
                        $objActSheet->getColumnDimension($cell)->setWidth(30);
                    }
                }




                foreach ($user_list as $value) {

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
                    $objActSheet->setCellValueExplicit('P' . $index, number_format($value[15],2));
                    $objActSheet->setCellValueExplicit('Q' . $index, $value[16]);

//                    //增加自定义字段单选问题
//                    $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
//                    if($village_data_config){
//                        foreach($village_data_config as $kk=>$vv){
//                            if($vv['title']){
//                                if($vv['type'] ==2){
//
//                                    $objExcel->setActiveSheetIndex(0);
//                                    $objActSheet = $objExcel->getActiveSheet();
//                                    $objValidation = $objActSheet->getCell($test[$kk].'2')->getDataValidation(); //这一句为要设置数据有效性的单元格
//
//                                    $objValidation -> setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
//                                        -> setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
//                                        -> setAllowBlank(false)
//                                        -> setShowInputMessage(true)
//                                        -> setShowErrorMessage(true)
//                                        -> setShowDropDown(true)
//                                        -> setErrorTitle('输入的值有误')
//                                        -> setError('您输入的值不在下拉框列表内.')
//                                        -> setPromptTitle($vv['title'])
//                                        -> setFormula1('"'.$vv['use_field'].'"');
//                                }
//
//                            }
//                        }
//                    }



                    //增加自定义字段单选问题
                    $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
                    if($village_data_config){
                        foreach($village_data_config as $kk=>$vv){
                            if($vv['title']){
                                if($vv['type'] ==2){
                                    fdump($vv['use_field'],'11====1111',1);
                                    $objExcel->setActiveSheetIndex(0);
                                    $objActSheet = $objExcel->getActiveSheet();
                                    $objValidation = $objActSheet->getCell($test[$kk].'2')->getDataValidation(); //这一句为要设置数据有效性的单元格

                                    $objValidation -> setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                                        -> setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                                        -> setAllowBlank(false)
                                        -> setShowInputMessage(true)
                                        -> setShowErrorMessage(true)
                                        -> setShowDropDown(true)
                                        -> setErrorTitle('输入的值有误')
                                        -> setError('您输入的值不在下拉框列表内.')
                                        -> setPromptTitle($vv['title'])
                                        -> setFormula1('"'.$vv['use_field'].'"');
                                }


                            }
                        }
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
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null && $vv['L'] === null && $vv['M'] === null && $vv['N'] === null) continue;

                            //增加导入资料字段控制=>
                            $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写正确的手机号！';
                                    $error_arr[] = $vv;

                                }else {
                                    $vv['R'] = '请填写正确的手机号！';
                                    $error_arr[] = $vv;
                                }
                            }

                            if (empty($vv['I'])) {
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '请填写单元名称！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '请填写单元名称！';
                                    $error_arr[] = $vv;
                                    // $err_msg = '请填写单元名称！';
                                    continue;
                                }
                            }

                            if (empty($vv['J'])) {
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
                                if($village_data_config_count > 0){
                                    $vv[$test[$village_data_config_count]] = '单元不存在，请查看小区中心，单元管理-单元列表！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else {
                                    $vv['R'] = '单元不存在，请查看小区中心，单元管理-单元列表!';
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
                                $err_msg = htmlspecialchars(trim($vv['I']), ENT_QUOTES).htmlspecialchars(trim($vv['J']), ENT_QUOTES). htmlspecialchars(trim($vv['K']), ENT_QUOTES).htmlspecialchars(trim($vv['L']), ENT_QUOTES)."房间不存在";
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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

                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();
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
                            $objActSheet->setCellValue('I1', '单元名称');
                            $objActSheet->setCellValue('J1', '楼号');
                            $objActSheet->setCellValue('K1', '层号');
                            $objActSheet->setCellValue('L1', '门牌号');
                            $objActSheet->setCellValue('M1', '备注');
                            $objActSheet->setCellValue('N1', '物业开始时间');
                            $objActSheet->setCellValue('O1', '物业结束时间');
                            $objActSheet->setCellValue('P1', '房子平方(计算物业费使用)');
                            $objActSheet->setCellValue('Q1', '属性');
                            $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
                            if($village_data_config){
                                foreach($village_data_config as $kk=>$vv){
                                    $objActSheet->setCellValue($test[$kk].'1', $vv['title']);
                                }
                                $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();

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

                                $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
                                if($village_data_config){
                                    foreach($village_data_config as $kk=>$vv){
                                        $objActSheet->setCellValueExplicit($test[$kk] . $index, $value[$test[$kk]]);
                                    }
                                    $village_data_config_count = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->count();

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
        $bind_user = M('House_village_user_bind')->field('usernum,name,phone')->where($where)->select();
        $index = 2;
        foreach ($bind_user as $value) {
            $objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
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
            $data['end_time'] = strtotime($_POST['start_time'].'+'.$_POST['cycle_sum']*$standard_info['pay_cycle'].$cycle_type[$standard_info['cycle_type']]);
            $data['cycle_sum'] = $_POST['cycle_sum'];
            $data['pay_cycle'] = 0;
            $data['remarks'] = $_POST['remarks'];

            $database_house_village_user_bind = D('House_village_user_bind');

            $where_bind = array(
                'status' => 1,
                'type' => array('in', array(0,3)),
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
}
?>

