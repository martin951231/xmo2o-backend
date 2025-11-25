<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsTicketSku extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return int|mixed
     * 最高价格
     */
    public function getMaxPice($where,$field){
        $arr = $this->where($where)->max($field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return 0;
        }
    }


    /**
     * @param $where
     * @return int|mixed
     * 最低价格
     */
    public function getMinPice($where,$field){
        $arr = $this->where($where)->min($field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return 0;
        }
    }

    /**
     * @param $where
     * @return int|mixed
     * 获取总和
     */
    public function getSum($where,$field){
        $arr = $this->where($where)->sum($field);
        return $arr;
    }

    /**
     * @param $where
     * @return int|mixed
     * 获取某值
     */
    public function getVal($where,$field){
        $arr = $this->where($where)->value($field);
        return $arr;
    }
}