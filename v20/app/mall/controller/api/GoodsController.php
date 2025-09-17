<?php
/**
 * 商城商品接口控制器
 * Created by subline.
 * Author: lumin
 * Date Time: 2020/9/09 10:46
 */

namespace app\mall\controller\api;
use app\common\model\db\Area;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\percent_rate\PercentRateService;
use app\mall\model\db\MallGoodReply;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MallLimitedActNotice;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewBargainTeam;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallPrepareActSku;
use app\mall\model\db\MerchantStore;
use app\mall\model\service\activity\MallLimitedActService;
use app\mall\model\service\MallBrowseNewService;
use app\mall\model\service\MallDeliveryService;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\mall\model\service\MallGoodsSpecService;
use app\common\model\service\UserAdressService;
use app\mall\model\service\GoodsReplyService;
use app\mall\model\service\MerchantStoreMallService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\MerchantService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\coupon\MerchantCouponService;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\MallPriceNoticeService;
use app\mall\model\service\MallUserCollectService;
use app\mall\model\service\ExpressTemplateService;
use app\mall\model\service\MallBrowseService;
use app\common\model\db\CustomerService;
use app\common\model\db\User;
use app\common\model\service\UserService;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\Merchant;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\service\store\MerchantStoreShopService;
use think\helper\Arr;

class GoodsController extends ApiBaseController
{
    /**
     * 商品详情接口
     * @return [type] [description]
     */
    public function goodsDetail(){
        $goods_id = $this->request->param("goods_id", "", "intval");
        if(empty($goods_id)){
            return api_output_error(1001, 'goods_id必传!');
        }
        $now_user_id = request()->log_uid ? request()->log_uid : 0;
        $sku_id = $this->request->param("sku_id", 0, "intval");
        $address_id = $this->request->param("address_id", 0, "intval");
        $source = $this->request->param("source", "", "trim");
        $app_type = $this->request->param("app_type", "packapp", "trim");
        if($app_type!="wxapp"){
            $app_type='web';
        }else{
            $app_type='wxapp';
        }
        $output = [
            'style' => 'normal',//正常：normal；砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购:periodic
            'basic_info' => [],//基本信息
            'sku' => [//规格详情
                'types' => [],//规格类型
                'combination' => []//规格组合规则
            ],
            'extra_info' => [//额外信息
                'currency' => cfg('Currency_symbol'),
                'price_reduction' => (new MallPriceNoticeService)->isSetNotice($goods_id, $now_user_id) ? 1 : 0,
                'live_id' => 0,
                'collect' => empty((new MallUserCollectService)->getCollections($now_user_id, $goods_id)) ? 0 : 1,
                'share' => [
                    'title' => '戳一戳，有你意想不到的惊喜哦！',
                    'desc' => '',
                    'share_img' => '',
                    'activity_title' => '',
                    'activity_desc' => '',
                ],
                'form' => [],
                'notes' => [],
                'roll_text' => []
            ],
            'address' => [
                'title' => '',
                'id' => 0,
                'province' => 0,
                'city' => 0,
                'is_out_range' => 0, //1-地址超出服务范围
                'note' => '',
                'area' => 0,
            ],//收货地址
            'comments' => [],//评论相关
            'store' => [],//店铺信息相关
            'activity_info' => [//活动相关
                'limited' => [
                    'limit' => 0,
                    'price' => 0,
                    'surplus' => 0,
                ],
                'group' => [
                    'limit' => 0,
                    'id' => 0,
                    'nums' => 0,
                    'price' => 0,
                    'surplus' => 0,
                    'palyer' => '',
                    'ingroup' => [
                        'title' => '',
                        'data' => []
                    ],
                ],
                'bargain' => [
                    'limit' => 0,
                    'price' => 0,
                    'surplus' => 0,
                    'success' => 0,
                    'palyer' => '',
                    'pre' => 0,
                    'next' => 0,
                    'already' => 0,
                    'url' => '',
                    'help' => []
                ],
                'periodic' => [
                    'limit' => 0,
                    'explain' => '',
                    'limit_txt' => '',
                    'near_date' => '',
                    'nums' => 0,
                    'price' => 0,
                    'select_option' => [
                        'periodic_type' => 0,
                        'periodic_count' => 0,
                        'options' => []
                    ]
                ],
                'prepare' => [
                    'limit' => 0,
                    'deposit' => 0,
                    'deduct' => 0,
                    'surplus' => 0,
                    'date' => '',
                    'price' => 0,
                    'balance' => 0,
                    'send' => '',
                    'step' => '',
                    'status' => 0,
                    'deposit_order_id' => 0,
                ],
                'system_card' => [
                    'title' => '',
                    'money' => '',
                    'status' => 0,
                ],
                'merchant_card' => [
                    'title' => '',
                    'money' => '',
                    'status' => 0,
                ],
                'coupon' => [
                    'data' => []
                ],
                'marketing' => [
                    'type' => '',
                    'type_txt' => '',
                    'activity_id' => 0,
                    'activity_title' => '',
                    'extra' => [
                        'type_txt' => '',
                        'title' => ''
                    ],
                ]
            ]
        ];

        $MallGoodsService = new MallGoodsService();
        $MallGoodsSkuService = new MallGoodsSkuService();
        $MallGoodsSpecService = new MallGoodsSpecService();
        $param11=[
            'goods_id'=>$goods_id,
            'origin'=>$app_type,
            'uid'=>$now_user_id,
            'act_type'=>"normal",
            'act_price'=>0,
        ];

        //基本信息
        $basic_info = $MallGoodsService->getBasicInfo($goods_id, true);
        if(empty($basic_info)){
            return api_output_error(1001, '当前商品未找到!');
        }

        $sku_info = [];
        if($sku_id){
            $sku_info = $MallGoodsSkuService->getSkuById($sku_id);
            if(empty($sku_info)){
                return api_output_error(1001, '当前sku未找到!');
            }            
        }

        //分享
        $output['extra_info']['share']['title'] =$basic_info['name'];
        $output['extra_info']['share']['desc'] = $basic_info['name'];

        $MerchantService = new MerchantService;
        $merchant = $MerchantService->getInfo($basic_info['mer_id']);
        $store_status=(new MerchantStoreService())->getOne(['store_id'=>$basic_info['store_id'],'status'=>1]);
        if(empty($store_status)){
            return api_output_error(1001, '店铺不存在');
        }
        $basic_video = $basic_info['video_url'] != '' ? explode(';', $basic_info['video_url']) : [];

        $limit_buy = $MallGoodsService->get_limit_buy($basic_info['goods_id'], $basic_info['is_restriction'], $basic_info['restriction_type'], $basic_info['restriction_periodic'], $basic_info['restriction_num']);
        $restriction_txt = '';
        if($limit_buy['nums'] > 0){
            switch ($limit_buy['type']) {
                case '0':
                    $restriction_txt = '限购'.$limit_buy['nums'].'件';
                    break;
                case '1':
                    $restriction_txt = '每日限购'.$limit_buy['nums'].'件';
                    break;
                case '2':
                    $restriction_txt = '每周限购'.$limit_buy['nums'].'件';
                    break;
                case '3':
                    $restriction_txt = '每月限购'.$limit_buy['nums'].'件';
                    break;
            }
        }
        $virtual_sales = $basic_info['virtual_set'] == 1 ? $basic_info['plat_virtual_sales'] : $basic_info['virtual_sales'];
        $saleNumCondition = [];
        $saleNumCondition[] = ['od.goods_id', '=', $basic_info['goods_id']];
        if (customization('sale_num_cancel')) { //销量展示加上已取消的订单数量
            $saleNumCondition[] = ['od.status', 'between', [0, 59]];
        } else {
            $saleNumCondition[] = ['od.status', 'between', [0, 49]];
        }
        $sale_num = (new MallOrderDetail())->alias('od')
        ->join('mall_order o', 'o.order_id=od.order_id')
        ->join('user u', 'u.uid = o.uid')
        ->where($saleNumCondition)
        ->sum('num'); 
        $output['basic_info'] = [
            'goods_id' => $basic_info['goods_id'],
            'imgs' => empty($basic_info['imgs'])?[]:$basic_info['imgs'],
            'video_url' => replace_file_domain($basic_video[0] ?? ''),
            'video_img' => replace_file_domain($basic_video[1] ?? ''),
            'video_time' => $basic_video[2] ?? '0',
            'price' => get_format_number($basic_info['price']),
            'title' => $basic_info['name'],
            'status' => $basic_info['status'],
            'sale_nums' => $sale_num + $virtual_sales,
            'stock_num' => $basic_info['stock_num'],
            'location' => $basic_info['location'],
            'guarantee' => $MallGoodsService->dealGoodsService($basic_info['service_desc']),
            'guarantee_detail' => $MallGoodsService->dealGoodsServiceDetail($basic_info['service_desc']),
            'detail' => replace_file_domain_content_img($basic_info['goods_desc']),
            'parameters' => replace_file_domain_content_img($basic_info['spec_desc']),
            'packing' => replace_file_domain_content_img($basic_info['pack_desc']),
            'restriction_num' => $limit_buy['nums'],
            'restriction_txt' => $restriction_txt,
            'initial_salenum' => $basic_info['initial_salenum'],
            'delivery_note' => $basic_info['free_shipping'] == '1' ? '包邮' : '',
            'bargain_can_pay' => 0, //1-砍价过程可以支付，显示去除已砍后的金额
            'bargain_price' => 0, //1-砍价过程可以支付，显示去除已砍后的金额
        ];
        $output['basic_info'] = array_merge($output['basic_info'], Arr::only($basic_info,[
            'unit'
        ]));
        $output['extra_info']['live_id'] = $basic_info['current_liveshow_id'] ? : 0;
        //积分展示
        $output['basic_info']['score_text'] = '';
        if(customization('goods_show_score') && $scorePrice = floatval($basic_info['price'])){
            $scorePercent = PercentRateService::scorePercent('mall', $basic_info['goods_id'], $basic_info['mer_id']);
            $score = bcmul($scorePrice, $scorePercent, 2);
            if(cfg('score_round_two')){
                $score = sprintf('%.2f',floatval($score));
            }else{
                $score = round($score);
            }
            floatval($score) > 0 && $output['basic_info']['score_text'] = cfg('score_name').'+'.$score;
        }
        //组装form 和 其他备注
        $notes = $basic_info['notes'] ? @unserialize($basic_info['notes']) : [];
        if($notes){
            foreach ($notes as $key => $value) {
                $output['extra_info']['notes'][] = [
                    'property' => $value['name'],
                    'select_num' => $value['num'],
                    'property_val' => $value['val'],
                ];
            }
        }
        $forms = $basic_info['leave_message'] ? @unserialize($basic_info['leave_message']) : [];
        if($forms){
            foreach ($forms as $key => $value) {
                $output['extra_info']['form'][] = [
                    'title' => $value['message'],
                    'type' => $value['type'] == '2' ? 'text' : 'image',
                    'require' => $value['is_must'] != 0 ? '1' : '0'
                ];
            }
        }

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
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($value['price'],'mall',false,$basic_info['score_percent']);
            $output['basic_info']['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $output['basic_info']['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
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
                'buy_can_get_score' => $buy_can_get_score?$buy_can_get_score['score']:0,
                'buy_can_get_score_txt' => $buy_can_get_score?$buy_can_get_score['score_txt']:'',
            ];
        }
        //消费一元获得积分
        $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($basic_info['price'],'mall',($basic_info['goods_type']=='sku'?true:false),$basic_info['score_percent']);
        $output['basic_info']['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
        $output['basic_info']['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        $UserAdressService = new UserAdressService();
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
        }
        elseif($now_user_id){
            //选择一个默认的收货地址
            $now_user_address = $UserAdressService->getAdressByUid(request()->log_uid, 1);
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

        //店铺信息
        $store_info = (new MerchantStoreMallService)->getMallStoreInfo($basic_info['store_id']);
        $output['store']['only_zt'] =0;
        if(!empty($store_info)){
            if($store_info['is_delivery']==0 && $store_info['is_houseman']==0 && $store_info['is_zt']==1){
                $output['store']['only_zt'] =1;
            }
        }
        //获取
        if(cfg('open_mall_order_area')&&$output['address']['id']&&$store_info&&$store_info['is_zt']!=1){
            $goods_area_id[] = 0;
            if($output['address']['area']>0){
                $goods_area_id[] = $output['address']['area'];
            }
            if($output['address']['city']>0){
                if($output['address']['area']<=0){
                    $children_area = (new Area())->where(['area_pid'=>$output['address']['city'],'is_open'=>1])->column('area_id');
                    $goods_area_id = array_merge($goods_area_id,$children_area);
                }
                $goods_area_id[] = $output['address']['city'];
            }
            if($output['address']['province']>0){
                $goods_area_id[] = $output['address']['province'];
            }
            $output['address']['is_out_range'] = 0;
            $output['address']['note'] = '';
            
            //获取商品服务范围
            if(cfg('open_mall_order_area')==1){
                $goods_area = (new MallGoodsArea())->where([['goods_id','=',$basic_info['goods_id']],['store_id','=',$basic_info['store_id']],['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                $is_out_range = $goods_area?0:1;
            }else{
                $is_out_range = (new MallDeliveryService())->checkRange($output['address'],[$store_info])['is_out_range'];
            }
            
            if($is_out_range){
                $output['address']['is_out_range'] = 1;
                $output['address']['note'] = '超出可服务范围，无法下单';
            }
        }elseif($output['address']['id']){
            $output['address']['is_out_range'] = 0;
            $output['address']['note'] = '';
        }

        //评价
        $comments = (new GoodsReplyService)->getCommentList($goods_id, 0, 0, 2, 1, 2);
        $output['comments']['nums'] = $comments['all_comments'] ?? 0;
        $output['comments']['favorable_comments_rate'] = $comments['all_comments'] ? get_format_number(($comments['good_comments']/$output['comments']['nums']) * 100).'%' : '0%';
        $output['comments']['lists'] = [];
        $merchantModel = new Merchant();
        $userModel = new User();
        foreach ($comments['list'] as $key => $value) {
            $merchant_reply = $user_reply = [];
            if(!empty($value['merchant_reply_content'])){
                $merInfo = $merchantModel->field(['name','logo'])->where('mer_id', $value['mer_id'])->find();
                $merchant_reply = [
                    'headimg'   =>  !empty($merInfo['logo']) ? replace_file_domain($merInfo['logo']) : '',
                    'nickname'  =>  $merInfo['name'] ?? '',
                    'content'   =>  $value['merchant_reply_content'],
                    'time'      =>  date('Y/m/d H:i', $value['merchant_reply_time'])
                ];
            }
            if(!empty($value['user_reply_merchant'])){
                $userInfo = $userModel->field(['nickname','avatar'])->where('uid', $value['uid'])->find();
                $user_reply = [
                    'headimg'   =>  !empty($userInfo['avatar']) ? replace_file_domain($userInfo['avatar']) : '',
                    'nickname'  =>  $userInfo['nickname'] ?? '',
                    'content'   =>  $value['user_reply_merchant'],
                    'time'      =>  date('Y/m/d H:i', $value['user_reply_merchant_time'])
                ];
            }
            $temp = [
                'rpl_id' => $value['rpl_id'],
                'avatar' => $value['user_image'],
                'nickname' => $value['user_name'],
                'skuname' => $value['goods_sku_dec'],
                'comments' => $value['comment'],
                'imgs' => $value['reply_pic'],
                'reply_mv' => $value['reply_mv'],
                'create_day' => $value['create_day'],
                'merchant_reply'    =>  $merchant_reply,
                'user_reply'    =>  $user_reply,
            ];
            $output['comments']['lists'][] = $temp;
        }

        //店铺信息
        $store_info = (new MerchantStoreMallService)->getMallStoreInfo($basic_info['store_id']);
        $output['extra_info']['share']['desc'] = $store_info['name'];
        $output['store']['only_zt'] =0;
        if(!empty($store_info)){
            if($store_info['is_delivery']==0 && $store_info['is_houseman']==0 && $store_info['is_zt']==1){
                $output['store']['only_zt'] =1;
            }
        }
        $output['store']['store_id'] = $basic_info['store_id'];
        $output['store']['mer_id'] = $basic_info['mer_id'];
        $output['store']['logo'] = $store_info['logo'];
        $output['store']['store_name'] = $store_info['name'];
        $service_score=(new MallGoodReply())->getAvg(['store_id'=>$basic_info['store_id']],'service_score');
        $goods_score=(new MallGoodReply())->getAvg(['store_id'=>$basic_info['store_id']],'goods_score');
        $logistics_score=(new MallGoodReply())->getAvg(['store_id'=>$basic_info['store_id']],'logistics_score');
        $avg=get_number_format(($service_score+$goods_score+$logistics_score)/3);
        $output['store']['stars'] = $avg ? : 5;
        $output['store']['phone'] = $store_info['phone'];
        $now_user = (new UserService)->getUser($now_user_id);
        // $output['store']['service_url'] = (new CustomerService)->getUrl(cfg('im_appid'), cfg('im_appkey'), cfg('im_url'), $now_user['openid'] ?? '', $basic_info['mer_id']);
        $_service_url = (new MallGoodsService)->serviceCustomer($now_user_id, $basic_info['store_id']);
        $output['store']['service_url'] = $_service_url ? $_service_url : "";
        $output['store']['recoms'] = [];


        $now_city = $this->request->param('now_city', 0, 'intval');
        $addrs=(new UserService())->userFind('province_id,city_id,area_id',['uid'=>$now_user_id]);
        if(empty($addrs)){
            $arr1['province_id']='';
            $arr1['city_id']=$now_city;
            $arr1['area_id']='';
        }else{
            $addrs=$addrs->toArray();
            $arr1['province_id']=$addrs['province_id'];
            $arr1['city_id']=$addrs['city_id'];
            $arr1['area_id']=$addrs['area_id'];
        }
        

        $output['store']['long'] = $store_info['long'];
        $output['store']['lat'] = $store_info['lat'];
	$store_recoms = (new MallGoodsService)->recommendGoodsList([], 1, [['s.store_id','=',$basic_info['store_id']], ['s.cate_first', '=', $basic_info['cate_first']]],0,$arr1);

        foreach ($store_recoms['list'] as $key=>$val){
            $temp['goods_id']=$val['goods_id'];
            $temp['img']=$val['image'];
            $temp['title']=$val['name'];
            $temp['price']=get_number_format($val['min_price']);
            $act_id=0;
            $condition3=[//条件
                ['s.store_id','=',$val['store_id']],
                ['m.goods_id','=',$val['goods_id']],
                ['s.status','<>',2],
                ['s.start_time','<',time()],
                ['s.end_time','>=',time()],
                ['s.is_del','=',0],
                ['s.type','in',['bargain','group','limited','prepare','periodic']],
            ];
            $ret=(new MallActivity())->getActByGoodsID($condition3,$field='*');
            if(empty($ret)){//周期购不能时间判断
                $store_recoms['list'][$key]['activity_type']='normal';
                $condition4=[//条件
                    ['s.store_id','=',$val['store_id']],
                    ['m.goods_id','=',$val['goods_id']],
                    ['s.status','=',1],
                    ['s.type','=','periodic'],
                    ['s.is_del','=',0],
                ];
                $ret1=(new MallActivity())->getActByGoodsID($condition4,$field='*');
                if(!empty($ret1)){
                    $act_id=$ret1['act_id'];
                    $store_recoms['list'][$key]['activity_type']=$ret1['type'];
                    $where3=[['s.id','=',$act_id],['m.goods_id','=',$val['goods_id']]];
                    $li=(new MallNewPeriodicPurchase())->getGoodsAndPeriodic($where3,$field="s.periodic_count,m.min_price");
                    $temp['price']=$li['periodic_count']*$li['min_price'];
                }
            }else{
                $act_id=$ret['act_id'];
                $store_recoms['list'][$key]['activity_type']=$ret['type'];
                $temp['activity_type']=$ret['type'];
            }
            $store_recoms['list'][$key]['act_price']="";
            if($store_recoms['list'][$key]['activity_type'] !='normal'){//活动商品给价格
                switch ($store_recoms['list'][$key]['activity_type']){
                    case 'bargain':
                        $where=[['act_id','=',$act_id],['goods_id','=',$val['goods_id']]];
                        $arr=(new MallNewBargainSku())->getBySkuId($where,$field="act_price");
                        $temp['price']=get_format_number($arr['act_price']);
                        break;
                    case 'group':
                        $where=[['act_id','=',$act_id],['goods_id','=',$val['goods_id']]];
                        $price=(new MallNewGroupSku())->getPice($where);
                        $temp['price']=get_format_number($price);
                        break;
                    case 'limited':
                        $price=(new MallLimitedSku())->limitMinPrice($act_id,$val['goods_id']);
                        $temp['price']=get_format_number($price);
                        break;
                    case 'prepare':
                        $price=(new MallPrepareActSku())->prepareMinPrice($act_id,$val['goods_id']);
                        $temp['price']=get_format_number($price);
                        break;
                    default:
                        break;
                }

            }
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($temp['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
            $temp['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $temp['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            $output['store']['recoms'][] = $temp;
        }
        if($output['store']['only_zt']){//只有有字体地址
            if($address_id){//自提地址
                if (strpos($address_id, 's') !== false) {
                    $store_addr_id=str_replace('s','',$address_id);
                    $store_msg=(new MerchantStore())->getOne(['store_id'=>$store_addr_id]);
                    if(!empty($store_msg)){
                        $output['address']['title'] = $store_msg['adress'] ?? '';
                        $output['address']['id'] = $address_id;
                        $output['address']['province'] = $store_msg['province_id'];
                        $output['address']['city'] = $store_msg['city_id'];
                    }
                }elseif (strpos($address_id, 'p') !== false){
                    $pick_id=str_replace('p','',$address_id);
                    $pick_msg=(new PickAddress())->getOne(['id'=>$pick_id]);
                    if(!empty($pick_msg)){
                        $pick_msg=$pick_msg->toArray();
                        $output['address']['title'] = $pick_msg['pick_addr'] ?? '';
                        $output['address']['id'] = $address_id;
                        $output['address']['province'] = $pick_msg['province_id'];
                        $output['address']['city'] = $pick_msg['city_id'];
                    }
                }
            }else{
                $store_msg=(new MerchantStore())->getOne(['store_id'=>$basic_info['store_id']]);
                if(!empty($store_msg)){
                    $output['address']['title'] = $store_msg['adress'] ?? '';
                    $output['address']['id'] = 's'.$basic_info['store_id'];
                    $output['address']['province'] = $store_msg['province_id'];
                    $output['address']['city'] = $store_msg['city_id'];
                }
            }
        }

        $ExpressTemplateService = new ExpressTemplateService;
        if($basic_info['free_shipping'] != '1'){
            if($store_info['is_delivery'] == '1'){
                $param = [];
                $param[] = [
                    'fright_id' => $basic_info['fright_id'],
                    'num' => $basic_info['initial_salenum'] > 0 ? $basic_info['initial_salenum'] : 1,
                    'other_area_fright' => $basic_info['other_area_fright'] ?? 0,
                    'goods_id' => $goods_id
                ];
                $output['basic_info']['delivery_note'] = '运费'. cfg('Currency_symbol') . get_format_number($ExpressTemplateService->computeFee($basic_info['store_id'], $param, $output['address']['city'],$output['address']['province']));
                if($output['address']['id'] == 0){
                    $output['basic_info']['delivery_note'] = '';
                }
            }
            elseif($store_info['is_houseman'] == '1'){
                $output['basic_info']['delivery_note'] = '同城配送';
            }
            elseif($store_info['is_zt'] == '1'){
                $output['basic_info']['delivery_note'] = '同城自提';
            }
        }

        if($output['store']['only_zt']){
            $output['basic_info']['delivery_note'] = '自提';
        }

        $where_notice_status=[
            ['goods_id','=',$goods_id],
            ['uid','=',$now_user_id]
        ];
        //各种活动
        $MallActivityService = new MallActivityService;
        $activity_info = $MallActivityService->getActivity($goods_id, 0, $now_user_id, '', 0, $source);
        if(isset($activity_info['act_stock_num'])){
            if($output['basic_info']['stock_num'] == '-1'){
                $output['basic_info']['stock_num'] = $activity_info['act_stock_num'];
            }
            elseif($output['basic_info']['stock_num'] > 0){
                if($activity_info['act_stock_num'] != '-1'){
                    $output['basic_info']['stock_num'] = ($output['basic_info']['stock_num'] < $activity_info['act_stock_num']) ? $output['basic_info']['stock_num'] : $activity_info['act_stock_num'];
                }
            }

            array_push($where_notice_status,['act_id','=',$activity_info['act_id']]);
        }

        if($now_user_id){
            $notice_status=(new MallLimitedActNotice())->getNoticeStatus($where_notice_status,"id")?1: 0;
        }else{
            $notice_status=0;
        }
        //限时优惠
        if($activity_info['style'] == 'limited'){
            $output['activity_info']['limited'] = [
                'price' => get_format_number($activity_info['price']),
                'surplus' => $activity_info['surplus'],
                'limited_status' => $activity_info['limited_status'],
                'start_time' => $activity_info['start_time'],
                'act_id' => $activity_info['act_id'],
                'notice_status'=>$notice_status
            ];
            $output['basic_info']['stock_num'] = $activity_info['act_stock_num'];
            if($activity_info['min_stock_num']==-1){
                $output['basic_info']['stock_num'] = -1;
            }
            if(isset($activity_info['sku_list'])){
                $group_sku = array_column($activity_info['sku_list'], 'act_price', 'sku_id');
                $group_sku_stock = array_column($activity_info['sku_list'], 'act_stock', 'sku_id');
                //修改sku活动价格
                foreach ($output['sku']['combination'] as $combination_key => $combination_value) {
                    if(isset($group_sku[$combination_value['sku_id']])){
                        $output['sku']['combination'][$combination_key]['price'] = get_format_number($group_sku[$combination_value['sku_id']]);
                        $output['sku']['combination'][$combination_key]['stock'] = $group_sku_stock[$combination_value['sku_id']];
                    }
                }
            }
            /**
             * 补充展示，限时存在有-1库存的规格，开始不选则时暂时库存充足
             */
            if($activity_info['min_stock_num'] == -1){
                $output['basic_info']['stock_num'] =-1;
            }
            $output['style'] = 'limited';
        }
        if($activity_info['style'] != 'normal'){
            $param11['act_price']=isset($activity_info['price'])?get_format_number($activity_info['price']):$output['basic_info']['price'];
            $param11['act_type']=$activity_info['style'];
        }
        try {
            $ret = (new MallGoodsService())->share($param11);
            $output['extra_info']['share']['share_img'] = $ret['data']['friend_img'];
        } catch (\Exception $e) {
            $output['extra_info']['share']['share_img'] = '';
        }
        
        //有预告的也在商品详情展示
        $limitMsg=(new MallLimitedActService())->activityList(0,1,$now_user_id,10,"k.act_id",'home',$goods_id);
        if(!empty($limitMsg)){
            $output['activity_info']['limited'] = [
                'start_time' => $limitMsg[0]['start_time'],
                'price' => get_format_number($limitMsg[0]['limited_price']),
                'surplus' => $limitMsg[0]['left_time'],
                'limited_status' => $limitMsg[0]['limited_status'],
                'act_id' => $limitMsg[0]['id'],
                'notice_status'=>$notice_status
            ];
            $output['style'] = 'limited';
        }

        //拼团
        if($activity_info['style'] == 'group'){
            $output['activity_info']['group'] = [
                'id' => $activity_info['id'],
                'nums' => $activity_info['nums'],
                'price' => get_format_number($activity_info['price']),
                'surplus' => $activity_info['surplus'],
                'palyer' => $activity_info['palyer'],
                'ingroup' => $activity_info['ingroup']
            ];
            if(isset($activity_info['sku_list'])){
                $group_sku = array_column($activity_info['sku_list'], 'act_price', 'sku_id');
                $group_sku_stock = array_column($activity_info['sku_list'], 'act_stock', 'sku_id');
                //修改sku活动价格
                foreach ($output['sku']['combination'] as $combination_key => $combination_value) {
                    if(isset($group_sku[$combination_value['sku_id']])){
                        $output['sku']['combination'][$combination_key]['price'] = get_format_number($group_sku[$combination_value['sku_id']]);
                        $output['sku']['combination'][$combination_key]['stock'] = $group_sku_stock[$combination_value['sku_id']];
                    }
                }
            }
            $output['style'] = 'group';
        }
        //砍价
        if($activity_info['style'] == 'bargain'){
            $output['activity_info']['bargain'] = [
                'price' => get_format_number($activity_info['act_price']),
                'surplus' => $activity_info['surplus'],
                'success' => $activity_info['success'],
                'palyer' => $activity_info['palyer'],
                'pre' => $activity_info['pre'],
                'next' => $activity_info['next']?:0,//暂时这样修改，mrdeng
                'already' => $activity_info['already'],//是否已经发起过砍价
                'url' => $activity_info['url'],//砍价分享地址
                'team_id' => $activity_info['team_id'] ?? 0,
                'help' => [],
                'id' => $activity_info['act_id'] ?: 0,//砍价ID
            ];
            foreach ($activity_info['help'] as $_help) {
                $output['activity_info']['bargain']['help'][] = [
                    'nickname' => $_help['nickname'],
                    'avatar' => $_help['user_logo'] ?: '',
                    'price' => get_format_number($_help['bar_price']),
                    'date' => date('Y-m-d',$_help['bar_time']),
                ];
            }
            if(isset($activity_info['sku_id'])){
                foreach ($output['sku']['combination'] as $key => $value) {
                    if($value['sku_id'] == $activity_info['sku_id']){
                        $output['sku']['combination'][$key]['is_bargain'] = 1;
                    }
                }
            }
            $output['style'] = 'bargain';
            if($now_user_id){
                $user_team = (new MallNewBargainTeam())->where(['status'=>0,'user_id'=>$now_user_id,'act_id'=>$activity_info['act_id']])->findOrEmpty()->toArray();
                if($user_team){
                    $output['extra_info']['share']['activity_title'] = '差你一刀了！快来一起砍价【'.$basic_info['name'].'】';
                    $output['extra_info']['share']['activity_desc'] = '价值'.get_format_number($basic_info['price']).'元，砍到'.get_format_number($activity_info['act_price']).'元就拿走，点击立即参与';
                }
            }
            //开启砍价过程可以支付显示去除已砍后金额
            if($activity_info['bargain_can_pay']){
                $output['basic_info']['bargain_can_pay'] = 1;
                $output['basic_info']['bargain_price'] = getFormatNumber(($output['basic_info']['price'] - $activity_info['pre']));
            }
        }
        //周期购
        if($activity_info['style'] == 'periodic'){
            $output['activity_info']['periodic'] = [
                'explain' => $activity_info['explain'],
                'limit' => $activity_info['limit'],
                'near_date' => $activity_info['near_date_msg'],
                'limit_txt' => $activity_info['limit_txt'] > 0 ? '限制'.$activity_info['limit_txt'].'次' : '不限制',
                'select_option' => []
            ];
            $periodic_info = $MallActivityService->getGoodsPeriodic($goods_id);
            $output['activity_info']['periodic']['select_option']['periodic_type'] = $periodic_info['periodic_type'];
            $output['activity_info']['periodic']['select_option']['periodic_count'] = $periodic_info['periodic_count'];
            $dates = $periodic_info['dates'] ? $periodic_info['dates'] : [];
            if($dates){
                sort($dates);
            }
            $output['activity_info']['periodic']['select_option']['options'] = $dates;
            $output['style'] = 'periodic';
        }
        //预售
        if($activity_info['style'] == 'prepare'){
            $output['activity_info']['prepare'] = [
                'deposit' => $activity_info['deposit'],
                'deduct' => $activity_info['deposit'] + $activity_info['discount_price'],
                'surplus' => $activity_info['surplus'],
                'date' => $activity_info['date'],
                'price' => get_format_number($activity_info['price']),
                'balance' => $activity_info['balance'],
                'send' => $activity_info['send'],
                'step' => $activity_info['step'],
                'selected_sku' => [],
                'status' => 0,
                'is_pay_end' => 0
            ];
            if(isset($activity_info['sku_list'])){
                $group_sku = array_column($activity_info['sku_list'], 'act_price', 'sku_id');
                $group_sku_stock = array_column($activity_info['sku_list'], 'act_stock', 'sku_id');
                $group_sku_bargain_price = array_column($activity_info['sku_list'], 'bargain_price', 'sku_id');
                $group_sku_discount_price = array_column($activity_info['sku_list'], 'discount_price', 'sku_id');
                //修改sku活动价格
                foreach ($output['sku']['combination'] as $combination_key => $combination_value) {
                    if(isset($group_sku[$combination_value['sku_id']])){
                        $output['sku']['combination'][$combination_key]['price'] = get_format_number($group_sku[$combination_value['sku_id']]);
                        $output['sku']['combination'][$combination_key]['stock'] = $group_sku_stock[$combination_value['sku_id']];
                        $output['sku']['combination'][$combination_key]['prepare_deposit'] = $group_sku_bargain_price[$combination_value['sku_id']];
                        $output['sku']['combination'][$combination_key]['prepare_discount'] = number_format($group_sku_discount_price[$combination_value['sku_id']] + $group_sku_bargain_price[$combination_value['sku_id']],2);
                    }
                }
            }
            if($now_user_id > 0){
                $prepare_order = $MallActivityService->getPrepareOrderStatus($goods_id, $now_user_id, $activity_info['id']);
                if($prepare_order){
                    $second_pay = $prepare_order['second_pay'] ?? 0;
                    if($prepare_order['pay_status'] == 1){
                        $nowtime = time();

                        if($nowtime < $prepare_order['start_time']){
                            $output['activity_info']['prepare']['surplus'] = $prepare_order['start_time'] - $nowtime;
                        }
                        elseif($nowtime > $prepare_order['start_time'] && $nowtime < $prepare_order['end_time']){
                            $output['activity_info']['prepare']['surplus'] = $prepare_order['end_time'] - $nowtime;
                            $output['activity_info']['prepare']['is_pay_end'] = 1;
                        }
                        // else{
                        //     //超时取消订单
                        //     $MallActivityService->getPrepareOrderRefund($prepare_order['order_id']);
                        //     return api_output_error(1007, '请前端再次请求本接口！');
                        // }
                        $output['activity_info']['prepare']['status'] = 1;
                        $output['activity_info']['prepare']['deposit_order_id'] = $prepare_order['order_id'];
                        if(isset($prepare_order['sku_id']) && $prepare_order['sku_id'] > 0){
                            $combination = '';
                            foreach ($output['sku']['combination'] as $combination_key => $combination_value) {
                                if($combination_value['sku_id'] == $prepare_order['sku_id']){
                                    $combination = $combination_key;
                                    break;
                                }
                            }
                            if($combination){
                                $combination = explode('|', $combination);
                                foreach ($combination as $bi) {
                                    $_bi = explode(':', $bi);
                                    foreach ($output['sku']['types'] as $_type) {
                                        if($_type['id'] == $_bi[0]){
                                            foreach ($_type['sons'] as $_t) {
                                                if($_t['id'] == $_bi[1]){
                                                    $output['activity_info']['prepare']['selected_sku'][] = $_t['title'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $output['style'] = 'prepare';
        }
        //平台会员卡
        $output['activity_info']['system_card'] = [//暂时废弃，因为还没有规划好
            'title' => '',
            'money' => '',
            'status' => 0
        ];
        //商家会员卡
        $output['activity_info']['merchant_card'] = [
            'title' => '',
            'money' => '',
            'status' => 0
        ];
        $MerchantCardService = new MerchantCardService;
        $card_data = $MerchantCardService->getCard($basic_info['mer_id']);
        if(empty($card_data) || $card_data['status'] == '0'){
            $output['activity_info']['merchant_card']['status'] = -1;
        }
        if($now_user_id){
            $current_card = $MerchantCardService->getUserCard($now_user_id, $basic_info['mer_id']);
            if($current_card){
                $output['activity_info']['merchant_card']['status'] = 1;
                if($current_card['discount'] != 10 && $current_card['discount'] != '0'){
                    $output['activity_info']['merchant_card']['title'] = $merchant['name'].'会员卡';
                    $output['activity_info']['merchant_card']['money'] = get_format_number($current_card['discount']).'折优惠';
                }
            }
        }
        //优惠券信息
        //平台优惠券
        $output['activity_info']['coupon']['data'] = [];
        $system_coupon = (new SystemCouponService)->getSystemCouponList('mall', '', $now_user_id, true, true);
        $couponStoreMod = new \app\common\model\db\SystemCouponStore();
        foreach ($system_coupon as $key => $value) {
            //指定店铺
            if ($value['use_type'] == 1) {
                $findStore = $couponStoreMod->where('coupon_id', $value['coupon_id'])->where('store_id', $basic_info['store_id'])->find();
                if (empty($findStore)) {
                    continue;
                }
            }
            if($value['is_use'] == '0' && $value['status'] == '1'){
                $temp = [
                    'title' => $value['name'],
                    'rule' => $value['discount_des'],
                    'limit' => $value['limit_date'],
                    'money' => $value['discount_title'],
                    'get' => $value['is_get'],
                    'type' => 'system',
                    'coupon_id' => $value['coupon_id']
                ];
                $output['activity_info']['coupon']['data'][] = $temp;
            }
        }
        //商家优惠券
        $system_coupon = (new MerchantCouponService)->getMerchantCouponList($basic_info['mer_id'], $goods_id, 'mall', '', $now_user_id, true);
        foreach ($system_coupon as $key => $value) {
            $c_store_ids = $value['store_id']?explode(',',$value['store_id']):[];
            if(!in_array($basic_info['store_id'],$c_store_ids)){
                continue;
            }
            if($value['is_use'] == '0' && $value['status'] == '1'){
                $temp = [
                    'title' => $value['name'],
                    'rule' => $value['discount_des'],
                    'limit' => $value['limit_date'],
                    'money' => $value['discount_title'],
                    'get' => $value['is_get'],
                    'type' => 'merchant',
                    'coupon_id' => $value['coupon_id']
                ];
                $output['activity_info']['coupon']['data'][] = $temp;
            }
        }
        //参与的营销活动
        $output['activity_info']['marketing'] = [
            'type' => '',
            'type_txt' => '',
            'activity_id' => 0,
            'activity_title' => '',
            'extra' => [
                'type_txt' => '',
                'title' => ''
            ]
        ];
        $goods_store_activity = $MallActivityService->checkGoodsBelongAct($basic_info['store_id'], $goods_id);
        if($goods_store_activity){
            $output['activity_info']['marketing']['type'] = $goods_store_activity['type'];
            switch ($goods_store_activity['type']) {
                case 'reached':
                    $output['activity_info']['marketing']['type_txt'] = 'N元N件';
                    break;
                case 'minus_discount':
                    $output['activity_info']['marketing']['type_txt'] = '满减';
                    break;
                case 'give':
                    $output['activity_info']['marketing']['type_txt'] = '满赠';
                    break;
                case 'shipping':
                    $output['activity_info']['marketing']['type_txt'] = '满包邮';
                    break;
            }
            $output['activity_info']['marketing']['activity_id'] = $goods_store_activity['id'];
            $output['activity_info']['marketing']['activity_title'] = $goods_store_activity['title'];
        }

        //调用浏览记录
        $mallBrowseService = new MallBrowseService();
        $mallBrowseService->insertRecord($now_user_id, $basic_info['goods_id'] ?? 0, $basic_info['cate_second'] ?? 0);
        $mallBrowseService->insertNewRecord($now_user_id, $basic_info['goods_id'] ?? 0, $basic_info['cate_second'] ?? 0);
        //查询商品今日浏览量
        $todayBrowseNum = (new MallBrowseNewService())->getBrowseNumToday($basic_info['goods_id']);
        $updateTodayBrowseNumStatus = $basic_info['browse_num_today'] == $todayBrowseNum ? false : true;//是否更新商品今日浏览量
        //更新商品浏览数量
        $mallBrowseService->updateGoodsBrowseNum($basic_info['goods_id'],$todayBrowseNum,$updateTodayBrowseNumStatus);
        return api_output(0, $output);
    }

    /**
     * 商品分享海报
     */
    public function shareGoods(){
        $param['goods_id'] = $this->request->param('goods_id', '', 'intval');
        $param['origin'] = $this->request->param("app_type", "packapp", "trim");
        $param['poster_params'] = $this->request->param("poster_params", [], "trim");
        $param['uid'] = request()->log_uid ? request()->log_uid : 0;
        $param['avatar'] = $this->userInfo['avatar'];
        $param['nickname'] = $this->userInfo['nickname'];
        $param['group_price'] = $this->request->param('group_price','-1');
        $param['now_user_id'] = request()->log_uid ? request()->log_uid : 0;
        if(empty($param['uid'])){
            throw new \think\Exception("用户未登录", 1002);
        }
        try {
            $img = (new MallGoodsService())->shareGoods($param);
            return api_output(0, ['url'=>$img], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}