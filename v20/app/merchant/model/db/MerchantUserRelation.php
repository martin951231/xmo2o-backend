<?php
/**
 * 商家和用户关系表model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/11 18:03
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantUserRelation extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取一条数据
     * @param $where
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where) {
        if(empty($where)) {
             return false;
        }

        $result = $this->where($where)->find();
        return $result;
    }
}