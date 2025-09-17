<?php
/**
 * 餐饮桌台分类model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 11:27
 */

namespace app\foodshop\model\db;
use think\Model;
class FoodshopTableType extends Model { 


    /**
     * 根据id获取桌台类型信息
     * @param $id int 桌台id
     * @return array|bool|Model|null
     */
    public function geTableTypeById($id) {
        if(!$id){
            return null;
        }

        $where = [
            'id' => $id
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }

    
    
   /**
     * 获得桌台分类列表
     * @param $where array 条件
     * @return array
     */
    public function getTableTypeListByCondition($where, $order) {
        if(!$where){
            return null;
        }

        $this->name = _view($this->name);
        $result = $this->where($where)->order($order)->select();
        return $result;
    }


    /**
     * 获得桌台分类
     * @param $where array 条件
     * @return array
     */
    public function getTableTypeByCondition($where) {
        if(!$where){
            return null;
        }

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }


    
}