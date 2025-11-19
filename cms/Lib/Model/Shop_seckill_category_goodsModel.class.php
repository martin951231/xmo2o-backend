<?php
/*
 *平台外卖限时秒杀活动商品
 *author 衡婷妹
 *date 20210111
 */
class Shop_seckill_category_goodsModel extends Model
{

    /* 
	 * 获得秒杀商品列表
     */
    public function getList($param,$order='b.sort DESC',$page=0,$limit=10)
    {
        // 有库存
        $where['_string'] = '(l.stock = "-1" OR l.stock > 0)';

        $where['l.end_date'] = ['egt',date('Y-m-d')];
        $where['l.is_del'] = 0;
        $where['g.status'] = 1;

        if($param['cat_id']){
            $where['b.cat_id'] = $param['cat_id'];
        }

        if($param['cate.status']){
            $where['cate.status'] = $param['cate.status'];
        }

        if($param['city_id']){
            $where['_string'] .= ' AND (city.city_id='.$param['city_id'] . ' OR city.city_id is null)';
        }

        if($param['_string']){
            $where['_string'] .= ' AND ' .$param['_string'];
        }
        if(C('customization.life_tools')){
        $where['_string'] .= ' AND (ms.mer_id NOT IN (' . C('config.set_employee_card_mer_id') . ') OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
//        $where['_string'] .= ' AND (ms.mer_id <> ' . C('config.set_employee_card_mer_id') . ' OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
        if (!empty($param['uid'])) {
            $sql1 = "(select ecu.lable_ids from pigcms_employee_card_user as ecu where ecu.uid = " . $param['uid'] . " AND ecu.mer_id = g.employee_mer_id AND ecu.status = 1)";
            $where['_string'] .= " OR (concat(',',g.employee_lables,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),'))";
        }
        $where['_string'] .= ')';
        }
        $field = 'b.*,l.id as l_id,l.start_date,l.end_date,l.week,l.start_time,l.end_time,l.limit_num,l.limit_type,l.stock_type,l.is_spec,l.stock,l.origin_stock,l.limit_price,l.update_stock_date,l.is_del,g.name,g.price,g.image,g.sort_id,g.store_id,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,g.spec_value,g.is_properties';
        $list = $this->join(' as b join '.C('DB_PREFIX').'time_limited_discount_goods as l on l.goods_id=b.goods_id inner join '.C('DB_PREFIX').'shop_goods as g on g.goods_id=b.goods_id inner join '.C('DB_PREFIX').'shop_seckill_category as cate on cate.cat_id=b.cat_id left join '.C('DB_PREFIX').'shop_seckill_category_city as city on city.cat_id=b.cat_id inner join '.C('DB_PREFIX').'merchant_store as ms on ms.store_id = g.store_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->page($page,$limit)
                ->select();
        if (empty($list)) {
        	return [];
        }

        $returnArr = [];
        foreach ($list as $key => $value) {
            $value = $this->formatGoods($value);
           
            $returnArr[] = $value;
        }

        return $returnArr;
    }

    /* 
     * 组装数据
     */


    public function formatGoods($goods,$isShow=false)
    {
        $goods_image_class = new goods_image();
        // 商品id
        $returnArr['goods_id'] = $goods['goods_id'];

        // 店铺id
        $returnArr['store_id'] = $goods['store_id'];

        // 分类id
        $returnArr['sort_id'] = $goods['sort_id'];

        // 商品名称
        $returnArr['product_name'] = $goods['name'];
        
        //商品图片
        $tmp_pic_arr = $goods_image_class->get_allImage_by_path($goods['image'], 's');
        $returnArr['product_image'] = thumb_img($tmp_pic_arr[0],240,240,'fill');

        // 商品原价
        $returnArr['product_price'] = getFormatNumber($goods['price']);
        $returnArr['o_price'] = getFormatNumber($goods['price']);


        // 限时优惠数据
        $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($goods['goods_id']);
        if ($seckill_record &&($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
            //存在限时优惠
            $goods['is_seckill_price'] = true;
            if ($seckill_record['is_spec'] == 1) {
                $limit_prices = array_column($seckill_record['spec_list'], 'limit_price');
                $total_stock = array_sum(array_column($seckill_record['spec_list'], 'stock'));
                sort($limit_prices);
                $goods['price'] = isset($limit_prices[0]) ? getFormatNumber($limit_prices[0]) : getFormatNumber($goods['o_price']);
                $seckill_stock_num = intval($total_stock);
            } else {
                $goods['price'] = getFormatNumber($seckill_record['limit_price']);
                $seckill_stock_num = $seckill_record['stock'];
            }

            $goods['old_price'] = floatval($goods['old_price']);
            $goods['seckill_price'] = getFormatNumber($goods['price']);
            $goods['max_num'] = $seckill_record['limit_num'];
        } else {
            $goods['is_seckill_price'] = false;
            $goods['price'] = getFormatNumber($goods['price']);
            $seckill_stock_num = 0;
            $goods['old_price'] = floatval($goods['old_price']);
        }


        $returnArr['price'] = $goods['price'];
        $returnArr['stock_num'] = $seckill_stock_num;

         if ($goods['is_spec']) {
                // 获得规格的详情
                $specList = M('Time_limited_discount_goods_spec')->where(['limit_id'=>$goods['l_id']])->select();

                // 不计算限时优惠的价格
                $formatSpecSource = D('Shop_goods')->format_spec_value($goods['spec_value'], $goods['goods_id'], $goods['is_properties'],0,null,'');
                $formatSpecSourceList = isset($formatSpecSource['list']) ? $formatSpecSource['list'] : [];

                $miniDiscount = 0;
                $maxDiscount = 0;
                foreach ($specList as $k => &$s) {
                    $valId = explode('_', $s['spec_index']);
                    $where = [
                        'id'=>['in', $valId]
                    ];
                    $values = M('Shop_goods_spec_value')->where($where)->select();
                    $values = array_column($values ,'name');
                    $s['str'] = $values ? implode('、', $values) : L_('规格关系已变动');

                    // 现价
                    $s['price'] = getFormatNumber($s['limit_price']);

                    // 原价
                    $s['product_price'] = isset($formatSpecSourceList[$s['spec_index']]) ? getFormatNumber($formatSpecSourceList[$s['spec_index']]['price']) : '0';

                    // 折扣数
                    $discount = $s['product_price'] ? ($s['product_price']>$s['price'] ? round($s['price']/$s['product_price']*10,1) : 10) : 10;

                    $miniPrice = $k==0 ? $s['price'] : min($miniPrice, $s['price']);
                    $miniDiscount =  $k==0 ? $discount : min($miniDiscount, $discount);
                    $maxDiscount = max($maxDiscount, $discount);
                }
                $discount = $miniDiscount ?: 10;
            } else {

                // 折扣数
                $discount = $returnArr['product_price'] ? ($returnArr['product_price']>$returnArr['price'] ? round($returnArr['price']/$returnArr['product_price']*10,1) : 0) : 0;
                $discount = $discount ?: 10;
            }

        $returnArr['discount_number'] = $discount;
        return $returnArr;
    }
}
?>