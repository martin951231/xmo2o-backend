<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsAppointJoinOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $json = ['custom_form'];
    protected $jsonAssoc = true;

    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     * 我的列表
     */
    public function myCompetList($where = [], $field = true,$order=true,$page=1,$pageSize=10){
        $prefix = config('database.connections.mysql.prefix');
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this->alias('j')
            ->field($field)
            ->join($prefix . 'life_tools_appoint c', 'c.appoint_id = j.appoint_id')
            ->where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $arr;
    }
    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     * 我的订单详情
     */
    public function orderDetail($where = [], $field = true){
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('j')
            ->field($field)
            ->join($prefix . 'life_tools_appoint c', 'c.appoint_id = j.appoint_id')
            ->join($prefix . 'user u', 'u.uid = j.uid')
            ->where($where)
            ->find();
        if(empty($arr)){
            $arr=[];
        }else{
            $arr=$arr->toArray();
        }
        return $arr;
    }

    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     * 我的订单详情不关联
     */
    public function getOneDetail($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 获取列表
     */
    public function getOrderList($where, $field='o.*,o.pigcms_id as order_id', $page_size = 0)
    {
        $prefix = config('database.connections.mysql.prefix');
        if($page_size){
            $data = $this->alias('o')
                ->join($prefix . 'life_tools_appoint a', 'o.appoint_id = a.appoint_id')
                ->join($prefix . 'user u', 'o.uid = u.uid')
                ->field($field)
                ->where($where)
                ->order('o.add_time DESC')
                ->paginate($page_size)
                ->toArray();
        }else{
            $data = $this->alias('o')
                ->join($prefix . 'life_tools_appoint a', 'o.appoint_id = a.appoint_id')
                ->field($field)
                ->where($where)
                ->select();
        }
        return $data;
    }

    public function getCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('o')
        ->join($prefix . 'life_tools_appoint a', 'o.appoint_id = a.appoint_id')
        ->join($prefix . 'user u', 'o.uid = u.uid')
        ->where($where)
        ->count();
        return $data;
    }
    

    public function appoint()
    {
        return $this->belongsTo(LifeToolsAppoint::class, 'appoint_id', 'appoint_id');
    }

    public function orderList($where, $field='o.*,o.pigcms_id as order_id')
    {
        $prefix = config('database.connections.mysql.prefix');
        $where[] = ['so.type', '=', 'life_tools_appoint_join'];
        $data = $this->alias('o')
        ->join($prefix . 'life_tools_appoint a', 'o.appoint_id = a.appoint_id')
        ->join($prefix . 'system_order so', 'so.order_id = o.pigcms_id')
        ->field($field)
        ->where($where)
        ->order('o.add_time DESC')
        ->select()
        ->toArray();
        return $data;
    }
}