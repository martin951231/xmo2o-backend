<?php
/**
 * 店铺线下支付方式model
 * Author: hengtingmei
 * Date Time: 2020/09/02 14:17
 */

namespace app\merchant\model\db;
use think\Model;
class StorePay extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function getOneVal($where,$field){
      return $this->where($where)->column($field);
    }

    public function delData($where){
        return $this->where($where)->delete();
    }
}