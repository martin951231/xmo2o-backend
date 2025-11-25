<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageNews extends Model
{
    /**
     * 获取新闻列表
     * @author lijie
     * @date_time 2020/08/17 17:33
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=10,$order='news_id DESC')
    {
        $data = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * 获取新闻详情
     * @author lijie
     * @date_time 2020/08/17 17:35
     * @param $where
     * @param $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field,$order='news_id ASC')
    {
        $data = $this->field($field)->where($where)->order($order)->find();
        return $data;
    }
}