<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/9/28 14:23
 */

namespace app\community\model\db;

use think\Model;

class HouseNewAutoOrderLog extends Model
{
    /**
     * 添加
     * @author zhubaodi
     * @date_time 2021/9/28 14:23
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * 修改
     * @author zhubaodi
     * @date_time 2021/9/28 14:23
     * @param $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where,$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 获取详情
     * @author zhubaodi
     * @date_time 2021/9/28 14:23
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->order($order)->find();
        return $data;
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
     * @date_time 2021/9/28 14:23
     */
    public function getList($where = [], $field = true, $page = 0, $limit = 10, $order = 'id DESC')
    {
        if ($page)
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        else
            $data = $this->where($where)->field($field)->order($order)->select();

        return $data;
    }
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
}