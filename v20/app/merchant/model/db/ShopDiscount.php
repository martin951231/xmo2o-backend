<?php
/**
 * 店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/16 10:48
 */

namespace app\merchant\model\db;
use think\Model;
class ShopDiscount extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function getDel($where){
        $result = $this->where($where)->delete();
        return $result;
    }
}