<?php
/**
 * 三级分销业务员表
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsDistributionUser extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'user_id';

    public function getList($merId,$where = [], $field = true,$order=true,$page=0,$pageSize=0)
    {
        $prefix = config('database.connections.mysql.prefix');
        if($page){
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $result = $this->alias('a')
                ->field($field)
                ->join($prefix.'life_tools_distribution_user_bind_merchant b','a.user_id = b.user_id AND b.mer_id = '.$merId)
                ->join($prefix.'user c','a.uid = c.uid')
                ->where($where)
                ->where('b.is_del',0)
                ->order($order);
            $data = $result->paginate($limit);
        }else{
            $data = $this->alias('a')
                ->field($field)
                ->join($prefix.'life_tools_distribution_user_bind_merchant b','a.user_id = b.user_id AND b.mer_id = '.$merId)
                ->join($prefix.'user c','a.uid = c.uid')
                ->where($where)
                ->where('b.is_del',0)
                ->order($order)
                ->select();
        }
        return $data;
    }

    public function getLowerLevel($params,$where,$field,$order,$page=1,$pageSize=10)
    {
        $level = $params['level'] ?? 1;
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize
        ];
        $result = $this->alias('a')
            ->join('life_tools_distribution_user_bind_merchant b','a.user_id = b.user_id');
        if($level == 1){
            $result = $result->join('user e','a.uid = e.uid');
            $result = $result->join('life_tools_distribution_user i','a.pid = i.user_id');
            $result = $result->join('user g','i.uid = g.uid');
        }
        if($level == 2){
            $result = $result->join('life_tools_distribution_user c','a.user_id = c.pid')
                ->join('life_tools_distribution_user_bind_merchant d','c.user_id = d.user_id')
                ->join('user f','c.uid = f.uid')
                ->join('life_tools_distribution_user j','c.pid = j.user_id')
                ->join('user h','j.uid = h.uid');
        }
        $result = $result->where($where)
            ->field($field)
            ->order($order);
        $data = $result->paginate($limit);
        return $data;
    }
    

    /**
     * 关联User模型
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    /**
     * 关联LifeToolsDistributionOrder模型
     */
    public function distributionOrder()
    {
        return $this->hasMany(LifeToolsDistributionOrder::class, 'user_id', 'user_id');
    }

    /**
     * 关联LifeToolsDistributionLog模型
     */
    public function distributionLog()
    {
        return $this->hasMany(LifeToolsDistributionLog::class, 'user_id', 'user_id');
    }

    /**
     * 下级推广数据
     * @param int $user_id 业务员id
     * @param int $t 0 推广订单数， 1 推广金额（下级佣金，） 2下级邀请奖励
     */
    public function getSubData($user_id, $t = 0)
    {   
        $condition = [];
        $condition[] = ['u.is_del', '=', 0];
        $condition[] = ['u.pid', '=', $user_id];
        $condition[] = ['l.status', '=', 2];
        switch($t){
            case 0: //订单数
                $subUser = $this->alias('u')->field('COUNT(*) num')->join('life_tools_distribution_log l', 'l.user_id = u.user_id')->where($condition)->find();
                $return = $subUser['num'] ?: 0;
                break;
            case 1: //佣金
                $subUser = $this->alias('u')->field('sum(l.commission_level_1) num')->join('life_tools_distribution_log l', 'l.user_id = u.user_id')->where($condition)->find();
                $return = $subUser['num'] ?: 0;
                break;
            case 2: //邀请奖励
                $subUser = $this->alias('u')->field('sum(l.commission_level_2) num')->join('life_tools_distribution_log l', 'l.user_id = u.user_id')->where($condition)->find();
                $return = $subUser['num'] ?: 0;
                break;
            case 3: //邀请人数
                $return = $this->where('pid', $user_id)->where('is_cert', 1)->count();
                break;
        }
        
        return $return;
    }

    /**
     * 分销者中心-查看详情
     */
    public function getUserMerchant($where = [], $field = '',$order=true,$page=0,$pageSize=0)
    {
        $prefix = config('database.connections.mysql.prefix');
        if (!$field) {
            $field = "b.mer_id,c.name,c.phone,c.logo,b.commission,b.invit_money,b.rejected_money,b.audit_msg,b.audit_status";
        }
        $query = $this->alias('a')
            ->field($field)
            ->join($prefix . 'life_tools_distribution_user_bind_merchant b', 'a.uid = b.uid')
            ->join($prefix . 'merchant c', 'b.mer_id = c.mer_id')
            ->where($where)
            ->where('b.is_del', 0)
            ->order($order);
        if ($page) {
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $data = $query->paginate($limit);
        } else {
            $data = $query->select();
        }
        return $data;
    }

    /**
     * 分销中心-推广订单列表
     * @param array $where //查询条件
     * @param string $field  //要查询字段
     * @param bool $order //排序
     * @param int $page //当前页数
     * @param int $pageSize
     * @return mixed
     */
    public function getUserOrder($where = [], $field = '',$order=true,$page=0,$pageSize=0,$whereOther=''){
        $prefix = config('database.connections.mysql.prefix');
        if(!$field){
            $field = 'b.order_id,d.order_status,d.num,d.pay_money,e.title,e.cover_image,e.type,f.price,f.title as ticket_title';
        }
        $query = $this->alias('a')
            ->field($field)
            ->join($prefix. 'life_tools_distribution_log b','b.user_id = a.user_id and b.mer_id = b.mer_id')
            ->join($prefix . 'life_tools_order d','b.order_id = d.order_id and b.mer_id = d.mer_id')
            ->join($prefix . 'life_tools e','d.tools_id = e.tools_id and d.mer_id = e.mer_id')
            ->join($prefix . 'life_tools_ticket f','d.ticket_id = f.ticket_id and d.tools_id = f.tools_id and d.mer_id = f.mer_id')
            ->where($where)
            ->where($whereOther)
            ->order($order);
        if ($page) {
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $data = $query->paginate($limit);
        } else {
            $data = $query->select();
        }
        return $data;
    }
}