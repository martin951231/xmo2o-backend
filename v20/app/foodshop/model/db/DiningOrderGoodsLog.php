<?php
/**
 * 餐饮订单商品操作日志model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:28
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderGoodsLog extends Model {
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