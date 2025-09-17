<?php
/**
 * 社区管理app 物业支出
 * @author weili
 * @date 2020/10/12
 */

namespace app\community\model\db;

use think\Model;
class PropertyBill extends Model
{
    /**
     * Notes: 计算支出金额
     * @param $where
     * @param array $whereAnd
     * @param string $field
     * @return float
     * @author: weili
     * @datetime: 2020/10/12 15:48
     */
    public function sumMoney($where,$field='money')
    {
        $sumMoney = $this->where($where)->sum($field);
        return $sumMoney;
    }
    public function getSelect($where,$field=true,$group='',$page=0,$limit=7)
    {
        $sql = $this->where($where)->field($field);
        if($group){
            $list = $sql->group($group)->limit($limit)->select();
        }elseif($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
}