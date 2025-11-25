<?php
/**
 * 商家积分model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/12 10:51
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantScoreList extends Model {
	

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
        return $result;
    }
}