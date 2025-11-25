<?php

class Merchant_qiye_money_listModel extends Model
{
    public function get_income_list($search, $limit = 20, $is_system = false)
    {
        $where = [];
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        if ($search['mer_id']) {
            $where['l.mer_id'] = intval($search['mer_id']);
        }
        if ($search['store_id']) {
            $where['l.store_id'] = $search['store_id'];
        }
        if ($search['type']) {
            $where['l.type'] = $search['type'];
        }
        if ($search['begin_time']) {
            $where['l.use_time'][] = ['egt', $search['begin_time']];
        }
        if ($search['end_time']) {
            $where['l.use_time'][] = ['elt', $search['end_time']];
        }
        if ($search['order_id']) {
            $where['l.order_id'] = $search['order_id'];
        }

        $count = $this->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id left join ' . C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id ')->where($where)->count();

        $p = new Page($count, $limit);
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id ';
        $join .= 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id ';
        $income_list = $this
            ->alias('l')
            ->join($join)
            ->field('ms.name as store_name,l.*')
            ->where($where)
            ->order('id DESC')
            ->limit($p->firstRow, $p->listRows)
            ->select();
        return array('pagebar' => $p->show(), 'income_list' => $income_list);
    }
}