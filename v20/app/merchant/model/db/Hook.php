<?php
/**
 * 系统钩子（系统自有hook_id设置为1万以下，客户定制设置为1万以上）
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/16 15:08
 */

namespace app\merchant\model\db;
use think\Model;
class Hook extends Model {
    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOne($where) {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->find();
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