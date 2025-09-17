<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetEventCategory extends Model
{
    /**
     * Notes: 获取单条信息
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/23 13:58
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 添加事件分类
     * @author lijie
     * @date_time 2021/02/22
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 修改事件分类
     * @author lijie
     * @date_time 2021/02/22
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 获取事件分类列表
     * @author lijie
     * @date_time 2020/02/22
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
    public function getList($where,$field=true,$page=1,$limit=15,$order='cat_id DESC')
    {
        $sql = $this->where($where)->field($field);
        if ($page) {
            $sql->page($page,$limit);
        }
        $data = $sql->order($order)->select();
        return $data;
    }

    /**
     * 获取事件分类数量
     * @author lijie
     * @date_time 2021/02/23
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

}