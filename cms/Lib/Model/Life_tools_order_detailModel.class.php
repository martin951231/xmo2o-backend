<?php
/**
 * 景区体育健身订单详情
 */
class Life_tools_order_detailModel extends Model{

    public function get_verify_list($where = array(), $order = 'd.last_time DESC')
    {
        $condition_where = "`o`.`order_id` = `d`.`order_id` AND `o`.`tools_id` = `t`.`tools_id`";
        $condition_table = array(
            C('DB_PREFIX') . 'life_tools_order_detail' => 'd',
            C('DB_PREFIX') . 'life_tools_order' => 'o',
            C('DB_PREFIX') . 'life_tools' => 't'
        );
        $condition_field = '`o`.order_id,`o`.real_orderid,`o`.ticket_title,`o`.nickname as username,`o`.phone as userphone,`o`.sku_type,`d`.price,`d`.last_time,`d`.sku_id,`t`.title,`t`.type,`t`.address';
        $count = $this->field($condition_field)->where($where)->where($condition_where)->table($condition_table)->order($order)->count();
        $page  = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pagesize = 10;
        $star = $pagesize * ($page - 1);
        $list = $this->field($condition_field)->where($where)->where($condition_where)->table($condition_table)->order($order)->limit($star . ',' . $pagesize)->select();
        foreach ($list as $k => &$order) {
            $order['last_time']  = date('Y-m-d H:i:s', $order['last_time']);
            $order['userphone']  = phone_show($order['userphone']);
            switch ($order['type']) {
                case 'course':
                    $order['order_from'] = '体育课程';
                    break;
                case 'stadium':
                    $order['order_from'] = '体育馆';
                    break;
                case 'scenic':
                    $order['order_from'] = '景区';
                    break;
            }
            //场馆分布图
            $list[$k]['sku_str'] = '';
            if($order['sku_type'] == 2 && $order['sku_id']){
                $list[$k]['sku_str'] = M('Life_tools_ticket_sku')->where(['sku_id' => $order['sku_id']])->getField('sku_str');
            }
            // $order['order_from'] = $order['type'] == 'course' ? '体育课程' : '体育馆';
        }
        return array('order_list' => $list, 'page' => ceil($count / 10),'count' => $count);
    }

}

?>