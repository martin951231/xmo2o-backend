<?php
/**
 * 种草文章绑定商品表model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:07
 */

namespace app\grow_grass\model\db;
use think\Model;
class GrowGrassBindGoods extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function getArticleGroup($id)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $field = 'a.goods_id,a.goods_type, b.name as group_name,b.*';
        $arr = $this->alias('a')
            ->join($prefix . 'group b', 'a.goods_id=b.group_id')
            ->field($field)
            ->where(array('a.article_id'=>$id))
            ->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }else{
            $arr = [];
        }
        return $arr;
    }
}