<?php
/**
 * 餐饮订单日志model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:30
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderLog extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取日志列表
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getLogListByCondition($where) {
        if(!$where){
            return null;
        }
        $result = $this->where($where)->select();
        return $result;
    }
}