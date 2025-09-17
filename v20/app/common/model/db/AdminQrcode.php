<?php
/**
 * 系统后台使用微信登录生成的临时二维码
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/18 11:19
 */

namespace app\common\model\db;
use think\Model;
class AdminQrcode extends Model {

    /**
     * 根据条件删除数据
     * @param $where
     */
    public function del($where) {
        if(empty($where)) {
            return false;
        }

        $result = $this->where($where)->delete();
        return $result;
    }
        
    /**
     * 根据id更新数据
     * @param $id
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateById($id,$data) {
        if(!$id || !$data){
            return false;
        }

        $where = [
            'id' => $id
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 根据id获取数据
     * @param $id
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdminQrcodeById($id) {
        if(empty($id)) {
             return false;
         }
 
         $where = [
             "id" => trim($id),
         ];
 
         $result = $this->where($where)->find();
         return $result;
     }

}