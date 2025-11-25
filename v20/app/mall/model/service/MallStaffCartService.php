<?php


namespace app\mall\model\service;

use app\common\model\db\Area;
use app\common\model\db\User;
use app\common\model\db\UserAdress;
use app\common\model\service\address\AddressService;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallFullGiveAct;
use app\mall\model\db\MallFullMinusDiscountAct;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderCombine;
use app\mall\model\db\MallStaffCart;
use app\mall\model\db\MallStaffCartGoods;
use app\mall\model\db\Merchant;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\merchant\model\db\MerchantStoreStaff;
use app\ole_member_mall\model\db\MerchantUnion;
use app\storestaff\model\service\CouponService;
use think\Exception;
use think\facade\Db;
use think\helper\Arr;

class MallStaffCartService
{
    /**
     * 获取列表
     * @param $params
     * @throws \think\Exception
     */
    public function getGoodsList($params){
        $search_data = Arr::only($params,['sort_id','sort_level','store_id','mer_id','keywords','page','pageSize']);
        $search_data = array_merge($search_data, [
            'search_type' => 1,
            'keyword' => $params['keywords'],
            'audit_status' => 1,
            'offline_sales_status' => 1,
        ]);
        if($params['sort_id']<=0){
            throw new \think\Exception(L_('请选择分类！'));
        }
        $data = (new MallGoodsService())->getMerchantGoodsList($search_data);
        $data['list'] = $data['list']->toArray();
        if($data['list']){
            $goods_id = array_column($data['list'],'goods_id');
            $activity = (new MallActivity())->alias('a')
                ->join('mall_activity_detail d','a.id = d.activity_id')
                ->where([['a.is_del','=',0],['d.goods_id','in',$goods_id],['start_time','<',time()],['end_time','>',time()]])
                ->column('a.id,a.type,a.act_id','goods_id');
            foreach ($data['list'] as $key=>$item){
                $data['list'][$key]['activity_name'] = '';
                if(isset($activity[$item['goods_id']])){
                    $cashier_available = 0; //是否店员端可以使用
                    if($activity[$item['goods_id']]['type'] == 'give' && customization('shanshu_custom')){
                        $cashier_available = (new MallFullGiveAct())->where(['id'=>$activity[$item['goods_id']]['act_id']])->value('cashier_available');
                        $data['list'][$key]['activity_name'] = $cashier_available ? L_('满赠') : '';
                    }elseif($activity[$item['goods_id']]['type'] == 'minus_discount' && customization('shanshu_custom')){
                        $minus_discount_info = (new MallFullMinusDiscountAct())->where(['id'=>$activity[$item['goods_id']]['act_id']])->field('cashier_available,is_discount')->findOrEmpty()->toArray();
                        $cashier_available = $minus_discount_info['cashier_available'];
                        if($minus_discount_info){
                            if($minus_discount_info['is_discount']==0){
                                $data['list'][$key]['activity_name'] = $cashier_available ? L_('满减') : '';
                            }else{
                                $data['list'][$key]['activity_name'] = $cashier_available ? L_('满折') : '';
                            }
                        }
                    }
                    if($cashier_available != 1){
                        unset($data['list'][$key]);
                    }
                }
            }
            $data['list'] = array_values($data['list']);
        }
        return $data;
    }
    
    /**
     * 获取商品规格和备注
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function getGoodsSku($params){
        $goods_id = $params['goods_id'];
        $store_id = $params['store_id'];
        $MallGoodsService = new MallGoodsService();
        $MallGoodsSkuService = new MallGoodsSkuService();
        $MallGoodsSpecService = new MallGoodsSpecService();
        //基本信息
        $basic_info = $MallGoodsService->getBasicInfo($goods_id, true);
        if(empty($basic_info)){
            throw new \think\Exception('当前商品未找到!');
        }
        if($basic_info['store_id']!=$store_id){
            throw new \think\Exception('不是本店铺商品无法操作!');
        }
        $output = [
            'sku' => [
                'types' => [],
                'combination' => [],
            ],
            'notes' => [], //商品备注信息
            'form' => [], //自定义表单
            'goods_type' => $basic_info['goods_type'],
            'price' => $basic_info['price'],
            'stock_num' => $basic_info['stock_num'],
            'goods_id' => $basic_info['goods_id'],
            'imgs' => empty($basic_info['imgs'])?[]:$basic_info['imgs'][0],
            'title' => $basic_info['name'],
            'initial_salenum' => $basic_info['initial_salenum'],
        ];
        //sku信息
        $sku = $MallGoodsSkuService->getSkuByGoodsId($goods_id);
        $sku_types = $MallGoodsSpecService->getList($goods_id);
        $select_sku = isset($sku_info['sku_info']) ? explode('|', $sku_info['sku_info']) : [];
        $select_sku_option = [];
        if($select_sku){
            foreach ($select_sku as $sku_option) {
                $select_sku_option[$sku_option] = 1;
            }
        }
        foreach ($sku_types as $key => $value) {
            $temp = [
                'id' => $value['spec_id'],
                'title' => $value['name'],
                'sons' => []
            ];
            if(isset($value['val_list'])) {
                foreach ($value['val_list'] as $spec_val) {
                    $temp['sons'][] = [
                        'id' => $spec_val['id'],
                        'title' => $spec_val['name'],
                        'display' => 1,
                        'select' => isset($select_sku_option[$value['spec_id'] . ':' . $spec_val['id']]) ? 1 : 0
                    ];
                }
            }
            $output['sku']['types'][] = $temp;
        }
        foreach ($sku as $key => $value) {
            $output['sku']['combination'][$value['sku_info']] = [
                'sku_id' => $value['sku_id'],
                'is_bargain' => 0,
                'price' => get_format_number($value['price']),
                'old_price' => get_format_number($value['price']),
                'stock' => $value['stock_num'],
                'old_stock' => $value['stock_num'],
                'image' => replace_file_domain($value['image']),
                'prepare_deposit' => 0,//预售活动时使用
                'prepare_discount' => 0,//预售活动时使用
            ];
        }
        //组装form 和 其他备注
        $forms = $basic_info['leave_message'] ? @unserialize($basic_info['leave_message']) : [];
        if($forms){
            foreach ($forms as $key => $value) {
                $output['form'][] = [
                    'title' => $value['message'],
                    'type' => $value['type'] == '2' ? 'text' : 'image',
                    'require' => $value['is_must'] != 0 ? '1' : '0'
                ];
            }
        }
        $notes = $basic_info['notes'] ? @unserialize($basic_info['notes']) : [];
        if($notes){
            foreach ($notes as $key => $value) {
                $output['notes'][] = [
                    'property' => $value['name'],
                    'select_num' => $value['num'],
                    'property_val' => $value['val'],
                ];
            }
        }
        return $output;
    }

    /**
     * 添加商品到店员购物车
     * @param $params
     */
    public function addStaffCartGoods($params){
        $goods_id = $params['goods_id'];
        $store_id = $params['store_id'];
        $sku_id = $params['sku_id'];
        $num = $params['num'];
        $form = $params['form'];
        $note = $params['note'];
        $staff_uid = $params['staff_uid'];
        //获取商品
        $goods = (new MallGoods())->where(['goods_id'=>$goods_id,'offline_sales_status'=>1])->findOrEmpty()->toArray();
        if(!$goods){
            throw new \think\Exception(L_('商品不存在/已下架！'));
        }
        if($goods['store_id']!=$store_id){
            throw new \think\Exception(L_('不是本店商品无法操作！'));
        }
        if(empty($goods['offline_sales_status'])){
            throw new \think\Exception(L_('该商品未开启收银台线下售卖！'));
        }
        $db = (new MallStaffCart());
        $db_goods = (new MallStaffCartGoods());
        //查询是否有购物车
        $staff_cart = $db->where(['staff_uid'=>$staff_uid,'status'=>0])->findOrEmpty()->toArray();
        $sku = (new MallGoodsSkuService)->getSkuById($sku_id);//sku信息
        if(empty($sku) || empty($sku['goods_id'])){
            throw new \think\Exception(L_('当前商品未找到或已被删除'));
        }
        if($sku['stock_num']>-1&&$sku['stock_num']<$num){
            throw new \think\Exception(L_('库存不足！'));
        }
        $insert = [
            'mer_id' => $goods['mer_id'],
            'store_id' => $goods['store_id'],
            'goods_id' => $sku['goods_id'],
            'sku_id' => $sku['sku_id'],
            'num' => $num,
            'sku_info' => $sku['sku_str'],
            'join_price' => $sku['price'],
            'create_time' => time(),
            'notes' => $note ?: json_encode([]),
            'forms' => $form ?: json_encode([]),
            'last_time'=>time(),
            'staff_uid' => $staff_uid
        ];
        Db::startTrans();
        try {
            if($staff_cart){
                $staff_cart_goods = $db_goods->where(['cart_id'=>$staff_cart['id'],'goods_id'=>$goods_id,'sku_id'=>$sku_id])->findOrEmpty()->toArray();
                //不能只根据sku判断，要结合属性一起
                $note && $staff_cart_goods = $db_goods->where(['cart_id'=>$staff_cart['id'],'goods_id'=>$goods_id,'sku_id'=>$sku_id,'notes'=>$note])->findOrEmpty()->toArray();
                if($staff_cart_goods){
                    if(($num + $staff_cart_goods['num']) > 9999){
                        throw new \think\Exception("您的购物车里该商品已超出最大购买量9999");
                    }
                    $update = [
                        'num' => $num + $staff_cart_goods['num'],
                        'sku_info' => $sku['sku_str'],
                        'join_price' => $sku['price'],
                        'last_time'=>time()
                    ];
                    $MallActivityService = new \app\mall\model\service\activity\MallActivityService();
                    $act_type=$MallActivityService->getActivity($sku['goods_id'],'','','',$sku['sku_id']);
                    if($act_type['style']=='normal'){
                        if($update['num']>$sku['stock_num'] && $sku['stock_num']!=-1){
                            $update['num']=$sku['stock_num'];
                        }
                    }elseif ($act_type['style']=='limited'){
                        $limited_act = $MallActivityService->getLimitedActGoodsDetail($sku['goods_id'],0,0,0,$sku['sku_id']);
                        if(isset($limited_act['act_stock_num']) && $limited_act['act_stock_num'] != '-1' && $limited_act['act_stock_num']>0){
                            $all_check_num=$num + $staff_cart_goods['num'];
                            if($all_check_num>$limited_act['act_stock_num']){
                                $update['num']=$limited_act['act_stock_num'];
                            }
                        }
                    }
                    $db_goods->where(['id'=>$staff_cart_goods['id']])->save($update);
                }else{
                    $insert['cart_id'] = $staff_cart['id'];
                    $db_goods->save($insert);
                }
            }else{
                $cart_id = $db->insertGetId([
                    'mer_id' => $goods['mer_id'],
                    'store_id' => $goods['store_id'],
                    'staff_uid' => $staff_uid,
                    'create_time' => time(),
                    'status' => 0,
                ]);
                $insert['cart_id'] = $cart_id;
                
                $area = MerchantStoreStaff::alias('ss')
                    ->join('merchant_store s', 'ss.store_id = s.store_id')
                    ->where(['ss.id' => $staff_uid])
                    ->field('s.province_id, s.city_id, s.area_id')
                    ->find();
                $insert['province_id'] = $area['province_id'] ?? 0;
                $insert['city_id'] = $area['city_id'] ?? 0;
                $insert['area_id'] = $area['area_id'] ?? 0;
                $db_goods->save($insert);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 店员购物车商品列表
     * @param $params
     */
    public function staffCartGoodsList($params){
        $staff_uid = $params['staff_uid'];
        $store_id = $params['store_id'];
        $where[] = ['c.staff_uid','=',$staff_uid];
        $where[] = ['c.store_id','=',$store_id];
        $where[] = ['c.status','=',0];
        $staff_cart_goods = (new MallStaffCart())->getList($where);
        $return_arr = [
            'cart_id' => $staff_cart_goods?$staff_cart_goods[0]['cart_id']:0,
            'goods_list' => [],
            'total_price' => 0,
            'activity' => null,
        ];
        
        if($staff_cart_goods){
            $userCartSevice = new CartService();
            $MallGoodsSkuService = new MallGoodsSkuService();
            $skus = $MallGoodsSkuService->getFullSkuInfo(array_column($staff_cart_goods, 'sku_id'));
            //过滤有效数据和无效数据
            $lists = $userCartSevice->filterGoods($skus);
            foreach ($staff_cart_goods as $item){
                if(!isset($lists[$item['sku_id']])){
                    continue;
                }
                $lists[$item['sku_id']]['image'] = $lists[$item['sku_id']]['image'] ? : $lists[$item['sku_id']]['goods_image'];
                $return_arr['goods_list'][] = [
                    'id' => $item['id'],
                    'goods_id' => $item['goods_id'],
                    'name' => $lists[$item['sku_id']]['name'],
                    'image' => replace_file_domain($lists[$item['sku_id']]['image']),
                    'num' => $item['num'],
                    'sku_id' => $item['sku_id'],
                    'initial_salenum' => $lists[$item['sku_id']]['initial_salenum'],
                    'stock_num' => $lists[$item['sku_id']]['stock_num'],
                    'max_num' => $lists[$item['sku_id']]['max_num'],
                    'start_num' => $lists[$item['sku_id']]['initial_salenum'],
                    'original_price' => $lists[$item['sku_id']]['original_price'],
                    'price' => get_format_number($lists[$item['sku_id']]['price']),
                    'sku_info' => $item['sku_info'],
                    'sort_first_id' => $lists[$item['sku_id']]['sort_first'],
                ];
                $return_arr['total_price'] = bcadd($return_arr['total_price'],get_format_number(($lists[$item['sku_id']]['price']*$item['num'])), 2);
            }
            $store_activitys = (new \app\mall\model\service\activity\MallActivityService())->getStoreActivity($store_id, $return_arr['goods_list']);
            if(isset($store_activitys['type']) && $store_activitys['type']){
                $return_arr['activity']['type'] = $store_activitys['type'];
                $return_arr['activity']['title'] = $store_activitys['title'] ?? '';
                $return_arr['activity']['id'] = $store_activitys['id'];
                $return_arr['activity']['status'] = $store_activitys['status'];
                $return_arr['activity']['skuids'] = $store_activitys['satisfied_skuids'] ?? [];
                $return_arr['activity']['buy_limit'] = $store_activitys['buy_limit'] ?? 0;
                $return_arr['activity']['discount_money'] = $store_activitys['satisfied_money'] ?? 0;
                $return_arr['activity']['discount_card'] = $store_activitys['discount_card'];
                $return_arr['activity']['discount_coupon'] = $store_activitys['discount_coupon'];
                if($store_activitys['type'] == 'give' && !empty($store_activitys['give_goods']) && $store_activitys['status'] == 1){
                    $give_skuids = array_keys($store_activitys['give_goods']);
                    $give_skus = $MallGoodsSkuService->getFullSkuInfo($give_skuids);
                    $gives = [];
                    foreach ($give_skus as $give_sku) {
                        $gives[] = [
                            'goods_id' => $give_sku['goods_id'],
                            'sku_id' => $give_sku['sku_id'],
                            'goods_name' => $give_sku['name'],
                            'image' => replace_file_domain($give_sku['image']),
                            'image_original' => $give_sku['image'],
                            'sku_info' => $give_sku['sku_info'],
                            'sku_str' => $give_sku['sku_str'],
                            'num' => $store_activitys['give_goods'][$give_sku['sku_id']]
                        ];
                    }
                    $return_arr['give_goods'] = $gives;
                }
                $goods_id = array_column($return_arr['goods_list'],'goods_id');
                $activity = (new MallActivity())->alias('a')
                    ->join('mall_activity_detail d','a.id = d.activity_id')
                    ->where([['a.is_del','=',0],['d.goods_id','in',$goods_id],['start_time','<',time()],['end_time','>',time()]])
                    ->column('a.id,a.type,a.act_id','goods_id');
                foreach ($return_arr['goods_list'] as &$val){
                    $val['activity_name'] = '';
                    if(isset($activity[$item['goods_id']])){
                        if($activity[$item['goods_id']]['type'] == 'give' && customization('shanshu_custom')){
                            $cashier_available = (new MallFullGiveAct())->where(['id'=>$activity[$item['goods_id']]['act_id']])->value('cashier_available');
                            $val['activity_name'] = $cashier_available ? L_('满赠') : '';
                        }elseif($activity[$item['goods_id']]['type'] == 'minus_discount' && customization('shanshu_custom')){
                            $minus_discount_info = (new MallFullMinusDiscountAct())->where(['id'=>$activity[$item['goods_id']]['act_id']])->field('cashier_available,is_discount')->findOrEmpty()->toArray();
                            $cashier_available = $minus_discount_info['cashier_available'];
                            if($minus_discount_info){
                                if($minus_discount_info['is_discount']==0){
                                    $val['activity_name'] = $cashier_available ? L_('满减') : '';
                                }else{
                                    $val['activity_name'] = $cashier_available ? L_('满折') : '';
                                }
                            }
                        }
                    }
                }
            }
        }
        return $return_arr;
    }

    /**
     * 加减数量
     * @param $params
     */
    public function updateStaffCartGoods($params){
        $staff_uid = $params['staff_uid'];
        $store_id = $params['store_id'];
        $id = $params['id'];
        $num = $params['num'];
        if($num<0||!is_numeric($num)){
            throw new \think\Exception(L_("商品数量必须是大于1的整数！"));
        }
        if(!$id){
            throw new \think\Exception(L_("请选择商品！"));
        }
        if($num==0){
            $this->delStaffCartGoods($params);
        }else{
            $cart_goods = $this->checkStaffCart($id,$staff_uid,$store_id);
            $sku = (new MallGoodsSkuService)->getSkuById($cart_goods['sku_id']);//sku信息
            $saveData = [
                'num' => $num
            ];
            if($sku){
                if($sku['stock_num']>-1&&$sku['stock_num']<$num){
                    throw new \think\Exception(L_("库存不足！"));
                }
                $saveData['join_price'] = $sku['price'];
            }
            try {
                (new MallStaffCartGoods())->where(['id'=>$id])->save($saveData);
            }catch (\Exception $e){
                throw new \think\Exception(L_($e->getMessage()));
            }
        }
        return true;
    }

    /**
     * 删除/清空购物车
     * @param $params
     * @throws \think\Exception
     */
    public function delStaffCartGoods($params){
        $staff_uid = $params['staff_uid'];
        $store_id = $params['store_id'];
        $id = $params['id'];
        Db::startTrans();
        try {
            if($id){
                $cart_goods = $this->checkStaffCart($id,$staff_uid,$store_id);
                (new MallStaffCartGoods())->where(['id'=>$id])->delete();
            }else{
                $staff_cart_id = $params['staff_cart_id'];
                if($staff_cart_id){
                    $order_info = (new MallOrder())->field('order_id,pay_time')->where(['staff_cart_id' => $staff_cart_id])->findOrEmpty()->toArray();
                    if($order_info){
                        if($order_info['pay_time']>0){
                            throw new \think\Exception(L_('订单已支付无法取消！'));
                        }
                        //取消订单
                        (new MallOrderService())->changeOrderStatus($order_info['order_id'],50,'店员取消');
                    }
                    (new MallStaffCart())->where(['id'=>$id])->delete();
                    (new MallStaffCartGoods())->where(['cart_id'=>$staff_cart_id])->delete();
                }else{
                    $cart_id = (new MallStaffCart())->where(['staff_uid'=>$staff_uid,'store_id'=>$store_id,'status'=>0])->value('id');
                    if($cart_id){
                        (new MallStaffCartGoods())->where(['cart_id'=>$cart_id])->delete();
                    }
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * @throws Exception
     */
    private function checkStaffCart($cart_goods_id, $staff_uid, $store_id){
        if(!$cart_goods_id){
            throw new \think\Exception("请选择要修改的商品");
        }
        $cart_goods = (new MallStaffCartGoods())->where(['id'=>$cart_goods_id,'staff_uid'=>$staff_uid,'store_id'=>$store_id])->findOrEmpty()->toArray();
        if(!$cart_goods){
            throw new \think\Exception("商品不存在");
        }
        $cart = (new MallStaffCart())->where(['id'=>$cart_goods['cart_id']])->findOrEmpty()->toArray();
        if($cart['status']!=0){
            throw new \think\Exception("无法操作！");
        }
        return $cart_goods;
    }

    /**
     * 店员下单操作
     * @param $params
     */
    public function saveStaffOrder($params){
        $express_style = $params['express_style'];
        $user_name = $params['user_name'];
        $user_phone = $params['user_phone'];
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $area_id = $params['area_id'];
        $address = $params['address'];
        $detail = $params['detail'];
        $dis_phone = $params['dis_phone'];
        $store_id = $params['store_id'];
        $staff_uid = $params['staff_uid'];
        $lng = $params['lng'];
        $lat = $params['lat'];
        if(!$user_name) {
            throw new \think\Exception(L_("用户名不能为空！"));
        }
        $preg_phone = '/^1((34[0-8]\d{7})|((3[0-3|5-9])|(4[5-7|9])|(5[0-3|5-9])|(66)|(7[2-3|5-8])|(8[0-9])|(9[1|8|9]))\d{8})$/';
        if(!preg_match($preg_phone,$user_phone)){
            throw new \think\Exception(L_("手机号格式不正确！"));
        }
        if($dis_phone&&!preg_match($preg_phone,$dis_phone)){
            throw new \think\Exception(L_("分销员手机号格式不正确！"));
        }
        $area_info = (new Area())->alias('a')
            ->join('area c','a.area_pid = c.area_id')
            ->join('area p','c.area_pid = p.area_id')
            ->where(['a.area_id'=>$area_id,'c.area_id'=>$city_id,'p.area_id'=>$province_id])
            ->findOrEmpty()->toArray();
        if(!$area_info){
            throw new \think\Exception(L_("省市区有误！"));
        }
        if(!in_array($express_style,[1,2,3])){
            throw new \think\Exception(L_("配送方式有误！"));
        }
        if(!$lng||!$lat){
            throw new \think\Exception(L_("经纬度不能为空！"));
        }
        $user = (new User())->where(['phone'=>$user_phone])->findOrEmpty()->toArray();
        if($user&&in_array($user['status'],[0,4])){
            throw new \think\Exception(L_("该用户账号已被禁止，请联系管理员！"));
        }
        //查询购物车是否存在
        $staff_cart = (new MallStaffCart())->where(['staff_uid'=>$staff_uid,'status'=>0])->findOrEmpty()->toArray();
        if(!$staff_cart){
            throw new \think\Exception(L_("订单不存在/已下单！"));
        }
        if(!$user){
            //不存在自动注册
            $user_data[] = [
                'phone' => $user_phone,
                'add_time' => time(),
                'last_time' => time(),
                'add_ip' => get_client_ip(0),
                'status' => 1,
                'score_clean_time' => time(),
                'nickname' => $user_name
            ];
            if(cfg('live_show_new_user_open') == '1'){
                $user_data['open_live_show'] = 1;
            }
            if(cfg('video_show_new_user_open') == '1'){
                $user_data['open_live_video'] = 1;
            }
            $user_id = (new User())->insertGetId($user_data);
        }else{
            $user_id = $user['uid'];
        }
        Db::startTrans();
        try {
            //添加用户地址
            $user_address_data = [
                'uid' => $user_id,
                'name' => $user_name,
                'province' => $province_id,
                'city' => $city_id,
                'area' => $area_id,
                'adress' => $address,
                'store_id' => $store_id,
                'longitude' => $lng,
                'latitude' => $lat,
                'detail' => $detail,
                'phone_country_type' => 86,
            ];
            $address_id = (new UserAdress())->insertGetId($user_address_data);
            $staff_cart_data = [
                'province_id' => $province_id,
                'city_id' => $city_id,
                'area_id' => $area_id,
                'address_id' => $address_id,
                'express_style' => $express_style,
                'uid' => $user_id,
            ];
            (new MallStaffCart())->where(['staff_uid'=>$staff_uid,'status'=>0])->save($staff_cart_data);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        //生成图片二维吗
        $qrcodeUrl = cfg('site_url').'/packapp/plat/pages/shopmall_third/placeOrder?type=cart&address_id='.$address_id.'&staff_cart_id='.$staff_cart['id'].'&uid='.$user_id.'&un_distributor_uid='.$staff_cart_data['share_id'];
        $qrcodeUrl = $this->getQrCode($staff_cart['id'],$qrcodeUrl);
        return ['code_url'=>$qrcodeUrl];
    }

    /**
     * 获取二维码
     */
    public function getQrCode($code,$text,$folder='staffcart')
    {
        $date = date('Y-m-d');
        $time = date('Hi');
        require_once '../extend/phpqrcode/phpqrcode.php';
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/'.$folder.'/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/'.$folder.'/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($text, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/'.$folder.'/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        return cfg('site_url').'/'.$QR;
    }

    /**
     * 获取店员代下单商品
     * @param $uid
     * @param $cart_id
     */
    public function getStaffCart($uid,$cart_id){
        $where[] = ['c.id','=',$cart_id];
        $where[] = ['c.status','=',0];
        $goods_list = (new MallStaffCart())->getList($where);
        foreach ($goods_list as $key => $item) {
            $goods_list[$key]['uid'] = $uid;
            $goods_list[$key]['is_select'] = 1;
            $goods_list[$key]['is_del'] = 0;
            $goods_list[$key]['share_id'] = 0;
            $goods_list[$key]['is_agent_goods'] = 0;
            $goods_list[$key]['unPlusSource'] = '';
        }
        return $goods_list;
    }

    /**
     * 用户扫码下单后修改状态
     * @param $uid
     * @param $cart_id
     * @return bool
     */
    public function delCart($uid,$cart_id,$order_id){
        $staff_cart = (new MallStaffCart())->where(['id'=>$cart_id])->findOrEmpty()->toArray();
        if(!$staff_cart){
            return false;
        }
        if($staff_cart['status']!=0){
            return false;
        }
        try {
            (new MallStaffCart())->where(['id'=>$cart_id])->save(['status'=>1,'order_id'=>$order_id]);
        }catch(\Exception $e){
            fdump_api([$uid,$cart_id,$order_id],'staff_cart/del_cart',1);
        }
        return true;
    }

    /**
     * 校验当前购物车是否已下单
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function checkCart($params){
        $cart_id = $params['id'];
        $staff_uid = $params['staff_uid'];
        $store_id = $params['store_id'];
        $staff_cart = (new MallStaffCart())->where(['id'=>$cart_id,'staff_uid'=>$staff_uid,'store_id'=>$store_id])->findOrEmpty()->toArray();
        if(!$staff_cart){
            throw new \think\Exception(L_('信息有误，请刷新重试！'));
        }
        if($staff_cart['status']!=0){
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function cartConfirm($staffCartId, $params): array
    {
        $discountMoney = 0;
        $cartService = new CartService();
        $uid = $params['uid'] ?: 0;
        $cart = $cartService->computeCart($uid, true, $discountMoney,0,0, $staffCartId);
        if(empty($cart)){
            throw_exception('未检测到选中商品');
        }
        $store = [
            $params['store_id'] => [
                'express_style' => 3,
                'coupon_id' => $params['store_coupon_id'] ?: 0,
                'merchant_card' => $params['merchant_card'] ?: 0,
            ]
        ];
        $params['store'] = !empty($params['store'])?json_decode($params['store'],true):[];
        $unionCard = [];
        if(!empty($params['user_card_id'])) {
            $unionCard = $this->checkUnionMerchant($params['user_card_id'], $params['union_id'], $params['store'], $params);
        }
        $store[$params['store_id']] = array_merge($store[$params['store_id']],($params['store'][$params['store_id']]??[]));
        $cart['from_staff_cashier'] = true;
        $cart['plat_paper_coupon_code'] = $params['plat_paper_coupon_code'] ?? '';
        $cart['mer_paper_coupon_code'] = $params['mer_paper_coupon_code'] ?? ''; 
        $cart['union_card'] = $unionCard;
        $platCouponId = $params['plat_coupon_id'] ?? 0;
        $store = json_encode($store);
        return $cartService->confirmCartData($uid, $cart, 0, $platCouponId, $store, $discountMoney,'cart',$staffCartId);
    }
    
    public function checkUnionMerchant($userCardId, $unionId, $store, $params): array
    {
        if ($store) {
            $store = array_values($store)[0];
        }
        $union = MerchantUnion::where([
            'id' => $unionId,
            'is_delete' => 0,
            'status' => 1
        ])
            ->whereFindInSet('mer_ids', $params['mer_id'])
            ->field('id union_id,union_name, all_use, discount, mer_ids, deducted_score')
            ->find()
            ->toArray();
        if (empty($union['all_use']) && (!empty($store['use_merchant_card'] || !empty($store['use_ole_card'])))) {
            throw_exception('该商户联盟折扣不可与其他会员卡同时使用');
        }
        $merchantCards = Merchant::alias('m')
            ->join('card_userlist cl', 'm.mer_id = cl.mer_id')
            ->where('cl.id', $userCardId)
            ->where('cl.status', 1)
            ->where('m.mer_id', 'IN', $union['mer_ids'])
            ->where('cl.mer_id', '<>', $params['mer_id'])
            ->field('m.mer_id, m.name merchant_name, cl.id user_card_id, cl.uid, cl.card_score')
            ->find()
            ->toArray();
        if (empty($merchantCards)) {
            throw_exception('联盟会员卡不存在，信息有误');
        }
        $union['discount_title'] = (float)$union['discount'] . '折优惠';
        return array_merge($merchantCards, $union);
    }
    public function checkOrder($staffCartId, array $params, $staffUser, $staffDiscount = -1): array
    {
        if (
            $staffDiscount >= 0 &&
            !in_array($staffDiscount, $staffUser['order_discount_setting']) &&
            ($staffDiscount < $staffUser['max_discount'])
        ) {
            throw_exception('您暂无此折扣权限，请联系店长');
        }
        $checkCart = $this->checkCart($params);
        if ($checkCart){
            throw_exception('该订单已下单');
        }
        $this->checkPaperCoupon($params, $staffUser);
        $data = $this->cartConfirm($staffCartId, $params);
        foreach ($data['order'] as &$order) {
            foreach ($order['goods_list'] as $goods) {
                $goods = (new MallGoodsService)->getOne($goods['goods_id']);//商品信息
                if(isset($goods['audit_status'])&&$goods['audit_status']!=1){
                    throw_exception(L_("商品【X1】未审核，暂无法下单",['X1'=>$goods['name']]));
                }
                if(isset($goods['offline_sales_status'])&&$goods['offline_sales_status']!=1){
                    throw_exception(L_("商品【X1】未开起收银台线下售卖，暂无法下单",['X1'=>$goods['name']]));
                }
            }
            if(!empty($params['staff_discount_money']) && $staffUser['max_discount']) {
                if($params['staff_discount_money'] < bcmul($data['money'], $staffUser['max_discount']/100, 2)) {
                    throw_exception(L_("您暂无此优惠金额权限，请联系店长"));
                }
                $order['staff_change_order_price'] = $params['staff_discount_money'];
            }else
            if($staffDiscount >= 0){
                $order['staff_change_order_price'] = bcmul($data['money'], $staffDiscount/100, 2);//修改订单价格
            }
        }
        
        return $data;
    }
    public function checkPaperCoupon($params, $staffUser): array
    {
        $paperCoupon = [];
        try{
            if(!empty($params['plat_paper_coupon_code'])) {//平台券
                if($params['plat_coupon_id'] > 0) {
                    throw_exception('平台优惠券与纸质券不可同时使用');
                }
                $platPaperCoupon = (new CouponService($staffUser))->getCodeInfo([
                    'code' => $params['plat_paper_coupon_code'],
                    'staff_user' => $staffUser,
                ]);
                if($platPaperCoupon['status']) {
                    throw_exception($platPaperCoupon['status_txt']);
                }
                if($platPaperCoupon['type'] != 'system') {
                    throw_exception("非平台优惠券");
                }
                $paperCoupon['plat_paper_coupon'] = $platPaperCoupon;
            }
            if(!empty($params['mer_paper_coupon_code'])) {//商家券
                if($params['store_coupon_id'] > 0) {
                    throw_exception('商家优惠券与纸质券不可同时使用');
                }
                $merPaperCoupon = (new CouponService($staffUser))->getCodeInfo([
                    'code' => $params['mer_paper_coupon_code'],
                    'staff_user' => $staffUser,
                ]);
                if($merPaperCoupon['status']) {
                    throw_exception($merPaperCoupon['status_txt']);
                }
                if($merPaperCoupon['type'] != 'mer') {
                    throw_exception("非商家优惠券");
                }
                $paperCoupon['mer_paper_coupon'] = $merPaperCoupon;
            }
        }catch (\Exception $e){
            throw_exception("{$params['plat_paper_coupon_code']}优惠券:".$e->getMessage());
        }
        
        return $paperCoupon;
    }
    /**
     * 店员下单(微信小程序扫码)
     * @param $parmas
     * @return string[]
     * @throws Exception
     */
    public function wechatMiniProgramPayment($parmas){
        $store_id = $parmas['store_id'];
        $staff_uid = $parmas['staff_uid'];
        $staff_cart_id = $parmas['staff_cart_id'];
        $staff_remark = $parmas['staff_remark'];
        //查询购物车是否存在
        $staff_cart = (new MallStaffCart())->where(['store_id'=>$store_id,'staff_uid'=>$staff_uid,'id'=>$staff_cart_id])->findOrEmpty()->toArray();
        if(!$staff_cart){
            throw new \think\Exception(L_("订单不存在！"));
        }
        if($staff_cart['status'] != 0){
            throw new \think\Exception(L_("已分配给用户！"));
        }
        try {
            $staff_cart_data = [
                'remark' => $staff_remark,
                'is_qr_code' => 1,
            ];
            (new MallStaffCart())->where(['staff_uid'=>$staff_uid,'is_qr_code'=>0])->save($staff_cart_data);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        //生成小程序码
        $qrcodeUrl = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page='.urlencode('pages/shopmall_third/placeOrder?type=cart&staff_cart_id='.$staff_cart['id']);
        return ['code_url'=>$qrcodeUrl];
    }
}