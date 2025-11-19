<?php
/**
 * 店铺店员model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/06/11 09:46
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantStoreStaff extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根条件获取店员列表
     * @param $where array  
    * @return array
    */
    public function getStaffListByCondition($where){
        if(empty($where)){
            return false;
        }

        $result = $this->where($where)->select();
        return $result;
    }

    /**
     * 获取一条店员信数据
     * @param $id 店员ID
     * @return array|bool|Model|null
     */
    public function getStaffById($id) {
        if(!$id){
            return null;
        }

        $where = [
            'id' => $id
        ];

        $result = $this->where($where)->find();
        return $result;
    }

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