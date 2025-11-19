<?php


namespace app\community\model\db;

use think\Model;

class MeterReadRecord extends Model
{
    /**
     * 抄表记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * 抄表记录列表
     * @author lijie
     * @date_time 2020/11/02
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
    public function getList($where,$field=true,$page=1,$limit=20,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->page($page,$limit)->select();
        return $data;
    }

    /**
     * 添加抄表记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $data
     * @return mixed
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 抄表记录数量
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @return mixed
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}