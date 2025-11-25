<?php


namespace app\merchant\model\db;

use think\Model;

/**
 * Class MerchantStoreSlider
 * @package app\merchant\model\db
 * 店铺导航
 */
class MerchantStoreSlider extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * 删除
     */
    public function del($where){
        return $this->where($where)->delete();
    }
}