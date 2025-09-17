<?php
/**
 * 商城公共接口控制器
 * Created by subline.
 * Author: JJC
 * Date Time: 2020/5/27 10:46
 */

namespace app\mall\controller\api;

use app\common\model\service\ConfigDataService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\SingleFaceService;
use app\common\model\service\UserAdressService;
use app\common\model\service\UserService;
use app\community\model\service\ConfigService;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallGoodReply;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewGroupTeamUser;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallPrepareActSku;
use app\mall\model\db\MallPriceNotice;
use app\mall\model\db\MallSearchFind;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\activity\MallFullGiveActService;
use app\mall\model\service\activity\MallFullGiveLevelService;
use app\mall\model\service\activity\MallFullMinusDiscountActService;
use app\mall\model\service\activity\MallLimitedActService;
use app\mall\model\service\activity\MallNewBargainActService;
use app\mall\model\service\activity\MallNewBargainTeamService;
use app\mall\model\service\activity\MallNewGroupActService;
use app\mall\model\service\activity\MallNewGroupOrderService;
use app\mall\model\service\activity\MallNewGroupTeamService;
use app\mall\model\service\activity\MallReachedActService;
use app\mall\model\service\activity\MallShippingActService;
use app\mall\model\service\MallBrowseNewService;
use app\mall\model\service\MallBrowseService as MallBrowseService;
use app\mall\model\service\MallCartNewService as MallCartNewService;
use app\mall\model\service\MallCategoryService as MallCategoryService;
use app\mall\model\service\MallCategorySpecService as MallCategorySpecService;
use app\mall\model\service\MallDeliveryService;
use app\mall\model\service\MallGoodsActivityBannerService;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\mall\model\service\MallGoodsSpecService as MallGoodsSpecService;
use app\mall\model\service\MallGoodsWordsService;
use app\mall\model\service\MallGoodUserWordService;
use app\mall\model\service\MallHomeDecorateService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallRecommendCateService as MallRecommendCateService;
use app\mall\model\service\MallSearchLogService as MallSearchLogService;
use app\mall\model\service\MallStoreService as MallStoreService;
use app\mall\model\service\MallUserCollectService;
use app\mall\model\service\SearchHotMallNewService;
use pinyin\Pinyin;
use think\facade\Config;
use think\facade\Db;

class HomeController extends ApiBaseController
{
    /**
     * @author  朱梦群
     * @time  2020/10/21
     * 商城首页模块接口
     */
    public function index()
    {
        $Homeservice = new MallHomeDecorateService();
        $return = [];//返回的数据集合
        //搜索默认
        $return['default_search_word'] = "";

        //热搜
        $return['hot_search_words'] = (new SearchHotMallNewService())->getIndexHotSearch();

        //顶部轮播图
        $return['top_adver'] = $Homeservice->getAdverByCatKey('wap_mall_index_top', 8);

        //导航栏导航列表
        $return['slider'] = $Homeservice->getAdverByCatKey('wap_mall_slider', 10);

        //单图广告
        $return['middle_adver'] = $Homeservice->getAdverByCatKey('index_middle_mall', 1);

        //六宫格
        $return['six_adver'] = $Homeservice->getSixList(6,1);
        $return['share_data'] = array(
            'title' => cfg('mall_share_title') ? cfg('mall_share_title') : cfg('mall_alias_name_new'),
            'content' => '',
            'url' => $this->request->param('Device-Id') == 'wxapp' ? '/pages/shopmall_third/shopmall_index' : cfg('site_url') . "/packapp/plat/pages/shopmall_third/shopmall_index",
            'image' =>cfg('mall_share_pic')?replace_file_domain(cfg('mall_share_pic')) :replace_file_domain(cfg('wechat_share_img'))
        );
        //猜你喜欢（推荐）
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $return['recommend'] = $Homeservice->getRecList(['is_display' => 1, 'status' => 1], $page, $pageSize);


        $return['right_top_menu'] = [
            [
                'url' => get_base_url().'pages/my/address/index',
                'icon' => cfg('site_url').'/static/images/mall/address.png',
                'title' => '收货地址'
            ],            
            [
                'url' =>  get_base_url().'pages/shopmall_third/mybargaining',
                'icon' => cfg('site_url').'/static/images/mall/baragain.png',
                'title' => '我的砍价'
            ],            
            [
                'url' =>  get_base_url().'pages/my/collect?type=mall',
                'icon' => cfg('site_url').'/static/images/mall/collection.png',
                'title' => '我的收藏'
            ],            
            [
                'url' =>  get_base_url().'pages/shopmall_third/myGroup',
                'icon' => cfg('site_url').'/static/images/mall/group.png',
                'title' => '我的拼团'
            ],            
            [
                'url' =>  get_base_url().'pages/my/my_order?state=0&type=mall',
                'icon' => cfg('site_url').'/static/images/mall/order.png',
                'title' => '我的订单'
            ],
        ];

        $configData = (new ConfigDataService())->getConfigData();
        // 小程序流量广告
		if($configData['wxapp_adver_mall_index_status'] == 1 && $configData['wxapp_adver_mall_index_unit_id']){
			$return['wxapp_adver'] = [
				'type' => 'banner',
				'id' => $configData['wxapp_adver_mall_index_unit_id'],
			];
		}


		//商城首页访问记录
        $now_user_id = request()->log_uid ? request()->log_uid : 0;
        $mallBrowseIndex = new MallBrowseNewService();
        $mallBrowseIndex->insertRecord($now_user_id);


        return api_output(0, $return);
    }
    /**
     * 导入抄表
     * @return \json
     */
    public function loadExcel()
    {
        $file = $this->request->file('file');
        $filed = [
            'A' => 'card_number',
            'B' => 'name',
            'C' => 'identity',
            'D' => 'department',
            'E' => 'phone',
        ];
        $data = $this->readFile($file, $filed, 'Xls');
    }
    /**
     * @author 朱梦群
     * 商城首页推荐模块关联商品
     */
    public function getRelatedGoods()
    {
        $status = $this->request->param('status', 1, 'intval');
        $now_city = $this->request->param('now_city', 0, 'intval');
        $nowArea = $this->request->param('now_area', 0, 'intval');
        $id = $this->request->param('id', '', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $mall_lat = $this->request->param('mall_lat','','trim,string');
        $mall_lng = $this->request->param('mall_lng','','trim,string');
        $Homeservice = new MallHomeDecorateService();
        $uid =$this->request->log_uid??0;
        //设置商品区域展示
        $addrs=(new UserService())->userFind('province_id,city_id,area_id',['uid'=>$uid]);
        if(!empty($now_city)){
            $arr1['province_id']='';
            $arr1['city_id']=$now_city;
            $arr1['area_id']= $nowArea ?: '';
        }else{
            $addrs=$addrs->toArray();
            $arr1['province_id']=$addrs['province_id'];
            $arr1['city_id']=$addrs['city_id'];
            $arr1['area_id']=$addrs['area_id'];
        }
        $store_ids = [];
        if(cfg('open_mall_order_area')==2&&$mall_lat&&$mall_lng){
            $store_ids = (new MallDeliveryService())->getRangeStoreIds($mall_lng,$mall_lat);
        }
        try {
            if ($status == 1) {
                    $MallBrowseService = new MallBrowseService();
                    if($uid){
                        $condition = [
                            ['uid', '=', $uid],
                        ];
                    }else{
                        $condition=[];
                    }
                    $return = $MallBrowseService->getMyList($condition, $page);
                    $MallGoodsService = new MallGoodsService();
                    $arr = $MallGoodsService->recommendMySetGoodsList($return, $page,[],$uid,$id,$arr1,$store_ids);
                    if(!empty($arr['list'])) {//展示商品级活动标识
                        foreach ($arr['list'] as $key => &$val) {
                            if(!empty($val['images'])){
                                $val['images'] = explode(';', str_replace(',',';',$val['images']));
                                foreach ($val['images'] as &$img){
                                    $img = replace_file_domain($img);
                                }
                            }
                            $val['like_image'] = !empty($val['like_image']) ? replace_file_domain($val['like_image']) : '';
                            $act_id = 0;
                            $condition3 = [//条件
                                ['s.store_id', '=', $val['store_id']],
                                ['m.goods_id', '=', $val['goods_id']],
                                ['s.status', '<>', 2],
                                ['s.start_time', '<', time()],
                                ['s.end_time', '>=', time()],
                                ['s.is_del', '=', 0],
                                ['s.type', 'in', ['bargain', 'group', 'limited', 'prepare', 'periodic']],
                            ];
                            $ret = (new MallActivity())->getActByGoodsID($condition3, $field = '*');
                            if (empty($ret)) {//周期购不能时间判断
                                $arr['list'][$key]['activity_type'] = 'normal';
                                $condition4 = [//条件
                                    ['s.store_id', '=', $val['store_id']],
                                    ['m.goods_id', '=', $val['goods_id']],
                                    ['s.status', '=', 1],
                                    ['s.type', '=', 'periodic'],
                                    ['s.is_del', '=', 0],
                                ];
                                $ret1 = (new MallActivity())->getActByGoodsID($condition4, $field = '*');
                                if (!empty($ret1)) {
                                    $act_id = $ret1['act_id'];
                                    $arr['list'][$key]['activity_type'] = $ret1['type'];

                                    $where3 = [['s.id', '=', $act_id], ['m.goods_id', '=', $val['goods_id']]];
                                    $li = (new MallNewPeriodicPurchase())->getGoodsAndPeriodic($where3, $field = "s.periodic_count,m.min_price");
                                    $arr['list'][$key]['act_price'] = $li['periodic_count'] * $li['min_price'];
                                }
                            }
                            else {
                                $act_id = $ret['act_id'];
                                $arr['list'][$key]['activity_type'] = $ret['type'];
                            }
                            $arr['list'][$key]['act_price'] = 0;
                            if ($arr['list'][$key]['activity_type'] != 'normal') {//活动商品给价格
                                if ($arr['list'][$key]['activity_type'] == 'bargain') {
                                    $where = [['act_id', '=', $act_id]];
                                    $arr1 = (new MallNewBargainSku())->getBySkuId($where, $field = "act_price");
                                    $arr['list'][$key]['act_price'] = get_format_number($arr1['act_price']);
                                } elseif ($arr['list'][$key]['activity_type'] == 'group') {
                                    $where = [['act_id', '=', $act_id]];
                                    $price = (new MallNewGroupSku())->getPice($where);
                                    $arr['list'][$key]['act_price'] = get_format_number($price);
                                } elseif ($arr['list'][$key]['activity_type'] == 'limited') {
                                    //未开始的活动不要活动标签
                                    $rets = (new MallLimitedAct())->getLimitedByActID($act_id, $val['goods_id']);
                                    if (!empty($rets) && $rets[0]['limited_status'] == 0) {
                                        $arr['list'][$key]['activity_type'] = 'normal';
                                    }
                                    $price = (new MallLimitedSku())->limitMinPrice($act_id, $val['goods_id']);
                                    $arr['list'][$key]['act_price'] = get_format_number($price);
                                    $arr['list'][$key]['stock_num'] = (new MallLimitedSku())->getLimitActSum($act_id,'act_stock_num');
                                } else {
                                    $price = (new MallPrepareActSku())->prepareMinPrice($act_id, $val['goods_id']);
                                    $arr['list'][$key]['act_price'] = get_format_number($price);
                                }
                            }
                            //积分展示
                            $val['score_text'] = '';
                            if(customization('goods_show_score') && $scorePrice = floatval($val['min_price'])){
                                $scorePercent = PercentRateService::scorePercent('mall', $val['goods_id'], $val['mer_id']);
                                $score = bcmul($scorePrice, $scorePercent, 2);
                                if(cfg('score_round_two')){
                                    $score = sprintf('%.2f',floatval($score));
                                }else{
                                    $score = round($score);
                                }
                                floatval($score) > 0 && $val['score_text'] = cfg('score_name').'+'. $score;
                            }
                        }
                        $arr['list'][$key]['old_age_pension'] = cfg('open_yanglao_and_sande') == 1 ? '消费100可得养老金' . cfg('Currency_symbol') . (new PercentRateService)->getOldAgePension($val['mer_id'], 'mall', $val['min_price']) : '';
                    }
            } elseif ($status == 2) {
                $arr = $Homeservice->getRelatedGoods($id, $page, $pageSize,$uid,$arr1,$store_ids);
            }else{
                throw new \think\Exception('status参数错误');
            }
            if($arr){
                foreach ($arr['list'] as &$item){
                    //消费一元获得积分
                    $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($item['price'],'group',($item['goods_type']=='sku'?true:false),$item['score_percent']);
                    $item['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                    $item['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
                }
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * [recommend_category_goods_list 首页推荐分类下的商品列表]
     * @Author   JJC
     * @DateTime 2020-05-28T09:20:52+0800
     * @return   [type]                   [description]
     */
    public function recommend_category_goods_list($page, $recommend_id,$arr1,$store_ids=[])
    {
        $MallRecommendCateService = new MallRecommendCateService();
        $recommend_info = $MallRecommendCateService->getOne($recommend_id);
        if (empty($recommend_info['bind_arr'])) {
            return api_output(1003, '', '抱歉该推荐分类下未绑定对应商品！');
        }
        $condition['cate_second'] = $recommend_info['bind_arr'];//二级商品分类id
        $MallGoodsService = new MallGoodsService();
        $return = $MallGoodsService->getList($condition, '', $page,0,$arr1,$store_ids);
        return $return;
    }

    /**
     * [recommend_goods_list 猜你喜欢接口]
     * @Author   JJC
     * @DateTime 2020-06-15T15:16:48+0800
     * @return   [type]                   [description]
     */
    public function recommend_goods_list()
    {
        $recommend_id = $this->request->param("recommend_id", "", "intval");//二级分类id
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        $from = $this->request->param('from','','trim');
        $mall_lat = $this->request->param('mall_lat','','trim,string');
        $mall_lng = $this->request->param('mall_lng','','trim,string');
        $uid =$this->request->log_uid??0;
        $now_city = $this->request->param('now_city', 0, 'intval');
        
        //个人中心猜你喜欢
        if ($from == 'mall_my_center' && !cfg('mall_center_recommend')) {
            return api_output(1000, ['list' => []]);
        }
        //商品详情页
        if ($from == 'commodity_details' && !cfg('goods_detail_recommend')) {
            return api_output(1000, ['list' => []]);
        }
        //购物车页面
        if ($from == 'shopping_cart' && !cfg('cart_recommend')) {
            return api_output(1000, ['list' => []]);
        }

        if (empty($recommend_id) && $recommend_id != 0) {
            return api_output(1003, '', 'recommend_id必须！');
        } else {
            $addrs=(new UserService())->userFind('province_id,city_id,area_id',['uid'=>$uid]);
            if(!empty($now_city)){
                $arr1['province_id']='';
                $arr1['city_id']=$now_city;
                $arr1['area_id']='';
            }else{
                $arr1['province_id']=$addrs['province_id'] ?? 0;
                $arr1['city_id']=$addrs['city_id'] ?? 0;
                $arr1['area_id']=$addrs['area_id'] ?? 0;
            }
            $store_ids = [];
            if(cfg('open_mall_order_area')==2&&$mall_lat&&$mall_lng){
                $arr1 = [];
                $store_ids = (new MallDeliveryService())->getRangeStoreIds($mall_lng,$mall_lat);
            }
            if ($recommend_id == 0) {
                //猜你喜欢
                $MallBrowseService = new MallBrowseService();
                $condition = [
                    ['uid', '=', $uid],
                ];
                $return1 = $MallBrowseService->getMyList($condition, $page);
                $MallGoodsService = new MallGoodsService();
                $return = $MallGoodsService->recommendGoodsList($return1, $page,[],$uid,$arr1,$store_ids);
                if(!empty($return)){//展示商品级活动标识
                    foreach ($return['list'] as $key=>$val){
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
                            $return['list'][$key]['activity_type']='normal';
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
                                $return['list'][$key]['activity_type']=$ret1['type'];
                                $where3=[['s.id','=',$act_id],['m.goods_id','=',$val['goods_id']]];
                                $li=(new MallNewPeriodicPurchase())->getGoodsAndPeriodic($where3,$field="s.periodic_count,m.min_price");
                                $return['list'][$key]['act_price']=$li['periodic_count']*$li['min_price'];
                            }
                        }else{
                            $act_id=$ret['act_id'];
                            $return['list'][$key]['activity_type']=$ret['type'];
                        }
                        $return['list'][$key]['act_price']="";
                        if($return['list'][$key]['activity_type'] !='normal'){//活动商品给价格
                            switch ($return['list'][$key]['activity_type']){
                                case 'bargain':
                                    $where=[['act_id','=',$act_id]];
                                    $arr=(new MallNewBargainSku())->getBySkuId($where,$field="act_price");
                                    $return['list'][$key]['act_price']=get_format_number($arr['act_price']);
                                    break;
                                case 'group':
                                    $where=[['act_id','=',$act_id]];
                                    $price=(new MallNewGroupSku())->getPice($where);
                                    $return['list'][$key]['act_price']=get_format_number($price);
                                    break;
                                case 'limited':
                                    $price=(new MallLimitedSku())->limitMinPrice($act_id,$val['goods_id']);
                                    $return['list'][$key]['act_price']=get_format_number($price);
                                    break;
                                case 'prepare':
                                    $price=(new MallPrepareActSku())->prepareMinPrice($act_id,$val['goods_id']);
                                    $return['record_list'][$key]['act_price']=get_format_number($price);
                                    break;
                                default:
                                    break;
                            }

                        }
                        //消费一元获得积分
                        $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
                        $return['list'][$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                        $return['list'][$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
                    }
                }
            } else {
                //同排二级菜单
                $return = $this->recommend_category_goods_list($page, $recommend_id,$arr1,$store_ids);
            }
        }
        return api_output(1000, $return);
    }

    /**[搜索商品列表接口]
     * @return \json
     */
    public function search_goods_list()
    {
        $uid =$this->request->log_uid??0;
        $sort = $this->request->param("sort", "", "trim");//排序
		
        $keyword = $this->request->param("keyword", "", "trim");//搜索关键字
		//去除搜索词里的emoji表情
		$keyword = preg_replace_callback(    
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
			$keyword);
		
        $page = intval($this->request->param("page", "", "intval")) ? intval($this->request->param("page", "", "intval")) : 1;//分页
        $cat_first = $this->request->param("cat_first", "", "intval");//一级分类id
        $cat_second = $this->request->param("cat_second", "", "trim");//二级分类id
        $cat_three = $this->request->param("cat_three", "", "intval");//三级分类id
        $now_city = $this->request->param('now_city', 0, 'intval');
        $now_area = $this->request->param('now_area', 0, 'intval');
        Config::set(['page_size' =>  min(input('page_size', 10),20)],'api');
        //筛选sev_type
        $sev_type = $this->request->param("sev_type", "", "trim");//同城送达
        $delive_local = $this->request->param("is_location", "", "trim");//同城送达
        $join_activity = $this->request->param("join_activity", "", "intval");//参与商品级活动
        $free_shipping = $this->request->param("free_shipping", "", "intval");//是否包邮
        $is_new = $this->request->param("is_new", "", "intval");//是否新品
        $is_hot = $this->request->param("is_hot", "", "intval");//是否热门
        $spec_val_ids = $this->request->param("spec_val_ids", "", "trim");//二级分类的分类属性值id,多个以英文逗号分隔

        $store_id = $this->request->param("store_id", "", "intval");//
        $lat = $this->request->param("lat", "", "floatval");
        $lng = $this->request->param("lng", "", "floatval");
        $min_price = $this->request->param("min_price", "", "floatval");//搜索区间最低价
        $max_price = $this->request->param("max_price", "", "floatval");//搜索区间最高价
        $mall_lat = $this->request->param("mall_lat", "", "floatval");
        $mall_lng = $this->request->param("mall_lng", "", "floatval");
        $is_from_index_search = $this->request->param("is_from_index_search", 0, "intval"); //是否来源首页搜索
        $brand_id = $this->request->param("brand_id", '', "trim");
        $condition = [];//搜索条件
        $condition['is_del'] =0;
        if ($cat_first) {
            $condition['cate_first'] = $cat_first;
        }
        if ($cat_second) {
            $condition['cate_second'] = $cat_second;
        }
        if ($cat_three) {
            $condition['cate_three'] = $cat_three;
        }
        if ($spec_val_ids) {
            $condition['spec_val_ids'] = $spec_val_ids;
        }

        if ($store_id) {
            $condition['store_id'] = $store_id;
        }
        if ($join_activity) {
            $condition['join_activity'] = $join_activity;
        }
        if ($free_shipping) {
            $condition['free_shipping'] = $free_shipping;
        }
        if ($delive_local) {//同城送达
            $condition['is_location'] = $delive_local;
        }
        /*if ($delive_local>0) {//同城送达
            $condition['is_location'] = $delive_local;
        }*/
        if ($lat && $lng) {
            $condition['lat'] = $lat;
            $condition['lng'] = $lng;
        }
        //筛选服务折扣判断
       /* if (!empty($sev_type)) {
            if ($sev_type == "is_location") {
                $condition['is_location'] = 0;
            } else if ($sev_type == "free_shipping") {
                $condition['free_shipping'] = $free_shipping;
            } else if ($sev_type == "is_new") {
                $sort = "new_good";
                $condition['new_good'] = "new_good";
            } else if ($sev_type == "join_activity") {
                $condition['join_activity'] = $sev_type;
            } else if ($sev_type == "is_hot") { //热卖
                $sort = $sev_type;
                $condition['is_hot'] = "is_hot";
            }
        }*/
        if ($is_new) {
            $condition['is_new'] = $is_new;
        }
        if ($is_hot) {
            $condition['is_hot'] = $is_hot;
        }
        if ($min_price) {
            $condition['min_price'] = $min_price;
        }
        if ($max_price) {
            $condition['max_price'] = $max_price;
        }
        if ($sort == "new_good") {
            $condition['new_good'] = $sort;
        }
        if ($keyword) {
			$condition['keyword'] = $keyword;
            //记录下搜索字段
            $this->add_search_Log($keyword); 
        }
        if(!empty($brand_id)){
            $condition['brand_id_str'] = $brand_id;
        }
        $condition['is_from_index_search'] = $is_from_index_search;
       
        $MallGoodsService = new MallGoodsService();

        $addrs=(new UserService())->userFind('province_id,city_id,area_id',['uid'=>$uid]);
        if(!empty($now_city)){
            $arr1['province_id']='';
            $arr1['city_id']=$now_city;
            $arr1['area_id']=$now_area?:'';
        }else{
            $addrs=$addrs->toArray();
            $arr1['province_id']=$addrs['province_id'];
            $arr1['city_id']=$addrs['city_id'];
            $arr1['area_id']=$addrs['area_id'];
        }
        $store_ids = [];
        if(cfg('open_mall_order_area')==2&&$mall_lng&&$mall_lat){
            $store_ids = (new MallDeliveryService())->getRangeStoreIds($mall_lng,$mall_lat);
        }
        try{

        $return = $MallGoodsService->getList($condition, $sort, $page,$uid,$search=1,$arr1,$store_ids);
        if($return['list'] && !is_array($return['list'])){
            $return['list'] = $return['list']->toArray();
        }
        $return['list'] = array_map(function($r){
            $r['images'] = $r['image'];
            return $r;
        },$return['list']);

        //page=1且是商城首页搜索增加品牌返回，根据商品名模糊搜索获取品牌名称
        if ($is_from_index_search != 1 && $page == 1 && $keyword) {
            $return['brands'] = (new \app\mall\model\db\MallProductBrands())->alias('b')
                ->join('mall_goods g', 'b.id = g.brand_id')
                ->where('g.name', 'like', '%' . $keyword . '%')
                ->where('g.status', 1)
                ->where('g.brand_id', '>', 0)
                ->where('b.status', 1)
                ->field(Db::raw('distinct(brand_id),b.name,b.logo'))
                ->order('b.sort desc')
                ->select()
                ->each(function ($item) {
                    $item['logo'] = replace_file_domain($item['logo']);
                    return $item;
                })
                ->toArray();
        }

        return api_output(0, $return);
        }catch(\Exception $e){
             throw_exception($e->getMessage());
        }
    }

    //搜索店铺列表接口
    public function search_store_list()
    {
        try{
        $keyword = $this->request->param("keyword", "", "trim");//搜索关键字
        $keyword = preg_replace_callback(
                '/./u',
                function (array $match) {
                    return strlen($match[0]) >= 4 ? '' : $match[0];
                },
        $keyword);
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        if (empty($keyword)) {
            return api_output(1003, '', '关键词必须！');
        }
        $where['keyword'] = $keyword;
        $now_city = $this->request->param('now_city', 0, 'intval');
        $uid =$this->request->log_uid??0;
        $addrs=(new UserService())->userFind('province_id,city_id,area_id',['uid'=>$uid]);
        if(!empty($now_city)){
            $arr1['province_id']='';
            $arr1['city_id']=$now_city;
            $arr1['area_id']='';
        }else{
            $addrs=$addrs->toArray();
            $arr1['province_id']=$addrs['province_id'];
            $arr1['city_id']=$addrs['city_id'];
            $arr1['area_id']=$addrs['area_id'];
        }
        $MallGoodsService = new MallGoodsService();
        $return = $MallGoodsService->getListGroupStore($where, $page,$arr1);
        return api_output(0, $return);
        }catch (\Exception $e){
             throw_exception($e->getMessage());
        }
    }

    //字段联想接口
    public function words_associate()
    {
        $keyword = $this->request->param("keyword", "", "trim");//搜索关键字
        if (empty($keyword)) {
            return api_output(1003, '', '关键词必须！');
        }
        try {
            // //先从搜索记录里面去查，没有再从商品表中查询
            // $MallSearchLogService = new MallSearchLogService();
            // $return = $MallSearchLogService->getContent($keyword);
            // //当联想查询为空
            // if ($return) {
            //     //当联想查询不为空插入搜索记录
            //     $this->add_search_Log($keyword);
            // }
            $goodNames = (new \app\mall\model\db\MallGoods())
                ->where('name', 'like', $keyword . '%')
                ->where('status', '=', 1)
                ->group('name')
                ->order(\think\facade\Db::raw('LENGTH(`name`) ASC'))
                ->limit(10)
                ->column('name');

            $storeNames = (new \app\merchant\model\db\MerchantStore())
                ->where('name', 'like', $keyword . '%')->where('status', '=', 1)
                ->group('name')
                ->order(\think\facade\Db::raw('LENGTH(`name`) ASC'))
                ->limit(5)
                ->column('name');
            $return = array_merge($storeNames,$goodNames);

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**[插入搜索记录]
     * @param $keyword
     * @return int|string
     */
    public function add_search_Log($keyword)
    {
        $uid =$this->request->log_uid;
        //是否是中文,是就转个拼音出来
        $MallSearchLogService = new MallSearchLogService();
        $common = new Pinyin();
        $isPinyin = $common->isChinese($keyword);
        //记录下搜索字段
        $data = [
            'content' => $keyword,
            'content_assoicate' => $keyword,
            'content_transcription' => $isPinyin,
            'create_time' => time(),
            'uid' => $uid ?? 0
        ];
        return $MallSearchLogService->insertData2($data);
    }

    /**
     * 分类列表
     * update 朱梦群 2020/10/15
     * @return \json
     * @throws \think\Exception
     */
    public function category_list()
    {
        $type = $this->request->param('type', 2, 'intval');
        $MallCategoryService = new MallCategoryService();
        $return['result'] = $MallCategoryService->getNormalList('dealTree', $type);
        return api_output(0, $return);
    }

    //搜索发现，记录

    /**
     * [搜索发现接口，包含点击返回查询的商品列表]
     * @return \json
     */
    public function search_find()
    {
        $MallSearchFindModel = new MallSearchFind();
        $keyword = $this->request->param("keyword", "", "trim");//搜索关键字
        $page = intval($this->request->param("page", "", "intval")) ? intval($this->request->param("page", "", "intval")) : 1;//分页
        try {
            if ($keyword != "") {
                //返回商品列表
                $condition['keyword'] = $keyword;
                $MallGoodsService = new MallGoodsService();
                $return = $MallGoodsService->getList($condition, '', $page);
                //记录下搜索字段
                $this->add_search_Log($keyword);
            } else {
                //没有关键词则返回搜索发现关键词列表
                $return['keyword_list'] = (new SearchHotMallNewService())->getSearchFind();
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(1000, $return);
    }

    //筛选
    public function sift_con()
    {
        $keyword = $this->request->param("keyword", "", "trim");//搜索关键字
        if (empty($keyword)) {
            //1001 必填参数缺失错误
            $return = [];
            $msg = "搜索关键字缺失";
            return api_output(1001, $return, $msg);
        }
        try {
            //地址
            $uid =$this->request->log_uid;
            $UserAddress = new UserAdressService();
            $uis = $uid;
            $return['address'] = $UserAddress->getAdressByUid($uis, 1);

            //服务折扣，写死，点击分条件查询
            $return['service_list'] = [
                ["sev_id" => 1, "sev_type" => "is_location", "sev_name" => "同城速达"],
                ["sev_id" => 2, "sev_type" => "free_shipping", "sev_name" => "包邮"],
                ["sev_id" => 3, "sev_type" => "is_new", "sev_name" => "新品"],
                ["sev_id" => 4, "sev_type" => "join_activity", "sev_name" => "活动"],
                ["sev_id" => 5, "sev_type" => "is_hot", "sev_name" => "热卖"]
            ];
            //全部分类，通过关键词查询，pigcms_mall_category
            $MallGood = new MallGoodsService();
            $condition['keyword'] = $keyword;
            $return['category_list'] = $MallGood->getLevelTwoId($condition);
            //筛选确认查出商品列表在 search_goods_list()
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(1000, $return);
    }

    //筛选，点击二级分类，显示平台设定的二级分类参数和参数值
    public function getMallCategorySpec()
    {
        $keyword = $this->request->param("cat_id", "", "trim");//二级分类id
        if (empty($keyword)) {
            //1001 必填参数缺失错误
            $return = [];
            $msg = "二级分类id缺失";
            return api_output(1001, $return, $msg);
        }
        try {
            //获取二级分类对应的二级分类属性
            $MallCategorySpec = new MallCategorySpecService();
            $return = $MallCategorySpec->getCategorySpecBySecondId($keyword);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(1000, $return);
    }

    public function search_category_list()
    {
        //所有的商品分类属性值
        $MallCategoryService = new MallCategoryService();
        $list = $MallCategoryService->getLevelDetail();
        return api_output(1000, $list);
    }

    //返回地址名称
    public function getMallAdress()
    {
        $address_id = $this->request->param("address_id", "", "trim");//地址id
        //传递到地址service
        $address['adress'] = "";
        try {
            if (!empty($address_id)) {
                $UserAddress = new UserAdressService();
                $address['adress'] = $UserAddress->getAdressByAdressid($address_id);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(1000, $address);
    }

    //返回地址列表
    public function getMallAdressList()
    {
        //地址
        $UserAddress = new UserAdressService();
        $uis = $this->request->log_uid;
        $return = $UserAddress->getAdressByUid($uis, 0);
        return api_output(1000, $return);
    }

    /*-----------获取浏览记录----------------*/
    //浏览记录
    public function browse_records()
    {
        $page = intval($this->request->param("page", "", "intval")) ? intval($this->request->param("page", "", "intval")) : 1;//分页
        $MallBrowseService = new MallBrowseService();
        $uid =$this->request->log_uid;
        $condition = [
            ['uid', '=', $uid],
        ];

        $return = $MallBrowseService->getList($condition, $page);

        return api_output(1000, $return);
    }

    //删除浏览记录
    public function del_records()
    {
        $ids = $this->request->param("ids");
        $is_del_type = $this->request->param("is_del_type");//0不是全部删除，1是全部删除
        if (empty($ids)) {
            return api_output(1003, ['result' => false], 'id必须！');
        }
        if($is_del_type==0){
            $ids = explode(',', $ids);
            $where = [
                ['id', 'in', $ids],
            ];
        }else{
            $where = [['id','>',0]];
        }
        $data = ['is_del' => 1];
        $MallBrowseService = new MallBrowseService();

        $result = $MallBrowseService->updateData($where, $data);
        if ($result) {
            return api_output(1000, ['result' => true], '删除成功！');
        } else {
            return api_output(1003, ['result' => false], '删除失败！');
        }
    }

//商品详情
    public function goods_info()
    {
        $goods_id = $this->request->param("goods_id", "", "intval");
        //$goods_id=1;
        if (empty($goods_id)) {
            return api_output(1003, '', 'goods_id必须');
        }
        try {
            //商品详情
            $MallGoodsService = new MallGoodsService();
            $info = $MallGoodsService->getOne($goods_id);
            /*---------------------商品顶部图片缩略图--------------------------------*/
            if (!empty($info['images'])) {
                //判断 是否是多图，逗号隔开的
                if (strpos($info['images'], ",")) {
                    $arr = explode(",", $info['images']);
                    foreach ($arr as $key => $val) {
                        $info['img_list'][$key]['img_addr'] = thumb($val, 750, 750, $resize = '');
                    }
                } else {
                    $info['img_list']['img_addr'] = thumb($info['images'], 750, 750, $resize = '');
                }
            }


            //是否有规格及规格状态
            $MallGoodsSpecService = new MallGoodsSpecService();
            $spec_num = $MallGoodsSpecService->getSpecStatus($goods_id);
            if ($spec_num) {
                //有规格
                $info['spec_status'] = 1;
            } else {
                //无规格
                $info['spec_status'] = 0;
            }
            //获取店铺信息
            $MallStoreService = new MallStoreService();
            $info['store'] = $MallStoreService->getOne($info['store_id']);
            //客服地址：
            $uid =$this->request->log_uid;
            $info['customer_url'] = $MallGoodsService->serviceCustomer($uid, $info['store_id']);
            //优惠券列表
            $MerchantCouponService = new MerchantCouponService();
            //商户id
            $mer_id = 0;
            //业务信息
            $order_info = [
                'can_coupon_money' => 1,
                'store_id' => $info['store_id'],
                'platform' => 'weixin',//当前环境 如：wap/app/weixin
            ];
            $info['reduction'] = $MerchantCouponService->getAvailableCoupon($this->_uid, $mer_id, $order_info);

            //优惠和活动
            //$info['activity_list'] = $MallGoodsService->getGoodActivity($goods_id, $info['store_id']);
            $info['activity_list'] =[];
                //服务保障
            $info['safe_list'] = $info['service_desc'];
            //地址
            $UserAddress = new UserAdressService();
            $uis = $this->_uid;
            $default = 1;
            $arr = $UserAddress->getAdressByUid($uis, $default);
            $info['address'] = $arr[0]['name'];
            //平台会员卡
            /*------------暂无-----------------*/
            //商家会员卡信息
            $MerchantCardService = new MerchantCardService();
            $mer_id = 1;//商户id
            $info['mer_car'] = $MerchantCardService->getUserCard($this->_uid, $mer_id);
            /*--------------评论----------------*/
            $list = (new MallGoodReply())->getReplyList($goods_id);
            $comment['comments'] = $list['comments'];
            $comment['per'] = $list['per'];
            $comment['comments_two'] = $list['comments_two'];
            $info['comment_list'] = $comment;
            /*------保存浏览记录------------*/
            (new MallBrowseService())->insertRecord($this->_uid, $goods_id, $info['cate_second']);
            /*图文详情goods_desc富文本*/

            /*规格详情spec_desc*/
            if (!empty($info['spec_desc'])) {
                //判断 是否是多规格逗号隔开的
                if (strpos($info['spec_desc'], "|")) {
                    $spec_desc = explode("|", $info['spec_desc']);
                    foreach ($spec_desc as $key => $val) {
                        $info['spec_desc_list'][$key]['spec_desc'] = $val;
                    }
                } else {
                    $info['spec_desc_list']['spec_desc'] = $val;
                }
            }
            /*包装售后pack_desc*/

            /*猜你喜欢recommend_goods_list,前端传入商品的cate_second*/
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $info);
    }

    //展开sku列表
    public function get_sku()
    {
        $goods_id = $this->request->param("goods_id", "", "intval");
        if (empty($goods_id)) {
            return api_output(1003, '', 'goods_id必须');
        }
        $spec_info = $this->request->param("spec_info", "", "trim");//传过来的规格值拼接
        $spec_info1 = $this->request->param("spec_info_all", "", "trim");//传过来的规格值拼接集合
        try {
            if (strpos($spec_info1, '|')) {
                $spec_num1 = explode("|", $spec_info1);
                $spec_num = count($spec_num1);
            } else {
                $spec_num = 1;
            }
            $MallGoodsSpecService = new MallGoodsSpecService();
            $spec_num1 = $MallGoodsSpecService->getSpecStatus($goods_id);
            $MallGoodsSpecService = new MallGoodsSpecService();
            //获取商品库商品信息
            $MallGoodsService = new MallGoodsService();
            $info1 = $MallGoodsService->getOne($goods_id);
            //条件足够满足
            if ($spec_num == $spec_num1) {
                $spec_list1 = $MallGoodsSpecService->getList2($goods_id, $spec_info, $spec_info1, $spec_num);
                //是否可支付，是否满足全条件 1满足
                $info['pay_status'] = 1;
                $info['goods_info']['sku_id'] = $spec_list1[1]['sku_id'];//具体sku商品的规格id
                $info['goods_info']['sku_info'] = $spec_list1[1]['sku_info'];//具体sku商品的规格info
                $info['goods_info']['show_status'] = 1;//起始值选择显示这个
                $info['goods_info']['image'] = $spec_list1[1]['image'];
                $info['goods_info']['price'] = $spec_list1[1]['price'];
                $info['goods_info']['min_price'] = '';
                $info['goods_info']['max_price'] = '';
                $info['goods_info']['notes'] = $info1['notes'];//商家特别备注
                $info['goods_info']['sku_str'] = $spec_list1[1]['sku_str'];
                $info['goods_info']['stock_num'] = $spec_list1[1]['stock_num'];
                $spec_list = $spec_list1[0];
            } else {
                //获取商品
                $info['goods_info']['sku_id'] = '';
                $info['goods_info']['sku_info'] = '';
                $info['goods_info']['show_status'] = 0;//起始值选择显示这个
                $info['goods_info']['image'] = $info1['image'];
                $info['goods_info']['price'] = '';
                $info['goods_info']['notes'] = $info1['notes'];//商家特别备注
                $info['goods_info']['min_price'] = $info1['min_price'];
                $info['goods_info']['max_price'] = $info1['max_price'];
                $info['goods_info']['stock_num'] = $info1['stock_num'];
                //获取商品规格属性
                $spec_list = $MallGoodsSpecService->getList1($goods_id, $spec_info, $spec_info1, $spec_num);
                $info['pay_status'] = 0;
            }
            $info['spec_list'] = $spec_list;
            //先判断用户之前是否已经对该商品上传了凭证信息
            $word_list = (new MallGoodUserWordService())->getWordList($this->_uid, $goods_id);
            if (empty($word_list)) {
                //获取商家设置的留言信息,在规格需要显示
                $info['word_list'] = (new MallGoodsWordsService())->getGoodsWordType($goods_id);
            } else {
                $info['word_list'] = $word_list;
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(1000, $info);
    }

    //获取sku详情
    public function get_sku_info()
    {
        $goods_id = $this->request->param("goods_id", "", "intval");
        $spec_info = $this->request->param("spec_info", "", "trim");//规格属性值详情，应该是 规格id:属性id 相对应的一个json串
        $spec_arr = json_decode($spec_info, true);
        $MallGoodsSkuService = new MallGoodsSkuService();
        $return = $MallGoodsSkuService->getSkuInfo($spec_arr, $goods_id);
        return api_output(1000, $return);
    }

    //门店商品推荐
    public function shop_recommend_good()
    {
        $goods_id = $this->request->param("goods_id", "", "intval");
        $page = $this->request->param("page", 1, "intval");
        //$goods_id=1;
        if (empty($goods_id)) {
            return api_output(1003, '', 'goods_id必须');
        }
        try {
            //商品详情
            $MallGoodsService = new MallGoodsService();
            $info = $MallGoodsService->getOne($goods_id);
            //店铺推荐商品列表
            $cate_id['store_id'] = $info['store_id'];
            $cate_id['status'] =1;
            //判断当前商品分类等级
            if ($info['cate_three'] != 0 && !empty($info['cate_three'])) {
                $cate_id['cate_three'] = $info['cate_three'];
            } else {
                $cate_id['cate_second'] = $info['cate_second'];
            }
            $info1 = (new MallGoods())->getRemShopGoodList($cate_id, $field = "*", $page);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $info1);
    }

    //降价通知保存信息
    public function add_price_notice()
    {
        $add['goods_id'] = $this->request->param("goods_id", "", "intval");
        $add['uid'] = request()->log_uid;
        $add['old_price'] = $this->request->param("old_price", "", "trim");
        $add['create_time'] = time();
        if (empty($add['goods_id']) || empty($add['old_price'])) {
            return api_output(1003, '', 'goods_id必须');
        }
        $result = (new MallPriceNotice())->save($add);
        $stauts = 1003;
        $msg = "抱歉，保存信息失败！";
        if ($result) {
            $stauts = 1000;
            $msg = "成功保存！";
        }
        return api_output($stauts, '', $msg);
    }

    //评价信息列表
    public function comments_list()
    {
        //商品id
        $goods_id = $this->request->param("goods_id", "", "intval");
        //评价页面要传一个页数过来,点击进入不需要传入页数，为空也行
        $page = $this->request->param("page", "", "intval");
        //获取评论类别，默认是0，全部评论
        $serch_type = $this->request->param("serch_type", 0, "intval");
        //规格规格值
        $sku_info = $this->request->param("sku_info", "", "trim");
        if (empty($goods_id)) {
            return api_output(1003, '', 'goods_id必须');
        }
        //查询评价列表
        $list = (new MallGoodReply())->getReplyLists($goods_id, $serch_type, $page, $sku_info);

        return api_output(1000, $list);
    }

    /**
     * sku_id sku商品表id
     *
     */
    /*新增评价*/
    public function add_comments()
    {
        /*点击评价传入规格id到get_sku获取信息*/
        //order_id，order_detail_id，goods_id,store_id,sku_id,sku_info,image_status,comment,score,goods_sku，goods_sku_dec，reply_pic，reply_mv
        $uid =$this->request->log_uid;
        $data['uid'] = $uid;
        $data['order_id'] = $this->request->param("order_id", "", "intval");
        $data['order_detail_id'] = $this->request->param("order_detail_id", "", "intval");
        $data['goods_id'] = $this->request->param("goods_id", "", "intval");
        $data['store_id'] = $this->request->param("store_id", "", "intval");
        $data['sku_id'] = $this->request->param("sku_id", "", "intval");
        $data['sku_info'] = $this->request->param("sku_info", "", "intval");
        $data['image_status'] = $this->request->param("image_status", "", "intval");
        $data['comment'] = $this->request->param("comment", "", "intval");
        $data['score'] = $this->request->param("score", "", "intval");
        $data['goods_sku'] = $this->request->param("goods_sku", "", "intval");
        $data['goods_sku_dec'] = $this->request->param("goods_sku_dec", "", "intval");
        $data['reply_pic'] = $this->request->param("reply_pic", "", "intval");
        $data['reply_mv'] = $this->request->param("reply_mv", "", "intval");
        $data['create_time'] = time();
        /*缺一个用户信息*/
        $result = (new MallGoodReply())->addComment($data);
        return api_output(1000, $result);
    }


    /*图片视频上传*/
    public function upload()
    {
        if (request()->isPost()) {
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('imgFile');//根据表单name替换imgFile
            try {
                $dir_name = request()->param('dir');//获取上传类型,如图片写image或者视频写video

                //分类定义允许上传的文件
                $ext_arr = array(
                    'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
                    'flash' => array('flv', 'swf'),
                    'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb', 'mp4'),
                    'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
                );
                if (empty($ext_arr[$dir_name])) {
                    return false;
                } else {
                    $allowext = implode(',', $ext_arr[$dir_name]);
                }
                // 使用验证器验证上传的文件
                validate(['file' => [
                    // 限制文件大小(单位b)，这里限制为4M
                    'fileSize' => 4 * 1024 * 1024,
                    // 限制文件后缀，多个后缀以英文逗号分割
                    'fileExt' => $allowext,
                ]])->check(['file' => $file]);
                // 上传到本地服务器
                $savename = \think\facade\Filesystem::disk('public')->putFile($dir_name, $file, 'sha1');

                if ($savename) {
                    // 拼接路径
                    $path = \think\Facade\Filesystem::getDiskConfig('public', 'url') . str_replace('\\', '/', '/' . $savename);
                    return json(['code' => 1000, 'msg' => '图片上传成功', 'files_name' => $path]);
                    /*$data['filepath']       = $path;
                    $data['filename']       = $file->getOriginalName();
                    $data['fileext']        = $file->extension();
                    $data['authcode']       = $file->hash('sha1');
                    $data['status']         = 1;
                    $data['filesize']       = $file->getSize();
                    $data['downloads']      = 0;
                    $data['uploadtime']     = time();
                    $data['uploadip']       = request()->ip();*/

                    /* if(in_array($data['fileext'], $ext_arr['image'])){
                         $data['isimage'] = 1;
                     }else{
                         $data['isimage'] = 0;
                     }

                     $res=Db::name('attachment')->order('aid', 'desc')->insert($data);
                     if($res){
                         return json(['error'=>0, 'url'=>$path, 'message'=>'添加成功']);
                     }else{
                         return json(['error'=>1, 'url'=>'', 'message'=>'添加失败']);
                     }*/
                }
            } catch (think\exception\ValidateException $e) {
                return json(['error' => "上传失败", 'url' => '', 'message' => $e->getMessage()]);
            }
        }
    }

    //sku商品表详情
    public function get_goods_sku_detail()
    {
        //规格id
        $sku_id = $this->request->param("sku_id", "", "intval");
        $MallGoodsSku = new MallGoodsSkuService();
        $result = $MallGoodsSku->getSkuById($sku_id);
        return api_output(1000, $result);
    }

    //加入购物车接口

    /**
     * [add_cart 加入购物车]
     * @Author   mrdeng
     * @DateTime 2020-7-10
     * @return \json
     */
    public function add_cart()
    {
        /*缺一个备注，另外设置一张表：备注类型输入 身份证，凭证*/
        $sku_id = $this->request->param("sku_id", "", "intval");
        $num = $this->request->param("num", "", "intval") ? $this->request->param("num", "", "intval") : 1;
        $MallGoodsSkuService = new MallGoodsSkuService();
        $skuInfo = $MallGoodsSkuService->getSkuById($sku_id);
        if (empty($skuInfo)) {
            return api_output(1003, '', '抱歉，商品信息不存在！');
        }
        try {
            $MallCartNewService = new MallCartNewService();
            $uid =$this->request->log_uid;
            $return = $MallCartNewService->addCart($skuInfo, $uid, $num);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        if ($return) {
            return api_output(1000, ['result' => true], '操作成功');
        } else {
            return api_output(1003, ['result' => false], '抱歉，操作失败');
        }

    }

    /**
     * [my_cart 我的购物车]
     * @Author   JJC
     * @DateTime 2020-06-17T16:21:23+0800
     * @return   [type]                   [description]
     */
    public function my_cart()
    {
        $uid =$this->request->log_uid;
        if (empty($uid)) {
            return api_output(1003, ['result' => false], '抱歉，请先登录！');
        }
        try {
            $MallCartNewService = new MallCartNewService();
            $list = $MallCartNewService->cartList($uid);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(1000, $list);
    }

    //删除购物车
    public function del_cart()
    {
        $cart_ids = $this->request->param("cart_ids", "", "trim");//购物车id多个以英文逗号分隔
        if (empty($cart_ids)) {
            return api_output(1003, ['result' => false], '购物车id必须');
        }
        $id_arr = explode(',', $cart_ids);
        $MallCartNewService = new MallCartNewService();
        $return = $MallCartNewService->delCart($id_arr);
        if ($return) {
            return api_output(1000, ['result' => true], '删除成功！');
        } else {
            return api_output(1003, ['result' => false], '删除失败！');
        }

    }

    //商品收藏
    public function mall_good_collect()
    {
        /*---------------缺少参与的活动标签，拼团什么的------------------*/
        //要带上活动标签
        $goods_id = $this->request->param("goods_id", "", "intval");
        if (empty($goods_id)) {
            return api_output(1003, '', '抱歉，商品信息缺失！');
        }

        $goodInfo = (new MallGoods())->getOne($goods_id);
        if (empty($goodInfo)) {
            return api_output(1003, '', '抱歉，商品信息不存在！');
        }
        try {
            $uid =$this->request->log_uid;
            $MallUserCollectService = new MallUserCollectService();
            if(empty($uid)){
                return api_output(1002, '', '请登录！');
            }
            $return = $MallUserCollectService->addCollect($goods_id, $uid, $goodInfo['store_id']);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        if ($return) {
            return api_output(1000, $return, '操作成功');
        } else {
            return api_output(1003, ['result' => false], '抱歉，操作失败');
        }
    }


    /*---------------------------------------------------------- 个人中心开始-----------------------------------------------------*/
    //收货地址接口getAdressByUid

    /*---------------------------------------------------------- 个人中心结束-----------------------------------------------------*/

    //发票接口
    /**
     * 调接口开个人/企业发票
     * @param $e_config
     * @param $user_info
     * @param $post
     * @return mixed
     * @author mrdeng
     * @date_time 2020/07/07
     */
    public function addInvoice($e_config, $user_info, $post)
    {
        $config = $this->getConfig();
        $arr['identity'] = $config['house_e_invoice'];
        if ($post['head_up_type'] == 2) {
            $order['address'] = $post['address'];
            $order['account'] = $post['account_number'];
        }
        $order['buyername'] = $post['name'];
        $order['taxnum'] = $post['taxnum'];
        $order['phone'] = $post['receive_tel'];
        $order['order_no'] = $post['order_no'];
        $order['invoicedate'] = date('Y-m-d H:i:s', time());
        $order['clerk'] = 'name';
        $order['saletaxnum'] = $e_config['duty_paragraph'];
        $order['kptype'] = 1;
        foreach ($post['detail'] as $k => $v) {
            $order['detail'][$k]['goodsname'] = $v['name'];
            $order['detail'][$k]['hsbz'] = 0;
            $order['detail'][$k]['taxrate'] = 0.13;
            $order['detail'][$k]['spbm'] = 304080101;
            $order['detail'][$k]['fphxz'] = 0;
            $order['detail'][$k]['num'] = 1;
            $order['detail'][$k]['price'] = $v['price'];
        }
        $arr['order'] = $order;
        $res = $this->sendInvoice($arr, $config['house_e_invoice_url']);
        return $res;
    }


    /*获取身份认证信息*/
    public function getConfig()
    {
        $e_config = new ConfigService();
        $where['tab_id'] = 'mall_e_invoice';
        $config = $e_config->get_config_list($where, 'name,value');
        return $config;
    }

    //确认订单接口
    public function confirm_order()
    {
        $return = [
            'order_list' => [
                [
                    'store_id' => '111',
                    'store_name' => '华为自营店',
                    'goods_list' => [
                    ],
                    'deliver_type' => 1,
                    'store_discount' => ['coupoun_id' => '1', 'coupoun_desc' => '满5减1', 'discount_price' => 1],
                    'store_card' => ['card_id' => '1', 'discount_price' => 1],
                    'store_discount_total' => '2.99',
                    'store_total_price' => '99',
                ],
            ],
            'plat_discount' => ['coupoun_id' => 1, 'coupoun_desc' => '满5减10', 'discount_price' => '10'],
            'plat_card' => ['card_id' => 1, 'discount_price' => '10'],
            'goods_total_price' => 8999,
            'discount_total' => '50',
            'total_price' => '9999',
        ];

        return api_output(0, $return);
    }


    public function order_info()
    {
        $return = [
            'order_id' => '156455648897845',
            'order_time' => '15645564889',
            'deliver_type' => '配送方式',
            'status' => 1,//订单状态
            'note' => '订单备注',
            'store_name' => '华为自营店',
            'goods_list' => [[
                'goods_name' => '苹果',
                'sku_info' => '',
                'price' => '999',
                'give_goods' => [],
            ]],
            'goods_total_price' => '565',
            'fright_charge' => '56',
            'discount_total' => '15',
            'need_pay' => 12,
        ];
        return api_output(0, $return);
    }

    public function get_price_down()
    {

        return api_output(0, '', '成功！');
    }

    //砍价页面
    public function cut_act_info()
    {
        $return = [
            'banner' => [
                ['redirect_url' => '', 'image' => '']
            ],
            'my_cut_list' => [
                [
                    'goods_id' => '11',
                    'goods_image' => '',
                    'cut_time' => '15874',
                    'cut_price' => '25',
                    'now_price' => '11',
                    'cut_rate' => '50%'

                ],
                [
                    'goods_id' => '11',
                    'goods_image' => '',
                    'cut_time' => '15874',
                    'cut_price' => '25',
                    'now_price' => '11',
                    'cut_rate' => '50%'

                ],
            ],
        ];
        return api_output(0, $return);
    }

    public function cut_list()
    {
        $return = [
            [
                'goods_id' => '11',
                'goods_image' => '',
                'cut_time' => '15874',
                'cut_price' => '25',
                'now_price' => '11',
                'cut_rate' => '50%'

            ],
            [
                'goods_id' => '11',
                'goods_image' => '',
                'cut_time' => '15874',
                'cut_price' => '25',
                'now_price' => '11',
                'cut_rate' => '50%'

            ],
        ];
        return api_output(0, $return);
    }

    //拼团
    public function groupList()
    {
        //顶部广告图
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        $now_status = $this->request->param("now_status", "", "intval") ? $this->request->param("now_status", "", "intval") : 0;//0代表刚开始的2条数据，1是更多
        //在广告
        $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
        $arrs=array();
        $arr = $mallGoodsActivityBannerService->getBannerList($act_type=3);
        $uid =$this->request->log_uid;
        if(!empty($arr)){
            foreach ($arr as $k=>$v){
                $pic['url']=$v['url'];
                $pic['pic']=$v['image'];
                $pic['id']=$v['id'];
                $arrs[]=$pic;
            }
        }
        $return['banner'] = $arrs;
        $status = 0;
        if($now_status==0){
            $limit = 2;//返回两条数据
        }else{
            $limit = 0;
        }
        try {
            //$addr区分首页和我的列表
            $return['my_group_list'] = (new MallNewGroupActService())->getList($uid, $status, $page,$limit,$addr = 0);
            $return['group_list'] = (new MallNewGroupActService())->getRecGroupList($uid = 0, $status, $page,$limit1=0,$addr = 0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    //我的拼团
    public function myGroupList()
    {

        //传入参数status 0全部 1进行中 2已完成 3已过期
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        $status = $this->request->param("status", 0, "trim");
        try {
            $uid=$this->request->log_uid;
            if(empty($uid)){
                return api_output_error(1002, '当前接口需要登录');
            }
            $return = (new MallNewGroupActService())->getList($uid, $status, $page,$limit=0, $addr = 1);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    //拼团活动邀请好友展示页面
    public function pleaseFriend(){
        $teamid = $this->request->param("team_id", "", "intval");//团队id
        //$uid = $this->request->param("up_uid", "", "intval");//拼主id
        $uid =$this->request->log_uid;
        $orderid = $this->request->param("orderid", "", "intval");//订单
        $groupid = $this->request->param("groupid", "", "intval");//活动id
        try {
            $return = (new MallNewGroupActService())->group_detail($uid, $orderid, $groupid);
            $return['group_member_list'] = (new MallNewGroupActService())->getUserList($teamid, $uid, $orderid);
            $return['group_msg']['gap_peoples']=$return['groups']['complete_num']-count($return['group_member_list']);
            $return['group_msg']['gap_times']=$return['groups']['end_time']-time();
            $spec_info = (new MallNewGroupActService())->getOrderDetailOne($orderid);
            $return['group_spec'] = $spec_info['sku_info'];
        }catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    //好友打开连接
    public function friendOpenGroup()
    {
            $orderid = $this->request->param("orderid", "", "intval");//订单
            $tid=$this->request->param("tream_id", "", "intval");
            $up_uid=$this->request->param("up_uid", "", "intval");
            $act_id=$this->request->param("act_id", "", "intval");
            $uid=$this->request->log_uid;//用户id
            if(empty($uid)){
                return api_output_error(1002, "请登录");
            }
            //当前用户的是否有订单id
           /* if(empty($orderid)){*/
                $ret=(new MallNewGroupOrderService())->getOneOrder($tid,$uid);
                if(empty($ret)){
                    //没有就用空
                    //$ret1=(new MallNewGroupOrderService())->getOneOrder($tid,$up_uid);
                    $orderid1=$orderid;
                }else{
                    $orderid1=$orderid=$ret['order_id'];
                }
            /*}*/

            $arrs=(new MallNewGroupOrderService())->getOrderMsg($orderid);//获取拼团订单信息
            if(empty($arrs)){
                return api_output_error(1003, "获取不到订单信息");
            }else{
                /**
                 * 查看拼住订单状态
                 */
                $ret1=(new MallNewGroupOrderService())->getOneOrder($arrs['tid'],$arrs['user_id']);
                if(!empty($ret1)){
                    $order=(new MallOrderService())->getOne($ret1['order_id']);
                    $order1=(new MallOrderService())->getOne($orderid);//不是拼主也要判断当前团状态
                    /*'50' => '已取消',
                    '51' => '超时取消',
                    '52' => '用户取消',
                    '60' => '申请售后',
                    '70' => '已退款',*/
                    if($order['status']==50 || $order['status']==51 || $order['status']==70 || $order1['status']==50 || $order1['status']==51 || $order1['status']==70){
                        return api_output_error(1003, "该团已经失效");
                    }
                }
            }
            $return = (new MallNewGroupActService())->group_detail($arrs['user_id'], $orderid, $arrs['act_id'],$arrs['tid'],$uid);
            $return['group_web_status']=0;//参与拼团
            $return['is_team']=0;//默认未成团
            if($arrs['status']==1){//支付
                $ret=(new MallNewGroupTeamUser())->getUserActStatus($arrs['act_id'],$arrs['tid'],$uid);
                if(!empty($ret)){
                    $return['group_web_status']=1;//邀请好友
                }
            }

            $status=(new MallActivityService())->checkGroupTimeMag($arrs['act_id'],$arrs['tid']);//活动状态

            if(!$status){
                return api_output_error(1003, "数据异常");
            }

            $return['group_status']=$status;//活动的状态 0活动后台设置主活动失效  1团队超过时间，团队作废  2成团成功 3未成团可以参团 4满团但是有正在支付的用户 5分享之后打开已经满团

            $arr = (new MallNewGroupActService())->getMallGroupTeam($arrs['tid']);
            $return['need_mans'] = 0;//团队缺口
            if ($arr['status'] == 0) {
                $return['need_mans'] = $arr['complete_num'] - $arr['num'];
                $return['success_mans'] = $arr['num'];
            }else{
                $return['is_team']=1;//已成团
                $return['group_status']= 2;//已经成团
            }
            $return['order_id']=$orderid1;
            $get_status=(new MallNewGroupTeamService())->getOne($arrs['tid']);//团队状态及团队人数
            $return['pay_mans']=$get_status['pay_mans'];
            if($return['group_status']==3){
                if($get_status['status']==1){//未满团但是位置被沾满了
                    $return['group_status']=4;//满团但是有正在支付的用户
                    $return['group_web_status']=0;//参与拼团
                }
            }

            $return['activity_status']=0;//0默认用户可以参团,1用户已经参团，2主活动超时
            $is_user_in_act=(new MallNewGroupActService())->getUserActStatus($arrs['act_id'],$arrs['tid'],$uid);//判断用户有没有参团
            $return['is_your_team'] = 0;//默认不是自己的团
            if($is_user_in_act){
                   $return['activity_status'] = 1;//用户已经参团
            }else{
                if($return['group_status']==2){//用户没有参团但是团已满，给另一个状态提示
                   $return['group_web_status']=0;//邀请好友
                }
            }
            $is_your_team=(new MallNewGroupTeamService())->getIsInTeamStatus($uid,$arrs['tid']);
            if(!empty($is_your_team)){
                $return['is_your_team'] = 1;//是自己的团
            }

            if($return['group_status']==4 || $return['group_status']==3) {//满团但是有正在支付的用户
                if($return['is_your_team']==1){//是自己的团
                    $return['group_web_status']=1;//邀请参团
                }
            }
            $where=[['act_id','=',$arrs['act_id']],['start_time','<',time()],['end_time','>=',time()],['type','=','group']];
            $msg=(new MallNewGroupActService())->getActGroup($where);
            if(empty($msg)){
                $return['activity_status']=2;//主活动超时
            }elseif($msg['status']==2){
                $return['activity_status']=3;//主活动失效
            }

            $return['times'] = $this->timediff($return['groups']['end_time']);
            $return['team_id'] =$arrs['tid'];//团队id
            $group_order=(new MallNewGroupOrderService())->getOneOrder($arrs['tid'],$get_status['user_id']);//找到拼主的订单id
            $spec_info = (new MallNewGroupActService())->getOrderDetailOne($group_order['order_id']);
            $return['group_spec'] = $spec_info['sku_info'];
            return api_output(0, $return);

    }

    /**
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 判断用户有没有参与当前团
     */
    public function isYourGroupTeam(){
        $uid=$this->request->log_uid;//用户id
        $tid = $this->request->param("team_id", 0, "intval");//是否建团,默认未建0
        $act_id = $this->request->param("act_id", 0, "intval");//是否建团,默认未建0
        if ($tid==0 || $act_id==0) {
            return api_output(1003, ['result' => false], '必要参数缺失');
        }
        $return['is_your_team'] = 0;//默认不是自己的团
        if(empty($uid)){
            return api_output(1002, ['result' => false], '请登录');
        }
        $return['is_your_team'] = 0;
        $is_your_team=(new MallNewGroupTeamService())->getIsInTeamStatus($uid,$tid);
        if(!empty($is_your_team)){
            $return['is_your_team'] = 1;//是自己的团
        }
        $is_user_in_act=(new MallNewGroupActService())->getUserActStatus($act_id,$tid,$uid);//判断用户有没有参团
        $return['activity_status'] =0;//用户没有参与这个团
        if($is_user_in_act){
            $return['activity_status'] = 1;//用户已经参与这个团
        }
        return api_output(0, $return);
    }
    //建立拼团团队
    public function createGroupTeam()
    {
        $is_create = $this->request->param("is_create", 0, "intval");//是否建团,默认未建0
        if ($is_create) {
            $data['store_id'] = $this->request->param("store_id", "", "intval");//门店id
            $data['mer_id'] = $this->request->param("mer_id", "", "intval");//商家id
            $data['act_id'] = $this->request->param("group_id", "", "intval");//拼团活动id
            $data1['order_id'] = $this->request->param("order_id", "", "intval");//拼团订单id
            $data['user_id'] = $this->_uid;//用户id
            try{
                $arr = $this->base_group($data['act_id']);
                if (empty($arr)) {
                    return api_output(1003, ['result' => false], '抱歉，没有拼团活动信息');
                }
                $data['complete_num'] = $arr['complete_num'];
                $data['num'] = $this->request->param("num", 1, "intval");//当前团购数量
                $data['status'] = 0;

                $return = (new MallNewGroupActService())->addGroup($data);
                if ($return) {
                    $data1['tid'] = $return;
                    $data1['user_id'] = $this->_uid;
                    $data1['type'] = $this->request->param("type", 0, "intval");//用户类型，0-真实用户，1-机器人
                    $this->intoTeamUser($data1);
                    return api_output(0, $return);
                } else {
                    return api_output(1003, ['result' => false], '抱歉，建团失败');
                }
            }catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        }
    }

    //建立团队成员
    public function intoTeamUser($data1)
    {
        try {
            $return = (new MallNewGroupActService())->addTeamUser($data1);
        }catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    //建立团队成员表
    public function createTeamUser()
    {
        $data['tid'] = $this->request->param("tid",0, "intval");//拼团团队id
        $data['order_id'] = $this->request->param("order_id",0 , "intval");//拼团订单id
        $data['user_id'] = $this->_uid;
        $data['type'] = $this->request->param("type",0, "intval");//用户类型，0-真实用户，1-机器人
        try {
            $return = (new MallNewGroupActService())->addTeamUser($data);
        }catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    //拼团活动基本情况
    public function base_group($actid)
    {
        return (new MallNewGroupActService())->getBase($actid);
    }

    function timediff($end_time)
    {
        if (time() > $end_time) {
            return 0;
        }
        $timediff = $end_time - time();
        $remain = $timediff % 86400;
        $remain = $remain % 3600;
        $secs = $remain % 60;
        return $timediff;
    }


    //活动---砍价列表页

    /**
     * param page 页码
     * @return \json
     */
    public function bargainList()
    {
        //顶部广告图，
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        $status = $this->request->param("status", "", "intval") ? $this->request->param("status", "", "intval") : 0;//全部
        $now_status = $this->request->param("now_status", "", "intval") ? $this->request->param("now_status", "", "intval") : 0;//0代表刚开始的2条数据，1是更多

        $is_share = $this->request->param("is_share", "", "intval") ? $this->request->param("is_share", "", "intval") : 0;//是否通过分享进来，默认0否
        $goods_id = $this->request->param("goods_id", "", "intval") ? $this->request->param("goods_id", "", "intval") : 0;//商品id通过分享进来，默认0否
        $act_id = $this->request->param("act_id", "", "intval") ? $this->request->param("act_id", "", "intval") : 0;//活动id通过分享进来，默认0否
        $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
        $arrs=array();
        $uid=$this->request->log_uid;
        if(empty($uid)){
            return api_output_error(1002, "请登录");
        }
        $arr = $mallGoodsActivityBannerService->getBannerList($act_type=2);
        if(!empty($arr)){
            foreach ($arr as $k=>$v){
                $pic['url']=$v['url'];
                $pic['pic']=$v['image'];
                $pic['id']=$v['id'];
                $arrs[]=$pic;
            }
        }
        $return['banner'] = $arrs;
        //$status = 0;//全部砍价
        if($now_status==0){
            $limit = 2;
        }else{
            $limit = 0;
        }
        try {
            //$addr区分首页和我的列表
            $return['my_bargain_list'] = (new MallNewBargainActService())->getMyBargainList($uid, $status, $page,$limit, $addr = 0);
            $return['bargain_list'] = (new MallNewBargainActService())->getList($uid = 0,$status, $page, $limit,$addr = 0,$is_share,$goods_id,$act_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, $return);
    }

    //我的砍价列表
    public function myBargainList()
    {
        $status = $this->request->param("status", "", "intval") ? $this->request->param("status", "", "intval") : 0;//状态0全部 1进行中 2砍价成功 3 失败
        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        $uid=$this->request->log_uid;
        $return = (new MallNewBargainActService())->getMyBargainList($uid, $status, $page,$limit=0, $addr = 0);
        return api_output(0, $return);
    }

    /**
     * @return \json
     * 砍价页面
     * 我查看砍价与朋友打开砍价页面
     */
    public function friendBargain()
    {

        $team_id = $this->request->param("team_id", "", "intval") ? $this->request->param("team_id", "", "intval") : 1;//团队id
        $sku_id = $this->request->param("sku_id", "", "intval");//规格id
        $act_id = $this->request->param("act_id", "", "intval");//团队id
        if(empty($team_id) || empty($sku_id) || empty($act_id)){
            return api_output(1003, ['result' => false], '参数信息不完整');
        }
        $uid=$this->request->log_uid;
        if(empty($uid)){
            return api_output(1002, ['result' => false], '请登录');
        }
        $bargains=(new MallNewBargainActService())->getMyBargainDetail($team_id,$sku_id,$act_id,$uid);
        if(!$bargains){
            return api_output(1003, ['result' => false], '没有获取到相关的砍价信息');
        }
        $return['bargains'] = $bargains;
        $return['bargain_success'] = $return['bargains']['bargain_success'];
        $return['bargain_member_list'] = $return['bargains']['bargain_member_list'];
        $return['activity_status'] = $return['bargains']['activity_status'];
        $return['scroll_status'] = $return['bargains']['scroll_status'];
        $return['bargain_status'] = $return['bargains']['bargain_status'];
        return api_output(0, $return);
    }

    //好友砍价弹窗,发起砍价调用一次，好用砍价调用一次
    public function friendBargainWin()
    {
        $team_id = $this->request->param("team_id", '', "intval");//砍价团队id
        $sku_id = $this->request->param("sku_id", '', "intval");//砍价商品规格id
        $uid=$this->request->log_uid;
        if(empty($team_id) && empty($sku_id)){
            return api_output(1003, ['result' => false], '参数信息不完整');
        }
        if(empty($uid)){
            return api_output(1002, ['result' => false], '请登录');
        }
        $return = (new MallNewBargainActService())->getBargainPrice($team_id,$uid,$sku_id);
        if($return['status']==1){
            return api_output(0, $return);
        }else{
            return api_output_error(1003, $return['msg']);
        }
    }

    //发起砍价成队

    /**
     * @return \json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 砍价商品详情页我要砍价按钮接口
     * @author mrdeng
     */
    public function createBargainTeam()
    {
        try{
        $arr['act_id'] = $this->request->param("act_id", '', "intval");//砍价活动id
        $arr['mer_id'] = $this->request->param("mer_id", '', "intval");//商家id
        $arr['store_id'] = $this->request->param("store_id", '', "intval");//门店id
       // $arr['user_id'] = $this->request->param("user_id", '', "intval");//发起砍价用户id
        $arr['user_id'] =$this->request->log_uid;
        $arr['sku_id'] = $this->request->param("sku_id", '', "intval");//商品规格id
        $goods_price = $this->request->param("goods_price", '', "intval");//商品价格
        if(empty($arr['user_id'])){
            return api_output_error(1002, '当前接口需要登录');
        }
        if(empty($arr['user_id']) || empty($arr['act_id']) || empty($arr['sku_id'])){
            return api_output(1003, ['result' => false], '参数信息不完整');
        }
        $arr1=(new MallNewBargainTeamService())->isManInBarginTeam($arr,$goods_price);
        if($arr1){
            return api_output(0, $arr1);
        }

        }catch (\Exception $e){
            return api_output(1003, ['result' => false], $e->getMessage());
        }

    }


    //限时优惠列表去提醒
    public function addLimitedNotice()
    {
        $data['goods_id'] = $this->request->param("goods_id", "", "intval");//商品id
        $data['uid'] = $this->request->log_uid??0;
        $data['act_id'] = $this->request->param('act_id', '', 'intval');
        $data['start_time'] = $this->request->param('start_time', '', 'trim');
        if(empty($data['uid'])){
            return api_output_error(1002, '当前接口需要登录');
        }
        try {
            $return['status'] = (new MallLimitedActService())->addLimitedNotice($data);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }
    /**
     * @return \json
     * 限时优惠首页导航栏
     */
    public function getLimitCate(){
        try {
            //广告
            $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
            $arrs=array();
            $arr = $mallGoodsActivityBannerService->getBannerList($act_type=1);
            if(!empty($arr)){
                foreach ($arr as $k=>$v){
                    $pic['url']=$v['url'];
                    $pic['pic']=$v['image'];
                    $pic['id']=$v['id'];
                    $arrs[]=$pic;
                }
            }
            $return['banner'] = $arrs;
            $return['category_list']= (new MallLimitedActService())->getCategoryList();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }
    //限时优惠专题页搜索
    public function limitedSearch()
    {
        $goods_name = $this->request->param("keyname", "", "trim");//商品名称
        $uid = $this->request->log_uid;
        //查询出商品
        $goods_list = (new MallGoodsService())->getSearcehLimitGoods($goods_name);
        $limit_search_list = array();
        if(!empty($goods_list)){
            foreach ($goods_list as $key => $val) {
                $msg=(new MallActivityService())->getLimitedActivity($val['goods_id'], $val['id'], $val['goods_name'], $val['goods_image'], $val['price'],0,'',$uid);
                if(!empty($msg)){
                    $limit_search_list[$key] = $msg;
                }
            }
        }
        $limit_search_list=array_values($limit_search_list);
        $return['category_list'] =[];
        $return['limited_list'] = $limit_search_list;
        return api_output(0, $return);
    }

    //限时优惠列表
    public function limitedList()
    {

        $page = $this->request->param("page", "", "intval") ? $this->request->param("page", "", "intval") : 1;//分页
        try {
            $cate_id = $this->request->param("cate_id", "", "intval") ? $this->request->param("cate_id", "", "intval") : 0;//分类
            $uid =$this->request->log_uid;
            $return['limited_list'] = (new MallLimitedActService())->activityList($cate_id,$page,$uid,10,"k.act_id",'home',0);
        }
        catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * @return mixed
     * 满包邮活动详情
     * @author mrdeng
     */
    public function mallShippingAct()
    {
        $act_id = $this->request->param("act_id", "", "intval");//活动id
        return (new MallShippingActService())->getShippingGoodsList($act_id);
    }


    /**
     * @return \json
     * @author mrdeng
     * 满减、满包邮,活动规则
     */
    public function activityRule()
    {

        $act_id = $this->request->param("act_id", "", "intval");//活动id
        $activity_type = $this->request->param("activity_type", "", "intval");//活动id
        $uid = $this->request->log_uid??0;
        try {
            switch ($activity_type) {
                case 1://满减折
                    $return = (new MallFullMinusDiscountActService())->getMallFullMinusDiscountGoodsList($act_id,$uid);
                    break;
                case 2://满包邮
                    $return = (new MallShippingActService())->getShippingGoodsList($act_id,$uid);
                    break;
                case 3:
                    $return = (new MallReachedActService())->getReachedList($act_id,$uid);
                    break;
                default:
                    return api_output_error(1003, "没有活动类型参数");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 满赠活动规则
     */
    //满赠规则
    public function activityGive()
    {
      /*  $goods_id = $this->request->param("goods_id", "", "intval");//活动id*/
        /*$store_id = $this->request->param("store_id", "", "intval");//活动id*/
        $act_id = $this->request->param("act_id", "", "intval");//活动id
        $uid = $this->request->log_uid??0;
        //获取活动id
        //$arr = (new MallFullGiveSkuService())->getActId($goods_id, $store_id);
        //获取活动状态
        $where[] = ['act_id', '=', $act_id];
        $where[] = ['start_time', '<', time()];
        $where[] = ['end_time', '>=', time()];
        $where[] = ['type', '=', 'give'];
        $field = '*';
        $acivity = (new MallActivityService())->getActInfo($where, $field);
        //活动不存在
        if(empty($acivity)){
            $return['rule_msg']['give_list'] = [];
            $return['rule_msg']['left_time'] = 0;
            $return['goods_list'] =[];
            return api_output(0, $return);
        }
        //剩余时间秒
        $return['rule_msg']['left_time'] = $acivity[0]['end_time']-time();
        //赠品层级
        $where1[] = ['act_id', '=', $act_id];
        $level=(new MallFullGiveLevelService())->getActLevel($where1,$field="*",$order='id asc');
        //赠品列表
        $givie_list = (new MallFullGiveActService())->getGiveList($act_id);
        $list =(new MallFullGiveActService())->getEveryLevelGoods($level,$givie_list);
        /*$list = array();
        if (!empty($givie_list)) {
            foreach ($givie_list as $key => $val) {
                $list[$key] = (new MallFullGiveAct())->getList($val['full_type'], $val['level_money'], $val['goods_id'], $val['sku_id'], $val['act_stock_num']);
            }
        }*/

        $return['rule_msg']['give_list'] = $list;

        //参与活动的商品列表
        $goods_list = (new MallActivityService())->getGoodsListByActid($act_id, $acivity[0]['store_id'],$uid);
        $return['goods_list'] = $goods_list;
        return api_output(0, $return);
    }

    //电子面单打印
    public function singleFacePrint()
    {

        $order_id = $this->request->param("order_id", "0", "intval");//订单ID
        if (empty($order_id)) {
            return api_output(1003, '订单ID必填');
        }
        $singleFaceService = new SingleFaceService();
        $single_face_info = ['code' => 'shunfeng', 'tempid' => 'd3364641f0c74d798684a7d7b819f00a', 'partner_id' => 'logan0928'];
        $result = $singleFaceService->getSingleFace($order_id, $single_face_info);
        return api_output(0, $result);
    }

    //打印状态回调地址
    public function singleFaceCallBack()
    {
        $order_id = $this->request->param("order_id", "0", "intval");//订单ID
        $partner_id = $this->request->param("partner_id", "", "trim");
        $code = $this->request->param("code", "", "trim");
        $temp_id = $this->request->param("temp_id", "", "trim");
        $type = $this->request->param("type", "", "intval");
        $param = $this->request->param("param", "", "trim");//回调参数
        $param = json_decode($param, true);
        if ($param['status'] == 200) {//电子面单打印成功
            if ($type == 1) {
                $mallOrderService = new MallOrderService();
                $where = ['order_id' => $order_id];
                $updata = ['send_time' => time()];
                $mallOrderService->updateMallOrder($where, $updata);//更新订单发货时间
                $mallOrderService->changeOrderStatus($order_id, 20, '');//更新订单状态
            } else {//周期购订单 更新订单状态 快递单号 待确认

            }
        } else {//打印失败，自动重新打印电子面单信息
            $singleFaceService = new SingleFaceService();
            $single_face_info = ['code' => $code, 'temp_id' => $temp_id, 'partner_id' => $partner_id];
            $singleFaceService->getSingleFace($order_id, $single_face_info);
        }
        $response = ['result' => true, 'returnCode' => '200', 'message' => '成功'];
        echo json_encode($response);
    }

    /**
     * @param $periodic_order_id  周期购期数记录id
     * @param $order_id 订单id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundExceptionrecommend_goods_list
     * 周期购顺延
     */
    public function periodicDelay(){
        $periodic_order_id = $this->request->param("periodic_order_id", "", "intval");//订单ID
        $order_id = $this->request->param("order_id", "", "trim");
        if(empty($periodic_order_id) && empty($order_id)){
            return api_output_error(1003, "参数为空，错误");
        }else{
            $arr=(new MallActivityService())->periodicDelay($periodic_order_id,$order_id);
            if(!empty($arr)){
                return api_output(0, $arr);
            }else{
                return api_output_error(1003, "延期失败");
            }
        }
    }

    /**
     * 点击广告
     * @param int $id 广告id
     * 
     */
    public function clickAdver()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $return = (new MallHomeDecorateService())->clickAdver($id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * 点击分类轮播图
     * @param int $id 轮播图id
     * 
     */
    public function clickCategoryBanner()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $return = (new MallHomeDecorateService())->clickCategoryBanner($id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * 点击活动轮播图
     * @param int $id 轮播图id
     * 
     */
    public function clickActivityBanner()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $return = (new MallHomeDecorateService())->clickActivityBanner($id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * 点击六宫格广告
     * @param int $id 广告id
     * 
     */
    public function clickSixAdver()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $return = (new MallHomeDecorateService())->clickSixAdver($id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $return);
    }

    /**
     * 商品分享
     */
    public function share(){
        // web 网页二维码   mini 小程序二维码
        $param['goods_id'] = $this->request->param("goods_id", 2520, "intval");//商品id
        $param['origin'] = $this->request->param("origin", 'web', "trim");
        $param['uid']=$this->_uid??1;
        if(empty($param['uid'])){
            return api_output_error(1002, "获取用户信息失败,请重新登录");
        }
        if(empty($param['goods_id'])){
            return api_output_error(1003, "缺少商品id");
        }
        $ret=(new MallGoodsService())->share($param);
        if($ret['status']){
            return api_output(0, $ret['data'], '获取成功');
        }else{
            return api_output_error(1003, $ret['msg']);
        }

    }
}
