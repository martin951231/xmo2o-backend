<?php
/**
 * 用户访问到期时间表
 * Author: hengtingmei
 * Date Time: 2020/10/22
 */

namespace app\common\model\db;
use think\Model;
class AccessTokenWxappExpires extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    
    /**
     * 根据id更新数据
     * @param $id
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateById($id,$data) {
        if(!$id || $data){
            return false;
        }

        $where = [
            'id' => $id
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }
}