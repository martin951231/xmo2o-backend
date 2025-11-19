<?php

/**
 * 运动约战的订单表
 */


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsOrderBindSportsActivity extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $group_status = [ //订单状态 0未支付 10进行中 20已成团，30未成团已取消,40已成团已退款
        0  => '未支付',
        10 => '约战中',
        20 => '已成团',
        30 => '未成团已取消',
        40 => '已成团已退款',
        50 => '已核销',
        60 => '未成团已退款'
    ];

    public $group_type = [ //成团类型 1-团长请客 2-AA
        1 => '团长请客',
        2 => 'AA'
    ];

    /**
     *获取订单列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 0, $field = 'r.*,o.real_orderid,o.order_status,o.ticket_title,o.uid,o.nickname,o.phone,o.ticket_time,o.add_time,o.pay_type,o.price,a.title as tools_title,a.cover_image,a.long,a.lat', $order = 'o.add_time desc',$whereOr=[]) {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = r.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_sports_activity act', 'r.activity_id = act.activity_id', 'left');
            if($whereOr){
                $arr = $arr->whereOr([$where,$whereOr]);
            }else{
                $arr = $arr->where($where);
            }
            $arr = $arr->order($order)
                ->paginate($limit);
        } else if ($limit > 0) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = r.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_sports_activity act', 'r.activity_id = act.activity_id', 'left');
            if($whereOr){
                $arr = $arr->whereOr([$where,$whereOr]);
            }else{
                $arr = $arr->where($where);
            }
            $arr = $arr->limit($limit)
                ->order($order)
                ->select();
        } else {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = r.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_sports_activity act', 'r.activity_id = act.activity_id', 'left');
            if($whereOr){
                $arr = $arr->whereOr([$where,$whereOr]);
            }else{
                $arr = $arr->where($where);
            }
            $arr = $arr->order($order)
                ->select();
        }
        return !empty($arr) ? $arr->toArray() : [];
    }

    /**
     *获取订单详情
     * @param $where array
     * @return array
     */
    public function getDetail($where = [], $field = 'r.*,o.order_status,o.real_orderid,o.orderid,o.total_price,o.ticket_title,o.uid,o.nickname,o.phone,o.ticket_time,o.add_time,a.title as tools_title,a.cover_image,a.long,a.lat,a.time_txt,a.address,a.phone as tools_phone,a.tools_id') {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('r')
            ->field($field)
            ->join($prefix . 'life_tools_order o', 'o.order_id = r.order_id', 'left')
            ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
            ->join($prefix . 'life_tools_ticket t', 'o.ticket_id = t.ticket_id', 'left')
            ->where($where)
            ->find();
        return !empty($arr) ? $arr->toArray() : [];
    }

    /**
     * 约战订单
     */
    public function getOtherOrder($leaderOrderId,$uid)
    {
        $result = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->where(['a.leader_order_id'=>$leaderOrderId,'b.uid'=>$uid])
            ->where([['a.group_status','IN',[0,10,20,50]]])
            ->field('a.order_id')
            ->find();
        return $result;
    }

    /**
     * 获取所有人的openid
     */
    public function getUserInfo($leaderOrderId)
    {
        $result = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->join('user c','b.uid = c.uid')
            ->where(['a.leader_order_id'=>$leaderOrderId])
            ->where([['a.group_status','IN',[0,10,20,50]]])
            ->field('c.openid')
            ->find();
        return $result;
    }
}