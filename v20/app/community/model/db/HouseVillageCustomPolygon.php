<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageCustomPolygon extends Model
{
    /**
     * 获取网格员列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/12/19
     */
    public function getList($where, $field = true, $page = 1, $limit = 15, $order = 'id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->page($page)->limit($limit)->select();
        return $data;
    }

    /**
     * 获取网格员数量
     * @param $where
     * @return int
     * @author lijie
     * @date_time 2020/12/19
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 添加网格员
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/12/19
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 更新网格员
     * @param $where
     * @param $data
     * @return bool
     * @author lijie
     * @date_time 2020/12/19
     */
    public function saveOne($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 查看网格员详情
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where, $field = true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}