<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeCardUser extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
 
    
    public function card()
    {
        return $this->belongsTo(EmployeeCard::class, 'card_id', 'card_id');
    }

    public function user()
    {
        return $this->belongsTo(\app\common\model\db\User::class, 'uid', 'uid');
    }

    public function merchant()
    {
        return $this->belongsTo(\app\common\model\db\merchant::class, 'mer_id', 'mer_id');
    }

    public function couponSend()
    {
        return $this->hasMany(EmployeeCardCouponSend::class, 'user_id', 'user_id');
 
    }
    /**
     * 会员卡用户列表
     */
    public function getUserCardList($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        $prefix = config('database.connections.mysql.prefix');
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->join($prefix.'employee_card c','c.card_id = g.card_id')
                ->join($prefix.'user u','u.uid = g.uid');
            $ret['total']=$result->count();

            $ret['list']=$result->order($order)
                ->group('g.uid')
                ->select()
                ->toArray();
            return $ret;
    }

    /**
     * 会员卡用户列表求和
     */
    public function getUserCardListSum($where = [], $field = 'g.card_money')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->join($prefix.'employee_card c','c.card_id = g.card_id')
            ->join($prefix.'user u','u.uid = g.uid')
            ->sum($field);
        return $result;
    }

    /**
     * 员工卡编辑
     */
    public function editCardUser($where = [], $field = true, $order = []) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->join($prefix.'user u','u.uid = g.uid')
            ->order($order)->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 删除
     */
    public function delData($where)
    {
        $ret=$this->where($where)->delete();
        return $ret;
    }

    /**
     * 获取字段值
     */
    public function getColumn($where,$field)
    {
        $ret=$this->where($where)->column($field);
        return $ret;
    }
    /**
     * 获取员工信息
     */
    public function getUser($params)
    {
        $condition = [];
        $condition[] = ['status', '=', 1];
        $condition[] = ['uid', '=', $params['uid']];
        if(isset($params['card_id'])){
            $condition[] = ['card_id', '=', $params['card_id']];
        }
        if(isset($params['mer_id'])){
            $condition[] = ['mer_id', '=', $params['mer_id']];
        }
        return $this->where($condition)->find();
    }
}