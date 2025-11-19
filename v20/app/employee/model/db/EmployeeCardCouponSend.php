<?php


namespace app\employee\model\db;


use think\Model;

class EmployeeCardCouponSend extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';

    public function coupon()
    {
        return $this->belongsTo(EmployeeCardCoupon::class, 'coupon_id', 'pigcms_id');
    }

    public function user()
    {
        return $this->belongsTo(EmployeeCardUser::class, 'user_id', 'user_id');
    }

    public function getCoupon($params)
    {
        $time = date('H:i:s');
        $condition = [];
        $condition[] = ['cs.uid', '=', $params['uid']];
        $condition[] = ['cs.card_id', '=', $params['card_id']];
        $condition[] = ['cs.status', '=', 0];
        $condition[] = ['cs.send_num', '>', 0];
        $condition[] = ['cs.add_time', 'between', [strtotime(date('Y-m-d').' 00:00:00'), strtotime(date('Y-m-d').' 23:59:59')]];
        $condition[] = ['c.status', '=', 1];
        $condition[] = ['c.start_time', '<', $time];
        $condition[] = ['c.end_time', '>', $time];
    
        $prefix = config('database.connections.mysql.prefix');
    
       return $this->alias('cs')
            ->field('cs.*,c.*,cs.pigcms_id as coupon_send_id,c.pigcms_id as coupon_id')
            ->join($prefix . 'employee_card_coupon c', 'cs.coupon_id = c.pigcms_id')
            ->where($condition) 
            ->find(); 
    }
    

    public function getCouponMun($params)
    {
        $time = date('H:i:s');
        $condition = [];
        $condition[] = ['cs.uid', '=', $params['uid']];
        $condition[] = ['cs.card_id', '=', $params['card_id']];
        $condition[] = ['cs.status', '=', 0];
        $condition[] = ['cs.send_num', '>', 0];
        $condition[] = ['cs.add_time', 'between', [strtotime(date('Y-m-d').' 00:00:00'), strtotime(date('Y-m-d').' 23:59:59')]];
        $condition[] = ['c.status', '=', 1];
        $condition[] = ['c.start_time', '<', $time];
        $condition[] = ['c.end_time', '>', $time];
        $prefix = config('database.connections.mysql.prefix');
       
        $num = $this->alias('cs')
            ->field('SUM(cs.send_num) as sum')
            ->join($prefix . 'employee_card_coupon c', 'cs.coupon_id = c.pigcms_id')
            ->where($condition) 
            ->find(); 
        return $num['sum'] ?: 0;
    }

    public function getCouponMunNew($params)
    {
        $time = date('H:i:s');
        $condition = [];
        $condition[] = ['cs.uid', '=', $params['uid']];
        $condition[] = ['cs.card_id', '=', $params['card_id']];
        $condition[] = ['cs.status', '=', 0];
        $condition[] = ['cs.send_num', '>', 0];
        $condition[] = ['cs.add_time', 'between', [strtotime(date('Y-m-d').' 00:00:00'), strtotime(date('Y-m-d').' 23:59:59')]];
        $condition[] = ['cs.status', '=', 0];
        $condition[] = ['c.status', '=', 1];
        $condition[] = ['c.end_time', '>', $time];
        $prefix = config('database.connections.mysql.prefix');
       
        $num = $this->alias('cs')
            ->field('SUM(cs.send_num) as sum')
            ->join($prefix . 'employee_card_coupon c', 'cs.coupon_id = c.pigcms_id')
            ->where($condition) 
            ->find(); 
        return $num['sum'] ?: 0;
    }
}