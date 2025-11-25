<?php

/**
 * 门票订单model
 */


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    protected $pk = 'order_id';

    public $order_status = [ //10未支付不显示 20未消费已付款，30已消费未评价,40已消费已评价 45申请退款中 50用户取消已退款  60未付款已过期 70已付款已过期 80已全部转赠
        '10' => '待支付', //待付款
        '20' => '未核销已付款', //待核销
        '30' => '已核销未评价', //已核销
        '40' => '已核销已评价', //已核销
        '45' => '申请退款中', //售后中
        '50' => '用户取消已退款', //已退款
        '60' => '未付款已过期', //已取消
        '70' => '已付款已过期', //已过期
        '80' => '已全部转赠', //全部转赠
    ];

    public $sourceMap = [
        'pc'            =>      'PC',
        'wechat_mini'   =>      'H5',
        'h5'            =>      'H5',
        'wxapp'         =>      '微信小程序',
        'wechat_mini'   =>      '微信小程序',
        'wechat_h5'     =>      '微信H5',
        'alipayapp'     =>      '支付宝H5',
        'alipay'        =>      '支付宝H5',
        'ios'           =>      'APP',
        'iosapp'        =>      'APP',
        'androidapp'    =>      'APP',
        'window'        =>      '窗口购票',
        'OTA'           =>      'OTA'
    ];
    /**
     *获取订单列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = [], $field = 'o.*,o.order_source,a.type,a.title,t.start_time', $order = 'o.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (!empty($limit)) {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_ticket t', 'o.ticket_id = t.ticket_id', 'left')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->append(['add_time_text', 'ticket_type'])
                ->toArray();
        } else {
            $list = $this->alias('o')
                ->field($field)
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id')
                ->join($prefix . 'life_tools_ticket t', 'o.ticket_id = t.ticket_id', 'left')
                ->where($where)
                ->order($order)
                ->append(['add_time_text', 'ticket_type'])
                ->select();
            if (!empty($list)) {
                $arr = [
                    'data' => $list->toArray()
                ];
            } else {
                $arr = [
                    'data' => []
                ];
            }
        }
        return $arr;
    }

    public function getListDetail($where = [], $limit = [], $field = 'o.*,a.type,a.title,t.start_time', $order = 'o.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (!empty($limit)) {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_order_detail t', 'o.order_id = t.order_id')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $list = $this->alias('o')
                ->field($field)
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id')
                ->join($prefix . 'life_tools_order_detail t', 'o.order_id = t.order_id')
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($list)) {
                $arr = [
                    'data' => $list->toArray()
                ];
            } else {
                $arr = [
                    'data' => []
                ];
            }
        }
        return $arr;
    }

    /**
     *获取订单列表
     * @param $where array
     */
    public function getListSum($where = [], $field = 'num') {
        $prefix = config('database.connections.mysql.prefix');
        $sum    = $this->alias('o')
            ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id')
            ->join($prefix . 'life_tools_ticket t', 'o.ticket_id = t.ticket_id', 'left')
            ->where($where)
            ->sum($field);
        return $sum;
    }

    /**
     *获取订单详情
     * @param $where array
     * @return array
     */
    public function getDetail($where = [], $field = 'o.*,a.type,a.title,a.city_id') {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('o')
            ->field($field)
            ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id')
            ->where($where)
            ->find();
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
     * @return array
     */
    public function getSum($where = [], $field = 'num') {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    public function tools()
    {
        return $this->belongsTo(LifeTools::class, 'tools_id', 'tools_id');
    }

    public function getAddTimeTextAttr($value, $data)
    {
        return isset($data['add_time']) ? date('Y-m-d H:i:s', $data['add_time']) : '';
    }

    public function getVerifyTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['verify_time']);
    }

    public function getTicketTypeAttr($value, $data)
    {
        if(isset($data['order_source']) && $data['order_source'] == 'pft'){
            return '票付通门票';
        }else{
            return (isset($data['is_group']) && $data['is_group'] == 1) ? '团体票' : '普通票';
        }
    }

    /**
     * 获取核销列表
     * @param $where array
     * @return array
     */
    public function getVerifyList($where = [], $limit = [], $field = 'o.*,a.type,a.title', $order = 'o.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
         
        return $this->alias('o')
                ->field($field)
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'life_tools_order_detail od', 'od.order_id = o.order_id', 'left')
                ->join($prefix . 'life_tools_ticket t', 'o.ticket_id = t.ticket_id', 'left')
                ->where($where)
                ->order($order)
                ->append(['add_time_text', 'verify_time_text'])
                ->paginate($limit)
                ->each(function($item, $key){
                    $typeArr = ['scenic'=>'景区','stadium'=>'场馆','course'=>'课程'];
                    $item->type_text = $typeArr[$item['type']] ?? '';
                    $item->order_status_text = $this->order_status[$item['order_status']] ?? '';
                });
    }

    /**
     * 金额统计
     */
    public function sumPrice($where = [])
    {
        $prefix = config('database.connections.mysql.prefix');

        return $this->alias('o')
            ->join($prefix . 'life_tools_order_detail od', 'od.order_id = o.order_id', 'left')
            ->where($where)
            ->sum('od.price');
    }

    /**
     * 通过订单id获取团体篇用户信息
     * @author Nd
     * @date 2022/4/24
     * @param $order_id
     */
    public function getUserInfo($where,$field)
    {
        $data = $this->alias('a')
            ->join('user b','a.uid = b.uid')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }

    /**
     * 导出数据
     */
    public function getExportData($export_type, $type='scenic')
    { 
        $prefix = config('database.connections.mysql.prefix');
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
        }
        $where = '';
        if($type == 'scenic'){
            $where .= "t.type = 'scenic'";
        }else{
            $where .= "t.type in ('stadium', 'course')";
        }

        $where .= " AND o.order_status > 10";
         
        $sql = "SELECT FROM_UNIXTIME( o.add_time, '{$dateText}' ) AS dates,
                    COUNT( CASE WHEN `order_status` IN (20, 30, 40, 45, 70, 80) THEN 1 ELSE NULL END) AS total_order,
                    (SUM(CASE WHEN `order_status` IN (20, 30, 40, 45, 50, 70, 80) THEN o.total_price ELSE 0 END) - SUM(CASE WHEN `order_status` <> 45 THEN o.refund_money ELSE 0 END)) AS total_money,
                    SUM(CASE WHEN `order_status` <> 45 THEN o.refund_money ELSE 0 END) AS refund_money,
                    SUM(o.offline_pay_money) AS offline_pay_money
                FROM {$prefix}life_tools_order AS o
                LEFT JOIN {$prefix}life_tools AS t ON o.tools_id = t.tools_id
                WHERE {$where}
                GROUP BY FROM_UNIXTIME( o.add_time, '{$dateText}' )";
        return $this->query($sql);
    }
}