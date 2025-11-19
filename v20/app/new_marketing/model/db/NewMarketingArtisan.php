<?php
/**
 * 汪晨
 * 2021/08/17
 * 技术人员
 */

namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingArtisan extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 技术人员列表
    public function getMarketingArtisanList($where, $field, $order, $page, $pageSize)
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->field($field)
            // ->join($prefix . 'new_marketing_team_artisan ms', 'ms.store_id=s.store_id')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        return $result->toArray();
    }
    /**
     * Notes: 对应字段求和
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getSum($where,$field){
        $sum = $this->where($where)->sum($field);
        return $sum;
    }
    // 技术人员数量
    public function getMarketingArtisanCount($where, $field)
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->field($field)
            // ->join($prefix . 'new_marketing_team_artisan ms', 'ms.store_id=s.store_id')
            ->where($where)
            ->count();
        return $result;
    }

    //根据条件查出指定字段的列表
    public function getListByWhere($where, $field = '*')
    {
        $list = $this->where($where)->field($field)->select();
        return $list;
    }

    /**
     * @param array $where
     * @param bool $field
     * @param array $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询人员信息
     */
    public function getPersonMsg($where = [], $field = true, $order = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->join($prefix . 'user u', 'u.uid = p.uid')
            ->field($field)->where($where)->order($order)->find();
        if (empty($result)) {
            return [];
        } else {
            return $result->toArray();
        }

    }

    /**
     * @param array $where
     * @param bool $field
     * @param array $order
     * @return mixed
     * 技术员关联用户列表
     */
    public function getPersonMsgList($where = [], $field = true, $order = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->join($prefix . 'user u', 'u.uid = p.uid')
            ->field($field)->where($where)->order($order)->select()->toArray();
        return $result;

    }



    // 技术人员列表
    public function getMarketingTeamArtisanList($where, $field=true, $order=[])
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->field($field)
            ->join($prefix . 'user u', 'u.uid=g.uid')
             ->join($prefix . 'new_marketing_team_artisan ms', 'ms.artisan_id=g.id')
            ->where($where)
            ->order($order)
            ->select();
        return $result->toArray();
    }

    // 技术人员所属团队列表
    public function getMarketingArtisanTeamList($where, $field=true)
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->field($field)
            ->join($prefix . 'new_marketing_order_type_artisan or', 'or.artisan_id=s.id')
            ->join($prefix . 'new_marketing_team_artisan ms', 'ms.artisan_id=s.id')
            ->join($prefix . 'new_marketing_team mt', 'mt.id=ms.team_id')
            ->where($where)
            ->group('ms.team_id')
            ->select();
        return $result->toArray();
    }

   // 技术人员所属团队列表,不包含订单
    public function getMarketingArtisanTeamNoOrderList($where, $field=true)
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->field($field)
            ->join($prefix . 'new_marketing_team_artisan ms', 'ms.artisan_id=s.id')
            ->join($prefix . 'new_marketing_team mt', 'mt.id=ms.team_id')
            ->where($where)
            ->group('ms.team_id')
            ->select();
        return $result->toArray();
    }

    // 技术人员订单列表
    public function getMarketingArtisanOrderList($where, $field=true, $order=[])
    {
        if (!$where) {
            return [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->field($field)
            ->join($prefix . 'new_marketing_order_type_artisan ot', 'ot.artisan_id=g.id')
            ->join($prefix . 'new_marketing_order_type t', 't.id=ot.type_id')
            ->where($where)
            ->order($order)
            ->select()
            ->toArray();
        return $result;
    }

    /**
     * Notes: 获取字段值
     * @param string|\think\model\concern\string $where
     * @param mixed $value
     * @return mixed
     */
    public function getValues($where,$value)
    {
        $data = $this->where($where)->value($value);
        return $data;
    }
}