<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetMeetingLessonReply extends Model
{
    /**
     * 三会一课评论
     * @author lijie
     * @date_time 2020/09/17
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
     * @date_time 2020/09/17
     * @param $data
     * @return int|string
     */
    public function saveOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetMeetingLessonReply
     * @author: weili
     * @datetime: 2020/9/21 14:38
     */
    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/21 14:38
     */
    public function getCount($where)
    {
        $count = $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->where($where)->count();
        return $count;
    }
}