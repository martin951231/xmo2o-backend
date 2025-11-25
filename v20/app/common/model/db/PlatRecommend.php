<?php
/**
 * 首页推荐
 * User: 衡婷妹
 * Date: 2021/05/25 18:01
 */


namespace app\common\model\db;
use think\Model;
class PlatRecommend extends Model {


    use \app\common\model\db\db_trait\CommonFunc;

    public function getGuessLikeList($where,$field,$page=0,$pageSize=10,$sort='p.store_sort desc'){
        $query = $this->alias('p')->field($field)
            ->join('merchant_store s','p.store_id = s.store_id')
            ->join('merchant m','s.mer_id = m.mer_id')
            ->where($where)
            ->order($sort);
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}