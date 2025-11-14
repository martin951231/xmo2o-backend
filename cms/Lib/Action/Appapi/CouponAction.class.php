<?php
/**
 * 优惠券改版
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/11/23 10:25
 */


class CouponAction extends BaseAction
{
    /**
     * 已领取的优惠券列表
     * User: chenxiang
     * Date: 2020/11/23 10:43
     */
    public function myCouponList()
    {
        $uid = $this->_uid;
        if (empty($uid)) {
            $this->returnCode(0, []);
        }
        $now_user = D('User')->get_user($this->_uid);

        //合并 商家+平台优惠券
        $status = I('status', 1); // 1未使用 2已过期 3已使用
        $mer_id = I('mer_id'); //商家id
        $from = I('from') ?? 0; //来源，1=跑腿
        $where = [];
        $where['status'] = $status;
        if ($from == 1) {
            $where['cate_name'] = 'deliver';
        }
        $mer_id && $where['mer_id'] = $mer_id;

        $mer_jump_url = '';
        $is_store_list = true;

        //抖音探店来源
        $activity_id = I('post.activity_id', 0, 'intval');
        $isDouyinActivity = false;
        if ($activity_id > 0 && $from == 'douyi_shop') {
            $isDouyinActivity = true;
            $hadpullIds = D('Douyin_activity_coupon_receive_record')->where(['uid' => $this->_uid, 'activity_id' => $activity_id])->getField('hadpull_id', true);
            if(empty($hadpullIds)){
                $this->returnCode(0, ['coupon_list' => [], 'coupon_button' => []]);
            }else{
                $where['hadpull_ids'] = $hadpullIds;
            }
        }

        if (!empty($mer_id) || $isDouyinActivity) {
            //仅仅显示 商家优惠券
            $coupon_list_mer = D('Card_new_coupon')->get_user_all_coupon_list_where($this->_uid, $where);

            $merchantStoreModel = M('Merchant_store');
            $store_ids = [];
            foreach ($coupon_list_mer as $key => $value) {
                $merchant_info = M('Merchant')->field('name')->where(['status' => 1, 'mer_id' => $value['mer_id']])->find();
                $coupon_list_mer[$key]['merchant_name'] = $merchant_info['name']; //商家名称
                $store = explode(',', $value['store_id']);
                $isShow = false;
                foreach($store as $v){
                    if($merchantStoreModel->where(['store_id'=>$v, 'status' => 1])->count()){
                        $isShow = true;
                        break;
                    }
                }
                if(!$isShow){
                    unset($coupon_list_mer[$key]);
                    continue;
                }
                if (count($store) == 1) {  //单店铺
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $value['store_id'];
                    //店铺id 集合
                    $store_ids[] = $value['store_id'];
                } else { //多店铺
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'];
                    $store_ids[] = $value['store_id'];
                }
                if ($isDouyinActivity) {
                    $coupon_list_mer[$key]['coupon_url'] = $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/activity/probeShop/couponDetail?hadpull_id=' . $value['id'] . '&activity_id=' . $activity_id;
                }
                if($value['store_direct_verification']==1){
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=mer&user_had_id='.$value['id'].'&get_store_list=1';
                }
            }
            $coupon_list_mer = array_values($coupon_list_mer);

            if (empty($store_ids)) { //全店铺 跳店铺列表
                $mer_jump_url = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList';
                $is_store_list = true; //店铺列表页
            } else {
                $store_ids_unique = array_unique($store_ids);
                if (count(array_unique($store_ids_unique)) == 1) {
                    //单店铺 且 是同一个店铺 跳转店铺详情页
                    $mer_jump_url = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $store_ids_unique[0];
                    $is_store_list = false; //单店铺详情页
                } else { //单店铺 不是同一店铺 跳转店铺列表页
                    $mer_jump_url = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList';
                    $is_store_list = true; //店铺列表页
                }
            }

            $coupon_list = $coupon_list_mer;
        } else { // 合并 商家+平台券
            $coupon_list_mer = D('Card_new_coupon')->get_user_all_coupon_list_where($this->_uid, $where);

//        $coupon_list_mer_my = [];
            $merchantStoreModel = M('Merchant_store');
            foreach ($coupon_list_mer as $key => $value) {
                $merchant_info = M('Merchant')->field('name')->where(['status' => 1, 'mer_id' => $value['mer_id']])->find();
                $coupon_list_mer[$key]['merchant_name'] = $merchant_info['name']; //商家名称
                $store = explode(',', $value['store_id']);
                $isShow = false;
                foreach($store as $v){
                    if($merchantStoreModel->where(['store_id'=>$v, 'status' => 1])->count()){
                        $isShow = true;
                        break;
                    }
                }
                if(!$isShow){
                    unset($coupon_list_mer[$key]);
                    continue;
                }
                if (count($store) == 1) {  //单店铺
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $value['store_id'];
                } else { //多店铺
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'];
                }
                if($value['store_direct_verification']==1){
                    $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=mer&user_had_id='.$value['id'].'&get_store_list=1';
                }
            }
            $coupon_list_mer = array_values($coupon_list_mer);


            //平台优惠券
            $coupon_list_system = D('System_coupon')->get_user_coupon_list_where($this->_uid, $now_user['phone'], $where);

//        $coupon_list_system_my = [];
            foreach ($coupon_list_system as $key => $value) {
                $get_store_list = 0;
                if ($value['use_type'] == 1) {
                    $get_store_list = 1;
                    $store_ids = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $value['coupon_id']])->select();
                    if (count($store_ids) == 1) { //单店铺
                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                    } else { //多店铺
                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system';
                    }
                } else {
                    if ($value['cate_name'] == '社区拼团') { //跳转 社区团购首页
                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                    } else {
                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . D('House_village')->home_url;;
                    }
                }
                if($value['store_direct_verification']==1){
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system&user_had_id='.$value['id'].'&get_store_list='.$get_store_list;
                }
            }

            //合并
            if ($from != 1) {//配送优惠券只取平台的
                $coupon_list = $coupon_list_mer;
            }
            foreach ($coupon_list_system as $key => $value) {
                $coupon_list[] = $value;
            }

            //转赠券
            $card_new_coupon_list = D('System_coupon')->get_user_card_new_coupon($now_user, $where);

        }

        $tmp = [];
        $tmp_combine = [];
        foreach ($coupon_list as $key => $v) {
            //查看使用期限是否到期
            // 用户可使用的天数
            if ($v['valid_time']) {
                if ($v['receive_time'] < $v['start_time']) {
                    $end_time = $v['start_time'] + $v['valid_time'] * 86400;
                } else {
                    $v['start_time'] = $v['receive_time'];
                    $end_time = $v['receive_time'] + $v['valid_time'] * 86400;
                }
                $v['end_time'] = $end_time > $v['end_time'] ? $v['end_time'] : $end_time;
                if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                    $v['is_use'] = 2; //不能使用的
                }
            }


            if ($v['coupon_type'] == 'mer') {   //商家券
                if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                } else {
                    $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    $mer = M('Merchant')->where(array('mer_id' => $v['mer_id']))->find();
                    $tmp[$v['is_use']][$v['coupon_id']]['merchant'] = $mer['name'];
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
                    switch ($v['type']) {
                        case 'all':
                            $url = $this->config['site_url'] . '/wap.php';
                            break;
                        case 'group':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Group&a=index';
                            break;
                        case 'meal':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Meal_list&a=index';
                            break;
                        case 'appoint':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Appoint&a=index';
                            break;
                        case 'shop':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index';
                            break;
                        case 'mall':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=index';
                            break;
                        case 'village_group':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                            break;
                    }
                    $tmp[$v['is_use']][$v['coupon_id']]['url'] = $url;
                }

                $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = '';
            } elseif ($v['coupon_type'] == 'system') {
                if($v['membership_record_id'] > 0 && empty($v['img'])){
                    $v['img'] = $this->config['site_url'].'/static/membership_card_coupon.png';
                }

                if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                } else {
                    $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    $mer = M('Merchant')->where(array('mer_id' => $v['mer_id']))->find();
                    $tmp[$v['is_use']][$v['coupon_id']]['merchant'] = $mer['name'];
                    $tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
                    switch ($v['type']) {
                        case 'all':
                            $url = $this->config['site_url'] . '/wap.php';
                            break;
                        case 'group':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Group&a=index';
                            break;
                        case 'meal':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Meal_list&a=index';
                            break;
                        case 'appoint':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Appoint&a=index';
                            break;
                        case 'shop':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index';
                            break;
                        case 'mall':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=index';
                            break;
                        case 'village_group':
                            $url = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                            break;
                    }
                    $tmp[$v['is_use']][$v['coupon_id']]['url'] = $url;
                }

                $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = '';
            }

            if ($v['use_type'] == 1) {
                $store_names = M('System_coupon_store')->alias('cs')
                    ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = cs.store_id')
                    ->field('s.name')
                    ->where(['cs.coupon_id' => $v['coupon_id']])
                    ->order('cs.id ASC')
                    ->limit(2)
                    ->getField('name', true);
                if ($store_names) {
                    $tmp[$v['is_use']][$v['coupon_id']]['designated_shop'] = count($store_names) > 1 ? $store_names[0] . L_('等店铺') : $store_names[0];
                }
            }

            //合并 优惠券
            if ($v['is_use'] == 0) { //未使用的 去除掉 过期的
                unset($tmp[2]);
            }
            $tmp_combine[] = $tmp[$v['is_use']][$v['coupon_id']];
            unset($tmp[$v['is_use']][$v['coupon_id']]);
        }

        $coupon_list_my = [];
        //处理合并后 接口统一返回
        foreach ($tmp_combine as $kk => $vv) {
            $coupon_list_my[$kk]['is_discount'] = intval($vv['is_discount']); //是否是折扣券
            $coupon_list_my[$kk]['discount_value'] = floatval($vv['discount_value']); //折扣
            $coupon_list_my[$kk]['img'] = $vv['img']?replace_file_domain($vv['img']):C('config.site_url').'/static/images/coupon.png'; //优惠券图片  或者  img_coupon
            $coupon_list_my[$kk]['name'] = $vv['name']; //优惠券名称
            $coupon_list_my[$kk]['des_detial'] = $vv['des_detial']; //使用说明

            if ($vv['coupon_type'] == 'mer') { //商家券
                $merchant_info = M('Merchant')->field('name,pic_info')->where([ 'mer_id' => $vv['mer_id']])->find();

                $images = explode(";", $merchant_info['pic_info']);
                $merchant_image_class = new merchant_image();
                $images = explode(";", $images[0]);
                $pic = $merchant_image_class->get_image_by_path($images[0]);

                $coupon_list_my[$kk]['store_img'] = $pic; //商家logo
                $coupon_list_my[$kk]['plat_name'] = $merchant_info['name']?:''; //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'merchant'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
                $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['receive_time']); //使用开始时间 (领取时间)
                $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['end_time']); //使用结束时间 (优惠券过期时间)
            } elseif ($vv['coupon_type'] == 'system') { //平台券
                $coupon_list_my[$kk]['store_img'] = C('config.wechat_share_img'); //平台正方形logo
                $coupon_list_my[$kk]['plat_name'] = C('config.site_name'); //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'system'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
                $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['start_time']); //使用开始时间
                $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['end_time']); //使用开始时间
            }

            $coupon_list_my[$kk]['discount'] = floatval($vv['discount']); //优惠金额
            $coupon_list_my[$kk]['order_money'] = floatval($vv['order_money']); //门槛
            $coupon_list_my[$kk]['had_id'] = $vv['coupon_id']; // 优惠券id
            $coupon_list_my[$kk]['had_percent'] = intval(($vv['had_pull'] / $vv['num']) * 100); //已领取的百分比
            $coupon_list_my[$kk]['is_used'] = $vv['is_use']; //是否已使用 0-正常，1-已使用，2-不能使用，3-赠送中

            //判断是否快过期
            if (time() > $vv['end_time']) { //已过期
                $is_discount = 1;
            } else {
                if (time() + 86400 > $vv['end_time']) { //快过期
                    $is_discount = 2;
                } else { //未过期
                    $is_discount = 0;
                }
            }
            $coupon_list_my[$kk]['is_time_out'] = $status==2?0:$is_discount; //是否过期   三种状态; 0未过期 1已过期 2快过期
            if(empty($vv['coupon_url'])){
                $coupon_list_my[$kk]['url'] = $vv['jump_url']; //点击立即使用的跳转链接
            }else{
                $coupon_list_my[$kk]['url'] = $vv['coupon_url']; //点击立即使用的跳转链接
            }

            $coupon_list_my[$kk]['coupon_start_time'] = date('Y.m.d', $vv['start_time']); //优惠券有效期-开始时间
            $coupon_list_my[$kk]['coupon_end_time'] = date('Y.m.d', $vv['end_time']); //优惠券有效期-结束时间

            if (!empty($vv['cate_name'])) {
                if($vv['cate_name']  == 'new_user') {
                    $coupon_list_my[$kk]['use_limit'] = '新人券';
                } else {
                    $coupon_type_arr = D('System_coupon')->cate_platform();
                    if($vv['cate_name'] == 'all') {
                        $coupon_list_my[$kk]['use_limit'] =  $coupon_type_arr['category'][$vv['cate_name']];
                    } else {
                        $coupon_list_my[$kk]['use_limit'] =  $coupon_type_arr['category'][$vv['cate_name']] . '使用';
                    }
                }
            }
            $coupon_list_my[$kk]['user_had_id'] = $vv['id']; //优惠券领取id
        }
		M('User_notice')->where(['uid' => $this->_uid, 'is_read' => 0, 'business' => 'mer_coupon'])->save(['is_read' => 1, 'read_time' => time()]);
        if (!empty($mer_id) || $isDouyinActivity) {
            $coupon_button = [];
            $coupon_button['mer_jump_url'] = $mer_jump_url;
            $coupon_button['is_store_list'] = $is_store_list;
            $this->returnCode(0, ['coupon_list' => array_values($coupon_list_my), 'coupon_button' => $coupon_button]);
        } else {
            //转赠券
            $coupon_list_my = array_merge($coupon_list_my,$card_new_coupon_list);
            $this->returnCode(0, ['coupon_list' => array_values($coupon_list_my)]);
        }
    }
    /*
     *  淼迈领券列表
     */
    public function miaomaiCouponList()
    {
        import("@.ORG.Erp.MiaomaiErpService");
        $user = D("User")->where(['uid' => $this->_uid])->find();
        $page = I('page', 1);
        if(empty($user['phone'])){
            throw_exception('请先绑定手机号');
        }
        if(empty(I('store_id'))){
            throw_exception('店铺id不能为空');
        }
        $where = [
            'is_expired' => I('is_expired'),
            'use_status' => I('use_status'),
            'store_id' => I('store_id'),
        ];
        $couponList = MiaomaiErpService::getMiaomaiCouponList($user['phone'], $page, $where);
        $this->returnCode(0, $couponList);
	}
    public function miaomaiStoreList()
    {
        $storeList = D('Merchant')
            ->alias('m')
            ->join('INNER JOIN '.C("DB_PREFIX").'merchant_store s', 'm.mer_id = s.mer_id')
            ->where([
                'm.status' => 1,
                'm.miaomaiyun_switch' => 1,
                's.status' => 1,
                's.miaomaiyun_erp_storeid' => ['gt', 0],
                'm.miaomaiyun_erp_appid' => ['neq', ''],
                'm.miaomaiyun_erp_appsecret' => ['neq', ''],
                'm.miaomaiyun_url' => ['neq', ''],
            ])
            ->field('s.miaomaiyun_erp_storeid, s.name')
            ->select();
        
        $this->returnCode(0, $storeList);
    }
    /**
     * 已领页面统计tab接口
     * User: chenxiang
     * Date: 2020/11/23 18:51
     */
    public function myCouponListNum()
    {
        $uid = $this->_uid;
        if (empty($uid)) {
            $this->returnCode(1001, [], '请先登录~');
        }
        $now_user = D('User')->get_user($this->_uid);

        //合并 商家+平台优惠券
        $mer_id = I('mer_id'); //商家id
        $where = [];
        $mer_id && $where['mer_id'] = $mer_id;
        $from = I('from') ?? 0; //来源，1=跑腿
        if ($from == 1) {
            $where['cate_name'] = 'deliver';
        }

        $status = ['1' => L_('未使用'), '3' => L_('已过期'), '2' => L_('已使用')];
        $tab_list = [];
        foreach ($status as $key => $value) {
            $where['status'] = $key;

            //mer_id存在 则仅显示商家优惠券
            if (!empty($mer_id)) {
                //商家优惠券
                $coupon_list_mer = D('Card_new_coupon')->get_user_all_coupon_list_where($this->_uid, $where);
                $merchantStoreModel = M('Merchant_store');
                foreach($coupon_list_mer as $k => $val){
                    $store = explode(',', $val['store_id']);
                    $isShow = false;
                    foreach($store as $v){
                        if($merchantStoreModel->where(['store_id'=>$v, 'status' => 1])->count()){
                            $isShow = true;
                            break;
                        }
                    }
                    if(!$isShow){
                        unset($coupon_list_mer[$k]);
                        continue;
                    }
                }
                $coupon_list_mer = array_values($coupon_list_mer);
                $coupon_list = $coupon_list_mer;
            } else {
                //商家优惠券
                $coupon_list_mer = D('Card_new_coupon')->get_user_all_coupon_list_where($this->_uid, $where);
                 $merchantStoreModel = M('Merchant_store');
                foreach($coupon_list_mer as $k => $val){
                    $store = explode(',', $val['store_id']);
                    $isShow = false;
                    foreach($store as $v){
                        if($merchantStoreModel->where(['store_id'=>$v, 'status' => 1])->count()){
                            $isShow = true;
                            break;
                        }
                    }
                    if(!$isShow){
                        unset($coupon_list_mer[$k]);
                        continue;
                    }
                }
                $coupon_list_mer = array_values($coupon_list_mer);

                //平台优惠券
                $coupon_list_system = D('System_coupon')->get_user_coupon_list_where($this->_uid, $now_user['phone'], $where);
                if($key == 1) { //未使用时
                    foreach ($coupon_list_system as $k => $v) {
                        if ($v['valid_time']) {
                            if ($v['receive_time'] < $v['start_time']) {
                                $end_time = $v['start_time'] + $v['valid_time'] * 86400;
                            } else {
                                $v['start_time'] = $v['receive_time'];
                                $end_time = $v['receive_time'] + $v['valid_time'] * 86400;
                            }
                            $v['end_time'] = $end_time > $v['end_time'] ? $v['end_time'] : $end_time;
                            if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                                unset($coupon_list_system[$k]);
                            }
                        }
                    }
                }

                //合并
                if ($from != 1) {//配送优惠券只取平台的
                    $coupon_list = $coupon_list_mer;
                }
                foreach ($coupon_list_system as $kk => $vv) {
                    $coupon_list[] = $vv;
                }
            }

            $tmp = [];
            $tmp_combine = [];
            foreach ($coupon_list as $k => $v) {
                //查看使用期限是否到期
                // 用户可使用的天数
                if ($v['valid_time']) {
                    if ($v['receive_time'] < $v['start_time']) {
                        $end_time = $v['start_time'] + $v['valid_time'] * 86400;
                    } else {
                        $v['start_time'] = $v['receive_time'];
                        $end_time = $v['receive_time'] + $v['valid_time'] * 86400;
                    }
                    $v['end_time'] = $end_time > $v['end_time'] ? $v['end_time'] : $end_time;
                    if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                        $v['is_use'] = 2; //不能使用的
                    }
                }

                if ($v['coupon_type'] == 'mer') {   //商家券
                    if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                        $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                    } else {
                        $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    }
                } elseif ($v['coupon_type'] == 'system') {
                    if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                        $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
                    } else {
                        $tmp[$v['is_use']][$v['coupon_id']] = $v;
                    }
                }

                //合并 优惠券
                if ($v['is_use'] == 0) { //未使用的 去除掉 过期的
                    unset($tmp[2]);
                }

                $tmp_combine[] = $tmp[$v['is_use']][$v['coupon_id']];
                unset($tmp[$v['is_use']][$v['coupon_id']]);
            }

            $card_new_coupon_list = [];
            if (!$mer_id) {
                //转赠券
                $card_new_coupon_list = D('System_coupon')->get_user_card_new_coupon($now_user, $where);
            }

            $num = count($tmp_combine) + count($card_new_coupon_list);
            $tab_list[] = [
                'name' => $value,
                'value' => $key,
                'count' => $num
            ];
        }

        //时间过期的需要处理
        $this->returnCode(0, ['tab_list' => $tab_list]);
    }


    /**
     * 优惠券搜索关键词列表
     * User: chenxiang
     * Date: 2020/11/23 21:09
     */
    public function getHotKeywords()
    {
        $list = M('Search_hot_coupon_words')->field('name')->order('sort DESC')->select();

        $this->returnCode(0, ['list' => $list]);
    }


    /**
     * 店铺装修页基本信息
     * User: chenxiang
     * Date: 2020/11/24 13:55
     */
        public function getCouponIndex()
    {
        $tm = time();
        $city_id = intval($_POST['city_id']);
        if (empty($city_id)) {
            $this->returnCodeError('请选择城市');
        }

        //获取主题颜色
        $theme_color = C('config.coupon_main_color');

        //我的已领优惠券数量
        $uid = $this->_uid;
        if (empty($uid)) {  //未登录
            $mer_number = 0;
        } else { //登录后
            $now_user = D('User')->get_user($this->_uid);
            //	商家优惠券
            $mer_list = D('Card_new_coupon')->get_user_all_coupon_list($uid, 1);
            if ($mer_list) {
                $mer_number = count($mer_list);
            } else {
                $mer_number = 0;
            }

            //平台优惠券
            $coupon_list = D('System_coupon')->get_user_coupon_list($uid, $now_user['phone'], 1);
            foreach ($coupon_list as $key => $v) {
                if ($v['valid_time']) {
                    if ($v['receive_time'] < $v['start_time']) {
                        $end_time = $v['start_time'] + $v['valid_time'] * 86400;
                    } else {
                        $v['start_time'] = $v['receive_time'];
                        $end_time = $v['receive_time'] + $v['valid_time'] * 86400;
                    }
                    $v['end_time'] = $end_time > $v['end_time'] ? $v['end_time'] : $end_time;
                    if ($v['end_time'] < $_SERVER['REQUEST_TIME'] && $v['is_use'] != 1) {
                        unset($coupon_list[$key]);
                    }
                }
            }

            //商家优惠券 + 平台优惠券 合并
            $mer_number = $mer_number + count($coupon_list);
        }

        //分享信息
        $share_data = [];
        $share_data['share_img'] = empty(C('config.coupon_main_share_pic')) ? '' : replace_file_domain(C('config.coupon_main_share_pic'));
        $share_data['share_title'] = empty(C('config.coupon_main_title')) ? '' : C('config.coupon_main_title');
        $share_data['share_content'] = empty(C('config.coupon_main_sub_title')) ? '' : C('config.coupon_main_sub_title');

        //轮播图
        $adv_img = D('Adver')->get_adver_by_key_city('wap_coupon_index', 5, $city_id);

        //分类列表
        $category_list = [];
        //品牌精选
        $coupon_list = M('Combine_coupon_list')->field('id')->select();
        if ($coupon_list) {
            $category_list[0]['title'] = empty(C('config.coupon_main_brand_select_alias')) ? '品牌精选' : C('config.coupon_main_brand_select_alias');
            $category_list[0]['type'] = 'index';
        }

        //平台券
        $category_list[1]['title'] = C('config.coupon_main_system_coupon_alias')??'平台券';
        $category_list[1]['type'] = 'system';

        //商家店铺分类
        $store_cate = M('Merchant_category')->field('cat_id,cat_name,cat_url')->where(['cat_status' => 1, 'cat_fid' => 0])->order('cat_sort DESC, cat_id ASC')->select();

        foreach ($store_cate as $key => $value) {
            $key = $key + 2;
            $category_list[$key]['id'] = $value['cat_id'];
            $category_list[$key]['title'] = $value['cat_name'];
            $category_list[$key]['type'] = 'merchant';

            //需要判断分类下有无优惠券  无 则不显示分类
            $where = [];
            $where['cat_fid'] = $value['cat_id'];
            $where['city_id'] = [['eq', 0], ['eq', $city_id], 'or'];
            $where['status'] = 1;
            //分类下的店铺
            $store_info = M('Merchant_store')->field('mer_id,store_id')->where($where)->select();

            $is_show = false;
            if (count($store_info) > 0) {
                //优惠券包含的店铺
                foreach ($store_info as $item) {
                    $condition['status'] = 1;
                    $condition['allow_im_get'] = 0;
                    $condition['is_live'] = 0;
                    $condition['is_receive_full_reduction'] = 0;
                    $condition['send_type'] = array('neq', 2);

                    $condition['mer_id'] = $item['mer_id'];
                    $condition['end_time'] = array('gt', $tm);
                    $condition['start_time'] = array('lt', $tm);
                    $res_coupon = M('Card_new_coupon')->field('coupon_id,store_id')->where($condition)->select();

                    foreach ($res_coupon as $k => $v) {
                        if ($v['store_id'] == '') {  //商户下的 全店铺
                            $is_show = true;
                            break;
                        } else {
                            //检测分类下的店铺 是否在优惠券配置的店铺内
                            $is_in_store = in_array($item['store_id'], explode(',', $v['store_id']));
                            if ($is_in_store) {
                                $is_show = true;
                                break;
                            }
                        }
                    }
                    if ($is_show == true) {
                        break;
                    }
                }
            }

            //分类下店铺没有优惠券 则不显示
            if ($is_show == false) {
                unset($category_list[$key]);
            }
        }

        //数据组装
        $data = [
            'coupon_main_color' => $theme_color,
            'had_coupon_count' => $mer_number,
            'share_data' => $share_data,
            'adv_img' => $adv_img,
            'category_list' => array_values($category_list)
        ];
        $this->returnCode(0, $data);

    }

    /**
     * 装修页面优惠券列表
     * User: chenxiang
     * Date: 2020/11/24 17:37
     */
    public function getCouponIndexList()
    {
        $city_id = intval($_POST['city_id']);
        $coupon_type = $_POST['coupon_type'];
        if (empty($city_id)) {
            $this->returnCodeError('请选择城市');
        }
        if (empty($coupon_type)) {
            $this->returnCode(0, []);
        }

        $where = [];
        //mer_id 存在时  只显示商家券
        $mer_id = $_POST['mer_id'];
        $mer_id && $where['mer_id'] = $mer_id;

        $coupon_list = [];
        $coupon_list_system = [];
        $coupon_list_mer = [];
        $coupon_list_my = [];

        if ($mer_id) {   // 带有mer_id的只显示 商家可领取的商家优惠券
            $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id);

            //增加商家优惠券标识
            foreach ($coupon_list_mer as $key => $val) {
                $coupon_list_mer[$key]['coupon_type'] = 'mer';
                $coupon_list_mer[$key]['coupon_type_name'] = '商家券';
                $coupon_list_mer[$key]['selected'] = 0;  //是否已领取 0否 1是
            }

            if (!empty($this->_uid)) {  //登录后
                $now_user_coupon = D('Card_new_coupon')->get_coupon_category_by_uid($this->_uid);

                foreach ($now_user_coupon as $vv) {
                    if (!empty($coupon_list_mer[$vv['coupon_id']])) {
                        $coupon_list_mer[$vv['coupon_id']]['selected'] = 1;
                    }
                }

                //是否展示 新人券
                $paidOrderCount = D('User')->check_new($this->_uid, 'all');
                $isNew = $paidOrderCount ? 1 : 2;
                if($isNew == 1) { //新人
                    foreach ($coupon_list_mer as $value) {
                        $coupon_list[] = $value;
                    }
                } else { //非新人 不显示新人券
                    //屏蔽掉发券功能新客优惠券
                    $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                    $activityCoupon = $activity['coupon_lists'] ?? '';
                    $filterMerchantCouponId = [];
                    foreach ($activityCoupon as $c) {
                        if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                            $filterMerchantCouponId[] = $c['coupon_id'];
                        }
                    }

                    foreach ($coupon_list_mer as $value) {
                        if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                            continue;
                        }
                        $coupon_list[] = $value;
                    }
                }

                foreach ($coupon_list as &$vv) {
                    if ($vv['coupon_type'] == 'mer') { //商家优惠券
                        if ($vv['had_pull'] == $vv['num'] && $vv['last_time'] < $_SERVER['REQUEST_TIME'] - 86400) {
                            continue;
                        }
                        $had_pull = M('Card_new_coupon_hadpull')->where(array('uid' => $this->_uid, 'coupon_id' => $vv['coupon_id']))->count();
                        if ($had_pull == $vv['limit']) {
                            $vv['status'] = 4;//超过限制
                        }

                        if ($vv['limit'] - intval($had_pull) <= $vv['num'] - $vv['had_pull']) {
                            $vv['can_get_num'] = $vv['limit'] - intval($had_pull);
                        } else {
                            $vv['can_get_num'] = $vv['num'] - $vv['had_pull'];
                        }
                        $vv['has_pull'] = $had_pull;
                    }
                }

                //只保留 可以领取的商家优惠券
                foreach ($coupon_list as $k => $v) {
                    if ($v['can_get_num'] <= 0) {
                        unset($coupon_list[$k]);
                    }
                }
            } else { //未登录

                //屏蔽掉发券功能新客优惠券
                $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                $activityCoupon = $activity['coupon_lists'] ?? '';
                $filterMerchantCouponId = [];
                foreach ($activityCoupon as $c) {
                    if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                        $filterMerchantCouponId[] = $c['coupon_id'];
                    }
                }

                foreach ($coupon_list_mer as $value) {
                    if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                        continue;
                    }
                    $coupon_list[] = $value;
                }
            }
        } else {
            //品牌精选
            if ($coupon_type == 'index') {
                $combine_list = M('Combine_coupon_list')->field('id,coupon_id,coupon_type')->order('create_time DESC')->select();

                foreach ($combine_list as $k => $v) {
                    if ($v['coupon_type'] == 'system') { //平台券

                        $coupon = D('System_coupon');
                        $get_where = ['is_vip_level' => 0,'rand_send' => 0, 'is_partition' => 0,'is_privileged' => 0, 'is_hide' => 0, 'coupon_id' => $v['coupon_id']];
                        $get_where['city_id'] = [['eq', 0], ['eq', $city_id], 'or'];

                        $coupon_list_system = $coupon->get_coupon_list($get_where);

//                        //获取用户已经领取券的列表  平台优惠券
//                        $had_pull_obj = [];
//                        if($coupon_list_system){
//                            $had_pull_arr = M('System_coupon_hadpull')->field('`coupon_id`')->where(['uid'=>$this->_uid,'coupon_id'=>['in', array_column($coupon_list_system, 'coupon_id')]])->select();
//                            foreach($had_pull_arr as $value){
//                                $had_pull_obj[$value['coupon_id']]++;
//                            }
//                        }

                        if (count($coupon_list_system) > 0) {
                            foreach ($coupon_list_system as $key => $value) {
                                $coupon_list_system[$key]['coupon_type'] = 'system';
                                $coupon_list_system[$key]['coupon_type_name'] = '平台券';
                                $coupon_list_system[$key]['selected'] = 0;  //是否已领取 0否 1是

                                if ($value['use_type'] == 1) {
                                    $store_ids = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $value['coupon_id']])->select();
                                    if (count($store_ids) == 1) { //单店铺
                                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                                    } else { //多店铺
                                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system';
                                    }
                                } else {
                                    if ($value['cate_name'] == 'village_group') { //跳转 社区团购首页
                                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                                    } else {
                                        $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . D('House_village')->home_url;
                                    }
                                }
                                if($value['store_direct_verification']==1){
                                    $coupon_list_system[$key]['jump_url'] = get_base_url('pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system&get_store_list='.($value['use_type']==1?1:0).'&user_had_id=');
                                }
                            }

                            if ($this->_uid) {
                                $now_user_coupon = $coupon->get_coupon_category_by_phone($this->_uid);
                                foreach ($now_user_coupon as $vv) {
                                    if (!empty($coupon_list_system[$vv['coupon_id']])) {
                                        $coupon_list_system[$vv['coupon_id']]['selected'] = 1;
                                        if($vv['is_use'] == 0 && $coupon_list_system[$vv['coupon_id']]['store_direct_verification']==1){
                                            $coupon_list_system[$vv['coupon_id']]['jump_url'] .= $vv['id'];
                                        }
                                    }
                                }
                            }
                        }


                    } elseif ($v['coupon_type'] == 'merchant') { //商家券
                        $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_coupon_id($v['coupon_id']);
                        //增加商家优惠券标识
                        foreach ($coupon_list_mer as $key => $value) {
                            $coupon_list_mer[$key]['coupon_type'] = 'mer';
                            $coupon_list_mer[$key]['coupon_type_name'] = '商家券';
                            $coupon_list_mer[$key]['selected'] = 0;  //是否已领取 0否 1是

                            $store = explode(',', $value['store_id']);
                            if (count($store) == 1) { //单店铺
                                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $value['store_id'];
                            } else { //多店铺
                                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'];
                            }
                            if($value['store_direct_verification']==1){
                                $coupon_list_mer[$key]['jump_url'] = get_base_url('pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=mer&get_store_list=1&user_had_id=');
                            }
                        }

                        if (!empty($this->_uid)) {
                            $now_user_coupon = D('Card_new_coupon')->get_coupon_category_by_uid($this->_uid);

                            foreach ($now_user_coupon as $vv) {
                                if (!empty($coupon_list_mer[$vv['coupon_id']])) {
                                    $coupon_list_mer[$vv['coupon_id']]['selected'] = 1;
                                    if($vv['is_use'] == 0 && $coupon_list_mer[$vv['coupon_id']]['store_direct_verification']==1){
                                        $coupon_list_mer[$vv['coupon_id']]['jump_url'] .= $vv['id'];
                                    }
                                }
                            }
                        }
                    }
                    
                    if (!empty($this->_uid)) {  //登录后
                        //是否展示 新人券
                        $paidOrderCount = D('User')->check_new($this->_uid, 'all');
                        $isNew = $paidOrderCount ? 1 : 2;
                        if ($isNew == 1) { //新人
                            foreach ($coupon_list_mer as $key => $value) {
                                $coupon_list[] = $value;
                            }
                            foreach ($coupon_list_system as $key => $value) {
                                $coupon_list[] = $value;
                            }
                        } else { //非新人 不显示新人券
                            //屏蔽掉发券功能新客优惠券
                            $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                            $activityCoupon = $activity['coupon_lists'] ?? '';
                            $filterSystemCouponId = $filterMerchantCouponId = [];
                            foreach ($activityCoupon as $c) {
                                if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                                    $filterSystemCouponId[] = $c['coupon_id'];
                                }
                                if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                                    $filterMerchantCouponId[] = $c['coupon_id'];
                                }
                            }

                            foreach ($coupon_list_mer as $key => $value) {
                                if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                                    continue;
                                }
                                $coupon_list[] = $value;
                            }
                            foreach ($coupon_list_system as $key => $value) {
                                if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                                    continue;
                                }
                                $coupon_list[] = $value;
                            }
                        }
                    } else { //未登录
                        //屏蔽掉发券功能新客优惠券
                        $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                        $activityCoupon = $activity['coupon_lists'] ?? '';
                        $filterSystemCouponId = $filterMerchantCouponId = [];
                        foreach ($activityCoupon as $c) {
                            if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                                $filterSystemCouponId[] = $c['coupon_id'];
                            }
                            if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                                $filterMerchantCouponId[] = $c['coupon_id'];
                            }
                        }

                        foreach ($coupon_list_mer as $key => $value) {
                            if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                                continue;
                            }
                            $coupon_list[] = $value;
                        }
                        foreach ($coupon_list_system as $key => $value) {
                            if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                                continue;
                            }
                            $coupon_list[] = $value;
                        }
                    }
                }

                $uniq_coupon_ids = [];
                foreach ($coupon_list as $ck => $cv) {
                    $uniq_key = $cv['coupon_type'] . '_' . $cv['coupon_id'];
                    if (in_array($uniq_key, $uniq_coupon_ids)) {
                        unset($coupon_list[$ck]);
                    }
                    $uniq_coupon_ids[] = $uniq_key;

                }
                $coupon_list = array_values($coupon_list);

                foreach ($coupon_list as &$vv) {
                    if($vv['coupon_type'] == 'system') {  //平台优惠券
                        if($vv['status']==2 && $vv['last_time']<$_SERVER['REQUEST_TIME']-86400){
                            continue;
                        }

                        $had_pull = M('System_coupon_hadpull')->where(['uid'=>$this->_uid,'coupon_id'=>$vv['coupon_id']])->count();

                        if($had_pull>=$vv['limit']){
                            $vv['status'] = 4;//超过限制
                        }
                        $vv['platform'] = unserialize($vv['platform']);
                        $vv['has_get'] = $had_pull;

                        if($vv['limit']-intval($had_pull)<=$vv['num']-$vv['had_pull']){
                            $vv['can_get_num'] = $vv['limit']-intval($had_pull);
                        }else{
                            $vv['can_get_num'] = $vv['num']-$vv['had_pull'];
                        }

                        $vv['has_pull'] = $had_pull;
                        $vv['can_get'] = unserialize($vv['platform']);

                    } elseif($vv['coupon_type'] == 'merchant') { //商家优惠券
                        if($vv['had_pull']==$vv['num'] && $vv['last_time']<$_SERVER['REQUEST_TIME']-86400){
                            continue;
                        }
                        $had_pull = M('Card_new_coupon_hadpull')->where(array('uid'=>$this->user_session['uid'],'coupon_id'=>$vv['coupon_id']))->count();
                        if($had_pull==$vv['limit']){
                            $vv['status'] = 4;//超过限制
                        }

                        if($vv['limit']-intval($had_pull)<=$vv['num']-$vv['had_pull']){
                            $vv['can_get_num'] = $vv['limit']-intval($had_pull);
                        }else{
                            $vv['can_get_num'] = $vv['num']-$vv['had_pull'];
                        }

                        $vv['platform'] = unserialize($vv['platform']);
                        $vv['has_pull'] = $had_pull;
                    }
                }

            } elseif ($coupon_type == 'system') {  //平台券

                $coupon = D('System_coupon');
                $get_where = ['is_vip_level' => 0,'rand_send' => 0, 'is_privileged' => 0, 'is_hide' => 0, 'is_partition' => 0];
                $get_where['status'] = ['IN',[1,3]];
                //处理城市
                $get_where['city_id'] = [['eq', 0], ['eq', $city_id], 'or'];

                $coupon_list_system = $coupon->get_coupon_list($get_where);
                foreach ($coupon_list_system as $key => $value) {
                    $coupon_list_system[$key]['coupon_type'] = 'system';
                    $coupon_list_system[$key]['coupon_type_name'] = '平台券';
                    $coupon_list_system[$key]['selected'] = 0;

                    $get_store_list = 0;
                    if ($value['use_type'] == 1) {
                        $get_store_list = 1;
                        $store_ids = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $value['coupon_id']])->select();
                        if (count($store_ids) == 1) { //单店铺
                            $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                        } else { //多店铺
                            $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system';
                        }
                    } else {
                        if ($value['cate_name'] == 'village_group') { //跳转 社区团购首页
                            $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                        } else {
                            $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . D('House_village')->home_url;
                        }
                    }
                    if($value['store_direct_verification']==1){
                        $coupon_list_system[$key]['jump_url'] = get_base_url('pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system&get_store_list=' . $get_store_list . '&user_had_id=');
                    }
                }

                if ($this->_uid) { //登录后
                    $now_user_coupon = $coupon->get_coupon_category_by_phone($this->_uid);
                    foreach ($now_user_coupon as $vv) {
                        if (!empty($coupon_list_system[$vv['coupon_id']])) {
                            $coupon_list_system[$vv['coupon_id']]['selected'] = 1;
                            if($coupon_list_system[$vv['coupon_id']]['store_direct_verification']==1 && $vv['is_use']==0){
                                $coupon_list_system[$vv['coupon_id']]['jump_url'] .= $vv['id'];
                            }
                        }
                    }

                    //是否展示 新人券
                    $paidOrderCount = D('User')->check_new($this->_uid, 'all');
                    $isNew = $paidOrderCount ? 1 : 2;
                    if($isNew == 1) { //新人
                        foreach ($coupon_list_system as $key => $value) {
                            $coupon_list[] = $value;
                        }
                    } else {
                        //屏蔽掉发券功能新客优惠券
                        $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                        $activityCoupon = $activity['coupon_lists'] ?? '';
                        $filterSystemCouponId = [];
                        foreach ($activityCoupon as $c) {
                            if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                                $filterSystemCouponId[] = $c['coupon_id'];
                            }
                        }

                        foreach ($coupon_list_system as $key => $value) {
                            if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                                continue;
                            }
                            $coupon_list[] = $value;
                        }
                    }

                    foreach ($coupon_list as &$vv) {
                        if ($vv['status'] == 2 && $vv['last_time'] < $_SERVER['REQUEST_TIME'] - 86400) {
                            continue;
                        }
                        if ($filterSystemCouponId && in_array($vv['coupon_id'], $filterSystemCouponId)) {
                            continue;
                        }

                        $had_pull = M('System_coupon_hadpull')->where(array('uid' => $this->_uid, 'coupon_id' => $vv['coupon_id']))->count();;

                        if ($had_pull >= $vv['limit']) {
                            $vv['status'] = 4;//超过限制
                        }
                        $vv['platform'] = unserialize($vv['platform']);
                        $vv['has_get'] = $had_pull;

                        if ($vv['limit'] - intval($had_pull) <= $vv['num'] - $vv['had_pull']) {
                            $vv['can_get_num'] = $vv['limit'] - intval($had_pull);
                        } else {
                            $vv['can_get_num'] = $vv['num'] - $vv['had_pull'];
                        }

                        $vv['has_pull'] = $had_pull;
                        $vv['can_get'] = unserialize($vv['platform']);

//                    //平台优惠券增加指定店铺提示
//                    $vv['designated_shop'] = '';
//                    if ($vv['use_type'] == 1) {
//                        $fist_store_name = M('System_coupon_store')->alias('cs')
//                            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = cs.store_id')
//                            ->where(['cs.coupon_id' => $vv['coupon_id']])
//                            ->order('cs.id ASC')
//                            ->getField('s.name');
//                        $vv['designated_shop'] = $fist_store_name ? $fist_store_name : '';
//                    }

                    }
                } else { //未登录
                    //屏蔽掉发券功能新客优惠券
                    $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                    $activityCoupon = $activity['coupon_lists'] ?? '';
                    $filterSystemCouponId = [];
                    foreach ($activityCoupon as $c) {
                        if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                            $filterSystemCouponId[] = $c['coupon_id'];
                        }
                    }

                    foreach ($coupon_list_system as $key => $value) {
                        if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                            continue;
                        }
                        $coupon_list[] = $value;
                    }
                }
            } elseif ($coupon_type == 'merchant') {  //商家 各个分类优惠券
                //店铺分类id
                $store_cat_id = intval($_POST['id']);

//                /*模拟数据 start*/
//                $store_cat_id = 124;
//                /*模拟数据 end*/

                if (empty($store_cat_id)) {
                    $this->returnCodeError('分类参数为空');
                }
                $store_cat_id && $where['cat_fid'] = $store_cat_id;

                $where['city_id'] = [['eq', 0], ['eq', $city_id], 'or'];
                //分类下的店铺
                $where['status'] = 1;
                $store_info = M('Merchant_store')->field('mer_id,store_id')->where($where)->select();

                if (count($store_info) > 0) {
                    foreach ($store_info as $index => $item) {

                        $condition['status'] = 1;
                        $condition['allow_im_get'] = 0;
                        $condition['only_assign'] = 0;
                        $condition['is_live'] = 0;
                        $condition['is_partition'] = 0;
                        $condition['is_receive_full_reduction'] = 0;
                        $condition['send_type'] = array('neq', 2);

                        $condition['mer_id'] = $item['mer_id'];

                        $res_coupon = M('Card_new_coupon')->field('coupon_id,store_id')->where($condition)->select();

                        if (count($res_coupon) > 0) {
                            foreach ($res_coupon as $k => $v) {

                                if ($v['store_id'] == '' || in_array($item['store_id'], explode(',', $v['store_id']))) {  //商户下的 全店铺 或者 分类下的店铺在优惠券配置的店铺内

                                    $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_coupon_id($v['coupon_id']);


                                    if (!empty($coupon_list_mer)) {
                                        //增加商家优惠券标识
                                        foreach ($coupon_list_mer as $key => $value) {
                                            $coupon_list_mer[$key]['coupon_type'] = 'mer';
                                            $coupon_list_mer[$key]['coupon_type_name'] = '商家券';
                                            $coupon_list_mer[$key]['selected'] = 0;  //是否领取 0否 1是

                                            $store = explode(',', $value['store_id']);
                                            if (count($store) == 1) { //单店铺
                                                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $value['store_id'];
                                            } else { //多店铺
                                                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'];
                                            }
                                            if($value['store_direct_verification']==1){
                                                $coupon_list_mer[$key]['jump_url'] = get_base_url('pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=mer&get_store_list=1&user_had_id=');
                                            }
                                        }
                                        if (!empty($this->_uid)) {
                                            $now_user_coupon = D('Card_new_coupon')->get_coupon_category_by_uid($this->_uid);

                                            foreach ($now_user_coupon as $vv) {
                                                if (!empty($coupon_list_mer[$vv['coupon_id']])) {
                                                    $coupon_list_mer[$vv['coupon_id']]['selected'] = 1;
                                                    if($coupon_list_mer[$vv['coupon_id']]['store_direct_verification']==1 && $vv['is_use']==0){
                                                        $coupon_list_mer[$vv['coupon_id']]['jump_url'] .= $vv['id'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($this->_uid) { //登陆后
                                    //是否展示 新人券
                                    $paidOrderCount = D('User')->check_new($this->_uid, 'all');
                                    $isNew = $paidOrderCount ? 1 : 2;
                                    if ($isNew == 1) { //新人
                                        foreach ($coupon_list_mer as $key => $value) {
                                            $coupon_list[$key] = $value;
                                        }
                                    } else {

                                        //屏蔽掉发券功能新客优惠券
                                        $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                                        $activityCoupon = $activity['coupon_lists'] ?? '';
                                        $filterMerchantCouponId = [];
                                        foreach ($activityCoupon as $c) {
                                            if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                                                $filterSystemCouponId[] = $c['coupon_id'];
                                            }
                                        }

                                        foreach ($coupon_list_mer as $key => $value) {
                                            if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                                                continue;
                                            }

                                            $coupon_list[$key] = $value;
                                        }
                                    }


                                } else {
                                    //屏蔽掉发券功能新客优惠券
                                    $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                                    $activityCoupon = $activity['coupon_lists'] ?? '';
                                    $filterMerchantCouponId = [];
                                    foreach ($activityCoupon as $c) {
                                        if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                                            $filterMerchantCouponId[] = $c['coupon_id'];
                                        }
                                    }

                                    foreach ($coupon_list_mer as $key => $value) {
                                        if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                                            continue;
                                        }
                                        $coupon_list[$key] = $value;
                                    }
                                }
                            }
                        }
                    }

                    if ($this->_uid) {
                        foreach ($coupon_list as $key => &$vv) {
                            if ($vv['coupon_type'] == 'mer') { //商家优惠券
                                if ($vv['had_pull'] == $vv['num'] && $vv['last_time'] < $_SERVER['REQUEST_TIME'] - 86400) {
                                    continue;
                                }
                                $had_pull = M('Card_new_coupon_hadpull')->where(array('uid' => $this->_uid, 'coupon_id' => $vv['coupon_id']))->count();
                                if ($had_pull == $vv['limit']) {
                                    $vv['status'] = 4;//超过限制
                                }

                                if ($vv['limit'] - intval($had_pull) <= $vv['num'] - $vv['had_pull']) {
                                    $vv['can_get_num'] = $vv['limit'] - intval($had_pull);
                                } else {
                                    $vv['can_get_num'] = $vv['num'] - $vv['had_pull'];
                                }

                                $vv['has_pull'] = $had_pull;
                            }
                        }
                    }
                }

            }
        }


        //组装返回数据
        foreach ($coupon_list as $kk => $val) {
            $coupon_list_my[$kk]['is_discount'] = intval($val['is_discount']); //是否是折扣券
            $coupon_list_my[$kk]['discount_value'] = floatval($val['discount_value']); //折扣金额
            $coupon_list_my[$kk]['img'] = replace_file_domain($val['img']); //优惠券图片  或者  img_coupon
            $coupon_list_my[$kk]['name'] = $val['name']; //优惠券名称
//                $coupon_list_my[$kk]['des_detial'] = $vv['des_detial']; //使用说明

            if($val['can_get_num'] > 0) {
                $coupon_list_my[$kk]['is_had'] = 0; //是否已领取  0否 1是  仍可以领取
            } else {
                $coupon_list_my[$kk]['is_had'] = $val['selected']??0; //是否已领取  0否 1是
            }
            //已领取优惠券是否已使用完
            $coupon_list_my[$kk]['is_use'] = 0; //1-已使用完，0-未使用完
            if ($val['coupon_type'] == 'mer') { //商家券
                $merchant_info = M('Merchant')->field('name,pic_info')->where(['status' => 1, 'mer_id' => $val['mer_id']])->find();

                $images = explode(";", $merchant_info['pic_info']);
                $merchant_image_class = new merchant_image();
                $images = explode(";", $images[0]);
                $pic = $merchant_image_class->get_image_by_path($images[0]);

                $coupon_list_my[$kk]['store_img'] = $pic; //商家logo
                $coupon_list_my[$kk]['plat_name'] = $merchant_info['name']; //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'merchant'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
//                    $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['receive_time']); //使用开始时间 (领取时间)
//                    $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['end_time']); //使用结束时间 (优惠券过期时间)
                if($coupon_list_my[$kk]['is_had']==1){
                    $no_use = D('Card_new_coupon_hadpull')->where(['uid'=>$this->_uid,'coupon_id'=>$val['coupon_id'],'is_use'=>0])->find();
                    if(!$no_use){
                        $coupon_list_my[$kk]['is_use'] = 1;
                    } else {
                        if ($val['store_direct_verification'] == 1) {
                            $had_pull_id = $val['jump_url'] ? explode('=', $val['jump_url']) : [];
                            $had_pull_id = $had_pull_id ? $had_pull_id[count($had_pull_id) - 1] : 0;
                            if (!$had_pull_id) {
                                $val['jump_url'] .= $no_use['id'];
                            }
                        }
                    }
                }

            } elseif ($val['coupon_type'] == 'system') { //平台券
                $coupon_list_my[$kk]['store_img'] = replace_file_domain(C('config.wechat_share_img')); //平台正方形logo
                $coupon_list_my[$kk]['plat_name'] = C('config.site_name'); //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'system'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
//                    $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['get_start_time']); //使用开始时间
//                    $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['get_end_time']); //使用开始时间
                if($coupon_list_my[$kk]['is_had']==1){
                    $no_use = D('System_coupon_hadpull')->where(['uid'=>$this->_uid,'coupon_id'=>$val['coupon_id'],'is_use'=>0])->find();
                    if(!$no_use){
                        $coupon_list_my[$kk]['is_use'] = 1;
                    } else {
                        if ($val['store_direct_verification'] == 1) {
                            $had_pull_id = $val['jump_url'] ? explode('=', $val['jump_url']) : [];
                            $had_pull_id = $had_pull_id ? $had_pull_id[count($had_pull_id) - 1] : 0;
                            if (!$had_pull_id) {
                                $val['jump_url'] .= $no_use['id'];
                            }
                        }
                    }
                }
            }

            $coupon_list_my[$kk]['discount'] = floatval($val['discount']); //优惠金额
            $coupon_list_my[$kk]['order_money'] = floatval($val['order_money']); //门槛
            $coupon_list_my[$kk]['coupon_id'] = $val['coupon_id']; //优惠券id
            $coupon_list_my[$kk]['can_get_num'] = $val['can_get_num']; //可领取数量，为0标识已抢完
            $coupon_list_my[$kk]['had_percent'] = intval(($val['had_pull'] / $val['num']) * 100)??0; //已领取的百分比
//                $coupon_list_my[$kk]['is_used'] = $vv['is_use']; //是否已使用 0-正常，1-已使用，2-不能使用，3-赠送中
            //判断是否快过期
            if (time() > $val['end_time']) { //已过期
                $is_discount = 1;
            } else {
                if (time() + 86400 > $val['end_time']) { //快过期
                    $is_discount = 2;
                } else { //未过期
                    $is_discount = 0;
                }
            }
            $coupon_list_my[$kk]['is_time_out'] = $is_discount; //是否过期   三种状态; 0未过期 1已过期 2快过期

            if(empty($val['coupon_url'])){
                $coupon_list_my[$kk]['url'] = $val['jump_url']; //点击立即使用的跳转链接
            }else{
                $coupon_list_my[$kk]['url'] = $val['coupon_url']; //点击立即使用的跳转链接
            }

            $coupon_list_my[$kk]['coupon_start_time'] = date('Y.m.d', $val['start_time']); //优惠券有效期-开始时间
            $coupon_list_my[$kk]['coupon_end_time'] = date('Y.m.d', $val['end_time']); //优惠券有效期-结束时间


            if (!empty($val['cate_name'])) {
                if($val['cate_name'] == 'new_user') {
                    $coupon_list_my[$kk]['use_limit'] = '新人券';
                } else {
                    $coupon_type_arr = D('System_coupon')->cate_platform();
                    if($val['cate_name'] == 'all') {
                        $coupon_list_my[$kk]['use_limit'] =  $coupon_type_arr['category'][$val['cate_name']];
                    } else {
                        $coupon_list_my[$kk]['use_limit'] =  $coupon_type_arr['category'][$val['cate_name']] . '使用';
                    }
                }
            }

            //同步至微信卡券数据
            $coupon_list_my[$kk]['sync_wx'] = $val['sync_wx']??0; //是否同步微信卡券 0否 1是
            $coupon_list_my[$kk]['wx_cardid'] = $val['wx_cardid']??'';
            $coupon_list_my[$kk]['wx_ticket_addtime'] = $val['wx_ticket_addtime']??''; //微信ticket addtime
            $coupon_list_my[$kk]['cardsign'] = $val['cardsign']??'';
            $coupon_list_my[$kk]['allow_new'] = $val['allow_new']; //允许新用户使用 0-不允许 1-允许 默认1
            $coupon_list_my[$kk]['status'] = $val['status']; //0 未启用 1 启用 2过期 3领完了
        }


        if ($mer_id) {
            $merchant_info = M('Merchant')->field('name,pic_info')->where(['status' => 1, 'mer_id' => $mer_id])->find();

            $images = explode(";", $merchant_info['pic_info']);
            $merchant_image_class = new merchant_image();
            $images = explode(";", $images[0]);
            $pic = $merchant_image_class->get_image_by_path($images[0]);
            $mer_info = ['logo' => $pic, 'name' => $merchant_info['name']];

            //商家优惠券分享
            $share_title = $merchant_info['name'].'领券中心';
            $share_desc = '点击立即领取更多好券';
            $mer_share = ['title'=>$share_title, 'desc'=>$share_desc, 'pic'=>$pic, 'url'=>$this->config['site_url'] . '/packapp/plat/pages/coupon/storeCoupon?mer_id='.$mer_id];

            return $this->returnCode(0, ['mer_info' => $mer_info, 'coupon_list' => array_values($coupon_list_my), 'mer_share'=>$mer_share]);
        } else {
            return $this->returnCode(0, array_values($coupon_list_my));
        }

    }


    /**
     * 优惠券搜索
     * User: chenxiang
     * Date: 2020/11/25 21:10
     */
    public function getCouponSearchList()
    {
        $city_id = intval($_POST['city_id']);
        $keyword = $_POST['keyword'];

//        /* 模拟数据 start */
//        $this->_uid = 112358756;
//        $city_id = 105;
//        $keyword = '券';
//        /* 模拟数据 end */

        if (empty($city_id)) {
            $this->returnCodeError('请选择城市');
        }

        $coupon_list = [];
        $coupon_list_my = [];

        $where = [];
        $where['city_id'] = [['eq', 0], ['eq', $city_id], 'or'];
        if (!empty($keyword)) {
            $where['name|discount'] = ['like', '%' . $keyword . '%']; //优惠券名称 或者 优惠金额
        }
		$where['is_hide'] = '0';

        //平台券
        $coupon_list_system = D('System_coupon')->get_coupon_list($where);

        foreach ($coupon_list_system as $key => $value) {
            $coupon_list_system[$key]['coupon_type'] = 'system';
            $coupon_list_system[$key]['coupon_type_name'] = '平台券';
            $coupon_list_system[$key]['selected'] = 0;

            if ($value['use_type'] == 1) {
                $store_ids = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $value['coupon_id']])->select();
                if (count($store_ids) == 1) { //单店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                } else { //多店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system';
                }
            } else {
                if ($value['cate_name'] == 'village_group') { //跳转 社区团购首页
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                } else {
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $value['coupon_id'] . '&coupon_type=system';
                }
            }

        }

        if ($this->_uid) {
            $now_user_coupon = D('System_coupon')->get_coupon_category_by_phone($this->_uid);
            foreach ($now_user_coupon as $vv) {
                if (!empty($coupon_list_system[$vv['coupon_id']])) {
                    $coupon_list_system[$vv['coupon_id']]['selected'] = 1;
                }
            }
        }

        $condition = [];
        $condition['m.city_id'] = [['eq', 0], ['eq', $city_id], 'or'];
        if (!empty($keyword)) {
            $condition['m.name|c.name|c.discount'] = ['like', '%' . $keyword . '%']; //商家名称 优惠券名称 优惠金额
        }

        //商家券
        $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_coupon_id('', 0, 0, $condition);
        //增加商家优惠券标识
        foreach ($coupon_list_mer as $k => $v) {
            $coupon_list_mer[$k]['coupon_type'] = 'mer';
            $coupon_list_mer[$k]['coupon_type_name'] = '商家券';
            $coupon_list_mer[$k]['selected'] = 0;  //是否领取 0否 1是

            $store = explode(',', $v['store_id']);
            if (count($store) == 1) { //单店铺
                $coupon_list_mer[$k]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $v['store_id'];
            } else { //多店铺
                $coupon_list_mer[$k]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id=' . $v['coupon_id'];
            }
        }

        if (!empty($this->_uid)) { //登陆后
            $now_user_coupon = D('Card_new_coupon')->get_coupon_category_by_uid($this->_uid);

            foreach ($now_user_coupon as $co) {
                if (!empty($coupon_list_mer[$co['coupon_id']])) {
                    $coupon_list_mer[$co['coupon_id']]['selected'] = 1;
                }
            }

            //是否展示 新人券
            $paidOrderCount = D('User')->check_new($this->_uid, 'all');
            $isNew = $paidOrderCount ? 1 : 2;
            if($isNew == 1) { //新人
                foreach ($coupon_list_mer as $key => $value) {
                    $coupon_list[] = $value;
                }
                foreach ($coupon_list_system as $key => $value) {
                    $coupon_list[] = $value;
                }
            } else {
                //屏蔽掉发券功能新客优惠券
                $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
                $activityCoupon = $activity['coupon_lists'] ?? '';
                $filterSystemCouponId = $filterMerchantCouponId = [];
                foreach ($activityCoupon as $c) {
                    if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                        $filterSystemCouponId[] = $c['coupon_id'];
                    }
                    if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                        $filterMerchantCouponId[] = $c['coupon_id'];
                    }
                }

                foreach ($coupon_list_mer as $key => $value) {
                    if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                        continue;
                    }
                    $coupon_list[] = $value;
                }
                foreach ($coupon_list_system as $key => $value) {
                    if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                        continue;
                    }
                    $coupon_list[] = $value;
                }
            }

            foreach ($coupon_list as &$vv) {
                if ($vv['coupon_type'] == 'system') {  //平台优惠券
                    if ($vv['status'] == 2 && $vv['last_time'] < $_SERVER['REQUEST_TIME'] - 86400) {
                        continue;
                    }
                    if ($filterSystemCouponId && in_array($vv['coupon_id'], $filterSystemCouponId)) {
                        continue;
                    }

                    $had_pull = M('System_coupon_hadpull')->where(array('uid' => $this->_uid, 'coupon_id' => $vv['coupon_id']))->count();;

                    if ($had_pull >= $vv['limit']) {
                        $vv['status'] = 4;//超过限制
                    }
//                $vv['platform'] = unserialize($vv['platform']);
                    $vv['has_get'] = $had_pull;

                    if ($vv['limit'] - intval($had_pull) <= $vv['num'] - $vv['had_pull']) {
                        $vv['can_get_num'] = $vv['limit'] - intval($had_pull);
                    } else {
                        $vv['can_get_num'] = $vv['num'] - $vv['had_pull'];
                    }

                    $vv['has_pull'] = $had_pull;
                    $vv['can_get'] = unserialize($vv['platform']);

                } elseif ($vv['coupon_type'] == 'mer') { //商家优惠券
                    if ($vv['had_pull'] == $vv['num'] && $vv['last_time'] < $_SERVER['REQUEST_TIME'] - 86400) {
                        continue;
                    }
                    $had_pull = M('Card_new_coupon_hadpull')->where(array('uid' => $this->_uid, 'coupon_id' => $vv['coupon_id']))->count();
                    if ($had_pull == $vv['limit']) {
                        $vv['status'] = 4;//超过限制
                    }

                    if ($vv['limit'] - intval($had_pull) <= $vv['num'] - $vv['had_pull']) {
                        $vv['can_get_num'] = $vv['limit'] - intval($had_pull);
                    } else {
                        $vv['can_get_num'] = $vv['num'] - $vv['had_pull'];
                    }

                    $vv['has_pull'] = $had_pull;
                }
            }
        } else { //未登录
            //屏蔽掉发券功能新客优惠券
            $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
            $activityCoupon = $activity['coupon_lists'] ?? '';
            $filterSystemCouponId = $filterMerchantCouponId = [];
            foreach ($activityCoupon as $c) {
                if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                    $filterSystemCouponId[] = $c['coupon_id'];
                }
                if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                    $filterMerchantCouponId[] = $c['coupon_id'];
                }
            }

            foreach ($coupon_list_mer as $key => $value) {
                if (in_array($value['coupon_id'], $filterMerchantCouponId)) {
                    continue;
                }
                $coupon_list[] = $value;
            }
            foreach ($coupon_list_system as $key => $value) {
                if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                    continue;
                }
                $coupon_list[] = $value;
            }
        }

        //组装返回数据
        foreach ($coupon_list as $kk => $val) {
            $coupon_list_my[$kk]['is_discount'] = intval($val['is_discount']); //是否折扣
            $coupon_list_my[$kk]['discount_value'] = floatval($val['discount_value']); //折扣金额
            $coupon_list_my[$kk]['img'] = replace_file_domain( $val['img']); //优惠券图片  或者  img_coupon
            $coupon_list_my[$kk]['name'] = $val['name']; //优惠券名称
//                $coupon_list_my[$kk]['des_detial'] = $vv['des_detial']; //使用说明

            if ($val['coupon_type'] == 'mer') { //商家券
                $merchant_info = M('Merchant')->field('name,pic_info')->where(['status' => 1, 'mer_id' => $val['mer_id']])->find();

                $images = explode(";", $merchant_info['pic_info']);
                $merchant_image_class = new merchant_image();
                $images = explode(";", $images[0]);
                $pic = $merchant_image_class->get_image_by_path($images[0]);

                $coupon_list_my[$kk]['store_img'] = $pic; //商家logo
                $coupon_list_my[$kk]['plat_name'] = $merchant_info['name']; //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'merchant'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
//                    $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['receive_time']); //使用开始时间 (领取时间)
//                    $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['end_time']); //使用结束时间 (优惠券过期时间)

            } elseif ($val['coupon_type'] == 'system') { //平台券
                $coupon_list_my[$kk]['store_img'] = replace_file_domain(C('config.wechat_share_img')); //平台正方形logo
                $coupon_list_my[$kk]['plat_name'] = C('config.site_name'); //平台或商家名称
                $coupon_list_my[$kk]['coupon_type'] = 'system'; //商家优惠券    优惠券类型 system-平台优惠券 ，merchant-商家优
//                    $coupon_list_my[$kk]['use_start_time'] = date('Y.m.d', $vv['get_start_time']); //使用开始时间
//                    $coupon_list_my[$kk]['use_end_time'] = date('Y.m.d', $vv['get_end_time']); //使用开始时间
            }

            $coupon_list_my[$kk]['discount'] = floatval($val['discount']); //优惠金额
            $coupon_list_my[$kk]['order_money'] = floatval($val['order_money']); //门槛
            $coupon_list_my[$kk]['coupon_id'] = $val['coupon_id']; //优惠券id
            $coupon_list_my[$kk]['is_had'] = $val['selected']??0; //是否已领取  0否 1是
            $coupon_list_my[$kk]['can_get_num'] = $val['can_get_num']; //可领取数量，为0标识已抢完
            $coupon_list_my[$kk]['had_percent'] = intval(($val['had_pull'] / $val['num']) * 100); //已领取的百分比
//                $coupon_list_my[$kk]['is_used'] = $vv['is_use']; //是否已使用 0-正常，1-已使用，2-不能使用，3-赠送中


            //判断是否快过期
            if (time() > $val['end_time']) { //已过期
                $is_discount = 1;
            } else {
                if (time() + 86400 > $val['end_time']) { //快过期
                    $is_discount = 2;
                } else { //未过期
                    $is_discount = 0;
                }
            }
            $coupon_list_my[$kk]['is_time_out'] = $is_discount; //是否过期   三种状态; 0未过期 1已过期 2快过期
            if(empty($val['coupon_url'])){
                $coupon_list_my[$kk]['url'] = $val['jump_url']; //点击立即使用的跳转链接
            }else{
                $coupon_list_my[$kk]['url'] = $val['coupon_url']; //点击立即使用的跳转链接
            }

            $coupon_list_my[$kk]['coupon_start_time'] = date('Y.m.d', $val['start_time']); //优惠券有效期-开始时间
            $coupon_list_my[$kk]['coupon_end_time'] = date('Y.m.d', $val['end_time']); //优惠券有效期-结束时间

            if (!empty($val['cate_name'])) {
                $coupon_type_arr = D('System_coupon')->cate_platform();
                if ($val['cate_name'] == 'all') {
                    $coupon_list_my[$kk]['use_limit'] = $coupon_type_arr['category'][$val['cate_name']];
                } else {
                    $coupon_list_my[$kk]['use_limit'] = $coupon_type_arr['category'][$val['cate_name']] . '使用';
                }

            }

            //同步至微信卡券数据
            $coupon_list_my[$kk]['sync_wx'] = $val['sync_wx']??0; //是否同步微信卡券 0否 1是
            $coupon_list_my[$kk]['wx_cardid'] = $val['wx_cardid']??''; //是否过期   三种状态; 0未过期 1已过期 2快过期
            $coupon_list_my[$kk]['wx_ticket_addtime'] = $val['wx_ticket_addtime']??''; //微信ticket addtime
            $coupon_list_my[$kk]['cardsign'] = $val['cardsign']??'';
            $coupon_list_my[$kk]['allow_new'] = $val['allow_new']; //允许新用户使用 0-不允许 1-允许 默认1
            $coupon_list_my[$kk]['status'] = $val['status']; //0 未启用 1 启用 2过期 3领完了
        }



        return $this->returnCode(0, array_values($coupon_list_my));
    }

    /**
     * 优惠券领取接口
     * User: chenxiang
     * Date: 2020/11/26 15:42
     */
    public function getCoupon() {
        $coupon_type = $_POST['coupon_type'];
        $coupon_id = $_POST['coupon_id'];

        if (empty($coupon_type) || empty($coupon_id)) {
            $this->returnCodeError('参数错误');
        }

        $now_user = D('User')->get_user($this->_uid);
        if (empty($this->_uid)) {
            $this->returnCode(1001,[], '请先登录~');
        }elseif(empty($now_user['phone'])){
            $this->returnCode(1002,[], '未绑定手机号码');
        }

        //限制领取频率，避免接口被刷
        $uniqueKey = md5('coupon_getCoupon' . $this->_uid);
        $lockRs = setLock($uniqueKey, 2);
        if ($lockRs['error_no']) {
            setLock($uniqueKey, 2);
            $this->returnCodeError($lockRs['error_msg']);
        }

        //是否一次领取最多可领取数量
        $get_all = 1;// 1 全部领取

        if($coupon_type == 'system') { //领取平台券
            $model = D('System_coupon');
           // $has_get = $model->get_coupon_count_by_uid($coupon_id, $this->_uid);

            $result = $model->had_pull($coupon_id, $this->_uid,'',$get_all);
            if ($result['error_code'] != 0) {
                switch ($result['error_code']) {
                    case '1':
                        $error_msg = L_('领取失败');
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
                    case '6':
                        $error_msg = L_('您当前所在城市无法领取该优惠券');
                        break;
                }
                $this->returnCode(1003,[], $error_msg);
            }

            $reduce_num=$result['coupon']['reduce_num'];
            $reduce_num=$reduce_num>0?$reduce_num:1;
            $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少

        } elseif ($coupon_type == 'merchant') { //领取商家券
            $is_click_get = stripos($coupon_id, ',');
            if($is_click_get) { //一键领取 商家的 全部可领优惠券
                $coupon_ids = explode(',', $coupon_id);
                foreach ($coupon_ids as $value) {
                    $coupon_id = $value;
                    //$get_all = 1; // 1 全部领取
                    $model = D('Card_new_coupon');
//                $has_get = $model->get_coupon_count_by_uid($coupon_id, $this->_uid);
                    $result = $model->had_pull($coupon_id, $this->_uid,'',false,false,$get_all);

                    if ($result['error_code'] != 0) {
                        switch ($result['error_code']) {
                            case '1':
                                $error_msg = '领取失败';
                                break;
                            case '2':
                                $error_msg = '优惠券已过期';
                                break;
                            case '3':
                                $error_msg = '优惠券已经领完了';
                                break;
                            case '4':
                                $error_msg = '只允许新用户领取';
                                break;
                            case '5':
                                $error_msg = '不能再领取了';
                                break;
                        }
                        $this->returnCode(1003,[], $error_msg);
                    }
                    $reduce_num=$result['coupon']['reduce_num'];
                    $reduce_num=$reduce_num>0?$reduce_num:1;
                    $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少
                }
            } else { //领取某个优惠券
                //$get_all = 1; // 1 全部领取
                $model = D('Card_new_coupon');
//                $has_get = $model->get_coupon_count_by_uid($coupon_id, $this->_uid);
                $result = $model->had_pull(is_array($coupon_id)?$coupon_id[0]:$coupon_id, $this->_uid,'',false,false,$get_all);
                if ($result['error_code'] != 0) {
                    switch ($result['error_code']) {
                        case '1':
                            $error_msg = '领取失败';
                            break;
                        case '2':
                            $error_msg = '优惠券已过期';
                            break;
                        case '3':
                            $error_msg = '优惠券已经领完了';
                            break;
                        case '4':
                            $error_msg = '只允许新用户领取';
                            break;
                        case '5':
                            $error_msg = '不能再领取了';
                            break;
                    }
                    $this->returnCode(1003,[], $error_msg);
                }
                $reduce_num=$result['coupon']['reduce_num'];
                $reduce_num=$reduce_num>0?$reduce_num:1;
                $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少

            }
        }

        $this->returnCode(0, ['tip' => '恭喜您，领取成功']);

    }


    //优惠券获取小程序订阅消息模板
    public function getTemplateList() {

    }

    /**
     * 转赠优惠券-列表
     * @author :yzz
     * @date:2022/04/01
     */
    public function myCouponGiveList()
    {
        $uid = $this->_uid;
        $status = I('status', 1); // 1-我的领取，2-我的转赠
        $mer_id = I('mer_id'); //商家id

        if (empty($uid)) {
            $this->returnCode(1, [], L_('请先登录！'));
        }
        if (!in_array($status,array(1,2))) {
            $this->returnCode(2, [], L_('查询类型错误！'));
        }
        $now_user = D('User')->get_user($uid);
        if (!$now_user){
            $this->returnCode(3, [], L_('用户不存在！'));
        }
        $card_new_coupon_list = $this->myCouponGiveGet($uid,$status,$mer_id);
        if($status==1){
            $give_user = $pass_ids = [];
            foreach($card_new_coupon_list as $key=>$item){
                $pass_ids[] = $item['pass_id'];
            }
            $share_info = M('Card_new_coupon_share')->alias('a')
                ->join(' inner join '.C('DB_PREFIX').'user b ON a.uid = b.uid')
                ->join(' inner join '.C('DB_PREFIX').'group_pass_relation c ON a.order_id = c.order_id and a.pass_id = c.id')
                ->field('a.pass_id,b.nickname,c.status')
                ->where(['a.pass_id'=>['in',$pass_ids]])
                ->select();
            foreach($share_info as $vv){
                $give_user[$vv['pass_id']]['user_name'] = $vv['nickname'];
                $give_user[$vv['pass_id']]['status'] = $vv['status'];
            }
            foreach($card_new_coupon_list as $kk=>$val){
                if(isset($give_user[$val['pass_id']])){
                    $card_new_coupon_list[$kk]['user_name'] = $give_user[$val['pass_id']]['user_name'];
                    $card_new_coupon_list[$kk]['is_used'] = $give_user[$val['pass_id']]['status'];
                }
                unset($card_new_coupon_list[$kk]['pass_id']);
            }
        }
        $order_ids = [];
        foreach($card_new_coupon_list as $v){
            $order_ids[] = $v['order_id'];
        }
        //查询团购id
        $group_info = M('Group_order')->alias('a')
            ->join(' inner join '.C('DB_PREFIX').'group b ON a.group_id = b.group_id')
            ->field('a.order_id,a.group_id,b.begin_time,b.end_time,b.deadline_time,b.effective_type,a.pay_time')
            ->where(['a.order_id'=>['in',$order_ids]])
            ->select();
        $group_ids = [];
        foreach ($group_info as $item){
            $group_ids[$item['order_id']]['group_id'] = $item['group_id']?:'';
            $group_ids[$item['order_id']]['use_end_time'] = $item['end_time']?date('Y.m.d',$item['end_time']):'';
            $group_ids[$item['order_id']]['use_start_time'] = $item['begin_time']?date('Y.m.d',$item['begin_time']):'';
            $group_ids[$item['order_id']]['effective_type'] = $item['effective_type'];
            $group_ids[$item['order_id']]['deadline_time'] = $item['deadline_time'];
            $group_ids[$item['order_id']]['pay_time'] = $item['pay_time'];
        }
        foreach($card_new_coupon_list as $kk=>$v){
            $order_ids[] = $v['order_id'];
            $group_id = '';
            if(isset($group_ids[$v['order_id']])){
                $group_id = $group_ids[$v['order_id']]['group_id'];
                $card_new_coupon_list[$kk]['use_start_time'] = $group_ids[$item['order_id']]['use_start_time'];
                $card_new_coupon_list[$kk]['use_end_time'] = $group_ids[$item['order_id']]['use_end_time'];
                if($v['is_used']!=1&&(($group_ids[$v['order_id']]['effective_type']!=1&&$group_ids[$v['order_id']]['deadline_time']<time())||($group_ids[$v['order_id']]['effective_type']==1&&strtotime('+'.$group_ids[$v['order_id']]['deadline_time'].' day',$group_ids[$v['order_id']]['pay_time'])<time()))){
                    $card_new_coupon_list[$kk]['is_used'] = 2;
                }elseif($v['is_used']!=1&&(($group_ids[$v['order_id']]['effective_type']!=1&&$group_ids[$v['order_id']]['deadline_time']>time())||($group_ids[$v['order_id']]['effective_type']==1&&strtotime('+'.$group_ids[$v['order_id']]['deadline_time'].' day',$group_ids[$v['order_id']]['pay_time'])>time()))){
                    $card_new_coupon_list[$kk]['is_used'] = 0;
                }
            }
            $card_new_coupon_list[$kk]['redirect_url'] = C("config.site_url").'/packapp/platn/pages/group/v1/groupDetail/index?group_id='.$group_id;
        }
        $this->returnCode(0, ['coupon_list' => array_values($card_new_coupon_list)]);
    }

    /**
     * 转赠优惠券-统计tab接口
     * @author :yzz
     * @date:2022/04/01
     */
    public function myCouponGiveNum(){
        $uid = $this->_uid;
        $status = I('status', 1); // 1-我的领取，2-我的转赠
        $mer_id = I('mer_id'); //商家id

        if (empty($uid)) {
            $this->returnCode(1, [], L_('请先登录！'));
        }
        if (!in_array($status,array(1,2))) {
            $this->returnCode(2, [], L_('查询类型错误！'));
        }
        $now_user = D('User')->get_user($uid);
        if (!$now_user){
            $this->returnCode(3, [], L_('用户不存在！'));
        }
        $menu = array(1=>'我的领取',2=>'我的转赠');
        $tab_list = [];
        foreach ($menu as $key=>$item){
            if($key==1){
                $card_new_coupon_list = $this->myCouponGiveGet($uid,1,$mer_id);
            }else{
                $card_new_coupon_list = $this->myCouponGiveGet($uid,2,$mer_id);
            }
            $tab_list[] = [
                'name' => $item,
                'value' => $key,
                'count' => count($card_new_coupon_list)
                ];
        }

        $this->returnCode(0, ['tab_list' => $tab_list]);
    }

    /**
     * 获取转赠优惠券
     * @author yzz
     * @date 2022/04/01
     * @param $uid  //当前登录用户
     * @param $status  //1-我的领取，2-我的转赠
     * @param $mer_id //商家id
     * @return mixed
     */
    private function myCouponGiveGet($uid,$status,$mer_id){
        $mer_id && $where['mer_id'] = $mer_id;
        $where = [];
        if($status==1){
            $where['status'] = 4;
            //转赠券
        }else{
            $where['status'] = 5;
            $store_names = M('Card_new_coupon_share')->alias('a')
                ->field('a.pass_id,b.group_pass')
                ->join(' inner join '.C('DB_PREFIX').'group_pass_relation b ON a.pass_id = b.id')
                ->join(' inner join '.C('DB_PREFIX').'group_pass_get c ON c.group_pass = b.group_pass')
                ->where(['a.uid' => $uid])
                ->order('a.id ASC')
                ->select();
            foreach ($store_names as $item){
                $new_pass_ids[] = $item['pass_id'];
            }
            $where['pass_id'] = $new_pass_ids;
        }
        $where['type'] = $status;
        $card_new_coupon_list = D('System_coupon')->get_user_card_new_coupon(['uid'=>$uid], $where);
        return $card_new_coupon_list;
    }


}