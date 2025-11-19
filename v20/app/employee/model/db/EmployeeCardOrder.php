<?php
   /**
     * 充值订单     
    * */

namespace app\employee\model\db;


use think\Model;

class EmployeeCardOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 列表
     */
    public function orderList($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->join($prefix.'employee_card_user u','u.user_id = g.user_id')
            ->join($prefix.'employee_card c','c.card_id = g.card_id');
        $ret['total']=$result->count();

        $ret['list']=$result->order($order)
            ->group('g.order_id')
            ->select()
            ->toArray();
        return $ret;
    }

    /**
     * 获取订单列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 0, $field = 'o.*,a.card_number,a.card_money,a.identity,a.department,a.name as card_name', $order = 'o.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $list = $this->alias('o')
                ->field($field)
                ->join($prefix . 'employee_card_user a', 'o.user_id = a.user_id', 'left')
                ->join($prefix . 'user u', 'a.uid = u.uid', 'left')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'employee_card_user a', 'o.user_id = a.user_id', 'left')
                ->join($prefix . 'user u', 'a.uid = u.uid', 'left')
                ->where($where)
                ->limit($limit)
                ->order($order)
                ->select();
            $list = [
                'data' => []
            ];
            if (!empty($arr)) {
                $list['data'] = $arr->toArray();
            }
        } else {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'employee_card_user a', 'o.user_id = a.user_id', 'left')
                ->join($prefix . 'user u', 'a.uid = u.uid', 'left')
                ->where($where)
                ->order($order)
                ->select();
            $list = [
                'data' => []
            ];
            if (!empty($arr)) {
                $list['data'] = $arr->toArray();
            }
        }
        return $list;
    }

    /**
     * 获取订单详情
     * @param $where array
     * @return array
     */
    public function getDetail($where = [], $field = 'o.*,a.card_number,a.card_money,a.status') {
        $prefix = config('database.connections.mysql.prefix');
        $data   = $this->alias('o')
            ->field($field)
            ->join($prefix . 'employee_card_user a', 'o.user_id = a.user_id', 'left')
            ->where($where)
            ->find();
        if (!empty($data)) {
            $data = $data->toArray();
        } else {
            $data = [];
        }
        return $data;
    }

    /**
     * 计算总和
     * @param $where array
     */
    public function getSum($where = [], $field = 'total_price') {
        return $this->where($where)->sum($field);
    }

}