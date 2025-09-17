<?php
/**
 * Mall_goodsModel.class.php
 * 商城3.0 商品model
 * Create on 2020/9/30 11:25
 * Created by zhumengqun
 */
class Mall_goodsModel extends Model{
    public function get_list_by_storeid_diypage($param)
    {
        if(empty($param['store_id'])){
            return array('error_code' => true, 'msg' => L_('缺少store_id参数'));
        }
        $where = ['store_id'=>$param['store_id'],'status'=>1,'is_del'=>0];
        if(!empty($param['keyword'])){
            $where['name'] = array('like', '%' . $param['keyword'] . '%');
        }
        $field = 'goods_id,name,update_time';
        $order = 'update_time DESC';
        import('@.ORG.diypage');
        $count = $this->where($where)->count();
        $param['page_size'] = $param['page_size']?$param['page_size']:8;
        $Page = new Page($count, $param['page_size']);
        $list = $this->field($field)->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if(!empty($list)){
            foreach($list as $key => $val){
                if(in_array($val['goods_id'],explode(',',$param['selectedItems']))){
                    $list[$key]['selected'] = 1;
                }else{
                    $list[$key]['selected'] = 0;
                }
            }
            $list['count'] = $count;
            return array(
                'good_list' => $list,
                'page_bar' => $Page->show()
            );
        }else{
            return [];
        }
    }
    public function getActGoods($gwhere, $gfield)
    {
        $arr = $this->alias('g')
            ->join(C('DB_PREFIX') . 'mall_activity_detail d on d.goods_id=g.goods_id')
            ->field($gfield)
            ->where($gwhere)
            ->find();
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    public function getActGoodsCount($gwhere)
    {
        $count = $this->alias('g')
            ->join(C('DB_PREFIX') . 'mall_activity_detail d on d.goods_id=g.goods_id')
            ->field('g.goods_id')
            ->where($gwhere)
            ->count('g.goods_id');
        return $count;
    }

    public function get_list_diypage($param)
    {
        $where = [
            'mg.status' => 1,
            'st.status' => 1,
            'mg.is_del' => 0,
        ];
        if(!empty($param['name'])){
            $where['mg.name'] = array('like', '%' . $param['name'] . '%');
        }
        $field = 'mg.goods_id,mg.name,mg.update_time,mg.store_id,mg.price,mg.describe,mg.image';//old_price,last_time
        $order = 'mg.update_time DESC';
        import('@.ORG.diypage');
        $count = $this->alias('mg')
            ->join('inner join '.C('DB_PREFIX').'merchant_store st on mg.store_id = st.store_id')
            ->where($where)
            ->count();
        $param['page_size'] = $param['page_size']?$param['page_size']:8;
        $Page = new Page($count, $param['page_size']);
        
        $list = $this->alias('mg')
            ->join('inner join '.C('DB_PREFIX').'merchant_store st on mg.store_id = st.store_id')
            ->field($field)
            ->where($where)
            ->order($order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if(!empty($list)){
            foreach($list as $key => $val){
                if(in_array($val['goods_id'],explode(',',$param['selectedItems']))){
                    $list[$key]['selected'] = 1;
                }else{
                    $list[$key]['selected'] = 0;
                }
//                $list[$key]['image_url'] = (new goods_image())->get_image_by_path($val['image'])['image'];
            }
            return array(
                'good_list' => $list,
                'page_bar' => $Page->show()
            );
        }else{
            return [];
        }
    }
    
    public function goodsList($distance = 0, $pageSize = 10, $lng = '', $lat = '')
    {
        $page = $_GET['p'] ?: 1;
        $where = [
            'g.status' => 1, 
            'g.is_del' => 0, 
            'g.audit_status' => 1,
            's.status' => 1,
            's.is_business_open' => 1,
            's.have_mall' => 1
        ];
        
        $fields = 'g.mer_id, g.store_id, g.goods_id, g.image, g.name, g.describe, g.price, g.unit, g.sale_num, g.virtual_sales, g.plat_virtual_sales, g.virtual_set';
        $mallGoods = $this
            ->alias('g')
            ->join('INNER JOIN '.C('DB_PREFIX').'merchant_store s ON g.store_id = s.store_id')
            ->where($where);
        
        $sort = '';
        if(!empty($lng) && !empty($lat) && $distance){
            $fields .= " ,(st_distance(point(`long`, `lat`), point({$lng}, {$lat}) ) * 111195 / 1000) AS distance";
            $sort = 'distance ASC,';
            $mallGoods = $mallGoods->having("distance <= {$distance}");
        }
        $sort .= "sort_store DESC, sale_num DESC";

        $count = clone $mallGoods;
        
        $count = $count->count();
        $list = $this
            ->field($fields)
            ->order($sort)
            ->page("{$page},{$pageSize}")
            ->select();

        $Page = new Page($count, $pageSize);
        $show = $Page->show();
        
        if($list) {
            foreach ($list as &$v) {
                $v['image'] = replace_file_domain($v['image']);
                $v['jump_link'] = C('config.site_url').'/packapp/plat/pages/shopmall_third/commodity_details?goods_id='.$v['goods_id'];
                if($v['virtual_set']){
                    $v['sale_num'] = $v['sale_num'] + $v['plat_virtual_sales'];
                }else{
                    $v['sale_num'] = $v['sale_num'] + $v['virtual_sales'];
                }
                unset($v['virtual_sales'], $v['plat_virtual_sales'], $v['virtual_set']);

                $merchant = D('Merchant')
                    ->where([
                        'mer_id' => $v['mer_id']
                    ])
                    ->field('name')
                    ->find();
                $v['merchant_name'] = $merchant['name'] ?? '';

                $v['delivery_way'] = M('Merchant_store_mall')
                    ->where([
                        'store_id' => $v['store_id']
                    ])
                    ->field('is_delivery, is_houseman, is_zt')
                    ->find();
            }
        }
        return [
            'count' => $count,
            'list' => $list,
            'pagebar'=>$show
        ];
    }

    /*
     * 获取商品积分比例
     */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = self::where(['goods_id' => $goodsId])->getField('score_percent');
        if(strpos($scorePercent,'%')){
            $scorePercent = bcdiv($scorePercent,100,2);
        }
        if(empty(floatval($scorePercent))){
            return -1;
        }
        return $scorePercent;
    }

    public function getLimitedList($params)
    {
        if(!empty($params['goods_id'])){
            $where['l.goods_id'] = ['IN', [$params['goods_id']]];
            $where['a.type'] = 'limited';
        }else{
            import('@.ORG.merchant_page');
            $where = [
                'a.type' => 'limited',
                'a.is_del' => 0,
                'a.end_time' => ['gt', time()],
                'a.status' => ['IN', [0, 1]],
                'g.status' => 1,
                'g.audit_status' => 1,
            ];
        }
        
        if (!empty($params['goods_name'])) {
            $where['g.name'] = ['LIKE', '%' . $params['goods_name'] . '%'];
        }
        
        $prefix = C('DB_PREFIX');
        $subQuery = D('Mall_limited_sku')
            ->field('*,min(act_price) discount_price')
            ->group('goods_id')
            ->order('act_price DESC')
            ->buildSql();
        $fields = '
            l.goods_id, g.name goods_name, m.name merchant_name, ms.name store_name, g.min_price price, l.discount_price,
            g.image, g.status goods_status, g.stock_num, a.start_time active_start_time, a.end_time active_end_time 
        ';
        if (empty($params['goods_id'])) {
            $count = D('Mall_limited_act')
                ->alias('s')
                ->join($prefix.'mall_activity a ON s.id = a.act_id')
                ->join("$subQuery l ON l.act_id = a.act_id")
                ->join($prefix.'mall_goods g ON l.goods_id = g.goods_id')
                ->join($prefix.'merchant_store ms ON g.store_id = ms.store_id')
                ->join($prefix.'merchant m ON m.mer_id = g.mer_id')
                ->where($where)
                ->count();
            $page = new Page($count, 10);
            $list = D('Mall_limited_act')
                ->alias('s')
                ->join($prefix.'mall_activity a ON s.id = a.act_id')
                ->join("$subQuery l ON l.act_id = a.act_id")
                ->join($prefix.'mall_goods g ON l.goods_id = g.goods_id')
                ->join($prefix.'merchant_store ms ON g.store_id = ms.store_id')
                ->join($prefix.'merchant m ON m.mer_id = g.mer_id')
                ->where($where)
                ->field($fields)
                ->order('a.sort ASC,a.id DESC')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        }else{
            $list = D('Mall_limited_act')
                ->alias('s')
                ->join($prefix.'mall_activity a ON s.id = a.act_id')
                ->join("$subQuery l ON l.act_id = a.act_id")
                ->join($prefix.'mall_goods g ON l.goods_id = g.goods_id')
                ->join($prefix.'merchant_store ms ON g.store_id = ms.store_id')
                ->join($prefix.'merchant m ON m.mer_id = g.mer_id')
                ->where($where)
                ->field($fields)
                ->order('a.sort ASC,a.id DESC')
                ->select();
        }
        $tm = time();
        foreach ($list as &$v){
            $v['image'] = replace_file_domain($v['image']);
            $v['active_start_time'] = date('Y-m-d H:i:s', $v['active_start_time']);
            $v['active_end_time'] = date('Y-m-d H:i:s', $v['active_end_time']);
            $v['url'] = C('config.site_url')."/packapp/plat/pages/shopmall_third/commodity_details?goods_id={$v['goods_id']}&source=limited";

            if ($tm < strtotime($v['active_start_time'])) {
                $v['status'] = 1;//待生效
            } else if ($tm > strtotime($v['active_start_time']) && $tm < strtotime($v['active_end_time'])) {
                $v['status'] = 2;//进行中
            } else {
                $v['status'] = 3;//已结束
            }
            $v['active_end_timestamp'] = strtotime($v['active_end_time']);
            if(strtotime($v['active_end_time']) < time() || $v['status'] == 3){
                $v['over_time'] = 1;
            }
        }
        if (!empty($params['goods_id'])) {
            return $list ?: [];
        }
        
        return [
            'list' => $list,
            'page_bar' => $page->show(),
            'total_count' => (int) $page->totalRows
        ];
    }
}