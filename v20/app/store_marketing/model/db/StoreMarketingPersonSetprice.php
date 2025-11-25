<?php
/**
 * liuruofei
 * 2021/09/13
 * 分销员改价
 */
namespace app\store_marketing\model\db;

use think\Model;

class StoreMarketingPersonSetprice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 删除数据
     */
    public function delData($where){
        $result = $this->where($where)->delete();
        return $result;
    }
}