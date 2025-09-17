<?php


namespace app\mall\model\db;

use think\Model;

class MallFullGiveLevel extends Model
{
    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        return $this->insertGetId($data);

    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return boolean
     */
    public function updateOne($data, $where)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * 删除数据
     * @param $where
     * @return boolean
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
    /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$fields='*',$order)
    {
        $result = $this->field($fields)
            ->where($where)->order($order)->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    public function getInfoList($where,$fields='*',$order)
    {
        $result = $this->field($fields)
            ->where($where)->order($order)->select()->toArray();
        return $result;
    }

}