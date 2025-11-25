<?php


namespace app\community\model\db;

use think\Model;

class HouseMeterWarn extends Model
{
    /**
     * 获取电表告警数量
     * @author lijie
     * @date_time 2021/05/20
     * @param array $where
     * @return int
     */
    public function getCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 设备告警列表
     * @author lijie
     * @date_time 2021/05/20
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        return $data;
    }

    /**
     * 添加数据
     * @author zhubaodi
     * @datetime 2021/5/22
     * @param array $data
     * @return integer
     **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
}