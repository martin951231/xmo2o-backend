<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetPartyBuildReply extends Model
{
    /**
     * 党建资讯评论列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
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

    /**
     * 添加评论
     * @author lijie
     * @date_time 2020/09/16
     * @param $data
     * @return int|string
     */
    public function saveOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 获取党建资讯评论数量
     * @author lijie
     * @date_time 2020/10/14
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 删除党内资讯评论
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function delOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 修改评论状态
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return bool
     */
    public function changOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}