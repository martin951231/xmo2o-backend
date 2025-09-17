<?php


namespace app\community\model\db;

use think\Model;

class VillageQywxMessageRecord extends Model
{
    /**
     * 添加群发记录
     * @author lijie
     * @date_time 2021/03/19
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 用户接受消息列表
     * @author lijie
     * @date_time 2021/03/22
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
    public function getList($where,$field=true,$page=1,$limit=10,$order='r.id DESC')
    {
        $data = $this->alias('r')
            ->leftJoin('house_contact_way_user u','u.customer_id = r.contact_user_id')
            ->leftJoin('house_worker w','u.wid = w.wid')
            ->where($where)
            ->field($field)
            ->page($page,$limit)
            ->select();
        return $data;
    }

    public function getJoinCount($where)
    {
        $count = $this->alias('r')
            ->leftJoin('house_contact_way_user u','u.customer_id = r.contact_user_id')
            ->leftJoin('house_worker w','u.wid = w.wid')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 用户接受消息数量
     * @author lijie
     * @date_time 2021/03/22
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 分组查询记录
     * @author lijie
     * @date_time 2021/04/02
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $group
     * @return mixed
     */
    public function getListByUserId($where,$page=1,$limit=10,$field=true,$group='user_id')
    {
        if($page)
            $data = $this->field($field)->where($where)->group($group)->page($page,$limit)->select();
        else
            $data = $this->field($field)->where($where)->group($group)->select();
        return $data;
    }
}