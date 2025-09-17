<?php
/*
 * 数据分析
 *
 * @  Writers    jun
 * @  BuildTime  2015/12/30 8:00
 * 
 */
class AnalysisAction extends BaseAction {

    //交易汇总
    public function trade(){
       // $_POST['type'] = 'shop';
        if(empty($_GET['type'])){
            $_GET['type'] = 'shop';
        }
        if(empty($_GET['platform'])){
            $_GET['platform'] = 0;
        }
        if(empty($_GET['pay_type'])){
            $_GET['pay_type'] = 'all';
        }
        $_GET['mer_id'] = $this->merchant_session['mer_id'];
        $pay_method = D('Config')->get_pay_method();
        $this->assign('type_name',$this->get_alias_c_name());
        $this->assign('pay_method',$pay_method);
        $res = D('Order')->sell_order_date(0,$_GET);
        $this->assign($res);
        $this->display();
    }

    //菜品分析
    public function goods(){
        if(empty($_GET['type'])){
            $_GET['type'] = 'shop';
        }
        $type_name = array(
            'shop'=>$this->config['shop_alias_name'],
            'meal'=>$this->config['meal_alias_name'],
        );

        // 查看有没有老版的订餐管理
        $menu = M('System_menu')->where(['name'=>'订餐管理','status'=>1])->find();
        if($menu){
            $type_name['dining'] = $this->config['meal_alias_name'] ? '新版'. $this->config['meal_alias_name'] : '新版餐饮';
        }else{
            $type_name['dining'] = $this->config['meal_alias_name'] ?: '餐饮';
        }

        $this->assign('type_name',$type_name);


        $store_list = M('Merchant_store')->where(array('mer_id'=>$this->merchant_session['mer_id']))->select();
        $param = $_GET;
        $param['mer_id'] = $this->merchant_session['mer_id'];
        if($_GET['store_list']==-1 &&  $store_list){
            $param['store_list'] = array();
            foreach ($store_list as $v) {
                $tmp[] = $v['store_id'];
            }
            $param['store_list'] = implode(',',$tmp);
        }
        if($_GET['begin_time']>0){
            unset($_GET['selectTimeType']);
        }

        $res = D('Order')->goods_date(0,$param);

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
        $res = D('Order')->sell_order_date_jd(0,$_GET);
        $this->assign($res);
        $this->display();
    }

    public  function get_alias_c_name(){
        $alias_name = array(
            'shop'    => $this->config['shop_alias_name'],
            'group'   => $this->config['group_alias_name'],
            // 'meal'    => $this->config['meal_alias_name'],
            'appoint' => $this->config['appoint_alias_name'],
            'store'   => L_('到店'),
            'mall'    => L_($this->config['mall_alias_name_new']),
        );
        if (C('customization.life_tools')) {
            $alias_name['life_tools_appoint_join'] = L_('活动预约');
        }
        
         // 查看有没有老版的订餐管理
        $menu = M('System_menu')->where(['name'=>'订餐管理','status'=>1])->find();
        if($menu){
            $alias_name['dining'] = $this->config['meal_alias_name'] ? '新版'. $this->config['meal_alias_name'] : '新版餐饮';
        }else{
            unset($alias_name['meal']);
            $alias_name['dining'] = $this->config['meal_alias_name'] ?: '餐饮';
        }

        if(!isset($this->config['appoint_alias_name'])){
            unset($alias_name['appoint']);
        }

        $pluginLabels = M('Plugin')->where(['show' => 1])->getField('plugin_label', true);
        if (!in_array('shop', $pluginLabels)) {
            unset($alias_name['shop']);
        }
        if (!in_array('group', $pluginLabels)) {
            unset($alias_name['group']);
        }
        if (!in_array('dining', $pluginLabels)) {
            unset($alias_name['meal'],$alias_name['dining']);
        }
        if (!in_array('appoint', $pluginLabels)) {
            unset($alias_name['appoint']);
        }
        if (!in_array('store', $pluginLabels)) {
            unset($alias_name['store']);
        }
        if (!in_array('mall', $pluginLabels)) {
            unset($alias_name['mall']);
        }

        return $alias_name;
    }

    public  function store_list(){
        $where['mer_id'] = $this->merchant_session['mer_id'];
		$where['status'] = array('neq','4');
        $_POST['name'] && $where['name'] = array('like', "%{$_POST['name']}%");

       // $_GET['mer_id'] && $where['mer_id'] = array('in',$_GET['mer_id']);
        $count = M('Merchant_store')->where($where)->count();
        $Page = new Page($count, 5);
        $store_list = M('Merchant_store')->where($where)->field('store_id,name')->limit($Page->firstRow . ',' . $Page->listRows)->select();

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

    public  function staff_list(){
        $where['token'] = $this->merchant_session['mer_id'];
        if($_POST['name']){
            $where['_string'] = "name like '%{$_POST['name']}%' OR username like '%{$_POST['name']}%'";
        }

         $_GET['store_list'] && $where['store_id'] = array('in',$_GET['store_list']);
        $count = M('Merchant_store_staff')->where($where)->count();
        $Page = new Page($count, 5);
        $staff_list = M('Merchant_store_staff')->where($where)->field('id,name,store_id,username')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $get_list = explode(',', $_GET['staff_list']);

        if ($get_list){
            foreach ($staff_list as &$item) {
                if(in_array($item['id'],$get_list)){
                    $item['select'] =1;
                }
            }
        }
        $this->assign('page', $Page->show());
        $this->assign('staff_list',$staff_list);
        $this->display();
    }

    public  function type_list(){
        if(empty($_GET['store_list'])) {
            $condition['mer_id'] = $this->merchant_session['mer_id'];
            $store_list = M('Merchant_store')->where($condition)->select();
            foreach ($store_list as $v) {
                $tmp[] = $v['store_id'];
            }

            if(empty($tmp)){
                $this->error(L_('店铺不存在'));
            }
        }else{
            $tmp = explode(',',$_GET['store_list']);
        }


        if($_GET['type']=='shop'){
			$count =  M('Shop_goods_sort')->where(array('store_id'=>array('in',$tmp)))->count();
			$Page = new Page($count, 5);
			$sort_list = M(_view('Shop_goods_sort'))->where(array('store_id'=>array('in',$tmp)))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }else{
            $count =  M('Foodshop_goods_sort')->where(array('store_id'=>array('in',$tmp)))->count();
            $Page = new Page($count, 5);
            $sort_list = M('Foodshop_goods_sort')->where(array('store_id'=>array('in',$tmp)))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }


        $this->assign('page', $Page->show());
        $this->assign('sort_list',$sort_list);
        $this->display();
    }

    public function export(){
        $param = $_POST;
        $param['order_type'] = $_POST['type'];
        $param['type'] = 'trade';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        //$param['appoint_id'] = 'appoint_id';
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }

    public function goods_export(){
        $param = $_POST;
        $param['order_type'] = $_POST['type'];
        $param['type'] = 'goods';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        //$param['appoint_id'] = 'appoint_id';
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];

        $store_list = M('Merchant_store')->where(array('mer_id'=>$this->merchant_session['mer_id']))->select();
        if($_POST['store_list']==-1 &&  $store_list){
            $param['store_list'] = array();
            foreach ($store_list as $v) {
                $tmp[] = $v['store_id'];
            }
            $param['store_list'] = implode(',',$tmp);
        }
        
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }

    /*
        1. 营业额：今日流水总额，包含：1. 商品原价   2. 打包费  3. 配送费/快递费
        2. 订单收入：今日净收入，扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额
        3. 有效订单：已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消
        4. 无效订单：包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。
        5. 活动补贴：平台/商家满减优惠与平台/商家优惠券补贴总额，包含：1. 平台满减优惠平台补贴金额部分，含满减优惠的所有类型  2. 平台满减优惠商家补贴金额部分，含满减优惠的所有类
        型  3. 商家自有满减优惠商家补贴金额部分   4. 平台优惠券平台补贴金额部分   5. 商家优惠券商家补贴金额部分   6. 平台积分抵扣平台补贴部分
        6. 损失：包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。
        7. 客单价：有效订单的平均单价。
        8. 下单用户数：当前时段下的下单用户数。
        9. 占比：这条只是提供计算公式，不做描述给商家看，分【比昨日同时段下降多少百分比】与【比昨日同时段上升多少百分比】公式为：当期同比增长（下降）率(%)=[(当期营收额/上期营收额)-1]*100%。如果计算值为正值（+），则称增长率；如果计算值为负值（-），则称下降率；如果计算值为0，则称持平。
        10. 其他：【数据折线图里的累计】，即将今日分为24个小时点，【累计】即为从0点到选中的几点，比如查看0点到11点的数据；【数据折线图里的分时】，即将今日分为24个小时点，【分时】
        即为每一个小时段的数据，比如查看11点到12点的数据；【Excel里的打包费】，累计打包费；【Excel里的平台配送费用】，包括平台配送费；【Excel里的商家配送费用】，包括商家配送费、
        快递费用。
    */

    // 营业统计
    public function revenueStatistics(){
        $nowDate = date('Y-m-d',strtotime("-1 day"));
        $this->assign('nowDate',$nowDate);
        $this->assign('time_arr',$this->time_arr());

        if ($_GET['business_type'] == 'market') {
            $typeArr = ['shop'=>C('config.shop_alias_name')];
            $this->assign('typeArr',$typeArr);
            $this->display('revenueStatisticsMarket');
        }else if ($_GET['business_type'] == 'mall') {
            $typeArr = ['shop'=>C('config.mall_alias_name')];
            $this->assign('typeArr',$typeArr);
            $this->display('revenueStatisticsMall');
        }else if ($_GET['business_type'] == 'dining') {
            $typeArr = ['dining'=>C('config.meal_alias_name')];
            $this->assign('typeArr',$typeArr);
            $this->display('revenueStatisticsDining');
        }else{
            $this->display();
        }

    }

    // 今日营业统计及与昨日对比数据
    public function statisticsToday(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';

        // 开始时间
        $start_time = strtotime(date('Y-m-d'));
        // 结束时间
        $end_time = time();

        // 昨日开始时间
        $yesterday_start_time =  $start_time-86400;
        // 昨日结束时间
        $yesterday_end_time =  $end_time - 86400;

        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);

        // 商家id
        $condition['mer_id'] = $this->merchant_session['mer_id'];
        $m_condition['o.mer_id'] = $this->merchant_session['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
            $m_condition['o.store_id'] = array('in',$store_id);
        }

        // 返回数据
        $returnArrFormat = [];

        /*********************** 今日订单 ******************/
        switch ($business_type) {
            case 'market'://营销
                switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                        break;
                }
                $returnArr = D($business_model)->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],$start_time,$_POST);
                break;
            case 'shop'://外卖
                $business_model = 'Shop_order';
                // $returnArr = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$start_time,$_POST);
                $returnArr = D($business_model)->getHistoryStatistics($condition,$m_condition,1);
                break;
            case 'mall'://外卖
                $business_model = 'Shop_order';
                // $returnArr = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$start_time,$_POST);
                $returnArr = D($business_model)->getHistoryStatistics($condition,$m_condition,1,1);
                break;
            case 'dining'://新版餐饮
                $business_model = 'Dining_order';
                $returnArr = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$start_time,$_POST);
                break;
            
        }

        /*********************** 昨日订单  ****************/
        switch ($business_type) {
            case 'market'://营销
               switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                       break;
                }
                $returnArrCompare = D($business_model)->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],$yesterday_start_time,$_POST);
                break;
            case 'shop'://外卖
                $condition['pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $m_condition['o.pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $business_model = 'Shop_order';
                // $returnArrCompare = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$yesterday_start_time,$_POST);

                $returnArrCompare = D($business_model)->getHistoryStatistics($condition,$m_condition);
                break;
            case 'mall'://外卖
                $condition['pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $m_condition['o.pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $business_model = 'Shop_order';
                // $returnArrCompare = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$yesterday_start_time,$_POST);

                $returnArrCompare = D($business_model)->getHistoryStatistics($condition,$m_condition,0,1);
                break;
            case 'dining'://新版餐饮
                $condition['pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $m_condition['o.pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $business_model = 'Dining_order';
                $returnArrCompare = D($business_model)->getRevenueStatistics($this->merchant_session['mer_id'],$yesterday_start_time,$_POST);
                break;
            
        }

        /*********************** 订单数据对比  ****************/
        // 获得对比后的数据
        $returnArrFormat = D('Merchant_revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare);
        $returnArrFormat['discount_info'] = $returnArr['discount_info'];
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
        // 商家id
        $condition['mer_id'] = $this->merchant_session['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
        }

        // 已支付
        $condition['paid'] = 1;

        if($order_type=='mall'){
            $condition['order_from'] = 1;
        }else{
            // 去掉商城
            $condition['order_from'] = ['neq',1];
        } 

        $time_arr = $this->time_arr();

        // 今日0点时间戳
        $start_time = strtotime(date('Y-m-d'));

        $returnArr = [];
        $todayArr = [];
        $yesterdayArr = [];
        $yesterdayTotal = 0;
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
                case 'shop': // 外卖
                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]); // 支付时间
                        if ($data_type==1) {
                            // 有效订单统计
                            $condition['order_status'] = array('gt',0); // 已接单
                            $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                            $returnArr[$key]['today'] = M('Shop_order')->where($condition)->count();
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 有效订单统计
                        $condition['order_status'] = array('gt',0); // 已接单
                        $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                        $returnArr[$key]['yesterday'] = M('Shop_order')->where($condition)->count();
                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
                    break;
                case 'mall': // 商城
                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]); // 支付时间
                        if ($data_type==1) {
                            // 有效订单统计
                            $condition['order_status'] = array('gt',0); // 已接单
                            $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                            $returnArr[$key]['today'] = M('Shop_order')->where($condition)->count();
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 有效订单统计
                        $condition['order_status'] = array('gt',0); // 已接单
                        $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                        $returnArr[$key]['yesterday'] = M('Shop_order')->where($condition)->count();
                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
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
                            // 有效订单统计
                            $returnArr[$key]['today'] = M('Dining_order')->where($condition)->count();
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
                        // 有效订单统计
                        $returnArr[$key]['yesterday'] = M('Dining_order')->where($condition)->count();

                    }else{
                        // 营业额统计
                        $total_price = M('Dining_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        //echo $total_price;exit;
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    
                    break;
                case 'market': // 营销
                    // 有效订单
                    $condition['order_status'] = array('gt',0);  // 已接单
                    $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+3600) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);

                        $returnArr = D('Shop_order')->getMarketOrderStatisticsMerchant($condition);
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
                    $returnArr = D('Shop_order')->getMarketOrderStatisticsMerchant($condition);
                    if ($data_type==1) {
                        // 带动订单
                        $returnArr[$key]['yesterday'] = $returnArr['market_count'];

                    }else{
                        // 活动总营业额
                        $returnArr[$key]['yesterday'] =  $returnArr['market_total_money'];
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
                    break;
            }
            $returnArr[$key]['time'] = date('H:i',$tmp_s_time).'-'.date('H:i',$tmp_e_time);

        }
        $returnArr['yesterdayArr'] = $yesterdayArr;
        $returnArr['yesterdayTotal'] = L_('昨日全天').getFormatNumber($yesterdayTotal).($data_type ? L_('单') : C('config.Currency_txt'));
        $returnArr['todayArr'] = $todayArr;
        $this->success($returnArr);
    }

    // 历史数据分析 不包括今日
    public function statisticsHistory(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';
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
        $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间

         // 商家id
        $condition['mer_id'] = $this->merchant_session['mer_id'];
        $m_condition['o.mer_id'] = $this->merchant_session['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
            $m_condition['o.store_id'] = array('in',$store_id);
        }

        // 返回数据
        $returnArrFormat = [];
        switch ($business_type) {
            case 'shop': // 外卖 
                /*********************** 查询显示数据  *************************/
                $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition,1);

                /*********************** 查询对比数据  *************************/
                $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition);
                break;
            case 'mall': // 商城 
                /*********************** 查询显示数据  *************************/
                $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition,1,1);

                /*********************** 查询对比数据  *************************/
                $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition,0,1);
                break;
            case 'market': // 营销
                $_POST['start_time'] = $start_time;
                $_POST['end_time'] = $end_time;
                $returnArr = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],0,$_POST);

                // 处理数据
                $returnArr = D('Revenue_statistics')->getFormatData($business_type,$returnArr);

                $_POST['start_time'] = $compare_start_time;
                $_POST['end_time'] = $compare_end_time;
                $returnArrCompare = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],0,$_POST);

                // 处理数据
                $returnArrCompare = D('Revenue_statistics')->getFormatData($business_type,$returnArrCompare);
                break;
            case 'dining': // 营销
                
                $returnArr = D('Dining_order')->getHistoryStatistics($condition,$m_condition,1,1);

                /*********************** 查询对比数据  *************************/
                $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $returnArrCompare = D('Dining_order')->getHistoryStatistics($condition,$m_condition,0,1);
                break;
        }
       
        // 获得对比后的数据
        $returnArrFormat = D('Merchant_revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare,1,$compare_text);

        $returnArrFormat['discount_info'] = $returnArr['discount_info'];
        //dump($returnArrFormat);exit;
        $this->success($returnArrFormat);
    }

    // 历史数据分析 不包括今日 店铺明细 导出报表
    public function statisticsHistoryByStore(){
        $business_type = $_GET['business_type'] ? $_GET['business_type'] : 'shop';
        $order_type = $_GET['order_type'] ? $_GET['order_type'] : 'shop';
        // 昨日 近七日 近30日 自定义
        $type = $_GET['type'] ?: 2;

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
    
        // 返回数据
        $returnArr = [];

        // 商家id
        $store_condition['ms.mer_id'] = $this->merchant_session['mer_id'];

        // 店铺id
        $store_id = $_GET['store_list'];
        if ($store_id&&$store_id!=-1) {
            $store_condition['s.store_id'] = array('in',$store_id);
        }

        $store_count = M('Merchant_store_shop')->join(' as s left join '.C('DB_PREFIX')._view('merchant_store').' as ms on ms.store_id=s.store_id')->where($store_condition)->count();

        import('@.ORG.diypage');
        $p = new Page($store_count, 6);
        $store_list = M('Merchant_store_shop')->field('s.*,ms.*')->join(' as s left join '.C('DB_PREFIX')._view('merchant_store').' as ms on ms.store_id=s.store_id')->where($store_condition)->order('s.store_id ASC')->limit($p->firstRow,$p->listRows)->select();
        
        $storeArr = [];
        foreach ($store_list as $key => $value) {
            $returnArr = [];
            $_tmp_store_format = [];

            switch ($business_type) {
                case 'shop': // 外卖 
                    $condition['store_id'] = $value['store_id']; // 店铺id
                    $m_condition['o.store_id'] = $value['store_id']; // 店铺id
                    $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition);
                    // 对比数据
                    $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition);
                    break;
                case 'mall': // 商城
                    $condition['store_id'] = $value['store_id']; // 店铺id
                    $m_condition['o.store_id'] = $value['store_id']; // 店铺id
                    $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition,0,1);
                    // 对比数据
                    $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition,0,1);
                    break;
                case 'market': // 营销
                    $_GET['start_time'] = $start_time;
                    $_GET['end_time'] = $end_time;
                    $_GET['store_list'] = $value['store_id'];
                    $returnArr = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],0,$_GET);

                    // 处理数据
                    $returnArr = D('Revenue_statistics')->getFormatData($business_type,$returnArr);

                    $_GET['start_time'] = $compare_start_time;
                    $_GET['end_time'] = $compare_end_time;
                    $returnArrCompare = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant_session['mer_id'],0,$_GET);

                    // 处理数据
                    $returnArrCompare = D('Revenue_statistics')->getFormatData($business_type,$returnArrCompare);
                    break;
                case 'dining': // 新版餐饮
                    $condition['store_id'] = $value['store_id']; // 店铺id
                    $m_condition['o.store_id'] = $value['store_id']; // 店铺id
                    $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                    $returnArr = D('Dining_order')->getHistoryStatistics($condition,$m_condition,0,1);
                    // 对比数据
                    $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                    $returnArrCompare = D('Dining_order')->getHistoryStatistics($condition,$m_condition,0,1);
                    break;
            }

            // 获得对比后的数据
            $_tmp_store_format = D('Merchant_revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare,1,$compare_text);

            $_tmp_store_format['time_show'] = $time_show;
            $_tmp_store_format['name'] = $value['name'];
            $storeArr[] = $_tmp_store_format;
        }

        // var_dump($storeArr);
        $this->assign('page_bar',$p->show());
        $this->assign('storeArr',$storeArr);
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
        $param['type'] = 'statistics';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }

}
