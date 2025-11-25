<?php
/**
 * 用户访问到期时间表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/18 10:47
 */

namespace app\common\model\db;
use think\Model;
class AccessTokenExpires extends Model {

    /**
     * 获得一条数据
     * @param $account
     * @return array|bool|Model|null
     */
    public function getOne() {
        $result = $this->order(['id'=>'ASC'])->find();
        return $result;
    }

    
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

    /**
     * 添加数据
     * @param $data
     */
    public function add($data) {
        if(empty($data)) {
            return false;
        }
        $result = $this->save($data);
        if ($result) {
            return $result;
        }
        return false;
    }


}