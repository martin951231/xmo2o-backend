<?php
/**
 * MallOrder.php
 * 商城订单model
 * Create on 2020/9/10 17:37
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallOrder extends Model
{
    protected $pk = 'order_id';
    
    use \app\common\model\db\db_trait\CommonFunc;

    public static $orderStatus = [
        'wait_send' => 10, //待发货
    ];
    protected $verify = [
        1=>'手动核销（移动端）',
        2=>'手动核销（pc端）',
        3=>'扫码核销'
    ];
    public function getVerifyText($id)
    {
        $verifyText = $this->verify[$id]??'未知核销方式';
        return $verifyText;
    }
    
    public function extraDelivery()
    {
        return $this->hasMany(MallOrderDelivery::class, 'order_id', 'order_id');
    } 
    
    public function orderDetail()
    {
        return $this->hasMany(MallOrderDetail::class, 'order_id', 'order_id');
    }
    
    public function mallPeriodicDeliver()
    {
        return $this->hasMany(MallNewPeriodicDeliver::class, 'order_id', 'order_id');
    }
    /**
     * 通过订单id获取订单信息
     * @param $orderid
     * @return array
     */
    public function getOne($orderid)
    {
        $where = [
            ['order_id', '=', $orderid],
        ];
        $arr = $this->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getRudTrade($where=[])
    {
            $prefix = config('database.connections.mysql.prefix');
            $field = 'o.mer_id,o.store_id,o.order_id,o.order_no,re.refund_id,r.refund_money,re.refund_nums,re.order_detail_id';
            array_push($where,['r.status', '=', 1]);
            $arr = $this->alias('o')
                ->join($prefix . 'merchant_store s', 's.store_id = o.store_id')
                ->join($prefix . 'mall_order_refund r', 'r.order_id = o.order_id')
                ->join($prefix . 'mall_order_refund_detail re', 're.refund_id = r.refund_id')
                ->field($field)
                ->where($where)
                ->select()
                ->toArray();
            return $arr;
    }

    public function getRudTradeNew($where=[])
    {
            $prefix = config('database.connections.mysql.prefix');
            $field = 'o.mer_id,o.store_id,o.order_id,o.order_no,r.refund_money';
            array_push($where,['r.status', '=', 1]);
            $arr = $this->alias('o')
                ->join($prefix . 'merchant_store s', 's.store_id = o.store_id')
                ->join($prefix . 'mall_order_refund r', 'r.order_id = o.order_id')
                ->field($field)
                ->where($where)
                ->select()
                ->toArray();
            return $arr;
    }

    /**
     * 条件查询
     * @param $field
     * @param $where
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getSearch($field, $where, $where_periodic, $page = '', $pageSize = '')
    {
        if (!empty($page) && !empty($pageSize)) {
            $prefix = config('database.connections.mysql.prefix');
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->whereOr([$where, $where_periodic])
                ->order('create_time DESC')
                ->page($page, $pageSize)
                ->select();
            if (!empty($arr)) {
                return $arr->toArray();
            } else {
                return [];
            }
        } else {
            $prefix = config('database.connections.mysql.prefix');
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->whereOr([$where, $where_periodic])
                ->order('create_time DESC')
                ->select();
            if (!empty($arr)) {
                return $arr->toArray();
            } else {
                return [];
            }
        }

    }


    /**
     * 条件查询
     * @param $field
     * @param $where
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getSearchDown($field, $where, $where_periodic, $page = '', $pageSize = '')
    {
        if (!empty($page) && !empty($pageSize)) {
            $prefix = config('database.connections.mysql.prefix');
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'mall_order_detail md', 'md.order_id = o.order_id')
                ->whereOr([$where, $where_periodic])
                ->order('o.order_id DESC')
                ->page($page, $pageSize)
                ->select();
            if (!empty($arr)) {
                return $arr->toArray();
            } else {
                return [];
            }
        } else {
            $prefix = config('database.connections.mysql.prefix');
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'mall_order_detail md', 'md.order_id = o.order_id')
                ->join($prefix . 'mall_order_refund r', 'r.order_id = o.order_id and r.status = 1','left')
                ->whereOr([$where, $where_periodic])
                ->order('o.order_id DESC')
                ->group('md.id')
                ->select();
            if (!empty($arr)) {
                return $arr->toArray();
            } else {
                return [];
            }
        }
    }

    public function getMerchantSum($where, $where_periodic,$typeWhere)
    {
        $where = array_merge($where,$typeWhere);
        $where_periodic = array_merge($where,$where_periodic);
        $prefix = config('database.connections.mysql.prefix');
        $sum = $this->alias('o')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
            ->join($prefix . 'deliver_supply d', 'o.order_id = d.order_id and d.item = 4','left')
            ->join($prefix . 'merchant_money_list mml', 'o.order_no = mml.order_id')
            ->whereOr([$where, $where_periodic])
            ->sum('mml.money');
        return $sum;

    }

    public function getOrderCount($where, $where_periodic)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('o')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->whereOr([$where, $where_periodic])
            ->count();
        return $count;
    }

    /**
     * 获取订单各种信息
     * @param $where
     * @return mixed
     */
    public function getList($field, $where, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
        if (!empty($page) && !empty($pageSize)) {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->where($where)
                ->order('create_time DESC')
                ->page($page, $pageSize)
                ->select();
        } else {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->where($where)
                ->order('create_time DESC')
                ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    /**
     * 获取订单各种信息
     * @param $where
     * @return mixed
     */

    public function getOrderList($field, $where, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->join($prefix . 'user_adress adr', 'o.address_id = adr.adress_id')
            ->where($where)
            ->order('create_time DESC')
            ->paginate($pageSize);
            if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getListInfo($field, $where, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
        if (!empty($page) && !empty($pageSize)) {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'user u', 'o.uid = u.uid')
                ->where($where)
                ->order('create_time DESC')
                ->page($page, $pageSize)
                ->select();
        } else {
            $arr = $this->alias('o')
                ->field($field)
                ->join($prefix . 'merchant m', 'o.mer_id = m.mer_id')
                ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
                ->join($prefix . 'user u', 'o.uid = u.uid')
                ->where($where)
                ->order('create_time DESC')
                ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 获取订单各种信息
     * @param $where
     * @return mixed
     */
    public function getLimitedOrder($field, $where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->where($where)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 订单详情
     * @param $order_id
     * @param $field
     * @return mixed
     */
    public function getDetail($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->join($prefix . 'user u', 'o.uid = u.uid')
            ->where($where)
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 返回和
     * @param $col
     * @return float
     */
    public function getSum($where, $col)
    {
        $prefix = config('database.connections.mysql.prefix');
        $sum = $this->alias('o')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->where($where)
            ->sum($col);
        return $sum;
    }
    /**
     * 获取计算数值
     * @param $col
     * @return float
     */
    public function getMoneySum($where, $col)
    {
        $prefix = config('database.connections.mysql.prefix');
        $sum = $this->alias('o')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->where($where)
            ->value($col);
        return $sum;
    }

    /**
     * 返回总数
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('o')
            ->join($prefix . 'merchant_store s', 'o.store_id = s.store_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 获取各种优惠
     * @param $where
     * @param $field
     * @return array
     */
    public function getDiscount($where, $field)
    {
        $return = $this->field($field)->where($where)->find();
        if (!empty($return)) {
            $return = $return->toArray();
        }
        return $return;
    }

    /**
     * 获取周期购信息
     * @param $pfield
     * @param $order_id
     * @return mixed
     */
    public function getPeriodic($pfield, $order_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($pfield)
            ->join($prefix . 'mall_new_periodic_purchase_order p', 'o.order_id = p.order_id')
            ->where(['p.order_id' => $order_id])
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 更新店员设置的价格
     * @param $where
     * @param $data
     * @return MallOrder
     */
    public function updateClerkMoney($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 获取状态
     * @param $where
     * @return mixed
     */
    public function getStatus($where)
    {
        $orderStatus = $this->field('status,count(status)')->where($where)->group('status')->select()->toarray();
        return $orderStatus;
    }

    /**
     * 根据订单状态获取订单
     * @param $field
     * @param $where
     * @return mixed
     */
    public function getOrderByStatus($field, $where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $orderStatus = $this->alias('o')
            ->field($field)
            ->join($prefix . 'merchant_store s', 's.store_id = o.store_id')
            ->where($where)
            ->select();
        if (!empty($orderStatus)) {
            return $orderStatus->toArray();
        } else {
            return [];
        }
    }

    /**
     * 更新订单信息
     * @param $where
     * @param $data
     * @return MallOrder
     */
    public function updateThis($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 获取今日销量
     */
    public function getTodaySaleNum($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $number = $this->alias('o')
            ->join($prefix . 'mall_order_detail d', 'd.order_id=o.order_id')
            ->field('d.num')
            ->where($where)
            ->select();
        $sales = 0;
        if($number){
            $number = $number->toArray();
            foreach ($number as $key => $value) {
                $sales += $value['num'];  
            }
        }
        return $sales;
    }

    /**
     *根据条件获取
     */
    public function getSome($field, $where, $order = true, $page = 0, $limit = 0)
    {
        $arr = $this->field($field)->where($where);
        if ($order) {
            $arr->order($order);
        }
        if ($limit) {
            $arr->limit($page, $limit);
        }
        $result = $arr->select();
        if ($result) {
            $result = $result->toArray();
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     *根据条件获取数量
     */
    public function getSomeCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getOrder($where, $field, $order)
    {
        $result = $this->field($field)->where($where)->order($order)->find();
        if ($result) {
            return $result->toArray();
        }
        return [];
    }

    /**
     * 获取订单及购买者信息
     * User: zhanghan
     * Date: 2022/1/18
     * Time: 15:19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getOrderUserInfoList($where,$field = true,$page = 0,$limit = 10){
        $result = $this->alias('o')
            ->join('pigcms_user u','o.uid=u.uid')
            ->field($field)
            ->where($where);
        if($page){
            $list = $result->order('order_id DESC')
                ->limit($page, $limit)
                ->select();
        }else{
            $list = $result->order('order_id DESC')
                ->select();
        }

        if ($list && !$list->isEmpty()) {
            return $list->toArray();
        }
        return [];
    }
    
    public function getInfo($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('o')
            ->field($field)
            ->join($prefix .'mall_order_refund r', 'r.order_id=o.order_id')
            ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
            ->join($prefix .'merchant_store s', 's.store_id=o.store_id')
            ->where($where)
            ->order('r.create_time desc')
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}