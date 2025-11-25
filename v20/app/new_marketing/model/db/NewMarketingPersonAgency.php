<?php


namespace app\new_marketing\model\db;


use think\Model;

class NewMarketingPersonAgency extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * 删除数据
     */
    public function delData($where){
        $ret=$this->where($where)->delete();
        return $ret;
    }
}