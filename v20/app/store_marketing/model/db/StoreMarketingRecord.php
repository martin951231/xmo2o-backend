<?php
/**
 * liuruofei
 * 2021/09/13
 * 分销员改价
 */
namespace app\store_marketing\model\db;

use think\Model;

class StoreMarketingRecord extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 统计
     * @return float
     */
    public function getSum($where = [], $field='total_price'){
        $count =  $this->where($where)->sum($field);
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * 与用户关联
     */
    public function getSome($where = [], $field = true,$order=true,$page,$pageSize=10){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix.'user u','u.uid = s.uid')
            ->join($prefix.'store_marketing_person sp','sp.id = s.person_id')
            ->where($where);
        $list['total']=$result->count();
        if($page){
            $list['list']=$result
                ->field($field)
                ->order($order)
                ->page($page,$pageSize)
                ->select()
                ->toArray();
        }else{
            $list['list']=$result
                ->field($field)
                ->order($order)
                ->select()
                ->toArray();
        }
        return $list;
    }
}