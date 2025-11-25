<?php


namespace app\community\model\db;

use think\Model;

class HouseNewSelectProjectRecord extends Model
{
    /**
     * 选中的项目订单列表
     * @author lijie
     * @date_time 2021/07/31
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$order='record_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 添加选中项目订单
     * @author lijie
     * @date_time 2021/07/31
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $res = $this->insert($data);
        return $res;
    }
    /**
     * 删除选中的项目订单
     * @author lijie
     * @date_time 2021/07/31
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    public function del($where=[])
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}