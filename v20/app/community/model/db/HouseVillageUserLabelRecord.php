<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageUserLabelRecord extends Model
{
    /**
     * 获取跟踪记录数量
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true,$page,$limit,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * 添加跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 修改跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res  = $this->where($where)->save($data);
        return $res;
    }

    /**
     *记录详情
     * @param $where
     * @param $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}