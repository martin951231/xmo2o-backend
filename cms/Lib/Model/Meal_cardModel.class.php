<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/6.
 * Time: 10:08.
 */
class Meal_cardModel extends Model
{
    /**
     * @desc 获取正在使用中的饭票模板
     * @param $mer_id
     * @return array
     */
    public function get_use_one($mer_id){
        if(!$mer_id)return array();
        $where = array();
        $where['is_del'] = 0;
        $where['status'] = 1;
        $string  = '(merchant=0 OR FIND_IN_SET('.$mer_id.',`merchant`))';
        $string .= ' AND (effective_day=0 OR effective_time>'.time().')';
        $where['_string'] = $string;
        $data = $this->where($where)->find();
        return $this->deal_one($data);
    }


    private function deal_one($data){
        if($data){
            $data['recharge_money_arr'] = explode(',',$data['recharge_money']);
        }
        return $data;
    }
}