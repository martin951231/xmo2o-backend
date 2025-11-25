<?php
/**
 * 图文管理分类model
 * Author: wangchen
 * Date Time: 2021/5/24
 */

namespace app\atlas\model\db;
use think\Model;
class AtlasCategory extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得分类列表
     * @param $where array 条件
     * @return array
     */
    public function getAtlasCategoryList($where = [], $order = '', $field = '*'){
        $result = $this->where($where)->field($field)->order($order)->select()->toArray();
        return $result;
    }

    /**
     * 获得一条分类数据
     * @param $where array 条件
     * @return array
     */
    public function getAtlasCategoryInfo($where = [], $field = '*'){
        $result = $this->where($where)->field($field)->find()->toArray();
        return $result;
    }

    /**
     * 图文管理分类修改/添加
     */
    public function getAtlasCategoryCreate($cat_id, $cat_fid, $cat_name, $cat_status){
        if($cat_id > 0){
            // 修改
            $result = $this->where(['cat_id'=>$cat_id])->update(['cat_name'=>$cat_name, 'cat_status'=>$cat_status]);
        }else{
            // 添加
            $result = $this->insert(['cat_fid'=>$cat_fid, 'cat_name'=>$cat_name, 'cat_status'=>$cat_status]);
        }
        return $result;
    }

    /**
     * 图文管理分类删除
     */
    public function getAtlasCategoryDel($cat_id){
        // 修改
        $result = $this->where(['cat_id'=>$cat_id])->delete();
        return $result;
    }

    /**
     * 获得分类标签列表
     * @param $where array 条件
     * @return array
     */
    public function atlasCategoryList($where = [], $order = '', $field = '*'){
        $field = 'cat_id,cat_name,cat_pic';
        $result = $this->where($where)->field($field)->order($order)->find();
        return $result;
    }
}