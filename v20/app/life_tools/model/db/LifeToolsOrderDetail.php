<?php

/**
 * 门票订单详情model
 */


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsOrderDetail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'detail_id';

    protected $status = [
        '1'=>'未消费',
        '2'=>'已消费',
        '3'=>'已退款',
        '4'=>'已转赠',
    ];
    protected $verifyType = [
        '0'=>' ',
        '1'=>'闸机核销',
        '2'=>'平台操作核销',
        '3'=>'app店员核销',
        '4'=>'pc店员核销'
    ];

    /**
     * 根据条件获取总和
     * @param $where array
     */
    public function getSum($where = [], $field = 'num') {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    /**
     *获取核销列表
     * @param $where array
     * @return array
     */
    public function getVerifyList($where = [], $limit = 0, $field = 'd.*,o.mer_id,o.uid,o.real_orderid,a.type,a.title,s.name as store_name', $order = 'd.last_time desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('d')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = d.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'merchant_store s', 'd.store_id = s.store_id', 'left')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $data = $this->alias('d')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = d.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'merchant_store s', 'd.store_id = s.store_id', 'left')
                ->where($where)
                ->limit($limit)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        } else {
            $data = $this->alias('d')
                ->field($field)
                ->join($prefix . 'life_tools_order o', 'o.order_id = d.order_id', 'left')
                ->join($prefix . 'life_tools a', 'o.tools_id = a.tools_id', 'left')
                ->join($prefix . 'merchant_store s', 'd.store_id = s.store_id', 'left')
                ->where($where)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        }
        return $arr;
    }

    public function getStatusText($status)
    {
        return $this->status[$status];
    }
    public function getVerifyTypeText($verifyType)
    {
        return $this->verifyType[$verifyType];
    }

    /**
     * 通过核销码查询订单详情
     * @author Nd
     * @date 2022/4/25
     */
    public function getInfoByCode($where,$field)
    {
        $data = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->join('user c','b.uid = c.uid')
            ->join('life_tools d','b.tools_id = d.tools_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     * 获取景区体育销量
     */
    public function getSaleCount($tools_id = 0)
    {
        return $this->alias('d')
            ->join('life_tools_order o', 'd.order_id = o.order_id')
            ->where([['o.tools_id', '=', $tools_id], ['d.status', 'in', [1, 2]]])
            ->count();
    }
    /**
     * 获取门票销量
     */
    public function getTicketSaleCount($ticket_id = 0)
    {
        return $this->alias('d')
            ->join('life_tools_order o', 'd.order_id = o.order_id')
            ->where([['o.ticket_id', '=', $ticket_id], ['d.status', 'in', [1, 2]]])
            ->count();
    }
}