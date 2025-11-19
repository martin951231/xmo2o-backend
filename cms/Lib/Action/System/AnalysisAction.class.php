<?php
/*
 * 数据分析
 *
 * @  Writers    jun
 * @  BuildTime  2015/12/30 8:00
 * 
 */
class AnalysisAction extends BaseAction {
    //获取用户总数统计
    public function echart(){
        $this->assign('star_year',2015);

        $this->display();
    }
    public function userc(){
        $star_year='2015';
        $this->assign('star_year',$star_year);
        $express_list = D('House_village')->get_village_list();
         //array_unshift($express_list,array('village_id'=>0,'village_name'=>'所有社区'));

        $this->assign('village_list',$express_list);
        $this->display();
    }
    public function getuserc(){
        $result = D('Analysis')->get_count_byAreaId($_POST['area_id'],$_POST['type']);
        $result['note']="用户统计是按地区统计的，用户必须要有完整的配送地址信息";
        $this->ajaxReturn($result);
    }
    //粉丝排行统计
    public  function fanc(){
        $result = D('Analysis')->get_fan_count($_POST['area_id'],$_POST['type']);
        $result['note']="粉丝统计是统计关注每个商家的粉丝数量";
        $this->ajaxReturn(array('msg'=>$result['msg'],'area_pname'=>$result['area_pname'],'note'=>$result['note'],'error'=>''));
    }
    //获取商家总数统计
    public function merc(){
        $result = D('Analysis')->get_merchant_count($_POST['area_id'],$_POST['type']);
        $area_pname = empty($result['area_pname'])?"全国商家数量统计":$result['area_pname']."商家数量统计";
        $result['note']="按地区统计商家数量";
        $this->ajaxReturn(array('msg'=>$result['msg'],'area_pname'=>$area_pname,'note'=>$result['note'],'error'=>''));
    }
    //统计团购消费统计
    public function groupc(){
        $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 3,$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname'].=C('config.group_alias_name')."消费统计";
        $result['note']=C('config.group_alias_name')."消费统计按地区时间统计已消费的数据";
        $this->ajaxReturn($result);
    }
    //餐饮消费统计
    public function mealc(){
        $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 0,$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname'].=C('config.meal_alias_name')."消费统计";
        $result['note']=C('config.meal_alias_name')."消费统计按地区时间统计已消费的数据";

        $this->ajaxReturn($result);
    }

    //快店消费统计
    public function shopc(){
        $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 5,$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname'].=C('config.shop_alias_name')."消费统计";
        $result['note']=C('config.shop_alias_name')."消费统计按地区时间统计已消费的数据";
        $this->ajaxReturn($result);
    }

    //店铺消费统计
     public function storec(){
       $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 1,$_POST['year'],$_POST['month'],$_POST['period']);
       $result['area_pname'].=C('config.store_alias_name')."消费统计";
         $result['note']=C('config.store_alias_name')."消费统计按地区时间统计已消费的数据";
        $this->ajaxReturn($result);
    }
    //外卖消费统计
    public function waimaic(){
        $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 2,$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname'].=C('config.waimai_alias_name')."消费统计";
        $result['note']=C('config.waimai_alias_name')."消费统计按地区时间统计已消费的数据";
        $this->ajaxReturn($result);
    }
    //预约消费统计
    public function appointc(){
        $result = D('Analysis')->get_consumer($_POST['area_id'],$_POST['type'], 4,$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname'].=C('config.appoint_alias_name')."消费统计";
        $result['note']=C('config.appoint_alias_name')."消费统计按地区时间统计已消费的数据";
        $this->ajaxReturn($result);
    }
    //小区消费
    public function villagec(){
        $result = D('Analysis')->get_village_consumer($_POST['area_id'],$_POST['type'],$_POST['year'],$_POST['month'],$_POST['period']);
        $area_pname2 = empty($result['area_pname'])?"全国":$result['area_pname'];
        $area_pname = empty($result['area_pname'])?"全国".C('config.house_name')."缴费统计":$result['area_pname'].C('config.house_name')."缴费统计";
        $result['note']="小区消费统计按地区时间统计小区缴费的数据";
        $this->ajaxReturn(array('msg'=>$result['msg'],'type_money'=>$result['type_money'],'area_pname'=>$area_pname,'area_pname2'=>$area_pname2,'error'=>''));
    }

    public function villagebasec(){
        $result = D('Analysis')->get_village_base($_POST['type'],$_POST['year'],$_POST['month'],$_POST['period']);
        $result['area_pname2'] = empty($result['area_pname'])?"所有".C('config.house_name'):$result['area_pname'];
        $result['area_pname'] = empty($result['area_pname'])?"所有".C('config.house_name'):$result['area_pname'];
        $result['note']=C('config.house_name')."基础信息统计";
        $result['error']="";
        $this->ajaxReturn($result);
    }
	
//    
    //框架内菜单选项
    public function getmenu(){
        $this->ajaxReturn($this->menu());
    }
    public function menu(){
        $menu = array(
            'getuserc'=>'用户统计',
            'merc'=>'商家统计',
            'fanc'=>'商家粉丝排行',
            'groupc'=>C('config.group_alias_name').'消费统计',
            'mealc'=>C('config.meal_alias_name').'消费统计',
            'shopc'=>C('config.shop_alias_name').'消费统计',
            'storec'=>C('config.store_alias_name').'消费统计',
            'appointc'=>C('config.appoint_alias_name').'消费统计',
        );
        if (empty($this->config['is_cashier'])) {
            unset($menu['storec']);
        }
        if($this->config['house_open']){
            $menu['villagec'] = C('config.house_name').'缴费统计';
            $menu['villagebasec'] = C('config.house_name').'基础信息统计';
        }
        if($this->config['store_open_waimai']){
            $menu['waimaic'] = '外卖消费统计';
        }
        return $menu;
    }

    //交易汇总
    public function trade(){
        if(empty($_GET['type'])){
            $_GET['type'] = 'shop';
        }
        $pay_method = D('Config')->get_pay_method(0,0,0,0,1);
        foreach($pay_method as $key=>$value) {
            $pay_method['weixin_and_balance'] = ['name'=>'微信支付+余额支付'];
            $pay_method['alipay_and_balance'] = ['name'=>'支付宝支付+余额支付'];
        }
        $this->assign('type_name',$this->get_alias_c_name());
        $this->assign('pay_method',$pay_method);
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $_GET['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }
        if(!isset($_GET['selectTimeType']) && !isset($_GET['begin_time'])  && !isset($_GET['end_time'])){
            $_GET['selectTimeType'] = 1;
        }
        $res = D('Order')->sell_order_date(1,$_GET);
        $this->assign($res);
        $this->display();
    }

    //菜品分析
    public function goods(){
        if(empty($_GET['type'])){
            $_GET['type'] = 'shop';
        }

        $type_name = array(
            'shop'=>'全部'.$this->config['shop_alias_name'],
            'meal'=>$this->config['meal_alias_name'],
            'offline_shop'=>'线下零售',//线下零售
            'only_shop'=>$this->config['shop_alias_name'],//去除线下零售
        );

        // 查看有没有老版的订餐管理
        $menu = M('System_menu')->where(['name'=>'订餐管理','status'=>1])->find();
        if($menu){
            $type_name['dining'] = $this->config['meal_alias_name'] ? '新版'. $this->config['meal_alias_name'] : '新版餐饮';
        }else{
            unset($alias_name['meal']);
            $type_name['dining'] = $this->config['meal_alias_name'] ?: '餐饮';
        }
        $this->assign('type_name', $type_name);


        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();

            $this->assign('now_area',$now_area);

            if ($now_area['area_type'] == 3) {
                $_GET['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }
        if($_GET['begin_time']>0){
            unset($_GET['selectTimeType']);
        }
        $res = D('Order')->goods_date(1,$_GET);

        $this->assign($res);
        $this->display();
    }

    //京东交易
    public function jingdong(){
        // $_POST['type'] = 'shop';
        if(empty($_GET['type'])){
            $_GET['type'] = 'shop';
        }
        if(empty($_GET['platform'])){
            $_GET['platform'] = 0;
        }
        $_GET['mer_id'] = $this->merchant_session['mer_id'];
        $res = D('Order')->sell_order_date_jd(1,$_GET);
        $this->assign($res);
        $this->display();
    }

    public  function get_alias_c_name(){
        $alias_name = array(
            'shop'=>'全部'.($this->config['shop_alias_name'] ?: '外卖'),//全部快店订单
            'group'=>$this->config['group_alias_name'] ?: '团购',
            // 'meal'=>$this->config['meal_alias_name'] ?: '餐饮',
            'appoint'=>$this->config['appoint_alias_name'] ?: '预约',
            'sport'=>'体育',
            'scenic'=>'景区',
            'store'=>'到店',
            'offline_shop'=>'线下零售',//线下零售
            'only_shop'=>$this->config['shop_alias_name'],//去除线下零售
            'life_tools_appoint_join'=>'通用预约',
            'mall'=>$this->config['mall_alias_name_new'],
        );


        // 查看有没有老版的订餐管理
        $menu = M('System_menu')->where(['name'=>'订餐管理','status'=>1])->find();
        if($menu){
            $alias_name['dining'] = $this->config['meal_alias_name'] ? '新版'. $this->config['meal_alias_name'] : '新版餐饮';
        }else{
            unset($alias_name['meal']);
            $alias_name['dining'] = $this->config['meal_alias_name'] ?: '餐饮';
        }

		if(!isset($this->config['meal_alias_name'])){
            unset($alias_name['meal']);
            unset($alias_name['dining']);
        }
        if(!isset($this->config['appoint_alias_name'])){
            unset($alias_name['appoint']);
        }

        return $alias_name;
    }

    public  function mer_list()
    {
        if ($_GET['area'] && $_GET['area']!='undefined') {
            $where['area_id'] = $_GET['area'];
        } else if ($_GET['city'] && $_GET['city']!='undefined') {
            $where['city_id'] = $_GET['city'];
        } else if ($_GET['province'] && $_GET['province']!='undefined') {
            $where['province_id'] = $_GET['province'];
        }

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1) {
                $area_index = 'province_id';

            }
            $where[$area_index] = $this->system_session['area_id'];
        }
        if($_GET['jd']=='jd'){
            $where['is_open_jd'] = 1;
        }
        $_GET['name'] && $where['name'] = array('like', "%{$_GET['name']}%");
        $count = M('Merchant')->where($where)->count();

        $Page = new Page($count, 5);
        $mer_list = M('Merchant')->where($where)->field('mer_id,name')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $get_list = explode(',', $_GET['mer_list']);

        if ($get_list){
            foreach ($mer_list as &$item) {
                if(in_array($item['mer_id'],$get_list)){
                    $item['select'] =1;
                }
            }
        }
        $this->assign('page', $Page->show()); 
        $this->assign('mer_list',$mer_list);
        $this->display();
    }

    public  function store_list(){
        if ($_GET['area'] && $_GET['area']!='undefined') {
            $where['area_id'] = $_GET['area'];
        } else if ($_GET['city'] && $_GET['city']!='undefined') {
            $where['city_id'] = $_GET['city'];
        } else if ($_GET['province'] && $_GET['province']!='undefined') {
            $where['province_id'] = $_GET['province'];
        }

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1) {
                $area_index = 'province_id';

            }
            $where[$area_index] = $this->system_session['area_id'];
        }
        if($_GET['jd']=='jd'){
            $join = ' left join '.C('DB_PREFIX').'merchant as m ON s.mer_id = m.mer_id ';
            $where['m.is_open_jd'] = 1;
        }
        $_GET['name'] = trim($_GET['name']);
        $_GET['name'] && $where['name'] = array('like', "%{$_GET['name']}%");
        I('mer_list') && $where['s.mer_id'] = array('in',I('mer_list'));
       // $_GET['mer_id'] && $where['mer_id'] = array('in',$_GET['mer_id']);
        $count = M('Merchant_store')->alias('s')->join($join)->where($where)->count();
        $Page = new Page($count, 5);
        $store_list = M('Merchant_store')->alias('s')->join($join)->where($where)->field('s.store_id,s.name')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $get_list = explode(',', $_GET['store_list']);

        if ($get_list){
            foreach ($store_list as &$item) {
                if(in_array($item['store_id'],$get_list)){
                    $item['select'] =1;
                }
            }
        }
        $this->assign('page', $Page->show());
        $this->assign('store_list',$store_list);
        $this->display();
    }

    public  function  ajax_get_merchant(){
        $province_id = $_POST['province_id'];
        $city_id = $_POST['city_id'];
        $area_id = $_POST['area_id'];

        $province_id && $where['province_id'] = $province_id;
        $city_id && $where['city_id'] = $city_id;
        $area_id && $where['area_id'] = $area_id;

        $mer_list = M('Merchant')->field('mer_id,name')->where($where)->select();
        $this->ajaxReturn($mer_list);
    }

    public  function  ajax_get_store(){
        $mer_list = $_POST['mer_list'];
        $type = $_POST['type'];
        $where['mer_id']=array('in',$mer_list);
        if($type!='' && $type!='store'){
            $where['have_'.$type]=1;
        }
        $store_list = M('Merchant_store')->field('store_id,name')->where($where)->select();
        $this->ajaxReturn($store_list);
    }

    public function export(){
        $param = $_POST;
        $param['order_type'] = $_POST['type'];
        if($param['order_type'] == 'offline_shop'){
            $param['order_type'] = 'shop';
            $param['order_from'] = 6;
        }elseif($param['order_type'] == 'only_shop'){
            $param['order_type'] = 'shop';
            $param['order_from'] = 0;
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $param['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $param['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $param['province_idss'] =$now_area['area_id'];
            }
        }
        $param['type'] = 'trade_new';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }

    public function goods_export(){
        $param = $_POST;
        $param['order_type'] = $_POST['type'];
        if($param['order_type'] == 'offline_shop'){
            $param['order_type'] = 'shop';
            $param['order_from'] = 6;
        }elseif($param['order_type'] == 'only_shop'){
            $param['order_type'] = 'shop';
            $param['order_from'] = 0;
        }
        $param['type'] = 'goods';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }

    // 营业统计
    public function revenueStatistics(){
        $this->assign('time_arr',$this->time_arr());
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();

            $this->assign('now_area',$now_area);

            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $_GET['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }

        $nowDate = date('Y-m-d',strtotime("-1 day"));
        $this->assign('nowDate',$nowDate);
        if ($_GET['business_type'] == 'deliver') {
            $this->display('revenueStatisticsDeliver');
        }elseif ($_GET['business_type'] == 'market') {
            $typeArr = ['shop'=>C('config.shop_alias_name')];
            $this->assign('typeArr',$typeArr);
            $this->display('revenueStatisticsMarket');
        }elseif ($_GET['business_type'] == 'mall') {
            $this->display('revenueStatisticsMall');
        }elseif ($_GET['business_type'] == 'dining') {
            $this->display('revenueStatisticsDining');
        }else{
            $this->display();
        }
    }

    // 今日营业统计及与昨日对比数据
    public function statisticsToday(){
        $business_type = $_POST['business_type'] = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] =  $_POST['order_type'] ? $_POST['order_type'] : 'shop';
        // 开始时间
        $start_time = strtotime(date('Y-m-d'));
        // 结束时间
        $end_time = time();

        // 昨日开始时间
        $yesterday_start_time =  $start_time-86400;
        // 昨日结束时间
        $yesterday_end_time =  $end_time - 86400;
        
        $_POST['system_session'] = $this->system_session;

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

        // 返回数据
        $returnArrFormat = [];
        
        /*********************** 今日订单 ******************/
        switch ($business_type) {
            case 'market':
                switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                       break;
                }
                $returnArr = D($business_model)->getRevenueStatisticsMarket(0,$start_time,$_POST);
                break;
            case 'shop':
                $business_model = 'Shop_order';
                $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST);
                break;
            case 'mall':
                $business_model = 'Shop_order';
                $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST,1);
                break;
            case 'deliver':
                $business_model = 'Deliver_supply';
                $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST);
                break;
            case 'dining':
                $business_model = 'Dining_order';
                $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST);
                break;
            
        }


        // 昨日
        $_POST['start_time'] = $yesterday_start_time;
        $_POST['end_time'] = $yesterday_end_time;
        switch ($business_type) {
            case 'market':
                switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                       break;
                }
                $returnArrCompare = D($business_model)->getRevenueStatisticsMarket(0,$yesterday_start_time,$_POST);
                break;
            case 'shop':
                $business_model = 'Shop_order';
                $returnArrCompare = D($business_model)->getRevenueStatistics(0,$yesterday_start_time,$_POST);
                break;
            case 'mall':
                $business_model = 'Shop_order';
                $returnArrCompare = D($business_model)->getRevenueStatistics(0,$yesterday_start_time,$_POST,1);
                break;
            case 'deliver':
                $business_model = 'Deliver_supply';
                $returnArrCompare = D($business_model)->getRevenueStatistics(0,$yesterday_start_time,$_POST);
                break;
            
        }

        // 获得对比后的数据
        $returnArrFormat = D('Revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare);
        $this->success($returnArrFormat);
    }


    // 今日营业统计及与昨日对比数据 报表
    public function statisticsTodayChart(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';
        // 累计
        // 营业额 0点到现在 各时段数据
        $data_type = $_POST['data_type']; // 0 营业额 1 有效订单
        $is_add_up = $_POST['is_add_up']; // 0 分时 1 累计

        if ($_POST['area_id'] && $_POST['area_id'] != 'undefined') {
            $where_mer['area_id'] = $_POST['area_id'];
        } else if ($_POST['city_idss'] && $_POST['city_idss'] != 'undefined') {
            $where_mer['city_id'] = $_POST['city_idss'];
        } else if ($_POST['province_idss'] && $_POST['province_idss'] != 'undefined') {
            $where_mer['province_id'] = $_POST['province_idss'];
        }

        if(C('customization.life_tools') && $this->system_session['level'] != 2){// 城投生活通定制 商家绑定管理员
            $adminIdArr = D('Admin')->getAdminByFid($this->system_session['id']);
            $where_mer['admin_id'] = ['in', $adminIdArr];               
        }

        if ($where_mer) {
            $mer_ids = M('Merchant')->where($where_mer)->select();

            foreach ($mer_ids as $st) {
                $mer_id_arr[] = $st['mer_id'];
            }
            if (!$mer_id_arr) {
                $condition['mer_id'] = -1;
                $m_condition['o.mer_id'] = -1;
            }else{
                $condition['mer_id'] = array('in',$mer_id_arr);
                $m_condition['o.mer_id'] = array('in',$mer_id_arr);
            }
        }

        $time_arr = $this->time_arr();

        // 今日0点时间戳
        $start_time = strtotime(date('Y-m-d'));

        $returnArr = [];
        $todayArr = [];
        $yesterdayArr = [];
        foreach ($time_arr as $key => $_time) {
            // 开始时间
            if ($is_add_up) {
                // 累计
                $tmp_s_time = $start_time;
            }else{
                // 分时
                $tmp_s_time = $start_time + $_time*3600;
            }

            // 结束时间
            $tmp_e_time = $start_time + $_time*3600 + 3600;

            // 昨日订单
            if ($is_add_up) {
                // 累计
                $tmp_s_time_y = $start_time - 86400;
            }else{
                // 分时
                $tmp_s_time_y = $start_time + $_time*3600 - 86400;
            }

            // 结束时间
            $tmp_e_time_y = $start_time + $_time*3600 + 3600 - 86400;

            switch ($business_type) {
                case 'deliver': // 配送
                    // 今日订单
                    // 平台配送
                    $condition['type'] = 0;
                    // 有效订单
                    $condition['status'] = ['neq',0];
                    if ($tmp_e_time<time()+3600) {
                        $condition['create_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        $m_condition['o.create_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        if ($data_type==1) {
                            // 有效配送单数
                            $returnArr[$key]['today'] = D('Deliver_supply')->where($condition)->count();
                        }else{
                            $money = D('Deliver_supply')->field('sum(money) as money')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($money['money']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['create_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]);
                    $m_condition['o.create_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]);
                    if ($data_type==1) {
                        // 有效配送单数
                        $returnArr[$key]['yesterday'] = D('Deliver_supply')->where($condition)->count();
                    }else{
                        $money = D('Deliver_supply')->field('sum(money) as money')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($money['money']);
                    }

                    break;
                case 'shop': // 外卖

                    // 已支付
                    $condition['paid'] = 1;

                    // 去掉商城订单
                    $condition['order_from'] = ['neq',1];

                    // 有效订单
                    $condition['order_status'] = array('gt',0);  // 已接单
                    $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        // 支付时间
                        $m_condition['o.pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        if ($data_type==1) {
                            // 平台盈利
                            $returnArr[$key]['today'] = D('Revenue_statistics')->getPlatIncome($condition,$m_condition);
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 平台盈利
                        $returnArr[$key]['yesterday'] = D('Revenue_statistics')->getPlatIncome($condition,$m_condition);

                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    break;
                case 'mall': // 外卖

                    // 已支付
                    $condition['paid'] = 1;

                    // 商城订单
                    $condition['order_from'] = 1;

                    // 有效订单
                    $condition['order_status'] = array('gt',0);  // 已接单
                    $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        // 支付时间
                        $m_condition['o.pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        if ($data_type==1) {
                            // 平台盈利
                            $returnArr[$key]['today'] = D('Revenue_statistics')->getPlatIncome($condition,$m_condition);
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 平台盈利
                        $returnArr[$key]['yesterday'] = D('Revenue_statistics')->getPlatIncome($condition,$m_condition);

                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        //echo $total_price;exit;
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    break;
                case 'dining': // 新版餐饮

                    // 已支付
                    $condition['pay_time'] = ['gt', 0];

                    // 有效订单
                    $condition['status'] = array('between',[4,50]);;  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        // 支付时间
                        $m_condition['o.pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);
                        if ($data_type==1) {
                            // 平台盈利
                            $returnArr[$key]['today'] = D('Dining_order')->getPlatIncome($condition,$m_condition);
                        }else{
                            // 营业额统计
                            $total_price = M('Dining_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 平台盈利
                        $returnArr[$key]['yesterday'] = D('Dining_order')->getPlatIncome($condition,$m_condition);

                    }else{
                        // 营业额统计
                        $total_price = M('Dining_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        //echo $total_price;exit;
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    break;
                case 'market': // 营销

                    // 已支付
                    $condition['paid'] = 1;

                    // 去掉商城订单
                    $condition['order_from'] = ['neq',1];

                    // 有效订单
                    $condition['order_status'] = array('gt',0);  // 已接单
                    $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);

                        $returnArr = D('Shop_order')->getMarketOrderStatistics($condition);
                        if ($data_type==1) {
                            // 带动订单
                            $returnArr[$key]['today'] = $returnArr['market_count'];
                        }else{
                            // 活动总营业额
                            $returnArr[$key]['today'] = $returnArr['market_total_money'];
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    $returnArr = D('Shop_order')->getMarketOrderStatistics($condition);
                    if ($data_type==1) {
                        // 带动订单
                        $returnArr[$key]['yesterday'] = $returnArr['market_count'];

                    }else{
                        // 活动总营业额
                        $returnArr[$key]['yesterday'] =  $returnArr['market_total_money'];
                    }
                    break;
            }
            $yesterdayArr[] = $returnArr[$key]['yesterday'];
            $returnArr[$key]['time'] = date('H:i',$tmp_s_time).'-'.date('H:i',$tmp_e_time);
        }

        $returnArr['yesterdayArr'] = $yesterdayArr;
        $returnArr['todayArr'] = $todayArr;
        $this->success($returnArr);
    }

    // 历史数据分析 不包括今日
    public function statisticsHistory(){
        $business_type = $_POST['business_type'] = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] = $_POST['order_type'] ? $_POST['order_type'] : 'shop';
        // 昨日 近七日 近30日 自定义
        $type = $_POST['type'];

        // 昨日23:59点时间戳
        $end_time = strtotime(date('Y-m-d'))-1;
        switch ($type) {
            case '1': // 昨日
                $start_time = $end_time - 86400;

                // 对比前天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400;
                break;
            case '2': // 近七日
                $start_time = $end_time - 86400*7;
                
                // 对比前七日时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*7;
                break;
            case '3': // 近30日
                $start_time = $end_time - 86400*30;
                
                // 对比30天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*30;
                break;
            case '4': // 自定义
                $start_time = strtotime($_POST['start_time']);
                $end_time = strtotime($_POST['end_time'])+86399;
                
                // 对比时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - ($end_time - $start_time);
                break;
        }

        $compare_text = L_('比前').ceil(($end_time-$start_time)/86400).L_('日');

        // 返回数据
        $returnArrFormat = [];

        $_POST['system_session'] = $this->system_session;

        /*********************** 查询显示数据  *************************/
        $_POST['start_time'] = $start_time;
        $_POST['end_time'] = $end_time;
        $returnArr = D('Revenue_statistics')->getRevenueStatistics($_POST);

        /*********************** 查询对比数据  *************************/
        $_POST['start_time'] = $compare_start_time;
        $_POST['end_time'] = $compare_end_time;
        $returnArrCompare = D('Revenue_statistics')->getRevenueStatistics($_POST);

        $returnArrFormat = D('Revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare,1,$compare_text);
        $this->success($returnArrFormat);
    }

    // 历史数据分析 不包括今日 店铺明细 导出报表
    public function statisticsHistoryByMer(){ 
        $business_type = $_GET['business_type'] ? $_GET['business_type'] : 'shop';
        $order_type = $_GET['order_type'] ? $_GET['order_type'] : 'shop';

        // 昨日 近七日 近30日 自定义
        $type = $_GET['type'] ? $_GET['type'] : 2;

        // 昨日23:59点时间戳
        $end_time = strtotime(date('Y-m-d'))-1;
        $time_show = '';
        switch ($type) {
            case '1': // 昨日
                $start_time = $end_time - 86400;

                // 对比前天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400;
                break;
            case '2': // 近七日
                $start_time = $end_time - 86400*7;
                $time_show = L_('近7日');
                
                // 对比前七日时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*7;
                break;
            case '3': // 近30日
                $start_time = $end_time - 86400*30;
                $time_show = L_('近30日');
                
                // 对比30天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*30;
                break;
            case '4': // 自定义
                $start_time = strtotime($_GET['start_time']);
                $end_time = strtotime($_GET['end_time'])+86399;
                
                // 对比时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - ($end_time - $start_time);
                break;
        }
        $compare_text = L_('比前').ceil(($end_time-$start_time)/86400).L_('日');
        $time_show .= '（'.date('Y-m-d',$start_time).'-'.date('Y-m-d',$end_time).'）';

       
        $condition['s.statistics_date'] = array('between',[$start_time,$end_time]); // 时间
        
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }

            $this->assign('admin_area',$now_area['area_type']);
            $where_mer[$area_index] = $this->system_session['area_id'];

            if($this->system_session['street_id']){
                $where_mer['street_id'] = $this->system_session['street_id'];
            }
        }
        if ($_GET['area_id'] && $_GET['area_id'] != 'undefined') {
            $where_mer['area_id'] = $_GET['area_id'];
        } else if ($_GET['city_idss'] && $_GET['city_idss'] != 'undefined') {
            $where_mer['city_id'] = $_GET['city_idss'];
        } else if ($_GET['province_idss'] && $_GET['province_idss'] != 'undefined') {
            $where_mer['province_id'] = $_GET['province_idss'];
        }

        if(C('customization.life_tools') && $this->system_session['level'] != 2){// 城投生活通定制 商家绑定管理员
            $adminIdArr = D('Admin')->getAdminByFid($this->system_session['id']);
            $where_mer['admin_id'] = ['in', $adminIdArr];               
        }

        if ($where_mer) {
            $mer_ids = M('Merchant')->where($where_mer)->select();

            foreach ($mer_ids as $st) {
                $mer_id_arr[] = $st['mer_id'];
            }
            if (!$mer_id_arr) {
                $condition['s.mer_id'] = null;
            }else{
                $condition['s.mer_id'] = array('in',$mer_id_arr);
            }
        }     

        // 返回数据
        $returnArr = [];
        // 查询字段
        $field = D('Revenue_statistics')->getRevenueStatisticsField($business_type);
        $field .= ',m.name';
        // 查询类型
        $condition['s.business_type'] = $business_type;

        $store_count = M('Revenue_statistics')->field('s.mer_id')->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->select();
        $store_count = count($store_count);
        import('@.ORG.diypage');
        $p = new Page($store_count, 6);
        $mer_list = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->order('total_money DESC')->limit($p->firstRow,$p->listRows)->select();
        // 配送统计
        if ($business_type=='deliver'&&$_GET['page']<=1) {
            // 跑腿 社区团购
            $condition['s.mer_id'] = ['in',[-1,-2]];
            $mer_list_pt = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->select();
            $mer_list_pt = $mer_list_pt ? $mer_list_pt : array();
            $mer_list = array_merge($mer_list,$mer_list_pt);
        }

        // 对比数据
        $condition['s.statistics_date'] = array('between',[$compare_start_time,$compare_end_time]); // 时间
        $mer_list_compare = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->order('total_money DESC')->limit($p->firstRow,$p->listRows)->select();
        if ($business_type=='deliver'&&$_GET['page']<=1) {
            // 跑腿 社区团购
            $condition['s.mer_id'] = ['in',[-1,-2]];
            $mer_list_pt = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->select();
            $mer_list_pt = $mer_list_pt ? $mer_list_pt : array();
            $mer_list_compare = array_merge($mer_list_compare,$mer_list_pt);
        }
        $compareArray = [];
        foreach ($mer_list_compare as $key => $value) {
            $compareArray[$value['mer_id']] = $value;
        }

        $merchantArr = [];
        foreach ($mer_list as $key => &$value) {
            // 处理数据
            $value = D('Revenue_statistics')->getFormatData($business_type,$value);

            // 对比数据
            $_tmp_store_format = D('Revenue_statistics')->getCompareData($business_type,$value,$compareArray[$value['mer_id']],1,$compare_text);
            $_tmp_store_format['time_show'] = $time_show;
            $_tmp_store_format['name'] = $value['name'];
            $merchantArr[] = $_tmp_store_format;
        }
        $this->assign('page_bar',$p->show());
        $this->assign('merchantArr',$merchantArr);
        $this->display();
    }

    public function time_arr(){
        return array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
    }

    public function statistics_export(){
        $_GET['business_type'] = $_GET['business_type'] ? $_GET['business_type'] : 'shop';
        $_GET['order_type'] = $_GET['order_type'] ? $_GET['order_type'] : 'shop';
        $param = $_GET;
        $param['time_type'] = $_GET['type'];
        $param['type'] = 'platStatistics';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }


}