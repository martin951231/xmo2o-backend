<?php
/**
 * 用户充值平台余额
 */

namespace app\common\model\db;

use think\Model;
class UserRechargeOrder extends Model{
	use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where,$field){
       if(empty($where) || empty($field)){
           return false;
       }

        $res = $this->where($where)
                    ->field('sum('.$field.') as total')
                    ->find();
        return $res;
    }
}