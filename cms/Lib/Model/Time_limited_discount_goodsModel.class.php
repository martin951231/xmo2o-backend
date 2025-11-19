<?php

/**
 * 限时优惠商品配置
 */
class Time_limited_discount_goodsModel extends Model
{
    /**
     * 同一个商品进行中和待生效只能一条
     */
    public function in_progress_or_todo($goods_id, $except = [])
    {
        $tm = time();
        $where['is_del'] = 0;
        $where['goods_id'] = $goods_id;
        $where['_string'] = "UNIX_TIMESTAMP(CONCAT(`end_date`, ' ', `end_time`)) > $tm";
        if ($except) {
            $where['id'] = ['not in', $except];
        }
        $rs = $this->where($where)->find();
        return $rs ? true : false;
    }

    /**
     * 新增
     */
    public function add_record($post)
    {
        $total_limit_total_num = D('Config_data')->where(['name' => 'limit_total_num'])->getField('value');
        if (empty($post['start_date']) && empty($post['end_date'])) {
            throw new Exception(L_('请选择活动时间'));
        }
        if (strtotime($post['end_date']) < strtotime($post['start_date'])) {
            throw new Exception(L_('活动起始时间设置有误'));
        }

        $goods_ids = isset($post['goods_id']) ? $post['goods_id'] : [];
        $stock = isset($post['stock']) ? $post['stock'] : [];
        $limit_price = isset($post['limit_price']) ? $post['limit_price'] : [];
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
            $spec_mod = M('Time_limited_discount_goods_spec');
            foreach ($goods_ids as $gid) {
                $g_name = M('Shop_goods')->where(['goods_id' => $gid])->getField('name');
                if ($this->in_progress_or_todo($gid)) {
                    throw new Exception(L_('商品【' . $g_name . '】存在进行中或者待生效的活动'));
                }
                if ($post['limit_type'] == 0) {
                    $post['limit_num'] = 0;
                }
                if ($total_limit_total_num > 0 && $post['limit_num'] > $total_limit_total_num) {
                    throw new Exception(L_('限购数不能大于总限购数'));
                }
                $record = [
                    'store_id' => $post['store_id'],
                    'goods_id' => $gid,
                    'goods_name' => $g_name,
                    'start_date' => $post['start_date'],
                    'end_date' => $post['end_date'],
                    'week' => $post['week'],
                    'start_time' => $post['start_time'],
                    'end_time' => $post['end_time'],
                    'limit_type' => $post['limit_type'],
                    'limit_num' => $post['limit_num'],
                    'stock_type' => $post['stock_type'],
                    'is_spec' => isset($spec_index[$gid]) && array_filter($spec_index[$gid]) ? 1 : 0,
                    'last_time' => $tm,
                    'create_time' => $tm
                ];
                if (!$record['is_spec']) {
                    $record['stock'] = isset($stock[$gid]) ? $stock[$gid][0] : 0;
                    $record['origin_stock'] = isset($origin_stock[$gid]) ? $origin_stock[$gid][0] : 0;
                    $record['limit_price'] = isset($limit_price[$gid]) ? $limit_price[$gid][0] : 0;
                    if ($record['origin_stock'] != -1) {
                        if ($record['stock'] == -1 || $record['origin_stock'] < $record['stock']) {
                            throw new Exception(L_('原始库存必须大于当前库存'));
                        }
                    }
                }
                $limit_id = $this->add($record);
                if ($limit_id < 1) {
                    throw new Exception(L_('添加失败'));
                }
                if ($record['is_spec']) {
                    $spec_data = [];
                    $total_stock = 0;
                    $total_origin_stock = 0;
                    foreach ($spec_index[$gid] as $k => $v) {
                        $spec = [
                            'limit_id' => $limit_id,
                            'spec_index' => $v,
                            'stock' => isset($stock[$gid][$k]) ? $stock[$gid][$k] : 0,
                            'origin_stock' => isset($origin_stock[$gid][$k]) ? $origin_stock[$gid][$k] : 0,
                            'limit_price' => isset($limit_price[$gid][$k]) ? $limit_price[$gid][$k] : 0,
                        ];
                        if ($spec['origin_stock'] != -1) {
                            if ($spec['stock'] == -1 || $spec['origin_stock'] < $spec['stock']) {
                                throw new Exception(L_('原始库存必须大于当前库存'));
                            }
                        }

                        $spec_data[] = $spec;
                        $total_stock != -1 && $total_stock += $spec['stock'];
                        $total_origin_stock != -1 && $total_origin_stock += $spec['origin_stock'];
                    }
                    $spec_data && $spec_mod->addAll($spec_data);
                    $this->where(['id' => $limit_id])->save(['stock' => $total_stock, 'origin_stock' => $total_origin_stock]);
                }

                //记录新增日志
                $this->wirte_action_log(0, [], ['limit_data' => $record, 'spec_data' => $spec_data], $limit_id);
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception(L_($e->getMessage()));
        }
    }

    /**
     * 记录操作日志
     *
     * @author 张涛
     * @date 2020/04/08
     */
    public function wirte_action_log($action, $before, $after, $limit_id)
    {
        $data = [
            'action' => $action,
            'before_data' => json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_data' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'log_time' => time(),
            'limit_id' => $limit_id
        ];
        M('Time_limited_discount_log')->add($data);
    }

    public function edit_record($post)
    {
        $total_limit_total_num = D('Config_data')->where(['name' => 'limit_total_num'])->getField('value');
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
        $limit_price = isset($post['limit_price']) ? $post['limit_price'] : [];
        $origin_stock = isset($post['origin_stock']) ? $post['origin_stock'] : [];
        $spec_id = isset($post['spec_id']) ? $post['spec_id'] : [];
        $old = $this->where(['id' => $post['id']])->find();
        if (empty($old)) {
            throw new Exception(L_('限时优惠不存在'));
        }
        if ($post['limit_type'] == 1 && $post['limit_num'] <= 0) {
            throw new Exception(L_('每单限购必须大于0'));
        }
        $this->startTrans();
        try {
            if ($this->in_progress_or_todo($old['goods_id'], [$post['id']])) {
                $g_name = M('Shop_goods')->where(['goods_id' => $old['goods_id']])->getField('name');
                throw new Exception(L_('商品【' . $g_name . '】存在进行中或者待生效的活动'));
            }

            $tm = time();
            $spec_mod = M('Time_limited_discount_goods_spec');

            //记录修改前数据
            $before_limit_data = $this->where(['id' => $post['id']])->find();
            $before_limit_spec_data = $before_limit_data['is_spec'] ? $spec_mod->where(['limit_id' => $post['id']])->select() : [];

            $update = [];
            isset($post['start_date']) && $update['start_date'] = $post['start_date'];
            isset($post['end_date']) && $update['end_date'] = $post['end_date'];
            isset($post['week']) && $update['week'] = $post['week'];
            isset($post['start_time']) && $update['start_time'] = $post['start_time'];
            isset($post['end_time']) && $update['end_time'] = $post['end_time'];
            isset($post['limit_num']) && $update['limit_num'] = $post['limit_num'];
            isset($post['stock_type']) && $update['stock_type'] = $post['stock_type'];
            isset($post['limit_type']) && $update['limit_type'] = $post['limit_type'];
            if ($update['limit_type'] == 0) {
                $update['limit_num'] = 0;
            }
            if ($total_limit_total_num > 0 && $post['limit_num'] > $total_limit_total_num) {
                throw new Exception(L_('限购数不能大于总限购数'));
            }

            if ($spec_id && $old['is_spec'] == 1) {
                $total_stock = 0;
                $total_origin_stock = 0;
                foreach ($spec_id as $k => $v) {
                    $spec_data = [
                        'stock' => isset($stock[$k]) ? $stock[$k] : 0,
                        'origin_stock' => isset($origin_stock[$k]) ? $origin_stock[$k] : 0,
                    ];
                    isset($limit_price[$k]) && $spec_data['limit_price'] = $limit_price[$k];
                    if ($spec_data['origin_stock'] != -1) {
                        if ($spec_data['stock'] == -1 || $spec_data['origin_stock'] < $spec_data['stock']) {
                            throw new Exception(L_('原始库存必须大于当前库存'));
                        }
                    }

                    $spec_mod->where(['id' => $v])->save($spec_data);
                    $total_stock != -1 && $total_stock += $spec_data['stock'];
                    $total_origin_stock != -1 && $total_origin_stock += $spec_data['origin_stock'];
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

            //记录修改日志
            $after_limit_data = $this->where(['id' => $post['id']])->find();
            $after_limit_spec_data = $before_limit_data['is_spec'] ? $spec_mod->where(['limit_id' => $post['id']])->select() : [];
            $this->wirte_action_log(1, ['limit_data' => $before_limit_data, 'spec_data' => $before_limit_spec_data], ['limit_data' => $after_limit_data, 'spec_data' => $after_limit_spec_data], $post['id']);
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception(L_($e->getMessage()));
        }
    }

    /**
     * @param $where
     * @return mixed
     */
    public function getOneList($where)
    {
        $ret = $this->where($where)->find();
        return $ret;
    }

    /**
     * [get_list 获取限时优惠列表]
     * @Author   JJC
     * @DateTime 2020-04-26T10:05:38+0800
     * @param    [array]   $search    [搜索条件]
     * @param    [string]  $shop_type [类型：shop快店，mall商城]
     * @return   [array]              [description]
     */
    public function get_list($search, $shop_type = 'shop')
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
            $where['l.start_date'][] = ['EGT', $search['start_date']];
        }
        if ($search['end_date']) {
            $where['l.end_date'][] = ['ELT', $search['end_date']];
        }

        if ($shop_type == 'mall') {
            $where['g.cat_fid'] = ['gt', 0];
        } else {
            $where['g.goods_type'] = ['neq', 1];
        }

        $today = date('Y-m-d');
        if ($search['status']) {
            $where['l.is_del'] = 0;
            //1：待生效  2：进行中   3：已结束  4:已撤销
            if ($search['status'] == 1) {
                $where['l.start_date'][] = ['GT', $today];
            } else if ($search['status'] == 2) {
                $where['l.start_date'][] = ['ELT', $today];
                $where['l.end_date'][] = ['EGT', $today];
            } else if ($search['status'] == 3) {
                $where['l.end_date'][] = ['LT', $today];
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
        $lists = $this->alias('l')->join($join)->field($field)->where($where)->order('l.end_date DESC,l.is_del ASC')->limit($page->firstRow . ',' . $page->listRows)->select();
        $tm = time();
        $spec_mod = D('Time_limited_discount_goods_spec');
        $spec_value_mod = D('Shop_goods_spec_value');
        $goods_mod = D('Shop_goods');
        $goods_image_class = new goods_image();
        $week_arr = [L_('星期日'), L_('星期一'), L_('星期二'), L_('星期三'), L_('星期四'), L_('星期五'), L_('星期六')];
        foreach ($lists as &$l) {
            $l['start_time'] = substr($l['start_time'], 0, 5);
            $l['end_time'] = substr($l['end_time'], 0, 5);
            $tmp_pic_arr = array_filter(explode(';', $l['image']));
            $l['image'] = $goods_image_class->get_image_by_path(isset($tmp_pic_arr[0]) ? $tmp_pic_arr[0] : '', 0);
            if ($l['is_spec']) {
                $goods = $goods_mod->where(['goods_id' => $l['goods_id']])->field('goods_id,spec_value,is_properties')->find();
                $format_spec = $goods_mod->format_spec_value($goods['spec_value'], $goods['goods_id'], $goods['is_properties']);
                $format_spec_list = isset($format_spec['list']) ? $format_spec['list'] : [];
                $specs = $spec_mod->where(['limit_id' => $l['id']])->select();
                foreach ($specs as &$s) {
                    $val_id = explode('_', $s['spec_index']);
                    $values = $spec_value_mod->where(['id' => ['IN', $val_id]])->getField('name', true);
                    $s['str'] = $values ? implode('、', $values) : L_('规格关系已变动');
                    $s['price'] = isset($format_spec_list[$s['spec_index']]) ? getFormatNumber($format_spec_list[$s['spec_index']]['price']) : '--';
                    $s['today_sale'] = $this->getSaleByDate($l['id'], $s['id'], $today);
                }
                $l['spec_list'] = $specs;
                $l['spec_count'] = count($specs);
            } else {
                $l['spec_list'] = [];
                $l['spec_count'] = 0;
            }
            $l['week_arr'] = explode(',', $l['week']);
            $week_str = array_map(function ($v) use ($week_arr) {
                return isset($week_arr[$v]) ? $week_arr[$v] : $v;
            }, $l['week_arr']);
            $l['week_str'] = implode(',', $week_str);
            if ($tm < strtotime($l['start_date'])) {
                $l['status_str'] = L_('待生效');
                $l['status'] = 1;
            } else if ($tm > strtotime($l['start_date']) && $tm < strtotime($l['end_date']) + 86400) {
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

            $l['today_sale'] = $this->getSaleByDate($l['id'], 0, $today);
        }
        return ['lists' => $lists, 'page_bar' => $page->show(), 'total_count' => $page->totalRows];
    }
    
    public function seckillGoods($params = []): array
    {
        if (!empty($params['goods_id'])) {
            $where = [
                'l.is_del' => 0,
                'g.status' => 1,
                'l.goods_id' => ['IN', [$params['goods_id']]]
            ];
        } else {
            import('@.ORG.merchant_page');
            $where = [
                'l.is_del' => 0,
                'g.status' => 1,
                'l.end_date' => ['EGT', date('Y-m-d')]
            ];
        }
        
        if (!empty($params['goods_name'])) {
            $where['g.name'] = ['LIKE', '%' . trim($params['goods_name']) . '%'];
        }
        $prefix = C('DB_PREFIX');
        $fields = '
            l.id, l.is_spec, g.name AS goods_name, s.name store_name, m.name merchant_name, g.price, g.image, 
            l.stock as stock_num, g.status AS goods_status, g.spec_value, l.limit_price discount_price,
            l.start_date active_start_time, l.end_date active_end_time, l.goods_id, l.start_time, l.end_time,
            l.store_id
        ';
        if (empty($params['goods_id'])) {
            $count = $this
                ->alias('l')
                ->join($prefix. 'shop_goods AS g ON l.goods_id = g.goods_id')
                ->join($prefix. 'merchant_store AS s ON s.store_id = g.store_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = s.mer_id')
                ->where($where)
                ->count();
            $page = new Page($count, 10);
            $list = $this
                ->alias('l')
                ->join($prefix. 'shop_goods AS g ON l.goods_id = g.goods_id')
                ->join($prefix. 'merchant_store AS s ON s.store_id = g.store_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = s.mer_id')
                ->where($where)
                ->field($fields)
                ->order('l.end_date DESC')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        }else{
            $list = $this
                ->alias('l')
                ->join($prefix. 'shop_goods AS g ON l.goods_id = g.goods_id')
                ->join($prefix. 'merchant_store AS s ON s.store_id = g.store_id')
                ->join($prefix. 'merchant AS m ON m.mer_id = s.mer_id')
                ->where($where)
                ->field($fields)
                ->order('l.end_date DESC')
                ->select();
        }
        $goodsImageClass = new goods_image();
        $goodsMod = D('Shop_goods');
        $specMod = D('Time_limited_discount_goods_spec');
        $tm = time();
        foreach ($list as &$v) {
            $picArr = array_filter(explode(';', $v['image']));
            $v['image'] = $goodsImageClass->get_image_by_path($picArr[0] ?? '', 0);
            if ($v['is_spec']) {
                $goods = $goodsMod->where(['goods_id' => $v['goods_id']])->field('goods_id,spec_value,is_properties')->find();
                $formatSpec = $goodsMod->format_spec_value($goods['spec_value'], $goods['goods_id'], $goods['is_properties']);
                $formatSpecList = $formatSpec['list'] ?? [];
                $specs = $specMod->where(['limit_id' => $v['id']])->select();
                foreach ($specs as &$s) {
                    $s['price'] = isset($formatSpecList[$s['spec_index']]) ? getFormatNumber($formatSpecList[$s['spec_index']]['price']) : '--';
                }
                $v['discount_price'] = min(array_column($specs, 'limit_price'));
                $v['price'] = min(array_column($specs, 'price'));
            }
            $v['active_start_time'] = $v['active_start_time'].' '.$v['start_time'];
            $v['active_end_time'] = $v['active_end_time'].' '.$v['end_time'];
            $v['url'] = C('config.site_url')."/packapp/plat/pages/shop_new/goodsDetail/goodsDetail?store_id={$v['store_id']}&product_id={$v['goods_id']}";
            unset($v['start_time'], $v['end_time']);
            
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

    public function get_status($id)
    {
        $record = $this->where(['id' => $id])->find();
        $tm = time();
        $rs = [];
        if ($record) {
            if ($tm < strtotime($record['start_date'])) {
                $rs['status_str'] = L_('待生效');
                $rs['status'] = 1;
            } else if ($tm > strtotime($record['start_date']) && $tm < strtotime($record['end_date']) + 86400) {
                $rs['status_str'] = L_('进行中');
                $rs['status'] = 2;
            } else {
                $rs['status_str'] = L_('已结束');
                $rs['status'] = 3;
            }
        }
        return $rs;
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
        $week = date('w');
        $today = date('Y-m-d');
        $where = [
            'is_del' => 0,
            'goods_id' => $goods_id,
            'end_date' => ['egt', $today],
            'start_date' => ['elt', $today],
            '_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', `end_time`)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', `start_time`)) < $tm AND FIND_IN_SET($week,`week`)"
        ];
        $record = $this->where($where)->find();
        $limit_rec = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->order('limit_price asc')->select();
        if (empty($record)) {
            return [];
        }
        if ($record['limit_type'] == 0) {
            $record['limit_num'] = -1;
        }
        $record['goods_name'] = $goods['name'];

        //每日更新库存
        if ($this->update_stock_everyday($record)) {
            $record['stock'] = $record['origin_stock'];
        }

        // if ($record['stock_type'] == 0 && $record['update_stock_date'] != $today) {
        //     //每日更新库存，触发重置
        //     $this->where(['id' => $record['id']])->save(['stock' => $record['origin_stock'], 'update_stock_date' => $today]);
        //     if ($record['is_spec']) {
        //         $sql = 'UPDATE ' . C('DB_PREFIX') . 'time_limited_discount_goods_spec SET `stock`=`origin_stock` WHERE limit_id = ' . intval($record['id']);
        //         D('Time_limited_discount_goods_spec')->query($sql);
        //     }
        //     $record['stock'] = $record['stock'];
        // }
        if ($record['is_spec'] == 1) {
            $record['spec_list'] = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->getField('spec_index,id,limit_id,stock,origin_stock,limit_price', true);
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
        if (!empty($limit_rec)) {
            $record['limit_price'] = $limit_rec[0]['limit_price'];
        } else {
            /*if($record['limit_price']>0){
                $record['limit_price']=$record['limit_price'];
            }else{
                $record['limit_price']=$goods['price'];
            }*/
            $record['limit_price'] = $record['limit_price'];

        }
        return $record;
    }

    /**
     * 根据商品id获取12小时内要进行的活动
     */
    public function get_in_twelhour_progress_record($goods_id)
    {
        $goods = M('Shop_goods')->where(['goods_id' => $goods_id])->field('name,price,stock_num,spec_value')->find();
        if (!$goods || ($goods['stock_num'] != -1 && $goods['stock_num'] < 1)) {
            return [];
        }
        $act_info = $this->get_in_progress_record($goods_id);
        if ($act_info) {
            return [];
        } else {
            $tm = time();
            $tm1 = time() + 12 * 3600;
            $week = date('w', $tm1);
            $today = date('Y-m-d', $tm1);
            $where = [
                'is_del' => 0,
                'goods_id' => $goods_id,
                'end_date' => ['egt', $today],
                /*            '_string' => "UNIX_TIMESTAMP(CONCAT(start_date, ' ', start_time)) > $tm AND UNIX_TIMESTAMP(CONCAT(start_date, ' ', start_time)) < $tm1 AND FIND_IN_SET($week,`week`)"*/
            ];
            $record = $this->where($where)->find();
            if (empty($record)) {
                return [];
            }
            $wek = explode(",", $record['week']);
            if (in_array($week, $wek)) {
                $start_time = strtotime($today . ' ' . $record['start_time']);
            } else {
                if ($week == 0) {
                    $week = 7;
                }
                foreach ($wek as $key => $val) {
                    if ($val * 1 == 0) {
                        $wek[$key] = '7';
                    }
                }
                asort($wek);//重新排序
                $weks = array();
                foreach ($wek as $k => $v) {
                    $weks[] = $v;
                }
                $today1 = "";
                $week_nums = $this->NextNumberArray($week * 1, $weks);

                if ($week_nums * 1 < $week) {//得到最接近的星期小于当前星期数
                    $today1 = date("Y-m-d", $tm + (7 - $week + $week_nums * 1) * 86400);//得到周几
                } elseif ($week_nums * 1 > $week) {//得到最接近的星期大于当前星期数
                    $today1 = date("Y-m-d", $tm + ($week_nums * 1 - $week) * 86400);//得到周几
                }
                if ($today1 != "") {
                    $start_time = strtotime($today1 . ' ' . $record['start_time']);
                } else {
                    return [];
                }
            }
            if ($start_time - time() < 12 * 3600 && $start_time - time() > 0) {
                $record['time_out'] = $start_time - time();//倒计时
                $limit_rec = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->order('limit_price asc')->select();

                if ($record['limit_type'] == 0) {
                    $record['limit_num'] = -1;
                }
                $record['goods_name'] = $goods['name'];

                //每日更新库存
                if ($this->update_stock_everyday($record)) {
                    $record['stock'] = $record['origin_stock'];
                }

                if ($record['is_spec'] == 1) {
                    $record['spec_list'] = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->getField('spec_index,id,limit_id,stock,origin_stock,limit_price', true);
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
                if (!empty($limit_rec)) {
                    $record['limit_price'] = $limit_rec[0]['limit_price'];
                } else {
                    if ($record['limit_price'] > 0) {
                        $record['limit_price'] = $record['limit_price'];
                    } else {
                        $record['limit_price'] = $goods['price'];
                    }

                }
                return $record;
            } else {
                return [];
            }
        }
    }

    function NextNumberArray($Number, $NumberRangeArray)
    {
        $l = count($NumberRangeArray);
        $max = max($NumberRangeArray);
        if ($Number > $max) {
            $Number1 = min($NumberRangeArray);
        } else {
            for ($pos = 0; $pos < $l; $pos++) {
                $n = $NumberRangeArray[$pos];
                if ($n < $Number) {
                    continue;
                } else {
                    $Number1 = $NumberRangeArray[$pos];
                    break;
                }
            }
        }
        return $Number1;
    }

    public function update_stock_everyday($record)
    {
        $today = date('Y-m-d');
        if ($record['stock_type'] == 0 && $record['update_stock_date'] != $today) {
            //每日更新库存
            $this->where(['id' => $record['id']])->save(['stock' => $record['origin_stock'], 'update_stock_date' => $today]);
            if ($record['is_spec']) {
                $sql = 'UPDATE ' . C('DB_PREFIX') . 'time_limited_discount_goods_spec SET `stock`=`origin_stock` WHERE limit_id = ' . intval($record['id']);
                M('Time_limited_discount_goods_spec')->query($sql);
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * 根据商品id获取展示限时优惠价格
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function get_discount_price_by_goodsid($goods_id)
    {
        $goods = M('Shop_goods')->where(['goods_id' => $goods_id])->field('price,stock_num')->find();
        if (!$goods) {
            return ['is_discount' => false];
        }
        $record = $this->get_in_progress_record($goods_id);
        if (!$record) {
            return ['is_discount' => false];
        }
        if ($record['is_spec'] == 1) {
            $spec_lists = $record['spec_list'];
            $limit_price = $goods['price'];
            $limit_stock = $goods['stock_num'];
            foreach ($spec_lists as $s) {
                if ($s['limit_price'] < $limit_price) {
                    $limit_price = $s['limit_price'];
                    $limit_stock = $s['stock'];
                }
            }
        } else {
            $limit_price = $record['limit_price'];
            $limit_stock = $record['stock'];
        }
        return ['is_discount' => true, 'discount_price' => $limit_price, 'stock' => $limit_stock, 'max_num' => $record['limit_num']];
    }

    /**
     * 判断店铺是否存在限时优惠
     *
     * @author 张涛
     * @date 2020/04/07
     */
    public function check_store_has_time_limit($store_id)
    {
        $tm = time();
        $week = date('w');
        $today = date('Y-m-d');
        $where = [
            't.is_del' => 0,
            't.end_date' => ['egt', $today],
            't.start_date' => ['elt', $today],
            '_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', t.`end_time`)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', t.`start_time`)) < $tm AND FIND_IN_SET($week,t.`week`)",
            's.store_id' => $store_id,
            's.status' => 1,
            's.goods_type' => ['NEQ', 1]
        ];
        $goods = $this->alias('t')->join('INNER JOIN ' . C('DB_PREFIX') . 'shop_goods AS s ON s.goods_id = t.goods_id')->where($where)->find();
        return $goods ? true : false;
    }

    /**
     * 记录日志
     */
    public function write_log($order_id)
    {
        if (M('Time_limited_discount_sale_record')->where(['order_id' => $order_id])->find()) {
            return false;
        }

        $detail = D('Shop_order_detail')->where(['order_id' => $order_id])->select();
        $data = [];
        foreach ($detail as $v) {
            if ($v['is_seckill'] != 1) {
                continue;
            }
            $limit_id = $limit_spec_id = 0;
            if (empty($v['spec_id'])) {
                //无规格
                $limit_id = $v['limit_id'];
            } else {
                //有规格
                $limit_spec_id = $v['limit_id'];
            }
            $data[] = [
                'limit_id' => $limit_id,
                'limit_spec_id' => $limit_spec_id,
                'price' => $v['price'],
                'old_price' => $v['old_price'],
                'order_id' => $v['order_id'],
                'create_time' => $v['create_time'],
                'num' => $v['num']
            ];
        }
        $data && M('Time_limited_discount_sale_record')->addAll($data);
    }

    /**
     * 检查限购数量
     *
     * @author 张涛
     * @date 2020/04/08
     */
    public function check_limit_num($goods_id, $spec_index = '')
    {
        $record = $this->get_in_progress_record($goods_id);
        if (empty($record)) {
            return [];
        }
        $rs = [];
        if ($spec_index) {
            //规格
            $spec = isset($record['spec_list'][$spec_index]) ? $record['spec_list'][$spec_index] : [];
            if ($spec) {
                $rs['limit_num'] = $record['limit_num'];
                $rs['limit_price'] = isset($spec['limit_price']) ? $spec['limit_price'] : 0;
                $rs['stock'] = isset($spec['stock']) ? $spec['stock'] : 0;
            }
        } else {
            //无规格
            $rs['limit_num'] = $record['limit_num'];
            $rs['limit_price'] = $record['limit_price'];
            $rs['stock'] = $record['stock'];
        }
        return $rs;
    }

    /**
     * 限时优惠选择商品，非附属菜 + 非称重商品
     *
     * @author 张涛
     * @date 2020/04/08
     */
    public function select_goods($search, $shop_type = 'shop')
    {
        $not_subsidiary_sort_ids = M('Shop_goods_sort')->where(['store_id' => $search['store_id'], 'status' => 1])->getField('sort_id', true);
        if (empty($not_subsidiary_sort_ids)) {
            return [];
        }

        $shop_goods_mod = D('Shop_goods');
        $where = ['store_id' => $search['store_id'], 'status' => 1, 'sell_type' => 1, 'sort_id' => ['IN', $not_subsidiary_sort_ids]];
        if ($search['keyword']) {
            $where['name'] = ['LIKE', '%' . $search['keyword'] . '%'];
        }
        if ($shop_type == 'mall') {
            $where['cat_fid'] = ['gt', 0];
        } else {
            $where['goods_type'] = ['neq', 1];
        }
        $count = $shop_goods_mod->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 10);
        $goods_list = $shop_goods_mod
            ->field('goods_id,sort_id,store_id,name,image,price,spec_value,stock_num,is_properties')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('goods_id DESC')
            ->select();

        $goods_image_class = new goods_image();
        foreach ($goods_list as &$g) {
            $tmp_pic_arr = explode(';', $g['image']);
            if ($tmp_pic_arr) {
                $g['image'] = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 0);
            } else {
                $g['image'] = $goods_image_class->get_image_by_path('', 0);
            }
            if ($g['spec_value']) {
                $format_spec = $shop_goods_mod->format_spec_value($g['spec_value'], $g['goods_id'], $g['is_properties']);
                $spec_lists = $format_spec ? $format_spec['list'] : [];
                $spec = [];
                foreach ($spec_lists as $k => $v) {
                    $spec[] = [
                        'key' => $k,
                        'str' => implode('、', array_column($v['spec'], 'spec_val_name')),
                        'price' => $v['price']
                    ];
                }
                $g['spec_json_str'] = $spec ? htmlspecialchars(json_encode($spec, JSON_UNESCAPED_UNICODE)) : '{}';
                $g['spec_list'] = $spec;
            } else {
                $g['spec_json_str'] = '{}';
                $g['spec_list'] = [];
            }
        }
        return ['goods' => $goods_list, 'page_bar' => $page->show(), 'total_page' => $page->totalPage];
    }

    /**
     * 预售选商品
     * @param $search
     * @param string $shop_type
     * @return array
     * @author mrdeng
     */
    public function select_goods1($search, $shop_type = 'shop')
    {
        $not_subsidiary_sort_ids = M('Shop_goods_sort')->where(['store_id' => $search['store_id'], 'status' => 1])->getField('sort_id', true);
        if (empty($not_subsidiary_sort_ids)) {
            return [];
        }

        $shop_goods_mod = D('Shop_goods');
        $where = ['store_id' => $search['store_id'], 'status' => 1, 'sell_type' => 1, 'sort_id' => ['IN', $not_subsidiary_sort_ids]];
        if ($search['keyword']) {
            $where['name'] = ['LIKE', '%' . $search['keyword'] . '%'];
        }
        if ($shop_type == 'mall') {
            $where['cat_fid'] = ['gt', 0];
        } else {
            $where['goods_type'] = ['neq', 1];
        }
        $count = $shop_goods_mod->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 10);
        $goods_list = $shop_goods_mod
            ->field('goods_id,sort_id,store_id,name,image,price,spec_value,stock_num,is_properties,seckill_price')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('goods_id DESC')
            ->select();

        $goods_image_class = new goods_image();
        foreach ($goods_list as &$g) {
            $tmp_pic_arr = explode(';', $g['image']);
            if ($tmp_pic_arr) {
                $g['image'] = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 0);
            } else {
                $g['image'] = $goods_image_class->get_image_by_path('', 0);
            }
            if ($g['spec_value']) {
                $format_spec = $shop_goods_mod->format_spec_value($g['spec_value'], $g['goods_id'], $g['is_properties']);
                $spec_lists = $format_spec ? $format_spec['list'] : [];
                $spec = [];
                foreach ($spec_lists as $k => $v) {
                    $spec[] = [
                        'key' => $k,
                        'str' => implode('、', array_column($v['spec'], 'spec_val_name')),
                        'price' => $v['price']
                    ];
                }
                $g['spec_json_str'] = $spec ? htmlspecialchars(json_encode($spec, JSON_UNESCAPED_UNICODE)) : '{}';
                $g['spec_list'] = $spec;
            } else {
                $g['spec_json_str'] = '{}';
                $g['spec_list'] = [];
            }
        }
        return ['goods' => $goods_list, 'page_bar' => $page->show(), 'total_page' => $page->totalPage];
    }

    /**
     * 获取数据统计
     *
     * @author 张涛
     * @date 2020/04/09
     */
    public function get_sale_statistics($limit_id, $spec_id, $days)
    {
        $mod = M('Time_limited_discount_sale_record');
        $where = [];
        $limit_id && $where['limit_id'] = $limit_id;
        $spec_id && $where['limit_spec_id'] = $spec_id;
        $begin_time = strtotime(date('Y-m-d', strtotime("-$days days")));
        $end_time = time();
        $where['create_time'] = [
            ['EGT', $begin_time],
            ['ELT', $end_time]
        ];
        $total_where = ['limit_id' => $limit_id];
        //商品销量、总销量
        $sale_num = $mod->where($where)->sum('num');
        $total_sale_num = $mod->where($total_where)->sum('num');
        //订单数
        $order_num = $mod->where($where)->count();
        $total_order_num = $mod->where($total_where)->count();
        //活动订单净收入
        $order_price = $mod->where($where)->sum('price');
        $total_order_price = $mod->where($total_where)->sum('price');
        //活动订单补贴支出
        $order_subsidy_price = $mod->where($where)->sum('`old_price` - `price`');
        $order_subsidy_price < 0 && $order_subsidy_price = 0;
        //平均客单价
        $average_price = $total_order_num > 0 ? getFormatNumber($total_order_price / $total_order_num) : 0;
        //平均投入产出比
        $input_output_ratio = $order_subsidy_price ? round($total_order_price / $order_subsidy_price, 4) : 0;
        $rs = [
            'sale_num' => is_null($sale_num) ? 0 : $sale_num,
            'total_sale_num' => is_null($total_sale_num) ? 0 : $total_sale_num,
            'order_num' => is_null($order_num) ? 0 : $order_num,
            'total_order_num' => is_null($total_order_num) ? 0 : $total_order_num,
            'order_price' => is_null($order_price) ? 0 : $order_price,
            'total_order_price' => is_null($total_order_price) ? 0 : $total_order_price,
            'order_subsidy_price' => is_null($order_subsidy_price) ? 0 : $order_subsidy_price,
            'average_price' => is_null($average_price) ? 0 : $average_price,
            'input_output_ratio' => is_null($input_output_ratio) ? '0%' : ($input_output_ratio * 100) . '%'
        ];
        return $rs;
    }

    /**
     * 详情
     *
     * @author 张涛
     * @date 2020/04/09
     */
    public function get_detail($limit_id, $spec_id)
    {
        $where = ['d.id' => $limit_id];
        if ($spec_id > 0) {
            $where['spec.id'] = $spec_id;
        }
        $detail = $this
            ->alias('d')
            ->join(C('DB_PREFIX') . 'shop_goods AS g ON d.goods_id = g.goods_id')
            ->join(C('DB_PREFIX') . 'time_limited_discount_goods_spec AS spec ON spec.limit_id = d.id')
            ->field('d.*,g.name AS goods_name,g.price,spec.spec_index,spec.limit_price AS spec_limit_price')
            ->where($where)
            ->find();
        $symbol = C('config.Currency_symbol');
        $status_arr = $this->get_status($limit_id);
        $detail['status'] = isset($status_arr['status']) ? $status_arr['status'] : 0;
        $detail['status_str'] = isset($status_arr['status_str']) ? $status_arr['status_str'] : '--';
        $detail['week_str'] = $this->get_week_str($detail['week']);
        if ($spec_id > 0) {
            $goods = D('Shop_goods')->where(['goods_id' => $detail['goods_id']])->field('goods_id,spec_value,is_properties')->find();
            $format_spec = D('Shop_goods')->format_spec_value($goods['spec_value'], $goods['goods_id'], $goods['is_properties']);
            $format_spec_list = isset($format_spec['list']) ? $format_spec['list'] : [];
            $format_spec_info = isset($format_spec_list[$detail['spec_index']]) ? $format_spec_list[$detail['spec_index']] : [];
            $spec_str = empty($format_spec_info['spec']) ? '' : implode('、', array_column($format_spec_info['spec'], 'spec_val_name'));
            if ($spec_str) {
                $detail['goods_name'] .= '(' . $spec_str . ')';
            }
            $detail['price'] = isset($format_spec_info['price']) ? $format_spec_info['price'] : $detail['price'];
            $detail['limit_price'] = $detail['spec_limit_price'];
        }
        $detail['goods_name'] = $detail['goods_name'] ?: '原商品已被删除';
        $discount_val = $detail['price'] > 0 ? getFormatNumber($detail['limit_price'] / $detail['price']) * 10 : '--';
        $detail['discount_str'] = sprintf("【%s】商品%s折售卖，原价：%s%.2f，活动价：%s%.2f", $detail['goods_name'], $discount_val, $symbol, $detail['price'], $symbol, $detail['limit_price']);


        //计算多少天结束
        $detail['days_end'] = intval((strtotime($detail['end_date']) - time()) / 86400);
        return $detail;
    }

    protected function get_week_str($week)
    {
        $week_arr = $this->get_week_arr();
        if (!is_array($week)) {
            $week = explode(',', $week);
        }
        $week_str = array_map(function ($v) use ($week_arr) {
            return isset($week_arr[$v]) ? $week_arr[$v] : $v;
        }, $week);
        return implode(',', $week_str);
    }

    protected function get_week_arr()
    {
        $week_arr = [L_('星期日'), L_('星期一'), L_('星期二'), L_('星期三'), L_('星期四'), L_('星期五'), L_('星期六')];
        return $week_arr;
    }

    /**
     * 获取限时优惠数量
     * @author 张涛
     * @date 2020/04/17
     */
    public function stats($storeId)
    {
        $stats = [
            'to_do' => 0,
            'doing' => 0,
            'finish' => 0,
            'cancel' => 0
        ];
        $today = date('Y-m-d');
        $stats['to_do'] = $this->where(['store_id' => $storeId, 'is_del' => 0, 'start_date' => ['GT', $today]])->count();
        $stats['doing'] = $this->where(['store_id' => $storeId, 'is_del' => 0, 'start_date' => ['ELT', $today], 'end_date' => ['EGT', $today]])->count();
        $stats['finish'] = $this->where(['store_id' => $storeId, 'is_del' => 0, 'end_date' => ['LT', $today]])->count();
        $stats['cancel'] = $this->where(['store_id' => $storeId, 'is_del' => 1])->count();
        return $stats;
    }

    /**
     * 获取今日销量
     * @author 张涛
     * @date 2020/04/17
     */
    public function getSaleByDate($limitId, $specId = 0, $date)
    {
        $tm = strtotime($date);
        $where = ['create_time' => [['GT', $tm], ['LT', $tm + 86400]]];
        $limitId && $where['limit_id'] = $limitId;
        $specId && $where['limit_spec_id'] = $specId;
        $count = M('Time_limited_discount_sale_record')->where($where)->count();
        return intval($count);
    }
}