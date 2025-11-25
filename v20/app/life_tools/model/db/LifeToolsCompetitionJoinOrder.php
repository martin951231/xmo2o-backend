<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsCompetitionJoinOrder extends Model
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
            ->join($prefix . 'life_tools_competition c', 'c.competition_id = j.competition_id')
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
            ->join($prefix . 'life_tools_competition c', 'c.competition_id = j.competition_id')
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

    public function competition()
    {
        return $this->belongsTo(LifeToolsCompetition::class, 'competition_id', 'competition_id');
    }

    public function getNeedPayAttr($value, $data)
    {
        return $data['price'] > 0 ? 1 : 0;
    }
    public function getPayTimeTextAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['pay_time']);
    }
    public function getAuditStatusTextAttr($value, $data)
    {
        //0=待审核，1=审核中，2=审核成功，3=审核失败
        $auditStatusMap = ['待审核', '审核中', '审核成功', '审核失败'];
        return $auditStatusMap[$data['audit_status']] ?? '';
    }

    public function auditList()
    {
        return $this->hasMany(LifeToolsCompetitionAudit::class, 'order_id', 'pigcms_id')->order('sort DESC');
    }
}