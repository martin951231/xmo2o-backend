<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageNewsReply extends Model
{
    /**
     * 获取新闻评论
     * @author lijie
     * @date_time 2020/08/17 17:38
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
    public function getLists($where,$field=true,$page=1,$limit=10,$order='r.pigcms_id DESC')
    {
        $data = $this->alias('r')
            ->leftJoin('user u','r.uid = u.uid')
            ->field($field)
            ->where($where)
            ->page($page,$limit)
            ->order($order)
            ->select();
        return $data;
    }

    /**
     * 删除评论
     * @author lijie
     * @date_time 2020/08/18 10:05
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * 评论前台是否展示
     * @author lijie
     * @date_time 2020/08/20 15:41
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}