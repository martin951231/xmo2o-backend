<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetNews extends Model
{
    /**
     * 获取街道新闻分类下得新闻列表
     * @author lijie
     * @date_time 2020/09/09 11:27
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=20,$order='is_hot DESC,news_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        return $data;
    }

    /**
     * 获取街道最新要闻
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true,$order='news_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }
}