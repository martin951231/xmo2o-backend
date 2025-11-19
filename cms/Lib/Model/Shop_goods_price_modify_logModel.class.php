<?php

class Shop_goods_price_modify_logModel extends Model
{
    public function get_lists($search, $is_system = true)
    {
        $where = [];
        if (isset($search['mer_id'])) {
            $where['l.mer_id'] = intval($search['mer_id']);
        }
        if (!empty($search['goods_name'])) {
            $where['g.name'] = ['LIKE', '%' . trim($search['goods_name']) . '%'];
        }
        if (!empty($search['store_name'])) {
            $where['s.name'] = ['LIKE', '%' . trim($search['store_name']) . '%'];
        }
        if (!empty($search['merchant_name'])) {
            $where['m.name'] = ['LIKE', '%' . trim($search['merchant_name']) . '%'];
        }
        if (!empty($search['begin_time'])) {
            $where['l.time'][] = [
                'egt', strtotime($search['begin_time'])
            ];
        }
        if (!empty($search['end_time'])) {
            $where['l.time'][] = [
                'lt', strtotime($search['end_time']) + 86400
            ];
        }
        if (empty($where['l.time']) && $search['selectTimeType'] > 0) {
            $start_time = strtotime(date('Y-m-d', time() - ($search['selectTimeType'] - 1) * 86400));
            $where['l.time'] = ['egt', $start_time];
        }
        if($_GET['mer_status'] == 1){
            $where['m.status'] = 1;
        }

        $join = "LEFT JOIN " . C('DB_PREFIX') . 'merchant AS m ON m.mer_id=l.mer_id ';
        $join .= "LEFT JOIN " . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id=l.store_id ';
        $join .= "LEFT JOIN " . C('DB_PREFIX') . 'shop_goods AS g ON g.goods_id=l.goods_id ';
        $count = $this->alias('l')
            ->join($join)
            ->where($where)
            ->count();

        if ($is_system) {
            import('@.ORG.system_page');
            $p = new Page($count, 10, true);
        } else {
            import('@.ORG.merchant_page');
            $p = new Page($count, 10);
        }

        $logs = $this->alias('l')
            ->join($join)
            ->where($where)
            ->field('l.*,m.name AS merchant_name,m.phone,s.name AS store_name,s.phone AS store_phone,g.name AS real_goods_name')
            ->order('l.id DESC')
            ->limit($p->firstRow, $p->listRows)
            ->select();
        foreach ($logs as $k => $v) {
            $logs[$k]['spec_value_array'] = $v['spec_value'] ? json_decode($v['spec_value'], true) : [];
        }
        return [
            'pagebar' => $p->show(),
            'logs' => $logs
        ];
    }
}