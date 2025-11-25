<?php
/**
 * 系统后台可视化页面 外卖首页-限时秒杀功能 基础配置
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/21
 */

namespace app\common\model\service\viewpage;
use app\common\model\db\ShopSeckillCategoryGoods;
use app\shop\model\service\goods\GoodsImageService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\shop\model\service\goods\ShopGoodsSpecValueService;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsService;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsSpecService as TimeLimitedDiscountGoodsSpecService;
use app\shop\model\service\store\MerchantStoreShopService;
use think\facade\Db;

class ShopSeckillCategoryGoodsService {
    public $shopSeckillCategoryGoodsModel = null;
    public function __construct()
    {
        $this->shopSeckillCategoryGoodsModel = new ShopSeckillCategoryGoods();
    }

    /**
     * 批量添加分类绑定的商品
     * @param $param array
     * @return array
     */
    public function addCategoryGoods($param){
        $catId = $param['cat_id'] ?? 0;
        $goodsIds = $param['goods_ids'] ?? [];
        if(empty($catId) || empty($goodsIds)){
            throw  new \think\Exception('缺少参数',1001);
        }

        $where = [
            'cat_id' => $catId
        ];
        $detail = (new ShopSeckillCategoryService())->getOne($where);
        if(empty($detail)){
            throw  new \think\Exception('分类不存在',1003);
        }
        // 查看已添加的
        $goodsList = $this->getSome($where);
        $goodsidArr = array_column($goodsList,'goods_id');
        $saveData = [];
        foreach ($goodsIds as $goodsId){
            if(in_array($goodsId,$goodsidArr)){
                continue;
            }
            $saveData[] = [
                'cat_id' => $catId,
                'goods_id' => $goodsId,
                'sort' => 0,
                'create_time' => time(),
            ];
        }
        if(empty($saveData)){
            return ['status'=>1];
        }
        $res = $this->addAll($saveData);
        if($res === false){
            throw  new \think\Exception('添加失败，请稍后重试',1003);
        }
        return ['status'=>0];
    }

    /**
     * 批量删除分类绑定的商品
     * @param $param array
     * @return array
     */
    public function delCategoryGoods($param){
        $catId = $param['cat_id'] ?? 0;
        $goodsIds = $param['goods_ids'] ?? [];
        if(empty($catId) || empty($goodsIds)){
            throw  new \think\Exception('缺少参数',1001);
        }

        $where = [
            'cat_id' => $catId
        ];
        $detail = (new ShopSeckillCategoryService())->getOne($where);
        if(empty($detail)){
            throw  new \think\Exception('分类不存在',1003);
        }

        $where = [
            ['cat_id' ,'=', $catId],
            ['id' ,'in', implode(',',$goodsIds)],
        ];
        $res = $this->shopSeckillCategoryGoodsModel->where($where)->delete();
        if($res === false){
            throw  new \think\Exception('删除失败，请稍后重试',1003);
        }
        return $res;
    }

    /**
     * 批量删除分类绑定的商品
     * @param $param array
     * @return array
     */
    public function editCategoryGoodsSort($param){
        $id = $param['id'] ?? [];
        $sort = $param['sort'] ?? [];
        if(empty($id)){
            throw  new \think\Exception('缺少参数',1001);
        }

        $saveData = [
            'sort' => $sort
        ];

        $where = [
            ['id' ,'=', $id],
        ];
        $res = $this->updateThis($where,$saveData);
        if($res === false){
            throw  new \think\Exception('删除失败，请稍后重试',1003);
        }
        return $res;
    }

    /**
     * 首页限时秒杀列表页-头部商品列表
     * @param $param array
     * @return array
     */
    public function shopSeckillTopGoodsList($param){

        $cityId = $param['now_shop_city'] ?? ($param['now_city'] ?? 0);
        if(!$cityId){
            $cityId = cfg('now_shop_city') ?: (cfg('now_city') ?: 0);
        }

        $returnArr['list'] = [];
        $returnArr['total'] = 0;
        if(empty($cityId)){
            return $returnArr;
        }

        $param['cat_id'] = 1;
        $param['city_id'] = $cityId;
        $param['pageSize'] = 6;
        $param['is_show'] = 1;
        $returnArr = $this->getCategoryGoodsList($param);
        $returnArr['list'] = $this->formatGoods($returnArr['list']);

        return $returnArr;
    }

    /**
     * 首页限时秒杀列表页-每个分类下的商品列表
     * @param $param array
     * @return array
     */
    public function shopSeckillCategoryGoodsList($param){

        $cityId = $param['now_shop_city'] ?? ($param['now_city'] ?? 0);
        $catId = $param['cat_id'] ?? 0;

        $returnArr['list'] = [];
        $returnArr['total'] = 0;
        if(empty($catId)){
            return $returnArr;
        }

        $param['cat_id'] = $catId;
        $param['pageSize'] = 10;
        $param['is_show'] = 1;
        $returnArr = $this->getCategoryGoodsList($param);
        $returnArr['list'] = $this->formatGoods($returnArr['list']);

        return $returnArr;
    }

    /**
     * 处理商品数据
     * @param $param array
     * @return array
     */
    public function formatGoods($goodsList){
        $returnArr = [];
        if(isset($goodsList[0])){
            $storeIdArr = array_column($goodsList,'store_id');
            $where[] = [
                'store_id','in',implode(',',$storeIdArr)
            ];
            $shopStoreList = (new MerchantStoreShopService())->getSome($where);
            $shopStoreListFormat = [];
            foreach ($shopStoreList as $_store){
                $shopStoreListFormat[$_store['store_id']] = $_store;
            }
            foreach ($goodsList as $goods){
                $temp = [];

                // 商品id
                $temp['goods_id'] = $goods['goods_id'];

                // 店铺id
                $temp['store_id'] = $goods['store_id'];

                // 分类id
                $temp['sort_id'] = $goods['sort_id'];

                // 商品名称
                $temp['product_name'] = $goods['name'];

                // 店铺名称
                $temp['store_name'] = $goods['store_name'];

                //商品图片
                $tmpPicArr = (new GoodsImageService())->getAllImageByPath($goods['image'], 's');
                $temp['product_image'] = thumb_img($tmpPicArr[0],240,240,'fill');

                // 商品原价
                $temp['product_price'] = get_format_number($goods['product_price']);

                // 商品优惠价
                $temp['price'] = get_format_number($goods['price']);
                // 折扣数
                $temp['discount_number'] = $goods['mini_discount'] ?? ($goods['discount']);

                // 库存
                $temp['stock_num'] = $goods['stock'] ?? ($goods['discount']);

                $store = $shopStoreListFormat[$goods['store_id']] ?? [];
                $store['long'] = $goods['long'];
                $store['lat'] = $goods['lat'];
                $store['province_id'] = $goods['province_id'];
                $store['city_id'] = $goods['city_id'];
                $store['area_id'] = $goods['area_id'];
                $param = [
                    'store' => $store,
                    'lng' => request()->lng,
                    'lat' => request()->lat,
                ];

                $temp['delivery'] = $store['deliver_type'] == 2 ? false : true;//是否显示配送信息 true-是 false-否
                $temp['delivery_time'] = '';//配送时间
                $temp['delivery_time_type'] = '';//配送时间单位
                $temp['delivery_money'] = '';//配送费
                $res = invoke_cms_model('Merchant_store/get_store_deliver_info',$param);
                if($res && !$res['error_msg']){
                    $store = $res['retval'];
                    $temp['delivery_time'] = $store['delivery_time'];
                    $temp['delivery_time_type'] = $store['delivery_time_type'];
                    $temp['delivery_money'] = $store['delivery_money'];
                }
                
                $returnArr[] = $temp;
            }
        }elseif($goodsList){
            $goods = $goodsList;
            // 商品id
            $returnArr['goods_id'] = $goods['goods_id'];

            // 店铺id
            $returnArr['store_id'] = $goods['store_id'];

            // 分类id
            $returnArr['sort_id'] = $goods['sort_id'];

            // 商品名称
            $returnArr['product_name'] = $goods['name'];

            // 店铺名称
            $returnArr['store_name'] = $goods['store_name'];

            //商品图片
            $tmpPicArr = (new GoodsImageService())->getAllImageByPath($goods['image'], 's');
            $returnArr['product_image'] = thumb_img($tmpPicArr[0],240,240,'fill');

            // 商品原价
            $returnArr['product_price'] = get_format_number($goods['product_price']);

            // 商品优惠价
            $returnArr['price'] = get_format_number($goods['price']);

            // 折扣数
            $returnArr['discount_number'] = $goods['mini_discount'] ?? ($goods['discount']);

            // 库存
            $returnArr['stock_num'] = $goods['stock'] ?? ($goods['discount']);
        }
        return $returnArr;
    }

    /**
     * 获得分类绑定的商品列表
     * @param $where array
     * @return array
     */
    public function getCategoryGoodsList($param){
        $catId = $param['cat_id'] ?? 0;
        $cityId = $param['city_id'] ?? 0;
        $isShow = $param['is_show'] ?? 0;
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $keywords = $param['keywords'] ?? '';
        if(empty($catId)){
            throw  new \think\Exception('缺少参数',1001);
        }

        // 排序
        $order = [
            'b.sort' => 'DESC',
            'b.id' => 'ASC',
        ];

        $where = [
            ['b.cat_id' ,'=', $catId],
        ];

        // 城市
        if($cityId){
            $where[] = [ 'c.city_id','exp', Db::raw('='.$cityId.' OR c.city_id is null')];
        }

        // 前端展示时间范围内的
        if($isShow){
            $week = date('w');
            $where[] = [ 'l.end_date','>=', date('Y-m-d')];
            $where[] = [ 'l.end_time','>=', date('H:i')];
            $where[] = [ 'l.start_date','<=', date('Y-m-d')];
            $where[] = [ 'l.start_time','<=', date('H:i')];
            $where[] = [ 'l.is_del','=', 0];
            $where[] = [ 'g.status','=', 1];
            $where[] = [ '','exp', Db::raw('FIND_IN_SET('.$week.',week)')];
            $where[] = [ 'l.stock','exp', Db::raw('="-1" OR l.stock>0')];
        }

        if(!empty($keywords)){
            $keywords = addslashes($keywords);
            $where[] = ['g.name', 'exp', Db::raw('like "%' . $keywords . '%" OR m.name like "%'.$keywords.'%"  OR s.name like "%' . $keywords . '%"')];
        }
        $field = 'b.*,l.id as l_id,l.start_date,l.end_date,l.week,l.start_time,l.end_time,l.limit_num,l.limit_type,l.stock_type,l.is_spec,l.stock,l.origin_stock,l.limit_price,l.update_stock_date,l.is_del,g.name,g.price,g.image,g.sort_id,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,s.store_id,s.area_id,s.city_id,s.province_id,s.name as store_name,s.lat,s.long,m.name as merchant_name,g.spec_value,g.is_properties';
        $count = $this->getGoodsCountByJoin($where);
        $start = ($page-1)*$pageSize;
        if($start>=$count && request()->agent == 'pc'){
            $page = 1;
        }
        $list = $this->getGoodsListByJoin($where,$field,$order,$page,$pageSize);

        $shopGoodsSpecValueService = new ShopGoodsSpecValueService();
        foreach ($list as $key => &$l) {
            $l['start_time'] = substr($l['start_time'], 0, 5);
            $l['end_time'] = substr($l['end_time'], 0, 5);
            if ($l['is_spec']) {
                // 获得规格的详情
                $specList = (new TimeLimitedDiscountGoodsSpecService())->getSpecList($l['l_id']);

                // 不计算限时优惠的价格
                $formatSpecSource = (new ShopGoodsService())->formatSpecValue($l['spec_value'], $l['goods_id'], $l['is_properties'],0,null,'',0);
                $formatSpecSourceList = isset($formatSpecSource['list']) ? $formatSpecSource['list'] : [];

                $miniPrice = 0;
                $maxPrice = 0;
                $maxOldPrice = 0;
                $minOldPrice = 0;
                $miniDiscount = 0;
                $maxDiscount = 0;
                $maxStock = 0;
                foreach ($specList as $k => &$s) {
                    $valId = explode('_', $s['spec_index']);
                    $where = [
                        ['id', 'in', $valId]
                    ];
                    $values = $shopGoodsSpecValueService->getSome($where);
                    $values = array_column($values ,'name');
                    $s['str'] = $values ? implode('、', $values) : L_('规格关系已变动');

                    // 现价
                    $s['price'] = get_format_number($s['limit_price']);

                    // 原价
                    $s['product_price'] = isset($formatSpecSourceList[$s['spec_index']]) ? get_format_number($formatSpecSourceList[$s['spec_index']]['price']) : '0';

                    // 折扣数
                    $discount = $s['product_price'] ? ($s['product_price']>$s['price'] ? round($s['price']/$s['product_price']*10,1) : 10) : 10;

                    $miniPrice = $k==0 ? $s['price'] : min($miniPrice, $s['price']);

                    $maxPrice = max($maxPrice, $s['price']);
                    $miniDiscount =  $k==0 ? $discount : min($miniDiscount, $discount);
                    $maxDiscount = max($maxDiscount, $discount);
                    if ($k == 0) {
                        $maxStock = $s['stock'];
                    } else if ($s['stock'] == -1) {
                        $maxStock = -1;
                    } else if ($maxStock != -1) {
                        $maxStock = max($maxStock, $s['stock']);
                    }
                    
                    $maxOldPrice = max($maxOldPrice, $s['product_price']);
                    $minOldPrice =  $k==0 ? $s['product_price'] : min($minOldPrice, $s['product_price']);
                }
                $l['mini_price'] = $miniPrice;
                $l['max_price'] = $maxPrice;
                $l['mini_discount'] = $miniDiscount ?: 10;
                $l['max_discount'] = $maxDiscount ?: 10;
                $l['spec_list'] = $specList;
                $l['stock'] = $maxStock;
                $l['price'] = $miniPrice;
                $l['product_price'] = $maxOldPrice;
                $l['product_price_min'] = $minOldPrice;
                $l['spec_count'] = count($specList);
            } else {
                // 原价
                $l['product_price'] = get_format_number($l['price']);
                $l['price'] = get_format_number($l['limit_price']);

                // 折扣数
                $discount = $l['product_price'] ? ($l['product_price']>$l['price'] ? round($l['price']/$l['product_price']*10,1) : 0) : 0;
                $l['discount'] = $discount ?: 10;
                $l['spec_list'] = [];
                $l['spec_count'] = 0;
            }

            if (empty($l['goods_name'])) {
                $l['reason_for_not_sale'] = L_('原商品已被删除');
            } else if ($l['goods_stock'] == 0) {
                $l['reason_for_not_sale'] = L_('原商品当前库存为0');
            } else if ($l['goods_status'] != 1) {
                $l['reason_for_not_sale'] = L_('原商品已下架');
            } else if ($l['is_spec'] == 0 && $l['spec_value']) {
                $l['reason_for_not_sale'] = L_('规格关系已变动');
            } else {
                $l['reason_for_not_sale'] = '';
            }
            // 是否过期
            $l['over_time'] = 0;
            if(!$l['l_id'] || strtotime($l['end_date'].' '.$l['end_time']) < time()){
                $l['over_time'] = 1;
            }
        }
        $returnArr['list'] = $list;
        $returnArr['total'] = $count;
        return $returnArr;
    }

    /*
	 * 获得秒杀结束时间
     */
    public function getLastTime()
    {
        $week = date('w');
        $where = [
            ['l.is_del','=', 0],
            [ '','exp', Db::raw('FIND_IN_SET('.$week.',week)')]
        ];
        $prefix = config('database.connections.mysql.prefix');
        $order = [
            'l.end_date' => 'DESC',
            'l.end_time' => 'DESC',
        ];
        $info = $this->shopSeckillCategoryGoodsModel->alias('g')
            ->join([$prefix . 'time_limited_discount_goods' => 'l'], 'l.goods_id=g.goods_id')
            ->where($where)
            ->order($order)
            ->find();
        if (empty($info)) {
            return 0;
        }
        $datetime = $info['end_date'] . ' ' . $info['end_time'];
        return strtotime($datetime);
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getGoodsListByJoin($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->shopSeckillCategoryGoodsModel->getGoodsListByJoin($where,$field, $order, $page,$limit);
//        var_dump($this->shopSeckillCategoryGoodsModel->getLastSql());
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据总数
     * @param $where array
     * @return array
     */
    public function getGoodsCountByJoin($where = []){
        $result = $this->shopSeckillCategoryGoodsModel->getGoodsCountByJoin($where);
        if(empty($result)) return 0;
        return $result;
    }


    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        $id = $this->shopSeckillCategoryGoodsModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->shopSeckillCategoryGoodsModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     * 获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->shopSeckillCategoryGoodsModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }


    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->shopSeckillCategoryGoodsModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}