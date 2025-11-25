<?php
/**
 * 首页推荐
 * User: 衡婷妹
 * Date: 2021/05/25 18:01
 */

namespace app\common\model\service;

use app\common\model\db\PlatRecommend;

class PlatRecommendService
{
    public $platRecommendModel = null;
    public function __construct()
    {
        $this->platRecommendModel = new PlatRecommend();
    }

    /**
     * 获取总数
     * @param array $where
     * @return int
     */
    public function getCount($where = []) {
        $result = $this->platRecommendModel->getCount($where);
        return $result ? $result : 0;
    }

    /**
     * 获取一条数据
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->platRecommendModel->getOne($where, $field);
        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        $start = ($page-1)*$limit;
        $result = $this->platRecommendModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }
}