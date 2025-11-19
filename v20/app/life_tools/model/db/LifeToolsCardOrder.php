<?php


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsCardOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $order_status = [ //订单状态 10=未支付 20=已支付 30=已核销 40=已完成 50=退款申请中 51=已退款 60=未付款已过期 70=已付款已过期
        '10' => '未支付',
        '20' => '已支付',
        '30' => '已核销',
        '40' => '已完成',
        '50' => '退款申请中',
        '51' => '已退款',
        '60' => '已取消',
        '70' => '已过期'
    ];

    public function getOrderStatus($order_status = 10) {
        return $this->order_status[$order_status] ?? '未知状态';
    }

    /**
     *获取订单列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = [], $field = '*,title as card_title', $order = 'add_time desc') {
        if (!empty($limit)) {
            $arr = $this
                ->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $arr = $this
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
        }
        return $arr;
    }

     /**
     *获取订单列表
     * @param $where array
     * @return array
     */
    public function getOrderList($where = [], $limit = [], $field = 'o.*,o.title as card_title,c.num as total_num', $order = 'o.add_time desc') {
        if (!empty($limit)) {
            $arr = $this->alias('o')
                ->field($field)
                ->join('life_tools_card c', 'o.card_id = c.pigcms_id')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $arr = $this->alias('o')
                ->field($field)
                ->join('life_tools_card c', 'o.card_id = c.pigcms_id')
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
        }
        return $arr;
    }

    /**
     *获取订单列表
     * @param $where array
     */
    public function getListSum($where = [], $field = 'num') {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    /**
     *获取订单详情
     * @param $where array
     * @return array
     */
    public function getDetail($where = [], $field = '*') {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    /**
     * 根据条件获取总和
     * @param $where array
     */
    public function getSum($where = [], $field = 'num') {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    /**
     * 获取次卡列表
     */
    public function getAllList($where = [], $field = 'o.*,o.title AS o_title,c.*') {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->join($prefix . 'life_tools_card c', 'o.card_id = c.pigcms_id')
            ->where($where)
            ->select();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    /**
     * 获取次卡详情
     */
    public function getAllDetail($where = [], $field = 'c.*,o.*,c.num as all_num') {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->join($prefix . 'life_tools_card c', 'o.card_id = c.pigcms_id')
            ->where($where)
            ->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    public function getAddTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['add_time']);
    }

    public function getVerifyTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['verify_time']);
    }

     /**
     * 获取核销列表
     * @param $where array
     * @return array
     */
    public function getVerifyList($where = [], $limit = [], $field = 'o.*', $order = 'od.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
         
        return $this->alias('o')
            ->field($field)
            ->join($prefix . 'life_tools_card c', 'o.card_id = c.pigcms_id')
            ->join($prefix . 'life_tools_card_order_record od', 'od.order_id = o.order_id')
//            ->join($prefix . 'life_tools t', 'od.tools_id = t.tools_id')
            ->where($where)
            ->append(['add_time_text', 'verify_time_text'])
            ->order($order)
            ->paginate($limit)
            ->each(function($item, $key){
                $typeArr = ['scenic'=>'景区','stadium'=>'场馆','course'=>'课程'];
                $item->type_text = $typeArr[$item['type']] ?? '';
                $item->order_status_text = $this->order_status[$item['order_status']] ?? '';
            });
    }
    /**
     * 导出数据
     */
    public function getExportData($export_type, $type='scenic')
    {
        $prefix = config('database.connections.mysql.prefix');
        $where = '';
        switch($export_type){
            case 0: //日
                $dateText = '%Y-%m-%d';
                break;
            case 1: //月
                $dateText = '%Y-%m';
                break;
            case 2: //年
                $dateText = '%Y';
                break;
            case 3: //全部
                $dateText = '';
                break;
            case 4: //今日
                $dateText = '';
                $where .= " o.add_time >= ".strtotime(date('Y-m-d'));
                $where .= " AND o.add_time <= ".strtotime(date('Y-m-d').' 23:59:59');
                break;
        }

        $where .= $where ? " AND " : "";
        if($type == 'scenic'){
            $where .= "o.type = 'scenic'";
        }else{
            $where .= "o.type in ('stadium', 'course')";
        }

        $where .= " AND o.order_status > 10";

        $sql = "SELECT";
        if($dateText){
            $sql .= " FROM_UNIXTIME( o.add_time, '{$dateText}' ) AS dates,";
        }
        $sql .= " COUNT( CASE WHEN `order_status` IN (20, 30, 40, 50) THEN 1 ELSE NULL END) AS total_order,
                    SUM(CASE WHEN `order_status` IN (20, 30, 40, 50) THEN o.total_price ELSE 0 END) AS total_money,
                    SUM(CASE WHEN `order_status` = 51 THEN o.refund_money ELSE 0 END) AS refund_money";
        $sql .= " FROM {$prefix}life_tools_card_order AS o
                WHERE {$where}
                GROUP BY FROM_UNIXTIME( o.add_time, '{$dateText}' )";
        return $this->query($sql);
    }

    /**
     * 获取核销列表
     * @param $where array
     * @return array
     */
    public function getCardVerifyList($where = [], $limit = 10, $field = 'o.*,c.num as all_num,od.add_time as verify_time', $order = 'od.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');

        return $this->alias('o')
            ->field($field)
            ->join($prefix . 'life_tools_card c', 'o.card_id = c.pigcms_id')
            ->join($prefix . 'life_tools_card_order_record od', 'od.order_id = o.order_id')
            ->join($prefix . 'life_tools t', 'od.tools_id = t.tools_id','left')
            ->where($where)
            ->order($order)
            ->paginate($limit);
    }
}