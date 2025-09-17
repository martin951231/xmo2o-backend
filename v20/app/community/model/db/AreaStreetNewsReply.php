<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetNewsReply extends Model
{
    /**
     * 获取新闻评论列表
     * @author lijie
     * @date_time 2020/09/09 11:30
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
    public function getLists($where,$field=true,$page=1,$limit=20,$order='r.pigcms_id DESC')
    {
        $data = $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page,$limit)
            ->select();
        return $data;
    }
}