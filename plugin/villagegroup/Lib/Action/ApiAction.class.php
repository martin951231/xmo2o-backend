<?php
/**
 * 新版社区拼团接口api
 * 2019-7-04
 */
class ApiAction extends BaseAction{
	//首页接口
    public function index(){
    	$output = [];
		//顶部轮播图
		$adver = PD("Adver");
		$output['top_slider'] = $adver->get_adver_by_key('village_group_index',5);

		//团长信息
		$headerModel = D("Village_group_header");
		$output['leader_info'] = $headerModel->getInfo($this->leader_id, 'h.name,h.phone,u.avatar,a.name as village_name,h.status');
		
		if($this->leader_id && !$output['leader_info']){	//团长不存在时，返回前端状态为4，前端依据此值跳转重新选择团长。
            $output['leader_info'] = [
                'status' => '4'
            ];
        }
		
		//icon导航
		$slider = PD("Slider");
		$output['middle_slider'] = $slider->get_slider_by_key("village_group_index", 30);

        //新人专享
        $output['new_user'] = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, 1, 6, ['goods.is_new_user' => 1]);

		//每日必买
		$activityModel = D("Village_group_activity");
		$output['mustbuy_today'] = $activityModel->getTopActivity($this->leader_id);

		//限时抢购
		$output['limit_time_buy'] = $activityModel->getLimitBuyActivity($this->leader_id);

		//分类
		$categoryModel = D("Village_group_category");
		$output['category_list'] = $categoryModel->getHotRecomCategory($this->leader_id);

        //分享
        $output['share_info'] = [
            'village_group_share_open' => $this->config['village_group_share_open'],
            'wx_title' => $this->config['village_group_wx_share_title'],
            'wx_des' => $this->config['village_group_wx_share_des'],
            'wx_pic' => replace_file_domain($this->config['village_group_wx_share_img']),
            'pic' =>replace_file_domain($this->config['village_group_wxapp_share_img']),
            'title' =>$this->config['village_group_wxapp_share_title'],
            'des' =>$this->config['village_group_wx_share_des'],
        ];
        $output['alias_name'] = $this->config['village_group_alias'];

		returnCode(0, $output);
    }

    public function new_user(){
        $page = I("page") ? I("page") : 1;
        $pagesize = I("pagesize") ? I("pagesize") : 20;
        $img = $this->config['new_user_activity_img'];
        $data = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, $page, $pagesize, ['goods.is_new_user' => 1]);
        returnCode(0, ['img' => $img, 'goods' => $data]);
    }

    //活动商品接口
    public function activity_goods(){
    	$activity_id = I("activity_id");
    	$page = I("page") ? I("page") : 1;
    	$pagesize = I("pagesize") ? I("pagesize") : 20;
    	if(empty($activity_id)) returnCode(1, [], 'activity_id 参数错误！');
    	$Village_group_goodsModel = D("Village_group_goods");
    	$goods = $Village_group_goodsModel->get_goods($this->leader_id, 0, $activity_id, $page, $pagesize, ['goods.is_new_user' => 0]);
    	returnCode(0, $goods);
    }

    //分类接口(分类必须要有商品才可以展示哦)
    public function category_list(){
    	$Village_group_categoryModel = D("Village_group_category");
    	$category_list = $Village_group_categoryModel->getHasGoodsCategory($this->leader_id);
    	returnCode(0, $category_list);
    }

    //获取商品列表(带分页)
    public function goods_list(){
    	$keyword = trim(I("keyword"));
    	$category_id = I("category_id");
        $category_son_id = I("category_son_id");
    	$page = I("page") ? I("page") : 1;
    	$pagesize = I("pagesize") ? I("pagesize") : 20;
    	$Village_group_goodsModel = D("Village_group_goods");
    	$extra_where = [
            'goods.is_new_user' => ['in', [0, 1]]
        ];
    	if($keyword){
    		$extra_where['goods.name'] = ['like', '%'.$keyword.'%'];
    	}
    	if($category_id){
    		$extra_where['goods.cat_fid'] = ['eq', $category_id];
    	}
        if($category_son_id){
            $extra_where['goods.cat_id'] = ['eq', $category_son_id];
        }
    	$goods = $Village_group_goodsModel->get_goods($this->leader_id, 0, 0, $page, $pagesize, $extra_where);
    	returnCode(0, $goods);
    }

    //限时抢购活动详情
    public function limit_time_buy(){
        $activity_id = I("activity_id");
        if(empty($activity_id)){
             returnCode(1, [], 'activity_id 参数错误！');
        }
        $activityModel = D("Village_group_activity");
        $data = $activityModel->getLimitBuyActivity($this->leader_id, $activity_id, false);
        returnCode(0, $data);
    }

    //每日必买活动详情
    public function mustbuy_today(){
        $activity_id = I("activity_id");
        if(empty($activity_id)){
             returnCode(1, [], 'activity_id 参数错误！');
        }
        $activityModel = D("Village_group_activity");
        $data = $activityModel->getTopActivity($this->leader_id, $activity_id, false);
        returnCode(0, $data);
    }

    /**
     * 当前团长接口
     * 如果没有传入团长ID，则需要传入当前定位
     * @return [type] [description]
     */
    public function current_leader(){
        $leader_id = intval(I("leader_id"));
        $lng = I("lng");
        $lat = I("lat");
        if(empty($leader_id) && empty($lng) && empty($lat)){
            returnCode(1, [], '定位参数至少需要有一个！');
        }
        $header = [];
        if($leader_id){
            $header = PD('Village_group')->get_header_info(0,$leader_id);
            if($header['error_code'] == '0'){
                $header = $header['msg'];
            }
            else{
                returnCode(2, [], 'leader_id 当前团长不存在！');
            }
        }else{
            $now_area =  PD('Area')->cityMatching($lat,$lng);
            $condition['h.status'] = 1;
            $condition['s.status'] = 1;
            $field = "h.*,s.city_id,s.area_id,s.province_id,s.name as village_name,s.long,s.lat,s.pick_addr,u.avatar,ROUND(12756.276 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
            $header = M('Village_group_pick_address')->field($field)->join('as s LEFT JOIN '.C('DB_PREFIX').'village_group_header as h ON s.header_uid = h.id LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where($condition)->order('juli ASC')->find();
            if(empty($header)){
                returnCode(2, [], 'lat,lng 没有找到附近的团长信息！');
            }
            $header['pick_address']['name'] = $header['village_name'];
            $header['pick_address']['pick_addr'] = $header['pick_addr'];
            if (!$header['avatar']) {
                $header['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
            } else {
                if(stripos($header['avatar'],'/upload/') === 0){
                    $header['avatar'] = file_domain().$header['avatar'];
                }
                else{
                    $header['avatar'] = replace_file_domain($header['avatar']);
                }
            }
        }
        if (C('config.village_group_hide_detail')) {
            $header['phone'] = '';
            $header['name'] = str_replace_name($header['name']);
            if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
                if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
                    $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
                }else{
                    $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
                }
            }
        }
        if ($lat > 0 && $lng > 0 && $header['pick_address']['lat'] > 0 && $header['pick_address']['long'] > 0) {
            $header['distance']=$this->wapFriendRange( $header['juli']);
        } else {
            $header['distance']='';
        }
        returnCode(0, $header);
    }

    public function wapFriendRange($meter){
        if($meter < 100){
            return '<100m';
        }else if($meter <1000){
            return $meter.'m';
        }else{
            return round($meter/1000,1).'km';
        }
    }

    //附近团长列表
    public function near_leader_list(){
        $lng = I("lng");
        $lat = I("lat");
        if(empty($lng) && empty($lat)){
            returnCode(1, [], '定位参数必须！');
        }
        $now_area =  PD('Area')->cityMatching($lat,$lng);
        $condition['h.status'] = 1;
        $condition['s.status'] = 1;
        $field = "h.*,s.city_id,s.area_id,s.province_id,s.name as village_name,s.long,s.lat,s.pick_addr,u.avatar,ROUND(12756.276 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
        $header = M('Village_group_pick_address')->field($field)->join('as s LEFT JOIN '.C('DB_PREFIX').'village_group_header as h ON s.header_uid = h.id LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where($condition)->order('h.sort desc')->select();
        if($header){
            $header = sortArray($header,'juli');
            foreach ($header as &$item) {
                $item['juli_txt'] = $this->wapFriendRange($item['juli']);
                if (C('config.village_group_hide_detail')) {
                    $item['name'] = str_replace_name($item['name']);
                    if (mb_strlen($item['pick_addr'],'UTF8') > 3) {
                            if (mb_strlen($item['pick_addr'],'UTF8') == 4) {
                            $item['pick_addr'] =  mb_substr($item['pick_addr'],0,-3).'****';
                        }else{
                            $item['pick_addr'] =  mb_substr($item['pick_addr'],0,-4).'****';
                        }
                    }
                }
                if (!$item['avatar']) {
                    $item['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
                } else {
                    if(stripos($item['avatar'],'/upload/') === 0){
                        $item['avatar'] = file_domain().$item['avatar'];
                    }
                    else{
                        $item['avatar'] = replace_file_domain($item['avatar']);
                    }
                }
            }
        }
        returnCode(0, $header);
    }

    //添加、修改购物车
    public function save_cart(){
        //1、参数 单商品 对应修改数量
        $goods_id = I("goods_id");
        $nums = I("nums");
        $checked = I("checked") ? I("checked") : 0;

        //如果nums=0，就是删除了
        $del = false;
        if($goods_id == 0){//传0 = 全选 或者全不选
            if($checked == 1){//全选
                M('Village_group_order_cart')->where(['uid'=>$this->_uid, 'start_id' => $this->leader_id])->data(['checked' => 1])->save();
            }
            else{
                M('Village_group_order_cart')->where(['uid'=>$this->_uid, 'start_id' => $this->leader_id])->data(['checked' => 0])->save();   
            }
        }
        elseif($nums == 0){
            $del_where = [
                'goods_id' => $goods_id,
                'uid' => $this->_uid,
                'start_id' => $this->leader_id
            ];
            if(M("Village_group_order_cart")->where($del_where)->delete()){
                $del = true;
            }
            else{
                returnCode(2 ,[] , "移除失败");   
            }
        }
        else{
            $goods = [$goods_id=>$nums];

            //2、检查单个商品（过期、下架、活动前夕、库存不足、得到价格）
            if($checked){
                $check = D("Village_group_goods")->check_goods($this->leader_id, $goods, $this->_uid);
            }
            else{
                $check = D("Village_group_goods")->check_goods($this->leader_id, $goods, $this->_uid, false);   
            }
            if(!$check['status']){
                returnCode(2 ,[] , $check['msg']);
            }

            $where = [
                'uid' => $this->_uid,
                'start_id' => $this->leader_id,
                'count' => ['gt', 0],
                'single_buy' => 0
            ];
            $goods_cart = M('Village_group_order_cart')->where($where)->field("goods_id ,checked ,count")->find();
            if(!$goods_cart||$goods_cart['count']<$nums){
                //查询自提点是否关闭
                $pick_address = M('Village_group_pick_address')->where(array('header_uid'=>$this->leader_id))->find();
                if(!$pick_address||$pick_address['status']!=1){
                    returnCode(2, [], '自提点已关闭，请重新选择自提点！');
                }
            }

            //3、更新购物车
            foreach($check['data'] as $goods_id => $price){
                $price = sprintf('%.2f',round($price*100)/100);
                $has = M('Village_group_order_cart')->where(['goods_id'=> $goods_id, 'uid' => $this->_uid, 'start_id' => $this->leader_id])->find();
                $data = [
                    'goods_id' => $goods_id,
                    'uid' => $this->_uid,
                    'cart_cookie' => md5($this->_uid),
                    'productPrice' => $price,
                    'productParam' => serialize(['num' => $goods[$goods_id], 'good_price' =>$price]),
                    'start_id' => $this->leader_id,
                    'count' => $goods[$goods_id],
                    'checked' => $checked//是否选中
                ];
                if(!$has){
                    M('Village_group_order_cart')->data($data)->add();
                }
                else{
                    M('Village_group_order_cart')->where(['id' => $has['id']])->data($data)->save();
                }
            }
        }

        $where = [
            'uid' => $this->_uid,
            'start_id' => $this->leader_id,
            'count' => ['gt', 0],
            'single_buy' => 0 
        ];
        $goods = M('Village_group_order_cart')->where($where)->field("goods_id ,checked ,count")->select();
        if(empty($goods)){
            returnCode(0, ['all_money'=>0, 'all_nums'=>0, 'select_nums'=>0 ]);
        }
        $goods_count = array_column($goods, 'count', 'goods_id');
        $goods_check = array_column($goods, 'checked', 'goods_id');

        $data = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, -1, 0, ['goods.goods_id'=>['in', array_column($goods, 'goods_id')]]);
        $output = [
            'all_money' => 0,
            'all_nums' => 0,
            'select_nums' => 0
        ];
        foreach ($data as $key => $value) {
            if($goods_check[$value['goods_id']] == 1){
                $output['all_money'] += ($value['price']+$value['packing_fee'])*$goods_count[$value['goods_id']];
                $output['select_nums'] += $goods_count[$value['goods_id']];
            }
            $output['all_nums'] += $goods_count[$value['goods_id']];
        }
        $output['all_money'] = getFormatNumber($output['all_money']);

        returnCode(0, $output, $del ? '移除成功' : '更新成功');
    }

    //直接购买修改数量
    public function save_nums()
    {
        //1、参数 单商品 对应修改数量
        $goods_id = I("goods_id");
        $nums = I("nums");
        $order_id = I("order_id");

  
        $goods = [$goods_id=>$nums];

        //2、检查单个商品（过期、下架、活动前夕、库存不足、得到价格）
        $check = D("Village_group_goods")->check_goods($this->leader_id, $goods, $this->_uid);
         
        if(!$check['status']){
            returnCode(2 ,[] , $check['msg']);
        }

        if(!M('Village_group_order')->where(['order_id'=>$order_id])->find()){
            returnCode(2 ,[] , '订单不存在');
        }

        //更新数量
        M('Village_group_order_detail')->where(['order_id'=>$order_id])->data(['num'=>$nums])->save();
 
        returnCode(0, [], '更新成功');
    }



    //购物车列表
    public function cart_list(){
        $where = [
            'uid' => $this->_uid,
            'start_id' => $this->leader_id,
            'count' => ['gt', 0],
            'single_buy' => 0 
        ];
        $goods = M('Village_group_order_cart')->where($where)->field("goods_id, checked, count")->select();
        if(empty($goods)){
            returnCode(0, []);
        }
        $goods_count = array_column($goods, 'count', 'goods_id');
        $goods_check = array_column($goods, 'checked', 'goods_id');

        $data = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, -1, 0, ['goods.goods_id'=>['in', array_column($goods, 'goods_id')]], true);
        $output = [
            'list' => [],
            'all_money' => 0,
            'all_nums' => 0,
            'select_nums' => 0,//选中部分的数量
        ];
        foreach ($data as $key => $value) {
            $data[$key]['cart_nums'] = $goods_count[$value['goods_id']];
            $data[$key]['checked'] = $goods_check[$value['goods_id']];
            $data[$key]['packing_fee'] = $value['packing_fee'] > 0 ? ($value['packing_fee'] == intval($value['packing_fee']) ? intval($value['packing_fee']) : $value['packing_fee']) : 0;
            $goods_activity_info = D("Village_group_activity_goods")->getActivityInfo($value['goods_id'], 2);
            if($goods_activity_info && $goods_activity_info['start_time'] < time() && $goods_activity_info['end_time'] > time()){
                if($goods_activity_info['buy_price'] < $value['price']){
                    $data[$key]['old_price'] = $data[$key]['price'];
                    $data[$key]['price'] = $value['price'] = $goods_activity_info['buy_price'];
                }
            }
            $output['all_nums'] += $goods_count[$value['goods_id']];
            if($goods_check[$value['goods_id']] == 1){
                $output['all_money'] += ($value['price']+$value['packing_fee'])*$goods_count[$value['goods_id']];
                $output['select_nums'] += $goods_count[$value['goods_id']];
            }
        }
        $output['list'] = $data;
        $output['all_money'] = getFormatNumber($output['all_money']);

        returnCode(0, $output);
    }

    //商品详情接口
    public function goods_detail(){
        $goods_id = I("goods_id");
        if(empty($goods_id)){
            returnCode(1, [], 'goods_id 参数错误！');
        } 
        $goods_detail = D('Village_group_goods')->get_goods($this->leader_id, 0, 0, 1, 1, ['goods.goods_id'=>['eq', $goods_id]], true, false)[0];
        if(empty($goods_detail)){
            returnCode(4, [], '该商品不存在!');
        }

        $group_image_class = new group_image();
        $goods_detail['all_pic'] = $group_image_class->get_allImage_by_path($goods_detail['pic_list']);
        /*替换资源地址*/
        $goods_detail['content'] = replace_file_domain_content(htmlspecialchars_decode($goods_detail['content']));
		$goods_detail['content'] = preg_replace("/<img.*?src=(\"|\')(.*?)(\"|\').*?\/\>/is",'<img style="max-width:100%;vertical-align:top;" src="$2" />', $goods_detail['content']);

        //打包费(处理一下小数点)
        $goods_detail['packing_fee'] = $goods_detail['packing_fee'] > 0 ? ($goods_detail['packing_fee'] == intval($goods_detail['packing_fee']) ? intval($goods_detail['packing_fee']) : $goods_detail['packing_fee']) : 0;
        //剩余
        empty($goods_detail['surplus']) && $goods_detail['surplus'] = $goods_detail['stock_num'] == -1 ? "无限库存" : ($goods_detail['stock_num']-$goods_detail['sell_count']-$goods_detail['locking_num'] < 0 ? 0 : $goods_detail['stock_num']-$goods_detail['sell_count']-$goods_detail['locking_num']);
        //送达标题
        $goods_detail['service_title'] = "";
        if($goods_detail['arrive_type'] == '1'){//当日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time']){
                $goods_detail['service_title'] = date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
            else{
                $goods_detail['service_title'] = '明天'.date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，明天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
        }
        if($goods_detail['arrive_type'] == '2'){//次日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time']){
                $goods_detail['service_title'] = date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，明天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
            else{
                $goods_detail['service_title'] = '明天'.date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，后天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
        }
        if($goods_detail['arrive_type'] == '3'){//隔日达
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time']){
                $goods_detail['service_title'] = date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，后天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
            else{
                $goods_detail['service_title'] = '明天'.date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，大后天".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
        }
        if($goods_detail['arrive_type'] == '4'){//自定义
            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time']){
                $goods_detail['service_title'] = date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，".date("m月d日",strtotime("+".$goods_detail['arrive_days']." days"))." ".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
            else{
                $goods_detail['service_title'] = '明天'.date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_cutting_time'])."前下单，".date("m月d日",strtotime("+".($goods_detail['arrive_days']+1)." days"))." ".date("H:i", strtotime(date("Y-m-d 00:00:00"))+$goods_detail['arrive_time'])."送达";
            }
        }
        if($goods_detail['arrive_type'] == '5'){//固定日期
            if(time() < $goods_detail['arrive_cutting_datetime']){
                $goods_detail['service_title'] = date("m月d日 H点i分", $goods_detail['arrive_cutting_datetime'])."前下单，"." ".date("m月d日", $goods_detail['arrive_datetime'])."送达";
            }
        }

        //当前商品的活动
        $activityGoodsModel = D("Village_group_activity_goods");
        $goods_detail['count_down_title'] = "";
        $goods_detail['count_down'] = 0;//活动结束倒计时
        $goods_detail['no_start_time'] = '';//距离开始时使用
        $goods_detail['advance_save'] = 0;//是否预售
        $goods_detail['buy_status'] = 1;//是否可以购买 0=不可以 1=可以
        $goods_detail['buy_status_notips'] = '';//不可以买的原因
        $goods_activity_info = $activityGoodsModel->getActivityInfo($goods_id, 2);
        $activity_price = -1;
        if($goods_detail['start_time'] > time()){
            $goods_detail['count_down_title'] = "距上架时间";//即将开始的商品有效期
            $goods_detail['advance_save'] = 1;//是预售
            $goods_detail['count_down'] = $goods_detail['start_time'] - time();
            $goods_detail['no_start_time'] = "商品未上架";
            $goods_detail['buy_status'] = 0;
            $goods_detail['buy_status_notips'] = $goods_detail['no_start_time'];
        }
        elseif($goods_activity_info){
            $goods_detail['count_down_title'] = "距活动结束";//正在开始的活动，距离结束
            $goods_detail['count_down'] = $goods_activity_info['end_time'] - time();
            if($goods_detail['count_down'] > 0){
                $activity_price = $goods_activity_info['buy_price'];
            }
        }
        elseif($nostart = $activityGoodsModel->getNoStartActivityInfo($goods_id, 2)){
            $goods_detail['count_down_title'] = "距活动开始";//即将开始的活动
            $goods_detail['count_down'] = $nostart['start_time'] - time();
            $goods_detail['no_start_time'] = date('H:i',$nostart['start_time'])."开始";
            $goods_detail['buy_status'] = 0;
            $goods_detail['buy_status_notips'] = $goods_detail['no_start_time'];
        }
        elseif($goods_detail['end_time']){
            $goods_detail['count_down_title'] = "距结束";//当前商品的有效期，距离结束
            $goods_detail['count_down'] = $goods_detail['end_time'] - time();   
        }
        //当前商品是固定结束日期时，以截单时间判定能否加入购物车
        if($goods_detail['arrive_type'] == 5 && $goods_detail['arrive_cutting_datetime'] > 0 && time() > $goods_detail['arrive_cutting_datetime']){
            $goods_detail['count_down_title'] = "";
            $goods_detail['count_down'] = 0;//活动结束倒计时
            $goods_detail['no_start_time'] = '已过下单时间，关注下次拼团';//距离开始时使用
            if($goods_detail['buy_status'] == 1){
                $goods_detail['buy_status'] = 0;
                $goods_detail['buy_status_notips'] = '已截单';
            }
        }
        if($goods_detail['buy_status'] == 1 && $goods_detail['stock_status'] == 1){
            $goods_detail['buy_status'] = 0;
            $goods_detail['buy_status_notips'] = '已售罄';
        }
        if($goods_detail['is_new_user'] == 1){
            $w = [
                'o.status' => ['in', [1,2,3,5,6,7,8,13]],
                'o.uid' => $this->_uid
            ];
            $newbuy = M("Village_group_order_detail")->alias("d")->join("left join ".C("DB_PREFIX")."village_group_order as o on o.order_id=d.order_id")->where($w)->find();
            if($newbuy){
                $goods_detail['buy_status'] = 0;
                $goods_detail['buy_status_notips'] = '新人特惠购';
                $goods_detail['limit_buy'] = $goods_detail['new_user_limit_buy'];
            }
        }
        if($goods_detail['status'] == 2){
            $goods_detail['buy_status'] = 0;
            $goods_detail['buy_status_notips'] = '已下架';
        }

        //当前商品提供者
        $goods_detail['providers'] = $this->config['village_group_productor_info'];
        if($goods_detail['own_sell'] == '1'){
            $goods_detail['providers'] = $this->config['village_group_productor_info'];
        }
        elseif(!empty($goods_detail['mer_id'])){
            $merchant = M("Merchant")->where(['mer_id'=>$goods_detail['mer_id']])->field('name')->find();
            if($merchant){
                $goods_detail['providers'] = $merchant['name'];       
            }
        }

        //分享海报{pigcms{$config.site_url}/village_group/goods_{pigcms{$_GET['leader_id']}_{pigcms{$_GET['goods_id']}
        $goods_detail['share_poster'] = $this->config['site_url'].'/village_group/goods_'.$this->leader_id.'_'.$goods_id;

        $output = [
            'goods_detail' => $goods_detail
        ];
        //团长信息
        $headerModel = D("Village_group_header");
        $output['leader_info'] = $headerModel->getInfo($this->leader_id, 'h.name,h.phone,u.avatar,a.name as village_name,a.province_id,a.city_id,a.area_id,a.pick_addr,u.uid,h.money', false);

        //团长加价
        $output['add_price'] = [
            'is_add_price' => 0,//是否可以加价 0=不可以 1=可以
            'min_price' => 0,//加价区间起始价格
            'max_price' => 0,//加价区间结束价格
            'old_add_price' => 0,//之前的加价金额
        ];
        if($check_add_price = $headerModel->check_add_price($this->leader_id, $goods_id, $this->_uid)){//后台设置了加价才可以
            $output['add_price'] = [
                'is_add_price' => 1,//是否可以加价 0=不可以 1=可以
                'min_price' => $check_add_price['min_price'],//加价区间起始价格
                'max_price' => $check_add_price['max_price'],//加价区间结束价格
                'old_add_price' => $check_add_price['old_price'],//之前的加价金额
            ];
        }
        //leader_money = 佣金
        $cleader = $headerModel->getInfo($this->leader_id, 'u.uid');
        $output['goods_detail']['leader_money'] = $cleader['uid'] == $this->_uid ? ($goods_detail['leader_money'] > 0 ? sprintf("%.2f",$goods_detail['leader_money']*($goods_detail['price']-$goods_detail['increase_price'])/100) : ($output['leader_info']['money'] > 0 ? sprintf("%.2f",$output['leader_info']['money']*($goods_detail['price']-$goods_detail['increase_price'])/100) : '')) : '';

        // 获取周期购的信息
        $output['cycle_info'] = array();
        if ($goods_detail && $goods_detail['village_group_type'] == 1) {
            $village_group_cycle = M('Village_group_cycle')->where(array('goods_id'=>$goods_detail['goods_id']))->find();
            // 处理日期问题
            $d_village_group = D('Village_group_goods');
            if ($village_group_cycle['delivery_date']) {
                $village_group_cycle['delivery_date_arr'] = explode(',',$village_group_cycle['delivery_date']);
            }

            $village_group_cycle['date_msg'] = $d_village_group->choose_date['date_msg'];

            if ($village_group_cycle['cycle_type']==1) {
                $village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods_detail['goods_id'],0);
            } elseif ($village_group_cycle['cycle_type']==2) {
                if ($village_group_cycle['is_all_date']==1) {
                    $village_group_cycle['delivery_date_arr'] = $d_village_group->choose_date['week'];
                    $village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods_detail['goods_id'],1);
                } else {
                    $week = min($village_group_cycle['delivery_date_arr']);
                    $week = $week>0?$week:1;
                    $week_info = $d_village_group->choose_date['week'];
                    $new_delivery_date_arr = array();
                    foreach ($village_group_cycle['delivery_date_arr'] as $key=>$val) {
                        if ($week_info[$val]) {
                            $new_delivery_date_arr[$val] = $week_info[$val];
                        }
                    }
                    if ($new_delivery_date_arr) {
                        $village_group_cycle['delivery_date_arr'] = $new_delivery_date_arr;
                    }
                    $village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods_detail['goods_id'],$week);
                }
            } elseif ($village_group_cycle['cycle_type']==3) {
                if ($village_group_cycle['is_all_date']==1) { 
                    $village_group_cycle['delivery_date_arr'] = $d_village_group->choose_date['date'];
                    $village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods_detail['goods_id'],1);
                } else {
                    $new_delivery_date_arr = array();
                    $day = min($village_group_cycle['delivery_date_arr']);
                    $day = $day>0?$day:1;
                    $village_group_cycle['first_delivery_date'] = $d_village_group->lately_cycle_time($goods_detail['goods_id'],$day);
                }
            }

            $info[0] = array('name'=>'配送频次','info'=>'');
            $info[1] = array('name'=>'配送顺延限制','info'=>'');
            $info[2] = array('name'=>'最近配送日期','info'=>'');
            if ($village_group_cycle['cycle_type']==1) {
                $info[0]['info'] = '每日一期';
            }elseif ($village_group_cycle['cycle_type']==2) {
                $info[0]['info'] = '每周一期';
            }else{
                $info[0]['info'] = '每月一期';
            }
            $info[0]['info'] .= '共'.$village_group_cycle['delivery_periods'].'期';
            if ($village_group_cycle['is_limit_delay'] != 1) {
                $info[1]['info'] = '不限制次数';
            }else{
                $info[1]['info'] = '限制'.$village_group_cycle['limit_delay_num'].'次';
            }
            if ($village_group_cycle['limit_days'] > 0) {
               $info[2]['info'] = '需在最近配送日期前'.$village_group_cycle['limit_days'].'天，且'.$village_group_cycle['limit_time'].'点前下单支付';
            }else{
               $info[2]['info'] = '需在最近配送日期当天，且'.$village_group_cycle['limit_time'].'点前下单支付';
            }

            $village_group_cycle['desc'] = $info;
            $output['cycle_info'] = $village_group_cycle;
        }


        //获取商品评价
        $reply_list = M('Village_group_reply')->alias("r")->join("left join ".C("DB_PREFIX")."user as u on r.uid=u.uid")->field("u.avatar,u.nickname,r.level,r.add_time")->where(array('goods_id'=>$goods_id))->select(); 
        $reply_all = count($reply_list);
        $reply_level_1 = $reply_level_2 = $reply_level_3 = 0;
        foreach ($reply_list as $key => $value) {
            if($value['level'] == 1){
                $reply_level_1++;
            }elseif($value['level'] == 2){
                $reply_level_2++;
            }elseif($value['level'] == 3){
                $reply_level_3++;
            }
            if (!$value['avatar']) {
                $reply_list[$key]['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
            } else {
                if(stripos($value['avatar'],'/upload/') === 0){
                    $reply_list[$key]['avatar'] = file_domain().$value['avatar'];
                }
                else{
                    $reply_list[$key]['avatar'] = replace_file_domain($value['avatar']);
                }
            }
            $reply_list[$key]['add_time'] = $value['add_time'] ? date('Y-m-d', $value['add_time']) : "";
            if (C('config.village_group_hide_detail')) {
                $reply_list[$key]['nickname'] = str_replace_name($value['nickname']);
            }
        }
        $output['reply']['count'] = $reply_all;
        // 满意度
        $output['reply']['satisfaction'] = $reply_all ? round(($reply_level_1*60 + $reply_level_2*80 + $reply_level_3*100)/($reply_all*100),2)*100 : 0;
        $output['reply']['reply_list'] = array_slice($reply_list, 0, 6);

        //猜你喜欢(同分类下10条商品)
        $output['guess_you_like'] = D("Village_group_goods")->get_goods($this->leader_id, $goods_detail['cat_fid'], 0, $page = 1, $pagesize = 10, ['goods.goods_id'=>['neq',$goods_id], 'goods.is_new_user'=>0]);


        //当前正在进行的抢购活动倒计时
        $output['limit_buy_activity'] = [];
        $activityModel = D("Village_group_activity");
        $now_activity = $activityModel->getNowActivity(2);
        if($now_activity){
            $output['limit_buy_activity']['title'] = date("H:i", $now_activity['start_time'])."秒杀抢购中";
            $output['limit_buy_activity']['count_down'] = $now_activity['end_time'] - time();
        }
        $output['buy_type'] = '0';//1=不能直接购买 2=只能直接购买 0=随意
        if($this->config['village_group_express_open'] == 1){
            $output['buy_type'] = '1';
            if($goods_activity_info){
                $output['buy_type'] = '2';
            }
            elseif($activityGoodsModel->getActivityInfo($goods_id, 1)){
                $output['buy_type'] = '2';
            }
        }

        if($activity_price > -1 && $output['goods_detail']['price'] > $activity_price){
            $output['goods_detail']['old_price'] = $output['goods_detail']['price'];
            $output['goods_detail']['price'] = $activity_price;
        }
        $output['goods_detail']['price'] = floatval(getFormatNumber($output['goods_detail']['price']));
        $output['goods_detail']['old_price'] = floatval(getFormatNumber($output['goods_detail']['old_price']));

        //增加浏览量
        D('Village_group_goods')->where('goods_id = ' . $goods_id)->data(['browse_num'=> $goods_detail['browse_num']+1])->save();

        //消费一元获得积分
        $buy_can_get_score = PD('Goods_score')->getGoodsScore($output['goods_detail']['price'],'village_group',false,$output['goods_detail']['score_percent']);
        $output['goods_detail']['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
        $output['goods_detail']['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';

        returnCode(0, $output);
    }

    //团长加价
    public function goods_add_price(){
        $goods_id = I("goods_id");
        $price = I("price");
        if(empty($goods_id)) {
            returnCode(1, [], 'goods_id 参数错误！');
        }
        $headerModel = D("Village_group_header");

        $check_add_price = $headerModel->check_add_price($this->leader_id, $goods_id, $this->_uid);
        if(empty($check_add_price)){
            returnCode(1, [], '当前用户不可以设置加价！');   
        }
        if($price == '0'){
            if(M("Village_group_goods_increase_price")->where(['goods_id'=>$goods_id,'header_uid'=>$this->leader_id])->delete()){
                returnCode(0, [], '操作成功！');
            }
            else{
                returnCode(1, [], '操作失败！');
            }
        }
        if($price > $check_add_price['max_price'] || $price < $check_add_price['min_price']){
            returnCode(2, [], '超出加价区间范围，设置失败！');
        }
        if($check_add_price['old_id']){
            $res = M('Village_group_goods_increase_price')->where(array('goods_id'=>$goods_id,'header_uid'=>$this->leader_id))->save(['increase_price'=> $price, 'last_time' => time()]);
        }
        else{
            $data = [
                'increase_price' => $price,
                'goods_id' => $goods_id,
                'time' => time(),
                'header_uid' => $this->leader_id,
                'last_time' => time(),
            ];
            $res = M('Village_group_goods_increase_price')->data($data)->add();   
        }
        if ($res) {
            returnCode(0, [], '设置成功！');
        }else{
            returnCode(2, [], '设置失败！');
        }
    }

    //评价列表
    public function comment_list(){
        $page = I("page") ? I("page") : 1;
        $pagesize = I("pagesize") ? I("pagesize") : 20;
        $goods_id = I("goods_id");
        if(empty($goods_id)){
            returnCode(1, [], 'goods_id 参数错误！');
        } 
        $limit = $pagesize*($page-1).",".$pagesize;
        $reply_list = M('Village_group_reply')->alias("r")->join("left join ".C("DB_PREFIX")."user as u on r.uid=u.uid")->field("u.avatar,u.nickname,r.level,r.add_time")->where(array('goods_id'=>$goods_id))->limit($limit)->select(); 
        foreach ($reply_list as $key => $value) {
            if (!$value['avatar']) {
                $reply_list[$key]['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
            } else {
                if(stripos($value['avatar'],'/upload/') === 0){
                    $reply_list[$key]['avatar'] = file_domain().$value['avatar'];
                }
                else{
                    $reply_list[$key]['avatar'] = replace_file_domain($value['avatar']);
                }
            }
            $reply_list[$key]['add_time'] = $value['add_time'] ? date('Y-m-d', $value['add_time']) : "";
            if (C('config.village_group_hide_detail')) {
                $reply_list[$key]['nickname'] = str_replace_name($value['nickname']);
            }
        }

        returnCode(0, $reply_list);
    }

    //超值热卖
    public function hot_goods(){
        $village_group_goodsModel = D("Village_group_goods");
        $data = $village_group_goodsModel->getHotRecom($this->leader_id, 20);
        returnCode(0, $data);
    }

    //我的
    public function my(){
        $output = [];
        $output['leader_info'] = D("Village_group_header")->getInfo(0, 'h.uid,h.id,h.name,h.phone,u.avatar,a.name as village_name,a.province_id,a.city_id,a.area_id',false, $this->_uid);
        $output['leader_info']['level'] = "";
        if ($output['leader_info']['province_id']>0 || $output['leader_info']['city_id']>0 || $output['leader_info']['area_id']>0){
            $sale_price = PD('Village_group_order')->sum_header_sale_num($output['leader_info']['id']);
            // 如果存在
            $province_id = $output['leader_info']['province_id'];
            $city_id = $output['leader_info']['city_id'];
            $area_id = $output['leader_info']['area_id'];
            $area_level = array();
            if ($area_id > 0 && $sale_price>0) {
                $area_level = M('Village_group_leader_area_level')
                    ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                    ->find();
                if (!$area_level && $sale_price>0) {
                    $area_level = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price)))
                        ->order('level DESC')
                        ->find();
                    $output['leader_info']['level'] = $area_level ? $area_level['lname'] : "";
                }
            }
            if ($city_id > 0 && $sale_price>0 && empty($area_level)) {
                $area_level = M('Village_group_leader_area_level')
                    ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                    ->find();
                if (!$area_level && $sale_price>0) {
                    $area_level = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price)))
                        ->order('level DESC')
                        ->find();
                    $output['leader_info']['level'] = $area_level ? $area_level['lname'] : "";
                }
            }
            if ($province_id > 0 && $sale_price>0 && empty($area_level)) {
                $area_level = M('Village_group_leader_area_level')
                    ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                    ->find();
                if (!$area_level && $sale_price>0) {
                    $area_level = M('Village_group_leader_area_level')
                        ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price)))
                        ->order('level DESC')
                        ->find();
                    $output['leader_info']['level'] = $area_level ? $area_level['lname'] : "";
                }
            }
        }
        
        //  平台优惠券
        $coupon_list = PD('System_coupon')->get_user_coupon_list($this->_uid,'',1);
        $output['coupon_number']  =   count($coupon_list);

        $output['code'] = PD('Village_group')->get_code($this->_uid);
        $output['is_self'] = D("Village_group_header")->checkIsHeader($this->_uid);
        $output['code_img'] = U("consumer_code",['uid'=>$this->_uid]);

        $now_user = PD('User')->get_user($this->_uid);
        $level = M('User_level')->getField('level,lname,icon');
        $now_user['lname'] = $level[$now_user['level']] ? $level[$now_user['level']]['lname'] : "";
        if (!$now_user['avatar']) {
            $now_user['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
        } else {
            if(stripos($now_user['avatar'],'/upload/') === 0){
                $now_user['avatar'] = file_domain().$now_user['avatar'];
            }
            else{
                $now_user['avatar'] = replace_file_domain($now_user['avatar']);
            }
        }
        $output['open_im'] = $this->config['village_group_index_kf'];//是否开启平台客服
        $output['open_bss'] = $this->config['village_group_index_bbs'];//是否开启论坛
        $output['kefu_url'] = $this->config['site_url'].'/wap.php?c=My&a=concact_kefu&mer_id='.$this->config['kefu_mer_id'];
        $output['site_phone'] = $this->config['site_phone'];
        $output['user'] = $now_user;

        //签到
        $recently_sign = M('User_sign')->where(array('uid'=>$this->_uid))->order('id DESC')->find();
        $output['today_sign'] = 0;//未签到
        if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
            $output['today_sign'] = 1;//已签到
        }
        if($this->config['sign_get_score'] == 0){
            $output['today_sign'] = 2;//不显示签到
        }
        returnCode(0, $output);
    }

    //签到接口
    public function sign(){
        $return = PD('User')->sign_in($this->_uid);
        if($return['error_code'] > 0){
            if($return['error_code'] == 2){
                returnCode(3, ['redirect' => $this->config['site_url'] .'/wap.php?g=Wap&c=My&a=bind_user', 'msg'=>$return['msg']]);    
            }
            returnCode(2, [], $return['msg']);
        }
        returnCode(0, [], '签到成功！');
    }

    //提货码
    public function consumer_code(){
        $uid = I("uid");
        import('@.ORG.phpqrcode');
        $code = PD('Village_group')->get_code($uid);
        QRcode::png($code,false,2,8,2);
    }

    //购物车去结算
    public function save_order_old(){
        $goods_id = I("goods_id");
        $nums = I("nums");
        $buy_type = '0';//0=正常  1=购物车结算 2=直接购买
        if($goods_id && $nums){//直接购买某个商品，不走购物车
            $buy_type = $this->config['village_group_express_open'] == 1 ? '2' : '0';//如开启快递，则直接购买为活动
            $goods = [
                [
                    'count' => $nums,
                    'goods_id' => $goods_id
                ]
            ];
        }
        else{
            $buy_type = $this->config['village_group_express_open'] == 1 ? '1' : '0';//如开启快递，则直接购买为活动
            //1、检查购物车
            $where = [
                'uid' => $this->_uid,
                'start_id' => $this->leader_id,
                'count' => ['gt', 0],
                'checked' => 1,//选中的商品
                'single_buy' => 0 
            ];
            $goods = M('Village_group_order_cart')->where($where)->field("goods_id, count")->select();
            if(empty($goods)){
                returnCode(2, [], "没有可以购买的商品");
            }
        }
        $goods_count = array_column($goods, 'count', 'goods_id');
        $check = D("Village_group_goods")->check_goods($this->leader_id, $goods_count, $this->_uid);
        if(!$check['status']){
            returnCode(2 ,[] , $check['msg']);
        }
        $data = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, -1, 0, ['goods.goods_id'=>['in', array_column($goods, 'goods_id')]]);

        $all_money = $all_nums = 0;
        foreach ($data as $key => $value) {
            $all_money += ($value['price']+$value['packing_fee'])*$goods_count[$value['goods_id']];
            $all_nums += $goods_count[$value['goods_id']];
        }
        if($this->config['village_group_express_open'] == '1' && $this->config['village_group_start_price'] > 0 && $all_money < $this->config['village_group_start_price'] && !($goods_id && $nums)){
            returnCode(2, [], "小主再挑挑？满".$this->config['village_group_start_price']."免费配送哦！");
        }

        $insert['real_orderid']       = (8 > strlen($this->_uid)) ? date('Ymdhis') . substr(microtime(), 2, 8 - strlen($this->_uid)) . $this->_uid : date('Ymdhis') . $this->_uid;
        $insert['uid']                = $this->_uid;
        $insert['start_uid']          = $this->leader_id;
        $insert['total_money']        = $all_money;
        $insert['comment']            = '';
        $insert['add_time']           = time();
        $insert['status']             = 0;
        $insert['last_time']          = time();
        $insert['code']               = rand(100000,999999);
        $insert['buy_type']           = $buy_type;

        fdump($data[0],'village_goods');
        // 处理周期购信息
        $village_group_type = 0;
        if ($goods_id && $_POST['single_buy']==1) {
            $goods = $data[0];
            $village_group_type = $goods['village_group_type'];
            if ($goods && $goods['village_group_type']==1) {
                $village_group_type = 1;
                $cycle = M('Village_group_cycle')->where(array('goods_id'=>$goods['goods_id']))->find();
                // 配送类型 1周期购
                $insert['village_group_type'] = 1;
                // 周期类型 1 每日 2 每周 3 每月
                $insert['cycle_type'] = $cycle['cycle_type'];
                //配送日
                if ($cycle['cycle_type']==1) {
                    $_POST['time']=1;
                }
                if ($cycle['delivery_date'] && !$_POST['time']) {
                    $delivery_date_arr = explode(',',$cycle['delivery_date']);
                    if (1==count($delivery_date_arr)) {
                        $_POST['time'] = reset($delivery_date_arr);
                    } else {
                        returnCode(2, [], '请选择周期送时间');
                    }
                }
                $insert['delivery_date']  = $_POST['time'];
                $insert['is_limit_delay']  = $cycle['is_limit_delay'];
                $insert['limit_delay_num']  = $cycle['limit_delay_num'];
                $insert['delivery_periods']  = $cycle['delivery_periods'];
                $insert['is_all_date']  = $cycle['is_all_date'];
                $insert['limit_days']  = $cycle['limit_days'];
                $insert['limit_time']  = $cycle['limit_time'];
                $insert['delivery_date_info']  = $cycle['delivery_date'];
            }
        }
        $order_id = M('Village_group_order')->add($insert);

        if( $order_id){
            $info = M('Village_group_order')->where(['order_id'=>$order_id])->find();
            $_num = 0;
            foreach ($data as $key => $value) {
                $_num += $goods_count[$value['goods_id']];
                $detail['order_id'] = $order_id;
                $detail['goods_id'] = $value['goods_id'];
                $detail['mer_id'] =  $value['mer_id'];
                $detail['mer_group_id'] = $value['group_id'];
                $detail['name'] = $value['name'];
                $detail['purchase_price']  = $value['purchase_price'];
                $detail['price'] = $value['_price_old'];
                $detail['num']  = $goods_count[$value['goods_id']];
                $detail['create_time'] = time();
                $detail['arrive_cutting_time'] = 0;//截单时间
                $detail['arrive_time'] = 0;//到达时间
                if($value['arrive_type'] == '1'){//当日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time'];
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+1*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '2'){//次日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+1*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+2*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '3'){//隔日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+2*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+3*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '4'){//自定义
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+$value['arrive_days']*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+($value['arrive_days']+1)*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '5'){//固定日期
                    if(time() < $value['arrive_cutting_datetime']){
                        $detail['arrive_time'] = $value['arrive_datetime'];
                        $detail['arrive_cutting_time'] = $value['arrive_cutting_datetime'];
                    }
                }
                $detail['pay_price']  = $value['price'];
                $detail['old_price']  = $value['old_price'];
                $detail['is_merchant_send']  = $value['is_merchant_send'];
                $detail['leader_money']  = $value['leader_money']>0?$value['leader_money']:0;
                $detail['village_group_type']  = $village_group_type;
                $detail['increase_price'] = $value['increase_price'];
                $detail['packing_fee'] = $value['packing_fee'];

                M('Village_group_order_detail')->add($detail);
            }
            $info['num'] = $_num;

            //增加搜索关键词 商品名称
            $goods_name = [];

            foreach ($data as $v) {
                $goods_name[] = $v['name'];
            }

            $goods_name_str = rtrim(implode(',', $goods_name),',');
            $info['keywords'] = $goods_name_str;

            PD('System_order')->add_order('village_group',$info,$order_id);

            $pay_order_param = array(
                'business_type' => 'village_group',
                'business_id' => $order_id,
                'order_name' => $this->config['village_group_alias'].'订单',
                'uid' => $this->_uid,
                'total_money' => $all_money,
                'wx_cheap' => 0,
            );

            $now_user = PD('User')->get_user($this->_uid);
            $param['order_id'] = $order_id;
            $param['status'] = 0;
            $param['from_type'] = 0;
            $param['note'] = '用户下单';
            $param['name'] =$now_user['nickname'];
            $param['phone'] =$now_user['phone'];
            PD('Village_group')->add_log($param);

            $plat_order_result = PD('Plat_order')->add_order($pay_order_param);
            returnCode(0, ['order_id'=>$order_id]);
        }else{
            returnCode(2, [], '参团失败');
        }
    }
    
    //确认订单
    public function confirm_order_old(){
        $_GET['order_id'] = I("order_id");
        $_GET['unsys_coupon'] = I("unsys_coupon");//是否使用优惠券 1=不使用
        $_GET['sysc_id'] = I("sysc_id");//优惠券ID 
        $_GET['adress_id'] = I("adress_id");//地址ID
        $_GET['use_score'] = isset($_POST["use_score"]) ? $_POST["use_score"] : 1;//是否使用积分
        $_GET['change_leader_id'] = intval(I("change_leader_id"));
        if($_GET['change_leader_id'] > 0){//如果有重新选择团长，则修改当前订单的团长
            $this->leader_id = $_GET['change_leader_id'];
            M("Village_group_order")->where("order_id=".$_GET['order_id'])->data(['start_uid'=>$this->leader_id])->save();
        }

        $output = [
            'dates' => [],
//            'user_adress' => [],
            'deliver_info' => [],
            'first_fee' => '',//配送费
            'leader_info' => [],
            'order' => [],
            'score' => []
        ];//最终输出结果

        //以下是原来的老代码
        $order  = PD('Village_group_order')->order_detail($_GET['order_id'],$this->leader_id);
        $header = PD('Village_group')->get_header_info(0,$order['start_uid'],true);
        $header  =$header['msg'];

        //取活动价
        $activity_goods_ids = array_column($order['goods'], 'goods_id');
        $activity_goods = D("Village_group_activity_goods")->getActivityInfo($activity_goods_ids, 2);
        $activity_goods_price = array_column($activity_goods, 'buy_price', 'goods_id');

        //用户信息
        $now_user = PD('User')->get_user($this->_uid);

        if($order['status']==0 && $order['add_time']+1800<time()){
            M('Village_group_order')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
            D("System_order")->save_order_status('village_group', $_GET['order_id'], 5);
        }
        $order['pay_type_txt'] = PD('Pay')->get_pay_name($order['pay_type'],1,1);

        $type_ = $_GET['type'] ? $_GET['type'] : 'village_group';
        // 优惠的金额
        $discount_money = 0;
        // 重新计算原来金额
        $total_money_ = 0;
        // 等级优惠后金额
        $total_money = 0;
        // 优惠详情
        $discount_detail = array();
        if ($order['order_detail']) {
            // 获取平台用户等级设置
            $user_= M('User')->where(array('uid'=>$this->_uid))->find();
            $m_village_group_order_detail = M('Village_group_order_detail');
            if(!empty($user_) && isset($user_['level'])){
                $level_off_system = M('User_level')->where(array('level' => $user_['level']))->find();
                // $this->assign('level_off_system',$level_off_system);
                $is_discount = 0;
                $goods_info = $order['goods'];
                foreach ($order['order_detail'] as &$val) {
                    $val['packing_fee'] = $val['packing_fee'] > 0 ? ($val['packing_fee'] == intval($val['packing_fee']) ? intval($val['packing_fee']) : $val['packing_fee']) : 0;
                    $val['arrive_title'] = "";//到达时间说明
                    if($goods_info[$val['goods_id']]){
                        if($goods_info[$val['goods_id']]['arrive_type'] == 1){//当日达
                            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_info[$val['goods_id']]['arrive_cutting_time']){
                                $val['arrive_title'] = "当日达";
                            }
                            else{
                                $val['arrive_title'] = date('m月d日', strtotime("+1 day"))."到达";   
                            }
                        }
                        elseif(in_array($goods_info[$val['goods_id']]['arrive_type'], [2,3,4])){
                            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_info[$val['goods_id']]['arrive_cutting_time']){
                                $val['arrive_title'] = date('m月d日', strtotime("+".$goods_info[$val['goods_id']]['arrive_days']." days"))."到达";
                            }
                            else{
                                $val['arrive_title'] = date('m月d日', strtotime("+".($goods_info[$val['goods_id']]['arrive_days']+1)." days"))."到达";
                            }
                        }
                        elseif($goods_info[$val['goods_id']]['arrive_type'] == 5){
                            $val['arrive_title'] = date('m月d日', $goods_info[$val['goods_id']]['arrive_datetime'])."到达";
                        }
                    }

                    // var_dump($val['increase_price']);
                    $area_price = $val['goods_bind_area']['price']>0?$val['goods_bind_area']['price']:0;
                    $level_info = array();
                    $level_info['order_id']=$order['order_id'];
                    // 商品如果自定义了等级优惠取商品的，没有定义默认取平台的
                    $leveloff=!empty($goods_info[$val['goods_id']]['leveloff']) ? unserialize($goods_info[$val['goods_id']]['leveloff']) : '';

                    if ($leveloff) {
                        $level_off=$leveloff[$user_['level']];
                    } elseif($level_off_system && $level_off_system['type'] > 0) {
                        $level_off_system['vv']=$level_off_system['boon'];
                        $level_off=$level_off_system;
                    }
//                    fdump($goods_info,'================',true);
//                    fdump($leveloff,'================',true);
                    fdump($level_off,'================',true);
                    $val['is_level'] = $level_off ? 1 : 0;
                    //区域售价加价信息
                    if ($val['goods_bind_area']) {
                        $val['price'] = $area_price;
                        if ($area_price>0 && $area_price != $val['area_price']) {
                            $m_village_group_order_detail->where(array('id' => $val['id']))->setField('area_price', $area_price);
                        }
                        if ($val['goods_bind_area']['is_add_price']==0) { // 没有打开加价
                            $val['increase_price'] = 0;
                        }else{
                            $val['increase_price'] = floatval($val['increase_price']);
                        }
                    }
                    elseif($activity_goods_price[$val['goods_id']]){//如果没有设置区域价，那就再看看有没有设置活动价
                        $val['price'] = $activity_goods_price[$val['goods_id']];
                    }
                    $_old_price = $val['price'];
                    $val['price'] += $val['packing_fee'];//加上打包费

                    if($level_off['type']==1 && $level_off['vv'] > 0 && $level_off['vv'] <= 100){
                        $level_discount = $val['price']*(1-$level_off['vv']/100);
                        M('Village_group_order_detail')->where(array('goods_id'=>$val['goods_id'],'order_id'=>$_GET['order_id']))->setField('discount_start_money',$level_discount);
                        if ($level_discount) {
//                          $is_discount = 1;
                            $val['is_discount'] = 1;
                        }
                        $level_discount = round_number($level_discount,2);
                        $val['discount_price'] = round_number($val['price']-$level_discount,2); // 优惠后的价格
                        // 团长加价参与优惠
                        if ($val['increase_price'] && $val['increase_price']>0) {
                            $level_discount_increase = $val['increase_price']*(1-$level_off['vv']/100);
                            $val['discount_price'] = $val['discount_price']  + round_number($val['increase_price'] - $level_discount_increase,2);
                            $level_info['level_discount_increase'] = round_number($level_discount_increase,2);
                            $level_info['increase_price'] = $val['increase_price'];
                        }
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2); // 原价计算总金额
                        $val['total_money'] = round_number($val['discount_price']*$val['num'],2);
                        $discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        // 处理折扣详情
                        $level_info['level_discount'] = round_number($level_discount,2);
                        $level_info['offstr']='折扣'.round_number($level_off['vv']/10,2).'折';
                        $level_info['type'] = 1;
                        $level_info['discount']=$level_off['vv'].'%';
                        $level_info['price']=$val['price'];
                        $level_info['discount_price'] = $val['discount_price'];
                        $level_info['num']=$val['num'];
                        $level_info['goods_id']=$val['goods_id'];
                        $level_info['area_price']=$area_price;
                        $discount_detail[] = $level_info;


                    }elseif($level_off['type']==2){
                        $level_discount = $level_off['vv'];
                        M('Village_group_order_detail')->where(array('goods_id'=>$val['goods_id'],'order_id'=>$_GET['order_id']))->setField('discount_start_money',$level_off['vv']);
//
                        if ($level_discount) {
//                          $is_discount = 1;
                            $val['is_discount'] = 1;
                        }
                        $val['discount_price'] = $val['price'] > $level_discount ? round_number($val['price']-$level_discount,2) : 0; // 优惠后的价格
                        // 团长加价参与优惠
                        if ($val['increase_price'] && $val['increase_price']>0) {
                            if ($val['discount_price']>=0) {
                                $level_discount_increase = 0;
                            } else {
                                $level_discount_increase = -($val['discount_price']);
                            }
                            $val['discount_price'] = $val['discount_price'] + $val['increase_price'];
                            $level_info['level_discount_increase'] = $level_discount_increase;
                            $level_info['increase_price'] = $val['increase_price'];
                        }
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
                        $val['total_money'] = round_number($val['discount_price']*$val['num'],2);
                        $discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        // 处理立减详情
                        $level_info['level_discount'] = round_number($level_discount,2);
                        $level_info['offstr']='每份立减'.$level_off['vv'].C('config.Currency_txt');
                        $level_info['type'] = 3;
                        $level_info['discount']=$level_off['vv'];
                        $level_info['price']=$val['price'];
                        $level_info['discount_price'] = $val['discount_price'];
                        $level_info['num']=$val['num'];
                        $level_info['goods_id']=$val['goods_id'];
                        $level_info['area_price']=$area_price;
                        $discount_detail[] = $level_info;


                    }else{
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        $val['discount_price'] = $val['price'];
                    }
                    $val['price'] = $_old_price;
                    $val['price'] = round_number($val['price']+$val['increase_price'],2);
                    $val['discount_price'] = round_number($val['discount_price'],2);
                }
                // $this->assign('is_discount',$is_discount);
            }

            $vip_discount_money = $discount_money = round_number($discount_money,2);
            if ($total_money_ != $order['total_money']) {
                $total_money = round_number($total_money_-$discount_money,2);
            } else {
                $total_money = round_number($order['total_money']-$discount_money,2);
            }

            //平台优惠券
            if ($this->is_app_browser) {
                $platform = 'app';
            } else if ($this->is_weixin_browser) {
                $platform = 'weixin';
            } else {
                $platform = 'wap';
            }
            if ($_GET['unsys_coupon']!=1) {
                if (empty($_GET['sysc_id'])) {

                    $old_money = 0;
                    if ($total_money != $order['total_money']) {
                        $old_money = $order['total_money'];
                        $order['total_money'] = $total_money;
                    }
                    $now_coupon = PD('System_coupon')->get_noworder_coupon_list($order, $type_, $now_user['phone'], $this->_uid, $platform);
                    $system_coupon = reset($now_coupon);

                    if ($old_money>0) {
                        $order['total_money'] = $old_money;
                    }
                } else {
                    $system_coupon = PD('System_coupon')->get_coupon_info($_GET['sysc_id']);
                    if($system_coupon['is_discount']==1){
                        $system_coupon['discount_money'] =round_number( $total_money * (100-$system_coupon['discount_value']*10)/100,2);
                    }else{
                        $system_coupon['discount_money'] = $system_coupon['discount'];
                    }
                }
            }
            if($system_coupon && $system_coupon['order_money']){
                if($system_coupon['is_discount'] == '1'){
                    $system_coupon['name'] = "满".getFormatNumber($system_coupon['order_money'])."打".getFormatNumber($system_coupon['discount_value'])."折";
                }
                else{
                    $system_coupon['name'] = "满".getFormatNumber($system_coupon['order_money'])."减".getFormatNumber($system_coupon['discount']);   
                }
            }

            if (!empty($system_coupon)) {
                $system_coupon['coupon_url_param'] = array('sysc_id' => $system_coupon['id'], 'order_id' => $order['order_id'], 'type' => $type_,'discount_type'=>'coupon');

                if($_GET['discount_type']=='coupon'){
                    $system_coupon['coupon_url_param']['discount_type'] = 'coupon';
                }else{
                    unset($system_coupon['coupon_url_param']['discount_type']);
                }

                $_SESSION['sysc_id'] = $system_coupon['id'];

                $system_coupon['discount'] = $system_coupon['discount_money'];
                if ($total_money >= $system_coupon['order_money']) {
                    $level_info = array();
                    if ($system_coupon['is_discount'] == 1) {
                        $level_info['type'] = 4;
                        $level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受折扣'.$system_coupon['discount_value'];
                        $level_info['discount']=$system_coupon['discount_value'];
                    } else {
                        $level_info['type'] = 2;
                        $level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受满减'.$system_coupon['discount'];
                        $level_info['discount']=$system_coupon['discount'];
                    }
                    if($system_coupon['discount_money'] > $total_money){
                        $system_coupon['discount_money'] = $total_money;
                        $total_money = 0;
                    }
                    else{
                        $total_money = $total_money - $system_coupon['discount_money'];
                    }
                    $total_money = round_number($total_money,2);
                    $discount_money = $discount_money + $system_coupon['discount_money'];
                    $discount_money = round_number($discount_money,2);
                    // 处理优惠券详情
                    $level_info['level_discount'] = round_number($system_coupon['discount_money'],2);
                    $level_info['money'] = $system_coupon['order_money'];
                    $level_info['order_id']=$order['order_id'];
                    $discount_detail[] = $level_info;
                }
                // $this->assign('system_coupon', $system_coupon);

                $order['total_money'] -= empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];

            } else {
                $system_coupon['coupon_url_param'] = array();
            }
            $coupon_url = $system_coupon['coupon_url_param'];
            if($_GET['unsys_coupon']&&!empty($coupon_url)){
                unset($_SESSION['sysc_id']);
                $coupon_url['unsys_coupon']=1;
            }
            // $this->assign('coupon_url', $coupon_url);
            // 写入订单数据
            $save_info = array();
            // 平台优惠券id
            if ($system_coupon && $order['coupon_id'] != $system_coupon['id']) {
                $save_info['coupon_id'] = $system_coupon['id']?$system_coupon['id']:0;
            } elseif (!$system_coupon && $order['coupon_id']>0) {
                $save_info['coupon_id'] = 0;
            }
            // 平台优惠券优惠金额
            if ($system_coupon && $order['coupon_price'] != $system_coupon['discount_money']) {
                $save_info['coupon_price'] = $system_coupon['discount_money']?$system_coupon['discount_money']:0;
            } elseif (!$system_coupon && $order['coupon_price']>0) {
                $save_info['coupon_price'] = 0;
            }
            // 用户等级
            if ($user_ && $order['user_level'] != $user_['level']) {
                $save_info['user_level'] = $user_['level'];
            }
            // 用户等级优惠金额
            if ($order['vip_discount_money'] != $vip_discount_money) {
                $save_info['vip_discount_money'] = $vip_discount_money;
            }
            // 优惠后总金额
            if ($order['discount_price'] != $total_money) {
                $save_info['discount_price'] = $total_money;
            }
            // 优惠详情
            if ($discount_detail) {
                $discount_detail = serialize($discount_detail);
                if ($order['discount_detail'] != $discount_detail) {
                    $save_info['discount_detail'] = $discount_detail;
                }
            }
            // 重新计算金额，不同，改写金额数据
            if ($total_money_ != $order['total_money']) {
                $save_info['total_money'] = round_number($total_money_,2);
            }
            if ($save_info) {
                M('village_group_order')->where(array('order_id' => $order['order_id']))->data($save_info)->save();
            }



            //使用积分
            $score_can_use_count=0;
            $score_deducte=0;
            $user_score_use_condition = $this->config['user_score_use_condition'];
            $user_score_max_use = PD('Percent_rate')->get_max_core_use(-1, $type_,$total_money);//不同业务不同积分

            $user_score_use_percent = (float)$this->config['user_score_use_percent'];
            $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                $total_ = $total_money;

                if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                        $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    } else {
                        $score_can_use_count = round_number($total_ * $user_score_use_percent,2) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : round_number($total_ * $user_score_use_percent,2);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    }
                }
            }
            //可用积分
            if($_GET['use_score'] == '1'){
                $total_money = $total_money - $score_deducte; // 实际需要支付的金额 扣除积分抵扣金额
            }
            // $this->assign('score_can_use_count', $score_can_use_count);
            // $this->assign('score_deducte', round_number($score_deducte,2));
            // $this->assign('score_count', $now_user['score_count']);
            $output['score']['score_deducte'] = $score_deducte;
            $output['score']['score_can_use_count'] = $score_can_use_count;
            $output['score']['score_count'] = $score_count;
        }
        $order['actual_total_money_info'] = $order['actual_total_money'] = round_number($total_money,2);
        $order['actual_discount_money'] = $discount_money;

        if (($this->config['village_group_delivery_switch']==1 || $this->config['village_group_express_open'] == '1') && $order['village_group_type'] != 1) {
            // 配送相关处理  暂时只存在平台配送
            $deliver_info = PD('Village_group')->village_deliver_fee();

            $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']):0;
            $userLocationLat = $header['pick_address']['lat'];
            $userLocationLong = $header['pick_address']['long'];

            $user_address_temp = PD('User_adress')->field(true)->where(array('uid' => $this->_uid))->order('`default` DESC,`adress_id` DESC')->select();
            $deliver_info['address_count'] = count($user_address_temp);
            $user_adress = array();
            $distance_array = array();
            $user_address_list = array();
            $is_adress = false;
            foreach ($user_address_temp as $address) {
                $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                if ($header['pick_address']['delivery_range_type'] == 0) {
                    $distance = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                    $distance = $distance / 1000;
                    if ($header['pick_address']['delivery_radius'] < $distance) {
                        continue;
                    }
                }
                $is_adress = true;

                if ($address_id == $address['adress_id']) {
                    $user_adress = $address;
                    break;
                }

                if(empty($address_id) && $address['default']){
                    $user_adress = $address;
                    break;
                }
                $distance_array[] = $address['distance'];
                $user_address_list[] = $address;
            }
            // $this->assign('is_adress', $is_adress);

            if (empty($user_adress) && $distance_array && $user_address_list) {
                array_multisort($distance_array, SORT_ASC, $user_address_list);
                $user_adress = $user_address_list[0];
            }
            if ($user_adress) {
                $province = PD('Area')->get_area_by_areaId($user_adress['province'],false);
                $user_adress['province_txt'] = $province['area_name'];

                $city = PD('Area')->get_area_by_areaId($user_adress['city'],false);
                $user_adress['city_txt'] = $city['area_name'];

                $area = PD('Area')->get_area_by_areaId($user_adress['area'],false);
                $user_adress['area_txt'] = $area['area_name'];
            }
            // $this->assign('user_adress', $user_adress);

            //计算配送费
            if ($user_adress) {
                $distance = 0;
                if (C('config.is_riding_distance')) {
                    import('@.ORG.longlat');
                    $longlat_class = new longlat();
                    $distance = $longlat_class->getRidingDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);
                    if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');
                }
                $distance || $distance = getDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);

                $distance = $distance / 1000;
                $pass_distance = $distance > $deliver_info['basic_distance'] ? floatval($distance - $deliver_info['basic_distance']) : 0;
                $deliver_info['delivery_fee'] += round($pass_distance * $deliver_info['per_km_price'], 2);
                $deliver_info['delivery_fee_old'] = $deliver_info['delivery_fee'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] > 0 ? $deliver_info['delivery_fee'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance2'] ? floatval($distance - $deliver_info['basic_distance2']) : 0;
                $deliver_info['delivery_fee2'] += round($pass_distance * $deliver_info['per_km_price2'], 2);
                $deliver_info['delivery_fee2_old'] = $deliver_info['delivery_fee2'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] > 0 ? $deliver_info['delivery_fee2'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance3'] ? floatval($distance - $deliver_info['basic_distance3']) : 0;
                $deliver_info['delivery_fee3'] += round($pass_distance * $deliver_info['per_km_price3'], 2);
                $deliver_info['delivery_fee3_old'] = $deliver_info['delivery_fee3'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] > 0 ? $deliver_info['delivery_fee3'] : 0;

                switch (intval($this->config['count_freight_charge_method'])) {
                    case 0:
                        break;
                    case 1:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 2:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old'] * 10) / 10;

                        break;
                    case 3:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 4:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old']);
                        break;
                    case 5:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old']);
                        break;
                    case 6:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old']);
                        break;
                }
            }
            $paramTime = time();
            if ($header['pick_address'] && $header['pick_address']['s_send_time']) {
                $paramTime += intval($header['pick_address']['s_send_time'])*60;
            }
            $date_list = PD('Village_group')->getSelectTime($deliver_info,$paramTime);
            foreach ($date_list as $key => $rowset) {
                $temp = array();
                asort($rowset);
                foreach ($rowset as $rv) {
                    $temp[] = $rv;
                }
                if ($key == date('Y-m-d')) {
                    $d_list[] = array('ymd' => $key, 'show_date' => '今天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '明天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400*2)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '后天', 'date_list' => $temp);
                } else {
                    $d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
                }
            }
            if (empty($d_list)) {
                $d_list = array();
            }
            $output['dates'] = $d_list;
            if ($user_adress) {
                $output['user_adress'] = $user_adress;
            }
            $output['deliver_info'] = $deliver_info;

            // $this->assign('dates', $d_list);
            if ($d_list && $d_list[0]['date_list'] && $d_list[0]['date_list'][0] && $d_list[0]['date_list'][0]['delivery_fee_old']) {
                $first_fee = $d_list[0]['date_list'][0]['delivery_fee_old'];
                //$order['actual_total_money_info'] += $first_fee;
                //$order['actual_total_money'] = getFormatNumber($order['actual_total_money_info']);
            } else {
                $first_fee = 0;
            }
            // $this->assign('first_fee', $first_fee);
            $output['first_fee'] = $first_fee;


            // $this->assign('order_id',$_GET['order_id']);
            // $this->assign('deliver_info',$deliver_info);
        }


        if (C('config.village_group_hide_detail')) {
            $header['phone'] = '';
            // $header['name'] = str_replace_name($header['name']);
            // if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
            //     if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
            //         $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
            //     }else{
            //         $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
            //     }
            // }
        }
        $output['leader_info'] = $header;
        $output['order'] = $order;
        $output['system_coupon'] = $system_coupon;
        // $this->assign('header',$header);
        // $this->assign('order',$order);
        // $this->display();
        
        returnCode(0, $output);
    }



    //购物车去结算 
    public function save_order(){
        $goods_id = I("goods_id");
        $share_uid = I("share_uid");
        $nums = I("nums");
        $liveId = I("live_id",0);
        $liveType = I("live_type",'');
        $buy_type = '0';//0=正常  1=购物车结算 2=直接购买

        $user = M('User')->where(['uid' => $this->_uid])->field('phone')->find();
        if(empty($user['phone'])){
            returnCode(2, [], "请先到个人中心绑定手机号后再下单购买");
        }
        if($goods_id && $nums){//直接购买某个商品，不走购物车
            //查询自提点是否关闭
            $pick_address = M('Village_group_pick_address')->where(array('header_uid'=>$this->leader_id))->find();
            if(!$pick_address||$pick_address['status']!=1){
                returnCode(2, [], '自提点已关闭，请重新选择自提点！');
            }
            $buy_type = $this->config['village_group_express_open'] == 1 ? '2' : '0';//如开启快递，则直接购买为活动
            $goods = [
                [
                    'count' => $nums,
                    'goods_id' => $goods_id
                ]
            ];
        }
        else{
            $buy_type = $this->config['village_group_express_open'] == 1 ? '1' : '0';//如开启快递，则直接购买为活动
            //1、检查购物车
            $where = [
                'uid' => $this->_uid,
                'start_id' => $this->leader_id,
                'count' => ['gt', 0],
                'checked' => 1,//选中的商品
                'single_buy' => 0 
            ];
            $goods = M('Village_group_order_cart')->where($where)->field("goods_id, count")->select();
            if(empty($goods)){
                returnCode(2, [], "没有可以购买的商品");
            }
        }
        $goods_count = array_column($goods, 'count', 'goods_id');
        $check = D("Village_group_goods")->check_goods($this->leader_id, $goods_count, $this->_uid);
        if(!$check['status']){
            returnCode(2 ,[] , $check['msg']);
        }
        $data = D("Village_group_goods")->get_goods($this->leader_id, 0, 0, -1, 0, ['goods.goods_id'=>['in', array_column($goods, 'goods_id')]]);

        $all_money = $all_nums = 0;
        foreach ($data as $key => $value) {
            $all_money += ($value['price']+$value['packing_fee'])*$goods_count[$value['goods_id']];
            $all_nums += $goods_count[$value['goods_id']];
        }
        if($this->config['village_group_express_open'] == '1' && $this->config['village_group_start_price'] > 0 && $all_money < $this->config['village_group_start_price'] && !($goods_id && $nums)){
            returnCode(2, [], "小主再挑挑？满".$this->config['village_group_start_price']."免费配送哦！");
        }

        $insert['real_orderid']       = (8 > strlen($this->_uid)) ? date('Ymdhis') . substr(microtime(), 2, 8 - strlen($this->_uid)) . $this->_uid : date('Ymdhis') . $this->_uid;
        $insert['uid']                = $this->_uid;
        $insert['share_uid']          = $share_uid;
        $insert['start_uid']          = $this->leader_id;
        $insert['total_money']        = $all_money;
        $insert['comment']            = '';
        $insert['add_time']           = time();
        $insert['status']             = 0;
        $insert['last_time']          = time();
        $insert['code']               = rand(100000,999999);
        $insert['buy_type']           = $buy_type;
        $insert['live_id']            = $liveId;
        $insert['live_type']          = $liveType;

        fdump($data[0],'village_goods');
        // 处理周期购信息
        $village_group_type = 0;
        if ($goods_id && $_POST['single_buy']==1) {
            $goods = $data[0];
            $village_group_type = $goods['village_group_type'];
            if ($goods && $goods['village_group_type']==1) {
                $village_group_type = 1;
                $cycle = M('Village_group_cycle')->where(array('goods_id'=>$goods['goods_id']))->find();
                // 配送类型 1周期购
                $insert['village_group_type'] = 1;
                // 周期类型 1 每日 2 每周 3 每月
                $insert['cycle_type'] = $cycle['cycle_type'];
                //配送日
                if ($cycle['cycle_type']==1) {
                    $_POST['time']=1;
                }
                if ($cycle['delivery_date'] && !$_POST['time']) {
                    $delivery_date_arr = explode(',',$cycle['delivery_date']);
                    if (1==count($delivery_date_arr)) {
                        $_POST['time'] = reset($delivery_date_arr);
                    } else {
                        returnCode(2, [], '请选择周期送时间');
                    }
                }
                $insert['delivery_date']  = $_POST['time'];
                $insert['is_limit_delay']  = $cycle['is_limit_delay'];
                $insert['limit_delay_num']  = $cycle['limit_delay_num'];
                $insert['delivery_periods']  = $cycle['delivery_periods'];
                $insert['is_all_date']  = $cycle['is_all_date'];
                $insert['limit_days']  = $cycle['limit_days'];
                $insert['limit_time']  = $cycle['limit_time'];
                $insert['delivery_date_info']  = $cycle['delivery_date'];
            }
        }
        $order_id = M('Village_group_order_temp')->add($insert);

        if ($order_id) {
            $maxOrderId = M('Village_group_order')->where('1=1')->order('order_id desc')->getField('order_id');
            if ($maxOrderId > $order_id) { //社区团购订单表已存在该订单ID的订单数据，需要更新order_id
                M('Village_group_order_temp')->where(['order_id' => $order_id])->data(['order_id' => $maxOrderId + 1])->save();
                $order_id = $maxOrderId + 1;
            }
            $info = M('Village_group_order_temp')->where(['order_id' => $order_id])->find();
            $_num = 0;
            foreach ($data as $key => $value) {
                $_num += $goods_count[$value['goods_id']];
                $detail['order_id'] = $order_id;
                $detail['goods_id'] = $value['goods_id'];
                $detail['mer_id'] =  $value['mer_id'];
                $detail['mer_group_id'] = $value['group_id'];
                $detail['name'] = $value['name'];
                $detail['purchase_price']  = $value['purchase_price'];
                $detail['price'] = $value['_price_old'];
                $detail['num']  = $goods_count[$value['goods_id']];
                $detail['create_time'] = time();
                $detail['arrive_cutting_time'] = 0;//截单时间
                $detail['arrive_time'] = 0;//到达时间
                if($value['arrive_type'] == '1'){//当日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time'];
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+1*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '2'){//次日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+1*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+2*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '3'){//隔日达
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+2*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+3*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '4'){//自定义
                    if(time() < strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']){
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+$value['arrive_days']*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time'];
                    }
                    else{
                        $detail['arrive_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_time']+($value['arrive_days']+1)*86400;
                        $detail['arrive_cutting_time'] = strtotime(date("Y-m-d 00:00:00"))+$value['arrive_cutting_time']+1*86400;
                    }
                }
                if($value['arrive_type'] == '5'){//固定日期
                    if(time() < $value['arrive_cutting_datetime']){
                        $detail['arrive_time'] = $value['arrive_datetime'];
                        $detail['arrive_cutting_time'] = $value['arrive_cutting_datetime'];
                    }
                }
                $detail['pay_price']  = $value['price'];
                $detail['old_price']  = $value['old_price'];
                $detail['is_merchant_send']  = $value['is_merchant_send'];
                $detail['leader_money']  = $value['leader_money']>0?$value['leader_money']:0;
                $detail['village_group_type']  = $village_group_type;
                $detail['increase_price'] = $value['increase_price'];
                $detail['packing_fee'] = $value['packing_fee'];

                M('Village_group_order_detail_temp')->add($detail);
            }
            $info['num'] = $_num;

            //增加搜索关键词 商品名称
            $goods_name = [];

            foreach ($data as $v) {
                $goods_name[] = $v['name'];
            }

            $goods_name_str = rtrim(implode(',', $goods_name),',');
            $info['keywords'] = $goods_name_str;

            // PD('System_order')->add_order('village_group',$info,$order_id);

            $pay_order_param = array(
                'business_type' => 'village_group',
                'business_id' => $order_id,
                'order_name' => $this->config['village_group_alias'].'订单',
                'uid' => $this->_uid,
                'total_money' => $all_money,
                'wx_cheap' => 0,
            );

            $now_user = PD('User')->get_user($this->_uid);
            $param['order_id'] = $order_id;
            $param['status'] = 0;
            $param['from_type'] = 0;
            $param['note'] = '用户下单';
            $param['name'] =$now_user['nickname'];
            $param['phone'] =$now_user['phone'];
            PD('Village_group')->add_log($param);

            $plat_order_result = PD('Plat_order')->add_order($pay_order_param);
            returnCode(0, ['order_id'=>$order_id]);
        }else{
            returnCode(2, [], '参团失败');
        }
    }
    
    //确认订单
    public function confirm_order(){
        $_GET['order_id'] = I("order_id");
        $_GET['unsys_coupon'] = I("unsys_coupon");//是否使用优惠券 1=不使用
        $_GET['sysc_id'] = I("sysc_id");//优惠券ID 
        $_GET['adress_id'] = I("adress_id");//地址ID
        $_GET['use_score'] = isset($_POST["use_score"]) ? $_POST["use_score"] : 1;//是否使用积分
        $_GET['change_leader_id'] = intval(I("change_leader_id"));
        if($_GET['change_leader_id'] > 0){//如果有重新选择团长，则修改当前订单的团长
            $this->leader_id = $_GET['change_leader_id'];
            M("Village_group_order_temp")->where("order_id=".$_GET['order_id'])->data(['start_uid'=>$this->leader_id])->save();
        }

        $output = [
            'dates' => [],
//            'user_adress' => [],
            'deliver_info' => [],
            'first_fee' => '',//配送费
            'leader_info' => [],
            'order' => [],
            'score' => []
        ];//最终输出结果

        //以下是原来的老代码
        $order  = PD('Village_group_order_temp')->order_detail($_GET['order_id'],$this->leader_id);
        $header = PD('Village_group')->get_header_info(0,$order['start_uid'],true);
        $header  =$header['msg'];
        
        //取活动价
        $activity_goods_ids = array_column($order['goods'], 'goods_id');
        $activity_goods = D("Village_group_activity_goods")->getActivityInfo($activity_goods_ids, 2);
        $activity_goods_price = array_column($activity_goods, 'buy_price', 'goods_id');

        //用户信息
        $now_user = PD('User')->get_user($this->_uid);

        if($order['status']==0 && $order['add_time']+1800<time()){
            M('Village_group_order_temp')->where(array('order_id'=>$_GET['order_id']))->save(array('status'=>10,'last_time'=>time()));
        }
        $order['pay_type_txt'] = PD('Pay')->get_pay_name($order['pay_type'],1,1);

        $type_ = $_GET['type'] ? $_GET['type'] : 'village_group';
        // 优惠的金额
        $discount_money = 0;
        // 重新计算原来金额
        $total_money_ = 0;
        // 等级优惠后金额
        $total_money = 0;
        // 优惠详情
        $discount_detail = array();
        //商品总重量
        $goods_weight_all = 0;
        if ($order['order_detail']) {
            $goods_info = $order['goods'];
            foreach ($order['order_detail'] as $item){
                if($goods_info[$item['goods_id']]){
                    $goods_weight_all += $goods_info[$item['goods_id']]['goods_weight']*$item['num'];
                }
            }
            // 获取平台用户等级设置
            $user_= M('User')->where(array('uid'=>$this->_uid))->find();
            $m_village_group_order_detail = M('Village_group_order_detail_temp');
            if(!empty($user_) && isset($user_['level'])){
                $level_off_system = M('User_level')->where(array('level' => $user_['level']))->find();
                // $this->assign('level_off_system',$level_off_system);
                $is_discount = 0;
                foreach ($order['order_detail'] as &$val) {
                    $val['packing_fee'] = $val['packing_fee'] > 0 ? ($val['packing_fee'] == intval($val['packing_fee']) ? intval($val['packing_fee']) : $val['packing_fee']) : 0;
                    $val['arrive_title'] = "";//到达时间说明
                    if($goods_info[$val['goods_id']]){
                        if($goods_info[$val['goods_id']]['arrive_type'] == 1){//当日达
                            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_info[$val['goods_id']]['arrive_cutting_time']){
                                $val['arrive_title'] = "当日达";
                            }
                            else{
                                $val['arrive_title'] = date('m月d日', strtotime("+1 day"))."到达";   
                            }
                        }
                        elseif(in_array($goods_info[$val['goods_id']]['arrive_type'], [2,3,4])){
                            if(time() < strtotime(date("Y-m-d 00:00:00"))+$goods_info[$val['goods_id']]['arrive_cutting_time']){
                                $val['arrive_title'] = date('m月d日', strtotime("+".$goods_info[$val['goods_id']]['arrive_days']." days"))."到达";
                            }
                            else{
                                $val['arrive_title'] = date('m月d日', strtotime("+".($goods_info[$val['goods_id']]['arrive_days']+1)." days"))."到达";
                            }
                        }
                        elseif($goods_info[$val['goods_id']]['arrive_type'] == 5){
                            $val['arrive_title'] = date('m月d日', $goods_info[$val['goods_id']]['arrive_datetime'])."到达";
                        }
                    }

                    // var_dump($val['increase_price']);
                    $area_price = $val['goods_bind_area']['price']>0?$val['goods_bind_area']['price']:0;
                    $level_info = array();
                    $level_info['order_id']=$order['order_id'];
                    // 商品如果自定义了等级优惠取商品的，没有定义默认取平台的
                    $leveloff=!empty($goods_info[$val['goods_id']]['leveloff']) ? unserialize($goods_info[$val['goods_id']]['leveloff']) : '';

                    if ($leveloff) {
                        $level_off=$leveloff[$user_['level']];
                    } elseif($level_off_system && $level_off_system['type'] > 0) {
                        $level_off_system['vv']=$level_off_system['boon'];
                        $level_off=$level_off_system;
                    }
//                    fdump($goods_info,'================',true);
//                    fdump($leveloff,'================',true);
                    fdump($level_off,'================',true);
                    $val['is_level'] = $level_off ? 1 : 0;
                    //区域售价加价信息
                    if ($val['goods_bind_area']) {
                        $val['price'] = $area_price;
                        if ($area_price>0 && $area_price != $val['area_price']) {
                            $m_village_group_order_detail->where(array('id' => $val['id']))->setField('area_price', $area_price);
                        }
                        if ($val['goods_bind_area']['is_add_price']==0) { // 没有打开加价
                            $val['increase_price'] = 0;
                        }else{
                            $val['increase_price'] = floatval($val['increase_price']);
                        }
                    }
                    elseif($activity_goods_price[$val['goods_id']]){//如果没有设置区域价，那就再看看有没有设置活动价
                        $val['price'] = $activity_goods_price[$val['goods_id']];
                    }
                    $_old_price = $val['price'];
                    $val['price'] += $val['packing_fee'];//加上打包费

                    if($level_off['type']==1 && $level_off['vv'] > 0 && $level_off['vv'] <= 100){
                        $level_discount = $val['price']*(1-$level_off['vv']/100);
                        M('Village_group_order_detail_temp')->where(array('goods_id'=>$val['goods_id'],'order_id'=>$_GET['order_id']))->setField('discount_start_money',$level_discount);
                        if ($level_discount) {
//                          $is_discount = 1;
                            $val['is_discount'] = 1;
                        }
                        $level_discount = round_number($level_discount,2);
                        $val['discount_price'] = round_number($val['price']-$level_discount,2); // 优惠后的价格
                        // 团长加价参与优惠
                        if ($val['increase_price'] && $val['increase_price']>0) {
                            $level_discount_increase = $val['increase_price']*(1-$level_off['vv']/100);
                            $val['discount_price'] = $val['discount_price']  + round_number($val['increase_price'] - $level_discount_increase,2);
                            $level_info['level_discount_increase'] = round_number($level_discount_increase,2);
                            $level_info['increase_price'] = $val['increase_price'];
                        }
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2); // 原价计算总金额
                        $val['total_money'] = round_number($val['discount_price']*$val['num'],2);
                        $discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        // 处理折扣详情
                        $level_info['level_discount'] = round_number($level_discount,2);
                        $level_info['offstr']='折扣'.round_number($level_off['vv']/10,2).'折';
                        $level_info['type'] = 1;
                        $level_info['discount']=$level_off['vv'].'%';
                        $level_info['price']=$val['price'];
                        $level_info['discount_price'] = $val['discount_price'];
                        $level_info['num']=$val['num'];
                        $level_info['goods_id']=$val['goods_id'];
                        $level_info['area_price']=$area_price;
                        $discount_detail[] = $level_info;


                    }elseif($level_off['type']==2){
                        $level_discount = $level_off['vv'];
                        M('Village_group_order_detail_temp')->where(array('goods_id'=>$val['goods_id'],'order_id'=>$_GET['order_id']))->setField('discount_start_money',$level_off['vv']);
//
                        if ($level_discount) {
//                          $is_discount = 1;
                            $val['is_discount'] = 1;
                        }
                        $val['discount_price'] = $val['price'] > $level_discount ? round_number($val['price']-$level_discount,2) : 0; // 优惠后的价格
                        // 团长加价参与优惠
                        if ($val['increase_price'] && $val['increase_price']>0) {
                            if ($val['discount_price']>=0) {
                                $level_discount_increase = 0;
                            } else {
                                $level_discount_increase = -($val['discount_price']);
                            }
                            $val['discount_price'] = $val['discount_price'] + $val['increase_price'];
                            $level_info['level_discount_increase'] = $level_discount_increase;
                            $level_info['increase_price'] = $val['increase_price'];
                        }
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
                        $val['total_money'] = round_number($val['discount_price']*$val['num'],2);
                        $discount_money = $discount_money + round_number($val['old_money']-$val['total_money'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        // 处理立减详情
                        $level_info['level_discount'] = round_number($level_discount,2);
                        $level_info['offstr']='每份立减'.$level_off['vv'].C('config.Currency_txt');
                        $level_info['type'] = 3;
                        $level_info['discount']=$level_off['vv'];
                        $level_info['price']=$val['price'];
                        $level_info['discount_price'] = $val['discount_price'];
                        $level_info['num']=$val['num'];
                        $level_info['goods_id']=$val['goods_id'];
                        $level_info['area_price']=$area_price;
                        $discount_detail[] = $level_info;


                    }else{
                        $val['old_money'] = round_number(($val['price']+$val['increase_price'])*$val['num'],2);
                        // 计算原金额
                        $total_money_ = $total_money_ + $val['old_money'];
                        $val['discount_price'] = $val['price'];
                    }
                    $val['price'] = $_old_price;
                    $val['price'] = round_number($val['price']+$val['increase_price'],2);
                    $val['discount_price'] = round_number($val['discount_price'],2);
                }
                // $this->assign('is_discount',$is_discount);
            }

            $vip_discount_money = $discount_money = round_number($discount_money,2);
            if ($total_money_ != $order['total_money']) {
                $total_money = round_number($total_money_-$discount_money,2);
            } else {
                $total_money = round_number($order['total_money']-$discount_money,2);
            }

            //平台优惠券
            if ($this->is_app_browser) {
                $platform = 'app';
            } else if ($this->is_weixin_browser) {
                $platform = 'weixin';
            } else {
                $platform = 'wap';
            }
            if ($_GET['unsys_coupon']!=1) {
                if (empty($_GET['sysc_id'])) {

                    $old_money = 0;
                    if ($total_money != $order['total_money']) {
                        $old_money = $order['total_money'];
                        $order['total_money'] = $total_money;
                    }
                    $now_coupon = PD('System_coupon')->get_noworder_coupon_list($order, $type_, $now_user['phone'], $this->_uid, $platform);
                    $system_coupon = reset($now_coupon);

                    if ($old_money>0) {
                        $order['total_money'] = $old_money;
                    }
                } else {
                    $system_coupon = PD('System_coupon')->get_coupon_info($_GET['sysc_id']);
                    if($system_coupon['is_discount']==1){
                        $system_coupon['discount_money'] =round_number( $total_money * (100-$system_coupon['discount_value']*10)/100,2);
                    }else{
                        $system_coupon['discount_money'] = $system_coupon['discount'];
                    }
                }
            }
            if($system_coupon && $system_coupon['order_money']){
                if($system_coupon['is_discount'] == '1'){
                    $system_coupon['name'] = "满".getFormatNumber($system_coupon['order_money'])."打".getFormatNumber($system_coupon['discount_value'])."折";
                }
                else{
                    $system_coupon['name'] = "满".getFormatNumber($system_coupon['order_money'])."减".getFormatNumber($system_coupon['discount']);   
                }
            }

            if (!empty($system_coupon)) {
                $system_coupon['coupon_url_param'] = array('sysc_id' => $system_coupon['id'], 'order_id' => $order['order_id'], 'type' => $type_,'discount_type'=>'coupon');

                if($_GET['discount_type']=='coupon'){
                    $system_coupon['coupon_url_param']['discount_type'] = 'coupon';
                }else{
                    unset($system_coupon['coupon_url_param']['discount_type']);
                }

                $_SESSION['sysc_id'] = $system_coupon['id'];

                $system_coupon['discount'] = $system_coupon['discount_money'];
                if ($total_money >= $system_coupon['order_money']) {
                    $level_info = array();
                    if ($system_coupon['is_discount'] == 1) {
                        $level_info['type'] = 4;
                        $level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受折扣'.$system_coupon['discount_value'];
                        $level_info['discount']=$system_coupon['discount_value'];
                    } else {
                        $level_info['type'] = 2;
                        $level_info['offstr']='订单满足'.$system_coupon['order_money'].C('config.Currency_txt').'享受满减'.$system_coupon['discount'];
                        $level_info['discount']=$system_coupon['discount'];
                    }
                    if($system_coupon['discount_money'] > $total_money){
                        $system_coupon['discount_money'] = $total_money;
                        $total_money = 0;
                    }
                    else{
                        $total_money = $total_money - $system_coupon['discount_money'];
                    }
                    $total_money = round_number($total_money,2);
                    $discount_money = $discount_money + $system_coupon['discount_money'];
                    $discount_money = round_number($discount_money,2);
                    // 处理优惠券详情
                    $level_info['level_discount'] = round_number($system_coupon['discount_money'],2);
                    $level_info['money'] = $system_coupon['order_money'];
                    $level_info['order_id']=$order['order_id'];
                    $discount_detail[] = $level_info;
                }
                // $this->assign('system_coupon', $system_coupon);

                $order['total_money'] -= empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];

            } else {
                $system_coupon['coupon_url_param'] = array();
            }
            $coupon_url = $system_coupon['coupon_url_param'];
            if($_GET['unsys_coupon']&&!empty($coupon_url)){
                unset($_SESSION['sysc_id']);
                $coupon_url['unsys_coupon']=1;
            }
            // $this->assign('coupon_url', $coupon_url);
            // 写入订单数据
            $save_info = array();
            // 平台优惠券id
            if ($system_coupon && $order['coupon_id'] != $system_coupon['id']) {
                $save_info['coupon_id'] = $system_coupon['id']?$system_coupon['id']:0;
            } elseif (!$system_coupon && $order['coupon_id']>0) {
                $save_info['coupon_id'] = 0;
            }
            // 平台优惠券优惠金额
            if ($system_coupon && $order['coupon_price'] != $system_coupon['discount_money']) {
                $save_info['coupon_price'] = $system_coupon['discount_money']?$system_coupon['discount_money']:0;
            } elseif (!$system_coupon && $order['coupon_price']>0) {
                $save_info['coupon_price'] = 0;
            }
            // 用户等级
            if ($user_ && $order['user_level'] != $user_['level']) {
                $save_info['user_level'] = $user_['level'];
            }
            // 用户等级优惠金额
            if ($order['vip_discount_money'] != $vip_discount_money) {
                $save_info['vip_discount_money'] = $vip_discount_money;
            }
            // 优惠后总金额
            if ($order['discount_price'] != $total_money) {
                $save_info['discount_price'] = $total_money;
            }
            // 优惠详情
            if ($discount_detail) {
                $discount_detail = serialize($discount_detail);
                if ($order['discount_detail'] != $discount_detail) {
                    $save_info['discount_detail'] = $discount_detail;
                }
            }
            // 重新计算金额，不同，改写金额数据
            if ($total_money_ != $order['total_money']) {
                $save_info['total_money'] = round_number($total_money_,2);
            }
            if ($save_info) {
                M('village_group_order_temp')->where(array('order_id' => $order['order_id']))->data($save_info)->save();
            }



            //使用积分
            $score_can_use_count=0;
            $score_deducte=0;
            $user_score_use_condition = $this->config['user_score_use_condition'];
            $user_score_max_use = PD('Percent_rate')->get_max_core_use(-1, $type_,$total_money);//不同业务不同积分

            $user_score_use_percent = (float)$this->config['user_score_use_percent'];
            $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                $total_ = $total_money;

                if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                        $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    } else {
                        $score_can_use_count = round_number($total_ * $user_score_use_percent,2) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : round_number($total_ * $user_score_use_percent,2);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    }
                }
            }
            //可用积分
            if($_GET['use_score'] == '1'){
                $total_money = $total_money - $score_deducte; // 实际需要支付的金额 扣除积分抵扣金额
            }
            // $this->assign('score_can_use_count', $score_can_use_count);
            // $this->assign('score_deducte', round_number($score_deducte,2));
            // $this->assign('score_count', $now_user['score_count']);
            $output['score']['score_deducte'] = $score_deducte;
            $output['score']['score_can_use_count'] = $score_can_use_count;
            $output['score']['score_count'] = $score_count;
        }
        $order['actual_total_money_info'] = $order['actual_total_money'] = round_number($total_money,2);
        $order['actual_discount_money'] = $discount_money;

        if (($this->config['village_group_delivery_switch']==1 || $this->config['village_group_express_open'] == '1') && $order['village_group_type'] != 1) {
            // 配送相关处理  暂时只存在平台配送
            $deliver_info = PD('Village_group')->village_deliver_fee();
            $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']):0;

            $userLocationLat = $header['pick_address']['lat'];
            $userLocationLong = $header['pick_address']['long'];

            $user_address_temp = PD('User_adress')->field(true)->where(array('uid' => $this->_uid))->order('`default` DESC,`adress_id` DESC')->select();
            $deliver_info['address_count'] = count($user_address_temp);
            $user_adress = array();
            $distance_array = array();
            $user_address_list = array();
            $is_adress = false;
            foreach ($user_address_temp as $address) {
                $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                if ($header['pick_address']['delivery_range_type'] == 0) {
                    $distance = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
                    $distance = $distance / 1000;
                    if ($header['pick_address']['delivery_radius'] < $distance) {
                        continue;
                    }
                }
                $is_adress = true;

                if ($address_id == $address['adress_id']) {
                    $user_adress = $address;
                    break;
                }

                if(empty($address_id) && $address['default']){
                    $user_adress = $address;
                    break;
                }
                $distance_array[] = $address['distance'];
                $user_address_list[] = $address;
            }
            // $this->assign('is_adress', $is_adress);

            if (empty($user_adress) && $distance_array && $user_address_list) {
                array_multisort($distance_array, SORT_ASC, $user_address_list);
                $user_adress = $user_address_list[0];
            }
            if ($user_adress) {
                $province = PD('Area')->get_area_by_areaId($user_adress['province'],false);
                $user_adress['province_txt'] = $province['area_name'];

                $city = PD('Area')->get_area_by_areaId($user_adress['city'],false);
                $user_adress['city_txt'] = $city['area_name'];

                $area = PD('Area')->get_area_by_areaId($user_adress['area'],false);
                $user_adress['area_txt'] = $area['area_name'];
            }
            // $this->assign('user_adress', $user_adress);

            //计算配送费
            if ($user_adress) {
                $distance = 0;
                if (C('config.is_riding_distance')) {
                    import('@.ORG.longlat');
                    $longlat_class = new longlat();
                    $distance = $longlat_class->getRidingDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);
                    if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');
                }
                $distance || $distance = getDistance($user_adress['latitude'], $user_adress['longitude'], $userLocationLat, $userLocationLong);

                $distance = $distance / 1000;
                $pass_distance = $distance > $deliver_info['basic_distance'] ? floatval($distance - $deliver_info['basic_distance']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight'] ? floatval($goods_weight_all - $deliver_info['basic_weight']) : 0;
                $deliver_info['delivery_fee'] += round($pass_distance * $deliver_info['per_km_price'], 2);
                $deliver_info['delivery_fee'] += round($pass_weight * $deliver_info['per_weight_price'], 2);
                $deliver_info['delivery_fee_old'] = $deliver_info['delivery_fee'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] > 0 ? $deliver_info['delivery_fee'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance2'] ? floatval($distance - $deliver_info['basic_distance2']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight2'] ? floatval($goods_weight_all - $deliver_info['basic_weight2']) : 0;
                $deliver_info['delivery_fee2'] += round($pass_distance * $deliver_info['per_km_price2'], 2);
                $deliver_info['delivery_fee2'] += round($pass_weight * $deliver_info['per_weight_price2'], 2);
                $deliver_info['delivery_fee2_old'] = $deliver_info['delivery_fee2'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] > 0 ? $deliver_info['delivery_fee2'] : 0;

                $pass_distance = $distance > $deliver_info['basic_distance3'] ? floatval($distance - $deliver_info['basic_distance3']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight3'] ? floatval($goods_weight_all - $deliver_info['basic_weight3']) : 0;
                $deliver_info['delivery_fee3'] += round($pass_distance * $deliver_info['per_km_price3'], 2);
                $deliver_info['delivery_fee3'] += round($pass_weight * $deliver_info['per_weight_price3'], 2);
                $deliver_info['delivery_fee3_old'] = $deliver_info['delivery_fee3'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] > 0 ? $deliver_info['delivery_fee3'] : 0;

                switch (intval($this->config['count_freight_charge_method'])) {
                    case 0:
                        break;
                    case 1:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 2:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old'] * 10) / 10;

                        break;
                    case 3:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee'] * 10) / 10;
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2'] * 10) / 10;
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3'] * 10) / 10;
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old'] * 10) / 10;
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old'] * 10) / 10;
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old'] * 10) / 10;
                        break;
                    case 4:
                        $deliver_info['delivery_fee'] = ceil($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = ceil($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = ceil($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = ceil($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = ceil($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = ceil($deliver_info['delivery_fee3_old']);
                        break;
                    case 5:
                        $deliver_info['delivery_fee'] = floor($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = floor($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = floor($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = floor($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = floor($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = floor($deliver_info['delivery_fee3_old']);
                        break;
                    case 6:
                        $deliver_info['delivery_fee'] = round($deliver_info['delivery_fee']);
                        $deliver_info['delivery_fee2'] = round($deliver_info['delivery_fee2']);
                        $deliver_info['delivery_fee3'] = round($deliver_info['delivery_fee3']);
                        $deliver_info['delivery_fee_old'] = round($deliver_info['delivery_fee_old']);
                        $deliver_info['delivery_fee2_old'] = round($deliver_info['delivery_fee2_old']);
                        $deliver_info['delivery_fee3_old'] = round($deliver_info['delivery_fee3_old']);
                        break;
                }
            }
            $paramTime = time();
            if ($header['pick_address'] && $header['pick_address']['s_send_time']) {
                $paramTime += intval($header['pick_address']['s_send_time'])*60;
            }
            $date_list = PD('Village_group')->getSelectTime($deliver_info,$paramTime);
            foreach ($date_list as $key => $rowset) {
                $temp = array();
                asort($rowset);
                foreach ($rowset as $rv) {
                    $temp[] = $rv;
                }
                if ($key == date('Y-m-d')) {
                    $d_list[] = array('ymd' => $key, 'show_date' => '今天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '明天', 'date_list' => $temp);
                } else if($key == date('Y-m-d',time()+86400*2)){
                    $d_list[] = array('ymd' => $key, 'show_date' => '后天', 'date_list' => $temp);
                } else {
                    $d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
                }
            }
            if (empty($d_list)) {
                $d_list = array();
            }
            $output['dates'] = $d_list;
            if ($user_adress) {
                $output['user_adress'] = $user_adress;
            }
            $output['deliver_info'] = $deliver_info;

            // $this->assign('dates', $d_list);
            if ($d_list && $d_list[0]['date_list'] && $d_list[0]['date_list'][0] && $d_list[0]['date_list'][0]['delivery_fee_old']) {
                $first_fee = $d_list[0]['date_list'][0]['delivery_fee_old'];
                //$order['actual_total_money_info'] += $first_fee;
                //$order['actual_total_money'] = getFormatNumber($order['actual_total_money_info']);
            } else {
                $first_fee = 0;
            }
            // $this->assign('first_fee', $first_fee);
            $output['first_fee'] = $first_fee;


            // $this->assign('order_id',$_GET['order_id']);
            // $this->assign('deliver_info',$deliver_info);
        }


        if (C('config.village_group_hide_detail')) {
            $header['phone'] = '';
            // $header['name'] = str_replace_name($header['name']);
            // if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') > 3) {
            //     if (mb_strlen($header['pick_address']['pick_addr'],'UTF8') == 4) {
            //         $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-3).'****';
            //     }else{
            //         $header['pick_address']['pick_addr'] =  mb_substr($header['pick_address']['pick_addr'],0,-4).'****';
            //     }
            // }
        }
        $output['leader_info'] = $header;
        $output['order'] = $order;
        $output['system_coupon'] = $system_coupon;
        // $this->assign('header',$header);
        // $this->assign('order',$order);
        // $this->display();

        //增加默认自提人
        $output['self_pick_person'] = new \stdClass;
        $person = M('Self_pick_person')->where(['uid' => $this->_uid])->order('is_default desc,id ASC')->find();
        if ($person) {
            $output['self_pick_person'] = $person;
        }
        
        returnCode(0, $output);
    }

    //确认支付
    public function confirm_go_pay(){
        $use_score = I("use_score") ? : 0;
        $type_ = I("type") ? : 'village_group';
        $is_pick_group = I("is_pick_group");
        $address_id = I("address_id");
        $ouserTel = I("ouserTel");
        $ouserName = I("ouserName");
        $oarrivalTime = I("oarrivalTime");
        $oarrivalDate = I("oarrivalDate");
        $ouserAddres = I("ouserAddres");
        $order_id = I("order_id");
        $note = I("note");
        $time = trim(I("time"));
        $selfPickId = intval(I("selfPickId"));
        if($time){
            $time_arr = explode('-', $time);
        }

        //查询自提点是否关闭
        $pick_address = M('Village_group_pick_address')->where(array('header_uid'=>$this->leader_id))->find();
        if(!$pick_address||$pick_address['status']!=1){
            returnCode(2, [], '自提点已关闭，请重新选择自提点！');
        }
     
        $order  = PD('Village_group_order_temp')->order_detail($order_id);
        //查询是否更新在正式表
        $realOrderInfo = M('Village_group_order')->where(['order_id'=>$order_id])->find();
        if($realOrderInfo && $realOrderInfo['status'] == 10){
            returnCode(2 ,[] , '订单支付超时已取消，请重新下单');
        }
        // 从临时表更新数据到正式表 
        //复制商品经常并发重复
        $lockKey = 'village_group_order_' . $this->_uid . $order_id;
        $lockRs = setLock($lockKey, 30);
        if ($lockRs['error_no']) {
            returnCode(2, [], $lockRs['error_msg']);
        }
        $this->_tempToOrder($order_id, $order);
        unLock($lockKey);

        $goods_count = array_column($order['order_detail'], 'num', 'goods_id');

        //商品总重量
        $goods_weight_all = 0;
        $goods_info = $order['goods'];
        foreach ($order['order_detail'] as $item){
            if($goods_info[$item['goods_id']]){
                $goods_weight_all += $goods_info[$item['goods_id']]['goods_weight']*$item['num'];
            }
        }
 
        if(!$realOrderInfo || !C('config.village_group_stock_reduce_type')){
            //计算库存是否充足
            $check = D("Village_group_goods")->check_goods($this->leader_id, $goods_count, $this->_uid, true, $order_id);
            if(!$check['status']){
                returnCode(2 ,[] , $check['msg']);
            }
        }

        $total_money = $order['discount_price']>=0?$order['discount_price']:$order['total_money'];
        $total_money = round_number($total_money,2);
        //用户信息
        $now_user = PD('User')->get_user($this->_uid);
        //使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        if ($use_score>=1) {
            $user_score_use_condition = $this->config['user_score_use_condition'];
            $user_score_max_use = PD('Percent_rate')->get_max_core_use(-1, $type_,$total_money);//不同业务不同积分

            $user_score_use_percent = (float)$this->config['user_score_use_percent'];
            $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                $total_ = $total_money;

                if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                        $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    } else {
                        $score_can_use_count = round_number($total_ * $user_score_use_percent,2) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : round_number($total_ * $user_score_use_percent,2);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    }
                }
            }

            //积分抵扣不小于0.01元才算使用
            if (bccomp($score_deducte, "0.01", 2) == -1) {
                $score_can_use_count=0;
                $score_deducte=0;
                $use_score = 0;
            }

            //可用积分
            $total_money = $total_money - $score_deducte; // 实际需要支付的金额 扣除积分抵扣金额
        }
        // 对应处理支付订单列表
        $plat_order_info = M('Plat_order')->where(array('business_id' => $order['order_id'], 'business_type' => $type_))->find();


        // 修改表格 pigcms_village_group_order 数据
        $village_order = array();
        // 如果是平台配送
        // 处理配送信息
        $is_pick_group = $is_pick_group>0?$is_pick_group:0;// 默认自提
        if ($is_pick_group>0 && $this->config['village_group_delivery_switch'] != 1 && $order['village_group_type'] != 1) {
            returnCode(2, [], '平台关闭了配送功能！');
        }

        //自提需要保存自提人信息
        $selfPickPerson = [];
        if ($is_pick_group == 0) {
            if ($selfPickId == 0) {
                returnCode(2, [], '请选择提货人');
            }
            $selfPickPerson = M('Self_pick_person')->where(['uid' => $this->_uid, 'id' => $selfPickId])->find();
            if (empty($selfPickPerson)) {
                returnCode(2, [], '提货人信息不存在，请重新选择');
            }
            $village_order['pick_name'] = $selfPickPerson['name'];
            $village_order['pick_phone'] = $selfPickPerson['phone'];
        }

        if ($is_pick_group>0 && $order['village_group_type'] != 1) {
            $village_order['is_pick_group'] = $is_pick_group;
            $address_id = $address_id>0?$address_id:0;
            $phone = isset($ouserTel) ? htmlspecialchars($ouserTel) : '';
            $name = isset($ouserName) ? htmlspecialchars($ouserName) : '';
            $arrive_time = isset($oarrivalTime) ? htmlspecialchars($oarrivalTime) : 0;
            $arrive_date = isset($oarrivalDate) ? htmlspecialchars($oarrivalDate) : 0;
            $address = isset($ouserAddres) ? htmlspecialchars($ouserAddres) : '';// 地址
            if ($address_id<=0) {
                returnCode(2, [], '地址信息不存在！');
            } else {
                $header = PD('Village_group')->get_header_info(0,$order['start_uid'],true);
                $header = $header['msg'];
                $user_address = M('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->_uid))->find();
                $pick_address = $header['pick_address'];
                if ($user_address['longitude'] > 0 && $user_address['latitude'] > 0) {
                    if ($pick_address['delivery_range_type'] == 0) {
                        $distance = getDistance($user_address['latitude'], $user_address['longitude'], $pick_address['lat'], $pick_address['long']);
                        $delivery_radius = $pick_address['delivery_radius'] * 1000;
                        if ($distance > $delivery_radius) {
                            returnCode(2, [], '您到团长的距离是' . $distance . '米,超过了' . $delivery_radius . '米的配送范围');
                        }
                    }
                } else {
                    returnCode(2, [], '您选择的地址没有完善，请先编辑地址');
                }
            }

            $village_order['username'] = $name?:$user_address['name'];
            $village_order['userphone'] = $phone?:$user_address['phone'];
            $village_order['address'] = $address?:$user_address['adress'].$user_address['detail'];
            $village_order['address_id'] = $address_id;
            $village_order['address_lat'] = $user_address['latitude'];
            $village_order['address_long'] = $user_address['longitude'];
            if ($arrive_date == 0) {
                $arrive_date = date('Y-m-d');
            }
            // 配送相关处理  暂时只存在平台配送
            $deliver_info = PD('Village_group')->village_deliver_fee();

            $start_time = $deliver_info['village']['delivertime_start'];
            $stop_time = $deliver_info['village']['delivertime_stop'];
            $start_time2 = $deliver_info['village']['delivertime_start2'];
            $stop_time2 = $deliver_info['village']['delivertime_stop2'];
            $start_time3 = $deliver_info['village']['delivertime_start3'];
            $stop_time3 = $deliver_info['village']['delivertime_stop3'];
            if ($start_time == $stop_time && $start_time == '00:00:00') {
                $stop_time = '23:59:59';
            }

            $arrive_time = strtotime($arrive_date . $arrive_time);
            if ($arrive_time == 0) {
                returnCode(2, [], '请选择配送时间');
            }
            $village_group_delivery_base_time = C('config.village_group_delivery_base_time') ? intval(C('config.village_group_delivery_base_time')) : 30; //默认30分钟

            $village_order['expect_send_time'] = $arrive_time ? $arrive_time : time() + $village_group_delivery_base_time * 60;//无值取默认的期望送达时间

            //计算配送费
            $distance = 0;
            if (C('config.is_riding_distance')) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $distance = $longlat_class->getRidingDistance($user_address['latitude'], $user_address['longitude'],  $pick_address['lat'], $pick_address['long']);
                if($distance == -1) returnCode(2, [], '请求不到您的具体定位，请重试！');

            }
            $distance || $distance = getDistance($user_address['latitude'], $user_address['longitude'],  $pick_address['lat'], $pick_address['long']);
            $village_order['delivery_distance'] = $distance;
            $village_order['delivery_weight'] = $goods_weight_all?:0;
            $distance = $distance / 1000;

            $expect_send_time = date('H:i:s', $village_order['expect_send_time']);
            if ($this->checkTime($expect_send_time, $start_time, $stop_time, 1)) {
                //时间段一
                $pass_distance = $distance > $deliver_info['basic_distance'] ? floatval($distance - $deliver_info['basic_distance']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight'] ? floatval($goods_weight_all - $deliver_info['basic_weight']) : 0;
                $deliver_info['delivery_fee'] += round($pass_distance * $deliver_info['per_km_price'], 2);
                $deliver_info['delivery_fee'] += round($pass_weight * $deliver_info['per_weight_price'], 2);
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee'] = $deliver_info['delivery_fee'] > 0 ? $deliver_info['delivery_fee'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee'];//运费
            } elseif ($this->checkTime($expect_send_time, $start_time2, $stop_time2, 2)) {
                //时间段二
                $pass_distance = $distance > $deliver_info['basic_distance2'] ? floatval($distance - $deliver_info['basic_distance2']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight2'] ? floatval($goods_weight_all - $deliver_info['basic_weight2']) : 0;
                $deliver_info['delivery_fee2'] += round($pass_distance * $deliver_info['per_km_price2'], 2);
                $deliver_info['delivery_fee2'] += round($pass_weight * $deliver_info['per_weight_price2'], 2);
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee2'] = $deliver_info['delivery_fee2'] > 0 ? $deliver_info['delivery_fee2'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee2'];//运费
            } elseif ($this->checkTime($expect_send_time, $start_time3, $stop_time3, 3)) {
                //时间段二
                $pass_distance = $distance > $deliver_info['basic_distance3'] ? floatval($distance - $deliver_info['basic_distance3']) : 0;
                $pass_weight = $goods_weight_all > $deliver_info['basic_weight3'] ? floatval($goods_weight_all - $deliver_info['basic_weight3']) : 0;
                $deliver_info['delivery_fee3'] += round($pass_distance * $deliver_info['per_km_price3'], 2);
                $deliver_info['delivery_fee3'] += round($pass_weight * $deliver_info['per_weight_price3'], 2);
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] - $deliver_info['delivery_fee_reduce'];
                $deliver_info['delivery_fee3'] = $deliver_info['delivery_fee3'] > 0 ? $deliver_info['delivery_fee3'] : 0;
                $delivery_fee = $village_order['delivery_fee'] = $deliver_info['delivery_fee3'];//运费
            }else {
                returnCode(2, [], '您选择的时间不在配送时间段内！');
            }

            switch (intval($this->config['count_freight_charge_method'])) {
                case 0:
                    break;
                case 1:
                    $delivery_fee = $village_order['delivery_fee'] = ceil($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 2:
                    $delivery_fee = $village_order['delivery_fee'] = floor($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 3:
                    $delivery_fee = $village_order['delivery_fee'] = round($village_order['delivery_fee'] * 10) / 10;
                    break;
                case 4:
                    $delivery_fee = $village_order['delivery_fee'] = ceil($village_order['delivery_fee']);
                    break;
                case 5:
                    $delivery_fee = $village_order['delivery_fee'] = floor($village_order['delivery_fee']);
                    break;
                case 6:
                    $delivery_fee = $village_order['delivery_fee'] = round($village_order['delivery_fee']);
                    break;
            }
            if ($village_order['delivery_fee']>0) {
                $total_money += $delivery_fee;
            }
            $village_order['village_group_type']=2;
        }


        // 修改表格 pigcms_plat_order 数据
        $save_info = array();
        // 实际支付金额
        if ($total_money>0 && $total_money != $plat_order_info['total_money']) {
            $save_info['total_money'] = $total_money;
        } elseif ($total_money<=0 && $plat_order_info['total_money']>0) {
            $save_info['total_money'] = 0;
        }
        // 积分使用
        if ($score_can_use_count>0 && $score_can_use_count != $order['score_used_count']) {
            $village_order['score_used_count'] = $score_can_use_count;
        } elseif ($score_can_use_count<=0) {
            $village_order['score_used_count'] = 0;
        }
        // 积分抵扣
        if ($score_deducte>0 && $score_deducte != $order['score_deducte']) {
            $village_order['score_deducte'] = $score_deducte;
        } elseif ($score_deducte <= 0) {
            $village_order['score_deducte'] = 0;
        }
        if ($save_info) {
            M('Plat_order')->where(array('business_id' => $order['order_id'], 'business_type' => $type_))->data($save_info)->save();
        }
        $village_order['note'] = $note? $note:'';//备注信息
        if(count($time_arr) == 2){
            $village_order['get_start_time'] = $time_arr[0].":00";
            $village_order['get_end_time'] = $time_arr[1].":00";
        } 
        if ($village_order) {
            M('Village_group_order')->where(array('order_id' => $order['order_id']))->data($village_order)->save();
            if ($village_order['village_group_type']==2) {
                M('Village_group_order_detail')->where(array('order_id' => $order['order_id']))->setField('village_group_type',2);
            }
        }

        if ($order['order_id']>0) {
            PD('Village_group_order')->clear_cart($order['order_id'], ['checked' => 1]);
        }

        if(C('config.village_group_stock_reduce_type') == 1 && $goods_count && !$realOrderInfo){
            //商品减少库存，增加锁定库存
            $updateStockAry = [];
            foreach($goods_count as $kNum => $vNum){
                $updateStockAry[] = [
                    'goods_id'=>$kNum,
                    'lock_stock_num'=>$vNum,
                    'lock_stock_type'=>1
                ];
            }
            D("Village_group_goods")->updateStock($updateStockAry);
        }
        returnCode(0, ['order_id'=>$order['plat_order']['order_id']]);
    }

    public function checkTime($time, $start_time, $stop_time, $select)
    {
        $time = strtotime(date('Y-m-d ' . $time));
        $stime = strtotime(date('Y-m-d ' . $start_time));
        $etime = strtotime(date('Y-m-d ' . $stop_time));
        $next_stime = 0;
        $next_etime = 0;
        if ($etime < $stime) {
            $etime = strtotime(date('Y-m-d 23:59:59'));
            $next_stime = strtotime(date('Y-m-d'));
            $next_etime = strtotime(date('Y-m-d ' . $stop_time));
        }

        if ($stime <= $time && $time <= $etime) {
            return $select;
        }
        if ($next_stime <= $time && $time <= $next_etime) {
            return $select;
        }
        return 0;
    }

    //获取当前团长与上一次购物团长的位置差异
    public function get_difference(){
        $lat = I("lat");
        $lng = I("lng");
        //上一次购物团长  不论什么状态，只要下过单就行
        $last_order = M("Village_group_order")->where(['uid' => $this->_uid])->field("start_uid")->order("add_time desc")->find();

        if(empty($last_order) || $last_order['start_uid'] == $this->leader_id){
            returnCode(0, []);
        }
        
        $output = [
            'current_leader' => [],
            'last_leader' => []
        ];

        $leaders = M("Village_group_header")->alias("h")->join("left join ".C("DB_PREFIX")."village_group_pick_address as a on a.header_uid=h.id")->join("left join ".C("DB_PREFIX")."user as u on u.uid=h.uid")->where(['h.id'=>['in', [$this->leader_id, $last_order['start_uid']]],'h.status'=>1])->field('a.long,a.lat,h.name,a.province_id,a.city_id,a.area_id,a.pick_addr as address,u.avatar,h.id')->select();

        $areaids = array_merge(array_column($leaders, 'province_id'),array_column($leaders, 'city_id'),array_column($leaders, 'area_id'));
        $areas = array_column(M("Area")->where(['area_id' => ['in', $areaids]])->field("area_id,area_name")->select(), 'area_name', 'area_id');
        foreach ($leaders as $key => $value) {
            if($value['province_id'] && $areas[$value['province_id']]){
                $value['province'] = $areas[$value['province_id']];
            }
            if($value['city_id'] && $areas[$value['city_id']]){
                $value['city'] = $areas[$value['city_id']];
            }
            if($value['area_id'] && $areas[$value['area_id']]){
                $value['area'] = $areas[$value['area_id']];
            }
            if (!$value['avatar']) {
                $value['avatar'] = C('config.site_url') . '/static/qrcode/wxapp/avatar.png';
            } else {
                if(stripos($value['avatar'],'/upload/') === 0){
                    $value['avatar'] = file_domain().$value['avatar'];
                }
                else{
                    $value['avatar'] = replace_file_domain($value['avatar']);
                }
            }
            $value['juli'] = 0;
            if($lat && $lng){
                $value['juli'] = $this->getDistance($value['lat'], $value['long'], $lat, $lng)['distance'];
            }
            if($value['id'] == $this->leader_id){
                $output['current_leader'] = $value;
            }
            else{
                $output['last_leader'] = $value;
            }
        }
        if(empty($output['last_leader'])){
            $output['last_leader'] = (object)$output['last_leader'];
        }
        if(empty($output['current_leader'])){
            $output['current_leader'] = (object)$output['current_leader'];
        }
        returnCode(0, $output);
    }


    public function getDistance($lat1=0,$lng1=0,$lat2=0,$lng2=0)
    {
        $ak = $this->config['baidu_map_ak'];//您的百度地图ak，可以去百度开发者中心去免费申请  
        $distance = array();
        $distance['distance'] = 0.00;//距离 公里
        $distance['duration'] = 0.00;//时间 分钟
        $url = 'http://api.map.baidu.com/routematrix/v2/driving?output=json&origins='.$lat1.','.$lng1.'&destinations='.$lat2.','.$lng2.'&ak='.$ak;
        $data = file_get_contents($url);
        $data = json_decode($data,true);
        if (!empty($data) && $data['status'] == 0) {
            $distance['distance'] =preg_replace('/[^\.0123456789]/s', '', $data['result'][0]['distance']['text']); //计算距离
        }
        return $distance;
    }

    //获取最近可配送时间
    public function lately_cycle_time() {
        $goods_id=$_POST['goods_id']>0?intval($_POST['goods_id']):0;
        $time = $_POST['time']>0?intval($_POST['time']):0;
        if (!$goods_id || !$time) {
            returnCode(1,[], '参数错误');
        }
        $lately_cycle_time = D('Village_group_goods')->lately_cycle_time($goods_id,$time);
        $output = array('lately_cycle_time' => $lately_cycle_time);
        returnCode(0, $output);
    }

    //随机派发用户优惠券
    public function rand_coupon(){
        if($this->_uid){
            $now_user = PD('User')->get_user($this->_uid);
        }else{
            returnCode(0, []);
        }

        if($this->config['open_rand_send']){
            $where = array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$now_user['uid'],'from'=>'app','city_id'=>$now_user['city_id']);
            $where['cate_name'] = 'village_group';
            $coupon_html = PD('System_coupon')->rand_send_coupon_get($where);
            if(empty($coupon_html)){
                returnCode(0, []);

            }else{
                returnCode(0, $coupon_html);
            }

        }else{
            returnCode(0, []);
        }
    }

    //判断自提时间与团长取货时间的差异接口
    public function get_time_diff(){
        $lng = I("lng");
        $lat = I("lat");
        $time = trim(I("time"));
        $time_arr = explode('-', $time);
        $order_id = intval(I("order_id"));
        if(empty($order_id) || count($time_arr) != 2){
            returnCode(1,[], '参数错误');
        }
        $order  = PD('Village_group_order')->order_detail($order_id,$this->leader_id);
        $header = PD('Village_group')->get_header_info(0,$order['start_uid'],true);
        $header  =$header['msg'];
        $t[0] = strtotime(date('Y-m-d '.$time_arr[0].':00'));
        $t[1] = strtotime(date('Y-m-d '.$time_arr[1].':00'));
        $start_time = strtotime(date('Y-m-d '.$header['start_time']));
        $end_time = strtotime(date('Y-m-d '.$header['end_time']));

        if($t[0] > $start_time && $t[1] < $end_time){//时间吻合（用户选择的自提时间跟团长取货时间吻合）
            returnCode(0, ['verify' => 1, 'msg' => '', 'data' => []]);//通过
        }
        else{//如果时间不吻合，则推荐其他团长
            $condition['h.status'] = 1;
            $condition['s.status'] = 1;
            $condition['h.start_time'] = ['elt', $time_arr[0].':00'];
            $condition['h.end_time'] = ['egt', $time_arr[1].':00'];
            $field = "h.id as leader_id,h.name,s.name as village_name";
            if($lng && $lat){
                $field .= ",ROUND(12756.276 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
            }
            $header = M('Village_group_pick_address')->field($field)->join('as s LEFT JOIN '.C('DB_PREFIX').'village_group_header as h ON s.header_uid = h.id LEFT JOIN '.C('DB_PREFIX').'user as u ON u.uid = h.uid')->where($condition)->order('h.sort desc')->select();
            if($lng && $lat){
                $temp = [];
                foreach ($header as &$item) {
                    if($item['juli'] <= 1000){//如果有用户定位，只取一公里以内的（需求）
                        $temp[] = $item;
                    }
                }
                $header = $temp;
            }
            if(empty($header)){
                returnCode(0, ['verify' => 2, 'msg' => '该时间段内，没有可服务团长，请重新选择取货时间', 'data' => []]);//不通过，且没有符合条件的团长
            }
            returnCode(0, ['verify' => 3, 'msg' => '当前团长不在服务时间范围内，请选择其他团长', 'data' => $header]);//不通过，返回其他团长
        }
    }

    //从临时表更新数据到正式表
    private function _tempToOrder($order_id, $order)
    {
        $order_where = ['order_id'=>$order_id];
        $realOrderInfo =M('Village_group_order')->where($order_where)->find();
        if($realOrderInfo){
            $stock_reduce_type = $realOrderInfo['stock_reduce_type'];
        }
        M('Village_group_order')->where($order_where)->delete();
        M('Village_group_order_detail')->where($order_where)->delete();
        M('System_order')->where(['order_id'=>$order_id,'type'=>'village_group'])->delete();

 
        $info = M('Village_group_order_temp')->where($order_where)->find();
        
        $liveCommission = function ($goodsId = 0)use($info){
            $commission = 0;
            if(!empty($info['live_id']) && $info['live_type'] == 'live_show'){//直播下单
                $liveCommission = M('Live_goods')
                    ->alias("a")
                    ->join(C('DB_PREFIX').'live_show_goods lg on a.id = lg.goods_id')
                    ->join(C('DB_PREFIX')."village_group_goods b ON a.business_id = b.goods_id")
                    ->field('sum(a.percent*b.price) live_commission')
                    ->where([
                        'lg.live_id' => $info['live_id'],
                        'a.business' => 'live_show',
                        'a.status' => 1
                    ]);
                if($goodsId){
                    $liveCommission = $liveCommission->where([
                        'b.goods_id' => $goodsId
                    ]);
                }
                $liveCommission = $liveCommission->find();
                $commission = bcdiv($liveCommission['live_commission'],100,2);//直播下单佣金
            }
            return $commission;
        };
        $info['live_commission'] = $liveCommission();
        $info['stock_reduce_type'] = $stock_reduce_type ?? (C('config.village_group_stock_reduce_type')?:0);
        M('Village_group_order')->data($info)->add();

        $order_detail_temp = D('Village_group_order_detail_temp')->where(['order_id' => $order_id])->select();
        if ($order_detail_temp) {
            $order_detail_temp = array_map(function($r)use($liveCommission){
                unset($r['id']);
                $r['live_commission'] = $liveCommission($r['goods_id']);
                return $r;
            },$order_detail_temp);
            
            D('Village_group_order_detail')->addAll($order_detail_temp);
            fdump_api(M()->getLastSql(),'villagegroup/confirmorder',true);
        }

  
        $nums = 0;
        $goods_name = [];
        foreach($order['order_detail'] as $val){
            $nums += $val['num'];
            $goods_name[] = $val['name'];
        }
        

        $info['num'] = $nums;
        $info['keywords'] = rtrim(implode(',', $goods_name),','); 
        PD('System_order')->add_order('village_group',$info,$order_id); 
    }

}