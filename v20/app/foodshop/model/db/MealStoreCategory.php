<?php
/**
 * 系统后台餐饮分类model
 * Created by vscode.
 * Author: henhtingmei
 * Date Time: 2020/5/16 10:48
 */

namespace app\foodshop\model\db;
use think\Model;
class MealStoreCategory extends Model {

    /**
     * 根据分类父id获取分类列表
     * @param $catFid
     * @return array|bool|Model|null
     */
    public function getCategoryByCatFid($catFid = 0) {
        if(!$catFid){
            return null;
        }

        $where = [
            'cat_fid' => $catFid
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->select();
        return $result;
    }

    
    /**
     * 根据分类id获取分类
     * @param $catId
     * @return array|bool|Model|null
     */
    public function getCategoryByCatId($catId) {
        if(!$catId){
            return null;
        }

        $where = [
            'cat_id' => $catId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }
    
    /**
     * 根据条件返回分类列表
     * @param $where
     * @return array|bool|Model|null
     */
    public function getCategoryByCondition($where, $order) {
        $this->name = _view($this->name);
        $result = $this->where($where)->order($order)->select();
        return $result;
    }

    
}