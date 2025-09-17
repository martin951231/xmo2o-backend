<?php
/**
 * 三级分销业务员表
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsDistributionOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'id';

    /**
     *获取指定商户的订单
     */
    public function getListByMerchant($where,$field,$page=0,$pageSize=0)
    {
        $result = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->where($where)
            ->whereIn('b.order_status',[30,40,45,70])
            ->field($field);
        if($page){
            $limit = [
                'page' => $page,
                'list_rows' => $pageSize ?? 10
            ];
            $data['total'] = $result->count();
            $data['list'] = $result->paginate($limit);
        }else{
            $data = $result->select();
        }

        return $data;
    }

    /**
     *获取指定分销员的订单
     */
    public function getListByDistributor($where,$field,$page=0,$pageSize=0,$get_all = 0)
    {
        $result = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->join('life_tools c','b.tools_id = c.tools_id')
            ->where($where)
            ->whereIn('b.order_status',[30,40,45,70])
            ->field($field)
            ->order('b.add_time desc');
        if($page && !$get_all){
            $limit = [
                'page' => $page,
                'list_rows' => $pageSize ?? 10
            ];
            $data = $result->paginate($limit);
        }else{
            $data = $result->select();
        }

        return $data;
    }

    /**
     * 获取数组中订单的总金额
     */
    public function getAllOrder($where)
    {
        $price = $this->where($where)
            ->field(['id','commission_level_1','commission_level_2'])
            ->select();
        return $price;
    }
    
    /**
     * 关联LifeToolsOrder模型
     */
    public function order()
    {
        return $this->belongsTo(LifeToolsOrder::class, 'order_id', 'order_id');
    }

    public function getStatusTextAttr($value, $data)
    {
        $statusMap = ['待结算', '结算中', '已结算'];
        return $statusMap[$data['status']] ?? '';
    }

    /**
     * 查询结算单中的订单
     */
    public function getList($where,$field,$pageSize)
    {
        $data = $this->alias('a')
            ->join('life_tools_order b','a.order_id = b.order_id')
            ->join('life_tools c','b.tools_id = c.tools_id')
            ->where($where)
            ->field($field)
            ->order('a.order_id desc');
        if($pageSize){
            $data = $data->paginate($pageSize)->toArray();
        }else{
            $data = $data->select();
        }
        return $data;
    }

    
    /**
     * 获取分销员佣金
     */
    public function getCommission($user_id, $mer_id = 0, $level = 1)
    {
        $condition = [];
        if(!empty($mer_id)){
            $condition[] = ['mer_id', '=', $mer_id];
        }
        $condition[] = ['user_id', '=', $user_id];
        $condition[] = ['status', 'in', [-2, 0, 1, 2]];
        $commission_level = $level == 1 ? 'commission_level_1' : 'commission_level_2';
        return $this->where($condition)->sum($commission_level);
    }

    /**
     * 查询订单
     */
    public function getOrderList($where, $field)
    {
        $data = $this->alias('a')
            ->field($field) 
            ->join('life_tools_order o','a.order_id = o.order_id')
            ->join('life_tools_distribution_setting s','s.mer_id = o.mer_id')
            ->where($where)
            ->select();
        
        return $data;
    }

    /**
     * 分销中心结算列表
     */
    public function distributionSettlementList($user_id)
    {
        $field = [
            'o.*',
            'user.is_cert',
            'u.nickname',
            'u.avatar',
            'CASE o.status WHEN 2 THEN COUNT(o.order_id) ELSE 0 END AS order_num', //订单数量
            'SUM(CASE o.status WHEN 2 THEN o.commission_level_1 ELSE 0 END) AS order_price', //订单金额
        ];
        
        $condition = [];
        $condition[] = ['o.commission_type', '=', 1];
        $condition[] = ['o.user_id', '=', $user_id];
        $condition[] = ['o.is_cert', '=', 0];
        $result = $this->alias('o')
                    ->field($field)
                    ->join('life_tools_distribution_user user', 'o.from_user_id = user.user_id')            
                    ->join('user u', 'u.uid = user.uid')            
                    ->where($condition)
                    ->where(function($query) use($user_id){
                        $query->whereOr(function($query) use($user_id){
                            $condition = [];
                            $condition[] = ['user.is_cert', '=', 1];
                            $condition[] = ['user.pid', '<>', $user_id];
                            $query->where($condition);
                        })->whereOr('user.is_cert', 0);
                    })
                    ->group('o.from_user_id')
                    ->paginate(10)
                    ->toArray();
        return $result;
    }

    /**
     * 下级推广数据
     */
    public function lowerLevelData($user_id, $t=0, $pid=0)
    {
        switch($t){
            case 0: //下级邀请奖励
                $condition = [];
                $condition[] = ['user_id', '=', $user_id];
                $condition[] = ['pid', '=', $pid];
                $condition[] = ['commission_type', '=', 2];
                $condition[] = ['status', '=', 2];
                return $this->where($condition)->sum('commission_level_2');
                break;
            case 1: //下级订单数
                $condition = [];
                $condition[] = ['user_id', '=', $user_id];
                $condition[] = ['pid', '=', $pid];
                $condition[] = ['commission_type', '=', 2];
                $condition[] = ['status', '=', 2];
                return $this->where($condition)->count();
                break;
            case 2: //邀请游客数: 下单时时游客身份并且当前也是游客身份 || 下单时是游客身份现在是其他人的分销员
                $condition = [];
                $condition[] = ['o.user_id', '=', $user_id];
                $condition[] = ['o.commission_type', '=', 1];
                $condition[] = ['o.is_cert', '=', 0];
                return $this->alias('o')->join('life_tools_distribution_user u', 'o.from_user_id = u.user_id')->where($condition)->where(function($query) use($user_id){
                    $query->whereOr(function($query) use($user_id){
                        $condition = [];
                        $condition[] = ['u.is_cert', '=', 1];
                        $condition[] = ['u.pid', '<>', $user_id];
                        $query->where($condition);
                    })->whereOr('u.is_cert', 0);
                })->group('o.from_user_id')->count();
                break;
            case 3: //下一级订单数
                $condition = [];
                $condition[] = ['user_id', '=', $user_id];
                $condition[] = ['commission_type', '=', 1];
                $condition[] = ['status', '=', 2];
                return $this->where($condition)->count();
                break;
        }
        
    }
}