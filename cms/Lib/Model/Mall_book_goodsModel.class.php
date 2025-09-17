<?php

/**
 * 商城预售活动
 */
class Mall_book_goodsModel extends Model
{

    /**
     * 同一个商品进行中和待生效只能一条
     */
    public function in_progress_or_todo($goods_id, $except = [])
    {
        $tm = time();
        $where['is_del'] = 0;
        $where['goods_id'] = $goods_id;
        //$tm < strtotime($l['end_time'])
        if ($except) {
            $where['id'] = ['not in', $except];
        }
        $rs = $this->where($where)->find();
       if($tm>$rs['end_time']){
           return false;
       }else{
           return  true;
       }
       // return $rs ? true : false;
    }

    public function get_info($id){
        return $this->where(['id'=>$id])->find();
    }

    //获取正在进行中的活动
    public function get_effective_active($goods_id){
        $time = time();
        $where['is_del'] = 0;
        $where2['goods_id']=$where['goods_id'] = $goods_id;
        $where['start_time'] = ['ELT',$time];
        $where['end_time'] = ['EGT',$time];
        $where['_string'] = 'stock>0 or stock=-1';
        $res = $this->where($where)->find();
        $where1['book_id']=$res['id'];
        if($res){
            $res['bargin_price1']= $res['bargin_price'];
            /*获得定金区间*/
            $res['end_timestamp'] = $res['end_time'] - $time;
            $arr=M('Mall_book_goods_spec')->where($where1)->min('bargin_price');;
            $arr1=M('Mall_book_goods_spec')->where($where1)->max('bargin_price');
            $arr2=M('Mall_book_goods_spec')->where($where1)->max('discount_price');
            if($arr!=$arr1){
                $res['bargin_price']=C('config.Currency_symbol').number_format($arr, 2)."-".C('config.Currency_symbol').number_format($arr1, 2);
                $res['bargin_price1']=number_format($arr, 2)."-".C('config.Currency_symbol').number_format($arr1, 2);

            }else if($arr==$arr1 && !empty($arr) && !empty($arr1)){
                $res['bargin_price']=$arr;
            }else{
                if($res['discount_price']>0){
                    $res['bargin_price']=$res['bargin_price']." 抵 ".C('config.Currency_symbol').number_format(($res['bargin_price']+$res['discount_price']),2);
                }
            }
            if(!empty($arr)){
                /*给默认一个最大的优惠价格，防止为0时前端不显示抵扣信息*/
                $res['discount_price']=$arr2['discount_price'];
            }
            $shop_goods_mod = D('Shop_goods');
            $g=$shop_goods_mod->where($where2)->find();
           /*获取预售价范围区间*/
            if (!empty($g['spec_value'])) {
                $format_spec = $shop_goods_mod->format_spec_value($g['spec_value'], $g['goods_id'], $g['is_properties']);
                $spec_lists = $format_spec ? $format_spec['list'] : [];
                $max1=[];
                foreach ($spec_lists as $k => $v) {
                    $max1[$k]=$v['price'];
                }
                if(max($max1)!=min($max1)){
                    $res['price']=number_format(min($max1),2)."-".C('config.Currency_symbol').number_format(max($max1),2);
                }
            }else{
                $res['price']=$g['price'];
            }
        }
        return $res;
    }

    //获取预售中的，商品id集合
    public function get_effective_good_ids(){
        $time = time();
        $where['is_del'] = 0;
        $where['start_time'] = ['ELT',$time];
        $where['end_time'] = ['EGT',$time];
        $list = $this->where($where)->field('goods_id')->select();
        return array_column($list, 'goods_id');
    }


    //判断商品是否存在预售
    public function get_book_record($goods_id){
        $time = time();
        $where['is_del'] = 0;
        $where['start_time'] = ['ELT',$time];
        $where['end_time'] = ['EGT',$time];
        $where['goods_id'] = $goods_id;
        $where['stock'] = ['NEQ',0];
        $list = $this->where($where)->field('goods_id')->select();
        return count($list);
    }
    /**
     * 新增
     */
    public function add_record($post)
    {

        if (empty($post['start_time']) && empty($post['end_time'])) {
            throw new Exception(L_('请选择活动时间'));
        }
        
        /**
        if (strtotime($post['end_date']) < strtotime($post['start_date'])) {
            throw new Exception(L_('活动起始时间设置有误'));
        }**/

        $goods_ids = isset($post['goods_id']) ? $post['goods_id'] : [];
        $stock = isset($post['stock']) ? $post['stock'] : [];

        $bargin_price = isset($post['bargin_price']) ? $post['bargin_price'] : [];
        $discount_price = isset($post['discount_price']) ? $post['discount_price'] : [];

        $origin_stock = isset($post['origin_stock']) ? $post['origin_stock'] : [];
        $spec_index = isset($post['spec_index']) ? $post['spec_index'] : [];

        if (empty($goods_ids)) {
            throw new Exception(L_('促销商品不能为空'));
        }
        if ($post['limit_type'] == 1 && $post['limit_num'] <= 0) {
            throw new Exception(L_('每单限购必须大于0'));
        }

        $this->startTrans();
        try {
            $tm = time();
            $spec_mod = M('Mall_book_goods_spec');
            foreach ($goods_ids as $gid) {
                $g_name = M('Shop_goods')->where(['goods_id' => $gid])->getField('name');
                if ($this->in_progress_or_todo($gid)) {
                    throw new Exception(L_('商品【' . $g_name . '】已存在预售活动'));
                }

                $record = [
                    'store_id' => $post['store_id'],
                    'goods_id' => $gid,

                    'start_time' => strtotime($post['start_time']),
                    'end_time' => strtotime($post['end_time']),

                    'rest_pay_type' => $post['rest_pay_type'],

                    'rest_start_time' => $post['rest_pay_type']==1&&$post['rest_start_time']?strtotime($post['rest_start_time']):'',
                    'rest_end_time' =>  $post['rest_pay_type']==1&&$post['rest_end_time']?strtotime($post['rest_end_time']):'',

                    'rest_day' =>  $post['rest_pay_type']==2?$post['rest_day']:'',
                    'send_goods_type' => $post['send_goods_type'],

                    'send_goods_date' => $post['send_goods_type']=='1'?strtotime($post['send_goods_date']):'',

                    'send_goods_day' => $post['send_goods_type']=='2'?$post['send_goods_day']:'',
                    'is_spec' => isset($spec_index[$gid]) && array_filter($spec_index[$gid]) ? 1 : 0,

                    'stock' => max($stock[$gid]),
                    'origin_stock' => max($origin_stock[$gid]),
                    'discount_price' => min($discount_price[$gid]),
                    'bargin_price' => min($bargin_price[$gid]),

                    'create_time' => $tm
                ];
                if(min($bargin_price[$gid])=='0'){
                    throw new Exception(L_('定金不能为0'));
                }

                $book_id = $this->add($record);
                if ($book_id < 1) {
                    //echo M()->getLastSql();exit;
                    throw new Exception(L_('添加失败'));
                }
                if (isset($spec_index[$gid]) && array_filter($spec_index[$gid]) ) {
                    $spec_data = [];
                    $total_stock = 0;
                    $total_origin_stock = 0;
                    foreach ($spec_index[$gid] as $k => $v) {
                        $spec = [
                            'book_id' => $book_id,
                            'spec_index' => $v,
                            'stock' => isset($stock[$gid][$k]) ? $stock[$gid][$k] : 0,
                            'origin_stock' => isset($origin_stock[$gid][$k]) ? $origin_stock[$gid][$k] : 0,
                            'discount_price' => isset($discount_price[$gid][$k]) ? $discount_price[$gid][$k] : 0,
                            'bargin_price' => isset($bargin_price[$gid][$k]) ? $bargin_price[$gid][$k] : 0,
                        ];
                        if ($spec['origin_stock'] != -1) {
                            if ($spec['stock'] == -1 || $spec['origin_stock'] < $spec['stock']) {
                                throw new Exception(L_('原始库存必须大于当前库存'));
                            }
                        }

                        $spec_data[] = $spec;
                        $total_stock += $spec['stock'];
                        $total_origin_stock += $spec['origin_stock'];
                    }
                    $spec_data && $spec_mod->addAll($spec_data);
                    $this->where(['id' => $book_id])->save(['stock' => $total_stock, 'origin_stock' => $total_origin_stock]);
                }
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception(L_($e->getMessage()));
        }
    }

    public function edit_record($post)
    {

        if (empty($post['id']) || $post['id'] < 1) {
            throw new Exception(L_('请选择一条记录'));
        }
        /*if (empty($post['start_date']) && empty($post['end_date'])) {
            throw new Exception(L_('请选择活动时间'));
        }
        if (strtotime($post['end_date']) <= strtotime($post['start_date'])) {
            throw new Exception(L_('活动起始时间设置有误'));
        }*/

        $stock = isset($post['stock']) ? $post['stock'] : [];
        $bargin_price = isset($post['bargin_price']) ? $post['bargin_price'] : [];
        $discount_price = isset($post['discount_price']) ? $post['discount_price'] : [];
        $origin_stock = isset($post['origin_stock']) ? $post['origin_stock'] : [];
        $spec_id = isset($post['spec_id']) ? $post['spec_id'] : [];
        $old = $this->where(['id' => $post['id']])->find();
        if (empty($old)) {
            throw new Exception(L_('预售活动不存在'));
        }

        $this->startTrans();
        try {
            if ($this->in_progress_or_todo($old['goods_id'], [$post['id']])) {
                $g_name = M('Shop_goods')->where(['goods_id' => $old['goods_id']])->getField('name');
                throw new Exception(L_('商品【' . $g_name . '】存在进行中或者待生效的活动'));
            }

            $tm = time();
            $spec_mod = M('Mall_book_goods_spec');

            //记录修改前数据
            $before_limit_data = $this->where(['id' => $post['id']])->find();
            $before_limit_spec_data = $before_limit_data['is_spec'] ? $spec_mod->where(['limit_id' => $post['id']])->select() : [];

            $update = [];
        
            isset($post['start_time']) && $update['start_time'] = strtotime($post['start_time']);
            isset($post['end_time']) && $update['end_time'] = strtotime($post['end_time']);
            isset($post['rest_pay_type']) && $update['rest_pay_type'] = $post['rest_pay_type'];
            isset($post['rest_start_time']) && $update['rest_start_time'] = strtotime($post['rest_start_time']);
            isset($post['rest_end_time']) && $update['rest_end_time'] = strtotime($post['rest_end_time']);
            isset($post['rest_day']) && $update['rest_day'] = $post['rest_day'];
            isset($post['send_goods_type']) && $update['send_goods_type'] = $post['send_goods_type'];
            isset($post['send_goods_date']) && $update['send_goods_date'] = strtotime($post['send_goods_date']);
            isset($post['send_goods_day']) && $update['send_goods_day'] = $post['send_goods_day'];
 
            if ($spec_id) {
                $total_stock = 0;
                $total_origin_stock = 0;
                foreach ($spec_id as $k => $v) {
                    $spec_data = [
                        'stock' => isset($stock[$k]) ? $stock[$k] : 0,
                        'origin_stock' => isset($origin_stock[$k]) ? $origin_stock[$k] : 0,
                        'bargin_price' => isset($bargin_price[$k]) ? $bargin_price[$k] : 0,
                        'discount_price' => isset($discount_price[$k]) ? $discount_price[$k] : 0,
                    ];
                    isset($limit_price[$k]) && $spec_data['limit_price'] = $limit_price[$k];
                    if ($spec_data['origin_stock'] != -1) {
                        if ($spec_data['stock'] == -1 || $spec_data['origin_stock'] < $spec_data['stock']) {
                            throw new Exception(L_('原始库存必须大于当前库存'));
                        }
                    }

                    $spec_mod->where(['id' => $v])->save($spec_data);
                    $total_stock += $spec_data['stock'];
                    $total_origin_stock += $spec_data['origin_stock'];
                }
                $update['stock'] = $total_stock;
                $update['origin_stock'] = $total_origin_stock;
            } else {
                $update['stock'] = isset($stock[0]) ? $stock[0] : 0;
                $update['origin_stock'] = isset($origin_stock[0]) ? $origin_stock[0] : 0;
                isset($limit_price[0]) && $update['limit_price'] = $limit_price[0];
                if ($update['origin_stock'] != -1) {
                    if ($update['stock'] == -1 || $update['origin_stock'] < $update['stock']) {
                        throw new Exception(L_('原始库存必须大于当前库存'));
                    }
                }

            }
            $this->where(['id' => $post['id']])->save($update);
          
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception(L_($e->getMessage()));
        }
    }

    public function get_status($id)
    {
        $record = $this->where(['id' => $id])->find();
        $tm = time();
        $rs = [];
        if ($record) {
            if ($tm < $record['start_time']) {
                $rs['status_str'] = L_('待生效');
                $rs['status'] = 1;
            } else if ($tm > $record['start_time'] && $tm < $record['end_time'] ) {
                $rs['status_str'] = L_('进行中');
                $rs['status'] = 2;
            } else {
                $rs['status_str'] = L_('已结束');
                $rs['status'] = 3;
            }
        }
        return $rs;
    }


    public function get_list($search)
    {
        $where = [];
        if ($search['store_id']) {
            $where['l.store_id'] = $search['store_id'];
        }
        if ($search['id']) {
            $where['l.id'] = $search['id'];
        }
        if ($search['keyword']) {
            $where['g.name'] = ['LIKE', '%' . trim($search['keyword']) . '%'];
        }
        if ($search['start_date']) {
            $start_date=strtotime($search['start_date']." 00:00:00");
            $where['l.start_time'][] = ['EGT', $start_date];
        }
        if ($search['end_date']) {
            $end_date=strtotime($search['end_date']." 23:59:59");
            $where['l.end_time'][] = ['ELT', $end_date];
        }

        //$today = date('Y-m-d');
        $today=time();
        if ($search['status']) {
            $where['l.is_del'] = 0;
            //1：待生效  2：进行中   3：已结束  4:已撤销
            if ($search['status'] == 1) {
                $where['l.start_time'][] = ['GT', $today];
            } else if ($search['status'] == 2) {
                $where['l.start_time'][] = ['ELT', $today];
                $where['l.end_time'][] = ['EGT', $today];
            } else if ($search['status'] == 3) {
                $where['l.end_time'][] = ['LT', $today];
            } else if ($search['status'] == 4) {
                $where['l.is_del'] = 1;
            }
        }
        if ($where['store_id']) {
            $where['g.store_id'] = $where['store_id'];
        }

        $join = C('DB_PREFIX') . 'shop_goods AS g ON l.goods_id = g.goods_id';
        $field = 'l.*,g.name AS goods_name,g.price,g.image,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value';
        $count = $this->alias('l')->join($join)->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 10);
        $lists = $this->alias('l')->join($join)->field($field)->where($where)->order('l.end_time DESC,l.is_del ASC')->limit($page->firstRow . ',' . $page->listRows)->select();
        $tm = time();
        $spec_mod = D('Mall_book_goods_spec');
        $spec_value_mod = D('Shop_goods_spec_value');
        $goods_mod = D('Shop_goods');
        $goods_image_class = new goods_image();
        $week_arr = [L_('星期日'), L_('星期一'), L_('星期二'), L_('星期三'), L_('星期四'), L_('星期五'), L_('星期六')];
        foreach ($lists as &$l) {
            $l['start_time'] = date('Y-m-d H:i:s',$l['start_time']);
            $l['end_time'] = date('Y-m-d H:i:s',$l['end_time']);
            if(empty($l['rest_start_time'])|| $l['rest_start_time']==0){
                $l['rest_start_time'] = date('Y-m-d H:i:s',time());
            }else{
                $l['rest_start_time'] = date('Y-m-d H:i:s',$l['rest_start_time']);
            }

            if($l['rest_end_time']==0){
                $l['rest_end_time'] = $l['end_time'];
            }else{
                $l['rest_end_time'] = date('Y-m-d H:i:s',$l['rest_end_time']);
            }
          /*  if($l['send_goods_type']==1){*/
                if($l['send_goods_date']==0){
                    $l['send_goods_date'] = date('Y-m-d',time());
                }else{
                    $l['send_goods_date'] = date('Y-m-d',$l['send_goods_date']);
                }
           /* }*/

            $tmp_pic_arr = array_filter(explode(';', $l['image']));
            $l['image'] = $goods_image_class->get_image_by_path(isset($tmp_pic_arr[0]) ? $tmp_pic_arr[0] : '', 0);

            if ($l['spec_value']) {
                $goods = $goods_mod->where(['goods_id' => $l['goods_id']])->field('goods_id,spec_value,is_properties')->find();
                $format_spec = $goods_mod->format_spec_value($goods['spec_value'], $goods['goods_id'], $goods['is_properties']);
                $format_spec_list = isset($format_spec['list']) ? $format_spec['list'] : [];
                $specs = $spec_mod->where(['book_id' => $l['id']])->select();
                //fdump($specs);exit;
                foreach ($specs as &$s) {
                    $val_id = explode('_', $s['spec_index']);
                    $values = $spec_value_mod->where(['id' => ['IN', $val_id]])->getField('name', true);
                    $s['str'] = $values?implode('、', $values):L_('规格关系已变动');
                    $s['price'] = isset($format_spec_list[$s['spec_index']]) ? getFormatNumber($format_spec_list[$s['spec_index']]['price']) : '--';
                    $s['today_sale'] = $this->getSaleByDate($l['goods_id'], $s['id'],$today,$s['spec_index'],$search['store_id']);
                }
                $l['spec_list'] = $specs;
                $l['spec_count'] = count($specs);
            } else {
                $l['spec_list'] = [];
                $l['spec_count'] = 0;
            }
            if ($tm < strtotime($l['start_time'])) {
                $l['status_str'] = L_('待生效');
                $l['status'] = 1;
            } else if ($tm > strtotime($l['start_time']) && $tm < strtotime($l['end_time'])) {// + 86400,老版加了这个？
                $l['status_str'] = L_('进行中');
                $l['status'] = 2;
            } else {
                $l['status_str'] = L_('已结束');
                $l['status'] = 3;
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
            $l['today_sale'] = $this->getSaleByDate($l['goods_id'], 0, $today,$spec_index=0,$search['store_id']);
            //$l['today_sale'] = 0;//$this->getSaleByDate($l['id'], 0, $today);
        }

        return ['lists' => $lists, 'page_bar' => $page->show(), 'total_count' => $page->totalRows];
    }

    /**
     * 获取今日销量
     * @author mrdeng
     * @date 2020/07/15
     */
    public function getSaleByDate($goods_id, $specId = 0, $date,$spec_index=0,$store_id)
    {
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        //$tm = time();
        $where[] = ['a.book_time' => [['GT', $beginToday], ['LT',$endToday]]];
        $where[] =['b.goods_id'=>['eq',$goods_id]] ;
        $where[]=['a.book_status'=>['GT', 1]];
        $where[] =['a.store_id'=>['eq',$store_id]] ;
        //$where[]= ['a.book_status' => ['GT', 0]];
        if($spec_index){
            $where[]=['b.spec_id'=>['eq', $spec_index]];
        }
        $join = C('DB_PREFIX') . 'shop_order_detail AS b ON a.order_id = b.order_id';
        $count = M('Shop_order')->alias('a')->join($join)->where($where)->count();
        return intval($count);
    }
    /**
     * 根据商品id获取进行中的活动
     */
    public function get_in_progress_record($goods_id)
    {
        $goods = M('Shop_goods')->where(['goods_id' => $goods_id])->field('name,price,stock_num,spec_value')->find();
        if (!$goods || ($goods['stock_num'] != -1 && $goods['stock_num'] < 1)) {
            return [];
        }
        $tm = time();

        $where = [
            'is_del' => 0,
            'goods_id' => $goods_id,
            'end_time' => ['egt', $tm],
            'start_time' => ['elt', $tm],
        ];
        $record = $this->where($where)->find();

        if (empty($record)) {
            return [];
        }

        $record['goods_name'] = $goods['name'];
        
        if ($record['is_spec'] == 1) {
            $record['spec_list'] = D('Mall_book_goods_spec')->where(['book_id' => $record['id']])->getField('spec_index,id,book_id,stock,origin_stock,discount_price,bargin_price', true);
            //如果多规格变动，则限时优惠不生效
            $spec_arr = $record['spec_list'] ? array_unique(array_column($record['spec_list'], 'spec_index')) : [];
            $format_spec_list_keys = [];
            if ($goods['spec_value']) {
                $spec_array = explode('#', $goods['spec_value']);
                foreach ($spec_array as $v) {
                    $rowArray = explode('|', $v);
                    if ($rowArray[0]) {
                        $format_spec_list_keys[] = str_replace(':', '_', $rowArray[0]);
                    }
                }
            }
            if (array_diff($spec_arr, $format_spec_list_keys)) {
                return [];
            }
        } else {
            //如果商品增加了规格，则限时优惠不生效
            if ($goods['spec_value']) {
                return [];
            }
            $record['spec_list'] = [];
        }
        return $record;
    }
}