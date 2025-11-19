<?php
/**
 * 店员客服model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/09 13:26
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantStoreKefu extends Model {
    
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据店员返回绑定的客服
     * @param $where array 条件
     * @return array
     */
    public function getKefuByStaff($where, $field=true){

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('kf')
            ->join($prefix.'user u','kf.bind_uid = u.uid')
            ->field($field)
            ->where($where)
            ->find();
        return $result;
    }
    
}