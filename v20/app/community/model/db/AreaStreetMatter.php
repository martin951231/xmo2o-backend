<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetMatter extends Model
{
    /**
     * 事项列表
     * @author lijie
     * @date_time 2020/09/21
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
    public function getLists($where,$field=true,$page=1,$limit=20,$order='is_hot,matter_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        return $data;
    }

    /**
     * 事项详情
     * @author lijie
     * @date_time 2020/09/21
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

    /**
     * 增加阅读量
     * @author lijie
     * @date_time 2020/09/21
     * @param $where
     * @return mixed
     */
    public function incReadNum($where)
    {
        $res = $this->where($where)->inc('read_sum',1)->update();
        return $res;
    }

    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return AreaStreetMatter
     * @author: weili
     * @datetime: 2020/11/2 16:21
     */
    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/2 16:21
     */
    public function addData($data)
    {
        $res = $this->insert($data);
        return $res;
    }
}