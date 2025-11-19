<?php
/**
 * 图文管理文章分类标签选项值列表model
 * Author: wangchen
 * Date Time: 2021/5/29
 */

namespace app\atlas\model\db;
use think\Model;
class AtlasArticleOption extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得文章分类标签选项值列表
     * @param $where array 条件
     * @return array
     */
    public function atlasArticleOptionList($id){
        $where = [['a.article_id','=',$id]];
        $field = 'b.id,b.special_id,b.name';
        $result = $this->alias('a')
            ->where($where)
            ->field($field)
            ->join('atlas_special_option b', 'b.id = a.option_id');
        $assign = $result->select()
            ->toArray();
        return $assign;
    }

    /**
     * 获得文章分类标签选项值写入
     * @param $where array 条件
     * @return array
     */
    public function getAtlasArticleOptionCreate($id,$owner){
        
        $where = [['article_id','=',$id]];
        $this->where($where)->delete();
        foreach($owner as $v){
            if(is_array($v['owner'])){
                $own = [];
                foreach($v['owner'] as $ks=>$vs){
                    $own[$ks]['option_id'] = $vs;
                    $own[$ks]['article_id'] = $id;
                }
                $this->insertAll($own);
            }else{
                $this->insert(['article_id'=>$id,'option_id'=>$v['owner']]);
            }
        }
        return true;
    }
}