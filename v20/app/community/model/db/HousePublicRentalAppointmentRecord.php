<?php

namespace app\community\model\db;

use think\Model;
class HousePublicRentalAppointmentRecord extends Model
{

    public function getOne($where,$field=true){
        return $this->field($field)->where($where)->find();
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function getCount($where) {
        $set_info = $this->where($where)->count();
        return $set_info;
    }

    /**
     * 获取列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2022/8/9 20:55
     */
    public function getList($where = [], $field = true, $page = 0, $limit = 10, $order = 'id DESC')
    {
        if ($page)
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();

        return $data;
    }

}