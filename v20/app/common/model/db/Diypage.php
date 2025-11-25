<?php
namespace app\common\model\db;

use think\Model;

class Diypage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $source
     * @param $where
     * @param $field
     * @return mixed
     * 获得装修详情
     */
    public function getDiypageDetail($source,$where,$field){
        if($source=="category"){
            $msg=$this->alias('s')->field($field)->join('merchant_category'.' m','s.source_id = m.cat_id')->where($where)->find();
            if(!empty($msg)){
                $msg=$msg->toArray();
            }
            return $msg;
        }
    }
}