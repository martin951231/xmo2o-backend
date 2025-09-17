<?php
/**
 * 打印机model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/16 15:08
 */

namespace app\merchant\model\db;
use think\Model;
class OrderprintList extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOne($where,$field=true, $order=[]) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->field($field)->order($order)->find();
        return $result;
    }

    /**
     * 获得列表
     * @param $where
     * @param $order
     * @return array|bool|Model|null
     */
    public function getList($where,$order) {
        if(!$where){
            return false;
        }

        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    

    
}