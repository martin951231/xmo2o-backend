<?php

namespace app\community\model\db;

use think\Model;

class HouseNewMeterDirector extends Model
{
    /**
     * 负责人列表
     * @author lijie
     * @date_time 2021/07/09
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
     * 负责人数量
     * @author lijie
     * @date_time 2021/07/09
     * @param array $where
     * @return int
     */
    public function getCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 添加负责人
     * @author lijie
     * @date_time 2021/07/09
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * 修改负责人信息
     * @author lijie
     * @date_time 2021/07/09
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 负责人详情
     * @author lijie
     * @date_time 2021/07/10
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    public function getMeterDirectorList($where = [], $field = true, $page = 0, $limit = 10, $order = 'id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }


    public function getMeterDirectorWork($where = [], $field = true,$order='d.id DESC')
    {
        $data = $this->alias('d')
            ->leftJoin('house_worker w', 'w.wid = d.worker_id and d.village_id=w.village_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        return $data;
    }


}