<?php
/**
 * Created by PhpStorm.
 * Author: lumin
 */

namespace app\mall\model\service;
use app\common\model\service\percent_rate\PercentRateService;
use app\mall\model\db\Area;
use app\mall\model\db\MallCartNew;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MallGoodsSku;
use app\mall\model\db\MerchantStore;
use app\mall\model\db\User;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\MerchantService;
use app\mall\model\service\activity\MallActivityService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\UserService;
use app\common\model\service\UserLevelService;
use app\common\model\service\UserAdressService;
use app\common\model\service\AreaService;
use app\common\model\service\MerchantStoreService as ServiceMerchantStoreService;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\third_order_deliver\DeliverApiService;
use app\merchant\model\service\MerchantStoreService;
use app\mall\model\service\MerchantStoreMallService;
use app\mall\model\service\ExpressTemplateService;
use app\mall\model\service\MallRiderService;
use app\mall\model\service\MallOrderDetailService;
use app\mall\model\service\MallOrderService;
use app\mall\model\db\MallOrder;
use app\store_marketing\model\db\StoreMarketingPerson;
use app\store_marketing\model\db\StoreMarketingPersonSetprice;
use app\store_marketing\model\db\StoreMarketingShareLog;
use map\longLat;

class CartService
{
    /**
    * 添加购物车数据
    * @param [type]  $uid   [description]
    * @param [type]  $sku   一整条的sku数据信息 （对应pigcms_mall_goods_sku表）
        * @param [type]  $goods 一整条的商品数据信息 （对应pigcms_mall_goods表）
        * @param integer $num   [description]
    */
    public function addCart($uid, $sku, $goods, $num = 1, $note = '', $form = '', $share_id = 0){
        $insert = [
            'mer_id' => $goods['mer_id'],
            'store_id' => $goods['store_id'],
            'uid' => $uid,
            'goods_id' => $sku['goods_id'],
            'sku_id' => $sku['sku_id'],
            'num' => $num,
            'sku_info' => $sku['sku_str'],
            'join_price' => $sku['price'],
            'create_time' => time(),
            'is_del' => 0,
            'is_select' => 1,
            'notes' => $note ?: json_encode([]),
            'forms' => $form ?: json_encode([]),
            'share_id' => $share_id,
        ];
        $db = new MallCartNew();
        $has = $db->getOne($sku['sku_id'], $uid, $share_id);
        if($has){
            if(($num + $has['num']) > 9999){
                throw new \think\Exception("您的购物车里该商品已超出最大购买量9999", 1003);
            }

            //不能只根据sku判断，要结合属性一起
            $note && $has = $db->getOneBySkuAndProperty($sku['sku_id'],$note, $uid, $share_id);
            if($has){
                $update = [
                    'num' => $num + $has['num'],
                    'sku_info' => $sku['sku_str'],
                    'join_price' => $sku['price'],
                    'is_select' => 1,
                ];
                $MallActivityService = new MallActivityService;
                $act_type=$MallActivityService->getActivity($sku['goods_id'],'','','',$sku['sku_id']);
                if($act_type['style']=='normal'){
                   if($update['num']>$sku['stock_num'] && $sku['stock_num']!=-1){
                       $update['num']=$sku['stock_num'];
                   }
                }elseif ($act_type['style']=='limited'){
                    $limited_act = $MallActivityService->getLimitedActGoodsDetail($sku['goods_id'],0,0,0,$sku['sku_id']);
                    if(isset($limited_act['act_stock_num']) && $limited_act['act_stock_num'] != '-1' && $limited_act['act_stock_num']>0){
                        $all_check_num=$num + $has['num'];
                        if($all_check_num>$limited_act['act_stock_num']){
                            $update['num']=$limited_act['act_stock_num'];
                        }
                    }
                }
                $ret=$db->updateThis(['id'=>$has['id']], $update);
                if($ret!==false){
                    return true;
                }
                return false;
            }else{
                return $db->add($insert);
            }
        }
        else{
            return $db->add($insert);
        }
    }

    /**
     * 删除购物车数据
     * @param  [type] $uid     [description]
     * @param  [type] $cart_id [description]
     * @return [type]          [description]
     */
    public function delCart($uid, $cart_id){
        $where = [
            ['uid', '=', $uid]
        ];
        if(is_array($cart_id)){
            $where[] = ['id', 'in', $cart_id];
        }
        else{
            $where[] = ['id', '=', $cart_id];
        }
        $db = new MallCartNew();
        return $db->updateThis($where, ['is_del' => 1]);
    }

    /**
     * 选中购物车数据
     * @param  [type] $uid     [description]
     * @param  [type] $cart_id [description]
     * @return [type]          [description]
     */
    public function selectCart($uid, $cart_id, $select = 1){
        $where = [
            ['uid', '=', $uid],
            ['is_del', '=', 0]
        ];
        if(is_array($cart_id)){
            $where[] = ['id', 'in', $cart_id];
        }
        else{
            $where[] = ['id', '=', $cart_id];
        }
        $db = new MallCartNew();
        return $db->updateThis($where, ['is_select' => $select]);
    }

    /**
     * 通过skuid数组，取消选中
     * @param  [type] $uid    [description]
     * @param  [type] $skuids [description]
     * @return [type]         [description]
     */
    public function unSelectBySkuid($uid, $skuids){
        $where = [
            ['uid', '=', $uid],
            ['sku_id', 'in', $skuids]
        ];
        $db = new MallCartNew();
        return $db->updateThis($where, ['is_select' => 0]);
    }

    /**
     * 修改购物车数据（修改num）
     * @param  [type] $uid     [description]
     * @param  [type] $cart_id [description]
     * @param  [type] $num 修改之后的数量
     * @return [type]          [description]
     */
    public function updateCart($uid, $cart_id, $num, $sku_id = 0, $note = '', $form = ''){
        if(intval($num) <= 0) return false;
        $where = [
            ['id', '=', $cart_id],
            ['uid', '=', $uid],
            ['is_del', '=', 0],
        ];
        $db = new MallCartNew();
        $update_data = ['num' => $num];
        if($sku_id){
            $sku = (new MallGoodsSkuService)->getSkuById($sku_id);//sku信息
            $update_data['sku_id'] = $sku_id;
            $update_data['join_price'] = $sku['price'];
        }
        if($note){
            $update_data['notes'] = $note;
        }
        if($form){
            $update_data['forms'] = $form;
        }
        return $db->updateThis($where, $update_data);
    }

    /**
     * 获取用户的购物车数据
     * @param  [type] $uid 用户ID
     * @return [type]      [description]
     */
    public function getUserCart($uid){
        if(empty($uid)) return [];
        $where = [
            ['uid', '=', $uid],
            ['is_del', '=', 0],
        ];
        //如果是商家小程序，则过滤掉购物车不属于该商家的商品
        $wxappOwnMerId = request()->param('wxapp_own_mer_id', 0, 'intval');
        if ($wxappOwnMerId > 0) {
            $where[] = ['mer_id', '=', $wxappOwnMerId];
        }
        $db = new MallCartNew();
        $data = $db->getSome($where, true, 'id desc')->toArray();
        return $data;
    }

    /**
     * 过滤sku数据，无效（商品sku库存为0，或者该商品添加购物车的sku后台编辑删除）  
     * @param  [type] $skus 完整的sku数据信息
     * @return [type]       [description]
     */
    public function filterGoods($skus){
        if(empty($skus)) return [];
        foreach ($skus as $k=>&$value) {
            if($value['stock_num'] == '0'){
                $value['invalid'] = 1;
            }
            elseif ($value['is_del'] == '1') {
                $value['invalid'] = 1;
            }
            elseif ($value['goods_del'] == '1') {
                $value['invalid'] = 1;
            }
            elseif ($value['status'] == '0') {
                $value['invalid'] = 1;
            }
            elseif ($value['have_mall'] == '0') {

                unset($skus[$k]);
            }
            else{
                $value['invalid'] = 0;
            }
        }
        return $skus;
    }

    /**
     * 计算购物车
     * @param  [type]  $uid    用户ID
     * @param  boolean $compute_select 是否只结算用户选中的数据
     * @return [type]          [description]
     */
    public function computeCart($uid, $compute_select = false, &$ret_discount_money = 0, $form=0,$address_id=0){
        $output = [
            'normal_list' => [],//有效数据
            'no_effective_list' => [],//无效数据
            'settlement' => [
                'no_effective_nums' => 0,
                'nums' => 0,//购物车选中的数据总量
                'price' => 0,//购物车选中的数据价格总和
                'discount' => 0,//购物车选中的数据计算优惠总和
            ],
            'address' => [
                'title' => '',
                'id' => 0,
                'province' => 0,
                'city' => 0,
                'area' => 0,
            ]
        ];
        if(empty($uid)){
            return $output;
        }

        $UserAdressService = new UserAdressService();
        if(cfg('open_mall_order_area')){
            //购物车进来如果选择的地址id和记录的id不一致修改记录id并清空购物车已选状态
            $last_address_id = (new User())->where(['uid'=>$uid])->value('mall_cart_address_id');
            if((!$last_address_id||($address_id&&$last_address_id!=$address_id))&&!$compute_select){
                (new MallCartNew())->where([['uid', '=', $uid], ['is_del', '=', 0], ['is_select', '=', 1]])->save(['is_select'=>0]);
            }
            if($last_address_id&&!$address_id){
                $address_id = $last_address_id;
            }
        }
        if($address_id){
            $address_info = $UserAdressService->getAdressByAdressid($address_id);
            if($address_info){
                $output['address']['title'] = $address_info['title'] ?? '';
                $output['address']['id'] = $address_id;
                $output['address']['province'] = $address_info['province'];
                $output['address']['city'] = $address_info['city'];
                $output['address']['area'] = $address_info['area'];
                $output['address']['lng'] = $address_info['longitude'];
                $output['address']['lat'] = $address_info['latitude'];
            }
        } elseif($uid){
            //选择一个默认的收货地址
            $now_user_address = $UserAdressService->getAdressByUid($uid, 1);
            if($now_user_address){
                $output['address']['title'] = $now_user_address[0]['title'] ?? '';
                $output['address']['id'] = $now_user_address[0]['adress_id'] ?? 0;
                $output['address']['province'] = $now_user_address[0]['province'];
                $output['address']['city'] = $now_user_address[0]['city'];
                $output['address']['area'] = $now_user_address[0]['area'];
                $output['address']['lng'] = $now_user_address[0]['longitude'];
                $output['address']['lat'] = $now_user_address[0]['latitude'];
            }
        }
        $goods_area_id = [];
        if(cfg('open_mall_order_area')){
            $goods_area_id[] = 0;
            if($output['address']['area']>0){
                if($output['address']['area']<=0){
                    $children_area = (new Area())->where(['area_pid'=>$output['address']['city'],'is_open'=>1])->column('area_id');
                    $goods_area_id[] = array_merge($goods_area_id,$children_area);
                }
                $goods_area_id[] = $output['address']['area'];
            }
            if($output['address']['city']>0){
                $goods_area_id[] = $output['address']['city'];
            }
            if($output['address']['province']>0){
                $goods_area_id[] = $output['address']['province'];
            }
            if($form){
                (new User())->where(['uid'=>$uid])->save(['mall_cart_address_id'=>$output['address']['id']]);
            }
        }
        
        //获取所有数据
        $all_cart_data = $this->getUserCart($uid);
        if(empty($all_cart_data)){
            return $output;
        }

        //获取sku完整信息(映射)
        $MallGoodsSkuService = new MallGoodsSkuService;
        $skus = $MallGoodsSkuService->getFullSkuInfo(array_column($all_cart_data, 'sku_id'));

        //过滤有效数据和无效数据
        $lists = $this->filterGoods($skus);
        $MallGoodsService = new MallGoodsService;
        //组装数据
        $normal_list = [];//有效购物车商品数据
        $no_effective_list = [];//无效购物车商品数据
        foreach ($all_cart_data as $key => $value) {
            if(!isset($lists[$value['sku_id']])){
                continue;
            }
            $lists[$value['sku_id']]['image'] = $lists[$value['sku_id']]['image'] ? : $lists[$value['sku_id']]['goods_image'];
            $temp = [
                'cart_id' => $value['id'],
                'sku_id' => $value['sku_id'],
                'common_goods_id'=>$lists[$value['sku_id']]['common_goods_id'],
                'goods_id' => $value['goods_id'],
                'goods_name' => $lists[$value['sku_id']]['name'],
                'cate_first' => $lists[$value['sku_id']]['cate_first'],
                'fright_id' => $lists[$value['sku_id']]['fright_id'],
                'other_area_fright' => $lists[$value['sku_id']]['other_area_fright'],
                'image' => replace_file_domain($lists[$value['sku_id']]['image']),
                'image_original' => $lists[$value['sku_id']]['image'],
                'sku_str' => $lists[$value['sku_id']]['sku_str'],
                'price' => get_format_number($lists[$value['sku_id']]['price']),
                'original_price' => get_format_number($lists[$value['sku_id']]['price']),
                'end_price' => 0,
                'service_desc' => $lists[$value['sku_id']]['service_desc'],
                'isreduce' => $value['join_price'] > $lists[$value['sku_id']]['price'] ? 1 : 0,
                'reduce_price' => get_format_number($value['join_price'] - $lists[$value['sku_id']]['price']),
                'is_restriction' => $lists[$value['sku_id']]['is_restriction'],
                'restriction_type' => $lists[$value['sku_id']]['restriction_type'],
                'restriction_periodic' => $lists[$value['sku_id']]['restriction_periodic'],
                'restriction_num' => $lists[$value['sku_id']]['restriction_num'],
                'stock_num' => $lists[$value['sku_id']]['stock_num'],
                'num' => $value['num'],
                'max_num' => $lists[$value['sku_id']]['max_num'],
                'start_num' => $lists[$value['sku_id']]['initial_salenum'],
                'mer_id' => $lists[$value['sku_id']]['mer_id'],
                'store_id' => $lists[$value['sku_id']]['store_id'],
                'store_name' => $lists[$value['sku_id']]['store_name'],
                'is_select' => $value['is_select'],
                'free_shipping' => $lists[$value['sku_id']]['free_shipping'],
                'notes' => $value['notes'],
                'forms' => $value['forms'],
                'property' => [],
                'more_info' => [],
                'activity_type' => '',
                'activity_id' => 0,
                'activity_title' => '',
                'activity_surplus' => 0,
                'activity_buy_limit' => 0,//限购数量 0=不限购
                'activity_discount_card' => 0,
                'activity_discount_coupon' => 0,
                'activity_field' => '',
                'is_free_shipping' => 0,
                'is_marketing_goods' => 0,
                'share_id' => 0,
                'initial_salenum' => $lists[$value['sku_id']]['initial_salenum'],
                'is_out_range' => 0,
                'note' => '',
		'unPlusSource' => $value['unPlusSource'] ?? '',
                'last_time' => $value['last_time'] ?? 0,
            ];
            $get_limit_buy = $MallGoodsService->get_limit_buy($lists[$value['sku_id']]['goods_id'], $lists[$value['sku_id']]['is_restriction'], $lists[$value['sku_id']]['restriction_type'], $lists[$value['sku_id']]['restriction_periodic'], $lists[$value['sku_id']]['restriction_num']);
            
            $temp['max_num_type'] = $get_limit_buy['type'];
            $temp['max_num'] = $get_limit_buy['nums'];
            if(!empty($value['notes'])){
                $_notes = json_decode($value['notes'], true);
                foreach ($_notes as $n) {
                    $temp['property'][] = $n['property'].':'.implode('，', $n['property_val']);
                }
            }
            if(!empty($value['forms'])){
                $_forms = json_decode($value['forms'], true);
                foreach ($_forms as $f) {
                    if($f['type'] == 'image' && !empty($f['val'])){
                        $f['val'] = replace_file_domain($f['val']);
                    }
                    $temp['more_info'][] = ['title'=>$f['title'], 'type' => $f['type'], 'val' => $f['val']];
                }
            }

            //店铺分销员分销商品
            $temp['share_id'] = 0;
            if ($value['share_id'] > 0) {
                $shareData = (new StoreMarketingShareLog())->where([['id', '=', $value['share_id']]])->find();
                if (empty($shareData)) {
                    return api_output_error(1001, '分享id有误!');
                }
                if ($shareData['create_time'] > time() - 86400) {//分享链接24小时之内有效
                    $setpriceData = (new StoreMarketingPersonSetprice())->where([['share_id', '=', $shareData['id']], ['person_id', '=', $shareData['person_id']], ['goods_type', '=', 2], ['goods_id', '=', $temp['goods_id']], ['specs_id', '=', $temp['sku_id']]])->find();
                    if (empty($setpriceData)) {
                        $setpriceData = [
                            'share_id' => $shareData['id'],
                            'person_id' => $shareData['person_id'],
                            'goods_id' => $temp['goods_id'],
                            'goods_type' => 2,
                            'specs_id' => $temp['sku_id'],
                            'price' => $temp['price'],
                            'create_time' => time()
                        ];
                        $setpriceData['id'] = (new StoreMarketingPersonSetprice())->insertGetId($setpriceData);
                    }
                    $temp['share_id'] = $shareData['id'];
                    $temp['price'] = $setpriceData['price'];
                    $temp['is_marketing_goods'] = 1;
                }
            }
	    
            $merchant_store = (new MerchantStore())->alias('s')->field('s.store_id,s.mer_id,s.long,s.lat')
                ->join('merchant m','s.mer_id = m.mer_id')
                ->where(['m.status'=>1,'s.status'=>1,'s.store_id'=>$value['store_id']])
                ->findOrEmpty()->toArray();
            if(cfg('open_mall_order_area')==1){
                if($goods_area_id){
                    //获取商品服务范围
                    $goods_area = (new MallGoodsArea())->where([['goods_id','=',$temp['goods_id']],['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                    if(!$goods_area){
                        $temp['is_out_range'] = 1;
                        $temp['note'] = '超出可服务范围，无法下单';
                    }
                }
            }elseif(cfg('open_mall_order_area')==2){
                if(!empty($output['address']['lat'])&&!empty($output['address']['lng'])){
                    $temp['is_out_range'] = (new MallDeliveryService())->checkRange($output['address'],[$merchant_store])['is_out_range'];
                    if($temp['is_out_range']){
                        $temp['note'] = '超出可服务范围，无法下单';
                    }
                }
            }
            if($lists[$value['sku_id']]['invalid'] == '1'||!$merchant_store){
                $output['settlement']['no_effective_nums']++;
                $no_effective_list[] = $temp;
            }
            else{
                $normal_list[] = $temp;
            }
        }

        //无效数据按店铺分组
        $no_effective_group_store = [];
        foreach ($no_effective_list as $key => $value) {
            if(isset($no_effective_group_store[$value['store_id']])){
                $no_effective_group_store[$value['store_id']]['goods_list'][] = $value;
            }
            else{
                $store_info = (new MerchantStoreService)->getOne(['store_id' => $value['store_id']]);
                
                $no_effective_group_store[$value['store_id']] = [
                    'store_id' => $value['store_id'],
                    'store_name' => $value['store_name'],
                    'lng' => $store_info['long'] ?? '',
                    'lat' => $store_info['lat'] ?? '',
                    'adress' => $store_info['adress'] ?? '',
                    'phone' => $store_info['phone'] ?? '',
                    'coupoun_list' => [],
                    'activity' => [
                        'type' => '',//活动类型
                        'title' => '',//活动标题
                        'id' => 0,//活动ID
                        'status' => 0,//是否满足活动条件  0=未满足 1=已满足
                        'discount_money' => 0,//满足活动条件之后 要扣除的金额
                        'skuids' => [],//满足活动条件之后，要去除的对应活动skuid，如果不满足活动，这里就为空
                        'buy_limit' => 0,//限购次数  0=不限购
                        'discount_card' => 0,//是否与会员卡同享
                        'discount_coupon' => 0,//是否与优惠券同享
                    ],
                    'goods_list' => [$value],
                    'give_goods' => [],//赠品
                ];  
            }
        }

        /**
         * 针对有效数据按店铺分组
         * 获取商家优惠券
         * 获取满减优惠
         * 获取赠品信息
         */
        $group_store = [];
        $MerchantCouponService = new MerchantCouponService;
        $MallActivityService = new MallActivityService;
        foreach($normal_list as $v){
            if($v['is_select']){
                $goodsNums[$v['goods_id']] = $goodsNums[$v['goods_id']] ?? 0;
                $goodsNums[$v['goods_id']] += $v['num'];
            }
        }
        foreach ($normal_list as $key => $value) {
            if($compute_select && $value['is_select'] != 1) continue;//只结算已选中(且有效)的商品
            //获取限时优惠活动
            $limited_act = $MallActivityService->getLimitedActGoodsDetail($value['goods_id'],0,0,0,$value['sku_id']);
            $limited_act_discount_money = 0;//限时优惠活动优惠的金额
            if($limited_act['style'] == 'limited'){
                $check_join = true;
                if(isset($limited_act['buy_limit']) && $limited_act['buy_limit'] > 0){
                    $join_nums = (new MallOrderDetailService)->getActivityJoinNums('limited', $limited_act['id'], $uid);
                    $nowGoodsNums = $goodsNums[$value['goods_id']] ?? $value['num'];
                    if($join_nums + $nowGoodsNums > $limited_act['buy_limit']){
                        $check_join = false;
                    }
                }
                if($check_join){
                    $value['activity_type'] = $limited_act['style'];
                    $value['activity_id'] = $limited_act['id'];
                    $value['activity_title'] = $limited_act['name'];
                    $value['activity_surplus'] = $limited_act['surplus'];
                    $value['limit_count_down'] = $limited_act['surplus'];
                    $value['activity_buy_limit'] = $limited_act['buy_limit'];
                    $value['activity_discount_card'] = $limited_act['discount_card'];
                    $value['activity_discount_coupon'] = $limited_act['discount_coupon'];
                    $limited_act_discount_money = ($value['num'] * $value['price'])-($value['num'] * $limited_act['price']);
                    $value['price'] = get_format_number($limited_act['price']);
                    if(isset($limited_act['act_stock_num']) && $limited_act['act_stock_num'] != '-1'){
                        if($value['stock_num'] == '-1'){
                            $value['stock_num'] = $limited_act['act_stock_num'];
                        }
                        else{
                            $value['stock_num'] = $limited_act['act_stock_num'];
                        }
                    }
                }else if($form != 1){
                    throw new \think\Exception("您参与该活动的次数已达上限！", 1003);
                }
            }

            if(isset($group_store[$value['store_id']])){
                $group_store[$value['store_id']]['goods_list'][] = $value;
            }
            else{
                //获取商家优惠券
                $coupons = $MerchantCouponService->getMerchantCouponList($value['mer_id'], 0, 'mall', '', $uid);
                $deal_coupons = [];
                foreach ($coupons as $cpons) {
                    if($cpons['is_use'] == '0'){
                        $deal_coupons[] = [
                            'title' => $cpons['name'],
                            'money' => $cpons['discount_title'],
                            'discount_des' => $cpons['discount_des'],
                            'date' => date('Y.m.d', $cpons['start_time']).'-'.date('Y.m.d', $cpons['end_time']),
                            'get' => $cpons['is_get'],
                            'type' => 'merchant',
                            'coupon_id' => $cpons['coupon_id']
                        ];
                    }
                }

                $store_info = (new MerchantStoreService)->getOne(['store_id' => $value['store_id']]);
                
                $group_store[$value['store_id']] = [
                    'store_id' => $value['store_id'],
                    'store_name' => $value['store_name'],
                    'lng' => $store_info['long'] ?? '',
                    'lat' => $store_info['lat'] ?? '',
                    'adress' => $store_info['adress'] ?? '',
                    'phone' => $store_info['phone'] ?? '',
                    'coupoun_list' => $deal_coupons,
                    'activity' => [
                        'type' => '',//活动类型
                        'title' => '',//活动标题
                        'id' => 0,//活动ID
                        'status' => 0,//是否满足活动条件  0=未满足 1=已满足
                        'discount_money' => 0,//满足活动条件之后 要扣除的金额
                        'skuids' => [],//满足活动条件之后，要去除的对应活动skuid，如果不满足活动，这里就为空
                        'buy_limit' => 0,//限购次数  0=不限购
                        'discount_card' => 0,//是否与会员卡同享
                        'discount_coupon' => 0,//是否与优惠券同享
                    ],
                    'goods_list' => [$value],
                    'give_goods' => [],//赠品
                    'have_marketing_goods' => 0
                ];  
            }

            if ($value['is_marketing_goods'] == 1) {
                $group_store[$value['store_id']]['have_marketing_goods'] = 1;
            }

            if($value['is_select'] == '1'){
                $output['settlement']['nums'] += $value['num'];
                $output['settlement']['price'] += get_format_number($value['num'] * $value['price']);
                $output['settlement']['discount'] += get_format_number($limited_act_discount_money);
            }
        }

        foreach ($group_store as $key => $store_data) {
            
            //获取商家优惠 (满减活动、满折活动、满赠、满包邮、n元n件)  邓远辉提供代码
            $goods_data = [];
            foreach ($store_data['goods_list'] as $goods) {
                if($goods['is_select'] == '1'){
                    $goods_data[] = [
                        'sku_id' => $goods['sku_id'],
                        'goods_id' => $goods['goods_id'],
                        'num' => $goods['num'],
                        'price' => get_format_number($goods['price']),
                    ];
                }
            }
            if($goods_data){
                $store_activitys = $MallActivityService->getStoreActivity($store_data['store_id'], $goods_data);
                if(isset($store_activitys['type']) && !empty($store_activitys['type'])){
                    $group_store[$key]['activity']['type'] = $store_activitys['type'];
                    $group_store[$key]['activity']['title'] = $store_activitys['title'] ?? '';
                    $group_store[$key]['activity']['id'] = $store_activitys['id'];
                    $group_store[$key]['activity']['status'] = $store_activitys['status'];
                    $group_store[$key]['activity']['skuids'] = $store_activitys['satisfied_skuids'] ?? [];
                    $group_store[$key]['activity']['buy_limit'] = $store_activitys['buy_limit'] ?? 0;
                    $group_store[$key]['activity']['discount_money'] = $store_activitys['satisfied_money'] ?? 0;
                    $group_store[$key]['activity']['discount_card'] = $store_activitys['discount_card'];
                    $group_store[$key]['activity']['discount_coupon'] = $store_activitys['discount_coupon'];
                    if($store_activitys['status'] == 1 && $store_activitys['satisfied_money'] > 0){
                        $output['settlement']['price'] -= get_format_number($store_activitys['satisfied_money']);
                        $output['settlement']['discount'] += $store_activitys['satisfied_money'];
                    }
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
                        $group_store[$key]['give_goods'] = $gives;
                    }
                }
            }
            
        }

        foreach ($group_store as $key=>$item){
            $goods_num = count($item['goods_list']);
            $is_out_goods_num = 0;
            $is_select_goods_num = 0;
            foreach ($item['goods_list'] as $value){
                if($value['is_out_range']){
                    $is_out_goods_num++;
                }elseif($value['is_select']){
                    $is_select_goods_num++;
                }
            }
            $group_store[$key]['is_not_collect'] = 0; //1-全选不可操作
            $group_store[$key]['is_all_select'] = 0; //1-全选
            if($is_out_goods_num>=$goods_num){
                $group_store[$key]['is_not_collect'] = 1;
            }elseif(($is_select_goods_num+$is_out_goods_num)>=$goods_num){
                $group_store[$key]['is_all_select'] = 1;
            }
        }

        if($compute_select){
            $ret_discount_money = get_format_number($output['settlement']['discount']);
            return $group_store;
        }
        $output['normal_list'] = array_values($group_store);
        $output['no_effective_list'] = array_values($no_effective_group_store);
        $output['settlement']['price'] = get_format_number($output['settlement']['price']);
        $output['settlement']['discount'] = get_format_number($output['settlement']['discount']);
        
        return $output;
    }

    /**
     * 伪造购物车数据，用于商品单独购买、活动单独下单等...
     * @param  [type] $sku_id [description]
     * @param  [type] $num    [description]
     * @return [type]         [description]
     */
    public function createCartData($sku_id, $num, $activity_type = '', $activity_field = '', $note = '', $form = '', $uid = 0, $share_id = 0, $address_id = 0){
        $UserAdressService = new UserAdressService();
        $goods_area_id = [];
        if(cfg('open_mall_order_area')){
            //购物车进来如果选择的地址id和记录的id不一致修改记录id并清空购物车已选状态
            $last_address_id = (new User())->where(['uid'=>$uid])->value('mall_cart_address_id');
            $address_id = $address_id?:$last_address_id;
            if($address_id){
                $address_info = $UserAdressService->getAdressByAdressid($address_id);
                if($address_info){
                    $address_info['lng'] = $address_info['longitude'];
                    $address_info['lat'] = $address_info['latitude'];
                }
            } elseif($uid){
                //选择一个默认的收货地址
                $now_user_address = $UserAdressService->getAdressByUid($uid, 1);
                if($now_user_address){
                    $address_info['adress_id'] = $now_user_address[0]['adress_id'] ?? 0;
                    $address_info['province'] = $now_user_address[0]['province'];
                    $address_info['city'] = $now_user_address[0]['city'];
                    $address_info['area'] = $now_user_address[0]['area'];
                    $address_info['lng'] = $now_user_address[0]['longitude'];
                    $address_info['lat'] = $now_user_address[0]['latitude'];
                }
            }
            $goods_area_id[] = 0;
            if(isset($address_info)){
                if($address_info['area']>0){
                    $goods_area_id[] = $address_info['area'];
                }
                if($address_info['city']>0){
                    if($address_info['area']<=0){
                        $children_area = (new Area())->where(['area_pid'=>$address_info['city'],'is_open'=>1])->column('area_id');
                        $goods_area_id = array_merge($goods_area_id,$children_area);
                    }
                    $goods_area_id[] = $address_info['city'];
                }
                if($address_info['province']>0){
                    $goods_area_id[] = $address_info['province'];
                }
            }
        }
        
        $sku = (new MallGoodsSkuService)->getSkuById($sku_id);//sku信息
        if(empty($sku) || empty($sku['goods_id'])){
            return [
                'error'=>true,
                'msg'=>L_('当前商品未找到或已被删除'),
            ];
        }
        $goods = (new MallGoodsService)->getOne($sku['goods_id']);//商品信息
        if(!$goods['status']){
            return [
                'error'=>true,
                'msg'=>L_('订单包含已下架的商品'),
            ];
        }
        $store_info = (new MerchantStoreService)->getOne(['store_id' => $goods['store_id']]);
        $get_limit_buy = (new MallGoodsService)->get_limit_buy($sku['goods_id'], $goods['is_restriction'], $goods['restriction_type'], $goods['restriction_periodic'], $goods['restriction_num']);

        $setpriceData = [];
        $is_marketing_goods = 0;
        if(!empty($share_id)){
            $shareData = (new StoreMarketingShareLog())->where([['id', '=', $share_id]])->find();
            if (empty($shareData)) {
                return [
                    'error'=>true,
                    'msg'=>L_('分享码有误!'),
                ];
            }
            if ($shareData['create_time'] > time() - 86400) {//分享链接24小时之内有效
                $setpriceData = (new StoreMarketingPersonSetprice())->where([['share_id', '=', $shareData['id']], ['person_id', '=', $shareData['person_id']], ['goods_type', '=', 2], ['goods_id', '=', $sku['goods_id']], ['specs_id', '=', $sku_id]])->find();
                if (empty($setpriceData)) {
                    $setpriceData = [
                        'share_id' => $shareData['id'],
                        'person_id' => $shareData['person_id'],
                        'goods_id' => $sku['goods_id'],
                        'goods_type' => 2,
                        'specs_id' => $sku_id,
                        'price' => $sku['price'],
                        'create_time' => time()
                    ];
                    $setpriceData['id'] = (new StoreMarketingPersonSetprice())->insertGetId($setpriceData);
                }
                $sku['price'] = $setpriceData['price'];
                $is_marketing_goods = 1;
            }
        }

        $is_out_range = 0;
        $out_note = '';
        if(cfg('open_mall_order_area')==1){
            if($goods_area_id){
                //获取商品服务范围
                $goods_area = (new MallGoodsArea())->where([['goods_id','=',$sku['goods_id']],['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                if(!$goods_area){
                    $is_out_range = 1;
                    $out_note = '超出可服务范围，无法下单';
                }
            }
        }elseif(cfg('open_mall_order_area')==2){
            $is_out_range = (new MallDeliveryService())->checkRange($address_info,[$store_info])['is_out_range'];
            if($is_out_range){
                $out_note = '超出可服务范围，无法下单';
            }
        }

        $cart = [
            [
                'store_id' => $goods['store_id'],
                'store_name' => $store_info['name'] ?? '',
                'lng' => $store_info['long'] ?? '',
                'lat' => $store_info['lat'] ?? '',
                'adress' => $store_info['adress'] ?? '',
                'phone' => $store_info['phone'] ?? '',
                'activity' => [
                    'type' => '',//活动类型(店铺级活动)
                    'title' => '',//活动标题
                    'id' => 0,//活动ID
                    'status' => 0,
                    'buy_limit' => 0,//限制参与次数 0=不限购
                    'skuids' => [],
                    'discount_money' => 0,
                    'discount_card' => 0,
                    'discount_coupon' => 0,
                ],
                'goods_list' => [
                    [
                        'cart_id' => 0,
                        'sku_id' => $sku_id,
                        'goods_id' => $sku['goods_id'],
                        'goods_name' => $goods['name'],
                        'fright_id' => $goods['fright_id'],
                        'other_area_fright' => $goods['other_area_fright'],
                        'image' => empty($sku['image'])?replace_file_domain($goods['image']):replace_file_domain($sku['image']),
                        'image_original' => $sku['image'],
                        'mer_id' => $goods['mer_id'],
                        'limit_count_down' => 0,
                        'cate_first' => $goods['cate_first'],
                        'is_restriction' => $goods['is_restriction'],
                        'restriction_type' => $goods['restriction_type'],
                        'restriction_periodic' => $goods['restriction_periodic'],
                        'restriction_num' => $goods['restriction_num'],
                        'stock_num' => $goods['stock_num'],
                        'sku_str' => $sku['sku_str'],
                        'price' => $sku['price'],
                        'end_price' => 0,
                        'service_desc' => $goods['service_desc'],
                        'property' => [],
                        'more_info' => [],
                        'num' => $num,
                        'max_num_type' => $get_limit_buy['type'],
                        'max_num' => $get_limit_buy['nums'],
                        'store_id' => $goods['store_id'],
                        'store_name' => $store_info['name'] ?? '',
                        'notes' => $note,
                        'forms' => $form,
                        'is_select' => 1,
                        'free_shipping' => $goods['free_shipping'],
                        'activity_type' => '',//商品级活动
                        'activity_id' => 0,
                        'activity_title' => '',
                        'activity_surplus' => 0,
                        'activity_buy_limit' => 0,//限购数量 0=不限购
                        'activity_discount_card' => 0,
                        'activity_discount_coupon' => 0,
                        'activity_field' => '',
                        'is_free_shipping' => 0,
                        'common_goods_id'=>$goods['common_goods_id'],
                        'is_marketing_goods' => $is_marketing_goods,
                        'share_id' => $is_marketing_goods == 1 ? $shareData['id'] : 0,
                        'initial_salenum' => $goods['initial_salenum'],
                        'is_out_range' => $is_out_range,
                        'note' => $out_note,
                        'unPlusSource'=>request()->param('unPlusSource','')
                    ],
                ],
                'give_goods' => [],
                'coupoun_list' => [],
                'have_marketing_goods' => $is_marketing_goods
            ]
        ];

        if(!empty($note)){
            $_notes = json_decode($note, true);
            foreach ($_notes as $n) {
                $cart[0]['goods_list'][0]['property'][] = $n['property'].':'.implode(',', $n['property_val']);
            }            
        }
        if(!empty($form)){
            $_forms = json_decode($form, true);
            foreach ($_forms as $f) {
                $cart[0]['goods_list'][0]['more_info'][] = ['title'=>$f['title'], 'type' => $f['type'], 'val' => $f['val']];
            }
        }

        //活动组装
        $activity_info = [];
        $MallActivityService = new MallActivityService;
        if($activity_type=='bargain'){
            $activity_info = $MallActivityService->getActivity($sku['goods_id'], 0, $uid, $activity_field, $sku_id);
        }else {
            $activity_info = $MallActivityService->getActivity($sku['goods_id'], 0, $uid, 0, $sku_id);
        }
        if($activity_info['style']!='normal' && $activity_info['style']=='limited'){
            $activity_type=$activity_info['style'];
        }

        if($activity_type&&$activity_info&&$activity_type != 'normal'){
            fdump_sql(['act'=>$activity_info,'uid'=>$uid],"limit_data_getActivity");//秒杀偶现bug日志
            if(isset($activity_info['buy_limit']) && isset($activity_info['style']) && isset($activity_info['id']) && $activity_info['buy_limit'] > 0){
                $join_nums = (new MallOrderDetailService)->getActivityJoinNums($activity_info['style'], $activity_info['id'], $uid);
                fdump_sql(['act'=>$activity_info,'uid'=>$uid,'join_num'=>$join_nums],"limit_join_num");//秒杀参与限制记录
                if($join_nums + $num > $activity_info['buy_limit']){
                    throw new \think\Exception("您参与该活动的次数已达上限！", 1003);
                }
            }

            if(isset($activity_info['act_stock_num']) && $activity_info['act_stock_num'] != '-1'){
                if($cart[0]['goods_list'][0]['stock_num'] == '-1'){
                    $cart[0]['goods_list'][0]['stock_num'] = $activity_info['act_stock_num'];
                }
                else{
                    $cart[0]['goods_list'][0]['stock_num'] = min($activity_info['act_stock_num'], $cart[0]['goods_list'][0]['stock_num']);
                    if($activity_info['style']=='limited'){
                        $cart[0]['goods_list'][0]['stock_num'] = $activity_info['act_stock_num'];
                    }
                }
            }
            switch ($activity_info['style']) {
                case 'group'://拼团
                    if($activity_field > 0){
                        $check_group = $MallActivityService->getGroupTeamOrderStatus($activity_field);
                        if($check_group['part_status'] == '0'){
                            throw new \think\Exception("抱歉，您参与的拼团人数已满！", 1003);
                        }
                    }
                    $cart[0]['goods_list'][0]['activity_type'] = $activity_info['style'];
                    $cart[0]['goods_list'][0]['activity_id'] = $activity_info['id'];
                    $cart[0]['goods_list'][0]['activity_discount_card'] = $activity_info['discount_card'];
                    $cart[0]['goods_list'][0]['activity_discount_coupon'] = $activity_info['discount_coupon'];
                    $cart[0]['goods_list'][0]['activity_field'] = $activity_field;//拼团的团ID，当传0时表示开团
                    $cart[0]['goods_list'][0]['price'] = $activity_info['price'];
                    $cart[0]['goods_list'][0]['activity_buy_limit'] = $activity_info['buy_limit'];
                    if(empty($activity_field)){
                        $cart[0]['goods_list'][0]['price'] = $activity_info['price'] - $activity_info['team_discount_price'];
                        $cart[0]['goods_list'][0]['price'] < 0 && $cart[0]['goods_list'][0]['price'] = 0;
                    }
                    break;
                case 'bargain'://砍价
                    $cart[0]['goods_list'][0]['activity_type'] = $activity_info['style'];
                    $cart[0]['goods_list'][0]['activity_id'] = $activity_info['id'];
                    $cart[0]['goods_list'][0]['price'] = $activity_info['price'];
                    $cart[0]['goods_list'][0]['activity_discount_card'] = $activity_info['discount_card'];
                    $cart[0]['goods_list'][0]['activity_discount_coupon'] = $activity_info['discount_coupon'];
                    $cart[0]['goods_list'][0]['activity_field'] = $activity_field;//砍价的团队ID，当传0时表示开团
                    $cart[0]['goods_list'][0]['activity_buy_limit'] = $activity_info['buy_limit'];
                    break;
                case 'periodic'://周期购
                    $cart[0]['goods_list'][0]['activity_type'] = $activity_info['style'];
                    $cart[0]['goods_list'][0]['activity_id'] = $activity_info['id'];
                    $cart[0]['goods_list'][0]['price'] = $sku['price'];
                    $cart[0]['goods_list'][0]['periodic_nums'] = $activity_info['nums'] ?? 1;
                    $cart[0]['goods_list'][0]['activity_discount_card'] = $activity_info['discount_card'];
                    $cart[0]['goods_list'][0]['activity_discount_coupon'] = $activity_info['discount_coupon'];
                    $cart[0]['goods_list'][0]['activity_field'] = $activity_field;//配送周期值
                    $cart[0]['goods_list'][0]['is_free_shipping'] = $activity_info['freight_type'] == '1' ? 1 : 0;//周期购也有包邮的可能性
                    $cart[0]['goods_list'][0]['activity_buy_limit'] = $activity_info['buy_limit'];
                    break;
                case 'limited'://限时抢购
                    $cart[0]['goods_list'][0]['activity_type'] = $activity_info['style'];
                    $cart[0]['goods_list'][0]['activity_id'] = $activity_info['id'];
                    $cart[0]['goods_list'][0]['price'] = $activity_info['price'];
                    $cart[0]['goods_list'][0]['activity_discount_card'] = $activity_info['discount_card'];
                    $cart[0]['goods_list'][0]['activity_discount_coupon'] = $activity_info['discount_coupon'];
                    $cart[0]['goods_list'][0]['activity_buy_limit'] = $activity_info['buy_limit'];
                    break;
                case 'prepare'://预售
                    $cart[0]['goods_list'][0]['activity_type'] = $activity_info['style'];
                    $cart[0]['goods_list'][0]['activity_id'] = $activity_info['id'];
                    $cart[0]['goods_list'][0]['price'] = $activity_info['deposit'];
                    $cart[0]['goods_list'][0]['end_price'] = $activity_info['balance'];
                    $cart[0]['goods_list'][0]['prepare_price'] = $activity_info['deposit'] + $activity_info['balance'];
                    $cart[0]['goods_list'][0]['activity_discount_card'] = $activity_info['discount_card'];
                    $cart[0]['goods_list'][0]['activity_discount_coupon'] = $activity_info['discount_coupon'];
                    $cart[0]['goods_list'][0]['activity_buy_limit'] = $activity_info['buy_limit'];
                    break;
                default:
                    //throw new \think\Exception("未找到该活动内容！");
                    fdump_sql($activity_info,"getActivity_error");//未加载出活动记录
                    $cart[0]['goods_list'][0]['activity_type'] ="normal";
                    break;
            }

        }
        else{
            $store_activitys = $MallActivityService->getStoreActivity($goods['store_id'], [['sku_id'=>$sku_id,'goods_id'=>$sku['goods_id'], 'num'=>$num, 'price'=>$sku['price']]]);
            if(isset($store_activitys['type']) && !empty($store_activitys['type'])){
                $cart[0]['activity']['type'] = $store_activitys['type'];
                $cart[0]['activity']['title'] = $store_activitys['title'] ?? '';
                $cart[0]['activity']['id'] = $store_activitys['id'];
                $cart[0]['activity']['status'] = $store_activitys['status'];
                $cart[0]['activity']['skuids'] = $store_activitys['satisfied_skuids'] ?? [];
                $cart[0]['activity']['buy_limit'] = $store_activitys['buy_limit'] ?? 0;
                $cart[0]['activity']['discount_money'] = $store_activitys['satisfied_money'] ?? 0;
                $cart[0]['activity']['discount_card'] = $store_activitys['discount_card'];
                $cart[0]['activity']['discount_coupon'] = $store_activitys['discount_coupon'];
                if($store_activitys['type'] == 'give' && !empty($store_activitys['give_goods']) && $store_activitys['status'] == 1){
                    $give_skuids = array_keys($store_activitys['give_goods']);
                    $give_skus = (new MallGoodsSkuService)->getFullSkuInfo($give_skuids);
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
                    $cart[0]['give_goods'] = $gives;
                }
            }
        }
        return $cart;
    }

    /**
     * 结算购物车，获取结算数据
     * @param  [type]  $cart       以店铺ID分组的商品信息
     * @param  integer $address_id 选中的收货地址ID
     * @param  integer $coupon_id  选中的平台优惠券ID
     * @param  string  $store_param      门店订单支付信息 json数据存储  具体见接口文档
     * @param  float $discount_money  活动优惠金额
     * @return [type]              [description]
     */
    public function confirmCartData($uid, $cart, $address_id = 0, $coupon_id = 0, $store_param = '', $discount_money = 0, $from = 'cart'){
        $store_param = json_decode($store_param, true);
        $output = [
            'address' => [
                'id' => 0,
                'pca' => '',
                'is_default' => 0,
                'detail' => '',
                'name' => '',
                'phone' => '',
                'lng' => 0,
                'lat' => 0,
                'province_id' => '',
                'city_id' => '',
                'area_id' => '',
                'tag' => ''
            ],
            'order' => [],
            'plat' => [
                'coupon' => [
                    'id' => 0,
                    'title' => '',
                    'money' => 0,
                    'lists' => []
                ],
                'activity' => [
                    'id' => 0,
                    'title' => '',
                    'money' => 0,
                ],
                'level' => [
                    'money' => 0
                ],
                'score' => [
                    'use' => 0,
                    'money' => 0,
                    'is_checked' => 0
                ],
                'balance' => [
                    'use' => 0,
                    'is_checked' => 0
                ]
            ],
            'nums' => 0,
            'money' => 0,
            'freight' => 0,//运费总计
            'goods_money' => 0,//商品总价(单纯商品总价而已)
            'discount' => 0,//所有优惠合计
        ];
        if(empty($cart)) return $output;

        //处理收货地址
        $address = [];
        if($address_id){
            $address = (new UserAdressService)->getAdressByAdressid($address_id);
            if ($address['uid'] != $uid) {
                throw new \think\Exception('非法请求');
            }
        }
        else{
            $now_user_address = (new UserAdressService)->getAdressByUid($uid);
            if($now_user_address){
                $address = $now_user_address[0];
            }
            else{
                $last_order = (new MallOrder)->getOrder([['uid','=',$uid]], 'address_id', 'order_id desc');
                if($last_order){
                    $address = (new UserAdressService)->getAdressByAdressid($last_order['address_id']);
                }
            }
        }
        if($address){
            $output['address']['id'] = $address['adress_id'];
            $areas = (new AreaService)->getNameByIds([$address['province'], $address['city'], $address['area']]);
            $output['address']['pca'] = ($areas[$address['province']] ?? '').($areas[$address['city']] ?? '').($areas[$address['area']] ?? '');
            $output['address']['is_default'] = $address['default'] == '1' ? 1 : 0;
            $output['address']['detail'] = $address['adress'].$address['detail'];
            $output['address']['name'] = $address['name'];
            $output['address']['phone'] = $address['phone'];
            $output['address']['lng'] = $address['longitude'];
            $output['address']['lat'] = $address['latitude'];
            $output['address']['province_id'] = $address['province'];
            $output['address']['city_id'] = $address['city'];
            $output['address']['area_id'] = $address['area'];
            $output['address']['tag'] = $address['tag'] ?: '';
        }

        //循环处理购物车已选中的数据信息
        $MerchantService = new MerchantService;
        $orders = [];//所有订单
        $MerchantCouponService = new MerchantCouponService;
        $MallActivityService = new MallActivityService;
        $MerchantCardService = new MerchantCardService;
        $MerchantStoreMallService = new MerchantStoreMallService;
        $ExpressTemplateService = new ExpressTemplateService;
        $express_txt = [
            '1' => cfg('delivery_alias_name') ?? '骑手配送',
            '2' => '普通快递',
            '3' => '到店自提',
        ];
        $goods_all_money = 0;//用作计算平台优惠券
        $mallcates = [];
        $cart = array_values($cart);    //避免数组对象化了，初始化一次
        foreach ($cart as $key => $store_data) {
            $store_id = $store_data['store_id'];
            $goodsScore = 0;
            foreach ($store_data['goods_list'] as $_key => &$goodsInfo) {
                $store_data['goods_list'][$_key]['price'] = get_format_number($goodsInfo['price']);
                //积分展示
                if($scorePrice = floatval($goodsInfo['price'] * $goodsInfo['num'])){
                    $scorePercent = PercentRateService::scorePercent('mall', $goodsInfo['goods_id'], $goodsInfo['mer_id']);
                    $score = bcmul((string)$scorePrice, (string)$scorePercent, 2);
                    if(cfg('score_round_two')){
                        $score = sprintf('%.2f',floatval($score));
                    }else{
                        $score = round($score);
                    }
                    $goodsInfo['score'] = floatval($score);
                }
                $goodsScore += $goodsInfo['score'];
            }
            $store_param[$store_id] = [//赋默认值
                'express_style' => $store_param[$store_id]['express_style'] ?? '',
                'express_time' => $store_param[$store_id]['express_time'] ?? '',
                'coupon_id' => $store_param[$store_id]['coupon_id'] ?? '',
                'invoice_id' => $store_param[$store_id]['invoice_id'] ?? '',
                'merchant_card' => $store_param[$store_id]['merchant_card'] ?? '',
                'remark' => $store_param[$store_id]['remark'] ?? '',
            ];
            $mer_id = $store_data['goods_list'][0]['mer_id'];
            $merchant = $MerchantService->getInfo($mer_id);
            $store = MerchantStore::where('store_id', $store_id)->find();
            if($store){
                $output['address']['province_id'] = $store['province_id'];
                $output['address']['city_id'] = $store['city_id'];
                $output['address']['area_id'] = $store['area_id'];
            }
            
            $temp = [
                'store_id' => $store_id,
                'mer_id' => $mer_id,
                'store_name' => $store_data['store_name'],
                'address' => $store_data['adress'],
                'phone' => $store_data['phone'],
                'lat' => $store_data['lat'],
                'lng' => $store_data['lng'],
                'service_desc' => [],//服务保障
                'freight_free' => 0,//是否包邮 1=包邮 0=不包邮
                'goods_list' => $store_data['goods_list'],
                'give_goods' => [],
                'express' => [
                    'style' => 0,//配送方式1：骑手配送，2：普通快递 3：到店自提
                    'title' => '',//标题
                    'money' => 0,//运费
                    'distance' => 0,//距离 单位：米
                    'express_time' => $store_param[$store_id]['express_time'],
                    'option' => [],
                    'option_val' => [],
                    'delivery_time_list' => [],
                    'is_third_deliver' => 0,
                ],
                'coupon' => [],
                'activity' => [],//店铺级活动
                'invoice' => [],
                'merchant_card' => [],
                'total' => [
                    'discount' => 0,
                    'nums' => 0,
                    'nums_title' => '',
                    'money' => 0,//所有需要支付金额
                    'goods_money' => 0,//所有金额（除去优惠）
                    'total_money' => 0,//所有金额（商品价格+运费+...）
                    'goods_money_total' => 0,//所有商品金额
                    'sys_coupon_id' => 0,
                    'sys_coupon_money' => 0,//使用平台优惠券优惠的金额（按比例均摊）
                    'sys_coupon_money_merchant' => 0,//使用平台优惠券优惠的金额（商家补贴金额）
                ],
                'remark' => $store_param[$store_id]['remark'],
                'have_marketing_goods' => $store_data['have_marketing_goods'] ?? 0,//是否存在分销商品:0=否,1=是
            ];

            //计算当前订单所需总共的费用(只是商品费用)
            $total_money = 0;
            $goods_activity_discount_card = 1;//是否与商家会员卡优惠同享 1=是 0=否
            $goods_activity_discount_coupon = 1;//是否与商家优惠券优惠同享 1=是 0=否
            //得到服务保障
            $service_combine = $store_data['goods_list'][0]['service_desc'] ? unserialize($store_data['goods_list'][0]['service_desc']) : [];
            $goods_free_shipping_count = 0;
            $MallGoodsService = new MallGoodsService;
            $MallOrderService = new MallOrderService;
            
            foreach ($store_data['goods_list'] as $_key => $goods) {
                if(isset($goods['initial_salenum']) && $goods['initial_salenum']>0 && $goods['num'] < $goods['initial_salenum']){
                    throw new \think\Exception("最低".$goods['initial_salenum']."件起售!");
                }
                $mallcates[] = $goods['cate_first'];
                //判断商品是否参与了商品级活动
                $activity_info = $MallActivityService->getActivity($goods['goods_id'], 0, $uid,'',$goods['sku_id']);
                if($from == 'cart' && in_array($activity_info['style'], ['group', 'bargain', 'periodic', 'prepare'])){
                    throw new \think\Exception($goods['goods_name']."正在参与活动，不可在购物车购买，可前往详情页购买!");
                }

                $limitbuy = $MallGoodsService->get_limit_buy($goods['goods_id'], $goods['is_restriction'], $goods['restriction_type'], $goods['restriction_periodic'], $goods['restriction_num']);
                if(!$MallOrderService->checkLimit($uid, $goods['goods_id'], $limitbuy['type'], $limitbuy['nums'], $goods['num']))//判断限购
                {
                    throw new \think\Exception($goods['goods_name']."超出限购数量，不能下单!");
                }

                //判断库存
                if(($goods['stock_num'] == '0' || ($goods['stock_num'] > 0 && $goods['num'] > $goods['stock_num']))){
                    if($activity_info['style']=='limited' && $activity_info['act_stock_num']>0){

                    }else{
                        throw new \think\Exception($goods['goods_name']."库存不足，不能下单!");
                    }
                }



                $temp['total']['nums'] += $goods['num'];
                $periodic_nums = $goods['periodic_nums'] ?? 1;
                $total_money += $goods['num'] * $goods['price'] * $periodic_nums;
                if(!empty($goods['activity_type'])){
                    $goods_activity_discount_card = $goods['activity_discount_card'];
                    $goods_activity_discount_coupon = $goods['activity_discount_coupon'];
                }
                $tmp_service_desc = $goods['service_desc'] ? unserialize($goods['service_desc']) : [];
                $service_combine = array_intersect($service_combine, $tmp_service_desc);
                if($goods['free_shipping'] == '1'){
                    $goods_free_shipping_count++;
                }
            }
            $goods_free_shipping = false;//商品级包邮  需要所有商品都包邮才包邮
            if($goods_free_shipping_count == count($store_data['goods_list'])){
                $goods_free_shipping = true;
            }
            $temp['service_desc'] = (new MallGoodsService)->dealGoodsService(serialize($service_combine));

            //配送方式、计算运费等...
            $now_store_config = $MerchantStoreMallService->getMallStoreInfo($store_id);
            $temp['express']['delivery_time_list'] = (new MallRiderService)->getTimeList($store_id, $store_data['lng'], $store_data['lat'], $output['address']['lng'], $output['address']['lat']);
            if($now_store_config['is_houseman'] == '1'){
                $temp['express']['option']['1'] = $express_txt['1'];
                $temp['express']['option_val'][] = '1';
            }
            $temp['express']['express_time'] = $store_param[$store_id]['express_time'] ?: ($temp['express']['delivery_time_list'][0]['list'][0]['val'] ?? '');
            try {
                $mall_rider_info = (new MallRiderService)->computeFee($store_data['lng'], $store_data['lat'], $output['address']['lng'], $output['address']['lat'], $temp['express']['express_time']); 
            } catch (\think\Exception $e) {
                $temp['express']['option_val'] = []; 
                unset($temp['express']['option']['1']);
                unset($lumin);
                $store_param[$store_id]['express_style'] = "";
            }            

            if($now_store_config['is_delivery'] == '1'){
                $temp['express']['option']['2'] = $express_txt['2'];
                $temp['express']['option_val'][] = '2';
            }
            if($now_store_config['is_zt'] == '1' && !in_array($store_data['goods_list'][0]['activity_type'], ['periodic', 'prepare'])){
                $temp['express']['option']['3'] = $express_txt['3'];
                $temp['express']['option_val'][] = '3';
            }
            if(!isset($temp['express']['option_val'][0])){
                throw new \think\Exception("该店铺未配置配送方式，请联系商家配置！");
            }
            $temp['express']['style'] = $store_param[$store_id]['express_style'] ?: $temp['express']['option_val'][0];
            $temp['express']['title'] = $express_txt[$temp['express']['style']];
            
            if($temp['express']['style'] == '1'){          
                if($temp['express']['express_time']){//计算运费
                    if($merchant['open_third_deliver']){
                        $user_deliver_time = $temp['express']['express_time']?explode(',', $temp['express']['express_time']):[];
                        $temp['express']['money'] = (new DeliverApiService())->countPrice([
                            'user_lng' => $output['address']['lng']?:'',
                            'user_lat' => $output['address']['lat']?:'',
                            'user_address' => $output['address']['detail']?:'',
                            'user_deliver_time' => $user_deliver_time?date('Y-m-d H:i:s',$user_deliver_time[0]):'',
                            'store_lat' => $store_data['lat']?:'',
                            'store_lng' => $store_data['lng']?:'',
                            'store_name' => $now_store_config['name']?:'',
                            'store_address' => $now_store_config['adress']?:'',
                        ]);
                        $temp['express']['is_third_deliver'] = 1;
                    }else{
                        $temp['express']['money'] = $mall_rider_info['money'];
                    }
                    $temp['express']['distance'] = $mall_rider_info['distance'];
                }
            }
            elseif($temp['express']['style'] == '2'){
                $param = [];
                foreach ($store_data['goods_list'] as $goods) {
                    $param[] = [
                        'goods_id' => $goods['common_goods_id'],
                        'fright_id' => $goods['fright_id'],
                        'num' => $goods['num'],
                        'other_area_fright' => $goods['other_area_fright'],
                    ];
                }
                $temp['express']['money'] = $ExpressTemplateService->computeFee($store_id, $param, $output['address']['city_id'], $output['address']['province_id']);
            }


            /**
             * 杨宁宁定的计算优先顺序，所有的费用不包括邮费，邮费只是会在“满包邮”活动中减免，其他场景都不参与优惠
             * 1、先算店铺活动（满减、满折）  $step1 为店铺满减优惠掉的金额
             * 2、再算店铺优惠券                   $step2 为店铺优惠券优惠掉的金额 
             * 3、再算商家会员卡折扣               $step3 为商家会员卡优惠掉的金额 
             * 4、再算平台活动（满减、满折）          $step4 为平台活动优惠掉的金额
             * 5、再算平台优惠券                    $step5 为平台优惠券优惠掉的金额
             * 6、再算平台会员卡折扣                  $step6 为平台会员卡折扣优惠掉的金额
             */
            
            //先算店铺活动（满减/折，满包邮，n元n件，满赠）
            $step1 = 0;
            $step1_shipping = 0;//满包邮的优惠
            if($store_data['activity']['status'] == '1' && !($store_data['activity']['discount_coupon'] == '0' && $store_param[$store_id]['coupon_id'] > 0)){//如果检测到享受店铺优惠
                $temp['activity'] = [
                    'id' => $store_data['activity']['id'],
                    'type' => $store_data['activity']['type'],
                    'title' => $store_data['activity']['title'],
                    'money' => get_format_number($store_data['activity']['discount_money']),
                    'skuids' => $store_data['activity']['skuids'],
                ];
                $step1 = $store_data['activity']['discount_money'];
                switch ($store_data['activity']['type']) {
                    case 'shipping'://满包邮
                        $step1_shipping = $temp['express']['money'];
                        $temp['activity']['money'] = $step1_shipping;
                        $temp['freight_free'] = 1;
                        // $temp['express']['money'] = 0;
                        break;
                    case 'give'://满赠
                        $temp['give_goods'] = $store_data['give_goods'];
                        break;
                }
                
            }
            if(isset($store_data['goods_list'][0]['periodic_nums']) && $store_data['goods_list'][0]['periodic_nums'] > 0){
                $temp['express']['money'] = $temp['express']['money'] * $store_data['goods_list'][0]['periodic_nums'];
            }

            //商品级包邮
            if($goods_free_shipping && $temp['express']['style'] == '2'){
                $temp['express']['money'] = 0;
                $temp['freight_free'] = 1;
            }

            //商品的周期购活动有包邮的可能性
            if($store_data['goods_list'][0]['is_free_shipping'] == '1'){
                // $step1 += $temp['express']['money'];//如果是满包邮，邮费为0就可以了
                $temp['express']['money'] = 0;
                $temp['freight_free'] = 1;
            }

            // $current_store_activity = $MallActivityService->getMallFullMinusDiscountActGoods($store_id);
            // if(!(isset($current_store_activity['is_discount_share']) && $current_store_activity['is_discount_share'] == 1 && $current_store_activity['discount_coupon'] == '0' && $store_param[$store_id]['coupon_id'])){//与优惠券不同享则不享受该活动
            //     if($current_store_activity && $current_store_activity['activity_id']){
            //         if($current_store_activity['goods_list']){//去商品交集
            //             $current_activity_goodids = array_unique(array_intersect(array_column($store_data['goods_list'], 'goods_id'), $current_store_activity['goods_list']));
            //         }
            //         else{
            //             $current_activity_goodids = array_unique(array_column($store_data['goods_list'], 'goods_id'));
            //         }
            //         $current_activity_total_price = 0;//当前店铺参与活动的商品的总价格
            //         foreach ($store_data['goods_list'] as $goods) {
            //             if(in_array($goods['goods_id'], $current_activity_goodids)){
            //                 $current_activity_total_price += $goods['num'] * $goods['price'];
            //             }
            //         }
            //         if($current_activity_total_price > 0 && $current_store_activity['rule']){
            //             foreach ($current_store_activity['rule'] as $rule) {
            //                 $specific_discount = $current_store_activity['is_discount'] == 1 ? get_format_number($current_activity_total_price*$rule['discount']) : $rule['discount'];//当前优惠规则所优惠的具体金额
            //                 if($current_activity_total_price > $rule['full_money'] && $step1 < $specific_discount){//取最优的那个规则
            //                     $step1 = $specific_discount;
            //                     if($current_store_activity['is_discount'] == 1){
            //                         $per = '享'.(($rule['discount']*100)%10 === 0 ? ($rule['discount']*100)/10 : $rule['discount']*100).'折';
            //                     }
            //                     else{
            //                         $per = '减'.$rule['discount'];
            //                     }
            //                     $temp['activity'] = [
            //                         'id' => $current_store_activity['activity_id'],
            //                         'type' => 'minus_discount',
            //                         'title' => '满'.$rule['full_money'].$per,
            //                         'money' => $step1
            //                     ];
            //                 }
            //             }
            //         }
            //     }
            // }
            
            //再算店铺优惠券
            $step2 = 0;
            $current_order_money = 0;
            $point_goods = [];
            foreach ($store_data['goods_list'] as $goods) {
                $current_order_money += $goods['num'] * $goods['price'];
                $point_goods[$goods['goods_id']] = $goods['num'] * $goods['price'];
            }
            $current_coupons = $MerchantCouponService->formatDiscount($MerchantCouponService->getAvailableCoupon($uid, $mer_id, ['can_coupon_money'=>$current_order_money, 'business'=>'mall', 'store_id'=>$store_id, 'point_goods'=>$point_goods]));
            $select_coupon_id = 0;
            if((isset($temp['activity']['id']) && $temp['activity']['id'] > 0 && $store_data['activity']['discount_coupon'] == '0') || $goods_activity_discount_coupon == '0'){
                $temp['coupon']['id'] = 0;
                $temp['coupon']['title'] = '';
                $temp['coupon']['money'] = 0;
                $temp['coupon']['no_title'] = '与活动不同享';
            }
            else{
                $temp['coupon']['id'] = 0;
                $temp['coupon']['title'] = '';
                $temp['coupon']['money'] = 0;
                $temp['coupon']['no_title'] = '无可用优惠券';
                foreach ($current_coupons as $coupon) {
                    if($coupon['id'] == $store_param[$store_id]['coupon_id']){
                        $temp['coupon']['id'] = $coupon['id'];
                        $temp['coupon']['title'] = $coupon['name'];
                        $temp['coupon']['money'] = $coupon['discount'];
                        $temp['coupon']['no_title'] = '';
                        $step2 = $coupon['discount'];
                    }
                    elseif($temp['coupon']['money'] < $coupon['discount'] && empty($store_param[$store_id]['coupon_id'])){//默认选择优惠力度最大的优惠券
                        $temp['coupon']['id'] = $coupon['id'];
                        $temp['coupon']['title'] = $coupon['name'];
                        $temp['coupon']['money'] = $coupon['discount'];
                        $temp['coupon']['no_title'] = '';
                        $step2 = $coupon['discount'];
                        $select_coupon_id = $coupon['id'];
                    }
                    $temp['coupon']['money'] = get_format_number($temp['coupon']['money']);
                }
            }
            if($step2 > ($total_money - $step1)){
                $step2 = get_format_number($total_money - $step1);
                $temp['coupon']['money'] = $step2;
            }
            if($goods_activity_discount_coupon != '0'){
                foreach ($current_coupons as $coupon) {
                    $temp['coupon']['lists'][] = [
                        'id' => $coupon['id'],
                        'title' => $coupon['discount_des'],
                        'is_select' => ($store_param[$store_id]['coupon_id'] == $coupon['id'] || $select_coupon_id == $coupon['id']) ? 1 : 0
                    ];
                }
            }

            //再算商家会员卡折扣
            $step3 = 0;
            $temp['merchant_card']['merchant'] = $merchant['name'];
            $temp['merchant_card']['discount_title'] = '';
            $temp['merchant_card']['discount_money'] = 0;
            $temp['merchant_card']['money'] = 0;
            $temp['merchant_card']['is_checked'] = 0;
            $temp['merchant_card']['merchant_give_balance'] = 0;//商家会员卡赠送余额支付了多少钱(不用做展示，只做订单存储)
            $temp['merchant_card']['merchant_balance'] = 0;//商家会员卡余额支付了多少钱(不用做展示，只做订单存储)
            if($goods_activity_discount_card == 1 && (($store_data['activity']['status'] == '1' && $store_data['activity']['discount_card'] == '1') || $store_data['activity']['status'] == '0')){
                $current_card = $MerchantCardService->getUserCard($uid, $mer_id);
                if($current_card){
                    if($current_card['discount'] != 10 && $current_card['discount'] != '0'){
                        $card_discount_money = get_format_number(($total_money - $step1 - $step2) - ($total_money - $step1 - $step2)*($current_card['discount']/10));
                        if($card_discount_money > 0){
                            $step3 = $card_discount_money;
                            $temp['merchant_card']['discount_title'] = get_format_number($current_card['discount']).'折优惠';
                            $temp['merchant_card']['discount_money'] = $step3;
                        }
                    }
                    $order_pay_money = $total_money - $step1 - $step2 - $step3;//除掉优惠的剩余需要支付的金额
                    if($order_pay_money > 0){
                        $merchant_balance = $current_card['card_money'] + $current_card['card_money_give'];
                        if($merchant_balance >= $order_pay_money){
                            $temp['merchant_card']['money'] = $order_pay_money;
                            if($order_pay_money <= $current_card['card_money_give']){
                                $temp['merchant_card']['merchant_give_balance'] = $order_pay_money;
                            }
                            else{
                                $temp['merchant_card']['merchant_give_balance'] = $current_card['card_money_give'];
                                $temp['merchant_card']['merchant_balance'] = $order_pay_money - $current_card['card_money_give'];
                            }
                        }
                        else{
                            $temp['merchant_card']['money'] = $merchant_balance;
                            $temp['merchant_card']['merchant_give_balance'] = $current_card['card_money_give'];
                            $temp['merchant_card']['merchant_balance'] = $current_card['card_money'];
                        }
                        if($store_param[$store_id]['merchant_card'] == '1'){
                            $temp['merchant_card']['is_checked'] = 1;
                            $temp['merchant_card']['card_id'] = $current_card['id'];
                        }
                    }
                }    
            }

            $temp['express']['money'] = get_format_number($temp['express']['money']);

            //计算店铺订单汇总
            $temp['total']['discount'] = $step1 + $step2 + $step3;
            $temp['total']['money'] = get_format_number($total_money - $temp['total']['discount'] - ($store_param[$store_id]['merchant_card'] == '1' ? $temp['merchant_card']['money'] : 0));
            $temp['total']['money'] < 0 && $temp['total']['money'] = 0;
            $temp['total']['goods_money'] = get_format_number($total_money - $temp['total']['discount']);
            $temp['total']['total_money'] = get_format_number($total_money+$temp['express']['money'] - $step1_shipping);
            $temp['total']['goods_money_total'] = $total_money;
            customization('goods_show_score') && $temp['total']['score_text'] = $goodsScore >0 ? cfg('score_name').'+'.intval($goodsScore) : '';
            $goods_all_money += $total_money - $temp['total']['discount'];

            $temp['merchant_card']['merchant_give_balance'] = get_format_number($temp['merchant_card']['merchant_give_balance']);
            $temp['merchant_card']['money'] = get_format_number($temp['merchant_card']['money']);
            $temp['total']['nums_title'] = '共'.($store_data['goods_list'][0]['activity_type'] == 'periodic' ? $store_data['goods_list'][0]['periodic_nums'].'期' : $temp['total']['nums'].'件');

            $output['nums'] += $temp['total']['nums'];
            $output['goods_money'] += $total_money;
            $output['discount'] += $temp['total']['discount'];
            $output['money'] += $temp['total']['money'];
            $output['freight'] += $temp['express']['money'] - $step1_shipping;
            $orders[] = $temp;
        }
        //再选平台活动
        $step4 = 0;//目前平台满减活动还没做，所以先搁置

        //再算平台优惠券
        $step5 = $sel_sys_coupon_money = 0;
        $sel_sys_coupon_is_discount = 0;
        $sel_sys_coupon_plat_money = 0;
        $sel_sys_coupon_merchant_money = 0;
        if($output['money'] > 0){
            $SystemCouponService = new SystemCouponService;
            $current_sys_coupons = $SystemCouponService->formatDiscount($SystemCouponService->getAvailableCoupon($uid, ['can_coupon_money'=>$goods_all_money, 'business' => 'mall', 'category'=>$mallcates, 'store_id'=>$cart[0]['store_id']], true));
            $sel_sys_coupon = 0;
            if($current_sys_coupons){
                foreach ($current_sys_coupons as $sys_coupon) {
                    $_select = 0;
                    if($coupon_id == $sys_coupon['id']){//已选择的
                        $output['plat']['coupon']['id'] = $sys_coupon['id'];
                        $output['plat']['coupon']['title'] = $sys_coupon['name'];
                        $output['plat']['coupon']['money'] = $sys_coupon['discount_money'];
                        $step5 = $sel_sys_coupon_money = $sys_coupon['discount_money'];
                        $sel_sys_coupon = $sys_coupon['id'];
                        $sel_sys_coupon_is_discount = $sys_coupon['is_discount'];//是否是折扣券
                        $sel_sys_coupon_plat_money = $sys_coupon['plat_money'];//平台补贴金额或百分比
                        $sel_sys_coupon_merchant_money = $sys_coupon['merchant_money'];//商家补贴金额或百分比
                    }
                    elseif($output['plat']['coupon']['money'] < $sys_coupon['discount_money'] && empty($coupon_id)){//未选择取优惠力度最大的
                        $output['plat']['coupon']['id'] = $sys_coupon['id'];
                        $output['plat']['coupon']['title'] = $sys_coupon['name'];
                        $output['plat']['coupon']['money'] = $sys_coupon['discount_money'];
                        $step5 = $sys_coupon['discount_money'];
                        $sel_sys_coupon = $sys_coupon['id'];
                    }
                    
                }
            }
            foreach ($current_sys_coupons as $syscoupon) {
                $output['plat']['coupon']['lists'][] = [
                    'id' => $syscoupon['id'],
                    'title' => $syscoupon['discount_des'],
                    'is_select' => $sel_sys_coupon == $syscoupon['id'] ? 1 : 0
                ];
            }
        }
        $sel_sys_coupon_merchant_money_all = 0;
        if($step5 > 0){//把平台优惠券优惠的金额平摊到各个订单里（按照各个订单支付金额占的比重）
            $alrdy = 0;
            $disappear = 0;//因为溢出消失的金额
            foreach ($orders as $key => $order) {
                $orders[$key]['total']['sys_coupon_id'] = $output['plat']['coupon']['id'];
                if(count($orders) == 1){
                    if($step5 > $order['total']['goods_money']){
                        $orders[$key]['total']['sys_coupon_money'] = $order['total']['goods_money'];
                        $step5 = $order['total']['goods_money'];
                    }
                    else{
                        $orders[$key]['total']['sys_coupon_money'] = $step5;
                    }
                }
                else{
                    if($key < count($orders) - 1){
                        $nowGoodsMoney = $order['total']['goods_money']-$order['merchant_card']['merchant_balance']-$order['merchant_card']['merchant_give_balance'];
                        $goodsAllMoney = $goods_all_money-$order['merchant_card']['merchant_balance']-$order['merchant_card']['merchant_give_balance'];
                        $sys_coupon_money = get_format_number(($nowGoodsMoney/$goodsAllMoney)*$step5, 2);
                        if($sys_coupon_money > $order['total']['goods_money']){
                            $orders[$key]['total']['sys_coupon_money'] = $order['total']['goods_money'];
                            $disappear += $sys_coupon_money - $order['total']['goods_money'];
                        }
                        else{
                            $orders[$key]['total']['sys_coupon_money'] = $sys_coupon_money;
                        }
                        $alrdy += $sys_coupon_money;
                    }
                    else{
                        $shengyu = get_format_number($step5 - $alrdy);
                        if($shengyu > $order['total']['goods_money']){
                            $orders[$key]['total']['sys_coupon_money'] = $order['total']['goods_money'];
                            $disappear += $shengyu - $order['total']['goods_money'];
                        }
                        else{
                            $orders[$key]['total']['sys_coupon_money'] = $shengyu;
                        }
                    }
                }   
                $orders[$key]['total']['goods_money'] -= $orders[$key]['total']['sys_coupon_money'];
                $orders[$key]['total']['goods_money'] < 0 && $orders[$key]['total']['goods_money'] = 0;

                //获取平台优惠券-商家补贴金额
                if($orders[$key]['total']['sys_coupon_money']>0){
                    if($sel_sys_coupon_merchant_money==0){//全部由平台承担
                        $orders[$key]['total']['sys_coupon_money_merchant'] = 0;
                    }elseif($sel_sys_coupon_is_discount){//折扣
                        $orders[$key]['total']['sys_coupon_money_merchant'] = getFormatNumber(bcmul($sel_sys_coupon_merchant_money,$orders[$key]['total']['sys_coupon_money'],3));
                    }else{//非折扣
                        $discount_merchant = getFormatNumber(bcdiv($sel_sys_coupon_merchant_money,$sel_sys_coupon_money,3));
                        $orders[$key]['total']['sys_coupon_money_merchant'] = getFormatNumber(bcmul($discount_merchant,$orders[$key]['total']['sys_coupon_money'],3));
                    }
                    $sel_sys_coupon_merchant_money_all = getFormatNumber(bcadd($sel_sys_coupon_merchant_money_all,$orders[$key]['total']['sys_coupon_money_merchant'],3));
                }

            }
            $step5 -= $disappear;
            
            //如果是非折扣，要控制计算出的商家补贴金额不能超过设置的商家补贴金额，超过的金额直接在其中一个商家补贴金额大于所超金额的订单减去
            if($sel_sys_coupon_is_discount==0 && $sel_sys_coupon_merchant_money_all > $sel_sys_coupon_merchant_money){
                $overflow = bcsub($sel_sys_coupon_merchant_money_all,$sel_sys_coupon_merchant_money,3);
                foreach ($orders as $kk => $vv) {
                    if($orders[$kk]['total']['sys_coupon_money_merchant'] > $overflow){
                        $orders[$kk]['total']['sys_coupon_money_merchant'] = getFormatNumber(bcsub($orders[$kk]['total']['sys_coupon_money_merchant'],$overflow,3));
                    }
                }
            }
        }

        //最后算会员等级(产品说本期先不做2020.09.28)
        $step6 = 0;
        // $now_user = (new UserService)->getUser($uid);
        // $user_level = (new UserLevelService)->getOne(['level','=',$now_user['level']])->toArray();
        // if($user_level && $user_level['type'] > 0){
        //     switch ($user_level['type']) {
        //         case '1':
        //             if($user_level['boon'] < 100 && $user_level['boon'] > 0){

        //             }
        //             break;
                
        //         default:
        //             # code...
        //             break;
        //     }
        // }

        $output['discount'] += $step4 + $step5 + $step6;
        $output['money'] -= $step4 + $step5 + $step6;
        $output['money'] < 0 && $output['money'] = 0;
        $output['money'] += $output['freight'];
        $output['goods_money'] = get_format_number($output['goods_money']);
        $output['money'] = get_format_number($output['money']);
        $output['discount'] = get_format_number($output['discount']);

        $output['order'] = $orders;
        return $output;
    }
}
