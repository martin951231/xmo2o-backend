<?php
/**
 * 种草文章评论表model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:09
 */

namespace app\grow_grass\model\db;
use think\Model;
class GrowGrassArticleReply extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $page
     * @param $pageSize
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * 评论列表
     */
    public function getCommentList($page,$pageSize,$where,$field,$order){
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->join('grow_grass_article at', 'at.article_id = g.article_id')
            ->join('user u', 'g.uid = u.uid');
        $assign['count'] = $result->count();
        $assign['list'] = $result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $assign;
    }
}