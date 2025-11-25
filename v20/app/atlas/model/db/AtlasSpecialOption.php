<?php
/**
 * 图文管理分类标签选项值列表model
 * Author: wangchen
 * Date Time: 2021/5/28
 */

namespace app\atlas\model\db;
use think\Model;
class AtlasSpecialOption extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得分类标签选项值列表
     * @param $where array 条件
     * @return array
     */
    public function getAtlasSpecialOptionList($special_id){
        $result = $this->where(['special_id'=>$special_id])->select()->toArray();
        return $result;
    }
}