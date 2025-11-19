<?php
/**
 * 外卖商品限时优惠
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/20 09:40
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoods as ShopGoodsModel;
use app\shop\model\db\TimeLimitedDiscountGoods as TimeLimitedDiscountGoodsModel;
use app\shop\model\service\goods\TimeLimitedDiscountGoodsSpecService as TimeLimitedDiscountGoodsSpecService;
use app\shop\model\service\goods\ShopGoodsService as  ShopGoodsService;
use think\facade\Db;
class TimeLimitedDiscountGoodsService{
    public $timeLimitedDiscountGoodsModel = null;
    public function __construct()
    {
        $this->timeLimitedDiscountGoodsModel = new TimeLimitedDiscountGoodsModel();
    }

    /**
     * [get_list 获取限时优惠列表]
     * @Author   JJC
     * @DateTime 2020-04-26T10:05:38+0800
     * @param    [array]   $param    [搜索条件]
     * @param    [string]  $shop_type [类型：shop快店，mall商城]
     * @return   [array]              [description]
     */
    public function getGoodsList($param,$shopType='shop')
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $isSelect = $param['is_select'] ?? 0;

        $where = [];
        if (isset($param['store_id']) && $param['store_id']) {
            $where[] = ['l.store_id', '=', $param['store_id']];
        }
        if (isset($param['id']) && $param['id']) {
            $where[] = ['l.id', '=', $param['id']];
        }
        if (isset($param['keywords']) && $param['keywords']) {
            $keywords = addslashes($param['keywords']);
            $where[] = ['g.name', 'exp', Db::raw('like "%' . $keywords . '%" OR s.name like "%'.$keywords.'%"  OR m.name like "%' . $keywords . '%" OR g.goods_id like "%' . $keywords . '%"')];
        }
        if (isset($param['start_date']) && $param['start_date']) {
            $where[] = ['l.start_date', '>=',  $param['start_date']];
        }
        if (isset($param['end_date']) && $param['end_date']) {
            $where[] = ['l.end_date', '<=',  $param['end_date']];
        }

        if($shopType == 'mall'){
            $where[] = ['g.cat_fid', '>',  0];
        }else{
            $where[] = ['g.goods_type', '<>',  1];
        }

        $today = date('Y-m-d');
        if ($param['status']) {
            $where[] = ['l.is_del', '=',  0];
            $where[] = ['g.goods_type', '<>',  1];

            //1：待生效  2：进行中   3：已结束  4:已撤销
            if ($param['status'] == 1) {
                $where[] = ['l.start_date', '>',  $today];
            } else if ($param['status'] == 2) {
                $where[] = ['l.start_date', '<=',  $today];
                $where[] = ['l.end_date', '>=',  $today];
            } else if ($param['status'] == 3) {
                $where[] = ['l.end_date', '<',  $today];
            } else if ($param['status'] == 4) {
                $where[] = ['l.is_del', '=',  0];
            }
        }

        $field = 'l.*,g.name AS name,g.price,g.image,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,s.name as store_name,m.name as merchant_name,g.spec_value,g.is_properties';
        $count = $this->getGoodsCountByJoin($where);
        $lists = $this->getGoodsListByJoin($where,$field,[],$page,$pageSize);

        $shopGoodsSpecValueService = new ShopGoodsSpecValueService();
        foreach ($lists as $key => &$l) {
            $l['start_time'] = substr($l['start_time'], 0, 5);
            $l['end_time'] = substr($l['end_time'], 0, 5);
            $tmp_pic_arr = array_filter(explode(';', $l['image']));
            $l['image'] = (new GoodsImageService())->getImageByPath(isset($tmp_pic_arr[0]) ? $tmp_pic_arr[0] : '', 0);
            if ($l['is_spec']) {
                // 获得规格的详情
                $specList = (new TimeLimitedDiscountGoodsSpecService())->getSpecList($l['id']);
                $formatSpec = (new ShopGoodsService())->formatSpecValue($l['spec_value'], $l['goods_id'], $l['is_properties']);

                // 不计算限时优惠的价格
                $formatSpecSource = (new ShopGoodsService())->formatSpecValue($l['spec_value'], $l['goods_id'], $l['is_properties'],0,null,'',0);
                $formatSpecList = isset($formatSpec['list']) ? $formatSpec['list'] : [];
                $formatSpecSourceList = isset($formatSpecSource['list']) ? $formatSpecSource['list'] : [];

                $miniPrice = 0;
                $maxPrice = 0;
                $miniDiscount = 0;
                $maxDiscount = 0;
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
                    $discount = $s['product_price'] ? round($s['price']/$s['product_price']*10,1) : 0;

                    // 折扣数
                    $discount = $s['product_price'] ? ($s['product_price']>$s['price'] ? round($s['price']/$s['product_price']*10,1) : 10) : 10;
                    $miniPrice = $k==0 ? $s['price'] : min($miniPrice, $s['price']);
                    $maxPrice = max($maxPrice, $s['price']);
                    $miniDiscount =  $k==0 ? $discount : min($miniDiscount, $discount);
                    $maxDiscount = max($maxDiscount, $discount);
                }
                $l['mini_price'] = $miniPrice;
                $l['max_price'] = $maxPrice;
                $l['mini_discount'] = $miniDiscount;
                $l['max_discount'] = $maxDiscount;
                $l['spec_list'] = $specList;
                $l['spec_count'] = count($specList);
            } else {
                // 原价
                $l['product_price'] = get_format_number($l['price']);
                $l['price'] = get_format_number($l['limit_price']);

                // 折扣数
                $discount = $l['product_price'] ? round($l['price']/$l['product_price']*10,1) : 0;
                $l['discount'] = $discount;
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

            if($l['reason_for_not_sale'] && $isSelect){
                unset($lists[$key]);
            }
        }
        return ['list' => $lists, 'total' => $count];
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getGoodsListByJoin($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->timeLimitedDiscountGoodsModel->getGoodsListByJoin($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getGoodsCountByJoin($where = []){
        $result = $this->timeLimitedDiscountGoodsModel->getGoodsCountByJoin($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 根据条件获取分类列表
     * @return array
     */
    public function getDiscountByGoodsId($goodsId){
        if(!$goodsId){
            return [];
        }
        $result = $this->timeLimitedDiscountGoodsModel->getDiscountByGoodsId($goodsId);
        if(empty($result)){
            return [];
        }
        return $result->toArray();
    }
    
    /**
     * 根据商品id获取进行中的活动
     * @param $goodsId string 商品id
     * @param $showLimit string 获得规格时是否包含限时优惠
     * @return array
     */
    public function getInProgressRecord($goodsId,$showLimit=1){
        // 验证商品
        $shopGoodsModel = new ShopGoodsModel();
        $goods = $shopGoodsModel->getGoodsByGoodsId($goodsId);
        if (!$goods || ($goods['stock_num'] != -1 && $goods['stock_num'] < 1)) {
            return [];
        }

        // 获得限时优惠
        $record = $this->getDiscountByGoodsId($goodsId);
        if (empty($record)) {
            return [];
        }

        if ($record['limit_type'] == 0) {
            $record['limit_num'] = -1;
        }

        // 商品名
        $record['goods_name'] = $goods['name'];
        
        //每日更新库存
        if ($this->updateStockEveryday($record)) {
            $record['stock'] = $record['origin_stock'];
        }

        // 有规格
        if ($record['is_spec'] == 1) {
            $field = 'spec_index,id,limit_id,stock,origin_stock,limit_price';
            $record['spec_list'] = (new TimeLimitedDiscountGoodsSpecService())->getSpecList($record['id'], $field);

            //如果多规格变动，则限时优惠不生效
            $spec_arr = $record['spec_list'] ? array_unique(array_column($record['spec_list'], 'spec_index')) : [];

            // 获得商品规格
            $formatSpec = (new ShopGoodsService())->formatSpecValue($goods['spec_value'], $goods['goods_id'], $goods['is_properties'],0,null,'',$showLimit);
            
            $formatSpecList = isset($formatSpec['list']) ? $formatSpec['list'] : [];
            $formatSpecListKeys = array_keys($formatSpecList);
            if (array_diff($spec_arr, $formatSpecListKeys)) {
                return [];
            }
        } else {
            //如果商品增加了规格，则限时优惠不生效
            if ($goods['spec_value']) {
                return [];
            }
            $record['spec_list'] = [];
        }

        if(!empty($record['is_spec'])){
            $record['limit_price']=$record['spec_list'][0]['limit_price'];
        }else{
            if($record['limit_price']>0){
                $record['limit_price']=$record['limit_price'];
            }else{
                $record['limit_price']=$goods['price'];
            }

        }
        
        return $record;
    }

    /**
     * 每日更新库存
     * @param $record
     * @return bool
     */
    public function updateStockEveryday($record){
        $today = date('Y-m-d');
        if ($record['stock_type'] == 0 && $record['update_stock_date'] != $today) {
            //每日更新库存
            $data = [
                'stock' => $record['origin_stock'],
                'update_stock_date' => $today
            ];
            
            // 重置库存
            $this->updateById($record['id'], $data);

            // 重置规格库存
            if ($record['is_spec']) {
                $res = (new TimeLimitedDiscountGoodsSpecService())->resetStock($record['id']);
            }
        }else{
            return false;
        }
        return true;
    }

    /**
     * 更新库存
     * @param $num
     * @param $id
     * @param $type 操作类型 0：减库存，1：加库存
     * @return bool
     */
    public function updateStock($num, $id, $type){
        // 限时优惠详情
        $discount = $this->getDiscountById($id);
        if(!$discount){
            return false;
        }

        // 更新数据
        $data = [];
        if ($type == 0) {
            $data['stock'] = $discount['stock'] - $num;
            $data['stock'] = max(0,$data['stock']);
        } else {
            $data['stock'] = $discount['stock'] + $num;
        }

        // 更新库存
        $result = $this->updateById($id, $data);
        $today = date('Y-m-d');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 根据id获取
     * @param $id
     * @return array
     */
    public function getDiscountById($id){
        if(!$id){
            return [];
        }
        $result = $this->timeLimitedDiscountGoodsModel->getDiscountById($id);
        if(empty($result)){
            return [];
        }
        return $result->toArray();
    }
    
    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateById($id,$data){
        if (!$id || !$data) {
            return false;
        }
        
        try {
            $result = $this->timeLimitedDiscountGoodsModel->updateById($id,$data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    
    
    /**
     * 新增数据
     * @param $data
     * @return bool|intval
     */
    public function save($data){
        if (!$data) {
            return false;
        }
        
        try {
            $result = $this->timeLimitedDiscountGoodsModel->add();
        }catch (\Exception $e) {
            return false;
        }
        
        return $result->id;
    }

}