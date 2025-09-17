<?php


namespace app\community\model\db;
use think\Model;

class HouseVillageParkingGarage extends Model
{
    /**
     *获取车库列表
     * @author lijie
     * date_time 2020/07/16 13:25
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
    public function getLists($where, $field = true, $page = 0, $limit = 0, $order = 'garage_id DESC')
    {
        if (!$limit) {
            $data = $this->where($where)->field($field)->order($order)->select();
        } else {
            $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select();
        }
        return $data;
    }
    /**
     * 根据条件获取车库
     * @author lijie
     * @date_time 2020/07/17 14:01
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 修改车库信息
     * @author lijie
     * @date_time 2020/07/16 13:31
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
     * 添加车库
     * @author lijie
     * @date_time 2020/07/16 14:39
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data,true);
        return $res;
    }

    /**
     * 删库车库
     * @author lijie
     * @date_time 2020/07/16 13:28
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }


    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }


    public function getColumn($where,$field, $key = ''){
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}