<?php

/**
 * @Author: JJC
 * @Date:   2020-06-09 13:32:26
 * @Last Modified time: 2020-06-23 11:55:08
 */

namespace app\mall\model\service;

use app\common\model\db\Merchant;
use app\common\model\service\AreaService;
use app\common\model\service\AuditService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\image\ImageService;
use app\mall\model\db\ExpressTemplate;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallCartNew;
use app\mall\model\db\MallCategory;
use app\mall\model\db\MallCategorySpecVal;
use app\mall\model\db\MallFullAct;
use app\mall\model\db\MallFullGiveGiftSku;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoods as MallGoodsModel;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\db\MallGoodsSetRecommend;
use app\mall\model\db\MallGoodsSku;
use app\mall\model\db\MallGoodsSort;
use app\mall\model\db\MallGoodsSpec;
use app\mall\model\db\MallGoodsSpecVal;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainAct;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallPrepareActSku;
use app\mall\model\db\MallPriceNotice;
use app\mall\model\db\MallProductBrands;
use app\mall\model\db\MallReachedAct;
use app\mall\model\db\MallRecommendGoods;
use app\mall\model\db\MallShippingAct;
use app\mall\model\db\MerchantStoreKefu;
use app\mall\model\db\MerchantStoreMall;
use app\mall\model\db\ShopDiscount;
use app\mall\model\service\activity\MallActivityDetailService;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\activity\MallNewGroupActService;
use app\merchant\model\service\LoginService;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\service\goods\ShopGoodsService;
use net\Http;
use file_handle\FileHandle;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use pinyin\Pinyin;
use think\Exception;
use think\facade\Db;
use token\Token;

class MallGoodsService
{

    public $MallGoodsModel = null;

    public function __construct()
    {
        $this->MallGoodsModel = new MallGoodsModel();
    }

    public function getList($condition, $sort, $page, $uid = 0,$search=0,$arr1=[],$store_ids=[])
    {
        $whereOr = [];
        if(cfg('open_mall_order_area')==1){
            $area_id=(new MallGoodsAreaService())->getAreaId($arr1);
            $whereOr[]=['ma.area_id', 'in', $area_id];
        }
        
        $condition1 = $this->__dealSearchCondition($condition);
        $sort = $this->__dealSearchSort($sort);

        if (isset($condition['brand_id_str'])) {
            $brand_ids = explode(',', $condition['brand_id_str']);
            $brand_ids && $where[] = ['s.brand_id', 'in', $brand_ids];
            $brand_ids &&  $condition1[] = ['s.brand_id', 'in', $brand_ids];
            unset($condition['brand_id_str']);
        }

        $or = 1;
        $check_status = false;
        if ((isset($condition['keyword']) && $condition['keyword']) && empty($condition['cate_first']) && empty($condition['cate_second']) && empty($condition['cate_three'])) {
            $where[] = ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $condition['keyword'] . '%'];
        }
        /*同城送达，新品。热卖，包邮，活动*/
        if (!empty($condition['is_new']) || !empty($condition['join_activity'])
            || !empty($condition['is_hot']) || !empty($condition['is_location'])
            || !empty($condition['free_shipping'])) {
            $check_status = true;
        }
        //价格区间
        if (isset($condition['min_price']) && $condition['min_price']) {
            $where[] = ['s.min_price', '>=', $condition['min_price']];
        }
        if (isset($condition['max_price']) && $condition['max_price']) {
            $where[] = ['s.max_price', '<=', $condition['max_price']];
        }
        if(cfg('open_mall_order_area')==2){
            $where[] = ['g.store_id','in',$store_ids];
            $condition1[] = ['g.store_id','in',$store_ids];
            $condition['store_ids'] = $store_ids;
        }
        /*筛选*/
        if ($check_status) {
            $sort = 's.sort_platform desc,s.goods_id desc';
            $list = $this->MallGoodsModel->getList1($condition, $sort, $page, $or, $condition1, $uid,$whereOr);
        } elseif ((isset($condition['cate_first']) || isset($condition['cate_second']) || isset($condition['cate_three'])) && isset($condition['keyword'])) {
            $where[] = ['s.is_del', '=', 0];
            $where[] = ['s.status', '=', 1];
            $where[] = ['g.status', '=', 1];
            $where[] = ['g.have_mall','=',1];//不展示关闭商城的店铺的商品
            if (isset($condition['keyword']) && !empty($condition['keyword']) && (!empty($condition['cate_first']) || !empty($condition['cate_second']) || !empty($condition['cate_three'])) && !(!empty($condition['cate_first']) || !empty($condition['cate_second']))) {
                $where[] = ['w.cat_name', 'like', '%' . $condition['keyword'] . '%'];
            }
            if (isset($condition['cate_three'])) {
                $where[] = ['s.cate_three', '=', $condition['cate_three']];
                $or = 3;//三级分类
            } elseif (isset($condition['cate_second'])) {
                $where[] = ['s.cate_second', '=', $condition['cate_second']];
                $or = 2;//二级分类
            } else {
                $where[] = ['s.cate_first', '=', $condition['cate_first']];
                $or = 1;//一级分类
            }
            if (!empty($condition['spec_val_ids'])) {
                $where[] = ['s.spec_val_ids', 'like', '%' . $this->dealMallCatSpecValSearch($condition['spec_val_ids']) . '%'];
            }
            $list = $this->MallGoodsModel->getListByCateName($where, $sort, $page, $or, $uid,$whereOr);
        } else {
            $condition1[]=['g.status','=',1];
            $condition1[]=['g.have_mall','=',1];
            $list = $this->MallGoodsModel->getList($condition1, $sort, $page, $or, $uid,$search,$whereOr);
        }
        if (!empty($list['list'])) {//展示商品级活动标识
            foreach ($list['list'] as $key => $val) {
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
                    $list['list'][$key]['activity_type'] = 'normal';
                    $condition4 = [//条件
                        ['s.store_id', '=', $val['store_id']],
                        ['m.goods_id', '=', $val['goods_id']],
                        ['s.status', '<>', 2],
                        ['s.type', '=', 'periodic'],
                        ['s.is_del', '=', 0],
                    ];
                    $ret1 = (new MallActivity())->getActByGoodsID($condition4, $field = '*');
                    if (!empty($ret1)) {
                        $list['list'][$key]['activity_type'] = $ret1['type'];
                        $act_id = $ret1['act_id'];
                    }
                } else {
                    $act_id = $ret['act_id'];
                    $list['list'][$key]['activity_type'] = $ret['type'];
                }
                $list['list'][$key]['act_price'] = "";
                if ($list['list'][$key]['activity_type'] != 'normal') {//活动商品给价格
                    switch ($list['list'][$key]['activity_type']) {
                        case 'bargain':
                            $where = [['act_id', '=', $act_id]];
                            $arr = (new MallNewBargainSku())->getBySkuId($where, $field = "act_price");
                            $list['list'][$key]['act_price'] = get_format_number($arr['act_price']);
                            break;
                        case 'group':
                            $where = [['act_id', '=', $act_id]];
                            $price = (new MallNewGroupSku())->getPice($where);
                            $list['list'][$key]['act_price'] = get_format_number($price);
                            break;
                        case 'limited':
                            $rets = (new MallLimitedAct())->getLimitedByActID($act_id, $val['goods_id']);
                            if (!empty($rets) && $rets[0]['limited_status'] == 0) {
                                $list['list'][$key]['activity_type'] = 'normal';
                            }
                            $price = (new MallLimitedSku())->limitMinPrice($act_id, $val['goods_id']);
                            $list['list'][$key]['act_price'] = get_format_number($price);
                            break;
                        case 'prepare':
                            $price = (new MallPrepareActSku())->prepareMinPrice($act_id, $val['goods_id']);
                            $list['list'][$key]['act_price'] = get_format_number($price);
                            break;
                        default:
                            break;
                    }
                }
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
                $list['list'][$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $list['list'][$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
        }
        return $list;
    }

    //查询所有的关键词关联的二级分类id，查出所有的二级分类名称
    public function getLevelTwoId($condition)
    {
        //查询条件
        // $condition = $this->__dealSearchCondition($condition);
        if (isset($condition['keyword']) && $condition['keyword']) {
            $where[] = ['name|spell_capital|spell_full', 'like', '%' . $condition['keyword'] . '%'];
            $where[] = ['is_del', '=', 0];
            $where[] = ['status', '=', 1];
        }
        //查询所有的二级id
        $list = $this->MallGoodsModel->getLevelTwoId($where);
        //获取所有二级id的主键和名称
        $MallCategory = new MallCategory();
        return $MallCategory->getGoodLevelTwoName($list);
    }

    public function getSome($where, $field = '*')
    {
        return $this->MallGoodsModel->getAllList($where, $field);
    }

    //根据控制器传过来的查询条件，统一处理成db(getList)能接受的条件
    private function __dealSearchCondition($condition)
    {
        $where = [];//构造查询条件
        if (isset($condition['keyword']) && $condition['keyword']) {
            if(!empty($condition['is_from_index_search'])){ //首页搜索
                $where[] = ['s.name|s.spell_capital|s.spell_full|g.name', 'like', '%' . $condition['keyword'] . '%'];
            }else {
                $where[] = ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $condition['keyword'] . '%'];
            }
        }
        if (isset($condition['cate_first']) && $condition['cate_first']) {
            $where[] = ['s.cate_first', '=', $condition['cate_first']];
        }
        //二级分类
        if (isset($condition['cate_second']) && $condition['cate_second']) {
            if (strpos($condition['cate_second'], ',') !== false) {
                $where[] = ['s.cate_second', '=', $condition['cate_second']];
            } else {
                $conn = explode(',', $condition['cate_second']);
                $where[] = ['s.cate_second', 'in', $conn];
            }
        }

        //活动
        /*if (isset($condition['join_activity']) && $condition['join_activity']) {
            $where[] = ['s.join_activity', '<>', ''];
        }*/

        if (isset($condition['cate_three']) && $condition['cate_three']) {
            $where[] = ['s.cate_three', '=', $condition['cate_three']];
        }
        if (isset($condition['store_id']) && $condition['store_id']) {
            $where[] = ['s.store_id', '=', $condition['store_id']];
        }
        //包邮
        if (isset($condition['free_shipping']) && $condition['free_shipping']) {
            $where[] = ['s.free_shipping', '=', 1];
        }
        //价格区间
        if (isset($condition['min_price']) && $condition['min_price']) {
            $where[] = ['s.min_price', '>=', $condition['min_price']];
        }
        if (isset($condition['max_price']) && $condition['max_price']) {
            $where[] = ['s.min_price', '<=', $condition['max_price']];
        }
        //参数值
        if (isset($condition['spec_val_ids']) && $condition['spec_val_ids']) {
            $where[] = ['s.spec_val_ids', 'like', '%' . $this->dealMallCatSpecValSearch($condition['spec_val_ids']) . '%'];
        }

        /*if (!empty($condition['cate_first']) && !empty($condition['cate_second']) && !empty($condition['keyword'])) {
            if (isset($condition['keyword']) && $condition['keyword']) {
                $where[] = ['w.cat_name', 'like', '%' . $condition['keyword'] . '%'];
            }
            $where[] = ['w.cat_id', '=', $condition['cate_second']];
        }*/

        //品牌筛选
        if (isset($condition['brand_id_str']) && !empty($condition['brand_id_str'])) {
            $where[] = ['s.brand_id', 'in', explode(',', $condition['brand_id_str'])];
        }
        $where[] = ['s.status', '=', 1];
        $where[] = ['s.is_del', '=', 0];
        $where[] = ['g.have_mall', '=', 1];
       // $where[] = ['g.status', '=', 1];
        return $where;
    }
    
    public function dealMallCatSpecValSearch($spec_val_ids){
        if(strpos($spec_val_ids, ',') !== false){
            $cat_spec_val_ids = explode(',',$spec_val_ids);
            if(!$cat_spec_val_ids[count($cat_spec_val_ids)-1]){
                unset($cat_spec_val_ids[count($cat_spec_val_ids)-1]);
            }
            $cat_spec_ids_info = (new MallCategorySpecVal())->where([['id','in',$cat_spec_val_ids]])->order('cat_spec_id asc')->select()->toArray();
            $cat_spec_ids = [];
            foreach ($cat_spec_ids_info as $val){
                $cat_spec_ids[] = $val['cat_spec_id'].':'.$val['id'];
            }
            $spec_val_ids = implode('|',$cat_spec_ids);
        }
        return $spec_val_ids;
    }

    //根据控制器传过来的排序格则，统一处理成db(getList)能接受的条件
    private function __dealSearchSort($sort)
    {
        switch ($sort) {
            case 'price_asc':
                $sort_str = 's.min_price asc,s.goods_id desc';
                break;
            case 'price_desc':
                $sort_str = 's.min_price desc,s.goods_id desc';
                break;
            case 'reply_asc':
                $sort_str = 's.reply_count asc,s.goods_id desc';
                break;
            case 'reply_desc':
                $sort_str = 's.reply_count desc,s.goods_id desc';
                break;
            case 'sale_asc':
                $sort_str = 's.sale_num asc,s.goods_id desc';
                break;
            case 'sale_desc':
                $sort_str = 's.sale_num desc,s.goods_id desc';
                break;
            case 'sale_num':   //销量
                $sort_str = 's.sale_num desc';
                break;
            case 'browse_num':  //浏览量
                $sort_str = 's.browse_num desc';
                break;
            case 'new_good':  //新品
                $sort_str = 'new_good';
                break;
            case 'is_hot':  //新品
                $sort_str = 'is_hot';
                break;
            default:
                $sort_str = 's.sort_platform desc,s.sale_num desc,s.browse_num desc,s.reply_count desc';
                break;
        }
        return $sort_str;
    }

    public function getListGroupStore($condition, $page,$arr1=[])
    {
        $where = array();
        if ($condition['keyword']) {
            //$where[] = ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $condition['keyword'] . '%'];
            $where[] = ['m.name', 'like', '%' . $condition['keyword'] . '%'];
            $where[] = ['s.status', '=', 1];
            $where[] = ['s.is_del', '=', 0];
            $where[] = ['m.status', '=', 1];
            $where[] = ['m.have_mall', '=', 1];
        }
        $whereOr = [];
        if(cfg('open_mall_order_area')==1){
            $area_id=(new MallGoodsAreaService())->getAreaId($arr1);
            $whereOr[]=['ma.area_id', 'in', $area_id];
        }
        
        $merchant_settle_in = customization('merchant_settle_in');
        //店铺列表
        $list = $this->MallGoodsModel->getListGroupStore($where, $page,$whereOr);
        //var_dump(213);
        $list['list'] = $this->dealStoreListAddGoods($list['list'], $condition['keyword'],$whereOr);
        if (!empty($list['list'])) {//展示商品级活动标识
            foreach ($list['list'] as $key => $val) {
                //$list['list'][$key]['goods_list'] = $this->toHeavy($val['goods_list'], 'goods_id');
                foreach ($list['list'][$key]['goods_list'] as $k => $v) {
                    $list['list'][$key]['goods_list'][$k]['image'] = $v['image'] ? replace_file_domain($v['image']) : '';
                    $condition3[] = [//条件
                        ['s.store_id', '=', $v['store_id']],
                        ['m.goods_id', '=', $v['goods_id']],
                        ['s.status', '=', 1],
                        ['s.start_time', '<', time()],
                        ['s.end_time', '>=', time()],
                        ['s.is_del', '=', 0],
                    ];
                    $condition_or[] = [//条件
                        ['s.store_id', '=', $v['store_id']],
                        ['m.goods_id', '=', $v['goods_id']],
                        ['s.type', '=', 'periodic'],
                        ['s.status', '=', 1],
                        ['s.is_del', '=', 0],
                    ];
                    $ret = (new MallActivity())->getActByGoodsIDOr($condition3, $field = '*', $condition_or);
                    if (empty($ret)) {
                        $list['list'][$key]['goods_list'][$k]['activity_type'] = 'normal';
                    } else {
                        $list['list'][$key]['goods_list'][$k]['activity_type'] = $ret['type'];
                    }
                } 

                if($merchant_settle_in == 1){
                    //商家入驻缴费标志
                    $list['list'][$key]['settle_in_sign'] = $val['settle_in_money'] > 0 ? $val['settle_in_sign'] : '';
                    unset($list['list'][$key]['settle_in_money']);
                } 
            }
        }
        return $list;
    }

    /**
     * @param $data
     * @return array
     * 去掉二维数组中某个元素重复的一维数组
     */
    function toHeavy($array, $key1)
    {
        $tmp_arr = array();
        foreach ($array as $k => $v) {
            if (in_array($v[$key1], $tmp_arr)) {
                unset($array[$k]);
            } else {
                $tmp_arr[] = $v[$key1];
            }
        }
        $array = array_values($array);
        return $array;
    }

    //填充店铺列表的每个店铺下的商品列表
    public function dealStoreListAddGoods($list, $keyword,$whereOr=[])
    {
        if ($list) {
            //分三步走 
            //1.查询所有关键字和店铺下的商品列表
            //2.查询所有非关键字和店铺下的商品列表
            //3.先组装匹配关键字的商品列表，不满足三个的商品再去拿不满足关键字匹配的商品
            $store_ids = array_column($list, 'store_id');
            $match_where = [
                ['status', '=', 1],
                ['is_del', '=', 0],
                ['store_id', 'in', $store_ids],
                /*['name|spell_capital|spell_full', 'like', '%' . $keyword . '%'],*/
            ];
            $unmatch_where = [
                ['status', '=', 1],
                ['is_del', '=', 0],
                ['store_id', 'in', $store_ids],
                ['name', 'not like', '%' . $keyword . '%'],
                ['spell_capital', 'not like', '%' . $keyword . '%'],
                ['spell_full', 'not like', '%' . $keyword . '%']
            ];
            $order = "sale_num desc";
	    if(empty($whereOr)){
	      $match_goods_array = $this->MallGoodsModel->getAllList($match_where, "*", $order);
	    }else{
	      $match_goods_array = $this->MallGoodsModel->getAllList($match_where, "*", $order,$whereOr,$store_ids);
	    }
            $match_goods_array = $this->formatGoodsListByStoreId($match_goods_array);

            //$unmatch_goods_array = $this->MallGoodsModel->getAllList($unmatch_where);
           // $unmatch_goods_array = $this->formatGoodsListByStoreId($unmatch_goods_array);
            foreach ($list as $key => $val) {
                $goods_list = [];//该店铺下的商品列表，最多取三个
                $match_goods_count = isset($match_goods_array[$val['store_id']]) ? count($match_goods_array[$val['store_id']]) : 0;
                if ($match_goods_count >= 3) {
                    $goods_list = array_slice($match_goods_array[$val['store_id']], 0, 3);
                }else{
                    $goods_list=$match_goods_array[$val['store_id']];
                }
                /*} else {
                    $need = 3 - $match_goods_count;
                    if (isset($unmatch_goods_array[$val['store_id']])) {
                        $need_array = array_slice($unmatch_goods_array[$val['store_id']], 0, $need);
                        $goods_list = array_merge($match_goods_array[$val['store_id']], $need_array);
                    } else {
                        $goods_list = $match_goods_array[$val['store_id']];
                    }

                }*/
                $list[$key]['goods_list'] = $goods_list;
            }
        }
        return $list;
    }

    public function formatGoodsListByStoreId($list)
    {
        $return = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $return[$val['store_id']][] = $val;
            }
        }
        return $return;
    }

    public function getOne($goodsId, $del = 0)
    {   
        if(!$del){
            $info = $this->MallGoodsModel->getOne($goodsId);
        }
        else{
            $info = $this->MallGoodsModel->getOneByWhere([['goods_id','=',$goodsId]], true);
        }
        return $this->dealOne($info);
    }

    private function dealOne($info)
    {
        return $info;
    }

    public function getSearcehName($keyword)
    {
        if (empty($keyword)) {
            throw new \think\Exception("关键词缺失！");
        }
        $where = [
            ['name|spell_capital|spell_full', 'like', '%' . $keyword . '%'],
            ['is_del', '=', 0],
            ['status', '=', 1],
        ];
        $list = $this->MallGoodsModel->getAll($where, 'name');
        return array_column($list, 'name');
    }

    /**
     * @param $keyword
     * @return array
     * @throws \think\Exception
     * @author mrdeng
     * 模糊查询商品id和名称
     */
    public function getSearcehGoods($keyword)
    {
        if (empty($keyword)) {
            throw new \think\Exception("关键词缺失！");
        }
        $where = [
            ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $keyword . '%'],
            ['s.is_del', '=', 0],
            ['s.status', '=', 1],
        ];
        $list = $this->MallGoodsModel->getAllActivityList($where, 's.goods_id,s.name as goods_name,a.activity_id,s.image as goods_image,s.price');
        return $list;
    }

    /**
     * @param $keyword
     * @return array
     * @throws Exception
     * 查询限时优惠专题页商品
     */
    public function getSearcehLimitGoods($keyword)
    {
        if (empty($keyword)) {
            throw new \think\Exception("关键词缺失！");
        }
        $where = [
            ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $keyword . '%'],
            ['s.is_del', '=', 0],
            ['s.status', '=', 1],
            ['m.type', '=', 'limited'],
            ['l.is_recommend', '=', 1],
            ['m.end_time', '>=', time()],
            ['l.recommend_start_time', '<', time()],
            ['l.recommend_end_time', '>=', time()],
        ];
        $list = $this->MallGoodsModel->getAllLimitActivityList($where, 's.goods_id,s.name as goods_name,m.id,s.image as goods_image,s.price');
        return $list;
    }

    /**
     * @param $keyword
     * @return array
     * @throws Exception
     * 查询限时优惠专题页商品
     */
    public function getSearcehLimitGoods1()
    {
        $where = [
            ['s.is_del', '=', 0],
            ['s.status', '=', 1],
            ['m.type', '=', 'limited'],
            ['m.end_time', '>=', time()],
        ];
        $list = $this->MallGoodsModel->getAllLimitActivityList($where, '(l.recommend_end_time-l.recommend_start_time) as time,l.act_stock_num,l.sale_num,l.act_price,s.goods_id,s.name as goods_name,m.id,s.image as goods_image,s.price');
        return $list;
    }


    //获取商品参与的活动信息
    public function getGoodActivity($goods_id, $store_id)
    {
        //满减满赠
        $MallFullAct = new MallFullAct();
        $arr = $MallFullAct->getOne($goods_id, $store_id);
        if ($arr) {
            return $arr;
        }
        //满折
        $arr = (new ShopDiscount())->getOne($goods_id, $store_id);
        if ($arr) {
            return $arr;
        }
        //n元n件
        $arr = (new MallReachedAct())->getOne($goods_id, $store_id);
        if ($arr) {
            return $arr;
        }
        //满包邮
        $arr = (new MallShippingAct())->getOne($goods_id, $store_id);
        if ($arr) {
            return $arr;
        }

    }
    /**
     * [recommendGoodsList 猜你喜欢推荐列表]
     * @Author   JJC
     * @DateTime 2020-06-15T16:39:23+0800
     * @param    [type]                   $uid  [用户id]
     * @param    [type]                   $page [分页的页数]
     * @return   [type]                         [description]
     */

    /*public function recommendGoodsList($uid,$page){
        if($uid){
            $where = [];//筛选条件

            //浏览过的商品id
            $MallBrowseService = new MallBrowseService();
            $browseGoodsIds = $MallBrowseService->getAllList($uid);
            if($browseGoodsIds){
                $where[] = ['s.goods_id','in',$browseGoodsIds];
            }
            //搜索过的商品名称
            $MallSearchLogService = new MallSearchLogService();
            $searchContents = $MallSearchLogService->getAll($uid);
            $or = [];
            if($searchContents){
                foreach ($searchContents as $val) {
                    $where[] = ['s.name','like','%'.$val.'%'];
                }
            }
        }
        $sort = 's.sale_num desc,s.create_time desc,s.browse_num desc';
        $list = $this->MallGoodsModel->getList($where,$sort,$page,1);
        //echo $this->MallGoodsModel->getLastSql();exit;
        return $list;
    }*/
    /**
     * @Author Mrdeng
     * @param $condition
     * @param $page
     * @return array
     */
    public function recommendGoodsList($condition, $page, $extra_condition = [], $uid = 0,$arr=[],$store_ids=[])
    {
        $where = [];
        $where[] = ['s.is_del', '=', 0];
        $where[] = ['s.status', '=', 1];
        $where[] = ['mt.status', '=', 1];
        $where[] = ['g.status', '=', 1];
        $where[] = ['g.have_mall', '=', 1];
        if ($condition) {
            //筛选条件
            $where[] = ['s.cate_second', 'in', ($condition)];
        }
        $where = array_merge($where, $extra_condition);
        $sort = 's.sort_platform desc,s.goods_id desc';
        $area_id=(new MallGoodsAreaService())->getAreaId($arr);
        $whereor = [];
        if(cfg('open_mall_order_area')==1){
            $whereor = $area_id ? [['ma.area_id', 'in', $area_id]] : [];
        }elseif(cfg('open_mall_order_area')==2){
            $where[] = ['s.store_id','in',$store_ids];
        }
        $list = $this->MallGoodsModel->getRelatedAreaGoods($where, $sort, $page,$uid,$whereor);
        return $list;
    }

    /**
     * @Author Mrdeng
     * @param $condition
     * @param $page
     * @return array
     */
    public function recommendMySetGoodsList($condition, $page, $extra_condition = [], $uid = 0,$id=0,$arr1=[],$store_ids=[])
    {
        $whereOr[] = $where[] = ['s.is_del', '=', 0];
        $whereOr[] = $where[] = ['s.status', '=', 1];
        $whereOr[] = $where[] = ['mt.status', '=', 1];
        $whereOr[] = $where[] = ['g.status', '=', 1];
        $whereOr[] = ['r.recommend_id', '=', $id];
        $whereOr[] = ['s.is_del', '=', 0];
        $whereOr[] = ['s.status', '=', 1];
        $whereOr[] = ['mt.status', '=', 1];
        $whereOr[] = ['g.status', '=', 1];
        $whereOr[] = $where[] = ['g.have_mall', '=', 1];
        if ($condition) {
            $push_goods_ids = [];
            if($id){
                //获取推荐商品
                $push_goods_ids = (new MallRecommendGoods())->where(['recommend_id'=>$id])->column('goods_id');
            }
            //筛选条件
            if($push_goods_ids){
                $where[] = ['', 'exp', Db::raw("s.cate_second in (".$condition.") or s.goods_id in (".implode(',',$push_goods_ids).")")];
            }else{
                $where[] = ['s.cate_second', 'in', ($condition)];
            }
        }
        $where = array_merge($where, $extra_condition);
        $sort = 'r.sort desc,r.id desc';
        $whereOrS = [];
        if(cfg('open_mall_order_area')==1&&$arr1){
            $area_id=(new MallGoodsAreaService())->getAreaId($arr1);
            $whereOrS[]=['ma.area_id', 'in', $area_id];
        }elseif (cfg('open_mall_order_area')==2){
            $where[] = ['s.store_id','in',$store_ids];
        }
        $list = $this->MallGoodsModel->getMySetList($where, $sort, $page, $uid,$whereOr,$id,$whereOrS);
        return $list;
    }
    //验证购物车
    public function checkCart($goodsData)
    {
        //

    }

    //生成客服页面的地址
    public function serviceCustomer($uid, $store_id)
    {
        //调整客服判断
        if ($uid && $store_id) {
            $kf = (new MerchantStoreKefu())->getOne($store_id,'store');
            if ($kf && $uid > 0) {
                $kf_url = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store', ['from' => 'mall3']);
                return $kf_url;
            } else {
                return false;
            }
        } else {
            if(isset($_GET['referer'])){
                $referer = htmlspecialchars_decode($_GET['referer']);
            }else{
                $referer = $_SERVER['HTTP_REFERER'];
            }
            return cfg("site_url") . '/packapp/my/login.html?back=' . urlencode($referer) . '&loginFail=' . urlencode($referer);
        }
    }

//客服地址生成
    public function build_im_chat_url($fromUser, $toUser, $relation, $params = [])
    {
        if (empty($fromUser) || empty($relation)) {
            throw new \Exception('参数有误');
        }
        /*C('config.site_url') .地址是啥*/
        $url = cfg('site_url') . '/packapp/im/index.html#/chatInterface?from_user=' . $fromUser . '&to_user=' . $toUser . '&relation=' . $relation;
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . $v;
        }
        return $url;
    }

    /**
     * 获取商品基本详情
     * @param  [type]  $goods_id 商品ID
     * @param boolean $deal 是否需要处理基本格式
     * @return [type]            [description]
     */
    public function getBasicInfo($goods_id, $deal = false)
    {
        $basic = $this->MallGoodsModel->getOne($goods_id);
        if ($deal && $basic) {
            $basic['img'] = thumb($basic['image'], 100, 100);
            $images = $basic['images'] ? explode(";", $basic['images']) : [];
            foreach ($images as $img) {
//                $basic['imgs'][] = thumb($img, 750, 750);
                $basic['imgs'][] = thumb_img($img, 950, 950);
            }
            $store = (new MerchantStoreService)->getStoreByStoreId($basic['store_id']);
            if(empty($store)){
                $basic['location'] =  '';
            }else{
                $areaService = new AreaService();
                $province = $areaService->getOne(['area_id' => $store['province_id']]);
                $city = $areaService->getOne(['area_id' => $store['city_id']]);
                $basic['location'] = [$province ? $province['area_name'] : '', $city ? $city['area_name'] : ''];
            }

        }
        return $basic;
    }

    public function getNameById($goods_id)
    {
        $where = [['goods_id', '=', $goods_id], ['is_del', '=', 0], ['status', '=', 1]];
        $arr = $this->MallGoodsModel->getAllByID($where, 'name');
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }

    }

    /**
     * @param $goods_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 设置推荐
     */
    public function setRecommend($goods_id){
        foreach ($goods_id as $k=>$v){
            $exit=(new MallGoods())->getOneByWhere(['goods_id'=>$v,'status'=>1,'is_del'=>0],true);
            if(empty($exit)){
                continue;
            }
            $goods=(new MallGoodsSetRecommend())->getOne(['goods_id'=>$v]);
            if(empty($goods)){
                $data['goods_id']=$v;
                $data['create_time']=time();
                (new MallGoodsSetRecommend())->add($data);
            }
        }
        return true;
    }

    /**
     * @param $goods_id
     * @return bool
     * @throws \Exception
     * 取消推荐
     */
    public function cancelRecommend($goods_id){
        $ret=(new MallGoodsSetRecommend())->delData(['goods_id'=>$goods_id]);
        return $ret;
    }

    /*********@author zhumengqun* ************** */
    /**
     * 平台后台-按条件获取商品列表
     * @param $keyword
     * @param $merList
     * @param $storeList
     * @param $page
     * @param $pageSize
     * @param $checkOnSale
     * @param $param 新加的筛选条件
     * @return array
     */
    public function getPlatformGoodsList($keyword, $merList, $storeList, $page, $pageSize, $cat_id = 0, $checkOnSale = true,$param=[],$check_admin_area='')
    {
        $where = [['a.is_del', '=', 0], ['c.status', '<>', 4]];
        if ($checkOnSale) {
            $where[] = ['a.status', '=', 1];
        }
        //商品筛选
        if (!empty($keyword)) {
            array_push($where, [['a.name', 'like', '%' . $keyword . '%']]);
        }
        //商家筛选
        if (!empty($merList)) {
            array_push($where, ['a.mer_id', 'in', $merList]);
        }
        //店铺筛选
        if (!empty($storeList)) {
            array_push($where, ['a.store_id', 'in', $storeList]);
        }
        //分类筛选
        if (!empty($cat_id)) {
            array_push($where, ['a.cat_id', '=', $cat_id]);
        }
        //排序
        $orderBrowse = [];
        if($param && isset($param['browse']) && $param['browse']){
            $orderBrowse['browse_num'] = $param['browse']==2 ? 'ASC' : 'DESC';
        }
        if($param && isset($param['browseToday']) && $param['browseToday']){
            $orderBrowse['browse_num_today'] = $param['browseToday']==2 ? 'ASC' : 'DESC';
        }
        $order = [
            'sort_platform' => 'DESC',
            'is_first' => 'DESC',
            'create_time' => 'DESC'
        ];

        //如果开启区域管理员限制区域管理员只能看到指定区域商品
        if($check_admin_area){
            $where[] =  ['', 'exp', Db::raw($check_admin_area)];
        }

        $order = array_merge($orderBrowse,$order);
        $field = 'a.goods_id,a.store_id,a.mer_id,a.score_percent_type,a.score_percent,a.score_max,a.score_percent,a.spread_rate,a.sub_spread_rate,a.third_spread_rate,a.price,a.goods_type,a.cate_first,a.cate_second,a.cate_three,a.name as goods_name,a.image,a.join_activity,a.min_price,a.max_price,a.sale_num,a.virtual_sales,a.plat_virtual_sales,a.virtual_set,a.stock_num,a.status,a.sort_platform,a.is_first,b.name as mer_name,c.name as store_name,browse_num,browse_num_today';
        $arr = $this->MallGoodsModel->getByCondition($where, $field, $order, $page, $pageSize);
        $count = $this->MallGoodsModel->getByConditionCount($where, $field);
        if (!empty($arr)) {
            $list = array();
            $catService = new MallCategoryService();
            $goodsIdAry = [];
            foreach ($arr as $key => $val) {
                $arr[$key]['set_status']=0;
                $arr[$key]['image'] = replace_file_domain($val['image']);
                $set_msg=(new MallGoodsSetRecommend())->getOne(['goods_id'=>$val['goods_id']]);//查看是否精选为你推荐
                if(!empty($set_msg)){
                    $arr[$key]['set_status']=1;
                }
                //一级分类
                if (!empty($val['cate_first']) && empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = '';
                    $arr[$key]['cate_three'] = '';
                }
                //二级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = '';
                }
                //三级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && !empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $cate_third_name = $catService->getEditCategory($val['cate_three']) ? $catService->getEditCategory($val['cate_three'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = $cate_third_name;
                } 
                //单品销量
                $browse_num = (int)$arr[$key]['browse_num'];
                $arr[$key]['browse_num'] = $browse_num >= 10000 ? round(($browse_num / 10000), 1) . '万' : $browse_num;

                if ($val['virtual_set'] == 1) {
                    $arr[$key]['virtual_sales'] = $val['plat_virtual_sales'];
                }
                $goodsIdAry[] = $val['goods_id'];
            }
            $list['list'] = $arr;
            $list['count'] = $count;
            return $list;
        } else {
            return [];
        }

    }

    /**
     * 查询商品浏览量列表
     * @author Nd
     * @date 2022/5/17
     * @param $keyword
     * @param $merList
     * @param $storeList
     * @param $page
     * @param $pageSize
     * @param int $cat_id
     * @param bool $checkOnSale
     * @param array $param
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPlatformGoodsBrowseList($keyword, $merList, $storeList, $page, $pageSize, $cat_id = 0, $checkOnSale = true,$param=[])
    {
        $where = [['a.is_del', '=', 0], ['c.status', '<>', 4]];
        if ($checkOnSale) {
            $where[] = ['a.status', '=', 1];
        }
        //商品筛选
        if (!empty($keyword)) {
            array_push($where, [['a.name', 'like', '%' . $keyword . '%']]);
        }
        //商家筛选
        if (!empty($merList)) {
            array_push($where, ['a.mer_id', 'in', $merList]);
        }
        //店铺筛选
        if (!empty($storeList)) {
            array_push($where, ['a.store_id', 'in', $storeList]);
        }
        //分类筛选
        if (!empty($cat_id)) {
            array_push($where, ['a.cat_id', '=', $cat_id]);
        }
        //排序
        $orderBrowse = [];
        if($param && isset($param['browse']) && $param['browse']){
            $orderBrowse['browse_num'] = $param['browse']==2 ? 'ASC' : 'DESC';
        }
        if($param && isset($param['browseToday']) && $param['browseToday']){
            $orderBrowse['browse_num_today'] = $param['browseToday']==2 ? 'ASC' : 'DESC';
        }
        $order = [
            'sort_platform' => 'DESC',
            'is_first' => 'DESC',
            'create_time' => 'DESC'
        ];
        $order = array_merge($orderBrowse,$order);
        $field = 'a.goods_id,a.store_id,a.mer_id,a.score_percent_type,a.score_percent,a.score_max,a.score_percent,a.spread_rate,a.sub_spread_rate,a.third_spread_rate,a.price,a.goods_type,a.cate_first,a.cate_second,a.cate_three,a.name as goods_name,a.image,a.join_activity,a.min_price,a.max_price,a.sale_num,a.virtual_sales,a.plat_virtual_sales,a.virtual_set,a.stock_num,a.status,a.sort_platform,a.is_first,b.name as mer_name,c.name as store_name,browse_num,browse_num_today';
        $arr = $this->MallGoodsModel->getByCondition($where, $field, $order, $page, $pageSize);
        $count = $this->MallGoodsModel->getByConditionCount($where, $field);
        if (!empty($arr)) {
            $list = array();
            $catService = new MallCategoryService();
            foreach ($arr as $key => $val) {
                $arr[$key]['set_status']=0;
                $arr[$key]['image'] = replace_file_domain($val['image']);
                $set_msg=(new MallGoodsSetRecommend())->getOne(['goods_id'=>$val['goods_id']]);//查看是否精选为你推荐
                if(!empty($set_msg)){
                    $arr[$key]['set_status']=1;
                }
                //一级分类
                if (!empty($val['cate_first']) && empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = '';
                    $arr[$key]['cate_three'] = '';
                }
                //二级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = '';
                }
                //三级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && !empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $cate_third_name = $catService->getEditCategory($val['cate_three']) ? $catService->getEditCategory($val['cate_three'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = $cate_third_name;
                } 
                //单品销量
                $browse_num = (int)$arr[$key]['browse_num'];
                $arr[$key]['browse_num'] = $browse_num >= 10000 ? round(($browse_num / 10000), 1) . '万' : $browse_num;

                if ($val['virtual_set'] == 1) {
                    $arr[$key]['virtual_sales'] = $val['plat_virtual_sales'];
                }
                //加上时间筛选
                $whereBrowse = [];
                if($param && isset($param['start_time']) && $param['start_time'] && isset($param['end_time']) && $param['end_time']){
                    $whereBrowse[] = ['create_time','>=',strtotime($param['start_time'])];
                    $whereBrowse[] = ['create_time','<',strtotime($param['end_time']) + 86400];
                }
                $whereBrowse[] = ['goods_id','=',$val['goods_id']];
                $arr[$key]['browse_num_time'] = (new MallBrowseNewService())->getBrowseNum($whereBrowse);
            }
            $list['list'] = $arr;
            $list['count'] = $count;
            return $list;
        } else {
            return [];
        }

    }

    /*********@author mrdeng* ************** */
    /**
     * 平台后台-按条件获取商品列表
     * @param $keyword
     * @param $merList
     * @param $storeList
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getPlatformGoodsListByName($keyword, $merList, $storeList, $page, $pageSize, $cat_id = 0, $checkOnSale = true,$check_admin_area='')
    {
        $where = [['a.is_del', '=', 0], ['c.status', '<>', 4]];
        if ($checkOnSale) {
            $where[] = ['a.status', '=', 1];
        }
        //商品筛选
        if (!empty($keyword)) {
            array_push($where, [['a.name', 'like', '%' . $keyword . '%']]);
        }
        //商家筛选
        if (!empty($merList)) {
            array_push($where, ['a.mer_id', 'in', $merList]);
        }
        //店铺筛选
        if (!empty($storeList)) {
            array_push($where, ['a.store_id', 'in', $storeList]);
        }
        //分类筛选
        /*if (!empty($cat_id)) {
            array_push($where, ['a.cat_id', '=', $cat_id]);
        }*/
        //排序
        $order = [
            'is_first' => 'DESC',
            'sort_platform' => 'DESC',
            'create_time' => 'DESC'
        ];
        //如果开启区域管理员限制区域管理员只能看到指定区域商品
        if($check_admin_area){
            $where[] =  ['', 'exp', Db::raw($check_admin_area)];
        }
        $field = 'a.cat_id,a.goods_id,a.store_id,a.mer_id,a.score_percent_type,a.score_percent,a.score_max,a.score_percent,a.spread_rate,a.sub_spread_rate,a.third_spread_rate,a.price,a.goods_type,a.cate_first,a.cate_second,a.cate_three,a.name as goods_name,a.image,a.join_activity,a.min_price,a.max_price,a.sale_num,a.stock_num,a.status,a.sort_platform,a.is_first,b.name as mer_name,c.name as store_name';
        $arr = $this->MallGoodsModel->getByCondition($where, $field, $order, 0, 0);
        $count = $this->MallGoodsModel->getByConditionCount($where, $field);
        $tab_list = array();
        if (!empty($arr)) {
            $list = array();
            $catService = new MallCategoryService();
            foreach ($arr as $key => $val) {
                $arr[$key]['image'] = replace_file_domain($val['image']);
                $arr[$key]['set_status']=0;
                $set_msg=(new MallGoodsSetRecommend())->getOne(['goods_id'=>$val['goods_id']]);//查看是否精选为你推荐
                if(!empty($set_msg)){
                    $arr[$key]['set_status']=1;
                }
                //一级分类
                if (!empty($val['cate_first']) && empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = '';
                    $arr[$key]['cate_three'] = '';
                }
                //二级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = '';
                }
                //三级分类
                if (!empty($val['cate_first']) && !empty($val['cate_second']) && !empty($val['cate_three'])) {
                    $cate_first_name = $catService->getEditCategory($val['cate_first']) ? $catService->getEditCategory($val['cate_first'])['cat_name'] : '';
                    $cate_second_name = $catService->getEditCategory($val['cate_second']) ? $catService->getEditCategory($val['cate_second'])['cat_name'] : '';
                    $cate_third_name = $catService->getEditCategory($val['cate_three']) ? $catService->getEditCategory($val['cate_three'])['cat_name'] : '';
                    $arr[$key]['cate_first'] = $cate_first_name;
                    $arr[$key]['cate_second'] = $cate_second_name;
                    $arr[$key]['cate_three'] = $cate_third_name;
                }
                $tab_list[$val['cat_id']]['cat_name']=(new MallCategory())->getCateName(['cat_id'=>$val['cat_id']],'cat_name');
                $tab_list[$val['cat_id']]['data'][]=$arr[$key];
            }
           /* $list['list'] = $arr;*/
            $list['count'] = $count;
            $list['tab_list'] =$tab_list;
            
            return $list;
        } else {
            return [];
        }

    }
    /**
     * 设置积分
     * @param $goods_id
     * @param $price
     * @param $score_percent
     * @param $score_max
     */
    public function setIntegral($goods_id, $price, $score_percent, $score_max)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        if ($score_percent) {
            if (strpos($score_percent, '%')) {
                $tmp_percent = str_replace('%', '', $score_percent);
                if (floatval($tmp_percent) > 100 || floatval($tmp_percent) < 0) {
                    throw new \think\Exception('消费1元获得积分数百分比应在0-100%之间');
                }
            }
        }
        if ($score_percent < 0) {
            throw new \think\Exception('请填写大于0的数');
        }
		
		if(cfg('user_score_use_percent')){
			$score_max_deducte = bcdiv($score_max, cfg('user_score_use_percent'), 2);
			$price = min($price);
			if ($score_max_deducte > $price) {
				throw new \think\Exception('积分最大使用数抵扣金额超过了商品价格');
			}
        }
		
        if ($score_max < 0) {
            throw new \think\Exception('请填写大于0的数');
        }
        foreach ($goods_id as $val) {
            $where = ['goods_id' => $val];
            if (!empty($score_max)) {
                $data = ['score_percent' => $score_percent, 'score_max' => $score_max, 'score_percent_type' => 1];
            } else {
                $data = ['score_percent' => $score_percent, 'score_percent_type' => 0,'score_max' => 0];
            }
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        return true;
    }

    /**
     * 设置虚拟销量
     * @param $goods_id
     * @param $sales
     */
    public function setVirtual($goods_id, $sales, $virtual_set)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        if ($sales < 0) {
            throw new \think\Exception('请填写大于等于0的数');
        }

        foreach ($goods_id as $val) {
            $where = ['goods_id' => $val];
            $data = ['plat_virtual_sales' => $sales, 'virtual_set' => $virtual_set];
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        return true;
    }

    /**
     * 设置佣金
     * @param $goods_id
     * @param $spread_rate
     * @param $sub_spread_rate
     * @param $third_spread_rate
     */
    public function setCommission($goods_id, $spread_rate, $sub_spread_rate, $third_spread_rate)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        foreach ($goods_id as $val) {
            $where = ['goods_id' => $val];
            $data = [
                'spread_rate' => $spread_rate,
                'sub_spread_rate' => $sub_spread_rate,
                'third_spread_rate' => $third_spread_rate
            ];
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }

        return true;
    }

    /**
     * 设置上下架
     * @param $goods_id
     * @param $status
     */
    public function setStatus($goods_id, $status)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        foreach ($goods_id as $val) {
            //开启景区、体育、商城、外卖审核
            if(customization('open_scenic_sports_mall_shop_audit') == 1){
                if($status == 1 && $this->MallGoodsModel->where('goods_id', $val)->value('audit_status') != 1){
                    continue;
                }
            }
            $where = ['goods_id' => $val,];
            $data = ['status' => $status];
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        return true;
    }

    /**
     * 设置排序
     * @param $goods_id
     * @param $sort
     * @return bool
     */
    public function setSort($goods_id, $sort, $type)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        $where = ['goods_id' => $goods_id];
        if ($type == 'merchant') {
            $data = ['sort_store' => $sort, 'update_time' => time()];
        } elseif ($type == 'platform') {
            $data = ['sort_platform' => $sort, 'update_time' => time()];
        } else {
            throw new \think\Exception('类型错误');
        }
        $result = $this->MallGoodsModel->updateOne($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }

        return true;
    }

    /**
     * 设置置顶
     * @param $goods_id
     * @param $is_first
     * @param $cat_id
     * @return bool
     */
    public function setFirst($goods_id, $is_first, $cat_id)
    {
        if ($is_first == 1) {
            //将当前商品置顶
            $where = ['goods_id' => $goods_id,];
            $data = ['is_first' => 1];
            $result1 = $this->MallGoodsModel->updateOne($where, $data);
            //将同类的其他商品的置顶取消
            $where = [['cat_id', '=', $cat_id], ['goods_id', '<>', $goods_id]];
            $data = ['is_first' => 0];
            $result2 = $this->MallGoodsModel->updateOne($where, $data);
            if ($result1 === false || $result2 === false) {
                throw new \think\Exception('操作失败，请重试');
            }
            return true;
        } else {
            //取消置顶
            $where = ['goods_id' => $goods_id,];
            $data = ['is_first' => 0];
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
            return true;
        }
    }

    /**
     * 获取店铺或商店列表
     * @param $type
     * @param $search
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getMerOrStoreList($type, $search, $page, $pageSize)
    {
        if (empty($type)) {
            throw new \think\Exception('type参数不能为空');
        }
        if ($type == 1) {
            //商家
            if (!empty($search)) {
                $where = [['status','<>',4],['name', 'like', '%' . $search . '%']];
            } else {
                $where = [['status','<>',4]];
            }
            $merService = new MerchantService();
            $arr = $merService->getMerList($where, $page, $pageSize);
            $count = $merService->getMerListCount($where);
        }
        if ($type == 2) {
            //店铺
            if (!empty($search)) {
                $where = [['a.status','<>',4],['b.status','<>',4],['b.name', 'like', '%' . $search . '%']];
            } else {
                $where = [['a.status','<>',4],['b.status','<>',4]];
            }
            $storeService = new Merchant();
            $arr = $storeService->getStoreList($where, $page, $pageSize);
            $count = $storeService->getStoreListCount($where);
        }
        $list['list'] = $arr;
        $list['count'] = $count;
        return $list;
    }

    /**
     * 平台-商品导出
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     */
    public function addGoodsExportPlatForm($param, $systemUser = [], $merchantUser = [])
    {
        $title = cfg('mall_alias_name');
        $param['type'] = 'mall';
        $param['service_path'] = '\app\mall\model\service\MallGoodsService';
        $param['service_name'] = 'goodsExport';
        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param);
        return $result;
    }

    public function goodsExport($param)
    {
        $goodsList = $this->getPlatformGoodsList($param['keyword'], $param['merList'], $param['storeList'], '', '') ? $this->getPlatformGoodsList($param['keyword'], $param['merList'], $param['storeList'], '', '')['list'] : [];
        $csvHead = array(
            L_('商品分类'),
            L_('商品名称'),
            L_('商家名称'),
            L_('店铺名称'),
            L_('售价'),
            L_('总销量'),
            L_('当前库存'),
            L_('状态'),
            L_('排序')
        );
        $csvData = [];
        if (!empty($goodsList)) {
            foreach ($goodsList as $goodsKey => $value) {
                $cate = $value['cate_first'] . ($value['cate_second'] ? '/' . $value['cate_second'] : '') . ($value['cate_three'] ? '/' . $value['cate_three'] : '');
                $csvData[$goodsKey] = [
                    $cate,
                    $value['goods_name'],
                    $value['mer_name'],
                    $value['store_name'],
                    $value['min_price'] . '-' . $value['max_price'],
                    $value['sale_num'],
                    $value['stock_num'],
                    $value['status'] == 1 ? '上架' : '下架',
                    $value['sort_platform'] ?: 0
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function addGoodsExportMerchant($param, $systemUser = [], $merchantUser = [])
    {
        $title = cfg('mall_alias_name') . '导出商品';
        $param['type'] = 'mall';
        $param['service_path'] = '\app\mall\model\service\MallGoodsService';
        $param['service_name'] = 'GooodsExportPhpSpreadsheet';
        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;
    }

    /**
     * 店铺商品导出(Spreadsheet方法)
     * @param $param
     */
    public function GooodsExportPhpSpreadsheet($param)
    {
        $goodsList = $this->getAllGoods($param);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '商品id');
        $worksheet->setCellValueByColumnAndRow(2, 1, '商品名称');
        $worksheet->setCellValueByColumnAndRow(3, 1, '平台分类');
        $worksheet->setCellValueByColumnAndRow(4, 1, '店铺商品分类');
        $worksheet->setCellValueByColumnAndRow(5, 1, '商家名称');
        $worksheet->setCellValueByColumnAndRow(6, 1, '商品规格');
        $worksheet->setCellValueByColumnAndRow(7, 1, '配送方式');
        $worksheet->setCellValueByColumnAndRow(8, 1, '商品售价');
        $worksheet->setCellValueByColumnAndRow(9, 1, '总销量');
        $worksheet->setCellValueByColumnAndRow(10, 1, '当前库存');
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:O')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $len = count($goodsList)-1;
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($goodsList as $key => $val) {
            $j = $i + 2; //从表格第2行开始
            $worksheet->setCellValueByColumnAndRow(1, $j, $goodsList[$key]['goods_id']);
            $worksheet->setCellValueByColumnAndRow(2, $j, $goodsList[$key]['goods_name']);
            $worksheet->setCellValueByColumnAndRow(3, $j, $goodsList[$key]['plat_cate']);
            $worksheet->setCellValueByColumnAndRow(4, $j, $goodsList[$key]['store_sort']);
            $worksheet->setCellValueByColumnAndRow(5, $j, $goodsList[$key]['mer_name']);
            $worksheet->setCellValueByColumnAndRow(6, $j, $goodsList[$key]['sku_str']);
            $worksheet->setCellValueByColumnAndRow(7, $j, $goodsList[$key]['delivery_type']);
            $worksheet->setCellValueByColumnAndRow(8, $j, '¥' . $goodsList[$key]['price']);
            $worksheet->setCellValueByColumnAndRow(9, $j, $goodsList[$key]['all_sale_number']);
            $worksheet->setCellValueByColumnAndRow(10, $j, $goodsList[$key]['stock_num']);
            $i++;
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }

    /**
     * 根据条件获取商品
     * @param $field
     * @param $order
     * @param $where
     * @param $page
     * @param $pageSize
     */
    public function getGoodsByCondition($field, $order, $where, $page, $pageSize)
    {
        $arr = $this->MallGoodsModel->getGoodsByCondition($field, $order, $where, $page, $pageSize);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 根据条件获取商品总数
     * @param $where
     * @return int
     */
    public function getCountByCondition($where)
    {
        $count = $this->MallGoodsModel->getCountByCondition($where);
        return $count;
    }

    /**
     * 获取某个活动下的所有商品
     * @param $gwhere
     * @param $gfield
     * @return array
     */
    public function getActGoods($gwhere, $gfield)
    {
        $arr = $this->MallGoodsModel->getActGoods($gwhere, $gfield);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取某个活动下的所有商品的总数
     * @param $gwhere
     * @return array
     */
    public function getActGoodsCount($gwhere)
    {
        $count = $this->MallGoodsModel->getActGoodsCount($gwhere);
        return $count;
    }

    /**
     * @param $param
     * @return mixed|void
     * @throws \think\Exception
     * 获取商品列表（新建活动时选取商品）
     */
    public function getMallGoodsSelect($param)
    {
        if (empty($param['type'])) {
            throw new \think\Exception('缺少type参数');
        }

        if (!isset($param['goods_id']) && empty($param['sort_id']) && empty($param['keyword']) && $param['keyword'] !== "0") {
            throw new \think\Exception('sort_id和keyword参数至少需要一个');
        }
        if (!isset($param['goods_id']) && empty($param['store_id']) && empty($param['keyword'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        if (empty($param['mer_id'])) {
            throw new \think\Exception('缺少mer_id参数');
        }
        if (!isset($param['goods_id']) && !isset($param['start_time']) && ($param['type'] != 'periodic')) {
            throw new \think\Exception('缺少start_time参数');
        }
        if (!isset($param['goods_id']) && !isset($param['end_time']) && ($param['type'] != 'periodic')) {
            throw new \think\Exception('缺少end_time参数');
        }
        $act_sku = ['bargain', 'group', 'prepare', 'give_gift', 'limited'];
        $act_spu = ['reached', 'shipping', 'minus_discount', 'periodic', 'give'];
        //所有商品（原始）
        $field = 'goods_id,image,store_id,mer_id,name,min_price,max_price,price,stock_num,goods_type';
        if (isset($param['goods_id'])) {//编辑商品
            $where = [['goods_id', 'in', $param['goods_id']], ['mer_id', '=', $param['mer_id']], ['store_id', '=', $param['store_id']], ['status', '=', 1], ['is_del', '=', 0]];
        } else {
            $where = [['mer_id', '=', $param['mer_id']], ['store_id', '=', $param['store_id']], ['status', '=', 1], ['is_del', '=', 0]];
        }
        if (!empty($param['sort_id'])) {
            array_push($where, ['sort_id', '=', $param['sort_id']]);
        } elseif (!empty($param['keyword'])) {
            array_push($where, ['name', 'like', '%' . $param['keyword'] . '%']);
        }
        $order = ['sort_store' => 'DESC', 'goods_id' => 'DESC'];
        $allGoods = $this->MallGoodsModel->getGoodsByCondition($field, $order, $where, $param['page'], $param['pageSize']);

        if (in_array($param['type'], $act_sku)) {
            //sku商品
            $arr = $this->getSkuActList($allGoods, $param);
        } elseif (in_array($param['type'], $act_spu)) {
            //spu商品
            if (isset($param['goods_id'])) {//编辑商品
                foreach ($allGoods as &$value) {
                    $value['image'] = replace_file_domain($value['image']);
                    $value['sku_info'] = [];
                }
                $arr = $allGoods;
            } else {
                $arr = $this->getSpuActList($allGoods, $param)['allGoods'];
            }
        } else {
            throw new \think\Exception('不存在这种活动类型');
        }
        $list['list'] = $arr;
        $list['count'] = $this->MallGoodsModel->getGoodsByConditionCount($where);
        return $list;
    }

    public function getSkuActList($allGoods, $param)
    {
        //被新建的活动的等级
        $l_act = $this->getLevel($param['type']);
        //所有商品（已处理过图片等信息）
        $allGoods = $this->getSpuActList($allGoods, $param)['allGoods'];
        $skuService = new MallGoodsSkuService();
        if (!empty($allGoods)) {
            foreach ($allGoods as $key => $val) {
                if (isset($param['goods_id']) && isset($param['sku_id'][$val['goods_id']])) {
                    $sku_where = [['goods_id', '=', $val['goods_id']], ['is_del', '=', 0], ['sku_id', 'in', $param['sku_id'][$val['goods_id']]]];
                } else {
                    $sku_where = ['goods_id' => $val['goods_id'], 'is_del' => 0];
                }
                $sku_field = 'sku_id,sku_str,price,stock_num';
                //某个商品的sku
                $sku = $skuService->getSkuByCondition($sku_field, $sku_where);
                if ($val['goods_type'] == 'sku') {
                    foreach ($sku as $k => $v) {
                        $where = ['sku_id' => $v['sku_id']];
                        $arr=(new MallGoodsSku())->getOne($where);
                        $sku[$k]['image'] = replace_file_domain($arr['image']);
                        if (!empty($val['act_name'])) {
                            if ($allGoods[$key]['can_be_choose'] == 0) {
                                $sku[$k]['can_be_choose'] = 0;
                            } else {
                                $actSku = $skuService->getActSku($where, $val['type']);
                                if (!empty($actSku)) {
                                    //在参加活动（正在参加活动的sku的活动类型也是正在参加活动的商品的活动类型）
                                    $l_now = $this->getLevel($val['type']);
                                    if ($l_act === $l_now) {
                                        $sku[$k]['can_be_choose'] = 0;
                                    } else {
                                        $sku[$k]['can_be_choose'] = 1;
                                    }
                                    $sku[$k]['act_name'] = $val['act_name'];
                                } else {
                                    $sku[$k]['act_name'] = '';
                                    $sku[$k]['can_be_choose'] = 1;
                                }
                            }
                        } else {
                            $sku[$k]['act_name'] = '';
                            $sku[$k]['can_be_choose'] = 1;
                        }
                        if ($v['stock_num'] === 0) {
                            $sku[$k]['can_be_choose'] = 0;
                        }
                    }
                    //选sku的商品不需要活动名称
                    unset($allGoods[$key]['act_name']);
                    //unset($allGoods[$key]['can_be_choose']);
                    //单规格商品sku能否被选择=sku能否被选择
                } elseif ($val['goods_type'] == 'spu') {
                    $sku[0]['image'] = replace_file_domain($val['image']);
                    $sku[0]['can_be_choose'] = $val['can_be_choose'];
                }
                $allGoods[$key]['sku_info'] = $sku;
            }
        }
        return $allGoods;
    }

    public function getSpuActList($allGoods, $param)
    {
        //被新建的活动的等级
        $l_act = $this->getLevel($param['type']);
        //参与活动的spu信息
        if (!isset($param['goods_id'])) {
            $spu_where = [['a.status', '<>', 2], ['a.is_del', '=', 0], ['a.store_id', '=', $param['store_id']]];
        } else {
            $spu_where = [['a.status', '<>', 2], ['a.is_del', '=', 0], ['a.store_id', '=', $param['store_id']]];
        }
        //时间处理
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //所有正在参加活动的商品（不含周期购）
        $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        //正在参加周期购的活动的商品
        $spu_periodic = $this->getPeriodicList($param['store_id']);
        $spu = array_merge($spu, $spu_periodic);
        if (!empty($allGoods)) {
            foreach ($allGoods as $key => $val) {
                $allGoods[$key]['act_name'] = '';
                $allGoods[$key]['can_be_choose'] = 1;
                if (!isset($param['goods_id'])) {
                    if (!empty($spu)) {
                        foreach ($spu as $v) {
                            $flag = 0;
                            //判断在新建活动的时间范围内该商品是否正在参加活动
                            if ($v['goods_id'] == $val['goods_id']) {
                                //非周期购求时间交集
                                if ($param['type'] != 'periodic') {
                                    $flag = is_time_cross($param['start_time'], $param['end_time'], $v['start_time'], $v['end_time']);
                                } else {
                                    $flag = 1;  //新建的是周期购活动 ，其他所有正在参加活动的商品都不能选择了
                                }
                                //周期购根据状态判断
                                if ($flag || $v['type'] == 'periodic') {
                                    $allGoods[$key]['can_be_choose'] = 0;
                                }
                                $allGoods[$key]['act_name'] = $v['act_name'];
                                $allGoods[$key]['type'] = $v['type'];
                            }
                        }
                    }
                    //库存为0 不能被选
                    if ($val['stock_num'] === 0) {
                        $allGoods[$key]['can_be_choose'] = 0;
                    }
                }
                //处理图片
                $allGoods[$key]['image'] = replace_file_domain($val['image']);
            }
        }
        $arr['spu'] = $spu;
        $arr['allGoods'] = $allGoods;
        return $arr;
    }

    public function getPeriodicList($store_id)
    {
        //周期购的是商品不是sku（只要活动状态不为2就是在周期购中）
        $spu_where = [['a.type', '=', 'periodic'], ['a.status', '<>', 2], ['a.is_del', '=', 0], ['a.store_id', '=', $store_id]];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //正在参加周期购的商品
        $spu_periodic = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        return $spu_periodic;

    }

    public function getLevel($type)
    {
        //等级定义
        $param['level1'] = ['bargain', 'limited', 'group', 'prepare', 'reached', 'periodic', 'give_gift'];
        $param['level2'] = ['minus_discount'];
        $param['level3'] = ['give'];
        $param['level4'] = ['shipping'];
        if (in_array($type, $param['level1'])) {
            return 1;
        } elseif (in_array($type, $param['level2'])) {
            return 2;
        } elseif (in_array($type, $param['level3'])) {
            return 3;
        } elseif (in_array($type, $param['level4'])) {
            return 4;
        } else {
            throw new \think\Exception('该活动类型不存在');
        }
    }

    /**
     * @param $param
     * @return bool
     * @throws \think\Exception
     * 商家后台-添加商品
     */
    public function addOrEditGoods($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('参数缺失');
        }
        if (empty($param['goods_desc'])) {
            throw new \think\Exception('图文详情必填');
        }
        if (!empty($param['leave_message'])) {
            $param['leave_message'] = serialize($param['leave_message']);
        }
        else{
            $param['leave_message'] = serialize([]);   
        }
        if (!empty($param['notes'])) {
            $param['notes'] = serialize($param['notes']);
        }
        if (!empty($param['service_desc'])) {
            $param['service_desc'] = serialize($param['service_desc']);
        }else{
            $param['service_desc'] = '';
        }
        //处理视频
        $videos = [];
        if (!empty($param['videos'][0]) && !empty($param['videos'][1])) {
            $videos[0] = explode('/upload/', $param['videos'][0]) ? '/upload/' . explode('/upload/', $param['videos'][0])[1] : '';
            $videos[1] = explode('/upload/', $param['videos'][1]) ? '/upload/' . explode('/upload/', $param['videos'][1])[1] : '';
            $videos[2] = $param['videos'][2] ? $param['videos'][2] : 0;
        }
        $param['video_url'] = $param['video_url']??'';
//        $param['video_url'] = $param['video_url']?:implode(';', $videos);
        if($videos){//增加或者改动了商品视频
            $param['video_url'] = implode(';', $videos);
        }
        if($param['video_url'] && !$videos){//编辑的时候没有改动商品视频
            unset($param['video_url']);
        }
        //处理图片
        $images = [];
        if (!empty($param['images']) && $param['images'][0]!='/uploadundefined') {
            foreach ($param['images'] as $k => $v) {
                $images[$k] = explode('/upload/', $v) ? '/upload/' . explode('/upload/', $v)[1] : '';
            }
            $param['images'] = implode(';', $images); //以分号隔开
        }
        if (isset($param['image']) && $param['image']) {
            $param['image'] = parse_url($param['image'], PHP_URL_PATH);
        }
        unset($param['videos']);
        //处理商品详情
        $reg = '/src="([^"]*)"/isU';
        preg_match_all($reg, $param['goods_desc'], $matchs);
        if (!empty($matchs)) {
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                foreach ($matchs[1] as $val) {
                    $params = ['savepath' => $val];
                    invoke_cms_model('Image/oss_upload_image', $params);
                }
            }
        }
        preg_match_all($reg, $param['spec_desc'], $matchs);
        if (!empty($matchs)) {
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                foreach ($matchs[1] as $val) {
                    $params = ['savepath' => $val];
                    invoke_cms_model('Image/oss_upload_image', $params);
                }
            }
        }
        preg_match_all($reg, $param['pack_desc'], $matchs);
        if (!empty($matchs)) {
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                foreach ($matchs[1] as $val) {
                    $params = ['savepath' => $val];
                    invoke_cms_model('Image/oss_upload_image', $params);
                }
            }
        }
//        if (isset($param['goods_desc']) && stripos($param['goods_desc'], 'src="http') === false) {
//            $param['goods_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $param['goods_desc']);
//        }
//        if (isset($param['spec_desc']) && stripos($param['spec_desc'], 'src="http') === false) {
//            $param['spec_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $param['spec_desc']);
//        }
//        if (isset($param['pack_desc']) && stripos($param['pack_desc'], 'src="http') === false) {
//            $param['pack_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $param['pack_desc']);
//        }
        //库存判断
        if(!empty($param['stock_type'])){
            $param['stock_num'] = $param['stock_type']==2 ? $param['common_stock_num'] : $param['stock_num'];
        }
        $stock_num = $param['stock_num'];//商品库存
        $sku_stock_num = 0;
        if (!empty($param['list'])) {  //sku商品
            if (isset($param['common_stock_num']) && $param['common_stock_num'] > -1) {
                foreach ($param['list'] as $i) {
                    $sku_stock_num += $i['stock_num'];
                    if ($i['stock_num'] == -1 && $param['stock_type'] != 1) {
                        throw new \think\Exception('商品库存不能大于商品库库存');
                    }
                }
                if (isset($param['common_stock_num']) && $sku_stock_num > $param['common_stock_num'] && $param['stock_type'] != 1) {
                    throw new \think\Exception('商品库存不能大于商品库库存');
                }
            }
        } else {
            if (isset($param['common_stock_num']) && $param['common_stock_num'] > -1 && $param['common_stock_num']!="") {
                if ($param['stock_type'] != 1 && ($stock_num > $param['common_stock_num'] || $stock_num == -1)) {
                    throw new \think\Exception('商品库存不能大于商品库库存');
                }
            }
        }

        //库存计算
        if (!empty($param['list'])) {//多规格
            $param['stock_num'] = 0;
            if (isset($param['common_stock_num']) && $param['common_stock_num'] > -1) {
                foreach ($param['list'] as $i) {
                    $param['stock_num'] += $i['stock_num'];
                }
            } else {
                foreach ($param['list'] as $i) {
                    $param['stock_num'] += $i['stock_num'];
                    if ($i['stock_num'] == -1) {
                        $param['stock_num'] = -1;
                        break;
                    }
                }
            }
            $price = array_column($param['list'], 'price');
            $param['max_price'] = max($price);
            $param['min_price'] = min($price);
            //该商品是sku时price字段存入min_price的值
            $param['goods_type'] = 'sku';
            $param['price'] = $param['min_price'];
        } else {
            //单规格最高价和最低价都存入price
            $param['max_price'] = $param['price'];
            $param['min_price'] = $param['price'];
            $param['goods_type'] = 'spu';
        }
        $spec_list = $param['spec_list'];
        $list = $param['list'];
        $from = !empty($param['from']) ? $param['from'] : 0;
        unset($param['list']);
        unset($param['spec_list']);
        if(isset($param['common_stock_num'])){
            unset($param['common_stock_num']);
        }
        unset($param['from']);

        //查询是否需要修改审核状态
        if(customization('open_scenic_sports_mall_shop_audit') == 1){
            if(cfg('mall_goods_audit_type') === '0'){
                $param['audit_status'] = 1;
                $param['status'] = 1;
            }else{//手动审核
                $param['audit_status'] = 0;
                $param['status'] = 0;
            }
            $param['add_audit_time'] = time();
        }else{
            $param['audit_status'] = 1;
        }

        $sku_service = new MallGoodsSkuService();
        if(empty($param['goods_id']) || !empty($param['sort_id'])){
            if(!$param['sort_id']){
                throw new \think\Exception('请选择商品分类');
            }
            $sortInfo = (new MallGoodsSort())->where(['id'=>$param['sort_id']])->field('id')->find();
            if(!$sortInfo){
                throw new \think\Exception('商品分类不存在或者已删除，请重新选择商品分类');
            }
            //检查分类id是否是最上层id
            $sortNextCount = (new MallGoodsSort())->where(['fid'=>$param['sort_id'],'status'=>1])->count();
            if($sortNextCount > 0){
                throw new \think\Exception('商品分类请选择子分类');
            }
        }
        if(array_key_exists('area_id',$param)){
            unset($param['area_id']);
        }
        if(array_key_exists('areaParam',$param) && cfg('open_mall_order_area') !=1){
            unset($param['areaParam']);
        }
        Db::startTrans();
        try {
            if (!empty($param['goods_id'])) {
                if($param['fright_id'] > 0){
                    $where=[['id','=',$param['fright_id']]];
                    $msg=(new ExpressTemplate())->getOne($where);
                    if(empty($msg)){
                        $param['fright_id']=0;
                        //throw new \think\Exception("运费模板已经删除，请重新选择运费模板");
                    }
                }
                //降价通知推送消息
                //原本价格
                $originGoods = (new MallGoods())->getOne($param['goods_id']);
                //编辑
                $param['update_time'] = time();
                //编辑商品
                if(!empty($param['areaParam'])){
                    $param['areaParam']['goods_id']=array($param['goods_id']);
                    $param['areaParam']['store_id'] = $param['areaParam']['store_id']??$param['store_id'];
                    (new MallGoodsAreaService())->addOneMallGoodsArea($param['areaParam']);
                }elseif(isset($param['areaParam'])&&is_array($param['areaParam'])){
                    (new MallGoodsArea())->where(['goods_id'=>$param['goods_id']])->delete();
                }
                unset($param['areaParam']);
                unset($param['area_id']);
                $where = ['goods_id' => $param['goods_id']];
                $mallGoods = $this->MallGoodsModel->where($where)->find();
                $mallGoods->save($param);
                //编辑sku
                if ($from == 1) {
                    $this->dealSkuAndSpec($spec_list, $list, $param['goods_id'], $param['store_id'], $param['price'], '', $param['stock_num']);
                } else {
                    if (!empty($list)) {
                        foreach ($list as $k => $v) {
                            $originSku = (new MallGoodsSku())->getOne(['sku_id' => $v['sku_id']]);
                            $where = ['sku_id' => $v['sku_id'], 'goods_id' => $v['goods_id']];
                            $data = ['price' => $v['price'], 'stock_num' => $v['stock_num'], 'image' => $v['image']];
                            $sku_service->setSku($where, $data);
                            //多规格商品降价通知推送消息
                            if (!empty($originSku) && $originSku['price'] > $v['price']) {
                                //获取要推送的uid
                                $notice = (new MallPriceNotice())->getSome(['goods_id' => $param['goods_id']]);
                                if (!empty($notice)) {
                                    foreach ($notice as $item) {
                                        (new SendTemplateMsgService())->sendWxappMessage(['type' => 'reduce_success', 'goods_name' => $originGoods['name'] . ' ' . $originSku['sku_str'], 'origin_price' => $originSku['price'], 'now_price' => $v['price'], 'uid' => $item['uid'],'goods_id'=>$item['goods_id']]);
                                    }
                                }
                            }
                        }
                    } else {
                        $where = ['goods_id' => $param['goods_id']];
                        $data = ['price' => $param['price'], 'stock_num' => $param['stock_num'], 'image' => $param['image']];
                        $sku_service->setSku($where, $data);
                        //单规格商品降价通知推送消息
                        if (!empty($originGoods) && $originGoods['price'] > $param['price']) {
                            //获取要推送的uid
                            $notice = (new MallPriceNotice())->getSome(['goods_id' => $param['goods_id']]);
                            if (!empty($notice)) {
                                foreach ($notice as $item) {
                                    (new SendTemplateMsgService())->sendWxappMessage(['type' => 'reduce_success', 'goods_name' => $originGoods['name'], 'origin_price' => $originGoods['price'], 'now_price' => $param['price'], 'uid' => $item['uid']]);
                                }
                            }
                        }
                    }
                }
            } else {
                //添加
                //添加商品
                unset($param['goods_id']);
                $param['create_time'] = time();
                $param['update_time'] = time();
                $areaParam=$param['areaParam']??'';
                unset($param['areaParam']);
                unset($param['area_id']);
                $goods_id=$param['goods_id'] = $this->MallGoodsModel->addOne($param);
                if($goods_id && !empty($areaParam)){
                    $areaParam['goods_id']=$areaParam['goods_ids']=array($goods_id);
                    (new MallGoodsAreaService())->addOneMallGoodsArea($areaParam);
                }

                //添加sku
                $this->dealSkuAndSpec($spec_list, $list, $param['goods_id'], $param['store_id'], $param['price'], $param['image'], $param['stock_num']);
                $goods_area=(new MallGoodsArea())->count();
                if(!empty($goods_area)){
                    $goods_area_list=(new MallGoodsArea())->getSome(['goods_id'=>$param['goods_id']])->toArray();
                    if(empty($goods_area_list)){
                        (new MallGoodsArea())->add(array('goods_id' =>$param['goods_id'], 'area_id' => 0, 'all_area_id' => 0, 'level' => 0, 'store_id' => $param['store_id'], 'set_type' => 0, 'create_time' => time()));
                    }
                }else{
                    $goods_list=(new MallGoods())->getAllList([['status','=',1],['is_del','=',1]]);
                    if(!empty($goods_list)){
                        foreach ($goods_list as $k=>$v){
                            (new MallGoodsArea())->add(array('goods_id' =>$v['goods_id'], 'area_id' => 0, 'all_area_id' => 0, 'level' => 0, 'store_id' => $v['store_id'], 'set_type' => 0, 'create_time' => time()));
                        }
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @throws Exception
     * 处理sku 和 spec
     */
    public function dealSkuAndSpec($spec_list, $list, $goods_id, $store_id, $price, $image, $stock_num)
    {
        if (strpos($image, ';') !== false) {
            $image = explode(';', $image)[0];
        }
        $specModel = new MallGoodsSpecService();
        $specValModel = new MallGoodsSpecValService();
        $limitedSkuModel = new MallLimitedSku();
        $limitedList = $limitedSkuModel->where(['goods_id' => $goods_id])->field('sku_id,act_id,act_price')->select();
        $limitedSku  = [];
        $limitedAct  = [];
        if (!empty($limitedList)) {
            $limitedList = $limitedList->toArray();
            $limitedSku  = array_column($limitedList, 'sku_id');
            $limitedSku  = array_unique($limitedSku);
            $limitedAct  = array_column($limitedList, 'act_id');
            $limitedAct  = array_unique($limitedAct);
        }
        $specModel->delSome(['goods_id' => $goods_id]);
        //添加
        $sku_value_index = []; //新规格属性id
        $sku_value_sids = []; //新规格属性对应新规格id
        if (!empty($spec_list)) {
            foreach ($spec_list as $key => $val) {
                //添加spec
                $data_spec = ['name' => $val['name'], 'goods_id' => $goods_id, 'create_time' => time(),'sync_spec_id'=>$val['id']];
                $spec_id = (new MallGoodsSpec())->insertGetId($data_spec);
                //添加specval
                $specValModel->delSome(['spec_id' => $spec_id]);
                foreach ($val['list'] as $kk => $vv) {
                    $spec_val_data = ['name' => $vv['name'], 'spec_id' => $spec_id, 'create_time' => time(),'sync_val_id'=>$vv['id']];
                    $valId = $specValModel->insertGetId($spec_val_data);
                    $sku_info[$vv['sid']][] = $vv['sid'] . ':' . $vv['id'];
                    $sku_value_index[$key][$vv['id']] = $valId;
                    $sku_value_sids[$vv['id']] = $spec_id;
                }
            }
        }
        $sku_new_index = [];
        if(!empty($sku_value_index[0])){
            foreach ($sku_value_index[0] as $f_k=>$f_v){
                if(!empty($sku_value_index[1])){
                    foreach ($sku_value_index[1] as $s_k=>$s_v){
                        if(!empty($sku_value_index[2])){
                            foreach ($sku_value_index[2] as $t_k=>$t_v){
                                $sku_new_index[$f_k.'_'.$s_k.'_'.$t_k] = $f_v.'_'.$s_v.'_'.$t_v;
                            }
                        }else{
                            $sku_new_index[$f_k.'_'.$s_k] = $f_v.'_'.$s_v;
                        }
                    }
                }else{
                    $sku_new_index[$f_k] = $f_v;
                }
            }
        }
        $sku_service = new MallGoodsSkuService();
        $skuList = $sku_service->getSkuByGoodsId($goods_id);
        $sku_service->delSome(['goods_id' => $goods_id, 'store_id' => $store_id]);
        if (!empty($spec_list) && !empty($list)) {
            foreach ($list as $l_key=>$l_val){
                if(isset($sku_new_index[$l_val['index']])){
                    $l_spec = explode('_',$sku_new_index[$l_val['index']]);
                    $list[$l_key]['index'] = $sku_new_index[$l_val['index']];
                    foreach ($l_val['spec'] as $s_key=>$s_val){
                        $list[$l_key]['spec'][$s_key]['spec_val_sid'] = $sku_value_sids[$s_val['spec_val_id']] ?? $s_val['spec_val_sid'];
                        $list[$l_key]['spec'][$s_key]['spec_val_id'] = $l_spec[$s_key] ?? $s_val['spec_val_id'];
                    }
                }
            }
//            //删除原先的sku信息
//            $where = ['goods_id' => $goods_id];
//            $sku_service->delSome($where);
            $skuIds = [];
            //添加sku
            foreach ($list as $k => $v) {
                $info = '';
                $str = '';
                //处理图片
                if (isset($v['reg_img']['image']) && !empty($v['reg_img']['image'])) {
                    //去域名
                    $reg_img = explode('/upload/', $v['reg_img']['image']);
                    if (isset($reg_img[1])) {
                        $reg_img = $reg_img[1] ? '/upload/' . $reg_img[1] : $image;
                    } else {
                        $reg_img = $image;
                    }
                }elseif (isset($v['image']) && !empty($v['image'])){
                   //去域名
                    $reg_img = explode('/upload/', $v['image']);
                    if (isset($reg_img[1])) {
                        $reg_img = $reg_img[1] ? '/upload/' . $reg_img[1] : $image;
                    } else {
                        $reg_img = $image;
                    }
                } else {
                    //没上传默认首图
                    $reg_img = $image;
                }
                $data = ['goods_id' => $goods_id, 'store_id' => $store_id, 'image' => $reg_img, 'price' => $v['price'], 'stock_num' => $v['stock_num'], 'create_time' => time()];
                foreach ($list[$k]['spec'] as $item) {
                    $str .= $item['spec_val_name'] . ',';
                    $info .= $item['spec_val_sid'] . ':' . $item['spec_val_id'] . '|';
                }
                $data = array_merge($data, ['sku_str' => rtrim($str, ','), 'sku_info' => rtrim($info, '|')]);
                $sku_id = $sku_service->addOne($data);

                if (!empty($limitedSku)) {
                    if (!empty($skuList)) {
                        foreach ($skuList as $sv) {
                            if ($sv['sku_info'] == $data['sku_info']) {
                                $skuIds[] = $sku_id;
                                $upData = [
                                    'sku_id' => $sku_id,
                                    'act_stock_num' => $v['stock_num'],
                                ];
                                if ($sv['price'] != $v['price']) {
                                    $upData['act_price'] = $v['price'];
                                    $upData['discount_rate'] = 10;
                                    $upData['reduce_money'] = 0;
                                }
                                $limitedSkuModel->where(['sku_id' => $sv['sku_id'], 'goods_id' => $goods_id])->update($upData);

                                //更新购物车
                                $cartList = (new MallCartNew())->getSome(['sku_id' => $sv['sku_id'], 'goods_id' => $goods_id, 'is_del' => 0]);
                                if ($cartList) {
                                    $cartList = $cartList->toArray();
                                    $skuNew = (new MallGoodsSkuService())->getSkuById($sku_id);//sku信息
                                    $cartIds = array_column($cartList, 'id');
                                    (new MallCartNew())->updateThis([['id', 'in', $cartIds]], [
                                        'sku_id' => $sku_id,
                                        'sku_info' => $skuNew['sku_str'],
                                        'join_price' => $skuNew['price'],
                                    ]);
                                }
                            }
                        }
                    }
                    if (!in_array($sku_id, $skuIds)) {
                        foreach ($limitedAct as $actv) {
                            $limitedSkuModel->insert([
                                'act_id' => $actv,
                                'goods_id' => $goods_id,
                                'sku_id' => $sku_id,
                                'act_stock_num' => $v['stock_num'],
                                'act_price' => $v['price'],
                                'discount_rate' => 10,
                                'reduce_money' => 0
                            ]);
                        }
                        $skuIds[] = $sku_id;
                    }
                }
            }
            !empty($limitedSku) && $limitedSkuModel->where([['goods_id', '=', $goods_id], ['sku_id', 'not in', $skuIds]])->delete();
        } else {
            //spu商品在新增时添加一条空白的sku信息
            $data = ['goods_id' => $goods_id, 'store_id' => $store_id, 'image' => $image, 'price' => $price, 'stock_num' => $stock_num, 'create_time' => time()];
            $sku_id = $sku_service->addOne($data);

            if (!empty($limitedSku)) {
                $limitedSkuModel->where([['goods_id', '=', $goods_id], ['sku_id', 'in', $limitedSku]])->delete();
                foreach ($limitedAct as $actv) {
                    $limitedSkuModel->insert([
                        'act_id' => $actv,
                        'goods_id' => $goods_id,
                        'sku_id' => $sku_id,
                        'act_stock_num' => $data['stock_num'],
                        'act_price' => $data['price'],
                        'discount_rate' => 10,
                        'reduce_money' => 0
                    ]);
                }
            }
        }
        return true;
    }

    /**
     ** 实现二维数组的笛卡尔积组合
     ** $arr 要进行笛卡尔积的二维数组
     ** $str 最终实现的笛卡尔积组合,可不写
     **/
    public function cartesian($arr, $str = array())
    {
        //去除第一个元素
        $first = array_shift($arr);
        //判断是否是第一次进行拼接
        if (count($str) > 1) {
            foreach ($str as $k => $val) {
                foreach ($first as $key => $value) {
                    //可根据具体需求进行变更
                    $str2[] = $val . ',' . $value;
                }
            }
        } else {
            foreach ($first as $key => $value) {
                //可根据具体需求进行变更
                $str2[] = $value;
            }
        }
        //递归进行拼接
        if (count($arr) > 0) {
            $str2 = $this->cartesian($arr, $str2);
        }
        //返回最终笛卡尔积
        return $str2;
    }

    /**
     * @param $goods_id
     * @return array
     * @throws \think\Exception
     * 获取某条商品编辑时的数据
     */
    public function getEditGoods($goods_id)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('goods_id参数缺失');
        }
        $arr = $this->MallGoodsModel->getOne($goods_id);
        if (!empty($arr)) {
            //获取运费名称
            $fright = (new ExpressTemplateService())->getETById($arr['fright_id']);
            $cat_spec_val_temp = [];
            if(strpos($arr['spec_val_ids'], ':')){
                $cat_spec_val = explode('|', $arr['spec_val_ids']);
                foreach ($cat_spec_val as $cat_spec_val_v) {
                    $cat_spec_val_temp[] = ['cat_spec_id'=>explode(':', $cat_spec_val_v)[0], 'id'=>explode(':', $cat_spec_val_v)[1]];
                }
            }
            if ($arr['virtual_set'] == 1) {
                $arr['virtual_sales'] = $arr['plat_virtual_sales'];
            }
            $arr['cat_spec_val'] = $cat_spec_val_temp;
            $arr['fright_name'] = $fright ? $fright['name'] : '';
            //获取平台分类信息
            $catServe = new MallCategoryService();
            $arr['cate_first_name'] = $arr['cate_first'] && $catServe->getEditCategory($arr['cate_first']) ? $catServe->getEditCategory($arr['cate_first'])['cat_name'] : '';
            $arr['cate_second_name'] = $arr['cate_second'] && $catServe->getEditCategory($arr['cate_second']) ? $catServe->getEditCategory($arr['cate_second'])['cat_name'] : '';
            $arr['cate_third_name'] = $arr['cate_three'] && $catServe->getEditCategory($arr['cate_three']) ? $catServe->getEditCategory($arr['cate_three'])['cat_name'] : '';
            //获取店铺分类信息
            $sortServe = new MallGoodsSortService();
            $arr['sort_first_name'] = $arr['sort_first'] && $sortServe->getEditSort($arr['sort_first']) ? $sortServe->getEditSort($arr['sort_first'])['list']['name'] : '';
            $arr['sort_second_name'] = $arr['sort_second'] && $sortServe->getEditSort($arr['sort_second']) ? $sortServe->getEditSort($arr['sort_second'])['list']['name'] : '';
            $arr['sort_third_name'] = $arr['sort_third'] && $sortServe->getEditSort($arr['sort_third']) ? $sortServe->getEditSort($arr['sort_third'])['list']['name'] : '';
            $arr['image'] = $arr['image'] ? replace_file_domain($arr['image']) : '';
            $arr['like_image'] = $arr['like_image'] ? replace_file_domain($arr['like_image']) : '';
            $arr['video_url'] = $arr['video_url'] ? replace_file_domain(explode(';', $arr['video_url'])[0]) : '';
            $images = explode(';', $arr['images']);
            $images_url = [];
            if (!empty($images)) {
                foreach ($images as $key => $val) {
                    $images_url[] = $val ? replace_file_domain($val) : '';
                }
            }
            $arr['goods_desc'] = replace_file_domain_content($arr['goods_desc']);
            $arr['spec_desc'] = replace_file_domain_content($arr['spec_desc']);
            $arr['pack_desc'] = replace_file_domain_content($arr['pack_desc']);
//            if (stripos($arr['goods_desc'], 'src="http') === false) {
//                $arr['goods_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $arr['goods_desc']);
//            }
//            if (stripos($arr['spec_desc'], 'src="http') === false) {
//                $arr['spec_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $arr['spec_desc']);
//            }
//            if (stripos($arr['pack_desc'], 'src="http') === false) {
//                $arr['pack_desc'] = str_replace('/upload/ueditor/', cfg('site_url') . '/upload/ueditor/', $arr['pack_desc']);
//            }
            $arr['leave_message'] = unserialize($arr['leave_message']);
            $arr['notes'] = unserialize($arr['notes']);
            $arr['service_desc'] = unserialize($arr['service_desc']);
            $arr['images'] = $images_url;
            //获取公共商品库库存
            $commonGoodsInfo = (new ShopGoodsService())->getGoodsByGoodsId($arr['common_goods_id']);
            $arr['common_stock_num'] = $commonGoodsInfo['stock_num']??'';
            $arr['shop_name'] = $commonGoodsInfo['name']??'';
//            $arr['common_stock_num'] = (new ShopGoodsService())->getGoodsByGoodsId($arr['common_goods_id']) ? (new ShopGoodsService())->getGoodsByGoodsId($arr['common_goods_id'])['stock_num'] : '';
            //获取sku信息
            $sku_list = [];
            if ($arr['goods_type'] == 'sku') {
                $sku_info = $this->getGoodsSkuInfo($goods_id);
                if (!empty($sku_info)) {
                    foreach ($sku_info as $key => $val) {
                        $sku = explode('|', $val['sku_info']);
                        $sku_str = explode(',', $val['sku_str']);
                        $spec = [];
                        if (!empty($sku)) {
                            foreach ($sku as $k => $v) {
                                $ids = explode(':', $v);
                                if (!empty($ids) && !empty($sku_str)) {
                                    if (isset($ids[1]) && isset($ids[0])) {
                                        $spec[$k] = ['spec_val_id' => $ids[1], 'spec_val_sid' => $ids[0], 'spec_val_name' => $sku_str[$k]];
                                        $sid = $ids[0];
                                        $name = $sku_str[$k];
                                        $sku_info[$key]['spec_val_sid_' . $sid] = $name;
                                    }
                                }
                            }
                        }
                        $sku_info[$key]['reg_img'] = $val['image'] ? replace_file_domain($val['image']) : '';
                        unset($sku_info[$key]['sku_info']);
                        unset($sku_info[$key]['sku_str']);
                        if (!empty($spec)) {
                            $sku_list[] = $sku_info[$key];
                        }
                    }
                }
            }
            $arr['list'] = $sku_list;
            $arr['spec_list'] = $this->getSpecList($goods_id);
            $arr['audit_status_text'] = $this->auditStatusMap($arr['audit_status']);
            return $arr;
        } else {
            throw new \think\Exception('该商品不存在');
        }

    }

    public function auditStatusMap($status)
    {
        return $this->MallGoodsModel->auditStatusMap[$status];
    }

    /**
     * @param $param
     * @return mixed
     * @throws \think\Exception
     * 获取商家某个店铺的商品列表
     */
    public function getMerchantGoodsList($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('store_id参数缺失');
        }
        if (!empty($param['sort_id'])) {
            $where = [['sort_id|sort_first|sort_second|sort_third', '=', $param['sort_id']], ['mer_id', '=', $param['mer_id']], ['store_id', '=', $param['store_id']], ['is_del', '=', 0]];
        }
        if (!empty($param['keyword'])) {
            $where = [['name', 'like', '%' . $param['keyword'] . '%'], ['mer_id', '=', $param['mer_id']], ['store_id', '=', $param['store_id']], ['is_del', '=', 0]];
        }
        // 搜索审核状态
        if(isset($param['audit_status']) && !is_null($param['audit_status']) && is_numeric($param['audit_status'])){
            $where[] = ['audit_status', '=', $param['audit_status']];
        }
        switch ($param['search_type']) {
            case 1:
                break;
            case 2:
                array_push($where, ['status', '=', 1]);
                break;
            case 3:
                array_push($where, ['stock_num', '<', 10], ['stock_num', '>', '-1']);
                break;
            case 4:
                array_push($where, ['status', '=', 0]);
                break;
        }
        $field = 'goods_id,name,max_price,min_price,price,stock_num,image,status,sort_store,update_time,goods_type,sale_num,virtual_sales,plat_virtual_sales,virtual_set,browse_num,audit_status,audit_msg,add_audit_time,brand_id';
        $order = ['sort_store' => 'DESC', 'update_time' => 'DESC'];
        $arr = $this->MallGoodsModel->getGoodsByCondition($field, $order, $where, $param['page'], $param['pageSize']);
        $count = $this->MallGoodsModel->getGoodsByConditionCount($where);
        //销量
        $orderService = new MallOrderService();
        foreach ($arr as $key => $item) {
            $arr[$key]['update_time'] = date('Y-m-d H:i:s', $item['update_time']);
            $arr[$key]['image'] = replace_file_domain($item['image']);
            //总销量
            $where_all = ['d.goods_id' => $item['goods_id']];
            $all_sale_number = $orderService->getTodaySaleNum($where_all);
            $arr[$key]['all_sale_number'] = $item['sale_num'];
            //今日销量
            $todayStart = strtotime(date('Y-m-d 00:00:00', time()));
            $todayEnd = strtotime(date('Y-m-d 23:59:59', time()));
            $where_today = [['d.goods_id', '=', $item['goods_id']], ['o.create_time', '>', $todayStart], ['o.create_time', '<', $todayEnd],['o.pay_time','>',0]];
            $today_sale_number = $orderService->getTodaySaleNum($where_today);//今天支付成功的所有数量
            $today_sale_number += $orderService->getTodaySaleNum([['d.goods_id', '=', $item['goods_id']], ['o.create_time', '>', $todayStart], ['o.create_time', '<', $todayEnd],['o.status','<',10]]);//今天待支付的所有数量
            $today_refund_number = $orderService->getTodayRefundNum([['d.goods_id', '=', $item['goods_id']], ['o.create_time', '>', $todayStart], ['o.create_time', '<', $todayEnd],['f.status','=',1]]);//今天支付后退款的所有数量
            $arr[$key]['today_sale_number'] = $today_sale_number-$today_refund_number;
            //获取商品id的二维码
            $arr[$key]['qrcode'] = $this->createGoodsQrcode($item['goods_id']);
            //单品销量
            $browse_num = (int)$arr[$key]['browse_num'];
            $arr[$key]['browse_num'] = $browse_num >= 10000 ? round(($browse_num / 10000), 1) . '万' : $browse_num;
            if ($item['virtual_set'] == 1) {
                $arr[$key]['virtual_sales'] = $item['plat_virtual_sales'];
            }

            $arr[$key]['add_audit_time'] = $item['add_audit_time'] ? date('Y-m-d H:i:s', $item['add_audit_time']) : '';
            $arr[$key]['audit_status_text'] = $this->auditStatusMap($arr[$key]['audit_status']);
            $arr[$key]['brand_name'] = '';
            if($item['brand_id'] > 0){
                $arr[$key]['brand_name'] = MallProductBrands::where(['id' => $item['brand_id'],'status'=>1])->value('name');
            }
        }
        $list['list'] = $arr;
        $list['total'] = $count;
        return $list;
    }
 

    /**
     * @param $goods_id
     * @return string
     * 获取商品二维码
     */
    public function createGoodsQrcode($goods_id)
    {
        if (empty($goods_id)) return '';
        $dir = '/runtime/qrcode/mall/' . $goods_id;
        $path = '../..' . $dir;
        $filename = md5($goods_id) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return cfg('site_url') . $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $goods_id;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return cfg('site_url') . $dir . '/' . $filename;
    }

    public function getSpecList($goods_id)
    {
        $field_spec = 'gs.goods_id,gs.spec_id as id,gs.name';
        $spec_where = ['gs.goods_id' => $goods_id];
        $spec = (new MallGoodsSpec())->getSpec($field_spec, $spec_where);
        $specValService = new MallGoodsSpecVal();
        if (!empty($spec)) {
            foreach ($spec as $key => $val) {
                $spec_val = $specValService->getSpecValueBySid([$val['id']]);
                if (!empty($spec_val)) {
                    foreach ($spec_val as $v) {
                        $spec[$key]['list'][$v['id']] = $v;
                    }
                }

            }
        }
        return $spec;
    }

    public function getAllGoods($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id 参数');
        }
        $field = 'goods_id,mer_id,stock_num,store_id,name as goods_name,price,min_price,max_price,cate_first,cate_second,cate_three,sort_first,sort_second,sort_third';
        $where = ['store_id' => $param['store_id'], 'mer_id' => $param['mer_id']];
        $order = ['sort_store' => 'DESC', 'update_time' => 'DESC'];
        $arr = $this->MallGoodsModel->getAllGoods($field, $order, $where);
        if (!empty($arr)) {
            //分类
            $catServe = new MallCategoryService();
            $sortServe = new MallGoodsSortService();
            $orderService = new MallOrderService();
            $sukService = new MallGoodsSkuService();
            $storeService = new MerchantStoreMallService();
            //配送方式（商品走店铺的配送方式）
            $delivery_type = $storeService->getStoremallInfo($param['store_id'], 'is_delivery,is_houseman,is_zt');
            $delivery = '';
            if ($delivery_type['is_delivery'] == 1) {
                $delivery .= '快递配送，';
            }
            if ($delivery_type['is_houseman'] == 1) {
                $delivery .= '骑手配送，';
            }
            if ($delivery_type['is_zt'] == 1) {
                $delivery .= '自提';
            }
            $delivery = rtrim($delivery, '，');
            $goodsSortMod = new \app\mall\model\db\MallGoodsSort();
            foreach ($arr as $key => $val) {
                $arr[$key]['plat_cate'] = '';
                $arr[$key]['store_sort'] = '';
                //获取平台分类信息
                $val['cate_first_name'] = $val['cate_first'] && $catServe->getEditCategory($val['cate_first']) ? $catServe->getEditCategory($val['cate_first'])['cat_name'] . '/' : '';
                $val['cate_second_name'] = $val['cate_second'] && $catServe->getEditCategory($val['cate_second']) ? $catServe->getEditCategory($val['cate_second'])['cat_name'] . '/' : '';
                $val['cate_third_name'] = $val['cate_three'] && $catServe->getEditCategory($val['cate_three']) ? $catServe->getEditCategory($val['cate_three'])['cat_name'] : '';
                $arr[$key]['plat_cate'] = $val['cate_first_name'] . $val['cate_second_name'] . $val['cate_third_name'];
                //获取店铺分类信息
                $val['sort_first_name'] = $val['sort_first'] && $sortServe->getEditSort($val['sort_first'])['list'] ? $sortServe->getEditSort($val['sort_first'])['list']['name'] . '/' : '';
                $val['sort_second_name'] = $val['sort_second'] && $sortServe->getEditSort($val['sort_second'])['list'] ? $sortServe->getEditSort($val['sort_second'])['list']['name'] . '/' : '';
                $val['sort_third_name'] = $val['sort_third'] && $sortServe->getEditSort($val['sort_third'])['list'] ? $sortServe->getEditSort($val['sort_third'])['list']['name'] : '';
                $arr[$key]['store_sort'] = $val['sort_first_name'] . $val['sort_second_name'] . $val['sort_third_name'];
                //总销量
                $where_all = ['d.goods_id' => $val['goods_id']];
                $all_sale_number = $orderService->getTodaySaleNum($where_all);
                $arr[$key]['all_sale_number'] = $all_sale_number;
                //获取规格信息
                $sku_where = ['goods_id' => $val['goods_id']];
                $sku = $sukService->getSkuByCondition('sku_str', $sku_where);
                $arr[$key]['sku_str'] = '';
                foreach ($sku as $v) {
                    $arr[$key]['sku_str'] .= $v['sku_str'] . '/';
                }
                $arr[$key]['sku_str'] = rtrim($arr[$key]['sku_str'], '/');
                //配送方式
                $arr[$key]['delivery_type'] = $delivery;
                //商家名
                $arr[$key]['mer_name'] = $param['mer_name'];
                //处理价格
                if (empty($val['price'])) {
                    $arr[$key]['price'] = $val['min_price'] . '-' . $val['max_price'];
                }
            }
            return $arr;
        } else {
            return [];
        }

    }

    /**
     * @param $goods_id
     * @return mixed
     * @throws \think\Exception
     * 获取商品库信息
     */
    public function getGoodsLibInfo($goods_id)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('goods_id参数缺失');
        }
        $list = (new shopGoodsService)->getGoodsLibInfo($goods_id);
        return $list;
    }

    /**
     * @param $goods_ids
     * @return bool
     * @throws \think\Exception
     * 删除或批量删除(删除商品 删除sku 删除spec 删除specValue)
     */
    public function delGoods($goods_ids)
    {
        if (empty($goods_ids)) {
            throw new \think\Exception('goods_ids参数缺失');
        }
        //判断能否被删除
        $undeletableIds = $this->judgeDeleted($goods_ids);
        $undeletableNums = 0;
        $skuService = new MallGoodsSkuService();
        $specService = new MallGoodsSpecService();
        $specValService = new MallGoodsSpecValService();
        Db::startTrans();
        try {
            foreach ($goods_ids as $goods_id) {
                if (in_array($goods_id, $undeletableIds)) {
                    $undeletableNums++;
                    continue;
                }
                //删除商品
                $this->MallGoodsModel->delOne($goods_id);
                //删除sku
                $skuService->delSome(['goods_id' => $goods_id]);
                //删除规格值
                //获取被删除的规格id
                $spec_ids = $specService->getList($goods_id);
                if (!empty($spec_ids)) {
                    foreach ($spec_ids as $val) {
                        $specValService->delSome(['spec_id' => $val['spec_id']]);
                    }
                }
                //删除规格
                $specService->delSome(['goods_id' => $goods_id]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        if ($undeletableNums) {
            return $undeletableNums;
        } else {
            return true;
        }
    }

    public function judgeDeleted($goods_ids)
    {
        $mallActivityService = new MallActivityService();
        $ids = array();
        $ids1 = array();
        $ids2 = array();
        if (!empty($goods_ids)) {
            foreach ($goods_ids as $goods_id) {
                //普通活动
                $where1 = [['d.goods_id', '=', $goods_id], ['a.status', '<>', '2'], ['a.end_time', '>', time()]];
                if ($mallActivityService->getGoodsInAct($where1, 'd.goods_id')) {
                    $ids1[] = $mallActivityService->getGoodsInAct($where1, 'd.goods_id')[0]['goods_id'];
                } else {
                    //满赠赠品判断
                    $where3 = [['g.goods_id', '=', $goods_id], ['a.status', '<>', '2'], ['a.end_time', '>', time()], ['a.type', '=', 'give']];
                    if ((new MallFullGiveGiftSku())->getGiftInfo2('g.goods_id', $where3)) {
                        $ids2[] = (new MallFullGiveGiftSku())->getGiftInfo2('g.goods_id', $where3)[0]['goods_id'];
                    }
                }
                $ids = array_merge($ids1, $ids2);
                //周期购活动
                $where2 = [['d.goods_id', '=', $goods_id], ['a.status', '<>', '2'], ['a.type', '=', 'periodic']];
                if ($mallActivityService->getGoodsInAct($where2, 'd.goods_id')) {
                    $ids[] = $mallActivityService->getGoodsInAct($where2, 'd.goods_id')[0]['goods_id'];
                }
            }
        }
        return $ids;
    }

    /**
     * @param $goods_ids
     * @param $status
     * @return bool
     * @throws \think\Exception
     * 设置或批量设置上下架
     */
    public function setStatusLot($goods_ids, $status)
    {
        if (empty($goods_ids)) {
            throw new \think\Exception('goods_ids参数缺失');
        }
        //判断能否被下架
        if (!$status) {
            $undeletableIds = $this->judgeDeleted($goods_ids);
        }

        //判断是否有审核未通过的商品
        if($status){
            $goodsInfo = $this->MallGoodsModel->getGoodsAuditInfo($goods_ids);
            if($goodsInfo){
                throw new \think\Exception('选择的商品包含了审核未通过或者待审核的商品');
            }
        }
        $undeletableNums = 0;
        foreach ($goods_ids as $goods_id) {
            $where = ['goods_id' => $goods_id,];
            $data = ['status' => $status, 'update_time' => time()];
            if (!$status) {
                if (in_array($goods_id, $undeletableIds)) {
                    $undeletableNums++;
                    continue;
                }
            }
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        if ($undeletableNums) {
            return $undeletableNums;
        } else {
            return true;
        }
    }

    /**
     * @param $goods_ids
     * @param $sales
     * @return bool
     * @throws \think\Exception
     * 批量设置虚拟销量
     */
    public function setVirtualSalesLot($goods_ids, $sales)
    {
        if (empty($goods_ids)) {
            throw new \think\Exception('goods_ids参数缺失');
        }
        $virtualSetNums = 0;//平台设置了虚拟销量，商家不可设置
        foreach ($goods_ids as $goods_id) {
            $goodsData = $this->MallGoodsModel->getOne($goods_id);
            $where = ['goods_id' => $goods_id,];
            $data = ['virtual_sales' => $sales, 'update_time' => time()];
            if ($goodsData['virtual_set'] == 1) {
                $virtualSetNums++;
                continue;
            }
            $result = $this->MallGoodsModel->updateOne($where, $data);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        }
        if ($virtualSetNums) {
            return $virtualSetNums;
        } else {
            return true;
        }
    }

    /**
     * @param $goods_id
     * @return array
     * @throws \think\Exception
     * 获取某个商品的sku 信息
     */
    public function getGoodsSkuInfo($goods_id)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('goods_id参数缺失');
        }
        $sku_info = (new MallGoodsSkuService())->getSkuByGoodsId($goods_id);
        if (!empty($sku_info)) {
            foreach ($sku_info as $key => $val) {
                unset($sku_info[$key]['cost_price']);
                unset($sku_info[$key]['origin_stock']);
                unset($sku_info[$key]['create_time']);
                unset($sku_info[$key]['max_num']);
                unset($sku_info[$key]['is_del']);
            }
        }
        if (!empty($sku_info)) {
            return $sku_info;
        } else {
            return [];
        }
    }

    /**
     * 设置某个sku的价格
     */
    public function setGoodsSkuInfo($goods_id, $sku_info, $type, $price)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('参数缺失');
        }
        if ($type == 'spu') {
            $dataSku = ['price' => $price];
            $where = ['goods_id' => $goods_id];
            $data = ['price' => $price, 'max_price' => $price, 'min_price' => $price, 'update_time' => time()];
            $skuModel = new MallGoodsSkuService();
            $resSku = $skuModel->setSku($where, $dataSku);
            $res = $this->MallGoodsModel->updateOne($where, $data);
            if ($res === false && $resSku === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        } elseif ($type == 'sku') {
            if (empty($sku_info)) {
                throw new \think\Exception('参数缺失');
            }
            $skuModel = new MallGoodsSkuService();
            foreach ($sku_info as $key => $val) {
                $where = ['sku_id' => $val['sku_id'], 'goods_id' => $goods_id];
                $data = ['price' => $val['price']];
                $res = $skuModel->setSku($where, $data);
                if ($res === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
            //找出最高价和最低价存入商品表中
            $price = array_column($sku_info, 'price');
            $max_price = max($price);
            $min_price = min($price);
            $goods_date = ['min_price' => $min_price, 'max_price' => $max_price, 'price' => $min_price, 'update_time' => time()];
            $goods_where = ['goods_id' => $goods_id];
            $res = $this->MallGoodsModel->updateOne($goods_where, $goods_date);
            if ($res === false) {
                throw new \think\Exception('操作失败，请重试');
            }
        } else {
            throw new \think\Exception('type不能为空，且必须是 sku 或 spu ');
        }
        return true;
    }

    /**
     * @param $store_id
     * @return array
     * @throws Exception
     * 获取各种数
     */
    public function getNumbers($store_id, $keyword, $mer_id)
    {
        if (empty($store_id)) {
            throw new \think\Exception('store_id参数缺失');
        }
        $where1 = [['store_id', '=', $store_id], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
        $where2 = [['store_id', '=', $store_id], ['status', '=', 1], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
        $where3 = [['store_id', '=', $store_id], ['stock_num', '<', 10], ['stock_num', '>', '-1'], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
        $where4 = [['store_id', '=', $store_id], ['status', '=', 0], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
        if ($keyword !== '') {
            $where1 = [['store_id', '=', $store_id], ['name', 'like', '%' . $keyword . '%'], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
            $where2 = [['store_id', '=', $store_id], ['status', '=', 1], ['name', 'like', '%' . $keyword . '%'], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
            $where3 = [['store_id', '=', $store_id], ['stock_num', '<', 10], ['stock_num', '>', '-1'], ['name', 'like', '%' . $keyword . '%'], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
            $where4 = [['store_id', '=', $store_id], ['status', '=', 0], ['name', 'like', '%' . $keyword . '%'], ['is_del', '=', 0], ['mer_id', '=', $mer_id]];
        }
        $num1 = $this->MallGoodsModel->getCount($where1, 'goods_id');
        $num2 = $this->MallGoodsModel->getCount($where2, 'goods_id');
        $num3 = $this->MallGoodsModel->getCount($where3, 'goods_id');
        $num4 = $this->MallGoodsModel->getCount($where4, 'goods_id');
        $list = [['key' => '1', 'value' => '全部(' . $num1 . ')'], ['key' => '2', 'value' => '售卖中(' . $num2 . ')'], ['key' => '3', 'value' => '库存不足(' . $num3 . ')'], ['key' => '4', 'value' => '已下架(' . $num4 . ')']];
        return $list;
    }

    public function dealService()
    {
        return (new \app\mall\model\db\MallProductGuarantee())->where('status',1)->field('id AS `key`,title AS `value`,content')->select()->toArray();


        //如需添加服务保障 可以在此数组上累加
        $arr = [
            ['key' => '1', 'value' => '七天无理由退货'],
            ['key' => '2', 'value' => '假一赔三'],
            ['key' => '3', 'value' => '正品保障'],
            ['key' => '4', 'value' => '第三方质检'],
            ['key' => '5', 'value' => '闪电发货']
        ];
        return $arr;
    }

    /**
     * 处理用户端展示服务保障
     */
    public function dealGoodsService($serviceDesc)
    {
        $temp = $this->dealService();
        $arr = array_column($temp, 'value', 'key');
        $arrKeys = $arr ? array_column($temp, 'key') : [];
        $service = array();
        $serviceDesc = unserialize($serviceDesc);
        if (!empty($serviceDesc)) {
            foreach ($serviceDesc as $val) {
                if (in_array($val, $arrKeys)) {
                    $service[] = $arr[$val];
                }
            }
        }
        return $service;
    }

    public function dealGoodsServiceDetail($serviceDesc)
    {
        $temp = $this->dealService();
        $arr = array_combine(array_column($temp, 'key'), $temp);
       
        $service = array();
        $serviceDesc = unserialize($serviceDesc);
        if (!empty($serviceDesc)) {
            foreach ($serviceDesc as $val) {
                if (isset($arr[$val])) {
                    $service[] = $arr[$val];
                }
            }
        }
        return $service;
    }

    /**
     * 判断参加团购的商品
     * User: chenxiang
     * Date: 2020/11/5 9:51
     * @return mixed
     */
    public function getGroupList()
    {
        //周期购的是商品不是sku（只要活动状态不为2就是在团购中）
        $spu_where = [['a.type', '=', 'group'], ['status', '<>', 2], ['is_del', '=', 0]];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //正在参加团购的商品
        $spu_periodic = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        return $spu_periodic;

    }

    /**
     * [globelLabel description]
     *三个标签，商品的三个标签
     * 依次显示的顺序，同城送达，优惠券，满赠，包邮，新品，七天无理由
     * @Author   Mrdeng
     * @param [int] $stordid 商家ID
     * @param [int] $goodsid 商品ID
     * @return   [arr]                          [description]
     */
    public function globelLabel($stordid, $goodsid)
    {
        $label[0]["item_type"] = 0;
        $label[0]["item_label"] = "同城送达";
        $label[1]["item_type"] = 1;
        $label[1]["item_label"] = "满300减30";
        $label[2]["item_type"] = 0;
        $label[2]["item_label"] = "满赠";
        return $label;

        $label = [];
        if (count($label) < 3) {
            //同城送达
            $i = count($label);
            $label[$i]["item_type"] = 0;
            $label[$i]["item_label"] = "同城送达";
        }
        if (count($label) < 3) {
            //优惠券
            $MerchantCouponService = new MerchantCouponService();
            $coupon = $MerchantCouponService->getMerchantCouponList($stordid, 0);
            if ($coupon) {
                $i = count($label);
                $label[$i]["item_type"] = 1;
                $label[$i]["item_label"] = $coupon[0];
            }
        }
        if (count($label) < 3) {
            //满赠
            $MallFullAct = new MallFullAct();
            $coupon = $MallFullAct->getOne($stordid, $goodsid);
            if ($coupon) {
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = "满赠";
            }
        }
        if (count($label) < 3) {
            //包邮
            $where[] = ["goods_id", '=', $goodsid];
            //1为包邮
            $where[] = ["free_shipping", '=', 1];
            $field = "id";
            $result = (new MallGoods())->getAllList($where, $field);
            if ($result) {
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = "包邮";
            }
        }

        if (count($label) < 3) {
            //新品
            $onetime = strtotime(date('Y-m-d H:i:s', strtotime('- 3 day')));
            //当前时间戳
            $nowtime = time();
            $where[0] = $onetime;
            $where[1] = $nowtime;
            $where1[] = ["goods_id", '=', $goodsid];
            $result = (new MallGoods())->getMsg($where1, $where);
            if ($result) {
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = "新品";
            }
        }

        if (count($label) < 3) {
            //七天无理由等，在商品表service_desc字段里保存
            $where[] = ["goods_id", '=', $goodsid];
            $where[] = ["service_desc", 'neq', ''];
            $field = "service_desc";
            $result = (new MallGoods())->getAllList($where, $field);
            if ($result) {
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = $result[0]['service_desc'];
            }
        }
    }

    /**
     * @param $mer_id_goods
     * @param $mer_id
     * 平台编辑商品跳转获取商家信息
     */
    public function merchantGoodsEdit($ticket, $mer_id, $system_user)
    {
        if (empty($mer_id)) {
            throw new \think\Exception('缺少mer_id 参数');
        }
        if (!empty($ticket)) {
            $token = Token::checkToken($ticket);
            $mer_id_merchant = $token['memberId'];
            if ($mer_id_merchant == $mer_id) {
                $is_one = 1;
            } else {
                $is_one = 0;
            }
        } else {
            $is_one = 2;
        }
        $param['mer_id'] = $mer_id;
        $info = (new LoginService())->autoLogin($param, $system_user);
        return ['is_one' => $is_one, 'info' => $info];
    }

    /**
     * 获取平台装修活动选取商品
     */
    public function getDecorateActGoods($param)
    {
        if (empty($param['end_time']) || empty($param['start_time']) || empty($param['type'])) {
            throw new \think\Exception('参数缺失');
        }
        $spu_where = [['a.status', '<>', 2], ['a.type', 'like', '%' . $param['type'] . '%'], ['a.is_del', '=', 0], ['a.start_time', '<', $param['start_time']], ['a.end_time', '>', $param['end_time']]];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name';
        $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        $field = 'goods_id,image,store_id,mer_id,name as goods_name,min_price,max_price,price,stock_num,goods_type';
        $arr = array();
        //只有在子表中被推荐的商品才能被关联
        if (!empty($spu)) {
            foreach ($spu as $key => $val) {
                if ($param['type'] == 'group') {
                    $info = (new MallNewGroupActService())->getBase($val['act_id']);
                    if (!empty($info)) {
                        if ($info['is_recommend'] == 2) {
                            unset($spu[$key]);
                        }
                    }
                } elseif ($param['type'] == 'limited') {
                    $info = (new MallLimitedSku())->getSkuByActId($val['act_id']);
                    if (!empty($info)) {
                        if ($info['is_recommend'] == 2) {
                            unset($spu[$key]);
                        }
                    }
                } elseif ($param['type'] == 'bargain') {
                    $info = (new MallNewBargainAct())->getAct($val['act_id']);
                    if (!empty($info)) {
                        if ($info['is_recommend'] == 2) {
                            unset($spu[$key]);
                        }
                    }
                } else {
                    throw new \think\Exception('type参数错误');
                }
            }
            if (!empty($spu)) {
                foreach ($spu as $key => $val) {
                    $where = [['status', '=', 1], ['is_del', '=', 0], ['goods_id', '=', $val['goods_id']]];
                    if ($param['keyword'] !== '') {
                        $where = [['status', '=', 1], ['is_del', '=', 0], ['goods_id', '=', $val['goods_id']], ['name', 'like', '%' . $param['keyword'] . '%']];
                    } elseif (!empty($param['cat_id'])) {
                        $where = [['status', '=', 1], ['is_del', '=', 0], ['goods_id', '=', $val['goods_id']], ['cat_id', '=', $param['cat_id']]];
                    }
                    $goods = $this->MallGoodsModel->getOneByWhere($where, $field);
                    if (!empty($goods)) {
                        $goods['image'] = $goods['image'] ? replace_file_domain($goods['image']) : '';
                        $arr[] = $goods;
                    }
                }
            }
            $list['list'] = $arr;
            $list['count'] = count($arr);
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 获取商品限购量
     * @param  [type] $goods_id [description]
     * @return [type]           [description]
     */
    public function get_limit_buy($goods_id, $is_restriction = 0, $restriction_type = 1, $restriction_periodic = 1, $restriction_num)
    {
        $return = [
            'type' => 0, //0=终身限购 1=每天限购 2=每周限购 3=每月限购
            'nums' => 0,//0=不限购
        ];
        if ($is_restriction == '0') {
            return $return;
        }
        if ($restriction_type == '1') {
            $return['nums'] = $restriction_num;
            return $return;
        }
        $return['type'] = $restriction_periodic;
        $return['nums'] = $restriction_num;
        return $return;
    }


    public function getSpreadRate($goods_id){
        $return = [
            'first_rate' => 0,
            'second_rate' => 0,
            'third_rate' => 0
        ];
        if(empty($goods_id)) return [];

        $goods = $this->getBasicInfo($goods_id);
        $check = false;
        if($goods['spread_rate'] > 0){
            $return['first_rate'] = $goods['spread_rate'];
            $check = true;
        }
        if($goods['sub_spread_rate'] > 0){
            $return['second_rate'] = $goods['sub_spread_rate'];
            $check = true;
        }
        if($goods['third_spread_rate'] > 0){
            $return['third_rate'] = $goods['third_spread_rate'];
            $check = true;
        }
        if(!$check){//单品未设置
            $mall_first_rate = cfg('mall_first_rate');
            if($mall_first_rate > 0){
                $return['first_rate'] = $mall_first_rate;
                $check = true;
            }
            $mall_second_rate = cfg('mall_second_rate');
            if($mall_second_rate > 0){
                $return['second_rate'] = $mall_second_rate;
                $check = true;
            }
            $mall_third_rate = cfg('mall_third_rate');
            if($mall_third_rate > 0){
                $return['third_rate'] = $mall_third_rate;
                $check = true;
            }
            if(!$check){ //通用设置
                $first_rate = cfg('user_spread_rate');
                if($first_rate > 0){
                    $return['first_rate'] = $first_rate;
                }
                $second_rate = cfg('user_first_spread_rate');
                if($second_rate > 0){
                    $return['second_rate'] = $second_rate;
                }
                $third_rate = cfg('user_second_spread_rate');
                if($third_rate > 0){
                    $return['third_rate'] = $third_rate;
                }
            }
        }
        return $return;

    }

    public function goodsBatch($param, $merId)
    {
        $storeId = $param['store_id'] ?? 0;
        $sortId = $param['sort_id'] ?? 0;
        $goodsId = $param['goods_id'] ?? [];
        if (empty($storeId) || empty($sortId) || empty($goodsId)) {
            throw new \think\Exception(L_("缺少参数"), 1001);
        }
        // 验证店铺和分类
        $store = (new MerchantStoreMall())->getOne(['store_id' => $storeId]);
        if (empty($store)) {
            throw new \think\Exception(L_("店铺不存在"), 1003);
        }
        $where = [
            'id' => $sortId
        ];
        $sort = (new MallGoodsSort())->getEdit($where);
        if (empty($sort)) {
            throw new \think\Exception(L_("分类不存在"), 1003);
        }
        if ($sort['level'] == 1) {
            $sort_first = $sortId;
            $sort_second = 0;
            $sort_third = 0;
        } elseif ($sort['level'] == 2) {
            $sort_first = $sort['fid'];
            $sort_second = $sortId;
            $sort_third = 0;
        } elseif ($sort['level'] == 3) {
            $sort_first = $sort = ((new MallGoodsSort())->getEdit(['id' => $sort['fid']]))['fid'];
            $sort_second = $sort['fid'];
            $sort_third = $sortId;
        }
        // 查询商品库商品
        $condition = [];
        $condition[] = ['store_id', '=', $storeId];
        $condition[] = ['goods_id', 'in', explode(',', $goodsId)];
        $order = [
            'sort' => 'DESC',
            'goods_id' => 'ASC',
        ];
        $common = new Pinyin();
        $goodsInfo = (new ShopGoodsService())->getGoodsListByCondition($condition, $order, 0);
        foreach ($goodsInfo as $v) {
            //处理规格
            $detail = (new ShopGoodsService())->getGoodsDetail(['goods_id' => $v['goods_id'], 'store_id' => $storeId]);
            if (!empty($detail)) {
                if (!empty($detail['list'])) {
                    $goods_type = 'sku';
                    $min_price = min(array_column($detail['list'], 'price'));
                    $max_price = max(array_column($detail['list'], 'price'));
                } else {
                    $goods_type = 'spu';
                    $min_price = $v['price'];
                    $max_price = $v['price'];
                }
            }
            preg_match_all('/[\x{4e00}-\x{9fff}\d\w\s[:punct:]]+/u', $v['name'], $result);
            $spell_full = join('', $result[0]);
            $spell_full = str_replace(['(', ')', '.', '·', '（', '）', '-', '\\', '/', '\'', '@', '|', '{', '}', '[', ']', '+', '=', '#', '*', '$', '^', '&', '+', ';', ':', '、', '<', '>', '?'], '', $spell_full);
            $data = [
                'mer_id' => $merId,
                'store_id' => $storeId,
                'price' => $v['price'],
                'name' => $v['name'],
                'image' => $v['image'] ? explode(';', $v['image'])[0] : '',
                'images' => $v['image'],
                'min_price' => $min_price ?? 0,
                'max_price' => $max_price ?? 0,
                'unit' => $v['unit'],
                'video_url' => $v['video_url'],
                'create_time' => time(),
                'update_time' => time(),
                'sort_id' => $sortId,
                'sort_first' => $sort_first ?? 0,
                'sort_second' => $sort_second ?? 0,
                'sort_third' => $sort_third ?? 0,
                'goods_type' => $goods_type ?? 'spu',
                'common_goods_id' => $v['goods_id'],
                'stock_num' => $v['stock_num'],
                'spell_capital' => pinyin_long($spell_full),
                'spell_full' => $common->isChinese($spell_full),
                'goods_desc' => $v['des'],
                'notes' => serialize($detail['properties_list']),
                'status' => 0,
                'stock_type' => 1,
                'free_shipping' => 1
            ];
            //查询是否需要修改审核状态
            if(customization('open_scenic_sports_mall_shop_audit') == 1){
                if(cfg('mall_goods_audit_type') === '0'){
                    $data['audit_status'] = 1;
                }else{//手动审核
                    $data['audit_status'] = 0;
                }
                $data['add_audit_time'] = time();
            }else{
                $data['audit_status'] = 1;
            }
            Db::startTrans();
            try {
                $id = (new MallGoods())->addOne($data);
                $this->dealSkuAndSpec($detail['spec_list'], $detail['list'], $id, $detail['store_id'], $detail['price'], $detail['image'], $detail['stock_num']);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \think\Exception($e->getMessage());
            }
        }
        return ['msg' => '添加成功'];
    }

    /**
     * 分享海报
     */
    public function share($param)
    {
        $now_group = (new MallGoods())->getOne($param['goods_id']);
        if (empty($now_group)) {
            $out['status'] = 0;
            $out['msg'] = "商城商品不存在";
            return $out;
        } else {
            $group_id = $now_group['goods_id'];
            $group_price = floatval($now_group['price']);
            $goodMainPic = empty($now_group['image']) ? "" : replace_file_domain($now_group['image']);
//            if(!$this->file_img_exists($goodMainPic) && !@fopen($goodMainPic, 'r'))
//            {
//                $share_img_arr = array(
//                    'share_title' => '我推荐商品',
//                    'friend_img' => "",
//                );
//                $out['status'] = 1;
//                $out['data'] = $share_img_arr;
//                return $out;
//            }
            $wxapp_path = 'pages/webview/webview?webview_url=' . urlencode(get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $group_id));
            }
            $img_rand_path = sprintf("%09d", $group_id);
            $rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);

            $root_path = app()->getRootPath();
            $imgFriendPath = $root_path . '../upload/wxapp_mall_goods/' . $rand_num . '/friend_' . time() . '_' . $group_id . '.png';
            /* 朋友圈图片结束 */
            /*
             *   分享好友 500*400 图片
             */
            $font = realpath('../../static/fonts/PingFang Regular.ttf');
            if (!file_exists($imgFriendPath) && false) {
                if (!file_exists(dirname($imgFriendPath))) {
                    mkdir(dirname($imgFriendPath), 0777, true);
                }

                $img = imagecreatetruecolor(500, 400);
                $white = imagecolorallocate($img, 255, 255, 255);
                imagefill($img, 0, 0, $white);
                //背景图片绘制
                $src_im = imagecreatefrompng('static/mall/wxapp_friend_bg.png');
                //裁剪
                imagecopy($img, $src_im, 0, 0, 0, 0, 500, 400);

                //商品图像不变形绘制
                $tmpDir = $root_path.'../upload/wxapp_mall_goods/'. $rand_num;
                $goodsImg = $tmpDir.'/cover_'.time().'.jpg';
                (new ImageService())->thumb2($goodMainPic, $goodsImg,'',500, 308);
                if(file_exists($goodsImg)){
                    $src_ims = imagecreatefromstring(file_get_contents( $goodsImg));
                    imagecopy($img,$src_ims,0,0,0,0,500,308);
                    unlink($goodsImg);
                }


//                //商品图片绘制
//                $info = getimagesize($goodMainPic);
//                $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
//                $src_im = call_user_func_array($fun, array($goodMainPic));
//                //创建新图像
//                $newimg = imagecreatetruecolor(554, 308);
//                // 调整默认颜色
//                $color = imagecolorallocate($newimg, 255, 255, 255);
//                imagefill($newimg, 0, 0, $color);
//                //裁剪
//                imagecopyresampled($newimg, $src_im, 0, 0, 0, 0, 554, 308, $info[0], $info[1]);
//                imagedestroy($src_im); //销毁原图
//                imagecopy($img, $newimg, -27, 0, 0, 0, 554, 308);
                $MallActivityService = new MallActivityService;
                if($param['act_type']=='limited') {
                   //按钮图片绘制
                    $src_im_btn1 = imagecreatefrompng('static/mall/black.png');
                    //裁剪
                    imagecopy($img, $src_im_btn1, 120, 320, 0, 0, 74, 32);
                    $fontSize = 10;//像素字体
                    $fontColor = imagecolorallocate($img, 255, 255, 255);//字的RGB颜色
                    imagettftext($img, $fontSize, 0, 132, 340, $fontColor, $font, "限时抢购");
                    imagettftext($img, $fontSize, 0, 133, 340, $fontColor, $font, "限时抢购");
                    //按钮图片绘制
                    $src_im_btn2 = imagecreatefrompng('static/mall/red.png');
                    //裁剪
                    imagecopy($img, $src_im_btn2, 350, 320, 0, 0, 112, 48);
                    $fontSize = 17;//像素字体
                    $fontColor = imagecolorallocate($img, 255, 255, 255);//字的RGB颜色
                    imagettftext($img, $fontSize, 0, 360, 350, $fontColor, $font, "立即抢购");
                    imagettftext($img, $fontSize, 0, 361, 350, $fontColor, $font, "立即抢购");
                    $group_price=$param['act_price'];
                }
                $fontSize = 12;//像素字体
                $fontColor = imagecolorallocate($img, 0, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 6, 366, $fontColor, $font, cfg('Currency_symbol'));
                imagettftext($img, $fontSize, 0, 7, 366, $fontColor, $font, cfg('Currency_symbol'));

                //原价格
                $fontBox = imagettfbbox($fontSize, 0, $font,$group_price);//文字水平居中实质
                $groupPriceWidth = $fontBox[2];

                //实际价格
                $fontSize = 20;//像素字体
                $fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 29, 366, $fontColor, $font, $group_price);
                imagettftext($img, $fontSize, 0, 30, 366, $fontColor, $font, $group_price);

                $fontSize = 10;//像素字体
                $fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 60+$groupPriceWidth, 366, $fontColor, $font, "元");
                imagettftext($img, $fontSize, 0, 61+$groupPriceWidth, 366, $fontColor, $font, "元");
                //保存主图
                imagepng($img, $imgFriendPath);
            }
            $share_img_arr = array(
                'share_title' => '我推荐商品',
                'friend_img' =>  $goodMainPic . '?' . date('YmdHi'),
                'good_img' => $goodMainPic . '?' . date('YmdHi'),
                'wxapp_path' => $wxapp_path,
            );
            $out['status'] = 1;
            $out['data'] = $share_img_arr;
            return $out;
    }

    //判断远程图片是否存在
    function file_img_exists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if(curl_exec($ch)!==false)
            return true;
        else
            return false;
    }
    /**
     * @param $path
     * @param $width
     * @return array|bool|string
     * 获得二维码
     */
    protected function get_wxapp_qrcode($path, $width)
    {
        $wxapp_access_token = invoke_cms_model('Access_token_wxapp_expires/get_access_token');
        $wxapp_access_token = $wxapp_access_token['retval']['access_token'];
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $wxapp_access_token;
        $postData = array(
            'path' => $path,
            'width' => $width
        );

        $qrcode = Http::curlPostOwn($url, json_encode($postData));
        if (is_null(json_decode($qrcode))) {
            return $qrcode;
        }

        //若返回报错，则尝试生成无数量限制的
        $tmpUrlArr = parse_url($path);
        $urlParam = convertUrlQuery($tmpUrlArr['query']);
        if ($urlParam['redirect'] != 'webview') {
            return [];
        }

        $page = 'pages/webview/webview';
        $scene = urldecode($urlParam['webview_url']);
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $wxapp_access_token;
        $postData = array(
            'scene' => $scene,
            'page' => $page,
            'width' => $width
        );
        $qrcode = Http::curlPostOwn($url, json_encode($postData));
        return $qrcode;
    }

    /**
     * 按条件获取商品审核列表
     * @param $param
     * @return array
     */
    public function getAuditGoodsList($param)
    {
        $where = [['a.is_del', '=', 0], ['c.status', '<>', 4]];
        //商品筛选
        if (!empty($param['keyword'])) {
            array_push($where, [['a.name', 'like', '%' . $param['keyword'] . '%']]);
        }
        //状态筛选
        if(isset($param['audit_status']) && !is_null($param['audit_status'])){
            array_push($where, [['a.audit_status', '=', $param['audit_status']]]);
        }
        //排序
        $order = [
            'add_audit_time' => 'DESC',
            'sort_platform' => 'DESC',
            'is_first' => 'DESC',
            'create_time' => 'DESC'
        ];
        $field = 'a.goods_id,a.store_id,a.mer_id,a.name as goods_name,a.browse_num,b.name as mer_name,c.name as store_name,a.price,a.stock_num,a.add_audit_time,a.audit_status,a.audit_msg,a.audit_time,a.image';
        $arr = $this->MallGoodsModel->getAuditByCondition($where, $field, $order, $param['page'], $param['pageSize']);
        foreach ($arr as &$v){
            $v['add_audit_time'] = $v['add_audit_time'] ? date('Y.m.d H:i',$v['add_audit_time']) : '';
            $v['audit_time'] = $v['audit_time'] ? date('Y.m.d H:i',$v['audit_time']) : '';
            $v['image'] = $v['image'] ? replace_file_domain($v['image']) : '';
        }
        return $arr;
    }

    /**
     * 商品审核
     */
    public function auditGoods($param)
    {
        if(!in_array($param['audit_status'], [1, 2])){
            throw new \think\Exception('审核状态不正确！');
        }
        if($param['audit_status'] == 2 && !$param['audit_msg']){
            throw new \think\Exception('请填写审核理由！');
        }
        if(!is_array($param['goods_ids']) || !count($param['goods_ids'])){
            throw new \think\Exception('审核内容不能为空！');
        }
        Db::startTrans();
        try {
            $this->MallGoodsModel->whereIn('goods_id',$param['goods_ids'])->update([
                'audit_status' => $param['audit_status'],
                'audit_msg' => $param['audit_msg'],
                'status' => $param['audit_status'] == 1 ? 1 : 0,
                'update_time' => time(),
                'audit_time' => time()
            ]);
            //写入审核日志记录
            $param['admin_id'] = $param['admin_id'] ?? 0;
            $param['audit_object_ids'] = $param['goods_ids'];
            $param['type'] = 'goods';
            $auditService = new AuditService();
            $auditService->addLog($param);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return ['msg'=>'操作成功'];
    }

    /**

     * 新版商城商品分享海报
     */
    public function shareGoods($param)
    {
        $tm = time();
        $param['avatar'] ? $param['avatar'] : cfg('site_url'). '/static/images/user_avatar.jpg';
        $param['poster_params'] = $param['poster_params'] ?? [];
        $addParamStr = '';
        if($param['poster_params']){
            foreach ($param['poster_params'] as $kParam=>$vParam){
                $addParamStr = $addParamStr.'&'.$kParam.'='.$vParam;
            }
        }
        if(!$param['goods_id'] || !$param['origin'] || !$param['uid']){
            throw new \think\Exception(L_("缺少参数"), 1001);
        }
        if($param['origin']!="wxapp"){
            $param['origin']='web';
        }else{
            $param['origin']='wxapp';
        }
        $now_group = (new MallGoods())->getOne($param['goods_id']);
        if (empty($now_group)) {
            throw new \think\Exception(L_("商城商品不存在"));
        } else {
            $group_price = floatval($now_group['price']);
            $goodMainPic = empty($now_group['image']) ? "" : replace_file_domain($now_group['image']);
            $name = $now_group['name'];
        }
        //查询商品活动
        $action = '';$actionPrice = 0;
        $MallActivityService = new MallActivityService;
        $activity_info = $MallActivityService->getActivity($param['goods_id'], 0, $param['now_user_id'], '', 0, '');
        //限时优惠
        if($activity_info['style'] == 'limited'){
            $action = 'limited';
            $actionPrice = get_format_number($activity_info['price']);
        }

        //图片存在则直接返回
        $md5 = md5(var_export($param, true) . $group_price . $goodMainPic . $name);
        $mod = \think\facade\Db::name('map_icon');
        $record = $mod->where('md5', $md5)->where('type','mall_goods_share')->findOrEmpty();
        if ($record && $tm < $record['add_time'] + 300) {
            return replace_file_domain($record['icon_url']).'?t='.$tm;
        }

        //二维码图片
        if (cfg('mall_goods_share_qrcode_type')) {
            $qrcodePath = cfg('site_url') . '/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page=' . urlencode('pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'] . $addParamStr);
        } else {
            $qrcodePath = cfg('site_url') . '/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon=' . urlencode(get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $param['goods_id'] . $addParamStr));
        }

        // 生成图片路径 
        $tmpDir = app()->getRootPath() . 'runtime/mall_goods_share/' . $param['goods_id'].'_'.$param['uid'];
        $saveDir = app()->getRootPath() . '../upload/mall_goods_share';

        // 生成图片名称
        $image_name = $md5 . '.png';

        // 创建目录
        if(!is_dir($tmpDir)){
            mkdir($tmpDir,0755,true);
        }
//        if(!is_dir($saveDir)){
//            mkdir($saveDir,0755,true);
//        }

        // 图片最后保存路径
        $imgFriendPath = $saveDir.'/'.$image_name;

        //创建主图
        $imgH = 871;
        $img = imagecreatetruecolor(600,$imgH);
        $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
        imagefill ( $img ,  0 ,   0 ,  $white );

        // 商品图片
        // 压缩裁剪图片
        (new ImageService())->thumb2($goodMainPic, $tmpDir.'/cover_image.jpg','',420, 420);
        if(file_exists($tmpDir.'/cover_image.jpg')){
            $src_im = imagecreatefromstring(file_get_contents( $tmpDir.'/cover_image.jpg'));
            imagecopy($img,$src_im,90,120,0,0,420,420);
        }
        //字体
        $font =  app()->getRootPath().'../static/fonts/PingFang Regular.ttf';
        $left=0;
        $width = 350;
        if($action == 'limited'){//展示秒杀价名称格式
            (new ImageService())->arcRec($img,10,585,100,35,5,imagecolorallocate ($img, 250, 50, 0 ));
            $string = '限时秒杀';
            imagefttext($img, 16, 0, 19+$left, 580 + 29, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
            imagefttext($img, 16, 0, 19+$left + 1, 580 + 29, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
            imagefttext($img, 16, 0, 19+$left, 580 + 29 + 1, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
            imagefttext($img, 16, 0, 19+$left + 1, 580 + 29 + 1, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
            $left = 100;
            $width = 300;
        }
        //商品名称换行
        $fontSize = 22;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
        $top = '580'; //距离顶部距离
        $font_height = 33;
        $string = '';
        preg_match_all("/./u", $name, $arr);
        $letter = $arr[0];
        if($action == 'limited'){
            $str = '';
            $long = false;
            foreach($letter as $l) {
                if(!$long){
                    $teststr = $str.$l;
                }else{
                    $teststr = $string.$l;
                }
                $testbox = imagettfbbox($fontSize, 0, $font, $teststr);
                if (!$long && ($testbox[2] > $width) && ($str !== "")) {
                    $str .= PHP_EOL;
                    $long = true;
                    imagefttext($img, $fontSize, 0, 20+$left, $top + $font_height, $fontColor, $font, $str);
                    imagefttext($img, $fontSize, 0, 20+$left, $top + $font_height + 1, $fontColor, $font, $str);
                }
                if($long && ($testbox[2] > $width+80) && ($string !== "")){
                    $string .= PHP_EOL;
                }
                if(!$long){
                    $str .= $l;
                }else{
                    $string .= $l;
                }
            }
            if(!$long){
                imagefttext($img, $fontSize, 0, 20+$left, $top + $font_height, $fontColor, $font, $name);
                imagefttext($img, $fontSize, 0, 20+$left, $top + $font_height + 1, $fontColor, $font, $name);
            }
            imagefttext($img, $fontSize, 0, 20, $top + $font_height + $font_height+10, $fontColor, $font, $string);
            imagefttext($img, $fontSize, 0, 20, $top + $font_height + $font_height+10 + 1, $fontColor, $font, $string);
        }else{
            foreach($letter as $l) {
                $teststr = $string.$l;
                $testbox = imagettfbbox($fontSize, 0, $font, $teststr);
                if (($testbox[2] > $width) && ($string !== "")) {
                    $string .= PHP_EOL;
                }
                $string .= $l;
            }
            imagefttext($img, $fontSize, 0, 20, $top + $font_height, $fontColor, $font, $string);
            imagefttext($img, $fontSize, 0, 20, $top + $font_height + 1, $fontColor, $font, $string);    
        }

        //拼团价格
        if($param['group_price'] != '-1'){//展示拼团价
            $box = imagettfbbox($fontSize, 0, $font, $string);
            $height = $box[3] - $box[5];
            $groupPrice = $param['group_price'];
            $fontSize = 22;//像素字体
            $fontColor = imagecolorallocate ($img, 0, 179, 103 );//字的RGB颜色
            $string = $groupPrice;
            $leftStr = '拼团价：￥';
            $font_width = ImageFontWidth($fontSize);
            //取得 str 2 img 后的宽度
            imagefttext($img, $fontSize, 0, 20 + 150, $top + $font_height + $height + 20, $fontColor, $font, $string);
            imagefttext($img, $fontSize, 0, 20, $top + $font_height + $height + 20, $fontColor, $font, $leftStr);

            imagefttext($img, $fontSize, 0, 20 + 150 + 1, $top + $font_height + $height + 20, $fontColor, $font, $string);
            imagefttext($img, $fontSize, 0, 20 + 1, $top + $font_height + $height + 20, $fontColor, $font, $leftStr);

            imagefttext($img, $fontSize, 0, 20 + 150 + 2, $top + $font_height + $height + 20, $fontColor, $font, $string);
            imagefttext($img, $fontSize, 0, 20 + 2, $top + $font_height + $height + 20, $fontColor, $font, $leftStr);
        }
        if($action == 'limited'){//展示秒杀价价格格式
            $price = $group_price;//划线价
            $fontSize = 35;//像素字体
            $fontColor = imagecolorallocate ($img, 255, 63, 63 );//字的RGB颜色
            $string = $actionPrice;
            $leftStr = '￥';
            $font_width = ImageFontWidth($fontSize);
            $priceLen = strlen($string) * 25;//秒杀价文字长度
            $priceOldLen = strlen($price) * 16;//划线价文字长度
            //取得 str 2 img 后的宽度
            imagefttext($img, $fontSize, 0, 20 + 25, 830, $fontColor, $font, $string);//秒杀价
            imagefttext($img, 20, 0, 20, 830, $fontColor, $font, $leftStr);//秒杀价￥
            imageline( $img, 20 + 25 + $priceLen + 10, 823, 20 + 20 + $priceLen + 25 +10 + $priceOldLen + 10, 823, imagecolorallocate ($img, 147, 147, 147 ));
            imagefttext($img, 20, 0, 20 + 50 + $priceLen + 10, 830, imagecolorallocate ($img, 147, 147, 147 ), $font, $price);//划线价
            imagefttext($img, 20, 0, 20 + 25 + $priceLen + 10, 830, imagecolorallocate ($img, 147, 147, 147 ), $font, $leftStr);//划线价￥

            imagefttext($img, $fontSize, 0, 20 + 25 + 1, 830, $fontColor, $font, $string);
            imagefttext($img, 20, 0, 20 + 1, 830, $fontColor, $font, $leftStr);
            imagefttext($img, 20, 0, 20 + 50 + $priceLen + 10 + 1, 830, imagecolorallocate ($img, 147, 147, 147 ), $font, $price);//划线价
            imagefttext($img, 20, 0, 20 + 25 + $priceLen + 10 + 1, 830, imagecolorallocate ($img, 147, 147, 147 ), $font, $leftStr);//划线价￥

            imagefttext($img, $fontSize, 0, 20 + 25 + 2, 830, $fontColor, $font, $string);
            imagefttext($img, 20, 0, 20 + 2, 830, $fontColor, $font, $leftStr);

            $discount = getFormatNumber($price-$actionPrice) < 0 ? 0 : getFormatNumber($price-$actionPrice);
            $string = '已优惠￥'.$discount;
            $w = 130 + strlen($discount) * 14;
            $left = 20 + 20 + $priceLen + 25 +10 + $priceOldLen + 10;
            (new ImageService())->arcRec($img,$left + 10,800,$w,35,15,imagecolorallocate ($img, 250, 50, 0 ));
            imagefttext($img, 17, 0, 25+$left, 800 + 25, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
            imagefttext($img, 17, 0, 25+$left + 1, 800 + 25, imagecolorallocate ($img, 250, 250, 250 ), $font, $string);
        }else{
            //价格
            $price = $group_price;
            $fontSize = 35;//像素字体
            $fontColor = imagecolorallocate ($img, 255, 63, 63 );//字的RGB颜色
            $string = $price;
            $leftStr = '￥';
            $rightStr = '';
            if($now_group['goods_type']=='sku'){
                $rightStr = '起';
            }
            $font_width = ImageFontWidth($fontSize);
            //取得 str 2 img 后的宽度
            imagefttext($img, $fontSize, 0, 20 + 40, 830, $fontColor, $font, $string);
            imagefttext($img, 35, 0, 20, 830, $fontColor, $font, $leftStr);
            imagefttext($img, 25, 0, 20 + 40 + strlen($string) * 28, 830, $fontColor, $font, $rightStr);

            imagefttext($img, $fontSize, 0, 20 + 40 + 1, 830, $fontColor, $font, $string);
            imagefttext($img, 35, 0, 20 + 1, 830, $fontColor, $font, $leftStr);
            imagefttext($img, 25, 0, 20 + 40 + 1 + strlen($string) * 28, 830, $fontColor, $font, $rightStr);

            imagefttext($img, $fontSize, 0, 20 + 40 + 2, 830, $fontColor, $font, $string);
            imagefttext($img, 35, 0, 20 + 2, 830, $fontColor, $font, $leftStr);
            imagefttext($img, 25, 0, 20 + 40 + 2 + strlen($string) * 28, 830, $fontColor, $font, $rightStr);
        }
        

        // 创建二维码图像
        $filePath =  $tmpDir.'/wxapp_qrcode.png';
        $codeSize = 150;
        if(!file_exists($filePath) || !@getimagesize($filePath)){
            $image = file_get_contents($qrcodePath);
            file_put_contents($filePath, $image);
            (new ImageService())->scaleImg($filePath, $filePath, $codeSize, $codeSize);
        }
        if(file_exists($filePath)){
            $src_im = imagecreatefromstring(file_get_contents($filePath));
            imagecopy($img,$src_im,420,600,0,0,$codeSize,$codeSize);
        }

        //二维码说明
        $codeMsgTop = '长按立即购买';
        $codeMsgLeft = 435;
        $top = 800; //距离顶部距离
        $fontColor = imagecolorallocate ($img, 180, 180, 180 );//字的RGB颜色
        //取得 str 2 img 后的宽度

        imagefttext($img, 14, 0, $codeMsgLeft, $top, $fontColor, $font, $codeMsgTop);
        imagefttext($img, 14, 0, $codeMsgLeft + 1, $top, $fontColor, $font, $codeMsgTop);

        //头像
        // 压缩裁剪图片
        $avatar = $tmpDir.'/avatar.'.$param['uid'].'.jpg';
        (new ImageService())->thumb2($param['avatar'], $avatar,'',80, 80);
        if(file_exists($tmpDir.'/avatar.'.$param['uid'].'.jpg')){
            $src_ims = imagecreatefromstring(file_get_contents( $avatar));
            imagecopy($img,$src_ims,20,20,0,0,80,80);
        }

        //昵称
        if($param['nickname']){
            $nickname = '@ '.$param['nickname'];
            $codeMsgLeft = 120;
            $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
            imagefttext($img, 18, 0, $codeMsgLeft, 50, $fontColor, $font, $nickname);
            imagefttext($img, 18, 0, $codeMsgLeft + 1, 50, $fontColor, $font, $nickname);
        }
        //为您推荐
        $nicknameBottom = '为您推荐';
        $codeMsgLeft = 120;
        $fontColor = imagecolorallocate ($img, 255, 63, 63 );//字的RGB颜色
        imagefttext($img, 18, 0, $codeMsgLeft, 90, $fontColor, $font, $nicknameBottom);
        imagefttext($img, 18, 0, $codeMsgLeft + 1, 90, $fontColor, $font, $nicknameBottom);
        //保存主图
        if(!is_dir($saveDir)){
            mkdir($saveDir,0755,true);
        }
        imagepng($img,$imgFriendPath);

        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', ['savepath' => '/upload/mall_goods_share/' . $image_name]);
        }
//        (new \file_handle\FileHandle())->upload($imgFriendPath);
        $data = [
            'md5' => $md5,
            'origin_url' => $imgFriendPath,
            'icon_url' => trim(str_replace(app()->getRootPath(),'',$imgFriendPath),'.'),
            'type' => 'mall_goods_share',
            'add_time' => time()
        ];
        if ($record) {
            $mod->where('id', $record['id'])->update($data);
        } else {
            $mod->insert($data);
        }
        return replace_file_domain($data['icon_url']).'?t='.$tm;
    }

    /*
     * 获取商品积分比例
     */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = MallGoods::where('goods_id', $goodsId)->value('score_percent');
        if(strpos($scorePercent,'%')){
            $scorePercent = bcdiv($scorePercent,100,2);
        }
        if(empty(floatval($scorePercent))){
            return -1;
        }
        return $scorePercent;
    }

    //批量给商品设置分类
    public function setStoreGoodsSort($goodsIds, $sortId, $sortArr)
    {
        if (empty($goodsIds)) {
            throw new Exception('请选择商品');
        }
        if (empty($sortId) || empty($sortArr)) {
            throw new Exception('请选择分类');
        }
        $sortInfo = (new MallGoodsSort())->where(['id' => $sortId])->field('id')->find();
        if (!$sortInfo) {
            throw new \Exception('商品分类不存在或者已删除，请重新选择商品分类');
        }
        //检查分类id是否是最上层id
        $sortNextCount = (new MallGoodsSort())->where(['fid' => $sortId, 'status' => 1])->count();
        if ($sortNextCount > 0) {
            throw new Exception('当前分类存在子分类，请选择子分类');
        }

        $updateData = [
            'sort_id' => $sortId,
            'sort_first' => $sortArr['sort_first'],
            'sort_second' => $sortArr['sort_second'],
            'sort_third' => $sortArr['sort_third'],
        ];
        return $this->MallGoodsModel->whereIn('goods_id', $goodsIds)->update($updateData);
    }

    //批量给商品设置品牌
    public function setGoodsBrand($goodsIds, $brandId)
    {
        if (empty($goodsIds)) {
            throw new Exception('请选择商品');
        }
        if (empty($brandId)) {
            throw new Exception('请选择品牌');
        }

        $brand = (new \app\mall\model\db\MallProductBrands())->where(['status' => 1, 'id' => $brandId])->find();
        if (empty($brand)) {
            throw new Exception('品牌不存在或已删除');
        }
        return $this->MallGoodsModel->whereIn('goods_id', $goodsIds)->update(['brand_id'=>$brandId]);
    }
}
