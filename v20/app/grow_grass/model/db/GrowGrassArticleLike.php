<?php
/**
 * 种草文章点赞表model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:08
 */

namespace app\grow_grass\model\db;
use think\Model;
class GrowGrassArticleLike extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取喜欢列表
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where='', $field = true, $order = true, $page = 0, $limit = 0)
    {
        $query = $this->field($field)
            ->alias('l')
            ->join('grow_grass_article a', 'a.article_id = l.article_id')
            ->join('user u', 'u.uid = a.uid')
            ->where($where)
            ->order($order);
        if ($limit) {
            $query->page($page, $limit);
        }
        $result = $query->select()->toArray();
        return $result;
    }
}