<?php
class Foodshop_goods_libraryModel extends Model
{
    //获得推荐菜列表
    public function getRecommendListByStoreId($store_id)
    {
        $where=array(
            's.store_id' => $store_id, 
            'fs.status' => 1, 
            'fs.is_recommend' => 1
        );
       
        $order = 's.sort DESC, fs.goods_id ASC';

        $goodsList = M(_view('Shop_goods'))->field(true)->field('s.*,fs.*')->join(' as s inner join ' . C('DB_PREFIX').'foodshop_goods_library as fs on fs.goods_id=s.goods_id')->where($where)->order($order)->select();
        // var_dump(M(_view('Shop_goods'))->_sql());
		foreach ($goodsList as &$_good) {
            $_good = $this->getFormartData($_good);
        }
        return $goodsList;
    }

    /**
     * 根据条件获取商品列表
     * @return array
     */
    public function getFormartData($nowGoods, $isDetail = 0){
        $spicyArray = D('Shop_goods')->spicy_arr();
        $goods_image_class = new goods_image();
        $returnArr = [];
        // 售卖时间
        if ($nowGoods['spec_type'] == 1) {
            $nowTime = date('H:i');
            if (!$nowGoods['spec_open_time']&&!$nowGoods['spec_close_time']) {
                $openTime = '00:00';
                $closeTime = '24:00';
            }else{
                $openTime = date('H:i', $nowGoods['spec_open_time']);
                $closeTime = date('H:i', $nowGoods['spec_close_time']);
            }
        } else {
            $nowTime = time();
            if (!$nowGoods['spec_open_datetime']&&!$nowGoods['spec_close_datetime']) {
                $openTime = strtotime(date('Y-m-d 00:00:00'));
                $closeTime = strtotime(date('Y-m-d 23:59:59'));
            }else{
                $openTime = $nowGoods['spec_open_datetime'];
                $closeTime = $nowGoods['spec_close_datetime'];
            }
        }

        // 是否在售卖时间内
        $returnArr['is_time_out'] = false;
        if ( $openTime > $nowTime || $closeTime < $nowTime){
            $returnArr['is_time_out'] = true;
        } 
        
        // 库存
        if ($nowGoods['spec_stock'] == -1) {
            $returnArr['stock'] = $nowGoods['stock_num'];
        }else{
            $returnArr['stock'] = $nowGoods['spec_stock'];
        }

        // 是否售完
        $returnArr['is_sell_out'] = false;
        if ($returnArr['stock'] != -1 && $returnArr['stock'] <=0 ) {
            // 已售完
            $returnArr['is_sell_out'] = true;
        }

        // 商品id
        $returnArr['product_id'] = $nowGoods['goods_id'];
        // 店铺ID
        $returnArr['store_id'] = $nowGoods['store_id'];
        // 分类
        $returnArr['spec_sort_id'] = $nowGoods['spec_sort_id'];
        // 商品名
        $returnArr['product_name'] = html_entity_decode($nowGoods['name'], ENT_QUOTES);
        $returnArr['name'] = $returnArr['product_name'];
        // 商品描述
        $returnArr['product_describe'] = html_entity_decode($nowGoods['describe'], ENT_QUOTES);
        // 商品价格
        $returnArr['product_price'] = strval(floatval($nowGoods['price']));
        // 商品原价
        $returnArr['old_price'] = strval(floatval($nowGoods['old_price']));
        // 商品条形码
        $returnArr['number'] = $nowGoods['number'];
        // 打包费
        $returnArr['packing_charge'] = strval(floatval($nowGoods['packing_charge']));
        // 单位
        $returnArr['unit'] = $nowGoods['unit'];
        // 起购数量
        $returnArr['min_num'] = $nowGoods['min_num'];
        // 是否推荐 1推荐
        $returnArr['is_recommend'] = $nowGoods['is_recommend'];
        // 是否仅店员可操作0-否，1-是
        $returnArr['only_staff'] = $nowGoods['only_staff'];
        //辣度
        $returnArr['spicy_name'] = isset($spicyArray[$nowGoods['spicy']]) ? $spicyArray[$nowGoods['spicy']] : '';
        $returnArr['spicy'] = $nowGoods['spicy'];
        //口味
        $returnArr['flavor'] = $nowGoods['flavor'] ? explode('&^&',$nowGoods['flavor']) : array();
        //原料
        $returnArr['material'] = $nowGoods['material'] ? explode('&^&',$nowGoods['material']) : array();
        //特殊食材
        $returnArr['special_food_materials'] = $nowGoods['special_food_materials'] ? explode('&^&',$nowGoods['special_food_materials']) : array();
        //做法
        $returnArr['cooking_methods'] = $nowGoods['cooking_methods'] ? explode('&^&',$nowGoods['cooking_methods']) : array();
        
        //商品缩略图
          
        //商品缩略图
        if($nowGoods['image']){
            $tmp_pic_arr = $goods_image_class->get_allImage_by_path($nowGoods['image'], 's');
            $returnArr['product_image'] = thumb_img($tmp_pic_arr[0],180,180,'fill');
        }else{
            $returnArr['product_image'] = '';
        }
        $returnArr['image'] = $returnArr['product_image'];

        // 已售数量
        $returnArr['product_sale'] = $nowGoods['sell_count'];

        // $returnArr['product_reply'] = $nowGoods['reply_count'];

        // 是否有规格属性
        $returnArr['has_format'] = false;
        if ($nowGoods['spec_value'] || $nowGoods['is_properties']) {
            $returnArr['has_format'] = true;
        }

        //商品是否有规格
        $returnArr['has_spec']  = $nowGoods['spec_value'] ? true : false;

        //商品是否有附属菜
        $returnArr['is_subsidiary_goods']  = $nowGoods['is_subsidiary_goods'] ? true : false;

        if($isDetail){
            // $returnArr['json'] = isset($nowGoods['json']) ? $nowGoods['json'] : '';
            $returnArr['properties_list'] = isset($nowGoods['properties_list']) ? $nowGoods['properties_list'] : '';
            $returnArr['properties_status_list'] = isset($nowGoods['properties_status_list']) ? $nowGoods['properties_status_list'] : '';
            $returnArr['spec_list'] = isset($nowGoods['spec_list']) ? $nowGoods['spec_list'] : '';
            $returnArr['list'] = isset($nowGoods['list']) ? $nowGoods['list'] : '';
            $returnArr['subsidiary_piece'] = isset($nowGoods['subsidiary_piece']) ? $nowGoods['subsidiary_piece'] : '';
        }
        return $returnArr;
    }

    /**
     *  获取不同类型的商品
     * User: chenxiang
     * Date: 2020/9/5 16:34
     * @param $store_id
     * @param array $where_info
     * @return array
     */
    public function get_list_by_storeid_diypage($store_id, $where_info = array())
    {
        $condition_where = array('fs.status' => 1,'fs.is_must' => 0);
        if($store_id>=0){
            $condition_where['fs.store_id'] = $store_id;
        }

        if ($where_info['name']) {
            $condition_where['s.name'] = array('like', '%' . $where_info['name'] . '%');
        }

        if ($where_info['_string']) {
            $condition_where['_string'] = $where_info['_string'];
            $condition_where['_string'] .= ' AND s.goods_id=fs.goods_id';
        }else{
            $condition_where['_string'] = 's.goods_id=fs.goods_id';
        }

        $condition_field = 's.*,fs.*';
        $condition_order = '`s`.`sort` DESC, `fs`.`goods_id` ASC';
        $condition_table = array(C('DB_PREFIX').'shop_goods'=>'s',C('DB_PREFIX').'foodshop_goods_library'=>'fs');
        $count = D()->table($condition_table)->where($condition_where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 8);
        $good_list = D()
            ->field($condition_field)
            ->table($condition_table)
            ->where($condition_where)
            ->order($condition_order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        $goods_image_class = new goods_image();
        $timeNow = time();
        $goodsList = array();
        $site_url = C('config.site_url');
        foreach ($good_list as $value) {
            $tmp_pic_arr = explode(';', $value['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $value['pic_arr'][$k]['title'] = $v;
                $value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $value['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }
            $value['name'] = html_entity_decode($value['name'],ENT_QUOTES);
            $value['describe'] = html_entity_decode($value['describe'],ENT_QUOTES);
            $goodsList[] = $value;
        }
        return array(
            'good_list' => $goodsList,
            'page_bar' => $Page->show()
        );
    }
    
}
