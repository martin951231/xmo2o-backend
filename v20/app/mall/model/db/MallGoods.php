<?php

/**
 * @Author: JJC
 * @Date:   2020-06-09 11:17:43
 * @Desc:   商城3.0商品模块
 * @Last Modified time: 2020-06-23 11:51:53
 */

namespace app\mall\model\db;

use app\common\model\service\coupon\MerchantCouponService;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\MerchantStoreMallService;
use think\Model;
use think\facade\Config;

class MallGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $auditStatusMap = [
        0   =>  '待审核',
        1   =>  '审核成功',
        2   =>  '审核失败'
    ];

    /**
     * [getList description]
     * @Author   JJC
     * @DateTime 2020-06-09T11:39:54+0800
     * @param    [type]                   $where [description]
     * @return   [type]                          [description]
     */
    public function getList($where, $order = 's.goods_id desc', $page = 1, $or = false,$uid=0,$search=0,$whereOr)
    {
        //表前缀
        $prefix = config('database.connections.mysql.prefix');
        if (!$or) {
            $field = 'g.store_id,g.name as store_name,s.*';
            $result = $this->alias('s')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
                ->whereOr($where)
                ->group('s.goods_id');
        }else {
            if ($or == 1) {
                $field = 'g.store_id,g.name as store_name,s.*';
                $result = $this->alias('s')
                    ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                    ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                    ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id');

//                $goods_area_list=(new MallGoodsArea())->getSome()->toArray();
//                $goods_area_list=(new MallGoodsArea())->count();;
                if(!empty($whereOr)) {
//                    array_push($where,['ma.area_id', '=', 0]);
                    $result=$result->join($prefix . 'mall_goods_area ma', 'ma.goods_id=s.goods_id')
                        ->where($where)
                        ->where(function ($query)use($whereOr){
                            $query->where('ma.area_id',0)->whereOr($whereOr);
                        })
                        ->group('s.goods_id');
                }else{
                    $result=$result->where($where)->group('s.goods_id');
                }
//                    ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
//                    ->where($where)
//                    ->group('s.goods_id');
            } else {
                if (empty($where)) {
                    $where = [];
                }
                //暂时定3天
                $onetime = strtotime(date('Y-m-d H:i:s', strtotime('- 3 day')));
                //当前时间戳
                $nowtime = time();

                /*新品*/
                $field = 'g.store_id,g.name as store_name,s.*';
                $result = $this->alias('s')
                    ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                    ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
                    ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')->
                    where($where)->whereBetween('s.create_time', "$onetime,$nowtime")->group('s.goods_id');
            }
        }
        $count = $result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select()
            ->toArray();
        /*三个标签*/
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? thumb($val['image'], 400, 400, 'fill') : '';
                $list[$key]['images'] = $val['images'] ? replace_file_domain($val['images']) : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    /**
     * 猜你喜欢区域商品展示
     */
    public function getRelatedAreaGoods($where, $sort, $page,$uid,$whereor=[]){
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        if(empty($goods_area_list)){
            //表前缀
            $prefix = config('database.connections.mysql.prefix');
            $field = 'g.store_id,g.name as store_name,s.*';
            $result = $this->alias('s')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
                ->where($where);
            
            $result = $result->group('s.goods_id');
            $count = $result->count();
            $list = $result->page($page, Config::get('api.page_size'))
                ->order($sort)
                ->field($field)
                ->select()
                ->toArray();
        }else{
            //表前缀
            $prefix = config('database.connections.mysql.prefix');
            $field = 'g.store_id,g.name as store_name,s.*';
           // array_push($where,['ma.area_id', '=', 0]);
            $result = $this->alias('s')
                ->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
                ->where($where);
            if($whereor){
                $result = $result->where(function ($query)use($whereor){
                    $query->where('ma.area_id',0)->whereOr($whereor);
                });
            }
            $result = $result->group('s.goods_id');
            $count = $result->count();
            $list = $result->page($page, Config::get('api.page_size'))
                ->order($sort)
                ->field($field)
                ->select()
                ->toArray();
        }
        /*三个标签*/
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? thumb($val['image'], 400, 400, 'fill') : '';
                $list[$key]['images'] = $val['images'] ? replace_file_domain($val['images']) : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }
    /**
     * [getList description]
     * @DateTime 2020-06-09T11:39:54+0800
     * @param    [type]                   $where [description]
     * @return   [type]                          [description]
     */
    public function getMySetList($where, $order = 's.goods_id desc', $page = 1,$uid=0,$whereOr=[],$recommendId=0,$whereOrS=[])
    {
        //表前缀
        $prefix = config('database.connections.mysql.prefix');
        $field = 'g.store_id,g.name as store_name,s.*,IF(r.id > 0,1,0) AS re_sort';
        $result = $this->alias('s')
            ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
            ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
            ->join($prefix . 'merchant' . ' mt', 'mt.mer_id = s.mer_id')
            ->leftJoin($prefix . 'mall_recommend_goods' . ' r', 'r.goods_id = s.goods_id'.($recommendId?' AND r.recommend_id ='.$recommendId:''))
            ->where($where)
            ->group('s.goods_id');
        if(cfg('open_mall_order_area')==1){
            $result = $result->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id');
            if(!empty($whereOr)){
                $result = $result->where(function ($query) use ($where,$whereOrS) {
                    $query->where($where);
                    if($whereOrS){
                        $query->where(function ($query)use($whereOrS){
                            $query->where('ma.area_id',0)->whereOr($whereOrS);
                        });
                    }
                })->whereOr(function ($query) use ($whereOr,$whereOrS) {
                    $query->where($whereOr);
                    if($whereOrS){
                        $query->where(function ($query)use($whereOrS){
                            $query->where('ma.area_id',0)->whereOr($whereOrS);
                        });
                    }
                });
            }
        }
        
        if($recommendId > 0){
            $order = 're_sort DESC,'.$order;
        }

        $count=$result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select()
            ->toArray();
        /*三个标签*/
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? replace_file_domain($val['image']):"";
                $list[$key]['images'] = $val['images'] ? replace_file_domain($val['images']) : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $out['list']=$list;
        $out['total_count']=$count;
        return $out;
    }

    /**
     * @param $where
     * @param string $order
     * @param int $page
     * @param bool $or
     * @return array
     * 当有商品级活动时
     */
    public function getListByAct($where, $order = 's.goods_id desc', $page = 1, $or = false)
    {
        //表前缀
        $where1=[['a.type','in',['bargain','group','limited','prepare']],['a.start_time','<',time()],['a.end_time','>=',time()],['a.status','=',1]];
        $prefix = config('database.connections.mysql.prefix');
        $field = 'g.store_id,g.name as store_name,s.*';
        $result = $this->alias('s')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->join($prefix . 'mall_activity_detail' . ' d', 'd.goods_id = s.goods_id')
                ->join($prefix . 'mall_activity' . ' a', 'a.id = d.activity_id')
                ->where($where)
                ->where($where1)
                ->group('s.goods_id');
        $count0 = $result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select()
            ->toArray();


        $where2=[['a.type','=','periodic'],['a.status','=',1]];
        $result1 = $this->alias('s')
            ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
            ->join($prefix . 'mall_activity_detail' . ' d', 'd.goods_id = s.goods_id')
            ->join($prefix . 'mall_activity' . ' a', 'a.id = d.activity_id')
            ->where($where)
            ->where($where2)
            ->group('s.goods_id');
        $count1 = $result1->count();
        $list1 = $result1->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select()
            ->toArray();

        $count=$count0+$count1;
        if(!empty($list1)){
            $list=array_merge($list,$list1);
        }

        /*三个标签*/
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id']);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? replace_file_domain($val['image']) : '';
                $list[$key]['images'] = $val['images'] ? replace_file_domain($val['images']) : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    public function getListByCateName($where, $order = 's.goods_id desc', $page = 1, $or = false,$uid=0,$whereOr=[])
    {
        //表前缀
        $prefix = config('database.connections.mysql.prefix');

        $field = 'g.store_id,g.name as store_name,s.*';
        if($or==1){//一级分类
            $result = $this->alias('s')
                ->join($prefix . 'mall_category' . ' w', 'w.cat_id = s.cate_first')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->where($where);
        }elseif ($or==2){//二级分类
            $result = $this->alias('s')
                ->join($prefix . 'mall_category' . ' w', 'w.cat_id = s.cate_second')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id')
                ->where($where);
        }else{//三级分类
            $result = $this->alias('s')
                ->join($prefix . 'mall_category' . ' w', 'w.cat_id = s.cate_three')
                ->join($prefix . 'merchant_store_mall' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'merchant_store' . ' g', 's.store_id = g.store_id');
        }

        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }

        if(!empty($goods_area_list)) {
//            array_push($where,['ma.area_id', '=', 0]);
            //$whereOr = [['ma.area_id', '=', 0],$whereOr];
            $result=$result->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id')
                ->where($where)
                ->where(function($query)use($whereOr){
                    $query->where('ma.area_id',0)->whereOr($whereOr);
                })
//                ->whereOr($whereOr)
                ->group('s.goods_id');
        }else{
            $result=$result->where($where)->group('s.goods_id');
        }

        $count = $result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select()
             ->toArray();
        /*三个标签*/
        if ($list) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? thumb($val['image'], 400, 400, 'fill') : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    //热卖
    public function getList1($where, $order = 's.goods_id desc', $page = 1, $or = false, $condition,$uid=0,$whereOr)
    {
        $wheres=array();
        if(isset($where['is_location']) && $where['is_location']) {//同城
            $where2=[['a.is_houseman','=',1]];
            $where2[] = ['b.status','=',1];
            $store_ids=(new MerchantStoreMall())->getStoreIDList($where2,$field='a.store_id');
            if(!empty($store_ids)){
                $wheres[]=['s.store_id','in',$store_ids];
            }
        }
        if (isset($where['keyword']) && $where['keyword']) {
            $wheres[] = ['s.name|s.spell_capital|s.spell_full', 'like', '%' . $where['keyword'] . '%'];
        }
        //价格区间
        if (isset($where['min_price']) && $where['min_price']) {
            $wheres[] = ['s.min_price', '>=', $where['min_price']];
        }
        if (isset($where['max_price']) && $where['max_price']) {
            $wheres[] = ['s.max_price', '<=', $where['max_price']];
        }
        if(isset($where['is_new']) && $where['is_new']){//新品
            $days=cfg('mall_new_goods_time');
            $onetime = strtotime(date('Y-m-d H:i:s', strtotime('- '.$days.' day')));
            //当前时间戳
            $nowtime = time();
            $wheres[]=['s.create_time','between',[$onetime,$nowtime]];
        }

        if(isset($where['is_hot']) && $where['is_hot']) {//热卖
            $sales=cfg('mall_hot_goods_time');
            $wheres[] = ["s.sale_num", '>=', $sales];
        }

        if(isset($where['free_shipping']) && $where['free_shipping']) {//包邮
            $wheres[]=['s.free_shipping','=',1];
        }

        if(isset($where['join_activity']) && $where['join_activity']) {//活动
            $where11=[['a.type','in',['bargain','group','limited','prepare']],['a.start_time','<',time()],['a.end_time','>=',time()],['a.status','=',1]];
            $goods_ids=(new MallActivityDetail())->getActGoodsId($where11,$field='d.goods_id');

            $where12=[['a.type','=','periodic'],['a.status','=',1]];//周期购
            $goods_ids1=(new MallActivityDetail())->getActGoodsId($where12,$field='d.goods_id');
            if(!empty($goods_ids) || !empty($goods_ids1)){
                $goods_ids=array_merge($goods_ids,$goods_ids1);
                $wheres[]=['s.goods_id','in',$goods_ids];
            }
        }
        
        if(isset($where['store_ids'])){
            $wheres[]=['m.store_id','in',$where['store_ids']];
        }
        //不展示已经关闭商城的店铺的商品
        $wheres[]=['m.have_mall','=',1];
        //表前缀
        $prefix = config('database.connections.mysql.prefix');
        //放进商品表中查询
        $field = 'm.store_id,m.name as store_name,s.*';
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        if(empty($goods_area_list)) {
            if($condition){
                foreach ($condition as $k=>$v){
                    if(strpos($v[0], 'g.name') !== false){
                        $condition[$k][0] = str_replace('g.name','m.name',$v[0]);
                    }
                    if(strpos($v[0], 'g.have_mall') !== false){
                        $condition[$k][0] = str_replace('g.have_mall','m.have_mall',$v[0]);
                    }
                }
            }
            $result = $this->alias('s')
                ->join($prefix . 'merchant_store' . ' m', 's.store_id = m.store_id')
                ->where($wheres)
                ->where($condition);
        }else{
//            array_push($wheres,['ma.area_id', '=', 0]);
            //$whereOr = [['ma.area_id', '=', 0],$whereOr];
            $result = $this->alias('s')
                ->join($prefix . 'merchant_store' . ' m', 's.store_id = m.store_id')
                ->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id')
                ->where($wheres)
//                ->where($condition)
//                ->whereOr($whereOr)
                ->where(function($query)use($whereOr){
                    $query->where('ma.area_id',0)->whereOr($whereOr);
                });
        }
        $count = $result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->order($order)
            ->field($field)
            ->select();
        /*三个标签*/
        if ($list) {
            foreach ($list as $key => $val) {
                $result = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);
                $list[$key]['good_label'] = $result;
                $list[$key]['image'] = $val['image'] ? thumb($val['image'], 400, 400, 'fill') : '';
                $list[$key]['price'] = get_format_number($val['price']);
                $list[$key]['max_price'] = get_format_number($val['max_price']);
                $list[$key]['min_price'] = get_format_number($val['min_price']);
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    /**
     * @param $act_id
     * @return bool
     * 门店商品列表
     */
    public function getGoodsList($store_id,$uid=0)
    {
        $condition[] = ['store_id', '=', $store_id];
        $condition[] = ['is_del', '=', 0];
        $field = "goods_id,name as goods_name,image as goods_image,price as goods_price,sale_num as payed,store_id";
        $result = $this
            ->field($field)
            ->where($condition)
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
            foreach ($result as $key => $val) {
                $result[$key]['label_list'] = $this->globelLabel($val['store_id'], $val['goods_id'],$val['mer_id'],$uid);//商品标签
                $result[$key]['goods_image'] = $val['goods_image'] ? replace_file_domain($val['goods_image']) : '';
                $result[$key]['goods_price'] = get_format_number($val['goods_price']);
            }
        } else {
            return false;
        }

        return $result;
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
    public function globelLabel($stordid, $goodsid,$mer_id,$uid=0)
    {
        $label = [];
        if (count($label) < 3) {
            //同城送达
            $is_exit=(new MerchantStoreMallService())->getStoremallInfo($stordid,$field='is_houseman');
            if(!empty($is_exit) && $is_exit['is_houseman']==1){
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = "同城送达";
            }
        }

        if (count($label) < 3) {
            //优惠券(卢敏service)
            $MerchantCouponService = new MerchantCouponService();
            $coupon = $MerchantCouponService->getMerchantCouponList($mer_id, $goodsid, 'mall', '', $uid, true);
            if (!empty($coupon)) {
                foreach ($coupon as $key=>$val){
                    if($val['is_use']==0){
                        $i = count($label);
                        $label[$i]["item_type"] = 1;
                        $label[$i]["item_label"] = $val['discount_des'];
                        //优惠倒叙排列，找出一张即可
                        break;
                    }
                }
            }
        }

        if (count($label) < 3) {
            //包邮
            $result = $this->getOne($goodsid);
            if(!empty($result) && $result['free_shipping']){
                //店铺信息
                $store_info = (new MerchantStoreMallService)->getMallStoreInfo($stordid);
                if(!empty($store_info) && !($store_info['is_delivery']==0 && $store_info['is_houseman']==0 && $store_info['is_zt']==1)){
                    if ($result) {
                        $i = count($label);
                        $label[$i]["item_type"] = 0;
                        $label[$i]["item_label"] = "包邮";
                    }
                }
            }

        }

        if (count($label) < 3) {
            //新品
            $days=cfg('mall_new_goods_time');
            $onetime = strtotime(date('Y-m-d H:i:s', strtotime('- '.$days.' day')));
            //当前时间戳
            $nowtime = time();
            $where2[0] = $onetime;
            $where2[1] = $nowtime;
            $where1[] = ["goods_id", '=', $goodsid];
            $result = $this->where($where1)
                ->whereBetween('create_time', "$where2[0],$where2[1]")
				->find();
            if ($result) {
                $i = count($label);
                $label[$i]["item_type"] = 0;
                $label[$i]["item_label"] = "新品";
            }
        }

        if (count($label) < 3) {
            //热卖
            $sales=cfg('mall_hot_goods_time');
            $where1[] = ["goods_id", '=', $goodsid];
            $where1[] = ["status", '>', 0];
            $result = $this->where($where1)->find();
            /*$where1[] = ["sale_num", '>=', $sales];*/
            //$count=(new MallOrderDetail())->geOrderCount($where1);
            if (!empty($result)) {
                $result=$result->toArray();
                if($result['sale_num']>=$sales){
                    $i = count($label);
                    $label[$i]["item_type"] = 0;
                    $label[$i]["item_label"] = "热卖";
                }
            }
        }

        if (count($label) < 3) {
            //七天无理由等，在商品表service_desc字段里保存
            $where[] = ["goods_id", '=', $goodsid];
            $where[] = ["service_desc", '<>', ''];
            $field = "service_desc";
            $result = $this->getOneByWhere($where, $field);
            if ($result) {
                //$arr=unserialize($result['service_desc']);
                $arr=(new MallGoodsService())->dealGoodsService($result['service_desc']);
                $len=3-count($label);//还差几个
                $i = count($label);
                for($j=1;$j<=$len;$j++){
                    if($j<=count($arr)){
                        $label[$i+$j]["item_type"] = 0;
                        $label[$i+$j]["item_label"] = $arr[$j-1];
                    }
                }
            }
        }
        
        return array_values($label);
    }
    
//新品
    public function getNewMsg($where1,$where){
        $result = $this->where($where1)
            ->whereBetween('create_time', "$where[0],$where[1]");
        return $result;
    }
    /**
     * [getRemShopGoodList 获取所有的推荐商品列表(分页)]
     * 当前门店商品分类下所有同等级商品
     * @Author   Mrdeng
     * @DateTime 2020-06-10T11:19:05+0800
     * @param    [type]                   $where [description]
     * @return   [type]                          [description]
     */
    public function getRemShopGoodList($where, $field = '*', $page = 1)
    {
        $this->where($where)->field($field)->select()->toArray();
        // 表前缀
        $result = $this->where($where)->field($field)->order('sale_num desc');

        $count = $result->count();

        $list = $result->page($page, Config::get('api.page_size'))
            ->select()->toArray();

        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    /**
     * [getLevelTwoId description]
     *查出条件相关的关键词商品的二级id
     * @Author   Mrdeng
     * @param [array] $where 模糊关键词查询条件
     * @return   [arr]        description]
     */
    public function getLevelTwoId($where)
    {
        $field = 'cate_second';
        $result = $this->where($where)->field($field)->group('cate_second')->select()->toArray();
        return $result;
    }

    //搜索店铺列表
    public function getListGroupStore($where, $page,$whereOr=[])
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $field = 'm.store_id,m.logo,m.name,p.reply_count,p.score_all,p.score_mean as score_ave,p.deliver_type,count(s.goods_id) as goods_count,mer.settle_in_sign,mer.settle_in_money';

        $result = $this->alias('s')
            ->join($prefix . 'merchant_store' . ' m', 's.store_id = m.store_id');

        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        if(!empty($goods_area_list)) {
//            array_push($where,['ma.area_id', '=', 0]);
            //$whereOr = [['ma.area_id', '=', 0],$whereOr];
            $result=$result->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id')
                ->leftJoin($prefix . 'merchant_store_shop' . ' p', 'p.store_id = s.store_id')
                ->leftJoin($prefix . 'merchant' . ' mer', 'm.mer_id = mer.mer_id')
                ->field($field)
                ->where($where)
                ->where(function ($query)use($whereOr){
                    $query->where('ma.area_id',0)->whereOr($whereOr);
                })
//                ->whereOr($whereOr)
                ->group('s.store_id')
                ->order('goods_count desc');
        }else{
            $result=$result->leftJoin($prefix . 'merchant_store_shop' . ' p', 'p.store_id = s.store_id')
                ->leftJoin($prefix . 'merchant' . ' mer', 'm.mer_id = mer.mer_id')
                ->field($field)
                ->where($where)
                ->group('s.store_id')
                ->order('goods_count desc');
        }
//            ->join($prefix . 'merchant_store' . ' m', 's.store_id = m.store_id')
//            ->leftJoin($prefix . 'merchant_store_shop' . ' p', 'p.store_id = s.store_id')
//            ->leftJoin($prefix . 'merchant' . ' mer', 'm.mer_id = mer.mer_id')
//            ->field($field)
//            ->where($where)
//            ->order('goods_count desc')
//            ->group('s.store_id');
        $count = $result->count();
        $list = $result->page($page, Config::get('api.page_size'))
            ->select()->toArray();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $list[$key]['id']=$val['store_id'];
                $list[$key]['score_ave']=(empty($val['score_ave']) || $val['score_ave']>5)?5:$val['score_ave'];
                $list[$key]['imge_url']=$val['logo'] ? replace_file_domain($val['logo']) : '';
            }
        }
        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    /**
     * [getAllList 获取所有的商品列表(不分页)]
     * @Author   JJC
     * @DateTime 2020-06-10T11:19:05+0800
     * @param    [type]                   $where [description]
     * @return   [type]                          [description]
     */
    public function getAllList($where, $field = '*',$order="goods_id asc",$whereOr=[],$store_ids=[])
    {
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id');
        if(!empty($goods_area_list) && !empty($whereOr)) {
            $match_where = [
                ['s.status', '=', 1],
                ['s.is_del', '=', 0],
                ['s.store_id', 'in', $store_ids],
            ];
//            array_push($match_where,['ma.area_id', '=', 0]);
            //$whereOr = [['ma.area_id', '=', 0],$whereOr];
            $result=$result->field($field)
                ->where($match_where)
                ->where(function ($query)use($whereOr){
                    $query->where('ma.area_id',0)->whereOr($whereOr);
                })
//                ->whereOr($whereOr)
                ->group('s.goods_id')
                ->order('s.goods_id asc')
                ->select()
                ->toArray();
        }else {
            $result=$this->where($where)->field($field)->order($order)->select()->toArray();
        }
        return $result;
    }

    public function getOne($goods_id)
    {
        $where = [['goods_id', '=', $goods_id], ['is_del', '=', 0],];
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getOneByWhere($where,$field)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getAll($where, $field = '*')
    {
        return $this->where($where)->field($field)->select()->toArray();
    }

    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getSum($where, $field = '*')
    {
        return $this->where($where)->sum($field);
    }

    public function getAllActivityList($where, $field = '*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $return = $this->alias('s')
            ->join($prefix . 'mall_activity_detail' . ' a', 's.goods_id = a.goods_id')
            ->join($prefix . 'mall_activity' . ' m', 'm.id = a.activity_id')
            ->field($field)
            ->where($where)
            ->select();
        if (!empty($return)) {
            return $return->toArray();
        } else {
            return [];
        }
        return $return;
    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * 关联商品库
     */
    public function getGoodsByShopGoods($where, $field = '*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $return = $this->alias('s')
            ->join($prefix . 'shop_goods' . ' a', 's.common_goods_id = a.goods_id')
            ->field($field)
            ->where($where)
            ->find();
        if (!empty($return)) {
            return $return->toArray();
        } else {
            return [];
        }
        return $return;
    }
    /**
     * @param $where
     * @param string $field
     * @return array
     * 商品关联限时优惠
     */
    public function getAllLimitActivityList($where, $field = '*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $return = $this->alias('s')
            ->join($prefix . 'mall_limited_sku' . ' l', 'l.goods_id = s.goods_id')
            ->join($prefix . 'mall_activity' . ' m', 'm.act_id = l.act_id')
            ->field($field)
            ->where($where)
            ->group('s.goods_id')
            ->order('l.sort desc,l.id desc')
            ->select();
        if (!empty($return)) {
            return $return->toArray();
        } else {
            return [];
        }
        return $return;
    }

    //查询范围内商品
    public function getAllByID($where, $field = '*')
    {
        $arr=$this->where($where)->field($field)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 按条件获取商品列表
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getByCondition($where, $field, $order, $page, $pageSize)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if (!empty($page) && !empty($pageSize) && $page>0 && $pageSize>0) {
            $arr = $this->alias('a')
                ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
                ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->page($page, $pageSize)
                ->select();
            if(!empty($arr)){
                $arr=$arr->toArray();
            }
        } else {
            $arr = $this->alias('a')
                ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
                ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
            if(!empty($arr)){
                $arr=$arr->toArray();
            }
        }
        return $arr;
    }
    /**
     * 按条件获取商品列表
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getAuditByCondition($where, $field, $order, $page, $pageSize)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if(!$field){
            $arr = $this->alias('a')
                ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
                ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->count();
        }else
        if (!empty($page) && !empty($pageSize) && $page>0 && $pageSize>0) {
            $arr = $this->alias('a')
                ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
                ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
                ->field($field)
                ->where($where)
                ->append(['audit_status_text'])
                ->order($order)
                ->paginate($pageSize);
        } else {
            $arr = $this->alias('a')
                ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
                ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
            if(!empty($arr)){
                $arr=$arr->toArray();
            }
        }
        return $arr;
    }

    public function getByConditionCount($where, $field)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'merchant b', 'a.mer_id=b.mer_id')
            ->join($prefix . 'merchant_store c', 'a.store_id=c.store_id')
            ->field($field)
            ->where($where)
            ->count('a.goods_id');
        return $count;
    }

    /**
     * @param $field
     * @param $order
     * 获取所有商品
     */
    public function getAllGoods($field, $order, $where)
    {
        $arr = $this->field($field)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 编辑其中一项
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 添加一项
     * @param $where
     * @param $data
     */
    public function addOne($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * @param string $field
     * @param array $where
     * @return array
     * 获取字段值
     */
    public function getColumn($field = 'id', $where = []) {
        $result = $this->where($where)->column($field);
        return $result;
    }

    /**
     * 根据条件获取商品
     * @param $field
     * @param $order
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getGoodsByCondition($field, $order, $where, $page = '', $pageSize = '',$whereor=[])
    {
        $goods_area_list = [];
        if(cfg('open_mall_order_area')==1){
            $goods_area_list=(new MallGoodsArea())->count();
        }
        $prefix = config('database.connections.mysql.prefix');
        // 表前缀
        $result = $this->alias('s')
            ->join($prefix . 'mall_goods_area' . ' ma', 'ma.goods_id = s.goods_id','left');
        if ($page != '' && $pageSize != '' && $page!=0) {
            if(!empty($goods_area_list) && !empty($whereor)) {
                $arr=$result->field($field)
                    ->where($where)
                    ->where(function ($query)use($whereor){
                        $query->where('ma.area_id',0)->whereOr($whereor);
                    })
                    ->group('s.goods_id')
                    ->order($order?:'s.goods_id asc')
                    ->page($page, $pageSize)
                    ->select()
                    ->toArray();
            }else{
                $arr = $this->alias('s')->field($field)->where($where)->order($order)->page($page, $pageSize)->select();
            }
        } else {
            if(!empty($goods_area_list) && !empty($whereor)) {
                $arr=$result->field($field)
                    ->where($where)
                    ->where(function ($query)use($whereor){
                        $query->where('ma.area_id',0)->whereOr($whereor);
                    })
                    ->group('s.goods_id')
                    ->order($order?:'s.goods_id asc')
                    ->select()
                    ->toArray();
            }else {
                $arr = $this->alias('s')->field($field)->where($where)->order($order)->select();
            }
        }
        return $arr;
    }

    /**
     * 根据条件获取商品总数
     * @param $where
     */
    public function getGoodsByConditionCount($where)
    {
        $count = $this->alias('s')->where($where)->count('s.goods_id');
        return $count;
    }

    /**
     * 根据条件获取商品总数
     * @param $where
     */
    public function getCountByCondition($where)
    {
        $count = $this->field('goods_id')->where($where)->count('goods_id');
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
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('g')
            ->join($prefix . 'mall_activity_detail d', 'd.goods_id=g.goods_id')
            ->field($gfield)
            ->where($gwhere)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 获取某个活动下的所有商品总数
     * @param $gwhere
     * @param $gfield
     * @return array
     */
    public function getActGoodsCount($gwhere)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('g')
            ->join($prefix . 'mall_activity_detail d', 'd.goods_id=g.goods_id')
            ->field('g.goods_id')
            ->where($gwhere)
            ->count('g.goods_id');
        return $count;
    }

    /**
     * @param $goods_id
     * @param $sku_id
     * @return mixed
     * 获取商品名称和规格信息
     */
    public function getGoodsNameAndSku($goods_id, $sku_id)
    {
        if($goods_id!=0 && !empty($goods_id)){
            $gwhere[] = ['g.goods_id', '=', $goods_id];
        }
        $gwhere[] = ['d.sku_id', '=', $sku_id];
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('g')
            ->join($prefix . 'mall_goods_sku d', 'd.goods_id=g.goods_id')
            ->field('g.name,d.sku_str,d.image')
            ->where($gwhere)
            ->find();
        if(!empty($count)){
            $count=$count->toArray();
        }
        return $count;
    }

    /**
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 根据门店找商品
     */
    public function getGoodsListByStoreId($store_id,$uid=0)
    {
        //取热卖商品id
        $condition[] = ['store_id', '=', $store_id];

        $list = $this->where($condition)->select()->toArray();
        $goods = array();
        /*三个标签*/
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $goods[$key]['goods_id'] = $val['goods_id'];
                $goods[$key]['goods_image'] = $val['image'] ? replace_file_domain($val['image']) : '';
                $goods[$key]['goods_name'] = $val['name'];
                $goods[$key]['goods_price'] = get_format_number($val['price']);
                $goods[$key]['payed'] = $val['sale_num'];
                $result = $this->globelLabel($store_id, $val['goods_id'],$val['mer_id'],$uid);
                $goods[$key]['label_list'] = $result;
            }
        }
        return $goods;
    }

    /**
     * @param $goods_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 根据商品id查找相关商品信息
     */
    public function getGoodsByGoodsId($goods_id,$uid=0)
    {
        $condition[] = ['goods_id', '=', $goods_id];
        $list = $this->where($condition)->find()->toArray();
        $goods = array();
        /*三个标签*/
        if (!empty($list)) {
            $goods['goods_id'] = $list['goods_id'];
            $goods['goods_image'] = $list['image'] ? replace_file_domain($list['image']) : '';
            $goods['goods_name'] = $list['name'];
            $goods['goods_price'] = get_format_number($list['price']);
            $goods['payed'] = $list['sale_num'];
            $result = $this->globelLabel($list['store_id'], $list['goods_id'],$list['mer_id'],$uid);
            $goods['label_list'] = $result;
        }
        return $goods;
    }

    /**
     * @param $goods_id
     * @return MallGoods
     * 删除商品
     */
    public function delOne($goods_id)
    {
        $res = $this->where(['goods_id' => $goods_id])->update(['is_del' => 1]);
        return $res;
    }

    /**
     * 返回总数
     * @param $col
     * @return int
     */
    public function getCount($where, $col)
    {
        $count = $this->where($where)->count($col);
        return $count;
    }

    /**
     * 获取分销商品列表
     * @param $store_id
     * @param $pageSize 每页显示的数量
     * @return Object
     */
    public function getDistributeGoodsList($store_id, $pageSize)
    { 
        $field = 'goods_id,store_id,mer_id,price,name,image'; 
        $where = array(
            ['store_id', '=', $store_id],
            ['is_del', '=', 0],
            ['status', '=', 1]
        );
        $list =  $this->field($field)->where($where)->paginate($pageSize);
        return $list;
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListTool($where, $field = 'r.*',$page=1,$pageSize=10)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'merchant m', 'm.mer_id = r.mer_id')
            ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
            ->where($where)
            ->order('r.goods_id desc');
        $out['total']=$arr->count();
        $out['list']=$arr->page($page, $pageSize)
            ->select()->toArray();
        return $out;
    }

    public function getAuditStatusTextAttr($value, $data)
    {
        return $this->auditStatusMap[$data['audit_status']] ?? '';
    }

    public function getGoodsAuditInfo($idAry)
    {
        $where[] = ['goods_id','in',$idAry];
        $where[] = ['audit_status','<>',1];
        $data = $this->where($where)->field('goods_id')->select()->toArray();
        return $data;
    }
    
    /**
     * 获取库存不足需要提醒的商品
     */
    public function getStockWarnGoodsInfo($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix . 'mall_goods_sku b','a.goods_id = b.goods_id','left')
            ->where($where)
            ->field($field)
            ->group('a.goods_id')
            ->select()
            ->toArray();
        return $data;
    }
    
    public function getStockGoodsInfo($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix . 'mall_goods_sku b','a.goods_id = b.goods_id','left')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $data;
    }
    
    public function getGoodsInfoAndMerchant($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix . 'merchant b','a.mer_id = b.mer_id')
            ->join($prefix . 'merchant_store c', 'a.store_id = c.store_id')
            ->where($where)
            ->field($field)
            ->find()
            ->toArray();
        return $data;
    }
    
    public function getMallList($where,$field,$pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('a')
            ->field($field)
            ->join($prefix . 'merchant b', 'a.mer_id = b.mer_id')
            ->join($prefix . 'merchant_store c', 'a.store_id = c.store_id')
            ->where($where)
            ->order('a.goods_id desc')
            ->paginate($pageSize)
            ->toArray();
        return $list;
    }

    public function getWarn($goodsInfo)
    {
        $warn = '';
        if($goodsInfo['audit_status'] != 1){
            $warn = '商品审核状态异常';
        }
        if($goodsInfo['status'] != 1){
            $warn = $warn ? $warn.'/商品已下架' : '商品已下架';
        }
        if($goodsInfo['is_del']){
            $warn = $warn ? $warn.'/商品已被删除' : '商品已被删除';
        }
        if($goodsInfo['store_status'] != 1){
            $warn = $warn ? $warn.'/店铺状态异常' : '店铺状态异常';
        }
        if(!$goodsInfo['have_mall']){
            $warn = $warn ? $warn.'/未开启新版商城店铺' : '未开启新版商城店铺';
        }
        return $warn;
    }

    public function merchantStore()
    {
        return $this->belongsTo(MerchantStore::class, 'store_id', 'store_id');
    }

    public function merchantGoodsList($merId, $pageSize = 15, $params = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        
        $result = $this->alias('s')
            ->join('merchant_store_mall m', 's.store_id = m.store_id')
            ->join('merchant_store g', 's.store_id = g.store_id')
            ->join('merchant mt', 'mt.mer_id = s.mer_id')
            ->field(['s.goods_id', 's.name as goods_name', 's.price', 's.store_id','s.image'])
            ->where([
                's.mer_id'       => $merId,
                's.is_del'       => 0,
                's.status'       => 1,
                'g.mall_on_sale' => 1,
                'g.status'       => 1,
            ])
            ->with(['merchantStore' => function ($query) {
                $query->field('store_id, name');
            }])
            ->where('s.goods_id', 'NOT IN', function ($query)use($prefix) {
                $query->table($prefix.'wechat_live_goods')
                    ->where('goods_type', 'mall')
                    ->field('goods_id');
            });
            if(!empty($params['goods_name'])){
                $result = $result->where('s.name', 'like', "%{$params['goods_name']}%");
            }
            
            $result = $result->paginate(min($pageSize, 100))
            ->each(function ($item) {
                $item['store_name'] = $item['merchantStore']['name'];
                $item['cover_image'] = $item['image']?replace_file_domain($item['image']):'';
                unset($item['merchantStore']);
            });

        return $result;
    }

}