<?php
// 平台最低利润
class Merchant_minimum_profitModel extends Model{
     /*得到平台最低利润列表*/
    public function get_limit_list_page( $column = array(), $pageSize = 20)
    {
        $return = array();
        $count = $this->where($column)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list =  $this->where($column)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
        // var_dump($this->_sql());
        // var_dump($list);
        if ($list) {
            foreach ($list as &$value) {
                $value['score_pay_back_money'] = round(($value['score_pay_back'] * $value['money'])/100,2);
                $value['score_pay_back_mer_money'] = round(($value['score_pay_back_mer'] * $value['money'])/100,2);
                $value['user_spread_money'] = round(($value['user_spread'] * $value['money'])/100,2);
                $value['mer_spread_money'] = round(($value['mer_spread'] * $value['money'])/100,2);
                $value['reserve_fund_money'] = round(($value['reserve_fund'] * $value['money'])/100,2);
                $value['minimum_profit_money'] = round(($value['minimum_profit'] * $value['money'])/100,2);
            }
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['totalCount'] = $count;
        $return['pagebar'] = $p->show();
        $return['list'] = $list ?  $list : array();
        return $return;
    }
}