<?php
/**
 * 商家收入model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/12 09:51
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantMoneyList extends Model {


    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOne($where) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * @param $where
     * @return float
     * 根据条件获取和
     */
    public function getSum($where)
    {
        return $this->where($where)->sum('money');
    }

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

        $where = array_filter($where, function ($item){
            if($item[0] == 'income'){
                return false;
            }
            return true;
        });
        $incomePrice = $this->where($where)
            ->where(['income' => 1 ])
            ->field('sum('.$field.') as total')
            ->find();
        
        $outPrince = $this->where($where)
            ->where(['income' => 2 ])
            ->field('sum('.$field.') as total')
            ->find();
        $price['total'] = bcsub($incomePrice['total'], $outPrince['total'], 2);
        
        return $price;
    }
}